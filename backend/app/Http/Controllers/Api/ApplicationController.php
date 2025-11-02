<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Job;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class ApplicationController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        if ($user->user_type === 'employer') {
            // Employers see applications for their jobs
            $applications = Application::whereHas('job', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })->with(['user', 'job'])->get();
        } else {
            // Job seekers see their own applications
            $applications = Application::where('user_id', $user->id)->with('job')->get();
        }

        return response()->json($applications);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'job_id' => 'required|exists:job_listings,id',
            'cover_letter' => 'nullable|string|max:1000',
            'resume' => 'nullable|file|mimes:pdf,doc,docx|max:5120', // 5MB max
        ]);

        $user = Auth::user();

        // Check if user already applied
        $existingApplication = Application::where('user_id', $user->id)
            ->where('job_id', $validated['job_id'])
            ->first();

        if ($existingApplication) {
            return response()->json(['message' => 'You have already applied for this job'], Response::HTTP_CONFLICT);
        }

        // Check if user is employer (employers can't apply for jobs)
        if ($user->user_type === 'employer') {
            return response()->json(['message' => 'Employers cannot apply for jobs'], Response::HTTP_FORBIDDEN);
        }

        $application = Application::create([
            'user_id' => $user->id,
            'job_id' => $validated['job_id'],
            'status' => 'pending',
            'cover_letter' => $validated['cover_letter'] ?? null,
        ]);

        // Store the resume file if provided
        if ($request->hasFile('resume')) {
            $file = $request->file('resume');
            $filename = time() . '_' . $user->id . '_' . $application->id . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('resumes', $filename, 'public');
            $application->update(['resume_path' => $path]);
        }

        // Load the job and its owner
        $application->load(['job', 'user']);
        $job = $application->job;
        $jobOwner = $job->user;

        // Send notification to job owner (employer)
        if ($jobOwner) {
            Notification::create([
                'user_id' => $jobOwner->id,
                'type' => 'new_application',
                'title' => '📝 New Job Application!',
                'message' => $user->name . ' has applied for your job "' . $job->title . '". Check their resume and profile details.',
                'data' => [
                    'application_id' => $application->id,
                    'job_id' => $job->id,
                    'applicant_id' => $user->id,
                    'applicant_name' => $user->name,
                    'job_title' => $job->title
                ]
            ]);
        }

        return response()->json($application, Response::HTTP_CREATED);
    }

    public function update(Request $request, $id)
    {
        $application = Application::find($id);
        if (!$application) {
            return response()->json(['message' => 'Application not found'], Response::HTTP_NOT_FOUND);
        }

        $user = Auth::user();

        // Only employers can update application status
        if ($user->user_type !== 'employer') {
            return response()->json(['message' => 'Only employers can update application status'], Response::HTTP_FORBIDDEN);
        }

        // Check if employer owns the job
        if ($application->job->user_id !== $user->id) {
            return response()->json(['message' => 'You can only update applications for your own jobs'], Response::HTTP_FORBIDDEN);
        }

        $validated = $request->validate([
            'status' => 'required|in:pending,accepted,rejected',
        ]);

        $oldStatus = $application->status;
        $application->update($validated);

        // Send notification to jobseeker when application is accepted
        if ($validated['status'] === 'accepted' && $oldStatus !== 'accepted') {
            Notification::create([
                'user_id' => $application->user_id,
                'type' => 'application_accepted',
                'title' => '🎉 Application Accepted!',
                'message' => 'Congratulations! Your application for "' . $application->job->title . '" has been accepted. You can now upload your resume.',
                'data' => [
                    'application_id' => $application->id,
                    'job_id' => $application->job->id,
                    'job_title' => $application->job->title,
                    'employer_name' => $user->name
                ]
            ]);
        }

        // Send notification to jobseeker and delete application when rejected
        if ($validated['status'] === 'rejected') {
            // Send notification to jobseeker (only if this is a new rejection)
            if ($oldStatus !== 'rejected') {
                Notification::create([
                    'user_id' => $application->user_id,
                    'type' => 'application_rejected',
                    'title' => '❌ Application Rejected',
                    'message' => 'Unfortunately, your application for "' . $application->job->title . '" has been rejected.',
                    'data' => [
                        'application_id' => $application->id,
                        'job_id' => $application->job->id,
                        'job_title' => $application->job->title,
                        'employer_name' => $user->name
                    ]
                ]);
            }

            // Delete the rejected application
            $application->delete();

            return response()->json(['message' => 'Application rejected and removed']);
        }

        return response()->json($application->load(['user', 'job']));
    }

    public function destroy($id)
    {
        $application = Application::find($id);
        if (!$application) {
            return response()->json(['message' => 'Application not found'], Response::HTTP_NOT_FOUND);
        }

        $user = Auth::user();

        // Users can only delete their own applications, employers can delete applications for their jobs
        if ($application->user_id !== $user->id && $application->job->user_id !== $user->id) {
            return response()->json(['message' => 'You can only delete your own applications or applications for your jobs'], Response::HTTP_FORBIDDEN);
        }

        $application->delete();

        return response()->json(['message' => 'Application deleted']);
    }
}
