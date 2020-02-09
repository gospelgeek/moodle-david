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
 * @package    report_coursesstatus
 * @copyright 2017 David Herney Bernal - cirano
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once('locallib.php');

function prettyPrint( $json )
{
    $result = '';
    $level = 0;
    $in_quotes = false;
    $in_escape = false;
    $ends_line_level = NULL;
    $json_length = strlen( $json );

    for( $i = 0; $i < $json_length; $i++ ) {
        $char = $json[$i];
        $new_line_level = NULL;
        $post = "";
        if( $ends_line_level !== NULL ) {
            $new_line_level = $ends_line_level;
            $ends_line_level = NULL;
        }
        if ( $in_escape ) {
            $in_escape = false;
        } else if( $char === '"' ) {
            $in_quotes = !$in_quotes;
        } else if( ! $in_quotes ) {
            switch( $char ) {
                case '}': case ']':
                    $level--;
                    $ends_line_level = NULL;
                    $new_line_level = $level;
                    break;

                case '{': case '[':
                    $level++;
                case ',':
                    $ends_line_level = $level;
                    break;

                case ':':
                    $post = " ";
                    break;

                case " ": case "\t": case "\n": case "\r":
                    $char = "";
                    $ends_line_level = $new_line_level;
                    $new_line_level = NULL;
                    break;
            }
        } else if ( $char === '\\' ) {
            $in_escape = true;
        }
        if( $new_line_level !== NULL ) {
            $result .= "\n".str_repeat( "\t", $new_line_level );
        }
        $result .= $char.$post;
    }

    return $result;
}

echo $OUTPUT->header();

flush();

echo $OUTPUT->heading( "Reporte de curso [id:2]" );
echo $OUTPUT->box_start();
echo "<strong>Mensajes recibidos y enviados por cada usuario del sitio.</strong><br>";
echo "<pre><code>" . prettyPrint( json_encode( generate_report( get_all_users() ) ) ) . "</code></pre>";
echo "<br><br><strong>Estudiantes que han ingresado al menos una vez al curso.</strong><br>";
echo "<pre><code>" . prettyPrint( json_encode( get_students_with_last_access( 2 ) ) ) . "</code></pre>";
echo "<br><br><strong>Estudiantes que han ingresado la última semana al curso.</strong><br>";
echo "<pre><code>" . prettyPrint( json_encode( get_active_users_by_course( 2 ) ) ) . "</code></pre>";
echo "<br><br><strong>Estudiantes activos por curso en la semana previa a la pasada.</strong><br>";
echo "<pre><code>" . prettyPrint( json_encode( get_active_users_by_course( 2, strtotime("-2 week"),strtotime("-1 week") ) ) ) . "</code></pre>";
echo $OUTPUT->box_end();

echo $OUTPUT->footer();
