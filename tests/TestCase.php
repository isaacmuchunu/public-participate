<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Session;

abstract class TestCase extends BaseTestCase
{
    protected function startSession(): void
    {
        if (session()->isStarted()) {
            return;
        }

        Session::start();
    }

    protected function withCsrf(array $payload = []): array
    {
        $this->startSession();

        return [
            '_token' => Session::token(),
            ...$payload,
        ];
    }
}
