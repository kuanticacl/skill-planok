<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'description', 'created_by'])]
class ContactList extends Model
{
    public function entries(): HasMany
    {
        return $this->hasMany(ContactListEntry::class);
    }
}
