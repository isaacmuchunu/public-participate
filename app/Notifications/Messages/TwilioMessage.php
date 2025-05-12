<?php

namespace App\Notifications\Messages;

class TwilioMessage
{
    public function __construct(
        public string $content,
        public ?string $statusCallback = null,
    ) {}

    public static function make(string $content): self
    {
        return new self($content);
    }

    public function toArray(): array
    {
        return array_filter([
            'Body' => $this->content,
            'StatusCallback' => $this->statusCallback,
        ], fn ($value) => $value !== null);
    }
}
