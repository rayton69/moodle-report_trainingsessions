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
 * Report settings
 *
 * @package    report
 * @subpackage courseoverview
 * @copyright  1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    // no report settings
    $yesnoopts = array(0 => get_string('no'), 1 => get_string('yes'));
    $settings->add(new admin_setting_configselect('report_trainingsessions/csv_iso', get_string('csvoutputtoiso', 'report_trainingsessions'), get_string('csvoutputtoiso_desc', 'report_trainingsessions'), 0, $yesnoopts));

    $settings->add(new admin_setting_configtext('report_trainingsessions/recipient', get_string('recipient', 'report_trainingsessions'),
                       get_string('recipient_desc', 'report_trainingsessions'), ''));

    $settings->add(new admin_setting_configtext('report_trainingsessions/sessionreportdoctitle', get_string('sessionreporttitle', 'report_trainingsessions'),
                       get_string('sessionreporttitle_desc', 'report_trainingsessions'), ''));

    $settings->add(new admin_setting_configcheckbox('report_trainingsessions/printidnumber', get_string('printidnumber', 'report_trainingsessions'),
                       get_string('printidnumber_desc', 'report_trainingsessions'), ''));

    $settings->add(new admin_setting_configcheckbox('report_trainingsessions/hideemptymodules', get_string('hideemptymodules', 'report_trainingsessions'),
                       get_string('hideemptymodules_desc', 'report_trainingsessions'), 1));

    $settings->add(new admin_setting_configcheckbox('report_trainingsessions/printsessiontotal', get_string('printsessiontotal', 'report_trainingsessions'),
                       get_string('printsessiontotal_desc', 'report_trainingsessions'), 1));

    $novalue = array('0' => get_string('disabled', 'report_trainingsessions'));
    $fieldoptions = array_merge($novalue, $DB->get_records_menu('user_info_field', array(), 'id', 'id,name'));
    $settings->add(new admin_setting_configselect('report_trainingsessions/extrauserinfo1', get_string('extrauserinfo', 'report_trainingsessions').' 1',
                       get_string('extrauserinfo_desc', 'report_trainingsessions'), '', $fieldoptions));

    $settings->add(new admin_setting_configselect('report_trainingsessions/extrauserinfo2', get_string('extrauserinfo', 'report_trainingsessions').' 2',
                       get_string('extrauserinfo_desc', 'report_trainingsessions'), '', $fieldoptions));

    $settings->add(new admin_setting_heading('colors', get_string('colors', 'report_trainingsessions'), ''));

    // PDF Text colour setting.
    $name = 'report_trainingsessions/textcolor';
    $title = get_string('textcolor', 'report_trainingsessions');
    $description = get_string('textapplication', 'report_trainingsessions');
    $default = '#000000';
    $previewconfig = null;
    $setting = new admin_setting_configcolourpicker($name, $title, $description, $default, $previewconfig);
    $settings->add($setting);

    // PDF Head1 BackgroundColour setting.
    $name = 'report_trainingsessions/head1bgcolor';
    $title = get_string('bgcolor', 'report_trainingsessions').' 1';
    $description = get_string('head1application', 'report_trainingsessions');
    $default = '#91C1CA';
    $previewconfig = null;
    $setting = new admin_setting_configcolourpicker($name, $title, $description, $default, $previewconfig);
    $settings->add($setting);

    // PDF Head1 Text setting.
    $name = 'report_trainingsessions/head1textcolor';
    $title = get_string('textcolor', 'report_trainingsessions').' 1';
    $description = get_string('head1application', 'report_trainingsessions');
    $default = '#ffffff';
    $previewconfig = null;
    $setting = new admin_setting_configcolourpicker($name, $title, $description, $default, $previewconfig);
    $settings->add($setting);

    // PDF Head1 BackgroundColour setting.
    $name = 'report_trainingsessions/head2bgcolor';
    $title = get_string('bgcolor', 'report_trainingsessions').' 2';
    $description = get_string('head2application', 'report_trainingsessions');
    $default = '#91C1CA';
    $previewconfig = null;
    $setting = new admin_setting_configcolourpicker($name, $title, $description, $default, $previewconfig);
    $settings->add($setting);

    // PDF Head1 Text setting.
    $name = 'report_trainingsessions/head2textcolor';
    $title = get_string('textcolor', 'report_trainingsessions').' 2';
    $description = get_string('head2application', 'report_trainingsessions');
    $default = '#ffffff';
    $previewconfig = null;
    $setting = new admin_setting_configcolourpicker($name, $title, $description, $default, $previewconfig);
    $settings->add($setting);

    // PDF Summarizer Colour setting.
    $name = 'report_trainingsessions/head3bgcolor';
    $title = get_string('bgcolor', 'report_trainingsessions').' 3';
    $description = get_string('head3application', 'report_trainingsessions');
    $default = '#C06361';
    $previewconfig = null;
    $setting = new admin_setting_configcolourpicker($name, $title, $description, $default, $previewconfig);
    $settings->add($setting);

    // PDF Summarizer Text setting.
    $name = 'report_trainingsessions/head3textcolor';
    $title = get_string('textcolor', 'report_trainingsessions').' 3';
    $description = get_string('head3application', 'report_trainingsessions');
    $default = '#ffffff';
    $previewconfig = null;
    $setting = new admin_setting_configcolourpicker($name, $title, $description, $default, $previewconfig);
    $settings->add($setting);

    $settings->add(new admin_setting_heading('layout', get_string('layout', 'report_trainingsessions'), ''));

    $settings->add(new admin_setting_configcheckbox('report_trainingsessions/showhits', get_string('showhits', 'report_trainingsessions'),
                       get_string('showhits_desc', 'report_trainingsessions'), 0));

    $settings->add(new admin_setting_configstoredfile('pdfreportheader', get_string('pdfreportheader', 'report_trainingsessions'),
                       get_string('pdfreportheader_desc', 'report_trainingsessions'), 'pdfreportheader', 0));

    $settings->add(new admin_setting_configstoredfile('pdfreportinnerheader', get_string('pdfreportinnerheader', 'report_trainingsessions'),
                       get_string('pdfreportinnerheader_desc', 'report_trainingsessions'), 'pdfreportinnerheader', 0));

    $settings->add(new admin_setting_configstoredfile('pdfreportfooter', get_string('pdfreportfooter', 'report_trainingsessions'),
                       get_string('pdfreportfooter_desc', 'report_trainingsessions'), 'pdfreportfooter'));

    $settings->add(new admin_setting_configtext('report_trainingsessions/pdfabsoluteverticaloffset', get_string('pdfabsoluteverticaloffset', 'report_trainingsessions'),
                       get_string('pdfabsoluteverticaloffset_desc', 'report_trainingsessions'), '70'));

    $settings->add(new admin_setting_configtext('report_trainingsessions/pdfpagecutoff', get_string('pdfpagecutoff', 'report_trainingsessions'),
                       get_string('pdfpagecutoff_desc', 'report_trainingsessions'), '225'));

    $settings->add(new admin_setting_heading('coupling', get_string('coupling', 'report_trainingsessions'), ''));

    $settings->add(new admin_setting_configcheckbox('report_trainingsessions/enablelearningtimecheckcoupling', get_string('enablelearningtimecheckcoupling', 'report_trainingsessions'),
                       get_string('enablelearningtimecheckcoupling_desc', 'report_trainingsessions'), '225'));

    $cropoptions = array('mark' => get_string('mark', 'report_trainingsessions'), 'crop' => get_string('crop', 'report_trainingsessions'));
    $settings->add(new admin_setting_configselect('report_trainingsessions/learningtimesessioncrop', get_string('learningtimesessioncrop', 'report_trainingsessions'),
                       get_string('learningtimesessioncrop_desc', 'report_trainingsessions'), 'mark', $cropoptions));
}
