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
 * Strings for plugin 'fileconverter_gotenberg'.
 *
 * @package    fileconverter_gotenberg
 * @copyright  2026 Marius Preuss
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Gotenberg';
$string['privacy:metadata'] = 'The Gotenberg document converter plugin does not store any personal data itself, though the content of the document being converted is transiently sent to the configured Gotenberg server for the duration of the conversion.';
$string['test_gotenberg'] = 'Test Gotenberg connection';
$string['test_gotenbergdownload'] = 'Download the converted pdf test file.';
$string['test_gotenbergempty'] = 'The Gotenberg URL is not set. Please review your settings.';
$string['test_gotenbergok'] = 'The connection to Gotenberg appears to be working.';
$string['test_gotenbergunreachable'] = 'The Gotenberg server could not be reached. Please check the URL and that the server is running.';
$string['timeout'] = 'Request timeout';
$string['timeout_desc'] = 'The number of seconds to wait for Gotenberg to respond before giving up on a conversion.';
$string['url'] = 'Gotenberg URL';
$string['url_desc'] = 'The base URL of your Gotenberg instance, for example http://gotenberg:3000. Leave empty to disable this converter.';
