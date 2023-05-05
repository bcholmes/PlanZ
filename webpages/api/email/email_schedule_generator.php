<?php

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


?>