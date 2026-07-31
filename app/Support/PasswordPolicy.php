<?php

namespace App\Support;

use Illuminate\Validation\Rules\Password;

class PasswordPolicy
{
    public static function rule(): Password
    {
        $settings = PortalSettings::get('system.security');
        $rule = Password::min((int) $settings['password_min'])->letters();

        if ($settings['password_mixed_case']) {
            $rule->mixedCase();
        }
        if ($settings['password_numbers']) {
            $rule->numbers();
        }
        if ($settings['password_symbols']) {
            $rule->symbols();
        }

        return $rule;
    }
}
