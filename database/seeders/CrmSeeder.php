<?php

namespace Database\Seeders;

use App\Models\LeadSource;
use App\Models\PipelineStage;
use Illuminate\Database\Seeder;

class CrmSeeder extends Seeder
{
    /** Etapas y orígenes iniciales; luego se administran desde el CRM. */
    public function run(): void
    {
        if (PipelineStage::count() === 0) {
            $stages = [
                ['Ingreso', '#1AA0E4', 'open'],
                ['Contactado', '#6419DB', 'open'],
                ['Agendado', '#DF1E79', 'open'],
                ['Propuesta', '#FF5300', 'open'],
                ['Concretado', '#0D9F85', 'won'],
                ['Descartado', '#8A8A8A', 'lost'],
            ];

            foreach ($stages as $i => [$name, $color, $type]) {
                PipelineStage::create(['name' => $name, 'color' => $color, 'type' => $type, 'sort_order' => $i]);
            }
        }

        if (LeadSource::count() === 0) {
            $sources = [
                ['Sitio web', '#1AA0E4', 'globe', false],
                ['Landing page', '#6419DB', 'layout-template', false],
                ['Meta Ads', '#4A8CFF', 'megaphone', false],
                ['Google Ads', '#0D9F85', 'search', false],
                ['WhatsApp', '#25D366', 'message-circle', false],
                ['Referido', '#DF1E79', 'handshake', false],
                ['Manual', '#707070', 'pencil', true],
            ];

            foreach ($sources as $i => [$name, $color, $icon, $system]) {
                LeadSource::create([
                    'name' => $name,
                    'slug' => LeadSource::uniqueSlug($name),
                    'color' => $color,
                    'icon' => $icon,
                    'api_key' => LeadSource::generateKey(),
                    'is_system' => $system,
                    'sort_order' => $i,
                ]);
            }
        }
    }
}
