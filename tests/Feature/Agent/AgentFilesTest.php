<?php

namespace Tests\Feature\Agent;

use App\Services\Agent\DocumentReader;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\Concerns\CreatesCrmData;
use Tests\TestCase;
use ZipArchive;

class AgentFilesTest extends TestCase
{
    use CreatesCrmData;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedCrm();
    }

    private function zipped(string $name, array $entries): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'ag').'.zip';
        $zip = new ZipArchive;
        $zip->open($path, ZipArchive::CREATE);
        foreach ($entries as $entry => $xml) {
            $zip->addFromString($entry, $xml);
        }
        $zip->close();

        return new UploadedFile($path, $name, null, null, true);
    }

    public function test_reads_docx_xlsx_and_text_files(): void
    {
        $reader = new DocumentReader;

        $docx = $this->zipped('p.docx', ['word/document.xml' => '<w:document xmlns:w="x"><w:body><w:p><w:r><w:t>Valor neto: $5.000.000 &amp; 3 cuotas</w:t></w:r></w:p></w:body></w:document>']);
        $this->assertStringContainsString('Valor neto: $5.000.000 & 3 cuotas', $reader->read($docx)['text']);

        $xlsx = $this->zipped('s.xlsx', [
            'xl/workbook.xml' => '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheets><sheet name="Servicios"/></sheets></workbook>',
            'xl/sharedStrings.xml' => '<sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><si><t>Bot IA</t></si></sst>',
            'xl/worksheets/sheet1.xml' => '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData><row r="1"><c r="A1" t="s"><v>0</v></c><c r="B1"><v>5000000</v></c></row></sheetData></worksheet>',
        ]);
        $text = $reader->read($xlsx)['text'];
        $this->assertStringContainsString('Hoja: Servicios', $text);
        $this->assertStringContainsString("Bot IA\t5000000", $text);

        $txt = UploadedFile::fake()->createWithContent('n.txt', "Hola\n\n\n\nmundo");
        $this->assertSame("Hola\n\nmundo", $reader->read($txt)['text']);
    }

    public function test_rejects_unsupported_old_and_corrupt_files(): void
    {
        $reader = new DocumentReader;

        $this->expectException(\RuntimeException::class);
        $reader->read(UploadedFile::fake()->create('viejo.doc', 10));
    }

    public function test_attachments_endpoint_reports_each_file_and_long_text_is_truncated(): void
    {
        $admin = $this->userWithRole('admin');
        $long = UploadedFile::fake()->createWithContent('largo.txt', str_repeat('a', DocumentReader::MAX_CHARS + 500));
        $bad = UploadedFile::fake()->create('foto.png', 10);

        $res = $this->actingAs($admin)->postJson('/agent/attachments', ['files' => [$long, $bad]])->assertOk()->json('files');

        $this->assertTrue($res[0]['ok']);
        $this->assertTrue($res[0]['truncated']);
        $this->assertSame(DocumentReader::MAX_CHARS, mb_strlen($res[0]['text']));
        $this->assertFalse($res[1]['ok']);
    }

    public function test_attachments_and_transcription_require_permission(): void
    {
        $lectura = $this->userWithRole('lectura');
        $this->actingAs($lectura)->postJson('/agent/attachments', ['files' => [UploadedFile::fake()->create('a.txt', 1)]])->assertForbidden();
        $this->actingAs($lectura)->postJson('/agent/transcribe', ['audio' => UploadedFile::fake()->create('a.webm', 5)])->assertForbidden();

        // Sin proveedor de IA con transcripción, el servidor lo informa.
        $this->actingAs($this->userWithRole('admin'))->postJson('/agent/transcribe', ['audio' => UploadedFile::fake()->create('a.webm', 5)])->assertStatus(503);
    }
}
