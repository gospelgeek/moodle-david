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
 * A report to display the courses status (stats, counters, general information)
 *
 * @package     ...
 * @copyright   2017
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/*
Un reporte de curso y de sitio, que muestre y permita descargar:
- Cantidad de mensajes recibidos por cada estudiante del curso o cada usuario del sitio (mensajería interna).
- Cantidad de mensajes enviados por cada estudiante del curso o cada usuario del sitio (mensajería interna)
- Cantidad de estudiantes que han ingresado por lo menos una vez al curso.
- Cantidad de estudiantes activos por curso. Entendiendo activos como aquellos que han ingresado por lo menos una vez en un período de tiempo determinado (configurable, por defecto sería una semana).
*/

require_once('../../config.php');

function get_active_users_by_course( int $course_id, int $start_time = NULL, int $end_time = NULL ):array
{

    if( is_null($start_time) && is_null($end_time) ){
        $start_time = strtotime("-1 week");
        $end_time = time();
    }

    return get_students_with_last_access( $course_id, $start_time, $end_time );
}

function get_students_with_last_access( int $course_id, int $start_time = NULL, int $end_time = NULL ): array
{
    global $DB;

    $sql = <<<SQL
        SELECT
            id,
            userid
        FROM 
            {user_lastaccess}
        WHERE
            courseid = :course_id 
    SQL;

    $criteria = [ 'course_id' => $course_id ];

    if( $start_time && $end_time ){
        $sql .= " AND timeaccess >= :start_time AND timeaccess <= :end_time ";
        $criteria[ 'start_time' ] = $start_time;
        $criteria[ 'end_time' ] = $end_time;
    }

    $result = $DB->get_records_sql( $sql, $criteria );

    return get_users_data( 
        array_map(
            function( $in ):int{ return $in->userid; },
            $result
        )
    );
}

function get_users_data( array $ids ):array
{
    if( count( $ids ) == 0 ){
        return [];
    }

    global $DB;

    $sql ="
        SELECT
            id,
            username,
            firstname, 
            lastname
        FROM 
            {user}
        WHERE
            id IN ( " . implode( $ids, "," ) . ")";

    $result = $DB->get_records_sql( $sql );

    return array_Values( $result );
}

function get_all_users():array
{
    global $DB;

    $sql = <<<SQL
        SELECT
            id,
            username,
            firstname, 
            lastname
        FROM 
            {user}
    SQL;

    $result = $DB->get_records_sql( $sql );

    return $result;
}

function generate_report( array $users ):array
{
    $to_return = [];

    foreach ($users as &$user) {

        $userid = $user->id;

        array_push(
            $to_return,
            array(
                'user_data'     => $user,
                'inc_messages'  => get_inc_messages( $userid ),
                'out_messages'  => get_out_messages( $userid )
            )
       );
    }

    return $to_return;
}

/**
 * 
 * @see     get_messages(...) in localib.php
 * 
 * @param   int     $userid             
 * @return  array    
 */
function get_inc_messages( int $userid ):int
{

    global $DB;

    $sql = <<<SQL
        SELECT 
            count(*) AS count
        FROM 
            {message_conversation_members} AS MCM
        INNER JOIN 
            {messages} AS M 
        ON 
            MCM.conversationid = M.conversationid
        WHERE 
            MCM.userid = :id_user AND M.useridfrom != :user_id
    SQL;

    $result = $DB->get_record_sql( $sql, array( 'id_user' => $userid, 'user_id' => $userid ) );

    return $result->count;
}

/**
 * 
 * @see     get_messages(...) in localib.php
 *  
 * @param   int     $userid             
 * @return  array    
 */
function get_out_messages( int $userid ):int
{
    global $DB;

    $sql = <<<SQL
        SELECT 
            count(*) AS count
        FROM 
            {messages} AS M 
        WHERE 
            M.useridfrom = :userid
    SQL;

    $result = $DB->get_record_sql( $sql, array( 'userid' => $userid ) );

    return $result->count;
}