<?php

namespace App\Services\Email;

use App\Models\EmailTemplate;
use App\Models\User;
use App\Services\Ai\BrandedEmailDesign;

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
        return EmailTemplate::firstOrCreate(['slug' => self::WELCOME], [
            'name' => 'Bienvenida de usuario',
            'description' => 'Se envía al crear un usuario del CRM, con su correo, contraseña inicial y enlace de acceso.',
            'category' => 'transactional',
            'subject' => 'Tu acceso al CRM de Quiebre',
            'preheader' => 'Aquí tienes tus datos para ingresar',
            'editor' => 'html',
            'html' => $this->layout(<<<'HTML'
<tr><td style="padding:16px 32px 0"><h1 style="margin:0;font-size:26px;line-height:1.25;color:#393939">¡Bienvenido/a{{#if first_name}}, {{ first_name }}{{/if}}!</h1></td></tr>
<tr><td style="padding:12px 32px 0;font-size:16px;line-height:1.6">Te creamos un acceso al CRM de Quiebre{{#if role}} con el rol <strong>{{ role }}</strong>{{/if}}. Estos son tus datos para ingresar:</td></tr>
<tr><td style="padding:16px 32px 0"><table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="background:#FAFAFA;border-radius:12px"><tr><td style="padding:16px 20px;font-size:15px;line-height:1.8">
<span style="color:#8A8A8A">Usuario (correo)</span><br><strong>{{ email }}</strong><br>
<span style="color:#8A8A8A">Contraseña inicial</span><br><strong style="font-family:Consolas,Menlo,monospace;font-size:16px">{{ password }}</strong>
</td></tr></table></td></tr>
<tr><td align="center" style="padding:24px 32px 8px"><a href="{{ login_url }}" style="display:inline-block;background:#FF5300;color:#FFFFFF;text-decoration:none;font-weight:bold;font-size:16px;padding:16px 32px;border-radius:999px">Ingresar al CRM</a></td></tr>
<tr><td style="padding:8px 32px 28px;font-size:13px;line-height:1.5;color:#8A8A8A;text-align:center">Por seguridad, cambia tu contraseña después de ingresar (Configuración → Seguridad). Si no esperabas este correo, ignóralo.</td></tr>
HTML),
            'is_active' => true,
            'variables' => [
                ['key' => 'first_name', 'label' => 'Nombre', 'default' => '', 'sample' => 'María'],
                ['key' => 'email', 'label' => 'Correo de acceso', 'default' => '', 'sample' => 'maria@quiebre.cl'],
                ['key' => 'password', 'label' => 'Contraseña inicial', 'default' => '', 'sample' => 'Qb-Ejemplo-123'],
                ['key' => 'role', 'label' => 'Rol', 'default' => '', 'sample' => 'Comercial'],
                ['key' => 'login_url', 'label' => 'Enlace de acceso', 'default' => '', 'sample' => 'https://crm.quiebre.cl/login'],
            ],
        ]);
    }

    public function resetTemplate(): EmailTemplate
    {
        return EmailTemplate::firstOrCreate(['slug' => self::RESET], [
            'name' => 'Recuperar contraseña',
            'description' => 'Enlace para restablecer la contraseña (flujo «¿Olvidaste tu contraseña?»).',
            'category' => 'transactional',
            'subject' => 'Restablece tu contraseña del CRM de Quiebre',
            'preheader' => 'Usa este enlace para crear una nueva contraseña',
            'editor' => 'html',
            'html' => $this->layout(<<<'HTML'
<tr><td style="padding:16px 32px 0"><h1 style="margin:0;font-size:26px;line-height:1.25;color:#393939">Restablece tu contraseña</h1></td></tr>
<tr><td style="padding:12px 32px 0;font-size:16px;line-height:1.6">Hola{{#if first_name}} {{ first_name }}{{/if}}, recibimos una solicitud para restablecer la contraseña de tu acceso al CRM de Quiebre.</td></tr>
<tr><td align="center" style="padding:24px 32px 8px"><a href="{{ reset_url }}" style="display:inline-block;background:#FF5300;color:#FFFFFF;text-decoration:none;font-weight:bold;font-size:16px;padding:16px 32px;border-radius:999px">Crear nueva contraseña</a></td></tr>
<tr><td style="padding:8px 32px 0;font-size:13px;line-height:1.5;color:#8A8A8A;text-align:center">El enlace vence en {{ expires_minutes }} minutos y solo se puede usar una vez.</td></tr>
<tr><td style="padding:12px 32px 28px;font-size:13px;line-height:1.5;color:#8A8A8A;text-align:center">Si no lo solicitaste, ignora este correo: tu contraseña actual sigue funcionando.</td></tr>
HTML),
            'is_active' => true,
            'variables' => [
                ['key' => 'first_name', 'label' => 'Nombre', 'default' => '', 'sample' => 'María'],
                ['key' => 'reset_url', 'label' => 'Enlace para restablecer', 'default' => '', 'sample' => 'https://crm.quiebre.cl/reset-password/token'],
                ['key' => 'expires_minutes', 'label' => 'Minutos de vigencia', 'default' => '60', 'sample' => '60'],
            ],
        ]);
    }

    private function layout(string $body): string
    {
        $logo = BrandedEmailDesign::logoUrl();
        $address = e((string) MailSettings::footerAddress());

        return <<<HTML
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
<body style="margin:0;background:#F4F4F4;font-family:Arial,Helvetica,sans-serif;color:#393939">
<table width="100%" cellpadding="0" cellspacing="0" role="presentation"><tr><td align="center" style="padding:24px 12px">
<table width="600" cellpadding="0" cellspacing="0" role="presentation" style="max-width:100%;background:#FFFFFF;border-radius:16px;overflow:hidden">
<tr><td align="center" style="padding:28px 32px 8px"><img src="{$logo}" width="150" alt="Quiebre" style="display:inline-block;border:0;height:auto"></td></tr>
{$body}
<tr><td align="center" style="padding:20px 32px;background:#FAFAFA;font-size:12px;line-height:1.5;color:#8A8A8A">Quiebre · Inteligencia inmobiliaria · <a href="https://www.quiebre.cl" style="color:#FF5300;text-decoration:none">quiebre.cl</a><br>{$address}</td></tr>
</table></td></tr></table></body></html>
HTML;
    }
}
