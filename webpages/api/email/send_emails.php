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
require_once(__DIR__ . '/../external/swiftmailer-5.4.8/lib/swift_required.php');

function send_email($db, $emailToAddress, $name, $emailFrom, $emailCC, $emailReplyTo, $subject, $text) {
    try {
        $message = (new Swift_Message($subject));

        if ($emailToAddress != null && $name != null) {
            $message->setTo([$emailToAddress => $name->getBadgeName()]);
        }

        if ($emailCC != null) {
            $message->addBcc($emailCC->asSwiftAddress());
        }

    } catch (Swift_SwiftException $e) {
    }
}

function queue_email($db, $emailToAddress, $name, $emailFrom, $emailCC, $emailReplyTo, $subject, $text) {
    $sql = "INSERT INTO EmailQueue(emailto, `name`, emailfrom, emailcc, emailreplyto, emailsubject, body, status) VALUES (?, ?, ?, ?, ?, ?);";
    $param_arr = array($emailToAddress, $name->getBadgeName(), $emailFrom->address, $emailCC->address, $subject, $text, 0);
    $types = "sssssssi";

    $stmt = mysqli_prepare($db, $sql);
    mysqli_stmt_bind_param($stmt, $types, $value, $param_arr);
    if ($stmt->execute()) {
        mysqli_stmt_close($stmt);
    } else {
        throw new DatabaseSqlException("There was a problem with the update: $sql");
    }
}

function send_emails() {
    for ($i=0; $i<$recipient_count; $i++) {
        $ok=TRUE;
        //Create the message and set subject
        $message = (new Swift_Message($email['subject']));

        $name = new PersonName();
        $name->firstName = $recipientinfo[$i]['firstname'];
        $name->lastName = $recipientinfo[$i]['lastname'];
        $name->badgeName = $recipientinfo[$i]['badgename'];
        $name->pubsName = $recipientinfo[$i]['pubsname'];
        $repl_list = array($recipientinfo[$i]['badgeid'],
            $name->firstName,
            $name->lastName,
            $recipientinfo[$i]['email'],
            $name->getPubsName(),
            $name->getBadgeName());
        $emailverify['body'] = str_replace($subst_list, $repl_list, $email['body']);
        if ($status === "1" || $status === "2") {
            if ($status === "1") {
                $scheduleTag = '$EVENTS_SCHEDULE$';
            } else {
                $scheduleTag = '$FULL_SCHEDULE$';
            }
            if (isset($scheduleInfoArray[$recipientinfo[$i]['badgeid']])) {
                $scheduleInfo = " Start Time      Duration            Room Name          Session ID                      Title\n";
                $scheduleInfo .= implode("\n", $scheduleInfoArray[$recipientinfo[$i]['badgeid']]);
            } else {
                $scheduleInfo = "No scheduled items for you were found.";
            }
            $emailverify['body'] = str_replace($scheduleTag, $scheduleInfo, $emailverify['body']);
        }
        //Define from address
        $message->setFrom($emailfromfull);
        //Define body
        $message->setBody($emailverify['body'],'text/plain');
        //$message =& new Swift_Message($email['subject'],$emailverify['body']);
        echo ($recipientinfo[$i]['pubsname']." - ".$recipientinfo[$i]['email'].": ");
        try {
            $message->setTo([$recipientinfo[$i]['email'] => $name->getBadgeName()]);
        } catch (Swift_SwiftException $e) {
            echo $e->getMessage()."<br>\n";
            $ok=FALSE;
        }
        if ($emailcc != "" && $emailcc != null) {
            $message->addBcc($emailcc);
        }
        if (SMTP_QUEUEONLY === TRUE) {
            $sql = "INSERT INTO EmailQueue(emailto, emailfrom, emailcc, emailsubject, body, status) VALUES(?, ?, ?, ?, ?, ?);";
            $param_arr = array($recipientinfo[$i]['email'] , $emailfrom, $emailcc, $email['subject'], $emailverify['body'], 0);
            $types = "sssssi";
            $rows = mysql_cmd_with_prepare($sql, $types, $param_arr);
            if ($rows == 1)
                echo "Queued<br>";
            else
                echo "Queue failed<br>";
        } else {
            try {
                $code = 0;
                $mailer->send($message);
            }
            catch (Swift_SwiftException $e) {
                $code = $e->getCode();
                if ($code < 500) {
                    echo $e->getMessage() . ", adding to queue<br>\n";
                } else {
                    echo $e->getMessage() . ", not able to be retried.<br>\n";
                }

                $ok = FALSE;
                if ($code < 500) {
                    $sql = "INSERT INTO EmailQueue(emailto, emailfrom, emailcc, emailsubject, body, status) VALUES(?, ?, ?, ?, ?, ?);";
                    $param_arr = array($recipientinfo[$i]['email'] , $emailfrom, $emailcc, $email['subject'], $emailverify['body'], $e->getCode());
                    $types = "sssssi";
                    $rows = mysql_cmd_with_prepare($sql, $types, $param_arr);
                }
            }
            if ($ok == TRUE) {
                echo "Sent<br>";
            }
        }
        $sql = "INSERT INTO EmailHistory(emailto, emailfrom, emailcc, emailsubject, status) VALUES(?, ?, ?, ?, ?);";
        $param_arr = array($recipientinfo[$i]['email'] , $emailfrom, $emailcc, $email['subject'], $code);
        $types = "ssssi";
        $rows = mysql_cmd_with_prepare($sql, $types, $param_arr);
    }
}

function is_input_data_valid($json) {
    return array_key_exists("to", $json) && array_key_exists("from", $json)  && array_key_exists("text", $json);
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
