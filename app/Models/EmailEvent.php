<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['email_message_id', 'type', 'data', 'occurred_at'])]
class EmailEvent extends Model
{
    protected function casts(): array
    {
        return ['data' => 'array', 'occurred_at' => 'datetime'];
    }
}
