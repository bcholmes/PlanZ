<?php
// Copyright (c) 2023 BC Holmes. All rights reserved. See copyright document for more details.
// This function serves as a REST API to get the preview data for sending emails

if (file_exists(__DIR__ . '/../../config/db_name.php')) {
    include __DIR__ . '/../../config/db_name.php';
}
require_once(__DIR__ . '/../http_session_functions.php');
require_once(__DIR__ . '/../db_support_functions.php');
require_once(__DIR__ . '/../format_functions.php');
require_once(__DIR__ . '/../../data_functions.php');
require_once(__DIR__ . '/../authentication.php');
require_once(__DIR__ . '/email_model.php');
require_once(__DIR__ . '/email_schedule_generator.php');
require_once(__DIR__ . '/email_history_model.php');
require_once(__DIR__ . '/../../email_functions.php');
require_once(__DIR__ . '/../../name.php');
require_once(__DIR__ . '/../../email_functions.php');

function is_input_data_valid($json) {
    return array_key_exists("to", $json) && array_key_exists("text", $json);
}

start_session_if_necessary();
$db = connect_to_db(true);
date_default_timezone_set(PHP_DEFAULT_TIMEZONE);
$authentication = new Authentication();
try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $authentication->isEmailAllowed()) {

        $json_string = file_get_contents('php://input');
        $json = json_decode($json_string, true);

        if (is_input_data_valid($json)) {

            $emailTo = EmailTo::findById($db, $json["to"]);
            $text = $json["text"];

            if ($text != null && $emailTo != null) {

                $receivers = $emailTo->resolveEmailAddresses($db);

                $sampleText = "";
                if (count($receivers) > 0) {
                    $firstReceiver = array();
                    $to = $receivers[0];
                    $firstReceiver[] = $to;

                    $scheduleSubstitution = checkForShowSchedule($text);
                    $schedules = array();
                    if ($scheduleSubstitution == SHOW_FULL_SCHEDULE || $scheduleSubstitution == SHOW_EVENT_SCHEDULE) {
                        $schedules = generate_email_schedules($db, $scheduleSubstitution, $firstReceiver);
                    }
                    $sampleText = $to->performSubstitutionOnText($text, isset($schedules[$to->badgeId]) ? $schedules[$to->badgeId] : null);
                }

                $recipients = array();
                foreach ($receivers as $r) {
                    $recipients[] = $r->asJson();
                }

                header('Content-type: application/json; charset=utf-8');
                $json_string = json_encode(array("text" => $sampleText, "recipients" => $recipients));
                echo $json_string;

            } else {
                http_response_code(400);
            }

        } else {
            http_response_code(400);
        }
    } else if ($_SERVER['REQUEST_METHOD'] === 'POST' && $authentication->isLoggedIn()) {
        http_response_code(403);
    } else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        http_response_code(401);
    } else {
        http_response_code(405);
    }
} finally {
    $db->close();
}
?>