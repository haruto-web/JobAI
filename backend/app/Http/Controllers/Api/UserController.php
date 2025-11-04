<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Application;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public function search(Request $request)
    {
        $query = $request->input('q', '');
        if (empty($query)) {
            return response()->json(['users' => []]);
        }

        $searchTerm = strtolower($query);

        $users = User::with('profile')
            ->where(function($q) use ($searchTerm) {
                $q->whereRaw('LOWER(name) LIKE ?', ['%' . $searchTerm . '%'])
                  ->orWhereRaw('LOWER(email) LIKE ?', ['%' . $searchTerm . '%']);
            })
            ->limit(20)
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'user_type' => $user->user_type,
                    'profile' => $user->profile ? [
                        'bio' => $user->profile->bio,
                        'skills' => $user->profile->skills,
                        'experience_level' => $user->profile->experience_level,
                    ] : null,
                ];
            });

        return response()->json(['users' => $users]);
    }

    public function uploadResumeForJob(Request $request)
    {
        $validated = $request->validate([
            'resume' => 'required|file|mimes:pdf,doc,docx|max:5120', // 5MB max
            'project_id' => 'required|exists:applications,id',
        ]);

        $user = Auth::user();

        // Find the application
        $application = Application::with(['job', 'user'])->find($validated['project_id']);

        if (!$application) {
            return response()->json(['message' => 'Application not found'], 404);
        }

        // Check if user owns the application
        if ($application->user_id !== $user->id) {
            return response()->json(['message' => 'You can only upload resumes for your own applications'], 403);
        }

        // Check if application is accepted
        if ($application->status !== 'accepted') {
            return response()->json(['message' => 'You can only upload resumes for accepted applications'], 400);
        }

        // Store the resume file
        $file = $request->file('resume');
        $filename = time() . '_' . $user->id . '_' . $application->id . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs('resumes', $filename, 'public');

        // Update application with resume path
        $application->update(['resume_path' => $path]);

        // Send notification to employer
        $employer = $application->job->user;
        if ($employer) {
            Notification::create([
                'user_id' => $employer->id,
                'type' => 'resume_uploaded',
                'title' => '📄 Resume Uploaded!',
                'message' => $user->name . ' has uploaded their resume for the accepted job "' . $application->job->title . '". You can now view their resume.',
                'data' => [
                    'application_id' => $application->id,
                    'job_id' => $application->job->id,
                    'job_title' => $application->job->title,
                    'jobseeker_name' => $user->name,
                    'jobseeker_id' => $user->id,
                    'resume_path' => $path
                ]
            ]);
        }

        return response()->json([
            'message' => 'Resume uploaded successfully',
            'resume_path' => $path
        ]);
    }
}
