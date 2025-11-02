<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PaymentController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'application_id' => 'required|exists:applications,id',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'required|string|max:255',
        ]);

        $user = Auth::user();

        // Only employers can make payments
        if ($user->user_type !== 'employer') {
            return response()->json(['message' => 'Only employers can process payments'], 403);
        }

        // Find the application
        $application = Application::with(['job', 'user'])->find($validated['application_id']);

        if (!$application) {
            return response()->json(['message' => 'Application not found'], 404);
        }

        // Check if employer owns the job
        if ($application->job->user_id !== $user->id) {
            return response()->json(['message' => 'You can only pay for applications on your own jobs'], 403);
        }

        // Check if application is accepted
        if ($application->status !== 'accepted') {
            return response()->json(['message' => 'You can only pay for accepted applications'], 400);
        }

        // Check if payment already exists for this application
        $existingPayment = Payment::where('application_id', $application->id)->first();
        if ($existingPayment) {
            return response()->json(['message' => 'Payment already processed for this application'], 400);
        }

        // Create payment record
        $payment = Payment::create([
            'application_id' => $application->id,
            'employer_id' => $user->id,
            'jobseeker_id' => $application->user_id,
            'amount' => $validated['amount'],
            'description' => $validated['description'],
            'status' => 'completed', // For now, mark as completed immediately
            'processed_at' => now(),
        ]);

        // Send notification to jobseeker about salary payment
        \App\Models\Notification::create([
            'user_id' => $application->user_id,
            'type' => 'salary_paid',
            'title' => '💰 Salary Payment Received!',
            'message' => 'You have received a salary payment of $' . number_format($validated['amount'], 2) . ' for "' . $application->job->title . '".',
            'data' => [
                'application_id' => $application->id,
                'job_id' => $application->job->id,
                'job_title' => $application->job->title,
                'employer_name' => $user->name,
                'amount' => $validated['amount'],
                'payment_id' => $payment->id
            ]
        ]);

        return response()->json([
            'message' => 'Payment processed successfully',
            'payment' => $payment->load(['application.job', 'employer', 'jobseeker'])
        ], 201);
    }

    public function index()
    {
        $user = Auth::user();

        if ($user->user_type === 'employer') {
            $payments = Payment::where('employer_id', $user->id)
                ->with(['application.job', 'jobseeker'])
                ->orderBy('created_at', 'desc')
                ->get();
        } else {
            $payments = Payment::where('jobseeker_id', $user->id)
                ->with(['application.job', 'employer'])
                ->orderBy('created_at', 'desc')
                ->get();
        }

        return response()->json($payments);
    }

    public function manageMoney(Request $request)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric',
            'description' => 'required|string|max:255',
        ]);

        $user = Auth::user();

        // Only employers can manage money
        if ($user->user_type !== 'employer') {
            return response()->json(['message' => 'Only employers can manage money'], 403);
        }

        $type = $validated['amount'] > 0 ? 'money_added' : 'money_reduced';

        // Create a payment record for money management
        $payment = Payment::create([
            'application_id' => null,
            'employer_id' => $user->id,
            'jobseeker_id' => null,
            'amount' => abs($validated['amount']),
            'description' => $validated['description'],
            'type' => $type,
            'status' => 'completed',
            'processed_at' => now(),
        ]);

        return response()->json([
            'message' => 'Money ' . ($validated['amount'] > 0 ? 'added' : 'reduced') . ' successfully',
            'payment' => $payment
        ]);
    }
}
