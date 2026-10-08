<?php

namespace App\Services;

use App\Models\UfValue;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;

/**
 * Valor de la UF desde https://findic.cl/api/uf. Se guarda el historial en la base: así las propuestas
 * usan la UF del día aunque la API esté caída, y cada propuesta conserva el valor con el que se emitió.
 */
class UfService
{
    private const ENDPOINT = 'https://findic.cl/api/uf';

    /** UF vigente hoy (o la última conocida). @return array{value: float, date: string}|null */
    public function today(): ?array
    {
        $today = now('America/Santiago')->toDateString();

        $row = UfValue::find($today);
        if (! $row) {
            $this->sync();
            $row = UfValue::find($today) ?? UfValue::where('date', '<=', $today)->orderByDesc('date')->first();
        }

        return $row ? ['value' => (float) $row->value, 'date' => Carbon::parse($row->date)->toDateString()] : null;
    }

    /** UF de una fecha (la más cercana anterior si ese día no hay dato). */
    public function forDate(Carbon|string $date): ?float
    {
        $d = Carbon::parse($date)->toDateString();

        return UfValue::where('date', '<=', $d)->orderByDesc('date')->value('value')
            ?? ($this->fetchDate($d));
    }

    /** Descarga la serie reciente y la guarda. Devuelve cuántos días quedaron registrados. */
    public function sync(): int
    {
        try {
            $series = Http::timeout(8)->retry(1, 300)->acceptJson()->get(self::ENDPOINT)->throw()->json('serie', []);
        } catch (\Throwable $e) {
            report($e);

            return 0;
        }

        $n = 0;
        foreach ($series as $row) {
            if (isset($row['fecha'], $row['valor']) && is_numeric($row['valor'])) {
                UfValue::updateOrCreate(['date' => substr($row['fecha'], 0, 10)], ['value' => round((float) $row['valor'], 2)]);
                $n++;
            }
        }

        return $n;
    }

    private function fetchDate(string $date): ?float
    {
        try {
            $r = Http::timeout(8)->acceptJson()->get(self::ENDPOINT.'/'.Carbon::parse($date)->format('d-m-Y'))->throw()->json('serie.0');
            if (isset($r['valor']) && is_numeric($r['valor'])) {
                UfValue::updateOrCreate(['date' => $date], ['value' => round((float) $r['valor'], 2)]);

                return (float) $r['valor'];
            }
        } catch (\Throwable $e) {
            report($e);
        }

        return null;
    }
}
