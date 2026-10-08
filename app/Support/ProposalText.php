<?php

namespace App\Support;

/** Texto enriquecido mínimo de las propuestas: párrafos, **negrita**, *cursiva*, listas con «- » y enlaces https. Siempre escapado. */
class ProposalText
{
    public static function html(?string $text): string
    {
        $text = trim(str_replace("\r", '', (string) $text));
        if ($text === '') {
            return '';
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
}
