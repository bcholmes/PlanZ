<?php

// Copyright (c) 2023 BC Holmes. All rights reserved. See copyright document for more details.
// This function calculates initial metrics for the auto-scheduler.

if (file_exists(__DIR__ . '/../../config/db_name.php')) {
    include __DIR__ . '/../../config/db_name.php';
}

require_once(__DIR__ . '/../db_support_functions.php');
require_once(__DIR__ . '/../authentication.php');
require_once(__DIR__ . '/../http_session_functions.php');

function number_of_respondants($db) {
    $query = <<<EOD
    SELECT count(DISTINCT badgeid) as count
        FROM ParticipantSessionInterest;
EOD;
    $stmt = mysqli_prepare($db, $query);
    if (mysqli_stmt_execute($stmt)) {
        $result = 0;
        $resultSet = mysqli_stmt_get_result($stmt);
        if (mysqli_num_rows($resultSet) == 1) {
            $dbobject = mysqli_fetch_object($resultSet);
            $result = $dbobject->count;
        }
        mysqli_stmt_close($stmt);
        return $result;
    } else {
        throw new DatabaseSqlException("Cannot execute query $query");
    }
}

function number_of_panels($db, $interests) {

    $clause = "";
    if ($interests) {
        $clause = <<<EOD
            AND s.sessionid in (SELECT
                distinct PSI.sessionid
            FROM
                    ParticipantSessionInterest PSI
                JOIN Participants P USING (badgeid)
            WHERE
                P.interested = 1
                AND ((PSI.rank != 0 and PSI.rank is not null and PSI.rank != 5) OR PSI.willmoderate = 'Y')
            )
EOD;
    }
    $query = <<<EOD
    SELECT count(distinct s.sessionid) as count
        FROM Sessions s
        JOIN SessionStatuses ss USING (statusid)
        JOIN PubStatuses ps USING (pubstatusid)
    WHERE ss.may_be_scheduled = 1
        AND ps.pubstatusname = 'Public'
        AND s.divisionid in (select divisionid from Divisions where divisionname = 'Panels')
        $clause
EOD;
    $stmt = mysqli_prepare($db, $query);
    if (mysqli_stmt_execute($stmt)) {
        $result = 0;
        $resultSet = mysqli_stmt_get_result($stmt);
        if (mysqli_num_rows($resultSet) == 1) {
            $dbobject = mysqli_fetch_object($resultSet);
            $result = $dbobject->count;
        }
        mysqli_stmt_close($stmt);
        return $result;
    } else {
        throw new DatabaseSqlException("Cannot execute query $query");
    }
}

function number_of_interested_panelists($db) {
    $query = <<<EOD
    SELECT count(DISTINCT badgeid) as count
        FROM ParticipantSessionInterest
        where (`rank` is not null and `rank` != 0) or willmoderate = 1;
EOD;
    $stmt = mysqli_prepare($db, $query);
    if (mysqli_stmt_execute($stmt)) {
        $result = 0;
        $resultSet = mysqli_stmt_get_result($stmt);
        if (mysqli_num_rows($resultSet) == 1) {
            $dbobject = mysqli_fetch_object($resultSet);
            $result = $dbobject->count;
        }
        mysqli_stmt_close($stmt);
        return $result;
    } else {
        throw new DatabaseSqlException("Cannot execute query $query");
    }
}

function number_of_available_slots($db, $online) {
    $onlineFlag = $online ? "Y" : "N";

    $query = <<<EOD
    SELECT count(*) as count
        FROM Rooms r, room_availability_slot s, room_availability_schedule a, room_to_availability r2a
        where r2a.roomid = r.roomid
          and r2a.availability_id = a.id
          and r.is_online = '$onlineFlag'
          and a.id = s.availability_schedule_id
          and s.divisionid in (select divisionid from Divisions where divisionname = 'Panels');
EOD;
    $stmt = mysqli_prepare($db, $query);
    if (mysqli_stmt_execute($stmt)) {
        $result = 0;
        $resultSet = mysqli_stmt_get_result($stmt);
        if (mysqli_num_rows($resultSet) == 1) {
            $dbobject = mysqli_fetch_object($resultSet);
            $result = $dbobject->count;
        }
        mysqli_stmt_close($stmt);
        return $result;
    } else {
        throw new DatabaseSqlException("Cannot execute query $query");
    }
}

start_session_if_necessary();
$db = connect_to_db(true);
$authentication = new Authentication();
try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && $authentication->isProgrammingStaff()) {

        $countRespondants = number_of_respondants($db);
        $countPanelists = number_of_interested_panelists($db);
        $countPanels = number_of_panels($db, false);
        $countPanelsWithPanelists = number_of_panels($db, true);
        $countInPersonSlots = number_of_available_slots($db, false);
        $countOnlineSlots = number_of_available_slots($db, true);

        header('Content-type: application/json; charset=utf-8');

        $json_string = json_encode(array("respondants" => $countRespondants,
            "panelists" => $countPanelists,
            "panels" => $countPanels,
            "panelsWithPanelists" => $countPanelsWithPanelists,
            "inPersonSlots" => $countInPersonSlots,
            "onlineSlots" => $countOnlineSlots));
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
