<?php

namespace App\Services\Email;

use App\Models\EmailTemplate;
use App\Models\User;

/** Correos del sistema a usuarios del CRM: bienvenida con acceso y recuperación de contraseña (por Resend). */
class UserMailer
{
    public const WELCOME = 'bienvenida-usuario';

    public const RESET = 'recuperar-password';

    public function __construct(private EmailService $emails) {}

    public function sendWelcome(User $user, string $plainPassword): void
    {
        $this->emails->queueTemplate($this->welcomeTemplate(), $user->email, $user->name, [
            'first_name' => explode(' ', trim($user->name))[0] ?? '',
            'email' => $user->email,
            'password' => $plainPassword,
            'role' => $user->role?->name ?? '',
            'login_url' => url('/login'),
            '_redact' => ['password'],
        ]);
    }

    public function sendReset(User $user, string $token): void
    {
        $this->emails->queueTemplate($this->resetTemplate(), $user->email, $user->name, [
            'first_name' => explode(' ', trim($user->name))[0] ?? '',
            'reset_url' => url(route('password.reset', ['token' => $token, 'email' => $user->email], false)),
            'expires_minutes' => (int) config('auth.passwords.users.expire', 60),
            '_redact' => ['reset_url'],
        ]);
    }

    public function welcomeTemplate(): EmailTemplate
    {
        return app(DefaultTemplates::class)->ensure(self::WELCOME);
    }

    public function resetTemplate(): EmailTemplate
    {
        return app(DefaultTemplates::class)->ensure(self::RESET);
    }
}
