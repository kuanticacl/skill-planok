<?php

namespace App\Support;

/** Texto enriquecido mínimo de las propuestas: párrafos, **negrita**, *cursiva*, listas con «- » y enlaces https. Siempre escapado. */
class ProposalText
{
    private const ALLOWED = ['p', 'br', 'strong', 'b', 'em', 'i', 'u', 's', 'ul', 'ol', 'li', 'h3', 'h4', 'a', 'blockquote'];

    /** ¿Es HTML del editor enriquecido (y no «markdown mínimo»)? */
    public static function isHtml(?string $text): bool
    {
        return (bool) preg_match('/^\s*<(p|ul|ol|h[1-6]|div|blockquote|br)[\s>\/]/i', (string) $text);
    }

    /** Texto enriquecido → HTML seguro: HTML del editor (saneado) o «markdown mínimo» (escapado). */
    public static function html(?string $text): string
    {
        $text = trim(str_replace("\r", '', (string) $text));
        if ($text === '') {
            return '';
        }
        if (self::isHtml($text)) {
            return self::sanitize($text);
        }

        $out = [];
        foreach (preg_split("/\n{2,}/", $text) as $block) {
            $lines = explode("\n", trim($block));
            $isList = collect($lines)->every(fn ($l) => preg_match('/^\s*[-•*]\s+/', $l));

            if ($isList) {
                $items = array_map(fn ($l) => '<li>'.self::inline(preg_replace('/^\s*[-•*]\s+/', '', $l)).'</li>', $lines);
                $out[] = '<ul>'.implode('', $items).'</ul>';
            } else {
                $out[] = '<p>'.implode('<br>', array_map(fn ($l) => self::inline($l), $lines)).'</p>';
            }
        }

        return implode("\n", $out);
    }

    private static function inline(string $line): string
    {
        $s = e($line);
        $s = preg_replace('/\*\*(.+?)\*\*/u', '<strong>$1</strong>', $s) ?? $s;
        $s = preg_replace('/(?<![*\w])\*(?!\s)(.+?)(?<!\s)\*(?![*\w])/u', '<em>$1</em>', $s) ?? $s;

        return preg_replace_callback('/\[([^\]]+)\]\((https:\/\/[^\s)]+)\)/u', fn ($m) => '<a href="'.$m[2].'">'.$m[1].'</a>', $s) ?? $s;
    }

    /** Reemplaza los marcadores [CLIENTE] y [CONTACTO] que usa la IA. */
    public static function fill(string $text, array $recipient): string
    {
        return strtr($text, [
            '[CLIENTE]' => $recipient['company'] ?? $recipient['legal_name'] ?? 'el cliente',
            '[CONTACTO]' => $recipient['contact_name'] ?? 'equipo',
        ]);
    }

    /** Deja solo etiquetas y atributos seguros (lista blanca). */
    public static function sanitize(string $html): string
    {
        if (trim($html) === '') {
            return '';
        }

        $doc = new \DOMDocument;
        libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="utf-8"?><body>'.$html.'</body>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        $body = $doc->getElementsByTagName('body')->item(0);
        if (! $body) {
            return e(strip_tags($html));
        }
        self::clean($body);

        $out = '';
        foreach ($body->childNodes as $child) {
            $out .= $doc->saveHTML($child);
        }

        return trim($out);
    }

    private static function clean(\DOMNode $node): void
    {
        foreach (iterator_to_array($node->childNodes) as $child) {
            if (! $child instanceof \DOMElement) {
                continue;
            }
            self::clean($child);
            $tag = strtolower($child->tagName);

            if (in_array($tag, ['script', 'style', 'iframe', 'object', 'embed', 'svg'], true)) {
                $node->removeChild($child);

                continue;
            }
            if (! in_array($tag, self::ALLOWED, true)) {
                while ($child->firstChild) {
                    $node->insertBefore($child->firstChild, $child);
                }
                $node->removeChild($child);

                continue;
            }
            foreach (iterator_to_array($child->attributes) as $attr) {
                $keep = $tag === 'a' && $attr->name === 'href' && preg_match('/^(https:|mailto:)/i', trim($attr->value));
                if (! $keep) {
                    $child->removeAttribute($attr->name);
                }
            }
            if ($tag === 'a') {
                if (! $child->hasAttribute('href')) {
                    while ($child->firstChild) {
                        $node->insertBefore($child->firstChild, $child);
                    }
                    $node->removeChild($child);
                } else {
                    $child->setAttribute('rel', 'noopener nofollow');
                    $child->setAttribute('target', '_blank');
                }
            }
        }
    }

    /** HTML del editor → «markdown mínimo» (para pedirle texto a la IA). */
    public static function toMarkdown(?string $text): string
    {
        $t = (string) $text;
        if (! self::isHtml($t)) {
            return trim($t);
        }
        $t = preg_replace('/<\s*(strong|b)\b[^>]*>(.*?)<\/\s*\1\s*>/is', '**$2**', $t);
        $t = preg_replace('/<\s*(em|i)\b[^>]*>(.*?)<\/\s*\1\s*>/is', '*$2*', $t);
        $t = preg_replace('/<\s*li\b[^>]*>/i', '- ', $t);
        $t = preg_replace('/<\/\s*li\s*>/i', "\n", $t);
        $t = preg_replace('/<\s*br\s*\/?>/i', "\n", $t);
        $t = preg_replace('/<\/\s*(p|h[1-6]|ul|ol|blockquote)\s*>/i', "\n\n", $t);
        $t = html_entity_decode(strip_tags($t), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim(preg_replace("/\n{3,}/", "\n\n", $t));
    }

    /** Texto plano de un contenido enriquecido (listados, resúmenes). */
    public static function plain(?string $text, int $max = 0): string
    {
        $t = trim(preg_replace('/\s+/', ' ', html_entity_decode(strip_tags(str_replace(['</p>', '</li>', '<br>', '<br/>'], ' ', (string) $text)), ENT_QUOTES | ENT_HTML5, 'UTF-8')));

        return $max > 0 ? mb_substr($t, 0, $max) : $t;
    }
}
