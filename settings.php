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
 * Settings for fileconverter_gotenberg.
 *
 * @package    fileconverter_gotenberg
 * @copyright  2026 Marius Preuss
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$settings->add(new admin_setting_configtext(
    'fileconverter_gotenberg/url',
    new lang_string('url', 'fileconverter_gotenberg'),
    new lang_string('url_desc', 'fileconverter_gotenberg'),
    '',
    PARAM_URL
));

$settings->add(new admin_setting_configtext(
    'fileconverter_gotenberg/timeout',
    new lang_string('timeout', 'fileconverter_gotenberg'),
    new lang_string('timeout_desc', 'fileconverter_gotenberg'),
    30,
    PARAM_INT
));

$url = new moodle_url('/files/converter/gotenberg/testgotenberg.php');
$link = html_writer::link($url, get_string('test_gotenberg', 'fileconverter_gotenberg'));
$settings->add(new admin_setting_heading('fileconverter_gotenberg/test', '', $link));
