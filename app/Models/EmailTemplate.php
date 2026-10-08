<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['name', 'slug', 'description', 'category', 'subject', 'preheader', 'editor', 'html', 'text', 'design', 'variables', 'is_active', 'created_by', 'updated_by'])]
class EmailTemplate extends Model
{
    use SoftDeletes;

    public const CATEGORIES = ['marketing' => 'Marketing / boletín', 'transactional' => 'Transaccional / automatización'];

    protected function casts(): array
    {
        return [
            'design' => 'array',
            'variables' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function campaigns(): HasMany
    {
        return $this->hasMany(Campaign::class, 'template_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
