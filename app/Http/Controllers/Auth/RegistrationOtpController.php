<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\RegistrationOtpNotification;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class RegistrationOtpController extends Controller
{
    public function create(Request $request): Response|RedirectResponse
    {
        $user = $this->pendingUser($request);

        if (! $user instanceof User) {
            return to_route('register');
        }

        return Inertia::render('auth/RegisterVerify', [
            'contact' => [
                'email' => $user->email,
                'phone' => $this->maskPhone($user->phone),
            ],
            'status' => session('status'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'otp' => ['required', 'digits:6'],
        ]);

        $user = $this->pendingUser($request, true);

        if ($user->otp_verified_at !== null) {
            return to_route('login')
                ->with('status', 'Your account is already verified. Please sign in.')
                ->with('flash', [
                    'status' => 'info',
                    'title' => 'Already verified',
                    'message' => 'Your account is active. You can sign in immediately.',
                ]);
        }

        if ($user->otp_expires_at !== null && $user->otp_expires_at->isPast()) {
            throw ValidationException::withMessages([
                'otp' => 'The verification code has expired. Please request a new one.',
            ]);
        }

        $submittedOtp = $request->string('otp')->toString();

        if (! Hash::check($submittedOtp, (string) $user->otp_code)) {
            throw ValidationException::withMessages([
                'otp' => 'The verification code is incorrect.',
            ]);
        }

        $user->forceFill([
            'otp_verified_at' => now(),
            'phone_verified_at' => now(),
            'email_verified_at' => now(),
            'otp_code' => null,
            'otp_expires_at' => null,
            'is_verified' => true,
        ])->save();

        event(new Registered($user));

        Session::forget('pending_registration_user_id');

        return to_route('login')
            ->with('status', 'Verification successful. You can now sign in.')
            ->with('flash', [
                'status' => 'success',
                'title' => 'Verification complete',
                'message' => 'Your account is ready. Sign in to continue.',
            ]);
    }

    public function resend(Request $request): RedirectResponse
    {
        $user = $this->pendingUser($request, true);

        if ($user->otp_verified_at !== null) {
            return to_route('login')
                ->with('status', 'Your account is already verified. Please sign in.')
                ->with('flash', [
                    'status' => 'info',
                    'title' => 'Already verified',
                    'message' => 'Your account is active. You can sign in immediately.',
                ]);
        }

        $otp = (string) random_int(100000, 999999);

        $user->forceFill([
            'otp_code' => Hash::make($otp),
            'otp_expires_at' => now()->addMinutes(10),
        ])->save();

        $user->notify(new RegistrationOtpNotification($otp));

        return back()
            ->with('status', 'We have sent you a fresh verification code.')
            ->with('flash', [
                'status' => 'info',
                'title' => 'Verification code sent',
                'message' => 'We have sent you a new verification code. Check your phone or email.',
            ]);
    }

    private function pendingUser(Request $request, bool $failIfMissing = false): ?User
    {
        $userId = Session::get('pending_registration_user_id');

        if ($userId === null) {
            if ($failIfMissing) {
                throw ValidationException::withMessages([
                    'otp' => 'We could not find a registration in progress. Please start again.',
                ]);
            }

            return null;
        }

        $user = User::query()->find($userId);

        if ($user instanceof User) {
            return $user;
        }

        Session::forget('pending_registration_user_id');

        if ($failIfMissing) {
            throw ValidationException::withMessages([
                'otp' => 'We could not find a registration in progress. Please start again.',
            ]);
        }

        return null;
    }

    private function maskPhone(?string $phone): ?string
    {
        if ($phone === null) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $phone);

        if ($digits === null || strlen($digits) < 4) {
            return $phone;
        }

        $visible = substr($digits, -3);

        return str_repeat('•', max(strlen($digits) - 3, 0)).$visible;
    }
}
