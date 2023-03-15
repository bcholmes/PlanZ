<?php

// Copyright (c) 2023 BC Holmes. All rights reserved. See copyright document for more details.
// This function calculates initial metrics for the auto-scheduler.

if (file_exists(__DIR__ . '/../../config/db_name.php')) {
    include __DIR__ . '/../../config/db_name.php';
}

require_once(__DIR__ . '/../db_support_functions.php');
require_once(__DIR__ . '/../authentication.php');
require_once(__DIR__ . '/../http_session_functions.php');
require_once(__DIR__ . "/scheduler_participant_model.php");
require_once(__DIR__ . "/scheduler_session_model.php");


start_session_if_necessary();
$db = connect_to_db(true);
$authentication = new Authentication();
try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && $authentication->isProgrammingStaff()) {

        $sessions = Session::findAllSessions($db);
        $sessionJson = array();
        foreach ($sessions as $s) {
            $sessionJson[] = $s->asJson();
        }

        $participants = PersonData::findAllInterestedParticipants($db);
        $particiapntJson = array();
        foreach ($participants as $p) {
            $particiapntJson[] = $p->asJson();
        }

        header('Content-type: application/json; charset=utf-8');

        $json_string = json_encode(array("sessions" => $sessionJson, "participants" => $particiapntJson));
        echo $json_string;

    } else if ($_SERVER['REQUEST_METHOD'] === 'GET' && $authentication->isLoggedIn()) {
        http_response_code(403);
    } else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        http_response_code(401);
    } else {
        http_response_code(405);
    }
} finally {
    $db->close();
}


?>