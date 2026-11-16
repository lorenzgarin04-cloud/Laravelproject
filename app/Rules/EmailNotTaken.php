<?php

namespace App\Rules;

use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/** Unique na email na hindi nakadepende sa capital letters (Juan@Test.ph == juan@test.ph). */
class EmailNotTaken implements ValidationRule
{
    public function __construct(private ?int $ignoreUserId = null) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $exists = User::query()
            ->whereRaw('lower(email) = ?', [mb_strtolower(trim((string) $value))])
            ->when($this->ignoreUserId, fn ($q) => $q->where('id', '!=', $this->ignoreUserId))
            ->exists();

        if ($exists) {
            $fail('The email has already been taken.');
        }
    }
}
