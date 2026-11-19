<?php

namespace App\Services;

use App\Mail\AccessLinkMail;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * Access link na ipinapadala sa Gmail ng user:
 *  - Cashier: isang click, diretso sa Dashboard (single-use, 72 oras).
 *  - Admin: link para itakda ang sariling password.
 */
class AccessLink
{
    public const TTL_HOURS = 72;

    /** Gumawa ng bagong token (at bawiin ang mga lumang hindi pa nagagamit). Ibinabalik ang mismong token. */
    public static function issue(User $user): string
    {
        DB::table('access_tokens')->where('user_id', $user->id)->whereNull('used_at')->delete();

        $token = Str::random(64);

        DB::table('access_tokens')->insert([
            'user_id'    => $user->id,
            'token_hash' => hash('sha256', $token),
            'expires_at' => now()->addHours(self::TTL_HOURS),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $token;
    }

    /** Gumawa ng token at i-email ito sa user. Nagta-throw kung pumalya ang pagpapadala. */
    public static function send(User $user): void
    {
        $token = self::issue($user);

        try {
            Mail::to($user->email)->send(new AccessLinkMail($user, route('access.show', $token), self::TTL_HOURS));
        } catch (\Throwable $e) {
            DB::table('access_tokens')->where('user_id', $user->id)->whereNull('used_at')->delete();
            throw $e;
        }
    }

    /** Hanapin ang token na valid pa (hindi pa nagagamit at hindi pa expired). */
    public static function find(string $token): ?object
    {
        return DB::table('access_tokens')
            ->where('token_hash', hash('sha256', $token))
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->first();
    }

    public static function markUsed(int $id): void
    {
        DB::table('access_tokens')->where('id', $id)->update(['used_at' => now(), 'updated_at' => now()]);
    }
}
