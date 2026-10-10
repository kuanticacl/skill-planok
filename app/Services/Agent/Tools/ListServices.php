<?php

namespace App\Services\Agent\Tools;

use App\Models\Service;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Tools\Request;

class ListServices extends CrmTool
{
    protected ?string $permission = 'services.view';

    public function name(): string
    {
        return 'list_services';
    }

    public function description(): string
    {
        return 'Lista el catálogo de servicios y tarifas (id, nombre, categoría, modalidad, moneda, precio neto). Úsalo para armar propuestas con servicios existentes (service_id) en vez de inventar precios.';
    }

    public function schema(JsonSchema $schema): array
    {
        return ['query' => $schema->string()->description('Filtro por nombre o categoría (opcional).')];
    }

    protected function run(Request $r): array|string
    {
        $t = trim((string) ($r['query'] ?? ''));
        $rows = Service::where('is_active', true)->when($t !== '', fn ($q) => $q->where(fn ($w) => $w->where('name', 'like', "%$t%")->orWhere('category', 'like', "%$t%")))->orderBy('sort_order')->limit(40)->get();
        $this->ctx->record($this->name(), 'Consultó el catálogo de servicios');

        return $rows->map(fn (Service $s) => ['id' => $s->id, 'name' => $s->name, 'category' => $s->category, 'billing' => $s->billing, 'currency' => $s->currency, 'price' => $s->price, 'description' => mb_substr(strip_tags((string) $s->description), 0, 160)])->all() ?: 'Catálogo vacío.';
    }
}
