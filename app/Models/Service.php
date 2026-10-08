<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['name', 'category', 'description', 'deliverables', 'billing', 'unit', 'price', 'is_active', 'sort_order'])]
class Service extends Model
{
    use SoftDeletes;

    public const BILLING = ['one_time' => 'Pago único', 'monthly' => 'Mensual'];

    protected function casts(): array
    {
        return ['deliverables' => 'array', 'is_active' => 'boolean', 'price' => 'integer'];
    }
}
