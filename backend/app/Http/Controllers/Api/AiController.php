<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\OpenAIService;
use App\Services\ResumeParserService;
use App\Services\WebSearchService;
use App\Models\Job;
use App\Models\ChatMessage;
use App\Models\UserProfile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class AiController extends Controller
{
    public function resumeChat(Request $request)
    {
        $request->validate([
            'resume' => 'required|file|mimes:pdf,doc,docx|max:5120', // 5MB max
        ]);

        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        try {
            $file = $request->file('resume');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $filePath = $file->storeAs('resumes', $fileName, 'public');

            // Parse resume text (guard and return a helpful message if parsing fails)
            $parser = new ResumeParserService();
            try {
                $resumeText = $parser->parseResume(storage_path('app/public/' . $filePath));
            } catch (\Throwable $e) {
                Log::error('Resume chat analysis failed', ['error' => $e->getMessage()]);
                return response()->json(['message' => 'Unsupported file type or could not parse resume. Please upload a PDF or Word document.'], 400);
            }

            // Analyze resume with OpenAI if available; on failure, fall back to a safe default analysis
            $analysis = null;
            $openai = null;
            try {
                if (is_string(config('services.openai.api_key')) && trim(config('services.openai.api_key')) !== '') {
                    $openai = new OpenAIService();
                    $analysis = $openai->analyzeResumeComprehensively($resumeText);
                } else {
                    Log::info('OpenAI API key not configured; skipping AI analysis', ['user_id' => $user->id]);
                }
            } catch (\Throwable $e) {
                // Log the error but continue with a sensible fallback so the endpoint doesn't return 500
                Log::error('Resume analysis failed', ['error' => $e->getMessage(), 'user_id' => $user->id]);
                $analysis = [
                    'skills' => [],
                    'experience_years' => 'Unknown',
                    'education' => [],
                    'certifications' => [],
                    'languages' => [],
                    'summary' => 'AI analysis unavailable at the moment',
                    'strengths' => [],
                    'experience_level' => 'entry',
                    'key_achievements' => []
                ];
            }

            // Update user profile with analysis
            $profile = $user->profile ?? new UserProfile();
            if (!$profile->user_id) {
                $profile->user_id = $user->id;
            }
            $profile->fill([
                'skills' => $analysis['skills'] ?? [],
                'experience_level' => $analysis['experience_level'] ?? 'entry',
                'education_attainment' => $this->mapEducationLevel($analysis['education'] ?? []),
                'extracted_experience' => $analysis['experience_years'] ?? '',
                'extracted_education' => json_encode($analysis['education'] ?? []),
                'extracted_certifications' => json_encode($analysis['certifications'] ?? []),
                'extracted_languages' => json_encode($analysis['languages'] ?? []),
                'resume_summary' => $analysis['summary'] ?? '',
                'ai_analysis' => $analysis,
                'last_ai_analysis' => now(),
            ]);

            // Store resume info
            $resumes = $profile->resumes ?? [];
            $resumes[] = [
                'name' => $file->getClientOriginalName(),
                'url' => $filePath,
                'uploaded_at' => now()->toISOString(),
            ];
            $profile->resumes = $resumes;

            $profile->save();

            // Generate job suggestions based on extracted skills
            $skills = $analysis['skills'] ?? [];
            $experience = $analysis['experience_years'] ?? '';
            $suggestions = [];
            if ($openai) {
                try {
                    $suggestions = $openai->suggestJobsForSkills($skills, $experience, 5);
                } catch (\Throwable $e) {
                    Log::error('Job suggestion via OpenAI failed', ['error' => $e->getMessage(), 'user_id' => $user->id]);
                    $suggestions = [];
                }
            }

            // Also include local job matching as fallback
            $localSuggestions = [];
            if (!empty($skills)) {
                $jobs = Job::where('status', 'approved')->get();
                $skillsLower = array_map(fn($s) => strtolower($s), $skills);

                $scored = [];
                foreach ($jobs as $job) {
                    $text = strtolower($job->title . ' ' . ($job->description ?? '') . ' ' . ($job->requirements ? json_encode($job->requirements) : ''));
                    $matchCount = 0;
                    foreach ($skillsLower as $skill) {
                        if ($skill === '') continue;
                        if (str_contains($text, $skill)) $matchCount++;
                    }
                    $confidence = $matchCount > 0 ? min(100, (int) floor(($matchCount / max(1, count($skillsLower))) * 100)) : 0;

                    if ($confidence > 0) {
                        $scored[] = [
                            'job_id' => $job->id,
                            'title' => $job->title,
                            'description' => strlen($job->description ?? '') > 200 ? substr($job->description, 0, 197) . '...' : ($job->description ?? ''),
                            'recommended_level' => $job->type ?? 'mid',
                            'confidence' => $confidence,
                        ];
                    }
                }

                usort($scored, fn($a, $b) => $b['confidence'] <=> $a['confidence']);
                $localSuggestions = array_slice($scored, 0, 3);
            }

            // Combine OpenAI and local suggestions
            $allSuggestions = array_merge($suggestions, $localSuggestions);
            $uniqueSuggestions = [];
            $seenTitles = [];

            foreach ($allSuggestions as $suggestion) {
                $title = $suggestion['title'] ?? '';
                if (!in_array($title, $seenTitles)) {
                    $uniqueSuggestions[] = $suggestion;
                    $seenTitles[] = $title;
                }
            }

            $finalSuggestions = array_slice($uniqueSuggestions, 0, 5);

            return response()->json([
                'analysis' => $analysis,
                'suggestions' => $finalSuggestions,
                'message' => 'Resume analyzed successfully! Here are job suggestions based on your experience.'
            ]);

        } catch (\Exception $e) {
            Log::error('Resume analysis failed', ['error' => $e->getMessage(), 'user_id' => $user->id]);
            return response()->json(['message' => 'Failed to analyze resume. Please try again.'], 500);
        }
    }

    private function mapEducationLevel($education)
    {
        if (empty($education)) return '';

        $educationString = strtolower(implode(' ', $education));

        if (str_contains($educationString, 'phd') || str_contains($educationString, 'doctorate')) {
            return 'phd';
        } elseif (str_contains($educationString, 'master') || str_contains($educationString, 'ms') || str_contains($educationString, 'ma')) {
            return 'master';
        } elseif (str_contains($educationString, 'bachelor') || str_contains($educationString, 'bs') || str_contains($educationString, 'ba')) {
            return 'bachelor';
        } elseif (str_contains($educationString, 'associate')) {
            return 'associate';
        } elseif (str_contains($educationString, 'high school') || str_contains($educationString, 'diploma')) {
            return 'high_school';
        }

        return '';
    }

    public function skillChat(Request $request)
    {
        $request->validate([
            'skills' => 'required|array',
            'experience' => 'nullable|string',
            'limit' => 'nullable|integer|min:1|max:10'
        ]);

        $user = Auth::user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $skills = $request->input('skills');
        $experience = $request->input('experience', '');
        $limit = $request->input('limit', 5);

        // Use local job matching to suggest jobs from existing database
        $jobs = Job::where('status', 'approved')->get();
        $skillsLower = array_map(fn($s) => strtolower($s), $skills);

        $scored = [];
        foreach ($jobs as $job) {
            $text = strtolower($job->title . ' ' . ($job->description ?? '') . ' ' . ($job->requirements ? json_encode($job->requirements) : ''));
            $matchCount = 0;
            foreach ($skillsLower as $skill) {
                if ($skill === '') continue;
                if (str_contains($text, $skill)) $matchCount++;
            }
            $confidence = $matchCount > 0 ? min(100, (int) floor(($matchCount / max(1, count($skillsLower))) * 100)) : 0;

            $scored[] = [
                'job_id' => $job->id,
                'title' => $job->title,
                'description' => strlen($job->description ?? '') > 200 ? substr($job->description, 0, 197) . '...' : ($job->description ?? ''),
                'recommended_level' => $job->type ?? 'mid',
                'confidence' => $confidence,
            ];
        }

        // Sort by confidence descending, filter to only include jobs with confidence > 0
        usort($scored, fn($a, $b) => $b['confidence'] <=> $a['confidence']);
        $filtered = array_filter($scored, fn($job) => $job['confidence'] > 0);
        $suggestions = array_slice($filtered, 0, $limit);

        // If no matches, return empty array (will be handled in frontend)
        if (empty($suggestions)) {
            $suggestions = [];
        }

        return response()->json(['suggestions' => $suggestions]);
    }

    public function chat(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:1000'
        ]);

        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $message = $request->input('message');

        // Save user message
        ChatMessage::create([
            'user_id' => $user->id,
            'role' => 'user',
            'message' => $message,
        ]);

        // Check if user is employer and has ongoing job creation session
        if ($user->user_type === 'employer') {
            $sessionKey = "job_creation_{$user->id}";
            $jobDraft = Cache::get($sessionKey, []);
            if (!empty($jobDraft)) {
                // Check if session has expired (10 minutes)
                if (isset($jobDraft['started_at']) && now()->diffInMinutes($jobDraft['started_at']) > 10) {
                    Cache::forget($sessionKey);
                    $jobDraft = [];
                } else {
                    return $this->processJobCreationStep($message, $user, $jobDraft, $sessionKey);
                }
            }
        }

        // Check if user is employer and message contains "create job for me"
        if ($user->user_type === 'employer' && $this->isJobCreationRequest($message)) {
            return $this->handleJobCreation($message, $user);
        }

        // Check if message requires web search
        $requiresSearch = false;
        $searchResults = [];
        try {
            $webSearchService = new WebSearchService();
            $requiresSearch = $webSearchService->requiresWebSearch($message);
            if ($requiresSearch) {
                $searchResults = $webSearchService->search($message, 5);
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::info('Web search not available', ['error' => $e->getMessage()]);
        }

        // If OpenAI key is not configured, provide a simple fallback response
        if (!is_string(config('services.openai.api_key')) || trim(config('services.openai.api_key')) === '') {
            Log::info('OpenAI API key not configured - using simple fallback for chat', ['user_id' => $user->id]);

            $response = $this->generateFallbackResponse($message, $user);

            // Save bot response
            ChatMessage::create([
                'user_id' => $user->id,
                'role' => 'bot',
                'message' => $response,
            ]);

            return response()->json(['response' => $response]);
        }

        try {
            $openai = new OpenAIService();

            // Build context from user's profile if available
            $context = '';
            if ($user->profile) {
                $profile = $user->profile;
                $context = "User profile data: ";
                $context .= "ID: " . ($profile->id ?? '') . ". ";
                $context .= "Bio: " . ($profile->bio ?? '') . ". ";
                $context .= "Skills: " . (is_array($profile->skills) ? implode(', ', $profile->skills) : ($profile->skills ?? '')) . ". ";
                $context .= "Experience level: " . ($profile->experience_level ?? '') . ". ";
                $context .= "Education attainment: " . ($profile->education_attainment ?? '') . ". ";
                $context .= "Portfolio URL: " . ($profile->portfolio_url ?? '') . ". ";
                $context .= "Resume URL: " . ($profile->resume_url ?? '') . ". ";
                $context .= "Resumes: " . (is_array($profile->resumes) ? json_encode($profile->resumes) : ($profile->resumes ?? '')) . ". ";
                $context .= "AI analysis: " . (is_array($profile->ai_analysis) ? json_encode($profile->ai_analysis) : ($profile->ai_analysis ?? '')) . ". ";
                $context .= "Extracted experience: " . ($profile->extracted_experience ?? '') . ". ";
                $context .= "Extracted education: " . ($profile->extracted_education ?? '') . ". ";
                $context .= "Extracted certifications: " . ($profile->extracted_certifications ?? '') . ". ";
                $context .= "Extracted languages: " . ($profile->extracted_languages ?? '') . ". ";
                $context .= "Resume summary: " . ($profile->resume_summary ?? '') . ". ";
                $context .= "Last AI analysis: " . ($profile->last_ai_analysis ?? '') . ". ";
            }

            // Different system prompt based on user type
            if ($user->user_type === 'employer') {
                $systemPrompt = "You are an AI assistant for employers on a job recommendation website. Help employers with job posting creation, candidate management, hiring strategies, and company branding. Common topics include: creating effective job descriptions, reviewing applications, interview scheduling, hiring best practices, and managing job listings. Be professional, helpful, and focused on employer needs. Provide actionable advice for successful hiring.";
            } else {
                $systemPrompt = "You are an AI career advisor chatbot for a job recommendation website. Help users with job search & matching, application help, company information, interview assistance, status updates, and career advice. Common topics include: finding jobs by location/skill/type, applying for jobs, uploading resumes, cover letter tips, company details, interview preparation, application status, and career improvement. Be friendly, helpful, and professional. Use the provided context about the user when relevant. If asked about specific jobs, reference available job listings. Provide actionable advice and keep responses concise but informative. Always encourage next steps and offer to help further.";
            }

            // Fetch recent chat history
            $chatHistory = ChatMessage::where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->limit(5) // Get last 5 messages for context
                ->get()
                ->reverse(); // Reverse to get chronological order

            $messagesForOpenAI = [['role' => 'system', 'content' => $systemPrompt . ' ' . $context]];

            foreach ($chatHistory as $chatMessage) {
                $messagesForOpenAI[] = [
                    'role' => $chatMessage->role,
                    'content' => $chatMessage->message
                ];
            }

            // Add the current user message
            $messagesForOpenAI[] = ['role' => 'user', 'content' => $message];

            if ($requiresSearch && !empty($searchResults)) {
                $aiResponse = $openai->generateSearchEnhancedResponse($message, $searchResults, $user->user_type);
            } else {
                $response = $openai->getClient()->chat()->create([
                    'model' => 'gpt-3.5-turbo',
                    'messages' => $messagesForOpenAI,
                    'max_tokens' => 500,
                ]);
                $aiResponse = $response->choices[0]->message->content;
            }

            // Save bot response
            ChatMessage::create([
                'user_id' => $user->id,
                'role' => 'bot',
                'message' => $aiResponse,
            ]);

            return response()->json(['response' => $aiResponse]);
        } catch (\Throwable $e) {
            Log::error('AI chat failed', ['error' => $e->getMessage()]);

            // Fallback response
            $response = $this->generateFallbackResponse($message, $user);

            // Save bot response
            ChatMessage::create([
                'user_id' => $user->id,
                'role' => 'bot',
                'message' => $response,
            ]);

            return response()->json(['response' => $response]);
        }
    }

    public function getChatHistory(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $messages = ChatMessage::where('user_id', $user->id)
            ->orderBy('created_at', 'asc')
            ->limit(100)
            ->get(['role', 'message', 'created_at']);

        return response()->json(['messages' => $messages]);
    }

    private function generateFallbackResponse($message, $user)
    {
        $messageLower = strtolower($message);
        $isEmployer = $user->user_type === 'employer';

        // Job Search & Matching - Different for employers vs job seekers
        if (str_contains($messageLower, 'what jobs') || str_contains($messageLower, 'available for me') ||
            str_contains($messageLower, 'show me') && str_contains($messageLower, 'jobs') ||
            str_contains($messageLower, 'part-time') || str_contains($messageLower, 'remote') ||
            str_contains($messageLower, 'near') || str_contains($messageLower, 'location') ||
            str_contains($messageLower, 'jobs')) {

            if ($isEmployer) {
                return "As an employer, you can create job postings to attract candidates. Would you like me to help you create a job posting? Just tell me details like the position, requirements, and salary range.";
            }

            // Job seeker logic
            $profile = $user->profile;
            $hasSkills = $profile && is_array($profile->skills) && count($profile->skills) > 0;
            if ($hasSkills) {
                $skills = $profile->skills;
                // Use local job matching to suggest jobs
                $jobs = Job::where('status', 'approved')->get();
                $skillsLower = array_map(fn($s) => strtolower($s), $skills);
                $scored = [];
                foreach ($jobs as $job) {
                    $text = strtolower($job->title . ' ' . ($job->description ?? '') . ' ' . ($job->requirements ? json_encode($job->requirements) : ''));
                    $matchCount = 0;
                    foreach ($skillsLower as $skill) {
                        if ($skill === '') continue;
                        if (str_contains($text, $skill)) $matchCount++;
                    }
                    $confidence = $matchCount > 0 ? min(100, (int) floor(($matchCount / max(1, count($skillsLower))) * 100)) : 0;
                    if ($confidence > 0) {
                        $scored[] = [
                            'job_id' => $job->id,
                            'title' => $job->title,
                            'description' => strlen($job->description ?? '') > 100 ? substr($job->description, 0, 97) . '...' : ($job->description ?? ''),
                            'confidence' => $confidence,
                        ];
                    }
                }
                usort($scored, fn($a, $b) => $b['confidence'] <=> $a['confidence']);
                $suggestions = array_slice($scored, 0, 3);
                if (!empty($suggestions)) {
                    $response = "Based on your skills (" . implode(', ', $skills) . "), here are some job suggestions:\n\n";
                    foreach ($suggestions as $job) {
                        $response .= "• <a href=\"/job/" . $job['job_id'] . "\">" . $job['title'] . "</a> (Match: " . $job['confidence'] . "%)\n  " . $job['description'] . "\n\n";
                    }
                    $response .= "Would you like me to help you apply to any of these jobs or provide more details?";
                    return $response;
                } else {
                    return "I couldn't find any strong matches for your current skills. Try updating your profile with more specific skills or upload your resume for better suggestions!";
                }
            } else {
                return "To get personalized job suggestions, please update your profile with your skills or upload your resume. You can do this in your profile section!";
            }
        }
        // Application Help
        elseif (str_contains($messageLower, 'how do i apply') || str_contains($messageLower, 'upload') && str_contains($messageLower, 'résumé') ||
                str_contains($messageLower, 'cover letter') || str_contains($messageLower, 'application')) {
            return "To apply for jobs, visit our job listings page and click 'Apply' on any position that interests you. You can upload your resume and fill out the application form. For cover letters, highlight why you're a great fit for the role!";
        }
        // Company Information
        elseif (str_contains($messageLower, 'work at') || str_contains($messageLower, 'company') ||
                str_contains($messageLower, 'benefits') || str_contains($messageLower, 'work hours') ||
                str_contains($messageLower, 'still open') || str_contains($messageLower, 'position')) {
            return "For company information and job details, please check the specific job posting on our listings page. Each job includes information about requirements, benefits, and application status.";
        }
        // Interview Assistance
        elseif (str_contains($messageLower, 'interview') || str_contains($messageLower, 'prepare for') ||
                str_contains($messageLower, 'questions might') || str_contains($messageLower, 'they ask')) {
            return "Interview prep is key! Practice common questions like 'Tell me about yourself' and 'Why do you want this job.' Research the company and prepare questions for them. I can help you practice specific scenarios!";
        }
        // Status Updates
        elseif (str_contains($messageLower, 'application been received') || str_contains($messageLower, 'next step') ||
                str_contains($messageLower, 'status') || str_contains($messageLower, 'update')) {
            return "You can check your application status in your dashboard under 'My Applications'. We'll notify you of any updates via email or your notifications panel.";
        }
        // Resume Tips
        elseif (str_contains($messageLower, 'resume tip') || str_contains($messageLower, 'improve my resume') ||
                str_contains($messageLower, 'improve my résumé') || str_contains($messageLower, 'resume advice') ||
                str_contains($messageLower, 'cv tip') || str_contains($messageLower, 'improve my cv')) {
            return "Here are some key resume tips:\n\n" .
                   "1. **Keep it concise**: Aim for 1-2 pages maximum\n" .
                   "2. **Use action verbs**: Start bullet points with words like 'Developed', 'Managed', 'Led'\n" .
                   "3. **Quantify achievements**: Include numbers and metrics (e.g., 'Increased sales by 30%')\n" .
                   "4. **Tailor to each job**: Customize your resume for each position\n" .
                   "5. **Include keywords**: Use terms from the job description\n" .
                   "6. **Proofread carefully**: No typos or grammatical errors\n" .
                   "7. **Use a clean format**: Easy to read with clear sections\n" .
                   "8. **Add relevant skills**: Include both technical and soft skills\n\n" .
                   "Would you like help with any specific section of your resume?";
        }
        // Career Advice
        elseif (str_contains($messageLower, 'which jobs fit') || str_contains($messageLower, 'courses') ||
                str_contains($messageLower, 'get hired faster') || str_contains($messageLower, 'career') ||
                str_contains($messageLower, 'advice')) {
            if ($isEmployer) {
                return "As an employer, I can help you with hiring strategies, creating effective job descriptions, and managing your recruitment process. What specific aspect of hiring would you like assistance with?";
            }
            return "For career advice, share your skills below to get job suggestions! To improve your resume, focus on quantifiable achievements and relevant skills. Consider online courses on platforms like Coursera or Udemy to boost your employability.";
        }
        // General fallback
        else {
            if ($isEmployer) {
                return "I'm here to help employers with job posting creation, candidate management, hiring strategies, and company branding. What can I assist you with today?";
            }
            return "I'm here to help with your job search! You can ask me about finding jobs, applying for positions, interview preparation, resume tips, career advice, or check our job listings page. What would you like to know?";
        }
    }

    private function isJobCreationRequest($message)
{
    $messageLower = strtolower($message);
    $triggers = [
        // Direct phrases
        'create job', 'create a job', 'create job for me', 'create a job post',
        'create a job posting', 'create a job listing', 'make a job post',
        'make a job', 'make job posting', 'generate job', 'generate a job post',
        'generate a job listing', 'post a job', 'post new job', 'add a job',
        'add job posting', 'add new job',

        // Conversational or indirect phrases
        'help me create a job', 'i want to create a job', 'i need to post a job',
        'can you create a job for me', 'can you make a job posting',
        'please create a job post', 'i want to post a job',
        'i want to add a job posting', 'help me make a job post',
        'let’s make a job post', 'i need to hire someone', 'i want to hire someone',
        'create a new job for hiring', 'can you help me post a job',
        'i’d like to post a job', 'start a new job post', 'publish a job',

        // Short command style
        'new job', 'new job post', 'job post', 'job posting', 'post job',
        'add job', 'hiring post',

        // Contextual or job-type specific
        'create a job for', 'generate job ad for', 'create job description for',
    ];

        foreach ($triggers as $trigger) {
            if (str_contains($messageLower, $trigger)) {
                return true;
            }
        }

        return false;
    }

    private function handleJobCreation($message, $user)
    {
        // Parse the message for job details
        $parsedDetails = $this->parseJobDetails($message);

        // If we have enough details (title, location, salary), create the job directly
        if (!empty($parsedDetails['job']) && !empty($parsedDetails['location']) && !empty($parsedDetails['salary'])) {
            return $this->createJobDirectly($parsedDetails, $user);
        }

        // Check if user already has a job creation session in progress
        $sessionKey = "job_creation_{$user->id}";
        $jobDraft = Cache::get($sessionKey, []);

        // If no session exists, start new job creation flow
        if (empty($jobDraft)) {
            $jobDraft = [
                'step' => 'title',
                'data' => [],
                'started_at' => now()
            ];
            Cache::put($sessionKey, $jobDraft, 600); // 10 minutes

            $response = "Great! Let's create a job posting together. First, what's the job title? (e.g., Software Developer, Marketing Manager, Teacher, etc.)";

            // Save bot response
            ChatMessage::create([
                'user_id' => $user->id,
                'role' => 'bot',
                'message' => $response,
            ]);

            return response()->json(['response' => $response]);
        }

        // Handle ongoing job creation flow
        return $this->processJobCreationStep($message, $user, $jobDraft, $sessionKey);
    }

    private function processJobCreationStep($message, $user, $jobDraft, $sessionKey)
    {
        // If user wants to create a new job, restart the session
        if ($this->isJobCreationRequest($message)) {
            $jobDraft = [
                'step' => 'title',
                'data' => [],
                'started_at' => now()
            ];
            Cache::put($sessionKey, $jobDraft, 600); // 10 minutes

            $response = "Great! Let's create a job posting together. First, what's the job title? (e.g., Software Developer, Marketing Manager, Teacher, etc.)";

            // Save bot response
            ChatMessage::create([
                'user_id' => $user->id,
                'role' => 'bot',
                'message' => $response,
            ]);

            return response()->json(['response' => $response]);
        }

        $currentStep = $jobDraft['step'];
        $data = $jobDraft['data'];

        switch ($currentStep) {
            case 'title':
                $data['title'] = trim($message);
                $jobDraft['step'] = 'location';
                $jobDraft['data'] = $data;
                Cache::put($sessionKey, $jobDraft, 600); // 10 minutes
                $response = "Got it! Job title: {$data['title']}\n\nNext, where is this position located? (e.g., Metro Manila, Makati City, Quezon City, Remote)";
                break;

            case 'location':
                $data['location'] = trim($message);
                $jobDraft['step'] = 'type';
                $jobDraft['data'] = $data;
                Cache::put($sessionKey, $jobDraft, 600); // 10 minutes

                $response = "Location set to: {$data['location']}\n\nWhat type of employment is this? (Full-time, Part-time, Contract, Freelance)";

                break;

            case 'type':
                $type = strtolower(trim($message));
                $validTypes = ['full-time', 'part-time', 'contract', 'freelance'];
                if (!in_array($type, $validTypes)) {
                    $response = "Please choose from: Full-time, Part-time, Contract, or Freelance.";
                    break;
                }
                $data['type'] = $type;
                $jobDraft['step'] = 'summary';
                $jobDraft['data'] = $data;
                Cache::put($sessionKey, $jobDraft, 600); // 10 minutes

                $response = "Job type: {$data['type']}\n\nNow, provide a brief job summary (1-2 sentences describing the role).";

                break;

            case 'summary':
                $data['summary'] = trim($message);
                $jobDraft['step'] = 'description';
                $jobDraft['data'] = $data;
                Cache::put($sessionKey, $jobDraft, 600); // 10 minutes

                $response = "Summary added!\n\nPlease provide a detailed job description including duties and responsibilities.";

                break;

            case 'description':
                $data['description'] = trim($message);
                $jobDraft['step'] = 'salary';
                $jobDraft['data'] = $data;
                Cache::put($sessionKey, $jobDraft, 600); // 10 minutes

                $response = "Description added!\n\nWhat's the salary range or compensation? (e.g., $50,000 - $80,000, Competitive, DOE - Daily Rate)";

                break;

            case 'salary':
                $data['salary'] = trim($message);
                $jobDraft['step'] = 'review';
                $jobDraft['data'] = $data;
                Cache::put($sessionKey, $jobDraft, 600); // 10 minutes

                $response = $this->generateJobDraftPreview($data);

                break;

            case 'review':
                $messageLower = strtolower(trim($message));
                if (str_contains($messageLower, 'approve') || str_contains($messageLower, 'post') || str_contains($messageLower, 'publish')) {
                    return $this->finalizeJobPosting($user, $data, $sessionKey);
                } elseif (str_contains($messageLower, 'edit') || str_contains($messageLower, 'change')) {
                    $jobDraft['step'] = 'edit_choice';
                    Cache::put($sessionKey, $jobDraft, 600); // 10 minutes
                    $response = "Which part would you like to edit? Reply with: title, location, type, summary, description, or salary.";
                } elseif (str_contains($messageLower, 'cancel job') || str_contains($messageLower, 'stop') || str_contains($messageLower, 'stop creating job') || str_contains($messageLower, 'cancel creating job')) {
                    Cache::forget($sessionKey);
                    // Start over with new job creation
                    $jobDraft = [
                        'step' => 'title',
                        'data' => [],
                        'started_at' => now()
                    ];
                    Cache::put($sessionKey, $jobDraft, 600); // 10 minutes
                    $response = "Job creation cancelled. Let's start over!\n\nWhat's the job title? (e.g., Software Developer, Marketing Manager, Teacher, etc.)";
                } else {
                    $response = "Please choose: 'Approve and post', 'Edit', or 'Cancel'.";
                }
                break;

            case 'edit_choice':
                $field = strtolower(trim($message));
                $validFields = ['title', 'location', 'type', 'summary', 'description', 'salary'];
                if (!in_array($field, $validFields)) {
                    $response = "Please choose from: title, location, type, summary, description, or salary.";
                    break;
                }
                $jobDraft['step'] = 'editing_' . $field;
                Cache::put($sessionKey, $jobDraft, 600); // 10 minutes
                $response = "Current {$field}: " . ($data[$field] ?? 'Not set') . "\n\nWhat's the new {$field}?";

                break;

            default:
                // Handle editing specific fields
                if (str_starts_with($currentStep, 'editing_')) {
                    $field = str_replace('editing_', '', $currentStep);
                    $data[$field] = trim($message);
                    $jobDraft['step'] = 'review';
                    $jobDraft['data'] = $data;
                    Cache::put($sessionKey, $jobDraft, 600); // 10 minutes
                    $response = "Updated! Here's the revised draft:\n\n" . $this->generateJobDraftPreview($data);
                } else {
                    $response = "I'm not sure what you mean. Let's continue with the job creation.";
                }
                break;
        }

        // Save bot response
        ChatMessage::create([
            'user_id' => $user->id,
            'role' => 'bot',
            'message' => $response,
        ]);

        return response()->json(['response' => $response]);
    }

    private function generateJobDraftPreview($data)
    {
        $preview = "**Job Posting Draft**\n\n";
        $preview .= "**Title:** {$data['title']}\n";
        $preview .= "**Location:** {$data['location']}\n";
        $preview .= "**Type:** {$data['type']}\n";
        $preview .= "**Summary:** {$data['summary']}\n";
        $preview .= "**Description:** {$data['description']}\n";
        $preview .= "**Salary:** {$data['salary']}\n\n";
        $preview .= "Do you want to:\n✅ **Approve and post**\n✏️ **Edit** (tell me what to change)\n❌ **Cancel**";

        return $preview;
    }

    private function finalizeJobPosting($user, $data, $sessionKey)
    {
        try {
            // Create the job with pending_approval status
            $job = Job::create([
                'title' => $data['title'],
                'location' => $data['location'],
                'type' => $data['type'],
                'summary' => $data['summary'],
                'description' => $data['description'],
                'salary' => is_numeric($data['salary']) ? (float) $data['salary'] : null,
                'company' => $user->name ?? 'Company',
                'user_id' => $user->id,
                'status' => 'pending_approval',
                'requirements' => [],
            ]);

            // Notify admin of new job awaiting approval
            $admins = \App\Models\User::where('user_type', 'admin')->get();
            foreach ($admins as $admin) {
                \App\Models\Notification::create([
                    'user_id' => $admin->id,
                    'type' => 'job_awaiting_approval',
                    'title' => 'New Job Awaiting Approval',
                    'message' => "A new job post from {$user->name} is awaiting approval.",
                    'data' => [
                        'job_id' => $job->id,
                        'job_title' => $job->title,
                        'employer_name' => $user->name,
                    ]
                ]);
            }

            // Notify employer that job is pending approval
            \App\Models\Notification::create([
                'user_id' => $user->id,
                'type' => 'job_pending_approval',
                'title' => 'Job Submitted for Approval',
                'message' => "Your job '{$data['title']}' has been submitted and is awaiting admin approval.",
                'data' => [
                    'job_id' => $job->id,
                    'job_title' => $job->title,
                ]
            ]);

            // Clear the session
            Cache::forget($sessionKey);

            $response = "🎉 Job submitted successfully! Your job '{$data['title']}' is now awaiting admin approval.\n\n";
            $response .= "You'll be notified once it's approved. You can view and manage this job in your employer dashboard under 'My Job Posts'.";

            // Save bot response
            ChatMessage::create([
                'user_id' => $user->id,
                'role' => 'bot',
                'message' => $response,
            ]);

            return response()->json(['response' => $response]);

        } catch (\Exception $e) {
            Log::error('Job creation failed', ['error' => $e->getMessage(), 'user_id' => $user->id]);

            $response = "Sorry, there was an error posting your job. Please try again or contact support.";

            // Save bot response
            ChatMessage::create([
                'user_id' => $user->id,
                'role' => 'bot',
                'message' => $response,
            ]);

            return response()->json(['response' => $response]);
        }
    }

    private function parseJobDetails($message)
    {
        $details = [];
        $messageLower = strtolower($message);

        // Handle comma-separated format like "create a job: teacher, deped, remote, 1000"
        if (preg_match('/(?:job|position|role)(?:\s+of)?[\s:]+(.+)/i', $message, $matches)) {
            $parts = array_map('trim', explode(',', $matches[1]));
            if (count($parts) >= 4) {
                $details['job'] = $parts[0];
                $details['company'] = $parts[1];
                $details['location'] = $parts[2];
                $details['salary'] = $parts[3];
                // If more parts, check for type
                if (count($parts) > 4) {
                    $type = strtolower($parts[4]);
                    if (in_array($type, ['full-time', 'part-time', 'contract', 'freelance'])) {
                        $details['type'] = $type;
                    }
                }
                return $details; // Return early if parsed successfully
            }
        }

        // Fallback to individual regex patterns
        // Extract job title - handle "create a job:" or similar patterns
        if (preg_match('/(?:job|position|role)(?:\s+of)?[\s:]+([^,\n]+)/i', $message, $matches)) {
            $details['job'] = trim($matches[1]);
        }

        // Extract company
        if (preg_match('/(?:company|at)\s+([^,\n]+)/i', $message, $matches)) {
            $details['company'] = trim($matches[1]);
        }

        // Extract location - handle "located in" or "in"
        if (preg_match('/(?:location|located\s+in|in)\s+([^,\n]+)/i', $message, $matches)) {
            $details['location'] = trim($matches[1]);
        }

        // Extract type
        if (preg_match('/(?:type|employment)\s+(full-time|part-time|contract|freelance|remote|on-site)/i', $message, $matches)) {
            $details['type'] = trim($matches[1]);
        }

        // Extract salary
        if (preg_match('/(?:salary|pay|compensation)\s+(?:of\s+)?([^\n]+)/i', $message, $matches)) {
            $details['salary'] = trim($matches[1]);
        }

        return $details;
    }

    private function createJobDirectly($parsedDetails, $user)
    {
        try {
            // Create the job with pending_approval status
            $job = Job::create([
                'title' => $parsedDetails['job'],
                'location' => $parsedDetails['location'],
                'type' => $parsedDetails['type'] ?? 'full-time',
                'summary' => 'Job created via AI chat',
                'description' => 'Job description to be updated by employer.',
                'salary' => is_numeric($parsedDetails['salary']) ? (float) $parsedDetails['salary'] : null,
                'company' => $parsedDetails['company'] ?? $user->name ?? 'Company',
                'user_id' => $user->id,
                'status' => 'pending_approval',
                'requirements' => [],
            ]);

            // Notify admin of new job awaiting approval
            $admins = \App\Models\User::where('user_type', 'admin')->get();
            foreach ($admins as $admin) {
                \App\Models\Notification::create([
                    'user_id' => $admin->id,
                    'type' => 'job_awaiting_approval',
                    'title' => 'New Job Awaiting Approval',
                    'message' => "A new job post from {$user->name} is awaiting approval.",
                    'data' => [
                        'job_id' => $job->id,
                        'job_title' => $job->title,
                        'employer_name' => $user->name,
                    ]
                ]);
            }

            // Notify employer that job is pending approval
            \App\Models\Notification::create([
                'user_id' => $user->id,
                'type' => 'job_pending_approval',
                'title' => 'Job Submitted for Approval',
                'message' => "Your job '{$parsedDetails['job']}' has been submitted and is awaiting admin approval.",
                'data' => [
                    'job_id' => $job->id,
                    'job_title' => $job->title,
                ]
            ]);

            $response = "🎉 Job submitted successfully! Your job '{$parsedDetails['job']}' is now awaiting admin approval.\n\n";
            $response .= "You'll be notified once it's approved. You can view and manage this job in your employer dashboard under 'My Job Posts'.";

            // Save bot response
            ChatMessage::create([
                'user_id' => $user->id,
                'role' => 'bot',
                'message' => $response,
            ]);

            return response()->json(['response' => $response]);

        } catch (\Exception $e) {
            Log::error('Direct job creation failed', ['error' => $e->getMessage(), 'user_id' => $user->id]);

            $response = "Sorry, there was an error posting your job. Please try again or contact support.";

            // Save bot response
            ChatMessage::create([
                'user_id' => $user->id,
                'role' => 'bot',
                'message' => $response,
            ]);

            return response()->json(['response' => $response]);
        }
    }

    public function jobAction(Request $request)
    {
        $request->validate([
            'action' => 'required|string|in:approve,edit,cancel',
            'field' => 'nullable|string',
            'job_draft' => 'required|array'
        ]);

        $user = Auth::user();
        if (!$user || $user->user_type !== 'employer') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $action = $request->input('action');
        $field = $request->input('field');
        $jobDraft = $request->input('job_draft');

        try {
            if ($action === 'approve') {
                // Create the job with pending_approval status
                $job = Job::create([
                    'title' => $jobDraft['title'],
                    'location' => $jobDraft['location'] ?? '',
                    'type' => $jobDraft['type'] ?? 'full-time',
                    'summary' => $jobDraft['summary'] ?? '',
                    'description' => $jobDraft['description'],
                    'salary' => is_numeric($jobDraft['salary']) ? (float) $jobDraft['salary'] : null,
                    'company' => $user->name ?? 'Company',
                    'user_id' => $user->id,
                    'status' => 'pending_approval',
                    'requirements' => [],
                ]);

                // Notify admin of new job awaiting approval
                $admins = \App\Models\User::where('user_type', 'admin')->get();
                foreach ($admins as $admin) {
                    \App\Models\Notification::create([
                        'user_id' => $admin->id,
                        'type' => 'job_awaiting_approval',
                        'title' => 'New Job Awaiting Approval',
                        'message' => "A new job post from {$user->name} is awaiting approval.",
                        'data' => [
                            'job_id' => $job->id,
                            'job_title' => $job->title,
                            'employer_name' => $user->name,
                        ]
                    ]);
                }

                // Notify employer that job is pending approval
                \App\Models\Notification::create([
                    'user_id' => $user->id,
                    'type' => 'job_pending_approval',
                    'title' => 'Job Submitted for Approval',
                    'message' => "Your job '{$jobDraft['title']}' has been submitted and is awaiting admin approval.",
                    'data' => [
                        'job_id' => $job->id,
                        'job_title' => $job->title,
                    ]
                ]);

                $response = "🎉 Job submitted successfully! Your job '{$jobDraft['title']}' is now awaiting admin approval.\n\n";
                $response .= "You'll be notified once it's approved. You can view and manage this job in your employer dashboard under 'My Job Posts'.";

            } elseif ($action === 'edit') {
                // For edit, we need to ask which field to update
                $response = "Which part would you like to edit? Reply with: title, location, type, summary, description, or salary.";

            } elseif ($action === 'cancel') {
                $response = "Job creation cancelled. Let me know if you'd like to start over!";
            }

            return response()->json([
                'response' => $response,
                'job_draft' => $action === 'edit' ? $jobDraft : null
            ]);

        } catch (\Exception $e) {
            Log::error('Job action failed', ['error' => $e->getMessage(), 'user_id' => $user->id]);
            return response()->json(['message' => 'Failed to process job action. Please try again.'], 500);
        }
    }

}
