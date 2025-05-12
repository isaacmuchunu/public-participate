<?php

namespace App\Http\Controllers\Legislator;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Bill;
use App\Models\Submission;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function __invoke(Request $request, Bill $bill): StreamedResponse
    {
        $user = $request->user();
        $house = $this->resolveHouse($user);

        abort_unless(in_array($bill->house, [$house, 'both'], true), 403);

        $filename = 'bill-'.$bill->id.'-submissions-'.now()->format('Ymd_His').'.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ];

        $callback = function () use ($bill) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'Tracking ID',
                'Type',
                'Status',
                'Submitter',
                'County',
                'Submitted At',
                'Content',
            ]);

            Submission::query()
                ->where('bill_id', $bill->id)
                ->orderBy('created_at')
                ->chunk(500, function ($submissions) use ($handle) {
                    foreach ($submissions as $submission) {
                        fputcsv($handle, [
                            $submission->tracking_id,
                            $submission->submission_type,
                            $submission->status,
                            $submission->submitter_name ?? 'Anonymous',
                            $submission->submitter_county,
                            optional($submission->created_at)->toDateTimeString(),
                            $submission->content,
                        ]);
                    }
                });

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function resolveHouse($user): string
    {
        if (! empty($user->legislative_house)) {
            return $user->legislative_house;
        }

        $role = $user->role instanceof UserRole ? $user->role : UserRole::from($user->role);

        return $role === UserRole::Senator ? 'senate' : 'national_assembly';
    }
}
