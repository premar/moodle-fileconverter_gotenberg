<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace fileconverter_gotenberg;

/**
 * A set of tests for some of the Gotenberg fileconverter functionality within Moodle.
 *
 * @package    fileconverter_gotenberg
 * @copyright  2026 Marius Preuss
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \fileconverter_gotenberg\converter
 */
final class converter_test extends \advanced_testcase {
    /**
     * Helper to skip tests which _require_ a reachable Gotenberg server.
     */
    protected function require_gotenberg(): void {
        if (converter::test_connection()->status !== converter::STATUS_OK) {
            // No conversions are possible, sorry.
            $this->markTestSkipped('No Gotenberg server is configured/reachable for this test run.');
        }
    }

    /**
     * Tests for the start_document_conversion function.
     */
    public function test_start_document_conversion(): void {
        $this->resetAfterTest();

        $this->require_gotenberg();

        // Mock the file to be converted.
        $filerecord = [
            'contextid' => \context_system::instance()->id,
            'component' => 'test',
            'filearea'  => 'unittest',
            'itemid'    => 0,
            'filepath'  => '/',
            'filename'  => 'test.docx',
        ];
        $fs = get_file_storage();
        $source = __DIR__ . DIRECTORY_SEPARATOR . 'fixtures' . DIRECTORY_SEPARATOR . 'source.docx';
        $testfile = $fs->create_file_from_pathname($filerecord, $source);

        $converter = new converter();
        $conversion = new \core_files\conversion(0, (object) [
            'targetformat' => 'pdf',
        ]);
        $conversion->set_sourcefile($testfile);
        $conversion->create();

        // Convert the document.
        $converter->start_document_conversion($conversion);
        $result = $conversion->get_destfile();
        $this->assertNotFalse($result);
        $this->assertSame('application/pdf', $result->get_mimetype());
        $this->assertGreaterThan(0, $result->get_filesize());
    }

    /**
     * Tests for the supports function.
     *
     * @dataProvider provider_supports
     * @param   string $from The source type
     * @param   string $to The destination type
     * @param   bool $expected The expected result
     */
    public function test_supports($from, $to, $expected): void {
        $this->assertSame($expected, converter::supports($from, $to));
    }

    /**
     * Provider for test_supports.
     *
     * @return  array
     */
    public static function provider_supports(): array {
        return [
            'docx to pdf' => ['docx', 'pdf', true],
            'DOCX to pdf, case-insensitive' => ['DOCX', 'pdf', true],
            'odt to pdf' => ['odt', 'pdf', true],
            'pdf to docx is not supported' => ['pdf', 'docx', false],
            'unknown extension' => ['exe', 'pdf', false],
        ];
    }

    /**
     * Tests for the get_supported_conversions function.
     */
    public function test_get_supported_conversions(): void {
        $converter = new converter();
        $conversions = $converter->get_supported_conversions();

        $this->assertIsString($conversions);
        $this->assertStringContainsString('docx', $conversions);
    }

    /**
     * Tests for the test_connection function when the URL is not configured.
     */
    public function test_test_connection_empty(): void {
        $this->resetAfterTest();

        set_config('url', '', 'fileconverter_gotenberg');

        $result = converter::test_connection();
        $this->assertEquals(converter::STATUS_EMPTY, $result->status);
    }
}
