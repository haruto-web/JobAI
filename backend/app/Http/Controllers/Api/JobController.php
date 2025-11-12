<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Job;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Services\OpenAIService;
use App\Models\Notification;

class JobController extends Controller
{
    public function index()
    {
        try {
            $jobs = Job::where('status', 'approved')->get();

            $user = Auth::user();
            if ($user && $user->user_type === 'jobseeker' && $user->profile) {
                $userSkills = $user->profile->skills ?? [];
                if ($user->profile->ai_analysis && isset($user->profile->ai_analysis['skills']) && !empty($user->profile->ai_analysis['skills'])) {
                    $userSkills = array_merge($userSkills, $user->profile->ai_analysis['skills']);
                }
                $userSkills = array_unique($userSkills);

                $skillsLower = array_map(fn($s) => strtolower($s), $userSkills);
                $highMatchJobs = [];

                foreach ($jobs as $job) {
                    $text = strtolower($job->title . ' ' . ($job->description ?? '') . ' ' . ($job->requirements ? json_encode($job->requirements) : ''));
                    $matchCount = 0;
                    foreach ($skillsLower as $skill) {
                        if ($skill === '') continue;
                        if (str_contains($text, $skill)) $matchCount++;
                    }
                    $matchScore = $matchCount > 0 ? min(100, (int) floor(($matchCount / max(1, count($skillsLower))) * 100)) : 0;

                    $job->match_score = $matchScore;

                    if ($matchScore >= 75) {
                        $highMatchJobs[] = $job;
                    }
                }

                $jobs = $jobs->sortByDesc('match_score')->values();

                if (!empty($highMatchJobs)) {
                    $this->notifyHighMatchJobs($user, $highMatchJobs);
                }
            }

            return response()->json($jobs);
        } catch (\Exception $e) {
            Log::error('Failed to fetch jobs', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Failed to fetch jobs'], 500);
        }
    }

    public function show($id)
    {
        $job = Job::find($id);
        if (!$job) {
            return response()->json(['message' => 'Job not found'], 404);
        }
        return response()->json($job);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'summary' => 'nullable|string',
            'qualifications' => 'nullable|string',
            'company' => 'nullable|string|max:255',
            'location' => 'required|string|max:255',
            'type' => 'string|in:full-time,part-time,contract',
            'salary' => 'nullable|numeric|min:0',
            'requirements' => 'nullable|array',
            'urgent' => 'nullable|boolean',
            'status' => 'nullable|string|in:draft,pending_approval,approved,rejected,archived',
        ]);

        // Set default company name if not provided
        if (empty($validated['company'])) {
            $validated['company'] = $user->name;
        }

        $user = Auth::user();

        // Only employers can create jobs
        if ($user->user_type !== 'employer') {
            return response()->json(['message' => 'Only employers can create jobs'], 403);
        }

        $job = Job::create(array_merge($validated, ['user_id' => $user->id, 'status' => 'pending_approval']));

        // Notify admin of new job awaiting approval
        $admins = User::where('user_type', 'admin')->get();
        foreach ($admins as $admin) {
            Notification::create([
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
        Notification::create([
            'user_id' => $user->id,
            'type' => 'job_created',
            'title' => '⏳ Job Created - Pending Approval',
            'message' => "Your job '{$job->title}' has been created successfully. Please wait for the admin to approve your job. Thank you!",
            'data' => ['job_id' => $job->id]
        ]);

        return response()->json($job, 201);
    }

    public function update(Request $request, $id)
    {
        $job = Job::find($id);
        if (!$job) {
            return response()->json(['message' => 'Job not found'], 404);
        }

        $user = Auth::user();

        // Only the job owner can update the job
        if ($job->user_id !== $user->id) {
            return response()->json(['message' => 'You can only update your own jobs'], 403);
        }

        $validated = $request->validate([
            'title' => 'string|max:255',
            'description' => 'string',
            'summary' => 'nullable|string',
            'qualifications' => 'nullable|string',
            'company' => 'string|max:255',
            'location' => 'string|max:255',
            'type' => 'string|in:full-time,part-time,contract',
            'salary' => 'numeric|min:0',
            'requirements' => 'array',
            'urgent' => 'boolean',
            'status' => 'string|in:draft,approved,archived',
        ]);

        $job->update($validated);
        return response()->json($job);
    }

    public function destroy($id)
    {
        $job = Job::find($id);
        if (!$job) {
            return response()->json(['message' => 'Job not found'], 404);
        }

        $user = Auth::user();

        // Only the job owner can delete the job
        if ($job->user_id !== $user->id) {
            return response()->json(['message' => 'You can only delete your own jobs'], 403);
        }

        $job->delete();
        return response()->json(['message' => 'Job deleted']);
    }

    public function search(Request $request)
    {
        try {
            $query = $request->input('q', '');
            if (empty($query)) {
                return response()->json(['jobs' => []]);
            }

            $searchTerm = strtolower($query);
            $normalizedTerm = str_replace(
                ['fulltime', 'partime', 'freelance'],
                ['full-time', 'part-time', 'contract'],
                $searchTerm
            );

        $jobs = Job::where('status', 'approved')
            ->where(function($q) use ($searchTerm, $normalizedTerm) {
                $q->where('title', 'LIKE', '%' . $searchTerm . '%')
                  ->orWhere('description', 'LIKE', '%' . $searchTerm . '%')
                  ->orWhere('company', 'LIKE', '%' . $searchTerm . '%')
                  ->orWhere('location', 'LIKE', '%' . $searchTerm . '%')
                  ->orWhere('type', 'LIKE', '%' . $normalizedTerm . '%');
            })
            ->limit(20)
            ->get();

            return response()->json(['jobs' => $jobs]);
        } catch (\Exception $e) {
            Log::error('Job search failed', ['error' => $e->getMessage(), 'query' => $request->input('q')]);
            return response()->json(['message' => 'Search failed'], 500);
        }
    }

    public function urgentJobs()
    {
        $urgentJobs = Job::where('status', 'approved')
            ->where('urgent', true)
            ->get();
        return response()->json($urgentJobs);
    }

    public function employerJobs(Request $request)
    {
        $user = Auth::user();
        if (!$user || $user->user_type !== 'employer') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $jobs = Job::where('user_id', $user->id)->get();
        return response()->json($jobs);
    }

    /**
     * Notify user about high match jobs
     */
    private function notifyHighMatchJobs($user, $highMatchJobs)
    {
        try {
            // Check if we already sent a notification recently (within last 6 hours)
            $recentNotification = Notification::where('user_id', $user->id)
                ->where('type', 'high_job_match')
                ->where('created_at', '>=', now()->subHours(6))
                ->first();

            if (!$recentNotification) {
                $jobTitles = array_map(function($job) {
                    return $job->title;
                }, $highMatchJobs);

                $topMatch = $highMatchJobs[0] ?? null;
                $matchScore = $topMatch ? $topMatch->match_score : 0;

                Notification::create([
                    'user_id' => $user->id,
                    'type' => 'high_job_match',
                    'title' => '🎯 Perfect Job Matches Found!',
                    'message' => 'We found ' . count($highMatchJobs) . ' excellent job matches for you based on your resume! Your top match is "' . ($topMatch ? $topMatch->title : '') . '" with ' . $matchScore . '% compatibility.',
                    'data' => [
                        'job_ids' => array_map(function($job) {
                            return $job->id;
                        }, $highMatchJobs),
                        'match_count' => count($highMatchJobs),
                        'top_match_score' => $matchScore,
                        'top_match_title' => $topMatch ? $topMatch->title : null
                    ]
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to send high match job notification', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
        }
    }
}
