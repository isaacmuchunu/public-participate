<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterCitizenRequest;
use App\Models\County;
use App\Models\User;
use App\Models\Ward;
use App\Notifications\RegistrationOtpNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Show the registration page.
     */
    public function create(): Response
    {
        $counties = County::query()
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        return Inertia::render('auth/Register', [
            'counties' => $counties,
        ]);
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(RegisterCitizenRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $ward = Ward::query()
            ->with('constituency.county')
            ->findOrFail($data['ward_id']);

        $otp = (string) random_int(100000, 999999);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'password' => Hash::make($data['password']),
            'role' => 'citizen',
            'county_id' => $ward->constituency->county_id,
            'constituency_id' => $ward->constituency_id,
            'ward_id' => $ward->id,
            'national_id' => $data['national_id'],
            'is_verified' => false,
            'otp_code' => Hash::make($otp),
            'otp_expires_at' => now()->addMinutes(10),
        ]);

        $user->notify(new RegistrationOtpNotification($otp));

        session([
            'pending_registration_user_id' => $user->id,
        ]);

        return to_route('register.verify')
            ->with('status', 'We have sent a one-time passcode to your phone and email. Enter it below to finish sign up.')
            ->with('flash', [
                'status' => 'success',
                'title' => 'Account created',
                'message' => 'We have sent a verification code to your contacts. Enter it to finish sign up.',
            ]);
    }
}
