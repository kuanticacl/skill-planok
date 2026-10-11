<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property array<int, string>|null $permissions
 * @property bool $is_system
 */
#[Fillable(['name', 'slug', 'description', 'permissions', 'is_system'])]
class Role extends Model
{
    protected function casts(): array
    {
        return [
            'permissions' => 'array',
            'is_system' => 'boolean',
        ];
    }

    public const PORTAL_SLUG = 'cliente';

    /** Rol de los accesos al portal de clientes: no tiene permisos del CRM. */
    public static function portal(): self
    {
        return static::firstOrCreate(['slug' => self::PORTAL_SLUG], ['name' => 'Cliente (portal)', 'description' => 'Acceso al portal de clientes: ve sus propuestas, servicios y facturas.', 'permissions' => [], 'is_system' => true]);
    }

    public function scopeStaff($query)
    {
        return $query->where('slug', '!=', self::PORTAL_SLUG);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function isAdmin(): bool
    {
        return $this->slug === config('permissions.admin_role');
    }

    public function allows(string $permission): bool
    {
        return $this->isAdmin() || in_array($permission, $this->permissions ?? [], true);
    }

    /** @return array<int, string> Claves efectivas (el admin recibe todas). */
    public function effectivePermissions(): array
    {
        return $this->isAdmin() ? self::allPermissionKeys() : array_values($this->permissions ?? []);
    }

    /** @return array<int, string> */
    public static function allPermissionKeys(): array
    {
        return collect(config('permissions.groups'))
            ->flatMap(fn (array $group) => array_keys($group['permissions']))
            ->values()
            ->all();
    }
}
