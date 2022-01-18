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
 * Local plugin "enroluser" - Settings
 *
 * @package    local_enroluser
 * @copyright  2017 Alexander Bias, Ulm University <alexander.bias@uni-ulm.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/lib.php');

global $CFG;
// Ensure the configurations for this site are set
if ($hassiteconfig) {
    // Create admin settings category.
    $ADMIN->add('courses', new admin_category('local_enroluser',
            get_string('settingname', 'local_enroluser', null, true)));

    // Create empty settings page structure to make the site administration work on non-admin pages.
    if (!$ADMIN->fulltree) {
        // Settings page: Root nodes.
        $page = new admin_settingpage('local_enroluser_rootnodes',
                get_string('settingspage_integration', 'local_enroluser', null, true));
        $ADMIN->add('local_enroluser', $page);
    }
    // Create full settings page structure.
    elseif ($ADMIN->fulltree) {
        $page = new admin_settingpage('local_enroluser_rootnodes',
                get_string('settingspage_integration', 'local_enroluser', null, true));

        $ADMIN->add('local_enroluser', $page);

        $page->add(new admin_setting_configtext('teacherdataapiurl', 'Teacher Data API URL', 
                'Insert teacher data API url', 'Null'));
        $page->add(new admin_setting_configtext('studentdataapiurl', 'Student Data API URL', 
                'Insert student data API url', 'Null'));

        $page->add(new admin_setting_configtext('apiclientid', 'UCAM API Client Id', 
                'Insert the API client Id', 'MoodleApp'));
        
        $page->add(new admin_setting_configtext('apiclientsecret', 'UCAM API Client Secret', 
                'Insert the API client secret', 'Null'));

        $page->add(new admin_setting_configcheckbox('enableordisableplugin', 'Plugin State', 
                'If enabled then trigger each time a teacher/student login into Moodle', 0));

        $page->add(new admin_setting_configcheckbox('enableordisablestudentenrol', 'Student Enroll', 
                'If enabled then enroll student to his/her courses if not enrolled yet', 0));

        $page->add(new admin_setting_configcheckbox('enableordisablestudentunenrol', 'Student Unenroll', 
                'If enabled then unenroll student from courses based on API & existing enrolled courses in Moodle', 0));
        
        $page->add(new admin_setting_configcheckbox('enableordisableteacherenrol', 'Teacher Enroll', 
                'If enabled then enroll teacher to his/her courses if not enrolled yet', 0));
        
        $page->add(new admin_setting_configcheckbox('enableordisableteacherunenrol', 'Teacher Unenroll', 
                'If enabled then unenroll teacher from courses based on API & existing enrolled courses in Moodle', 0));

        $page->add(new admin_setting_configcheckbox('enableordisableofnewcoursecreation', 'New Course Creation', 
                'If enabled then add new courses with category', 0));
    }
}
