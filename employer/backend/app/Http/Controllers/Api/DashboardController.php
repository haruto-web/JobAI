<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Job;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json(['message' => 'Unauthenticated'], 401);
            }

            switch ($user->user_type) {
                case 'jobseeker':
                    return $this->jobseekerDashboard($user);
                case 'employer':
                    return $this->employerDashboard($user);
                case 'admin':
                    return $this->adminDashboard($user);
                default:
                    return response()->json(['message' => 'Invalid user type'], 400);
            }
        } catch (\Exception $e) {
            Log::error('Dashboard fetch failed', ['error' => $e->getMessage(), 'user_id' => Auth::id()]);
            return response()->json(['message' => 'Failed to load dashboard'], 500);
        }
    }

    private function jobseekerDashboard(User $user)
    {
        $applications = Application::where('user_id', $user->id)
            ->with('job')
            ->get();

        $incomingProjects = Application::where('user_id', $user->id)
            ->where('status', 'accepted')
            ->with('job')
            ->get();

        $profile = $user->profile; // Assuming User has profile relationship

        $transactions = Payment::where('jobseeker_id', $user->id)
            ->with(['application.job', 'employer'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($payment) {
                return [
                    'id' => $payment->id,
                    'amount' => $payment->amount,
                    'type' => 'earned',
                    'description' => $payment->description,
                    'date' => $payment->processed_at ? $payment->processed_at->format('Y-m-d') : null,
                ];
            });

        $totalEarnings = $transactions->sum('amount');

        return response()->json([
            'user_type' => 'jobseeker',
            'applications' => $applications,
            'incoming_projects' => $incomingProjects,
            'profile' => $profile,
            'transactions' => $transactions,
            'total_earnings' => $totalEarnings,
        ]);
    }

    private function employerDashboard(User $user)
    {
        $jobs = Job::where('user_id', $user->id)
            ->withCount('applications')
            ->with(['applications' => function($query) {
                $query->select('id', 'job_id', 'status', 'created_at');
            }])
            ->get();

        $applications = Application::whereHas('job', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })->with([
            'user:id,name,email',
            'user.profile:id,user_id,skills,experience_level',
            'job:id,title,company,user_id'
        ])->get();

        $workingOnJobs = Application::whereHas('job', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })->where('status', 'accepted')->with(['user', 'job'])->get();

        $transactions = Payment::where('employer_id', $user->id)
            ->with(['application.job', 'jobseeker'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($payment) {
                $amount = $payment->amount !== null ? (float) $payment->amount : 0.0;
                $type = 'paid';

                if ($payment->type === 'money_added') {
                    $type = 'added';
                } elseif ($payment->type === 'money_reduced') {
                    $type = 'reduced';
                    $amount = -((float)$amount);
                } else {
                    $amount = -((float)$amount); // Regular payments are negative
                }

                return [
                    'id' => $payment->id,
                    'amount' => $amount,
                    'type' => $type,
                    'description' => $payment->description,
                    'date' => $payment->processed_at ? $payment->processed_at->format('Y-m-d') : null,
                ];
            });

        // Compute employer balance by summing signed transaction amounts.
        // 'added' -> positive, 'reduced'/'paid' -> negative as set above.
        $totalSpent = (float) $transactions->sum('amount');

        // Analytics & Insights
        $totalJobPosts = $jobs->count();
        $activeJobs = $jobs->where('created_at', '>=', now()->subDays(30))->count(); // Jobs posted in last 30 days
        $closedJobs = $jobs->where('created_at', '<', now()->subDays(30))->count(); // Older jobs considered "closed"

        // Applications per job
        $applicationsPerJob = $jobs->map(function ($job) {
            return [
                'job_title' => $job->title,
                'applications_count' => $job->applications->count(),
            ];
        });

        // Application trends (last 7 days) - optimized single query
        $applicationTrends = Application::selectRaw('DATE(created_at) as date, COUNT(*) as applications')
            ->whereHas('job', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->where('created_at', '>=', now()->subDays(6))
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        // Fill missing dates with 0 applications
        $trends = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $trends[] = [
                'date' => $date,
                'applications' => $applicationTrends->get($date)?->applications ?? 0,
            ];
        }
        $applicationTrends = $trends;

        // Recent activity feed
        $recentActivities = [];

        // Recent applications
        $recentApplications = Application::whereHas('job', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })->with(['user', 'job'])
        ->orderBy('created_at', 'desc')
        ->limit(5)
        ->get()
        ->map(function ($app) {
            return [
                'type' => 'application',
                'message' => "New application from {$app->user->name} for '{$app->job->title}'",
                'date' => $app->created_at->format('Y-m-d H:i:s'),
            ];
        });

        // Recent payments
        $recentPayments = Payment::where('employer_id', $user->id)
            ->with(['application.job', 'jobseeker'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($payment) {
                $message = '';
                if ($payment->type === 'money_added') {
                    $message = "Money added: \${$payment->amount}";
                } elseif ($payment->type === 'money_reduced') {
                    $message = "Money reduced: \${$payment->amount}";
                } else {
                    $message = "Payment of \${$payment->amount} made to {$payment->jobseeker->name}";
                }
                return [
                    'type' => 'payment',
                    'message' => $message,
                    'date' => $payment->processed_at ? $payment->processed_at->format('Y-m-d H:i:s') : null,
                ];
            });

        // Combine and sort recent activities
        $recentActivities = collect(array_merge($recentApplications->toArray(), $recentPayments->toArray()))
            ->sortByDesc('date')
            ->take(10)
            ->values()
            ->all();

        return response()->json([
            'user_type' => 'employer',
            'jobs' => $jobs,
            'applications' => $applications,
            'working_on_jobs' => $workingOnJobs,
            'transactions' => $transactions,
            'total_spent' => $totalSpent,
            'active_jobs' => $jobs->count(),
            'total_applications' => $applications->count(),
            // Analytics data
            'analytics' => [
                'total_job_posts' => $totalJobPosts,
                'active_jobs' => $activeJobs,
                'closed_jobs' => $closedJobs,
                'applications_per_job' => $applicationsPerJob,
                'application_trends' => $applicationTrends,
                'recent_activities' => $recentActivities,
            ],
        ]);
    }

    private function adminDashboard(User $user)
    {
        // User statistics
        $totalUsers = User::count();
        $jobseekers = User::where('user_type', 'jobseeker')->count();
        $employers = User::where('user_type', 'employer')->count();
        $admins = User::where('user_type', 'admin')->count();

        // Job statistics
        $totalJobs = Job::count();
        $urgentJobs = Job::where('urgent', true)->count();

        // Application statistics
        $totalApplications = Application::count();
        $pendingApplications = Application::where('status', 'pending')->count();
        $acceptedApplications = Application::where('status', 'accepted')->count();
        $rejectedApplications = Application::where('status', 'rejected')->count();

        // Payment statistics
        $totalPayments = Payment::count();
        $totalPaymentAmount = Payment::sum('amount');

        // Recent users
        $recentUsers = User::with('profile')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'user_type' => $user->user_type,
                    'created_at' => $user->created_at->format('Y-m-d H:i:s'),
                ];
            });

        // Recent jobs
        $recentJobs = Job::with('user')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($job) {
                return [
                    'id' => $job->id,
                    'title' => $job->title,
                    'company' => $job->company,
                    'user' => $job->user ? $job->user->name : 'Unknown',
                    'urgent' => $job->urgent,
                    'created_at' => $job->created_at->format('Y-m-d H:i:s'),
                ];
            });

        // Recent applications
        $recentApplications = Application::with(['user', 'job'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($app) {
                return [
                    'id' => $app->id,
                    'job_title' => $app->job->title,
                    'user_name' => $app->user->name,
                    'status' => $app->status,
                    'created_at' => $app->created_at->format('Y-m-d H:i:s'),
                ];
            });

        return response()->json([
            'user_type' => 'admin',
            'summary' => [
                'total_users' => $totalUsers,
                'jobseekers' => $jobseekers,
                'employers' => $employers,
                'admins' => $admins,
                'total_jobs' => $totalJobs,
                'urgent_jobs' => $urgentJobs,
                'total_applications' => $totalApplications,
                'pending_applications' => $pendingApplications,
                'accepted_applications' => $acceptedApplications,
                'rejected_applications' => $rejectedApplications,
                'total_payments' => $totalPayments,
                'total_payment_amount' => $totalPaymentAmount,
            ],
            'graphs' => [
                'user_types' => [
                    'jobseeker' => $jobseekers,
                    'employer' => $employers,
                    'admin' => $admins,
                ],
                'application_status' => [
                    'pending' => $pendingApplications,
                    'accepted' => $acceptedApplications,
                    'rejected' => $rejectedApplications,
                ],
            ],
            'recent_users' => $recentUsers,
            'recent_jobs' => $recentJobs,
            'recent_applications' => $recentApplications,
        ]);
    }
}
