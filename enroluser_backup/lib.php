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
 * 
 * Version details.
 *
 * @package    local_enroluser
 * @author     Andreas Grabs <moodle@grabs-edv.de>
 * @copyright  Andreas Grabs
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


function local_enroluser_after_require_login(){
    global $USER, $DB, $CFG;

    $user_id = $USER->id;  //get current userid
    $user_name = $USER->username;  //get current username

    //get moodle app and moodle app value from config table
    if(empty($CFG->apiclientid)){
        $moodleapp = "MoodleApp";   //client id
    }
    elseif(!empty($CFG->apiclientid)){
        $moodleapp = $CFG->apiclientid;
    }
    $moodleapp_value = $CFG->apiclientsecret;   //client secret

    $curl = curl_init();

    curl_setopt_array($curl, array(
    CURLOPT_URL => "https://bims.bsmrmu.edu.bd:44396/api/MoodleCourseData?teacherCode=AU",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => "GET",
    CURLOPT_HTTPHEADER => array(
        "cache-control: no-cache", "Origin: training.bsmrmu.edu.bd", "$moodleapp: $moodleapp_value"
    ),
    ));
    

    $response = curl_exec($curl);
    $err = curl_error($curl);

    curl_close($curl);
    $data = json_decode( preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $response), true ); //convert json data into array
    
    $arr = array();
    $arr = $DB->get_records('course',[]);   //get course data from course table
  
    //get course shortname by current userid from json file into an array
    $short = array();
    
    foreach($data as $row){
        $str_arr = preg_split ("/\,/", $row['enrolledstudents']);   //split the student id with ',' 
        //store the all shortname in the short array by enrolledstudent id and teacher username
        foreach($str_arr as $str){
            if($row['username'] == $user_name || $str == $user_name){
                $short[]=$row['shortname'];
            } 
        }
    }
    
    if(!empty($CFG->enableordisableplugin)){ //if this condition is satisfied then the plugin will be enable
        //unenrol a user(teacher/student) who is not in a particular api/json data course info
        if(!empty($CFG->enableordisablestutendunenrol)){ //if this condition is satisfied then stdudent unenrol option will be enable, student can unenroll
            foreach($arr as $row){
                if(!in_array($row->shortname,$short)){
                    $sql_remove= $DB->get_record_sql("SELECT * FROM {user_enrolments} WHERE enrolid IN (SELECT id FROM (SELECT id,shortname,user FROM (SELECT cur.shortname AS shortname, enr.courseid, ue.userid AS user, ue.enrolid AS id
                                    FROM {course} AS cur
                                    LEFT JOIN {enrol} AS enr ON enr.courseid = cur.id
                                    LEFT JOIN {user_enrolments} AS ue ON ue.enrolid = enr.id) AS tb) AS new WHERE shortname = '".$row->shortname."' AND user = '".$user_id."') ");
    
                    $DB->delete_records('user_enrolments', ['enrolid'=>$sql_remove->enrolid]);
                }
            }
        }
        
        //store the all course shortname from course table to dshort array
        $dshort = array();
        foreach($arr as $row){
            $dshort[]=$row->shortname;
        }
        
        foreach( $data as $row){
            if (in_array($row['shortname'],$dshort)) {
                foreach($str_arr as $str){
                    if(in_array($row['shortname'],$short) ){

                        $get_courseid = $DB->get_record_sql("SELECT * FROM {course} WHERE shortname = '".$row['shortname']."'");    //get courseid
                        $manual_enrolid = $DB->get_record_sql("SELECT id FROM {enrol} WHERE courseid = $get_courseid->id AND enrol = 'manual' ");   //get manual enrolid of new course from enrol table
                        $context_info = $DB->get_record_sql("SELECT * FROM {context} WHERE instanceid = $get_courseid->id AND contextlevel = 50");
                        
                        $sql_check = $DB->get_record_sql("SELECT * FROM {user_enrolments} WHERE enrolid = $manual_enrolid->id AND userid = $user_id "); //check logged in user(student) enrol or not
                        //enrol student in the user_enrolments table if the student not enrolled in a particular course
                        if(!empty($CFG->enableordisablestutendenrol)){  //if this condition is satisfied then stdudent enroll option will be enable, student can enroll
                            if(empty($sql_check)){
                                $enrol_std = new stdClass();
                                $enrol_std->enrolid = $manual_enrolid->id;
                                $enrol_std->userid = $user_id;
                                $DB->insert_record('user_enrolments', $enrol_std);
                                
                                $role_exist = $DB->get_record_sql("SELECT * FROM {role_assignments} WHERE contextid = $context_info->id AND userid = $user_id "); //check logged in user student role exist or not
                                if(empty($role_exist)){
                                    $role_assign = new stdClass();
                                    $role_assign-> roleid = 5;
                                    $role_assign-> contextid = $context_info->id;
                                    $role_assign-> userid = $user_id;
                                    $DB->insert_record('role_assignments', $role_assign);
                                }
                            } 
                        }    
                    }
                }
            }
        }
    }
}

