<?php

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('registration screen can be rendered', function () {
    $response = $this->get(route('register'));

    $response->assertStatus(200);
});

test('new users can register', function () {
    $ward = \App\Models\Ward::factory()->create();
    $constituency = $ward->constituency;
    $county = $constituency->county;

    $response = $this->post(route('register.store'), $this->withCsrf([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'phone' => '0712345678',
        'national_id' => '12345678',
        'county_id' => $county->id,
        'constituency_id' => $constituency->id,
        'ward_id' => $ward->id,
        'password' => 'password',
        'password_confirmation' => 'password',
    ]));

    $response->assertRedirect(route('register.verify'));
    $this->assertGuest();
});
