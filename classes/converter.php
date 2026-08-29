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

/**
 * Class for converting files between different file formats using Gotenberg.
 *
 * @package    fileconverter_gotenberg
 * @copyright  2026 Marius Preuss
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace fileconverter_gotenberg;

/**
 * Class for converting files between different formats using Gotenberg.
 *
 * @package    fileconverter_gotenberg
 * @copyright  2026 Marius Preuss
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class converter implements \core_files\converter_interface {
    /** Connection to Gotenberg is working. */
    const STATUS_OK = 'ok';

    /** The Gotenberg URL has not been configured. */
    const STATUS_EMPTY = 'empty';

    /** The Gotenberg server could not be reached. */
    const STATUS_UNREACHABLE = 'unreachable';

    /** @var bool|null Cached result of the requirements check for this request. */
    protected static ?bool $requirementsmet = null;

    /** @var array<string, bool> File extensions that LibreOffice, via Gotenberg, can convert to PDF. */
    private static array $imports = [
        'doc' => true,
        'docx' => true,
        'odt' => true,
        'ott' => true,
        'rtf' => true,
        'txt' => true,
        'html' => true,
        'htm' => true,
        'xls' => true,
        'xlsx' => true,
        'ods' => true,
        'ots' => true,
        'csv' => true,
        'ppt' => true,
        'pptx' => true,
        'odp' => true,
        'otp' => true,
    ];

    /**
     * Convert a document to a new format and return a conversion object relating to the conversion in progress.
     *
     * @param   \core_files\conversion $conversion The file to be converted
     * @return  $this
     */
    public function start_document_conversion(\core_files\conversion $conversion) {
        global $CFG;
        require_once($CFG->libdir . '/filelib.php');

        if (!self::are_requirements_met()) {
            $conversion->set('status', \core_files\conversion::STATUS_FAILED);
            debugging(
                'Gotenberg conversion failed to verify the configuration meets the minimum requirements. ' .
                'Please check the Gotenberg URL setting and that the server is reachable.',
                DEBUG_DEVELOPER
            );
            return $this;
        }

        $file = $conversion->get_sourcefile();
        $filepath = $file->get_filepath();

        $fromformat = \core_text::strtolower(pathinfo($file->get_filename(), PATHINFO_EXTENSION));
        $format = $conversion->get('targetformat');

        if (!self::supports($fromformat, $format)) {
            $conversion->set('status', \core_files\conversion::STATUS_FAILED);
            debugging(
                "Gotenberg conversion for '" . $filepath . "' from '" . $fromformat . "' to '" . $format . "' " .
                "is not supported.",
                DEBUG_DEVELOPER
            );
            return $this;
        }

        // Copy the file to the tmp dir, keeping its extension so Gotenberg can detect the source format.
        $uniqdir = make_unique_writable_directory(make_temp_directory('fileconverter_gotenberg/conversions'));
        \core_shutdown_manager::register_function('remove_dir', [$uniqdir]);
        $localfilename = $file->get_id() . '.' . $fromformat;
        $localfilepath = $uniqdir . '/' . $localfilename;

        try {
            // This function can either return false, or throw an exception so we need to handle both.
            if ($file->copy_content_to($localfilepath) === false) {
                throw new \file_exception('storedfileproblem', 'Could not copy file contents to temp file.');
            }
        } catch (\file_exception $fe) {
            debugging(
                "Gotenberg conversion for '" . $filepath . "' encountered a disk permission error when copying " .
                "the submitted file contents to the temp file: '" . $localfilepath . "'.",
                DEBUG_DEVELOPER
            );
            throw $fe;
        }

        $url = rtrim(get_config('fileconverter_gotenberg', 'url'), '/') . '/forms/libreoffice/convert';
        $timeout = (int) get_config('fileconverter_gotenberg', 'timeout');

        $curl = new \curl();
        $response = $curl->post($url, [
            'files' => new \CURLFile($localfilepath, $file->get_mimetype(), $localfilename),
        ], [
            'CURLOPT_TIMEOUT' => $timeout,
        ]);

        $info = $curl->get_info();
        $httpcode = $info['http_code'] ?? 0;

        if ($curl->get_errno() || $httpcode != 200 || empty($response)) {
            $conversion->set('status', \core_files\conversion::STATUS_FAILED);
            debugging(
                "Gotenberg conversion for '" . $filepath . "' from '" . $fromformat . "' to '" . $format . "' " .
                "was unsuccessful; received HTTP status (" . $httpcode . "). Please check the Gotenberg URL " .
                "setting and that the server is reachable.",
                DEBUG_DEVELOPER
            );
            return $this;
        }

        $conversion
            ->store_destfile_from_string($response)
            ->set('status', \core_files\conversion::STATUS_COMPLETE)
            ->update();

        return $this;
    }

    /**
     * Poll an existing conversion for status update.
     *
     * @param   \core_files\conversion $conversion The file to be converted
     * @return  $this
     */
    public function poll_conversion_status(\core_files\conversion $conversion) {
        // Gotenberg conversions happen synchronously in start_document_conversion(), nothing to poll.
        return $this;
    }

    /**
     * Generate and serve the test document.
     *
     * @return  void
     */
    public function serve_test_document() {
        global $CFG;
        require_once($CFG->libdir . '/filelib.php');

        $format = 'pdf';

        $filerecord = [
            'contextid' => \context_system::instance()->id,
            'component' => 'test',
            'filearea' => 'fileconverter_gotenberg',
            'itemid' => 0,
            'filepath' => '/',
            'filename' => 'gotenberg_test.docx',
        ];

        // Get the fixture doc file content and generate a stored_file object.
        $fs = get_file_storage();
        $testdocx = $fs->get_file(
            $filerecord['contextid'],
            $filerecord['component'],
            $filerecord['filearea'],
            $filerecord['itemid'],
            $filerecord['filepath'],
            $filerecord['filename']
        );

        if (!$testdocx) {
            $fixturefile = dirname(__DIR__) . '/tests/fixtures/source.docx';
            $testdocx = $fs->create_file_from_pathname($filerecord, $fixturefile);
        }

        $conversions = \core_files\conversion::get_conversions_for_file($testdocx, $format);
        foreach ($conversions as $conversion) {
            if ($conversion->get('id')) {
                $conversion->delete();
            }
        }

        $conversion = new \core_files\conversion(0, (object) [
            'sourcefileid' => $testdocx->get_id(),
            'targetformat' => $format,
        ]);
        $conversion->create();

        // Convert the doc file to the target format and send it direct to the browser.
        $this->start_document_conversion($conversion);

        readfile_accel($conversion->get_destfile(), 'application/pdf', true);
    }

    /**
     * Whether the plugin is configured and the Gotenberg server is reachable.
     *
     * @return  bool
     */
    public static function are_requirements_met() {
        if (self::$requirementsmet === null) {
            self::$requirementsmet = self::test_connection()->status === self::STATUS_OK;
        }

        return self::$requirementsmet;
    }

    /**
     * Check whether the Gotenberg server is configured and reachable.
     *
     * @return  \stdClass
     */
    public static function test_connection() {
        $ret = new \stdClass();
        $ret->status = self::STATUS_OK;
        $ret->message = null;

        $url = get_config('fileconverter_gotenberg', 'url');
        if (empty($url)) {
            $ret->status = self::STATUS_EMPTY;
            return $ret;
        }

        $curl = new \curl();
        $curl->get(rtrim($url, '/') . '/health', [], ['CURLOPT_TIMEOUT' => 5]);
        $info = $curl->get_info();

        if ($curl->get_errno() || ($info['http_code'] ?? 0) != 200) {
            $ret->status = self::STATUS_UNREACHABLE;
            $ret->message = $curl->error;
        }

        return $ret;
    }

    /**
     * Whether a file conversion can be completed using this converter.
     *
     * @param   string $from The source type
     * @param   string $to The destination type
     * @return  bool
     */
    public static function supports($from, $to) {
        return $to === 'pdf' && isset(self::$imports[\core_text::strtolower($from)]);
    }

    /**
     * A list of the supported conversions.
     *
     * @return  string
     */
    public function get_supported_conversions() {
        return implode(', ', array_keys(self::$imports));
    }
}
