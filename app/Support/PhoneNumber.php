<?php

namespace App\Support;

use Illuminate\Validation\ValidationException;

final class PhoneNumber
{
    public static function normalize(string $value): string
    {
        $digits = preg_replace('/[\s+\-]/u', '', $value) ?? '';
        if (str_starts_with($digits, '0')) {
            $digits = '62'.substr($digits, 1);
        }
        if (! preg_match('/\A62[0-9]{8,13}\z/', $digits)) {
            throw ValidationException::withMessages(['customer_phone' => 'Nomor HP harus berawalan 62 dan terdiri dari 10–15 digit.']);
        }

        return $digits;
    }
}
