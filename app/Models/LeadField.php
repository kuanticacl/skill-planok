<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['label', 'key', 'type', 'options', 'is_required', 'show_on_card', 'is_active', 'sort_order'])]
class LeadField extends Model
{
    public const TYPES = [
        'text' => 'Texto corto',
        'textarea' => 'Texto largo',
        'number' => 'Número',
        'email' => 'Correo',
        'phone' => 'Teléfono',
        'url' => 'URL',
        'date' => 'Fecha',
        'select' => 'Lista de opciones',
        'checkbox' => 'Sí / No',
    ];

    protected function casts(): array
    {
        return [
            'options' => 'array',
            'is_required' => 'boolean',
            'show_on_card' => 'boolean',
            'is_active' => 'boolean',
        ];
    }
}
