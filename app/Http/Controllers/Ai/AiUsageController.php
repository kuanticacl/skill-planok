<?php

namespace App\Http\Controllers\Ai;

use App\Http\Controllers\Controller;
use App\Models\AiRun;
use App\Models\Setting;
use App\Services\Ai\AiPricing;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/** Monitor de consumo de IA: llamadas, tokens, costo estimado y fallos. */
class AiUsageController extends Controller
{
    public function index(Request $request, AiPricing $pricing): Response
    {
        $days = in_array((int) $request->query('days'), [7, 30, 90], true) ? (int) $request->query('days') : 30;
        $provider = $request->query('provider') ?: null;
        $from = now()->startOfDay()->subDays($days - 1);

        $base = fn () => AiRun::query()->where('ai_runs.created_at', '>=', $from)->when($provider, fn ($q) => $q->where('ai_runs.provider', $provider));

        // Por día + modelo: el costo depende del modelo, así que se calcula en PHP.
        $rows = $base()->selectRaw("date(created_at) as d, provider, model, count(*) as calls, sum(case when status = 'error' then 1 else 0 end) as errors, sum(input_tokens) as i, sum(output_tokens) as o, sum(duration_ms) as ms")
            ->groupBy('d', 'provider', 'model')->get();

        $daily = collect(range(0, $days - 1))->mapWithKeys(fn ($n) => [$from->copy()->addDays($n)->toDateString() => ['date' => $from->copy()->addDays($n)->toDateString(), 'calls' => 0, 'errors' => 0, 'input_tokens' => 0, 'output_tokens' => 0, 'cost' => 0.0]]);
        $byModel = [];
        $unpriced = [];

        foreach ($rows as $r) {
            $cost = $pricing->cost($r->provider, $r->model, (int) $r->i, (int) $r->o);
            $d = $daily[Carbon::parse($r->d)->toDateString()] ?? null;
            if ($d) {
                $daily[$r->d] = [...$d, 'calls' => $d['calls'] + $r->calls, 'errors' => $d['errors'] + $r->errors, 'input_tokens' => $d['input_tokens'] + (int) $r->i, 'output_tokens' => $d['output_tokens'] + (int) $r->o, 'cost' => $d['cost'] + ($cost ?? 0)];
            }
            $key = AiPricing::key($r->provider, $r->model);
            $m = $byModel[$key] ?? ['key' => $key, 'provider' => $r->provider, 'model' => $r->model, 'calls' => 0, 'errors' => 0, 'input_tokens' => 0, 'output_tokens' => 0, 'ms' => 0, 'cost' => 0.0];
            $byModel[$key] = [...$m, 'calls' => $m['calls'] + $r->calls, 'errors' => $m['errors'] + $r->errors, 'input_tokens' => $m['input_tokens'] + (int) $r->i, 'output_tokens' => $m['output_tokens'] + (int) $r->o, 'ms' => $m['ms'] + (int) $r->ms, 'cost' => $m['cost'] + ($cost ?? 0)];
        }

        $models = collect($byModel)->map(function ($m) use ($pricing) {
            $price = $pricing->find($m['provider'], $m['model']);

            return [...$m, 'avg_ms' => $m['calls'] ? (int) round($m['ms'] / $m['calls']) : 0, 'price' => $price, 'priced' => $price !== null];
        })->sortByDesc('cost')->values();

        $features = $base()->selectRaw("feature, count(*) as calls, sum(case when status = 'error' then 1 else 0 end) as errors, sum(input_tokens) as i, sum(output_tokens) as o, avg(duration_ms) as ms")
            ->groupBy('feature')->orderByDesc('calls')->get()->map(fn ($f) => ['feature' => $f->feature, 'calls' => (int) $f->calls, 'errors' => (int) $f->errors, 'input_tokens' => (int) $f->i, 'output_tokens' => (int) $f->o, 'avg_ms' => (int) $f->ms]);

        $users = $base()->leftJoin('users', 'users.id', '=', 'ai_runs.user_id')
            ->selectRaw("coalesce(users.name, 'Sistema') as name, count(*) as calls, sum(ai_runs.input_tokens + ai_runs.output_tokens) as tokens")
            ->groupBy('users.name')->orderByDesc('calls')->limit(10)->get()->map(fn ($u) => ['name' => $u->name, 'calls' => (int) $u->calls, 'tokens' => (int) $u->tokens]);

        $errors = $base()->where('ai_runs.status', 'error')->leftJoin('users', 'users.id', '=', 'ai_runs.user_id')->latest('ai_runs.id')->limit(25)
            ->get(['ai_runs.id', 'ai_runs.feature', 'ai_runs.provider', 'ai_runs.model', 'ai_runs.error', 'ai_runs.duration_ms', 'ai_runs.created_at', 'users.name as user'])
            ->map(fn ($e) => ['id' => $e->id, 'feature' => $e->feature, 'provider' => $e->provider, 'model' => $e->model, 'error' => $e->error, 'user' => $e->user, 'ms' => (int) $e->duration_ms, 'at' => Carbon::parse($e->created_at)->toIso8601String()]);

        $topErrors = $base()->where('status', 'error')->selectRaw('substr(error, 1, 140) as msg, count(*) as n, max(created_at) as last_at')->groupBy('msg')->orderByDesc('n')->limit(5)->get()
            ->map(fn ($e) => ['message' => $e->msg, 'count' => (int) $e->n, 'last_at' => Carbon::parse($e->last_at)->toIso8601String()]);

        $totals = [
            'calls' => (int) $rows->sum('calls'),
            'errors' => (int) $rows->sum('errors'),
            'input_tokens' => (int) $rows->sum('i'),
            'output_tokens' => (int) $rows->sum('o'),
            'cost' => round((float) $models->sum('cost'), 4),
            'unpriced_calls' => (int) $models->where('priced', false)->where('input_tokens', '>', 0)->sum('calls'),
            'avg_ms' => (int) round($rows->sum('ms') / max(1, $rows->sum('calls'))),
        ];

        // Mes en curso y proyección contra el presupuesto.
        $monthRows = AiRun::query()->where('created_at', '>=', now()->startOfMonth())->selectRaw('provider, model, sum(input_tokens) as i, sum(output_tokens) as o')->groupBy('provider', 'model')->get();
        $mtd = round((float) $monthRows->sum(fn ($r) => $pricing->cost($r->provider, $r->model, (int) $r->i, (int) $r->o) ?? 0), 4);
        $budget = (float) (Setting::get('ai.monthly_budget_usd') ?: 0);

        return Inertia::render('ai/Usage', [
            'filters' => ['days' => $days, 'provider' => $provider],
            'providers' => AiRun::query()->whereNotNull('provider')->distinct()->orderBy('provider')->pluck('provider'),
            'totals' => $totals,
            'daily' => $daily->values(),
            'models' => $models,
            'features' => $features,
            'users' => $users,
            'errors' => $errors,
            'topErrors' => $topErrors,
            'month' => ['spent' => $mtd, 'projected' => round($mtd / max(1, now()->day) * now()->daysInMonth, 4), 'budget' => $budget, 'day' => now()->day, 'days' => now()->daysInMonth],
        ]);
    }

    public function prices(Request $request, AiPricing $pricing): RedirectResponse
    {
        $data = $request->validate([
            'key' => ['required', 'string', 'max:200'],
            'input' => ['nullable', 'numeric', 'min:0', 'max:100000'],
            'output' => ['nullable', 'numeric', 'min:0', 'max:100000'],
        ]);

        $all = $pricing->overrides();
        if ($data['input'] === null && $data['output'] === null) {
            unset($all[$data['key']]); // vuelve al precio de referencia
        } else {
            $all[$data['key']] = [(float) ($data['input'] ?? 0), (float) ($data['output'] ?? 0)];
        }
        Setting::put('ai.prices', json_encode($all));

        $this->toast('Precio actualizado.');

        return back();
    }

    public function budget(Request $request): RedirectResponse
    {
        $data = $request->validate(['budget' => ['nullable', 'numeric', 'min:0', 'max:10000000']]);
        Setting::put('ai.monthly_budget_usd', (string) ($data['budget'] ?? 0));

        $this->toast('Presupuesto mensual guardado.');

        return back();
    }
}
