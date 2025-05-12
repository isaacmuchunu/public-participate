<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\Bill;
use App\Models\Submission;
use App\Models\SystemAlert;
use App\Models\User;
use App\Models\UserSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();
        $role = $user->role instanceof UserRole ? $user->role : UserRole::from($user->role);

        return match ($role) {
            UserRole::Citizen => $this->citizenDashboard($request),
            UserRole::Mp, UserRole::Senator => $this->legislatorDashboard($request),
            UserRole::Clerk => $this->clerkDashboard($request),
            UserRole::Admin => $this->adminDashboard($request),
        };
    }

    private function citizenDashboard(Request $request): Response
    {
        $user = $request->user();

        $openBills = Bill::openForParticipation()
            ->with('summary')
            ->orderBy('participation_end_date')
            ->limit(6)
            ->get(['id', 'title', 'bill_number', 'participation_end_date', 'house', 'submissions_count']);

        $recentSubmissions = $user->submissions()
            ->with('bill:id,title,bill_number')
            ->latest()
            ->limit(5)
            ->get(['id', 'bill_id', 'status', 'tracking_id', 'created_at']);

        $submissionStatusCounts = $user->submissions()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $stats = [
            'openBills' => $openBills->count(),
            'totalSubmissions' => $submissionStatusCounts->sum(),
            'pendingReviews' => $submissionStatusCounts->get('pending', 0),
        ];

        $upcomingDeadlines = $openBills
            ->filter(fn (Bill $bill) => ! empty($bill->participation_end_date))
            ->sortBy('participation_end_date')
            ->take(3)
            ->map(fn (Bill $bill) => [
                'id' => $bill->id,
                'title' => $bill->title,
                'participation_end_date' => $bill->participation_end_date,
                'bill_number' => $bill->bill_number,
            ])
            ->values();

        $topicHighlights = $openBills
            ->flatMap(function (Bill $bill) {
                $clauses = collect($bill->summary?->key_clauses ?? []);

                return $clauses->take(2)->map(fn ($clause) => [
                    'bill_id' => $bill->id,
                    'bill_title' => $bill->title,
                    'excerpt' => $clause,
                ]);
            })
            ->take(6)
            ->values();

        $notifications = [
            [
                'type' => 'deadline',
                'message' => 'Public commentary for the Finance Bill closes in 3 days.',
                'severity' => 'warning',
            ],
            [
                'type' => 'report',
                'message' => 'Participation report for the Housing Levy Amendment Bill is now available.',
                'severity' => 'info',
            ],
        ];

        $resourceShortcuts = [
            [
                'key' => 'start_submission',
                'title' => 'Craft a submission',
                'description' => 'Use the guided form to capture your views clause by clause.',
                'href' => route('submissions.create', absolute: false),
                'label' => 'Guided form',
            ],
            [
                'key' => 'track_submission',
                'title' => 'Track a tracking ID',
                'description' => 'See whether your submission has been reviewed or escalated.',
                'href' => route('submissions.track.form', absolute: false),
                'label' => 'Real-time status',
            ],
            [
                'key' => 'manage_sessions',
                'title' => 'Secure your devices',
                'description' => 'Log out of unfamiliar devices and review session activity.',
                'href' => route('sessions.index', absolute: false),
                'label' => 'Security',
            ],
            [
                'key' => 'update_profile',
                'title' => 'Update civic profile',
                'description' => 'Refresh your contact details and verification information.',
                'href' => route('profile.edit', absolute: false),
                'label' => '2 min update',
            ],
        ];

        $supportChannels = [
            [
                'key' => 'county_liaison',
                'type' => 'phone',
                'title' => 'County liaison desk',
                'contact' => '+254 709 555 120',
                'description' => 'For county specific participation queries and venue clarifications.',
                'link' => 'tel:+254709555120',
                'response_time' => 'Immediate',
                'languages' => ['English', 'Kiswahili'],
                'schedule' => [
                    'days' => ['Mon', 'Tue', 'Wed', 'Thu', 'Fri'],
                    'start' => '08:00',
                    'end' => '17:00',
                    'timezone' => 'Africa/Nairobi',
                    'timezone_label' => 'EAT',
                ],
            ],
            [
                'key' => 'digital_support',
                'type' => 'email',
                'title' => 'Digital support desk',
                'contact' => 'support@huduma.go.ke',
                'description' => 'Account access, password resets, and verification updates.',
                'link' => 'mailto:support@huduma.go.ke',
                'response_time' => 'Within 4 hours',
                'languages' => ['English', 'Kiswahili'],
                'schedule' => [
                    'days' => ['Mon', 'Tue', 'Wed', 'Thu', 'Fri'],
                    'start' => '07:00',
                    'end' => '19:00',
                    'timezone' => 'Africa/Nairobi',
                    'timezone_label' => 'EAT',
                ],
            ],
            [
                'key' => 'whatsapp_care',
                'type' => 'chat',
                'title' => 'WhatsApp helpdesk',
                'contact' => '+254 700 123 456',
                'description' => 'Chat with a participation advisor for quick clarifications.',
                'link' => 'https://wa.me/254700123456',
                'response_time' => 'Under 10 minutes',
                'languages' => ['English', 'Kiswahili'],
                'schedule' => [
                    'days' => ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
                    'start' => '09:00',
                    'end' => '21:00',
                    'timezone' => 'Africa/Nairobi',
                    'timezone_label' => 'EAT',
                ],
            ],
        ];

        $knowledgeBase = [
            [
                'key' => 'citizen_playbook',
                'title' => 'Citizen participation playbook',
                'description' => 'Step-by-step guidance on analysing bills and framing impactful submissions.',
                'href' => 'https://parliament.go.ke/sites/default/files/2023-02/Public%20Participation%20Guidelines.pdf',
                'format' => 'PDF · 18 pages',
                'category' => 'Guide',
                'external' => true,
            ],
            [
                'key' => 'video_walkthrough',
                'title' => 'How to submit feedback online',
                'description' => 'Five-minute walkthrough demonstrating the submission portal on mobile.',
                'href' => 'https://www.youtube.com/watch?v=0pK6-Participation',
                'format' => 'Video · 5 min',
                'category' => 'Tutorial',
                'external' => true,
            ],
            [
                'key' => 'clauses_checklist',
                'title' => 'Clause review checklist',
                'description' => 'Printable checklist to compare bill clauses with community priorities.',
                'href' => 'https://huduma.go.ke/resources/ClauseReviewChecklist.pdf',
                'format' => 'PDF · 2 pages',
                'category' => 'Template',
                'external' => true,
            ],
        ];

        $communityClinics = [
            [
                'key' => 'virtual_clinic_oct',
                'title' => 'Virtual participation clinic',
                'starts_at' => now()->addDays(5)->setTime(18, 0)->toIso8601String(),
                'duration' => '45 minutes',
                'channel' => 'Zoom (link shared on RSVP)',
                'registration_url' => 'https://forms.gle/huduma-participation-clinic',
                'language' => 'English & Kiswahili',
            ],
            [
                'key' => 'county_forum',
                'title' => 'Machakos in-person legal aid day',
                'starts_at' => now()->addDays(12)->setTime(10, 0)->toIso8601String(),
                'duration' => '3 hours',
                'channel' => 'Machakos Huduma Centre Hall B',
                'registration_url' => 'https://huduma.go.ke/events/machakos-legal-aid',
                'language' => 'Kiswahili & Kikamba translation available',
            ],
        ];

        $faqs = [
            [
                'question' => 'How long does it take to verify my account after registration?',
                'answer' => 'Verification typically completes within 15 minutes. If delayed beyond one hour, contact the digital support desk for assistance.',
            ],
            [
                'question' => 'Can I edit or withdraw a submission after sending it?',
                'answer' => 'Yes, you can request an edit within 24 hours of submission by contacting the WhatsApp helpdesk with your tracking ID.',
            ],
            [
                'question' => 'What documents should I attach to strengthen my submission?',
                'answer' => 'Attach official letters, community resolutions, or research summaries. Ensure documents are under 10 MB and in PDF format.',
            ],
        ];

        return Inertia::render('Dashboard/Citizen', [
            'openBills' => $openBills,
            'recentSubmissions' => $recentSubmissions,
            'notifications' => $notifications,
            'stats' => $stats,
            'upcomingDeadlines' => $upcomingDeadlines,
            'topicHighlights' => $topicHighlights,
            'resourceShortcuts' => $resourceShortcuts,
            'supportChannels' => $supportChannels,
            'knowledgeBase' => $knowledgeBase,
            'communityClinics' => $communityClinics,
            'faqs' => $faqs,
        ]);
    }

    private function legislatorDashboard(Request $request): Response
    {
        $user = $request->user();
        $house = $user->legislative_house ?? ($user->role === UserRole::Senator ? 'senate' : 'national_assembly');

        $billsQuery = Bill::query()
            ->with(['summary'])
            ->withCount('submissions')
            ->when($house, function ($query, $house) {
                $query->whereIn('house', [$house, 'both']);
            })
            ->latest('participation_end_date');

        $topBills = (clone $billsQuery)
            ->limit(6)
            ->get(['id', 'title', 'bill_number', 'participation_end_date', 'house', 'submissions_count']);

        $feedbackStats = Submission::query()
            ->selectRaw('bill_id, status, COUNT(*) as total')
            ->whereIn('bill_id', $topBills->pluck('id'))
            ->groupBy('bill_id', 'status')
            ->get();

        $summaries = $topBills->map(function (Bill $bill) use ($feedbackStats) {
            $stats = $feedbackStats->where('bill_id', $bill->id)->pluck('total', 'status');

            return [
                'bill' => [
                    'id' => $bill->id,
                    'title' => $bill->title,
                    'number' => $bill->bill_number,
                    'house' => $bill->house,
                    'participation_end_date' => $bill->participation_end_date,
                ],
                'metrics' => [
                    'total' => $stats->sum(),
                    'pending' => $stats->get('pending', 0),
                    'reviewed' => $stats->get('reviewed', 0),
                ],
                'aiSummary' => [
                    'headline' => 'AI summary placeholder',
                    'body' => 'AI-generated summaries of citizen feedback will appear here, highlighting key themes and sentiment per clause.',
                ],
            ];
        });

        $submissionBreakdown = Submission::query()
            ->whereIn('bill_id', $topBills->pluck('id'))
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $clauseHighlights = $topBills
            ->flatMap(function (Bill $bill) {
                $clauses = collect($bill->summary?->key_clauses ?? []);

                return $clauses->take(2)->map(fn ($clause) => [
                    'bill_id' => $bill->id,
                    'bill_title' => $bill->title,
                    'clause' => $clause,
                    'house' => $bill->house,
                    'deadline' => $bill->participation_end_date,
                ]);
            })
            ->take(6)
            ->values();

        $reportLinks = $topBills->map(fn (Bill $bill) => [
            'bill_id' => $bill->id,
            'title' => $bill->title,
            'url' => route('submissions.index', ['bill_id' => $bill->id]),
        ]);

        return Inertia::render('Dashboard/Legislator', [
            'house' => $house,
            'topBills' => $topBills,
            'summaries' => $summaries,
            'submissionBreakdown' => $submissionBreakdown,
            'clauseHighlights' => $clauseHighlights,
            'reportLinks' => $reportLinks,
        ]);
    }

    private function clerkDashboard(Request $request): Response
    {
        $billMetrics = [
            'total_bills' => Bill::count(),
            'open_bills' => Bill::openForParticipation()->count(),
            'needs_review' => Bill::where('status', 'committee_review')->count(),
        ];

        $submissionMetrics = Submission::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $submissionTypes = Submission::query()
            ->selectRaw('submission_type, COUNT(*) as total')
            ->groupBy('submission_type')
            ->pluck('total', 'submission_type');

        $userMetrics = [
            'pending_citizens' => User::query()
                ->where('role', UserRole::Citizen)
                ->where(function ($query) {
                    $query->where('is_verified', false)->orWhereNull('is_verified');
                })
                ->count(),
            'legislator_invites' => User::query()
                ->whereIn('role', [UserRole::Mp, UserRole::Senator])
                ->whereNull('email_verified_at')
                ->count(),
        ];

        $recentBills = Bill::query()
            ->with('creator:id,name')
            ->latest()
            ->limit(5)
            ->get(['id', 'title', 'bill_number', 'status', 'house', 'participation_end_date', 'created_by']);

        $recentSubmissions = Submission::query()
            ->with(['bill:id,title,bill_number', 'user:id,name'])
            ->latest()
            ->limit(5)
            ->get(['id', 'bill_id', 'user_id', 'status', 'tracking_id', 'created_at']);

        return Inertia::render('Dashboard/Clerk', [
            'billMetrics' => $billMetrics,
            'submissionMetrics' => $submissionMetrics,
            'submissionTypes' => $submissionTypes,
            'userMetrics' => $userMetrics,
            'recentBills' => $recentBills,
            'recentSubmissions' => $recentSubmissions,
        ]);
    }

    private function adminDashboard(Request $request): Response
    {
        $userCounts = User::query()
            ->select('role')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('role')
            ->pluck('total', 'role')
            ->map(fn ($total) => (int) $total);

        $newUsersThisWeek = User::query()
            ->where('created_at', '>=', now()->subDays(7))
            ->count();

        $pendingInvitations = User::query()
            ->whereNotNull('invitation_token')
            ->whereNull('email_verified_at')
            ->count();

        $billMetrics = [
            'total' => Bill::count(),
            'open' => Bill::openForParticipation()->count(),
            'drafts' => Bill::where('status', 'draft')->count(),
            'underReview' => Bill::where('status', 'committee_review')->count(),
        ];

        $submissionMetrics = Submission::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->map(fn ($total) => (int) $total);

        $dailySubmissionTrend = Submission::query()
            ->selectRaw('DATE(created_at) as date, COUNT(*) as total')
            ->where('created_at', '>=', now()->subDays(14))
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(fn ($row) => [
                'date' => $row->date,
                'total' => (int) $row->total,
            ])
            ->values();

        $recentUsers = User::query()
            ->latest()
            ->limit(6)
            ->get(['id', 'name', 'email', 'role', 'created_at', 'is_verified', 'last_active_at']);

        $recentBills = Bill::query()
            ->with('creator:id,name')
            ->latest()
            ->limit(6)
            ->get(['id', 'title', 'bill_number', 'status', 'house', 'participation_end_date', 'created_by']);

        $recentSubmissions = Submission::query()
            ->with(['bill:id,title,bill_number', 'user:id,name,email'])
            ->latest()
            ->limit(6)
            ->get(['id', 'bill_id', 'user_id', 'status', 'tracking_id', 'created_at']);

        $recentSessions = Schema::hasTable('user_sessions')
            ? UserSession::query()
                ->with('user:id,name,email,role')
                ->latest('last_activity_at')
                ->limit(6)
                ->get(['id', 'user_id', 'device', 'ip_address', 'last_activity_at'])
                ->map(fn (UserSession $session) => [
                    'id' => $session->id,
                    'device' => $session->device,
                    'ip_address' => $session->ip_address,
                    'last_activity_at' => $session->last_activity_at,
                    'user' => $session->user?->only(['id', 'name', 'email', 'role']),
                ])
                ->values()
            : collect();

        $activeSessions = Schema::hasTable('user_sessions')
            ? UserSession::query()
                ->where('last_activity_at', '>=', now()->subHours(12))
                ->count()
            : 0;

        $systemAlerts = Schema::hasTable('system_alerts')
            ? SystemAlert::query()
                ->whereNull('dismissed_at')
                ->where(function ($query) {
                    $query
                        ->whereNull('expires_at')
                        ->orWhere('expires_at', '>=', now());
                })
                ->orderByDesc('published_at')
                ->orderByDesc('created_at')
                ->limit(6)
                ->get()
                ->map(fn (SystemAlert $alert) => [
                    'id' => $alert->id,
                    'title' => $alert->title,
                    'message' => $alert->message,
                    'severity' => $alert->severity,
                    'href' => $alert->action_url,
                    'published_at' => $alert->published_at,
                ])
                ->values()
            : collect();

        $managementShortcuts = [
            [
                'key' => 'manage-users',
                'title' => 'Manage user registry',
                'description' => 'Review citizen, clerk, and legislator accounts in one place.',
                'href' => route('clerk.citizens.index', absolute: false),
            ],
            [
                'key' => 'bill-governance',
                'title' => 'Oversee bill lifecycle',
                'description' => 'Ensure bills move smoothly from drafting to publication.',
                'href' => route('bills.index', absolute: false),
            ],
            [
                'key' => 'participation-health',
                'title' => 'Monitor participation health',
                'description' => 'Track submission sentiment and response rates each week.',
                'href' => route('submissions.index', absolute: false),
            ],
        ];

        return Inertia::render('Dashboard/Admin', [
            'metrics' => [
                'users' => [
                    'total' => $userCounts->sum(),
                    'byRole' => $userCounts->all(),
                    'newThisWeek' => $newUsersThisWeek,
                    'pendingInvitations' => $pendingInvitations,
                ],
                'bills' => $billMetrics,
                'submissions' => [
                    'total' => $submissionMetrics->sum(),
                    'pending' => $submissionMetrics->get('pending', 0),
                    'reviewed' => $submissionMetrics->get('reviewed', 0),
                    'escalated' => $submissionMetrics->get('escalated', 0),
                ],
                'sessions' => [
                    'active' => $activeSessions,
                ],
            ],
            'dailySubmissions' => $dailySubmissionTrend,
            'recentUsers' => $recentUsers,
            'recentBills' => $recentBills,
            'recentSubmissions' => $recentSubmissions,
            'recentSessions' => $recentSessions,
            'systemAlerts' => $systemAlerts,
            'managementShortcuts' => $managementShortcuts,
            'adminResources' => [
                'legislative_houses' => ['national_assembly', 'senate'],
                'default_invitation_message' => 'Greetings, join the participation platform to collaborate on upcoming bills.',
                'alert_severities' => ['info', 'warning', 'critical'],
            ],
        ]);
    }
}
