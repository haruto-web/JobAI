<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\OpenAIService;
use App\Services\ResumeParserService;
use App\Models\Job;
use App\Models\ChatMessage;
use App\Models\UserProfile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

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

            // Parse resume text
            $parser = new ResumeParserService();
            $resumeText = $parser->parseResume(storage_path('app/public/' . $filePath));

            // Analyze resume with OpenAI
            $openai = new OpenAIService();
            $analysis = $openai->analyzeResumeComprehensively($resumeText);

            // Update user profile with analysis
            $profile = $user->profile ?? new UserProfile(['user_id' => $user->id]);
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
            $suggestions = $openai->suggestJobsForSkills($skills, $experience, 5);

            // Also include local job matching as fallback
            $localSuggestions = [];
            if (!empty($skills)) {
                $jobs = Job::all();
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
        $jobs = Job::all();
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

            $systemPrompt = "You are an AI career advisor chatbot for a job recommendation website. Help users with job search & matching, application help, company information, interview assistance, status updates, and career advice. Common topics include: finding jobs by location/skill/type, applying for jobs, uploading resumes, cover letter tips, company details, interview preparation, application status, and career improvement. Be friendly, helpful, and professional. Use the provided context about the user when relevant. If asked about specific jobs, reference available job listings. Provide actionable advice and keep responses concise but informative. Always encourage next steps and offer to help further.";

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

            $response = $openai->getClient()->chat()->create([
                'model' => 'gpt-3.5-turbo',
                'messages' => $messagesForOpenAI,
                'max_tokens' => 500,
            ]);

            $aiResponse = $response->choices[0]->message->content;

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

        // Job Search & Matching
        if (str_contains($messageLower, 'what jobs') || str_contains($messageLower, 'available for me') ||
            str_contains($messageLower, 'show me') && str_contains($messageLower, 'jobs') ||
            str_contains($messageLower, 'part-time') || str_contains($messageLower, 'remote') ||
            str_contains($messageLower, 'near') || str_contains($messageLower, 'location') ||
            str_contains($messageLower, 'jobs')) {
            // Check if user has profile/skills
            $profile = $user->profile;
            $hasSkills = $profile && is_array($profile->skills) && count($profile->skills) > 0;
            if ($hasSkills) {
                $skills = $profile->skills;
                // Use local job matching to suggest jobs
                $jobs = Job::all();
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
                        $response .= "• " . $job['title'] . " (Match: " . $job['confidence'] . "%)\n  " . $job['description'] . "\n\n";
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
        // Career Advice
        elseif (str_contains($messageLower, 'which jobs fit') || str_contains($messageLower, 'improve my résumé') ||
                str_contains($messageLower, 'courses') || str_contains($messageLower, 'get hired faster') ||
                str_contains($messageLower, 'career') || str_contains($messageLower, 'advice')) {
            return "For career advice, share your skills below to get job suggestions! To improve your resume, focus on quantifiable achievements and relevant skills. Consider online courses on platforms like Coursera or Udemy to boost your employability.";
        }
        // General fallback
        else {
            return "I'm here to help with your job search! You can ask me about finding jobs, applying for positions, interview preparation, resume tips, career advice, or check our job listings page. What would you like to know?";
        }
    }
}
