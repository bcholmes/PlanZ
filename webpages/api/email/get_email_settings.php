<?php
// Copyright (c) 2023 BC Holmes. All rights reserved. See copyright document for more details.
// This function serves as a REST API to get the various options used to populate the
// email screens.

if (file_exists(__DIR__ . '/../../config/db_name.php')) {
    include __DIR__ . '/../../config/db_name.php';
}
require_once(__DIR__ . '/../http_session_functions.php');
require_once(__DIR__ . '/../db_support_functions.php');
require_once(__DIR__ . '/../format_functions.php');
require_once(__DIR__ . '/../../data_functions.php');
require_once(__DIR__ . '/../authentication.php');
require_once(__DIR__ . '/email_model.php');

start_session_if_necessary();
$db = connect_to_db(true);
date_default_timezone_set(PHP_DEFAULT_TIMEZONE);
$authentication = new Authentication();
try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && $authentication->isEmailAllowed()) {
        $emailCC = EmailCC::findAll($db);
        $cc = [];
        foreach ($emailCC as $e) {
            $cc[] = $e->asArray();
        }

        $emailFrom = EmailFrom::findAll($db);
        $from = [];
        foreach ($emailFrom as $e) {
            $from[] = $e->asArray();
        }

        $emailTo = EmailTo::findAll($db);
        $to = [];
        foreach ($emailTo as $e) {
            $to[] = $e->asArray();
        }

        header('Content-type: application/json; charset=utf-8');
        $json_string = json_encode(array("emailCC" => $cc, "emailFrom" => $from, "emailTo" => $to));
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
