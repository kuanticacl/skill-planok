<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['contact_list_id', 'email', 'name', 'data'])]
class ContactListEntry extends Model
{
    protected function casts(): array
    {
        return ['data' => 'array'];
    }

    public function list(): BelongsTo
    {
        return $this->belongsTo(ContactList::class, 'contact_list_id');
    }
}
