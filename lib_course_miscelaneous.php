<?php

require_once '../../../config.php';

/**
 * get_best_students_nosql
 * @param int $courseid    Course ID
 * @param int $n_students  Number of studentes to return
 * @return Array
 * 
 * @author Samuel Ramirez <samuel.ramirez@correounivalle.edu.co> 
 * @author Iader E. Garcia G. <iadergg@gmail.com>
 */
function get_best_students_nosql($courseid, $nstudents) {

    global $DB, $CFG;

    require_once $CFG->libdir . '/gradelib.php';
    require_once($CFG->dirroot . '/grade/querylib.php');

    $coursecontext = context_course::instance($courseid);

    $usersenrolled = get_enrolled_users($coursecontext, '', 0, 'u.id');

    $usersarray = array();

    foreach ($usersenrolled as $user) {
        array_push($usersarray, $user->id);
    }

    $gradesinfo = grade_get_course_grades($courseid, $usersarray)->grades;

    $grades = array();

    // Order 
    foreach(array_keys($gradesinfo) as $key) {
        $grades[$key] = $gradesinfo[$key]->grade;
        $gradesinfo[$key]->userid = $key;
    }

    array_multisort($grades, SORT_DESC, $gradesinfo);

    $position = 0;
    $beststudents = array();

    foreach(array_keys($gradesinfo) as $key) {
        $position++;

        if($gradesinfo[$key]->grade == NULL){
            $gradesinfo[$key]->grade = "-";
        }

        $temp = array(
            'userid' => $gradesinfo[$key]->userid,
            'finalgrade' => $gradesinfo[$key]->grade,
            'position' => $position
        );

        array_push($beststudents, $temp);

        if($position == $nstudents) {
            break;
        }
    }

    return $beststudents;
}



/**
 * get_info_course_sections
 * @param int $courseid    Course ID
 * @param int $userid      User ID
 * @return stdClass
 * 
 * @author Samuel Ramirez <samuel.ramirez@correounivalle.edu.co> 
 * @author Iader E. Garcia G. <iadergg@gmail.com>
 */

function get_info_course_sections_by_user($courseid, $userid) {

    global $DB;
    
    $sql_query =   "SELECT
                        sections.id,
                        sections.section AS section_position,
                        sections.name AS section_name,
                        COUNT(DISTINCT modules_completion.coursemoduleid) AS modules,
                        SUM(modules_completion.completionstate) AS sum_completionstate,
                        COUNT(DISTINCT modules_completion.coursemoduleid) AS count_coursemoduleid
                    FROM
                        {course_sections} AS sections
                        INNER JOIN {course_modules} AS modules ON modules.section = sections.id
                        LEFT JOIN {course_modules_completion} AS modules_completion ON modules_completion.coursemoduleid = modules.id
                    WHERE
                        sections.course = $courseid
                        AND modules_completion.userid = $userid
                    GROUP BY
                        sections.id,
                        sections.section,
                        sections.name";
    
    $info_sections_array = $DB->get_records_sql($sql_query);
    
    foreach ($info_sections_array as &$info_section) {
        $info_section->percent = $info_section->sum_completionstate / ( $info_section->count_coursemoduleid * 100 );
    }

    return $info_sections_array;
}