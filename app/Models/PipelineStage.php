<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'color', 'type', 'sort_order', 'requires_proposal'])]
class PipelineStage extends Model
{
    public const TYPES = ['open' => 'En curso', 'won' => 'Concretado', 'lost' => 'Descartado'];

    protected function casts(): array
    {
        return ['requires_proposal' => 'boolean'];
    }

    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class, 'stage_id');
    }

    /** Etapa en la que ingresan los leads nuevos: la primera según el orden. */
    public static function initial(): ?self
    {
        return static::orderBy('sort_order')->orderBy('id')->first();
    }
}
