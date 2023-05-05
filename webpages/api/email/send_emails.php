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
require_once(__DIR__ . '/email_schedule_generator.php');
require_once(__DIR__ . '/email_history_model.php');
require_once(__DIR__ . '/../../email_functions.php');
require_once(__DIR__ . '/../../external/swiftmailer-5.4.8/lib/swift_required.php');
require_once(__DIR__ . '/../../name.php');
require_once(__DIR__ . '/../../email_functions.php');



function send_individual_email($db, $mailer, $emailToAddress, $name, $emailFrom, $emailCC, $emailReplyTo, $subject, $text) {
    try {
        $message = (new Swift_Message($subject));

        if ($emailToAddress != null && $name != null && $name->getBadgeName() != null && $name->getBadgeName() != "") {
            $message->setTo([$emailToAddress => $name->getBadgeName()]);
        } else if ($emailToAddress != null) {
            $message->setTo($emailToAddress);
        }

        if ($emailCC != null) {
            $message->setCc($emailCC->asSwiftAddress());
        }

        if ($emailReplyTo != null) {
            $message->setReplyTo($emailReplyTo->asSwiftAddress());
        }

        $message->setFrom($emailFrom->asSwiftAddress());
        $message->setBody($text,'text/plain');

        $code = 0;
        try {
            $mailer->send($message);
        } catch (Swift_SwiftException $e) {
            $code = $e->getCode();
            if ($code < 500) {
                queue_email($db, $emailToAddress, $name, $emailFrom, $emailCC, $emailReplyTo, $subject, $text);
            }
        }
        EmailHistory::write($db, $emailToAddress, $name, $emailFrom, $emailCC, $emailReplyTo, $subject, $code);
    } catch (Swift_SwiftException $e) {
    }
}

function queue_email($db, $emailToAddress, $name, $emailFrom, $emailCC, $emailReplyTo, $subject, $text) {
    $badgeName = $name == null ? null : $name->getBadgeName();
    $emailCCAddress = $emailCC == null ? null : $emailCC->address;
    $emailReplyToAddress = $emailReplyTo == null ? null : $emailReplyTo->address;
    $statusCode = 0;
    $sql = "INSERT INTO EmailQueue(emailto, `name`, emailfrom, emailcc, emailreplyto, emailsubject, body, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?);";

    $stmt = mysqli_prepare($db, $sql);
    mysqli_stmt_bind_param($stmt, "sssssssi", $emailToAddress, $badgeName, $emailFrom->address, $emailCCAddress, $emailReplyToAddress, $subject, $text, $statusCode);
    if ($stmt->execute()) {
        mysqli_stmt_close($stmt);
    } else {
        throw new DatabaseSqlException("There was a problem with the update: $sql");
    }
}

function send_individual_emails($db, $mailer, $emailTo, $emailFrom, $emailCC, $emailReplyTo, $subject, $text) {
    $sentCounter = 0;
    $addresses = $emailTo->resolveEmailAddresses($db);
    $scheduleSubstitution = checkForShowSchedule($text);
    $schedules = array();
    if ($scheduleSubstitution == SHOW_FULL_SCHEDULE || $scheduleSubstitution == SHOW_EVENT_SCHEDULE) {
        $schedules = generate_email_schedules($db, $scheduleSubstitution, $addresses);
    }

    foreach ($addresses as $to) {
        $alteredText = $to->performSubstitutionOnText($text, isset($schedules[$to->badgeId]) ? $schedules[$to->badgeId] : null);
        if (SMTP_QUEUEONLY === TRUE || $sentCounter > SMTP_MAX_MESSAGES) {
            queue_email($db, $to->address, $to->name, $emailFrom, $emailCC, $emailReplyTo, $subject, $alteredText);
        } else {
            send_individual_email($db, $mailer, $to->address, $to->name, $emailFrom, $emailCC, $emailReplyTo, $subject, $alteredText);
            $sentCounter = $sentCounter + 1;
        }
    }
}

function is_input_data_valid($json) {
    return array_key_exists("to", $json) && array_key_exists("from", $json) && array_key_exists("text", $json) && array_key_exists("subject", $json);
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
            $mailer = get_swift_mailer();

            $replyToId = array_key_exists("replyTo", $json) ? $json["replyTo"] : null;
            $ccToId = array_key_exists("cc", $json) ? $json["cc"] : null;

            $emailFrom = EmailFrom::findById($db, $json["from"]);
            $emailTo = EmailTo::findById($db, $json["to"]);
            $emailCC = $ccId == null ? null : EmailCC::findById($db, $ccId);
            $emailReplyTo = $replyToId == null ? null : EmailCC::findById($db, $replyToId);

            if ($emailFrom != null && $emailTo != null) {
                send_individual_emails($db, $mailer, $emailTo, $emailFrom, $emailCC, $emailReplyTo, $json["subject"], $json["text"]);
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
