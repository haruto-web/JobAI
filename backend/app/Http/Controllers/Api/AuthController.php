<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\Notification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use App\Services\OpenAIService;
use App\Services\ResumeParserService;

class AuthController extends Controller
{
    /**
     * Send password reset email
     */
    public function sendPasswordReset(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email|exists:users,email',
        ], [
            'email.exists' => 'No account found with this email address.'
        ]);

        try {
            $token = Str::random(64);
            
            \DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $validated['email']],
                [
                    'email' => $validated['email'],
                    'token' => Hash::make($token),
                    'created_at' => now()
                ]
            );

            return response()->json([
                'message' => 'Password reset token generated.',
                'email' => $validated['email'],
                'token' => $token,
                'reset_url' => config('app.frontend_url') . '/reset-password?token=' . $token . '&email=' . urlencode($validated['email'])
            ]);
        } catch (\Exception $e) {
            Log::error('Password reset failed: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to generate reset token.'], 500);
        }
    }

    /**
     * Reset password using token
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
            'email' => 'required|email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ])->setRememberToken(Str::random(60));

                $user->save();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return response()->json(['message' => 'Password has been reset successfully.']);
        }

        return response()->json(['message' => __($status)], 400);
    }

    /**
     * Register a new user and return token + user payload.
     */
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'user_type' => 'sometimes|in:jobseeker,employer,admin',
        ]);

        $user = User::create([
            'name' => $request->input('name'),
            'email' => $request->input('email'),
            'password' => Hash::make($request->input('password')),
            'user_type' => $request->input('user_type', 'jobseeker'),
        ]);

        // Create empty profile
        UserProfile::create(['user_id' => $user->id]);

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'user' => $user->load('profile'),
            'token' => $token,
        ], 201);
    }

    /**
     * Login existing user and return token + user payload.
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        // Hardcoded admin login - create user if not exists
        if ($request->input('email') === 'admin123@gmail.com' && $request->input('password') === 'admin12345') {
            $user = User::firstOrCreate(
                ['email' => 'admin123@gmail.com'],
                [
                    'name' => 'Admin User',
                    'password' => Hash::make('admin12345'),
                    'user_type' => 'admin',
                ]
            );
            $token = $user->createToken('api-token')->plainTextToken;

            return response()->json([
                'user' => $user->load('profile'),
                'token' => $token,
            ]);
        }

        $user = User::where('email', $request->input('email'))->first();

        if (!$user || !Hash::check($request->input('password'), $user->password)) {
            return response()->json(['message' => 'Invalid email or password'], 401);
        }

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'user' => $user->load('profile'),
            'token' => $token,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out']);
    }

    public function updateUser(Request $request)
    {
        $request->validate([
            'user_type' => 'required|in:jobseeker,employer',
        ]);

        $user = $request->user();
        $user->update($request->only('user_type'));

        return response()->json($user);
    }

    public function uploadProfileImage(Request $request)
    {
        $request->validate([
            'profile_image' => 'required|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $user = $request->user();

        // For now, use local storage until Cloudinary is properly configured
        // Delete old image if exists
        if ($user->getAttribute('profile_image')) {
            Storage::disk('public')->delete($user->getAttribute('profile_image'));
        }

        $path = $request->file('profile_image')->store('avatars', 'public');
        $url = config('app.url') . '/storage/' . $path;

        $user->setAttribute('profile_image', $url);
        $user->save();

        return response()->json($user);
    }

    private function extractCloudinaryPublicId($url)
    {
        // Extract public_id from Cloudinary URL
        // Example: https://res.cloudinary.com/cloud/image/upload/v123/avatars/abc.jpg -> avatars/abc
        if (preg_match('/\/upload\/(?:v\d+\/)?(.+)\.[^.]+$/', $url, $matches)) {
            return $matches[1];
        }
        return null;
    }

    public function user(Request $request)
    {
        return response()->json($request->user()->load('profile'));
    }

    public function updateProfile(Request $request)
    {
        $request->validate([
            'bio' => 'nullable|string|max:1000',
            'skills' => 'nullable|array',
            'experience_level' => 'nullable|in:entry_level,beginner,intermediate,experienced,expert_senior',
            'years_of_experience' => 'nullable|integer|min:0',
            'portfolio_url' => 'nullable|url',
            'education_attainment' => 'nullable|in:high_school,associate,bachelor,master,phd',
        ]);

        $user = $request->user();
        $profile = $this->ensureProfile($user);

        $profile->update($request->only(['bio', 'skills', 'experience_level', 'years_of_experience', 'portfolio_url', 'education_attainment']));

        return response()->json($user->fresh()->load('profile'));
    }

    private function extractAndMergeSkills($path, $profile)
    {
        try {
            // Try to instantiate parser safely (avoid autoload fatal)
            try {
                $parser = new ResumeParserService();
            } catch (\Throwable $e) {
                Log::error('Could not instantiate ResumeParserService', ['error' => $e->getMessage()]);
                return;
            }

            // parseResume should return text; guard if empty
            $resumeText = '';
            try {
                $resumeText = $parser->parseResume(storage_path('app/public/' . $path));
            } catch (\Throwable $e) {
                Log::error('Failed to parse resume file', ['path' => $path, 'error' => $e->getMessage()]);
            }

            if (empty($resumeText)) {
                Log::info('extractAndMergeSkills: no text extracted from resume', ['path' => $path]);
                return;
            }

            $extractedSkills = [];

            try {
                if (class_exists(OpenAIService::class) && is_string(config('services.openai.api_key')) && trim(config('services.openai.api_key')) !== '') {
                    try {
                        $openai = new OpenAIService();
                        $extractedSkills = $openai->extractSkillsFromResume($resumeText) ?? [];
                    } catch (\Throwable $e) {
                        Log::error('OpenAI skill extraction failed at instantiation or call', ['error' => $e->getMessage()]);
                    }
                } else {
                    Log::info('OpenAIService not available or API key missing; skipping skill extraction');
                }
            } catch (\Throwable $e) {
                Log::error('OpenAI skill extraction failed', ['error' => $e->getMessage()]);
            }

            // Merge with existing skills
            $existingSkills = is_array($profile->skills) ? $profile->skills : ($profile->skills ?? []);
            $mergedSkills = array_values(array_unique(array_merge($existingSkills, $extractedSkills)));
            $profile->update(['skills' => $mergedSkills]);
        } catch (\Throwable $e) {
            Log::error('extractAndMergeSkills exception', ['message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
        }
    }

    private function performComprehensiveResumeAnalysis($path, $profile)
    {
        try {
            // Try to instantiate parser safely
            try {
                $parser = new ResumeParserService();
            } catch (\Throwable $e) {
                Log::error('Could not instantiate ResumeParserService', ['error' => $e->getMessage()]);
                return;
            }

            // Parse resume text
            $resumeText = '';
            try {
                $resumeText = $parser->parseResume(storage_path('app/public/' . $path));
            } catch (\Throwable $e) {
                Log::error('Failed to parse resume file', ['path' => $path, 'error' => $e->getMessage()]);
                return;
            }

            if (empty($resumeText)) {
                Log::info('performComprehensiveResumeAnalysis: no text extracted from resume', ['path' => $path]);
                return;
            }

            // Perform comprehensive AI analysis
            $analysis = null;
            if (class_exists(OpenAIService::class) && is_string(config('services.openai.api_key')) && trim(config('services.openai.api_key')) !== '') {
                try {
                    $openai = new OpenAIService();
                    $analysis = $openai->analyzeResumeComprehensively($resumeText);
                } catch (\Throwable $e) {
                    Log::error('Comprehensive resume analysis failed at instantiation or call', ['error' => $e->getMessage()]);
                }
            } else {
                Log::info('OpenAIService not available; skipping comprehensive analysis');
            }

            if (! empty($analysis)) {
                // Update profile with AI analysis results
                $updateData = [
                    'ai_analysis' => $analysis,
                    'extracted_experience' => $analysis['experience_years'] ?? null,
                    'extracted_education' => is_array($analysis['education']) ? implode(', ', $analysis['education']) : $analysis['education'] ?? null,
                    'extracted_certifications' => is_array($analysis['certifications']) ? implode(', ', $analysis['certifications']) : $analysis['certifications'] ?? null,
                    'extracted_languages' => is_array($analysis['languages']) ? implode(', ', $analysis['languages']) : $analysis['languages'] ?? null,
                    'resume_summary' => $analysis['summary'] ?? null,
                    'last_ai_analysis' => now(),
                ];

                // Update experience level if not already set or if AI suggests a different level
                if (!$profile->experience_level || $analysis['experience_level']) {
                    $updateData['experience_level'] = $analysis['experience_level'] ?? $profile->experience_level;
                }

                // Merge skills with existing ones
                $existingSkills = is_array($profile->skills) ? $profile->skills : ($profile->skills ?? []);
                $aiSkills = is_array($analysis['skills']) ? $analysis['skills'] : [];
                $mergedSkills = array_values(array_unique(array_merge($existingSkills, $aiSkills)));
                $updateData['skills'] = $mergedSkills;

                $profile->update($updateData);

                Log::info('Comprehensive resume analysis completed', [
                    'user_id' => $profile->user_id,
                    'skills_count' => count($mergedSkills),
                    'experience_level' => $updateData['experience_level']
                ]);

                // Send notification to user
                Notification::create([
                    'user_id' => $profile->user_id,
                    'type' => 'ai_analysis_complete',
                    'title' => '🤖 Resume Analysis Complete!',
                    'message' => 'Your resume has been analyzed by AI. Check your profile to see the insights and improve your job matches!',
                    'data' => [
                        'analysis_id' => $profile->id,
                        'skills_count' => count($mergedSkills),
                        'experience_level' => $updateData['experience_level']
                    ]
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('performComprehensiveResumeAnalysis exception', ['message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
        }
    }

    public function uploadResume(Request $request)
    {
        Log::info('uploadResume called', [
            'user_id' => $request->user()?->id,
            'has_file' => $request->hasFile('resume'),
            'action' => $request->input('action'),
            'all_inputs' => $request->except('resume')
        ]);

        try {
            $user = $request->user();

            if (! $user) {
                Log::warning('uploadResume: unauthenticated');
                return response()->json(['error' => 'Unauthenticated'], 401);
            }

            // ensure profile exists
            $profile = $this->ensureProfile($user);

            $resumes = $profile->resumes ?? [];

            if ($request->has('action')) {
                $action = $request->input('action');
                $index = $request->input('index');

                if ($action === 'add' && $request->hasFile('resume')) {
                    $request->validate([
                        'resume' => 'required|file|mimes:pdf,doc,docx|max:5120', // 5MB max
                    ]);

                    $path = $request->file('resume')->store('resumes', 'public');

                    $resumes[] = [
                        'name' => $request->file('resume')->getClientOriginalName(),
                        'url' => $path,
                    ];

                    // Perform comprehensive AI analysis
                    $this->performComprehensiveResumeAnalysis($path, $profile);
                } elseif ($action === 'replace' && isset($index) && isset($resumes[$index]) && $request->hasFile('resume')) {
                    $request->validate([
                        'resume' => 'required|file|mimes:pdf,doc,docx|max:5120',
                    ]);

                    Storage::disk('public')->delete($resumes[$index]['url']);

                    $path = $request->file('resume')->store('resumes', 'public');
                    $resumes[$index]['url'] = $path;

                    $this->performComprehensiveResumeAnalysis($path, $profile);
                } elseif ($action === 'delete' && isset($index) && isset($resumes[$index])) {
                    // Handle both array format ['name' => '...', 'url' => '...'] and string format 'filename.pdf'
                    if (is_array($resumes[$index])) {
                        Storage::disk('public')->delete($resumes[$index]['url']);
                    } else {
                        // If it's a string, assume it's the filename and construct the path
                        $filename = $resumes[$index];
                        $path = 'resumes/' . $filename;
                        Storage::disk('public')->delete($path);
                    }
                    unset($resumes[$index]);
                    $resumes = array_values($resumes);
                } else {
                   Log::warning('uploadResume: invalid action', ['action' => $action]);
                    return response()->json(['error' => 'Invalid action or parameters'], 400);
                }
            } elseif ($request->hasFile('resume')) {
                // Legacy single resume upload
                $request->validate([
                    'resume' => 'required|file|mimes:pdf,doc,docx|max:5120',
                ]);

                if (! $profile) {
                    // Defensive fallback: create profile record
                    $profile = $user->profile()->create([]);
                }

                if ($profile->resume_url) {
                    Storage::disk('public')->delete($profile->resume_url);
                }

                $path = $request->file('resume')->store('resumes', 'public');
                $profile->update(['resume_url' => $path]);

                $this->performComprehensiveResumeAnalysis($path, $profile);
                return response()->json($user->load('profile'));
            } else {
                return response()->json(['error' => 'No action or file provided'], 400);
            }

            $profile->update(['resumes' => $resumes]);

            Log::info('uploadResume: updated profile', [
                'user_id' => $user->id,
                'resumes_count' => count($resumes),
            ]);

            return response()->json($user->load('profile'));
        } catch (\Throwable $e) {
           Log::error('uploadResume exception', ['message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json(['error' => 'Server error: '.$e->getMessage()], 500);
        }
    }

    /**
     * Get AI analysis of user's resume
     */
    public function getResumeAnalysis(Request $request)
    {
        $user = $request->user();
        $profile = $this->ensureProfile($user);

        if (!$profile->ai_analysis) {
            return response()->json(['message' => 'No AI analysis available. Please upload a resume first.'], 404);
        }

        return response()->json([
            'ai_analysis' => $profile->ai_analysis,
            'extracted_experience' => $profile->extracted_experience,
            'extracted_education' => $profile->extracted_education,
            'extracted_certifications' => $profile->extracted_certifications,
            'extracted_languages' => $profile->extracted_languages,
            'resume_summary' => $profile->resume_summary,
            'last_ai_analysis' => $profile->last_ai_analysis,
        ]);
    }

    /**
     * Manually trigger AI analysis for testing
     */
    public function triggerAiAnalysis(Request $request)
    {
        $user = $request->user();
        $profile = $this->ensureProfile($user);

        // Check if user has resumes
        $hasResumes = false;
        $resumePath = null;

        if ($profile->resumes && count($profile->resumes) > 0) {
            $hasResumes = true;
            $resumePath = $profile->resumes[0]['url']; // Use first resume
        } elseif ($profile->resume_url) {
            $hasResumes = true;
            $resumePath = $profile->resume_url;
        }

        if (!$hasResumes) {
            return response()->json(['message' => 'No resume found. Please upload a resume first.'], 400);
        }

        try {
            $this->performComprehensiveResumeAnalysis($resumePath, $profile);
            return response()->json(['message' => 'AI analysis triggered successfully. Check your profile for results.']);
        } catch (\Exception $e) {
            Log::error('Manual AI analysis failed', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'AI analysis failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get user notifications
     */
    public function getNotifications(Request $request)
    {
        $user = $request->user();
        $notifications = $user->notifications()
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        return response()->json($notifications);
    }

    /**
     * Mark notification as read
     */
    public function markNotificationAsRead(Request $request, $id)
    {
        $user = $request->user();
        $notification = $user->notifications()->find($id);

        if (!$notification) {
            return response()->json(['message' => 'Notification not found'], 404);
        }

        $notification->markAsRead();

        return response()->json(['message' => 'Notification marked as read']);
    }

    /**
     * Mark all notifications as read
     */
    public function markAllNotificationsAsRead(Request $request)
    {
        $user = $request->user();
        $user->notifications()->where('read', false)->update([
            'read' => true,
            'read_at' => now(),
        ]);

        return response()->json(['message' => 'All notifications marked as read']);
    }

    /**
     * Verify user email
     */
    public function verifyEmail(Request $request)
    {
        $request->validate([
            'id' => 'required|string',
            'hash' => 'required|string',
        ]);

        $user = User::find($request->id);

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email already verified'], 200);
        }

        if (!hash_equals((string) $request->id, (string) $user->getKey())) {
            return response()->json(['message' => 'Invalid verification link'], 400);
        }

        if (!hash_equals($request->hash, sha1($user->getEmailForVerification()))) {
            return response()->json(['message' => 'Invalid verification link'], 400);
        }

        $user->markEmailAsVerified();

        return response()->json(['message' => 'Email verified successfully! You can now log in.'], 200);
    }

    /**
     * Resend email verification
     */
    public function resendVerificationEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email already verified'], 200);
        }

        $user->sendEmailVerificationNotification();

        return response()->json(['message' => 'Verification email sent! Please check your email.'], 200);
    }

    // Google OAuth is handled by App\Http\Controllers\Auth\GoogleController
    // The redirect and callback logic was intentionally removed from this API controller
    // to keep a single canonical implementation in `Auth\GoogleController` and avoid
    // session-related issues when using API (stateless) endpoints.

    /**
     * Ensure the given user has a profile record and return it.
     */
    private function ensureProfile(User $user)
    {
        return $user->profile ?: $user->profile()->create([]);
    }
}
