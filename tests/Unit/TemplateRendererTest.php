<?php

namespace Tests\Unit;

use App\Services\Email\TemplateRenderer;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class TemplateRendererTest extends TestCase
{
    /** @return array<string, array{0: string, 1: array<string, mixed>, 2: string}> */
    public static function cases(): array
    {
        return [
            'escapa html' => ['Hola {{ nombre }}!', ['nombre' => '<b>Ana</b>'], 'Hola &lt;b&gt;Ana&lt;/b&gt;!'],
            'valor por defecto' => ['Hola {{ nombre | default:"estimado/a" }}', [], 'Hola estimado/a'],
            'sin escapar' => ['{{{ html }}}|{{ html | raw }}', ['html' => '<i>x</i>'], '<i>x</i>|<i>x</i>'],
            'if con else (verdadero)' => ['{{#if empresa}}De {{ empresa }}{{else}}Sin empresa{{/if}}', ['empresa' => 'Cities'], 'De Cities'],
            'if con else (falso)' => ['{{#if empresa}}De {{ empresa }}{{else}}Sin empresa{{/if}}', [], 'Sin empresa'],
            'unless' => ['{{#unless x}}no x{{/unless}}', [], 'no x'],
            'each de objetos' => ['{{#each items}}[{{@index}}:{{ this.n }}]{{/each}}', ['items' => [['n' => 'a'], ['n' => 'b']]], '[0:a][1:b]'],
            'each de escalares' => ['{{#each tags}}{{this}},{{/each}}', ['tags' => ['x', 'y']], 'x,y,'],
            'rutas y filtros' => ['{{ lead.first_name | upper }} {{ monto | money }} {{ f | date:"d/m/Y" }}', ['lead' => ['first_name' => 'ana'], 'monto' => 1500000, 'f' => '2026-10-08'], 'ANA $1.500.000 08/10/2026'],
            'if anidado' => ['{{#if a}}{{#if b}}AB{{else}}A{{/if}}{{else}}N{{/if}}', ['a' => 1, 'b' => 0], 'A'],
            'pipe dentro de comillas' => ['{{ x | default:"a|b" }}', [], 'a|b'],
        ];
    }

    /** @param array<string, mixed> $vars */
    #[DataProvider('cases')]
    public function test_renders(string $template, array $vars, string $expected): void
    {
        $this->assertSame($expected, (new TemplateRenderer)->render($template, $vars));
    }

    public function test_reports_missing_variables(): void
    {
        $r = new TemplateRenderer;
        $r->render('{{ a }} {{ b.c }}', ['a' => 1]);

        $this->assertSame(['b'], $r->missing());
    }

    public function test_detects_root_variables_and_ignores_loop_locals_and_system_ones(): void
    {
        $vars = (new TemplateRenderer)->variables('Hi {{ nombre }} {{#if empresa}}{{ empresa }}{{/if}} {{#each proyectos}}{{ this.n }} {{ otro }}{{/each}} {{ unsubscribe_url }} {{ lead.first_name }}');

        $this->assertSame(['nombre', 'empresa', 'proyectos', 'lead'], $vars);
    }
}
