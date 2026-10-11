<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property int|null $role_id
 * @property bool $is_active
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'email', 'phone', 'job_title', 'password', 'role_id', 'client_id', 'lead_id', 'is_active', 'must_change_password', 'last_login_at', 'portal_invited_at'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail, PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

    /** Recuperación de contraseña por Resend con la plantilla de marca (en vez de la notificación por defecto). */
    public function sendPasswordResetNotification($token): void
    {
        // Sin Resend configurado se usa el correo estándar de Laravel (mailer del .env) para no dejar al usuario sin recuperación.
        if (! \App\Services\Email\MailSettings::apiKey()) {
            parent::sendPasswordResetNotification($token);

            return;
        }

        app(\App\Services\Email\UserMailer::class)->sendReset($this, (string) $token);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'must_change_password' => 'boolean',
            'portal_invited_at' => 'datetime',
            'last_login_at' => 'datetime',
            /* @chisel-2fa */
            'two_factor_confirmed_at' => 'datetime',
            /* @end-chisel-2fa */
        ];
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /** Empresa a la que pertenece un acceso del portal de clientes (null en usuarios del equipo). */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class)->withTrashed();
    }

    /** Los usuarios del portal son contactos de una empresa: no usan el CRM interno. */
    public function isPortal(): bool
    {
        return $this->client_id !== null;
    }

    public function isAdmin(): bool
    {
        return (bool) $this->role?->isAdmin();
    }

    public function hasPermission(string $permission): bool
    {
        return $this->is_active && (bool) $this->role?->allows($permission);
    }

    /** @return array<int, string> */
    public function permissionKeys(): array
    {
        return $this->is_active ? ($this->role?->effectivePermissions() ?? []) : [];
    }
}
