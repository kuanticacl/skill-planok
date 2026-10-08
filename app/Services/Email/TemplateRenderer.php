<?php

namespace App\Services\Email;

use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Motor de plantillas de email (sintaxis tipo Handlebars/Liquid, sin ejecutar código).
 *
 *   {{ nombre }}                         variable (escapada para HTML)
 *   {{ lead.first_name }}                ruta con puntos
 *   {{ nombre | default:"estimado/a" }}  valor por defecto
 *   {{ monto | money }}  {{ fecha | date:"d/m/Y" }}  {{ x | upper|lower|capitalize|truncate:40|url|nl2br|raw }}
 *   {{{ html_crudo }}}                   sin escapar
 *   {{#if empresa}}…{{else}}…{{/if}}     condicional   ({{#unless x}}…{{/unless}} al revés)
 *   {{#each proyectos}}{{ this.nombre }} ({{@index}}){{/each}}   bucle
 */
class TemplateRenderer
{
    /** Variables que el sistema inyecta siempre (no hace falta declararlas). */
    public const SYSTEM_VARIABLES = ['unsubscribe_url', 'view_url', 'current_year', 'company_name', 'to_email', 'to_name', 'campaign_name', 'tracking_pixel'];

    /** @var array<int, string> */
    private array $missing = [];

    /** @param array<string, mixed> $vars */
    public function render(string $source, array $vars = []): string
    {
        $this->missing = [];
        $nodes = $this->parse($this->tokenize($source));

        return $this->renderNodes($nodes, $vars, []);
    }

    /** Variables que se usaron pero no venían en $vars (para avisar al llamador de la API). @return array<int, string> */
    public function missing(): array
    {
        return array_values(array_unique($this->missing));
    }

    /**
     * Variables "raíz" que usa una plantilla (para detectarlas automáticamente).
     *
     * @return array<int, string>
     */
    public function variables(string $source): array
    {
        $found = [];
        $this->collect($this->parse($this->tokenize($source)), $found, 0);

        return array_values(array_diff(array_unique($found), self::SYSTEM_VARIABLES));
    }

    // ---------------------------------------------------------------- tokenizer / parser

    /** @return array<int, array{type: string, value: string, raw?: bool}> */
    private function tokenize(string $source): array
    {
        $tokens = [];
        $offset = 0;

        preg_match_all('/\{\{\{\s*(.+?)\s*\}\}\}|\{\{\s*(.+?)\s*\}\}/s', $source, $matches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER);

        foreach ($matches as $m) {
            $start = $m[0][1];
            if ($start > $offset) {
                $tokens[] = ['type' => 'text', 'value' => substr($source, $offset, $start - $offset)];
            }
            $offset = $start + strlen($m[0][0]);

            $triple = isset($m[1]) && $m[1][1] >= 0 && ($m[1][0] !== '');
            $inner = $triple ? $m[1][0] : ($m[2][0] ?? '');

            if (! $triple && str_starts_with($inner, '#')) {
                [$kw, $rest] = array_pad(preg_split('/\s+/', substr($inner, 1), 2), 2, '');
                $tokens[] = ['type' => 'open', 'value' => strtolower($kw), 'arg' => trim($rest)];
            } elseif (! $triple && str_starts_with($inner, '/')) {
                $tokens[] = ['type' => 'close', 'value' => strtolower(trim(substr($inner, 1)))];
            } elseif (! $triple && strtolower($inner) === 'else') {
                $tokens[] = ['type' => 'else', 'value' => 'else'];
            } else {
                $tokens[] = ['type' => 'var', 'value' => $inner, 'raw' => $triple];
            }
        }

        if ($offset < strlen($source)) {
            $tokens[] = ['type' => 'text', 'value' => substr($source, $offset)];
        }

        return $tokens;
    }

    /** @return array<int, array<string, mixed>> */
    private function parse(array $tokens): array
    {
        $i = 0;

        return $this->parseBlock($tokens, $i, null);
    }

    /** @return array<int, array<string, mixed>> */
    private function parseBlock(array $tokens, int &$i, ?string $until): array
    {
        $nodes = [];

        while ($i < count($tokens)) {
            $t = $tokens[$i];

            if ($t['type'] === 'close' || $t['type'] === 'else') {
                if ($until !== null) {
                    return $nodes; // el que llama consume el cierre / else
                }
                $i++; // cierre huérfano: se ignora

                continue;
            }

            $i++;

            if ($t['type'] === 'text') {
                $nodes[] = ['t' => 'text', 'v' => $t['value']];
            } elseif ($t['type'] === 'var') {
                $nodes[] = ['t' => 'var', 'expr' => $t['value'], 'raw' => $t['raw'] ?? false];
            } elseif ($t['type'] === 'open' && in_array($t['value'], ['if', 'unless', 'each'], true)) {
                $then = $this->parseBlock($tokens, $i, $t['value']);
                $else = [];
                if (($tokens[$i]['type'] ?? null) === 'else') {
                    $i++;
                    $else = $this->parseBlock($tokens, $i, $t['value']);
                }
                if (($tokens[$i]['type'] ?? null) === 'close') {
                    $i++;
                }
                $nodes[] = ['t' => $t['value'], 'arg' => $t['arg'], 'then' => $then, 'else' => $else];
            }
        }

        return $nodes;
    }

    // ---------------------------------------------------------------- render

    /** @param array<int, array<string, mixed>> $nodes @param array<string, mixed> $vars @param array<int, array<string, mixed>> $stack */
    private function renderNodes(array $nodes, array $vars, array $stack): string
    {
        $out = '';

        foreach ($nodes as $n) {
            switch ($n['t']) {
                case 'text':
                    $out .= $n['v'];
                    break;

                case 'var':
                    [$value, $raw] = $this->evaluate($n['expr'], $vars, $stack);
                    $string = is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : (is_bool($value) ? ($value ? '1' : '') : (string) ($value ?? ''));
                    $out .= ($n['raw'] || $raw) ? $string : e($string);
                    break;

                case 'if':
                case 'unless':
                    $truthy = $this->truthy($this->evaluate($n['arg'], $vars, $stack, false)[0]);
                    if ($n['t'] === 'unless') {
                        $truthy = ! $truthy;
                    }
                    $out .= $this->renderNodes($truthy ? $n['then'] : $n['else'], $vars, $stack);
                    break;

                case 'each':
                    $list = $this->evaluate($n['arg'], $vars, $stack, false)[0];
                    if (is_iterable($list) && count((array) $list) > 0) {
                        $items = array_values((array) $list);
                        foreach ($items as $idx => $item) {
                            $frame = ['this' => $item, '@index' => $idx, '@first' => $idx === 0, '@last' => $idx === count($items) - 1];
                            $out .= $this->renderNodes($n['then'], $vars, [...$stack, $frame]);
                        }
                    } else {
                        $out .= $this->renderNodes($n['else'], $vars, $stack);
                    }
                    break;
            }
        }

        return $out;
    }

    /**
     * @param  array<string, mixed>  $vars
     * @param  array<int, array<string, mixed>>  $stack
     * @return array{0: mixed, 1: bool} [valor, ¿sin escapar?]
     */
    private function evaluate(string $expr, array $vars, array $stack, bool $track = true): array
    {
        $parts = $this->splitPipes($expr);
        $path = trim(array_shift($parts));
        $value = $this->lookup($path, $vars, $stack, $track);
        $raw = false;

        foreach ($parts as $filter) {
            [$name, $arg] = array_pad(explode(':', trim($filter), 2), 2, null);
            $name = strtolower(trim($name));
            $arg = $arg === null ? null : $this->literal($arg);

            switch ($name) {
                case 'default':
                    if ($value === null || $value === '' || $value === false || $value === []) {
                        $value = $arg;
                    }
                    break;
                case 'upper': $value = mb_strtoupper((string) $value); break;
                case 'lower': $value = mb_strtolower((string) $value); break;
                case 'capitalize': $value = Str::title(mb_strtolower((string) $value)); break;
                case 'truncate': $value = Str::limit((string) $value, (int) ($arg ?: 60)); break;
                case 'trim': $value = trim((string) $value); break;
                case 'url': $value = rawurlencode((string) $value); break;
                case 'nl2br': $value = nl2br(e((string) $value)); $raw = true; break;
                case 'raw': $raw = true; break;
                case 'money':
                    $value = is_numeric($value) ? '$'.number_format((float) $value, 0, ',', '.') : $value;
                    break;
                case 'date':
                    try {
                        $value = $value ? Carbon::parse($value)->format((string) ($arg ?: 'd/m/Y')) : '';
                    } catch (\Throwable) {
                        // se deja el valor original si no es una fecha válida
                    }
                    break;
            }
        }

        return [$value, $raw];
    }

    /** @param array<string, mixed> $vars @param array<int, array<string, mixed>> $stack */
    private function lookup(string $path, array $vars, array $stack, bool $track): mixed
    {
        if ($path === '') {
            return null;
        }

        // literales: "texto", 'texto', 123
        if (preg_match('/^(["\']).*\1$/s', $path) || is_numeric($path)) {
            return $this->literal($path);
        }

        $frame = end($stack) ?: null;

        if ($frame !== null) {
            if (str_starts_with($path, '@')) {
                return $frame[$path] ?? null;
            }
            if ($path === 'this') {
                return $frame['this'];
            }
            if (str_starts_with($path, 'this.')) {
                return data_get($frame['this'], substr($path, 5));
            }
        }

        $found = Arr::has($vars, $path) ? Arr::get($vars, $path) : null;

        if ($found === null && $frame !== null && is_array($frame['this'] ?? null)) {
            $found = data_get($frame['this'], $path);
        }

        if ($found === null && $track) {
            $this->missing[] = explode('.', $path)[0];
        }

        return $found;
    }

    private function literal(string $raw): mixed
    {
        $raw = trim($raw);

        if (preg_match('/^(["\'])(.*)\1$/s', $raw, $m)) {
            return $m[2];
        }

        return is_numeric($raw) ? $raw + 0 : $raw;
    }

    private function truthy(mixed $v): bool
    {
        if (is_string($v)) {
            return trim($v) !== '' && $v !== '0' && strtolower($v) !== 'false';
        }

        return ! empty($v);
    }

    /** @return array<int, string> */
    private function splitPipes(string $expr): array
    {
        $parts = [];
        $buf = '';
        $quote = null;

        foreach (mb_str_split($expr) as $ch) {
            if ($quote) {
                $buf .= $ch;
                if ($ch === $quote) {
                    $quote = null;
                }
            } elseif ($ch === '"' || $ch === "'") {
                $quote = $ch;
                $buf .= $ch;
            } elseif ($ch === '|') {
                $parts[] = $buf;
                $buf = '';
            } else {
                $buf .= $ch;
            }
        }
        $parts[] = $buf;

        return $parts;
    }

    // ---------------------------------------------------------------- extracción de variables

    /** @param array<int, array<string, mixed>> $nodes @param array<int, string> $found */
    private function collect(array $nodes, array &$found, int $depth): void
    {
        $add = function (string $path) use (&$found, $depth) {
            $path = trim($path);
            if ($path === '' || preg_match('/^(["\']).*\1$/s', $path) || is_numeric($path) || str_starts_with($path, '@') || str_starts_with($path, 'this')) {
                return;
            }
            // dentro de un bucle, las claves sueltas pertenecen al elemento
            if ($depth === 0) {
                $found[] = explode('.', $path)[0];
            }
        };

        foreach ($nodes as $n) {
            if ($n['t'] === 'var') {
                $add($this->splitPipes($n['expr'])[0]);
            } elseif (in_array($n['t'], ['if', 'unless', 'each'], true)) {
                $add($this->splitPipes($n['arg'])[0]);
                $inner = $n['t'] === 'each' ? $depth + 1 : $depth;
                $this->collect($n['then'], $found, $inner);
                $this->collect($n['else'], $found, $depth);
            }
        }
    }
}
