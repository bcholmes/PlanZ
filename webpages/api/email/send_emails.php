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
require_once(__DIR__ . '/email_history_model.php');
require_once(__DIR__ . '/../../email_functions.php');
require_once(__DIR__ . '/../../external/swiftmailer-5.4.8/lib/swift_required.php');
require_once(__DIR__ . '/../../name.php');
require_once(__DIR__ . '/../../email_functions.php');

define("SHOW_EVENT_SCHEDULE", "1");
define("SHOW_FULL_SCHEDULE", "2");

function generate_email_schedules($db, $status, $simpleTo) {
    $ConStartDatim = CON_START_DATIM;
    if ($status === SHOW_EVENT_SCHEDULE) {
        $extraWhereClause = "        AND S.divisionid=3"; // events
    } else {
        $extraWhereClause = "";
    }
    $badgeidArr = "";
    foreach($simpleTo as $to) {
        if ($badgeidArr != "") {
            $badgeidArr .= ", ";
        }
        $badgeidArr .= $to->badgeId;
    }
    $badgeidList = $badgeidArr;
    $query = <<<EOD
SELECT
        POS.badgeid, RM.roomname, S.title, DATE_FORMAT(ADDTIME('$ConStartDatim$', SCH.starttime),'%a %l:%i %p') as starttime,
        DATE_FORMAT(S.duration, '%i') as durationmin, DATE_FORMAT(S.duration, '%k') as durationhrs, SCH.sessionid
    FROM
             Schedule SCH
        JOIN Rooms RM USING (roomid)
        JOIN Sessions S USING (sessionid)
        JOIN ParticipantOnSession POS USING (sessionid)
    WHERE
            POS.badgeid IN ($badgeidList)
$extraWhereClause
    ORDER BY
        POS.badgeid,
        SCH.starttime;
EOD;

    $stmt = mysqli_prepare($db, $query);
    $returnResult = array();
    if (mysqli_stmt_execute($stmt)) {
        $resultSet = mysqli_stmt_get_result($stmt);
        while ($rowArr = mysqli_fetch_assoc($resultSet)) {
            $scheduleRow = str_pad($rowArr["starttime"], 15); // Fri 12:00 AM (plus 3 spaces)
            $scheduleRow .= str_pad(renderDuration($rowArr["durationmin"], $rowArr["durationhrs"]), 14); // 10 Hr 59 Min (plus 2 spaces)
            $scheduleRow .= str_pad(substr($rowArr["roomname"], 0, 25), 27); // Commonwealth Ballroom ABC (plus 2 spaces)
            $scheduleRow .= str_pad($rowArr["sessionid"], 12); // Session ID (plus 2 spaces)
            $scheduleRow .= str_pad($rowArr["title"], 50); // Video 201: Advanced Live Television Production
            if (!isset($returnResult[$rowArr["badgeid"]])) {
                $returnResult[$rowArr["badgeid"]] = array();
            }
            $returnResult[$rowArr["badgeid"]][] = $scheduleRow;
        }
    }
    return $returnResult;
}

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

        $alteredText = str_replace(
            array("\$BADGEID\$",
                "\$FIRSTNAME\$",
                "\$LASTNAME\$",
                "\$EMAILADDR\$",
                "\$PUBNAME\$",
                "\$BADGENAME\$"),
            array($to->badgeId,
                $to->name->firstName,
                $to->name->lastName,
                $to->address,
                $to->name->getPubsName(),
                $to->name->getBadgeName()),
            $text);

        if ($scheduleSubstitution == SHOW_FULL_SCHEDULE || $scheduleSubstitution == SHOW_EVENT_SCHEDULE) {
            if ($scheduleSubstitution == SHOW_EVENT_SCHEDULE) {
                $scheduleTag = '$EVENTS_SCHEDULE$';
            } else {
                $scheduleTag = '$FULL_SCHEDULE$';
            }
            if (isset($schedules[$to->badgeId])) {
                $scheduleInfo = " Start Time      Duration            Room Name          Session ID                      Title\n";
                $scheduleInfo .= implode("\n", $schedules[$to->badgeId]);
            } else {
                $scheduleInfo = "No scheduled items for you were found.";
            }
            $alteredText = str_replace($scheduleTag, $scheduleInfo, $alteredText);
        }

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
