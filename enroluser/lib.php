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
    $user_name = $USER->username;   //get current username

    //get moodle app and moodle app value from config table
    if(empty($CFG->apiclientid)){
        $moodleapp = "MoodleApp";   //client id
    }
    elseif(!empty($CFG->apiclientid)){
        $moodleapp = $CFG->apiclientid;
    }
    $moodleapp_value = $CFG->apiclientsecret;   //client secret

    //check current login user is student or teacher
    if(!preg_match('/^\d+$/', $user_name)){
        $APIurl = $CFG->teacherdataapiurl;  //store teacherAPIurl from config table
    }
    elseif(preg_match('/^\d+$/', $user_name)){
        $APIurl = $CFG->studentdataapiurl;  //store studentAPIurl from config table
    }

    $curl = curl_init();

    curl_setopt_array($curl, array(
    CURLOPT_URL => "$APIurl=$user_name",
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
    // print_r($data);
    $course_infos = array();
    $course_infos = $DB->get_records('course',[]);   //get course data from moodle course table
    
    //store course shortname by current username from api into an array
    $short = array();
    foreach($data as $row){
        if($row['username'] == $user_name){
            $short[]=$row['shortname'];
        }
    }
    
    if($CFG->enableordisableplugin == 1){ //if this condition is satisfied then the plugin will be enable
        //unenrol a user(teacher) who is not in a particular api/json data course info
        if($CFG->enableordisableteacherunenrol == 1){    //if this condition is satisfied then teacher unenrol option will be enable, teacher will be unenroll
            foreach($course_infos as $course){
                if(!in_array($course->shortname, $short) && (!preg_match('/^\d+$/', $user_name))){ 
                //with above not preg_match try to indentify current(current login) user_name is teacher or not
                    //if particualr course not in api but userid(enrolledstudents) of this course vissible in user_enrolments table then remove this userid         
                    $teacher_remove = $DB->get_record_sql("SELECT * FROM {user_enrolments} ue 
                                                        JOIN {enrol} e ON ue.enrolid = e. id 
                                                        JOIN {course} c ON e.courseid = c.id 
                                                        JOIN {user} u ON ue.userid = u.id 
                                                        WHERE c.shortname= '".$course->shortname."' AND ue.userid = '".$user_id."'");
                    if(!empty($teacher_remove)){
                        $DB->delete_records('user_enrolments', ['enrolid'=>$teacher_remove->enrolid,'userid'=>$user_id]);
                    }
                }
            }
        }
        //unenrol a user(student) who is not in a particular api/json data course info
        if($CFG->enableordisablestudentunenrol == 1){ //if this condition is satisfied then stdudent unenrol option will be enable, student can unenroll
            foreach($course_infos as $course){
                if(!in_array($course->shortname, $short) && (preg_match('/^\d+$/', $user_name))){
                //with above preg_match try to indentify current(current login) user_name is student or not
                    //if particualr course not in api but userid(enrolledstudents) of this course vissible in user_enrolments table then remove this userid    
                    $student_remove = $DB->get_record_sql("SELECT * FROM {user_enrolments} ue 
                                                        JOIN {enrol} e ON ue.enrolid = e. id 
                                                        JOIN {course} c ON e.courseid = c.id 
                                                        JOIN {user} u ON ue.userid = u.id 
                                                        WHERE c.shortname= '".$course->shortname."' AND ue.userid = '".$user_id."'");
                    if(!empty($student_remove)){
                        $DB->delete_records('user_enrolments', ['enrolid'=>$student_remove->enrolid,'userid'=>$user_id]);
                    }
                }
            } 
        }
        
        //store the all course shortname from course table to dshort array
        $dshort = array();
        foreach($course_infos as $course){
            $dshort[]=$course->shortname;
        }
        
        foreach($data as $row){
            
            $get_courseid = $DB->get_record_sql("SELECT * FROM {course} WHERE shortname = '".$row['shortname']."'");    //get courseid
            $manual_enrolid = $DB->get_record_sql("SELECT * FROM {enrol} WHERE courseid = $get_courseid->id AND enrol = 'manual' ");   //get manual enrolid of new course from enrol table
            $context_info = $DB->get_record_sql("SELECT * FROM {context} WHERE instanceid = $get_courseid->id AND contextlevel = 50");  //get context info

            $sql_check = $DB->get_record_sql("SELECT * FROM {user_enrolments} WHERE enrolid = $manual_enrolid->id AND userid = $user_id "); //check logged in user(student/teacher) enrol or not

            if(in_array($row['shortname'], $dshort) ){
                if(in_array($row['shortname'], $short) && (!preg_match('/^\d+$/', $user_name)) ){
                    //enrol teacher in the user_enrolments table if the student not enrolled in a particular course
                    if($CFG->enableordisableteacherenrol == 1){  //if this condition is satisfied then teacher enroll option will be enable, teacher can enroll
                        if(empty($sql_check)){
                            $enrol_teacher = new stdClass();
                            $enrol_teacher->enrolid = $manual_enrolid->id;
                            $enrol_teacher->userid = $user_id;
                            $DB->insert_record('user_enrolments', $enrol_teacher);

                            $teacher_role_exist = $DB->get_record_sql("SELECT * FROM {role_assignments} WHERE contextid = $context_info->id AND userid = $user_id "); //check logged in user teacher role exist or not
                            //teacher role assign if not exist in role_assignment table
                            if(empty($teacher_role_exist)){
                                $role_assign = new stdClass();
                                $role_assign-> roleid = 3;
                                $role_assign-> contextid = $context_info->id;
                                $role_assign-> userid = $user_id;
                                $DB->insert_record('role_assignments', $role_assign);
                            }
                        }
                    }
                }
            }
            
            if (in_array($row['shortname'],$dshort) ) {
                if(in_array($row['shortname'],$short) && (preg_match('/^\d+$/', $user_name))){
                    //enrol student in the user_enrolments table if the student not enrolled in a particular course
                    if($CFG->enableordisablestudentenrol == 1){  //if this condition is satisfied then stdudent enroll option will be enable, student can enroll
                        if(empty($sql_check)){
                            $enrol_std = new stdClass();
                            $enrol_std->enrolid = $manual_enrolid->id;
                            $enrol_std->userid = $user_id;
                            $DB->insert_record('user_enrolments', $enrol_std);
                            
                            $std_role_exist = $DB->get_record_sql("SELECT * FROM {role_assignments} WHERE contextid = $context_info->id AND userid = $user_id "); //check logged in user student role exist or not
                            //student role assign if not exist in role_assignment table
                            if(empty($std_role_exist)){
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

