<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['date', 'value'])]
class UfValue extends Model
{
    protected $primaryKey = 'date';

    public $incrementing = false;

    protected $keyType = 'string';

    protected function casts(): array
    {
        return ['date' => 'date:Y-m-d', 'value' => 'float'];
    }
}
