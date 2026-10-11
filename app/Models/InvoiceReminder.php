<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['invoice_id', 'kind', 'offset_days', 'skipped', 'recipients', 'sent_at'])]
class InvoiceReminder extends Model
{
    protected function casts(): array
    {
        return ['sent_at' => 'datetime', 'skipped' => 'boolean'];
    }
}
