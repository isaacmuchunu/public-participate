<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCitizenEngagementRequest;
use App\Jobs\SendLegislatorFollowUpNotification;
use App\Models\CitizenEngagement;
use App\Models\Submission;
use Illuminate\Http\RedirectResponse;

class CitizenEngagementController extends Controller
{
    public function store(StoreCitizenEngagementRequest $request, Submission $submission): RedirectResponse
    {
        $recipient = $submission->user;

        if (! $recipient) {
            return back()->withErrors([
                'subject' => 'This submission is anonymous and cannot receive follow-up messages.',
            ]);
        }

        $engagement = CitizenEngagement::create([
            'bill_id' => $submission->bill_id,
            'submission_id' => $submission->id,
            'sender_id' => $request->user()->id,
            'recipient_id' => $recipient->id,
            'subject' => $request->input('subject'),
            'message' => $request->input('message'),
            'channel' => 'platform',
            'sent_at' => now(),
        ]);

        $engagement->load('bill', 'sender', 'recipient');

        SendLegislatorFollowUpNotification::dispatch($engagement);

        return back()->with('flash', [
            'status' => 'success',
            'message' => 'Citizen has been notified of your follow-up request.',
        ]);
    }
}
