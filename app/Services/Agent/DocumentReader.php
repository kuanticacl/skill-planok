<?php

namespace App\Services\Agent;

use Illuminate\Http\UploadedFile;
use RuntimeException;
use SimpleXMLElement;
use Symfony\Component\Process\Process;
use ZipArchive;

/** Extrae el texto de los archivos que el usuario adjunta al Agent: PDF, Word (.docx), Excel (.xlsx), TXT/CSV/MD/JSON. */
class DocumentReader
{
    public const MAX_BYTES = 10 * 1024 * 1024;

    public const MAX_CHARS = 60000;

    public const EXTENSIONS = ['pdf', 'docx', 'xlsx', 'txt', 'csv', 'md', 'json'];

    private const MAX_UNZIPPED = 40 * 1024 * 1024;

    /** @return array{name: string, text: string, chars: int, truncated: bool} */
    public function read(UploadedFile $file): array
    {
        $ext = strtolower($file->getClientOriginalExtension());
        $name = mb_substr($file->getClientOriginalName(), 0, 120);

        if ($file->getSize() > self::MAX_BYTES) {
            throw new RuntimeException("«{$name}» pesa más de 10 MB.");
        }

        $text = match ($ext) {
            'pdf' => $this->pdf($file->getRealPath(), $name),
            'docx' => $this->docx($file->getRealPath(), $name),
            'xlsx' => $this->xlsx($file->getRealPath(), $name),
            'txt', 'csv', 'md', 'json' => $this->plain($file->getRealPath()),
            'doc', 'xls' => throw new RuntimeException("«{$name}» está en formato antiguo ({$ext}). Guárdalo como .".($ext === 'doc' ? 'docx' : 'xlsx').' y vuelve a adjuntarlo.'),
            default => throw new RuntimeException("«{$name}»: formato no compatible. Usa PDF, Word (.docx), Excel (.xlsx), TXT o CSV."),
        };

        $text = trim(preg_replace("/[ \t]+\n/", "\n", preg_replace("/\n{3,}/", "\n\n", $text)));
        if ($text === '') {
            throw new RuntimeException("No pude leer texto de «{$name}»".($ext === 'pdf' ? ' (¿es un PDF escaneado? no tengo OCR: pega el texto o sube una versión con texto)' : '').'.');
        }

        $truncated = mb_strlen($text) > self::MAX_CHARS;

        return ['name' => $name, 'text' => $truncated ? mb_substr($text, 0, self::MAX_CHARS) : $text, 'chars' => mb_strlen($text), 'truncated' => $truncated];
    }

    private function plain(string $path): string
    {
        $raw = (string) file_get_contents($path, false, null, 0, self::MAX_BYTES);

        return mb_check_encoding($raw, 'UTF-8') ? $raw : mb_convert_encoding($raw, 'UTF-8', 'ISO-8859-1');
    }

    private function pdf(string $path, string $name): string
    {
        $p = new Process(['pdftotext', '-enc', 'UTF-8', '-layout', '-nopgbrk', $path, '-']);
        $p->setTimeout(40);

        try {
            $p->run();
        } catch (\Throwable) {
            throw new RuntimeException('El servidor no puede leer PDF en este momento (falta pdftotext).');
        }
        if (! $p->isSuccessful()) {
            throw new RuntimeException("No pude abrir «{$name}» (¿está protegido con contraseña o dañado?).");
        }

        return $p->getOutput();
    }

    private function zip(string $path, string $name): ZipArchive
    {
        $zip = new ZipArchive;
        if ($zip->open($path) !== true) {
            throw new RuntimeException("«{$name}» no es un archivo válido o está dañado.");
        }
        $total = 0;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $total += (int) ($zip->statIndex($i)['size'] ?? 0);
        }
        if ($total > self::MAX_UNZIPPED) {
            $zip->close();
            throw new RuntimeException("«{$name}» es demasiado grande para leerlo.");
        }

        return $zip;
    }

    private function xml(string $raw): SimpleXMLElement
    {
        $x = @simplexml_load_string($raw, SimpleXMLElement::class, LIBXML_NONET | LIBXML_NOCDATA);
        if (! $x) {
            throw new RuntimeException('El archivo tiene un formato interno inválido.');
        }

        return $x;
    }

    private function docx(string $path, string $name): string
    {
        $zip = $this->zip($path, $name);
        $raw = $zip->getFromName('word/document.xml');
        $zip->close();
        if ($raw === false) {
            throw new RuntimeException("«{$name}» no parece un documento Word (.docx).");
        }

        $raw = preg_replace(['/<w:tab\/>/', '/<\/w:tc>/', '/<\/w:p>/', '/<w:br\/>/'], ["\t", "\t", "\n", "\n"], $raw);

        return html_entity_decode(strip_tags($raw), ENT_QUOTES | ENT_XML1, 'UTF-8');
    }

    private function xlsx(string $path, string $name): string
    {
        $zip = $this->zip($path, $name);
        $workbook = $zip->getFromName('xl/workbook.xml');
        if ($workbook === false) {
            $zip->close();
            throw new RuntimeException("«{$name}» no parece un Excel (.xlsx).");
        }

        $strings = [];
        if (($ss = $zip->getFromName('xl/sharedStrings.xml')) !== false) {
            foreach ($this->xml($ss)->si as $si) {
                $strings[] = trim(preg_replace('/<[^>]+>/', '', (string) $si->asXML()) ?? '');
            }
        }

        $wb = $this->xml($workbook);
        $wb->registerXPathNamespace('m', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        $out = [];
        $i = 0;
        foreach ($wb->sheets->sheet ?? [] as $sheet) {
            $i++;
            $xml = $zip->getFromName("xl/worksheets/sheet{$i}.xml");
            if ($xml === false) {
                continue;
            }
            $out[] = '## Hoja: '.(string) $sheet['name'];
            $rows = 0;
            foreach ($this->xml($xml)->sheetData->row ?? [] as $row) {
                if (++$rows > 600) {
                    $out[] = '… (más filas omitidas)';
                    break;
                }
                $cells = [];
                foreach ($row->c as $c) {
                    $col = $this->colIndex((string) $c['r']);
                    $v = (string) $c->v;
                    if ((string) $c['t'] === 's') {
                        $v = $strings[(int) $v] ?? '';
                    } elseif ((string) $c['t'] === 'inlineStr') {
                        $v = trim(strip_tags((string) $c->is->asXML()));
                    }
                    $cells[$col] = $v;
                }
                if ($cells) {
                    $max = min(40, max(array_keys($cells)));
                    $out[] = rtrim(implode("\t", array_map(fn ($c) => str_replace(["\t", "\n"], ' ', $cells[$c] ?? ''), range(1, $max))));
                }
            }
        }
        $zip->close();

        return implode("\n", $out);
    }

    private function colIndex(string $ref): int
    {
        $letters = preg_replace('/[^A-Z]/', '', strtoupper($ref));
        $n = 0;
        foreach (str_split($letters) as $ch) {
            $n = $n * 26 + (ord($ch) - 64);
        }

        return $n;
    }
}
