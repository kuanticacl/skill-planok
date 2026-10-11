<?php

namespace App\Services\Billing;

use App\Models\Client;
use App\Models\Role;
use App\Models\User;

/** Alta de accesos al portal de clientes: contraseña generada y correo de bienvenida. */
class PortalAccess
{
    public function __construct(private BillingMailer $mailer) {}

    /**
     * @param  array{name: string, email: string, phone?: ?string, lead_id?: ?int}  $data
     * @return array{user: User, mailed: bool}
     */
    public function create(Client $client, array $data): array
    {
        $password = self::generatePassword();
        $user = new User([...$data, 'password' => $password, 'role_id' => Role::portal()->id, 'client_id' => $client->id, 'is_active' => true, 'must_change_password' => true, 'portal_invited_at' => now()]);
        $user->email_verified_at = now();
        $user->save();
        $user->setRelation('client', $client);

        return ['user' => $user, 'mailed' => $this->welcome($user, $password)];
    }

    /** Contraseña nueva + correo; las sesiones abiertas se cierran solas por el cambio de contraseña. */
    public function reset(User $user): bool
    {
        $password = self::generatePassword();
        $user->forceFill(['password' => $password, 'must_change_password' => true, 'portal_invited_at' => now()])->save();

        return $this->welcome($user, $password);
    }

    private function welcome(User $user, string $password): bool
    {
        try {
            $this->mailer->sendWelcome($user, $password);

            return true;
        } catch (\Throwable $e) {
            report($e);

            return false;
        }
    }

    /** 12 caracteres sin letras ambiguas (0/O, 1/l/I), con al menos una mayúscula, minúscula y dígito. */
    public static function generatePassword(): string
    {
        $sets = ['ABCDEFGHJKMNPQRSTUVWXYZ', 'abcdefghjkmnpqrstuvwxyz', '23456789'];
        $chars = array_map(fn ($s) => $s[random_int(0, strlen($s) - 1)], $sets);
        $all = implode('', $sets);
        while (count($chars) < 12) {
            $chars[] = $all[random_int(0, strlen($all) - 1)];
        }
        shuffle($chars);

        return implode('', $chars);
    }
}
