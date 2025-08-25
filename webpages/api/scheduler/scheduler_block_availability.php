
<?php

// Copyright (c) 2023 BC Holmes. All rights reserved. See copyright document for more details.
// This function calculates initial metrics for the auto-scheduler.

if (file_exists(__DIR__ . '/../../config/db_name.php')) {
    include __DIR__ . '/../../config/db_name.php';
}

require_once(__DIR__ . '/../db_support_functions.php');
require_once(__DIR__ . '/../authentication.php');
require_once(__DIR__ . '/../http_session_functions.php');
require_once(__DIR__ . '/scheduler_block_model.php');
require_once(__DIR__ . '/../con_info.php');

start_session_if_necessary();
$db = connect_to_db(true);
$authentication = new Authentication();
try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && $authentication->isProgrammingStaff()) {

        $days = SchedulerDay::findAllForCurrentCon($db);

        header('Content-type: application/json; charset=utf-8');

        $json_string = json_encode(SchedulerDay::asJsonList($days));
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