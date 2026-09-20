<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ExamenesModel extends Model
{
    protected $connection = 'mysql_grupoihb';

    public function obtenerCursosAV()
    {
        return DB::connection($this->connection)->select("
            SELECT
                c.id            AS course_id,
                c.fullname      AS course_name,
                q.id            AS quiz_id,
                q.name          AS quiz_name,
                FROM_UNIXTIME(c.timecreated) AS course_created
            FROM mdl_course c
            JOIN mdl_course_modules cm ON cm.course = c.id
            JOIN mdl_modules m         ON m.id = cm.module AND m.name = 'quiz'
            JOIN mdl_quiz q            ON q.id = cm.instance
            WHERE c.visible = 1
            ORDER BY c.fullname, q.name
        ");
    }

    public function obtenerDetallesExamenAV($dni, $quizId)
    {
        $sql = "
        SELECT
            c.fullname                                                     AS course_name,
            DATE_FORMAT(FROM_UNIXTIME(qa.timestart), '%d/%m/%y %H:%i:%s')  AS attempt_date,
            qa.id                                                          AS attempt_id,
            ROUND(qa.sumgrades, 2)                                         AS obtained_grade,
            qa.attempt                                                     AS attempt_number,
            FROM_UNIXTIME(qa.timestart, '%H:%i:%s')                        AS start_time,
            FROM_UNIXTIME(qa.timefinish, '%H:%i:%s')                       AS end_time,
            SEC_TO_TIME(qa.timefinish - qa.timestart)                      AS duration,
            ROUND(qa.sumgrades / NULLIF(quiz.sumgrades, 0) * 100, 2)       AS passing_percentage,
            COALESCE(ans.correct_answers, 0)                               AS correct_answers,
            COALESCE(ans.incorrect_answers, 0)                             AS incorrect_answers,
            CONCAT(u.firstname, ' ', u.lastname)                           AS full_name
        FROM mdl_quiz_attempts qa
        JOIN mdl_user   u    ON u.id  = qa.userid
        JOIN mdl_quiz   quiz ON quiz.id = qa.quiz
        JOIN mdl_course c    ON c.id  = quiz.course
        LEFT JOIN (
            SELECT
                qat.questionusageid,
                SUM(CASE WHEN ls.fraction = 1 THEN 1 ELSE 0 END)                          AS correct_answers,
                SUM(CASE WHEN ls.fraction IS NULL OR ls.fraction < 1 THEN 1 ELSE 0 END)   AS incorrect_answers
            FROM mdl_question_attempts qat
            JOIN mdl_question_attempt_steps ls
                   ON ls.questionattemptid = qat.id
                  AND ls.sequencenumber = (
                        SELECT MAX(s2.sequencenumber)
                        FROM mdl_question_attempt_steps s2
                        WHERE s2.questionattemptid = qat.id
                  )
            GROUP BY qat.questionusageid
        ) ans ON ans.questionusageid = qa.uniqueid
        WHERE qa.quiz    = ?
          AND u.username = ?
          AND qa.id = (
                SELECT qa3.id
                FROM mdl_quiz_attempts qa3
                JOIN mdl_user u3 ON u3.id = qa3.userid
                WHERE qa3.quiz   = ?
                  AND u3.username = ?
                ORDER BY qa3.timestart DESC
                LIMIT 1
          )
        LIMIT 1
    ";

        $result = DB::connection($this->connection)->select($sql, [
            $quizId,
            $dni,
            $quizId,
            $dni,
        ]);

        return $result[0] ?? null;
    }

    public function obtenerPreguntasYRespuestasAV($dni, $attemptId)
    {
        $sql = "
            SELECT
                q.questiontext      AS question,
                qat.responsesummary AS response
            FROM mdl_quiz_attempts qa
            JOIN mdl_user u
                    ON u.id = qa.userid
            JOIN mdl_question_usages qu
                    ON qu.id = qa.uniqueid
            JOIN mdl_question_attempts qat
                    ON qat.questionusageid = qu.id
            JOIN mdl_question q
                    ON q.id = qat.questionid
            JOIN mdl_question_attempt_steps laststep
                    ON laststep.questionattemptid = qat.id
                AND laststep.sequencenumber = (
                        SELECT MAX(s2.sequencenumber)
                        FROM mdl_question_attempt_steps s2
                        WHERE s2.questionattemptid = qat.id
                )
            WHERE qa.id       = ?
            AND u.username  = ?
            ORDER BY qat.slot
        ";

        $rows = DB::connection($this->connection)->select($sql, [
            $attemptId,
            strtolower(trim($dni)),
        ]);

        return array_map(function ($row) {
            return (object) [
                'question'  => html_entity_decode($row->question,  ENT_QUOTES | ENT_HTML5, 'UTF-8'),
                'response' => html_entity_decode($row->response, ENT_QUOTES | ENT_HTML5, 'UTF-8'),
            ];
        }, $rows);
    }
}
