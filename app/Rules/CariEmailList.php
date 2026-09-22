<?php

namespace App\Rules;

use App\Models\Cari;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

final class CariEmailList implements ValidationRule
{
    public const MAX_LENGTH = 1000;

    public const MAX_ADDRESSES = 10;

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || (is_string($value) && trim($value) === '')) {
            return;
        }

        if (! is_string($value)) {
            $fail('E-posta listesi metin olmalıdır.');

            return;
        }

        $emails = Cari::parseEmails($value);
        if ($emails === []) {
            $fail('Geçerli bir e-posta girin.');

            return;
        }

        if (count($emails) > self::MAX_ADDRESSES) {
            $fail('En fazla '.self::MAX_ADDRESSES.' e-posta adresi girilebilir.');

            return;
        }

        foreach ($emails as $email) {
            if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
                $fail($email.' geçerli bir e-posta değil.');

                return;
            }
        }
    }
}
