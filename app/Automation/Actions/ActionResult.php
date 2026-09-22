<?php

namespace App\Automation\Actions;

final class ActionResult
{
    public function __construct(
        public readonly bool $ok,
        public readonly bool $skipped = false,
        public readonly ?string $message = null,
        public readonly ?string $toEmail = null,
    ) {
    }

    public static function success(?string $toEmail = null, ?string $message = null): self
    {
        return new self(true, false, $message, $toEmail);
    }

    public static function skipped(string $message): self
    {
        return new self(true, true, $message);
    }

    public static function failed(string $message): self
    {
        return new self(false, false, $message);
    }
}
