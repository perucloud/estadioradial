<?php

namespace App\Support;

use Illuminate\Http\Request;

class MathCaptcha
{
    private const SESSION_KEY = 'admin_login_captcha';

    /**
     * @return array{question: string, expires_at: int}
     */
    public function issue(Request $request): array
    {
        $minimum = (int) config('admin.captcha.minimum', 1);
        $maximum = (int) config('admin.captcha.maximum', 12);
        $left = random_int($minimum, $maximum);
        $right = random_int($minimum, $maximum);
        $expiresAt = now()->addSeconds((int) config('admin.captcha.expires_seconds', 300))->timestamp;

        $challenge = [
            'question' => "{$left} + {$right}",
            'answer' => $left + $right,
            'expires_at' => $expiresAt,
        ];

        $request->session()->put(self::SESSION_KEY, $challenge);

        return [
            'question' => $challenge['question'],
            'expires_at' => $expiresAt,
        ];
    }

    /**
     * @return array{question: string, expires_at: int}
     */
    public function challenge(Request $request): array
    {
        $challenge = $request->session()->get(self::SESSION_KEY);

        if (! is_array($challenge) || ($challenge['expires_at'] ?? 0) < now()->timestamp) {
            return $this->issue($request);
        }

        return [
            'question' => (string) $challenge['question'],
            'expires_at' => (int) $challenge['expires_at'],
        ];
    }

    public function verifyAndConsume(Request $request, mixed $answer): bool
    {
        $challenge = $request->session()->pull(self::SESSION_KEY);

        if (! is_array($challenge) || ($challenge['expires_at'] ?? 0) < now()->timestamp) {
            return false;
        }

        return filter_var($answer, FILTER_VALIDATE_INT) !== false
            && (int) $answer === (int) $challenge['answer'];
    }
}
