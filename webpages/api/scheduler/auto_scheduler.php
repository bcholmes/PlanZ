<?php

if (file_exists(__DIR__ . '/../../config/db_name.php')) {
    include __DIR__ . '/../../config/db_name.php';
}

require_once(__DIR__ . "/../db_support_functions.php");
require_once(__DIR__ . "/../../time_slot_functions.php");
require_once(__DIR__ . "/../../room_model.php");
require_once(__DIR__ . "/scheduler_participant_model.php");
require_once(__DIR__ . "/scheduler_session_model.php");
require_once(__DIR__ . "/scheduler_time_slot_model.php");

define("ATTEND_IN_PERSON", 1);
define("ATTEND_ONLINE", 2);
define("ATTEND_EITHER", 3);

// Find the X most popular panels...
define("NUMBER_OF_SESSIONS", 150);

// How many panelists should we assign?
define("MIN_NUMBER_OF_PANELISTS", 3);
define("IDEAL_NUMBER_OF_PANELISTS", 4);
define("MAX_NUMBER_OF_PANELISTS", 5);

class Participation {
    public $participant;
    public $moderator;
}

function persist_session_data($db, $session, $userBadgeId, $userName) {
    mysqli_begin_transaction($db);
    try {

        $query = <<<EOD
        INSERT INTO Schedule
                (sessionid, roomid, starttime)
        VALUES (?, ?, ?);
        EOD;

        $stmt = mysqli_prepare($db, $query);
        $time = $session->timeSlot->absoluteStartTime();
        mysqli_stmt_bind_param($stmt, "iis", $session->sessionId, $session->timeSlot->room->roomId, $time);

        if ($stmt->execute()) {
            mysqli_stmt_close($stmt);
        } else {
            throw new DatabaseSqlException($query);
        }

        $query = <<<EOD
        UPDATE Sessions
           SET statusid = 3
         WHERE sessionid = ?
        EOD;

        $stmt = mysqli_prepare($db, $query);
        mysqli_stmt_bind_param($stmt, "i", $session->sessionId);

        if ($stmt->execute()) {
            mysqli_stmt_close($stmt);
        } else {
            throw new DatabaseSqlException($query);
        }

        $query = <<<EOD
        INSERT INTO SessionEditHistory
        (sessionid, badgeid, name, sessioneditcode, statusid)
        VALUES
        (?, ?, ?, 3, 3)
        EOD;

        $stmt = mysqli_prepare($db, $query);
        mysqli_stmt_bind_param($stmt, "iss", $session->sessionId, $userBadgeId, $userName);

        if ($stmt->execute()) {
            mysqli_stmt_close($stmt);
        } else {
            throw new DatabaseSqlException($query);
        }

        foreach ($session->assignedParticipants as $p) {
            $query = <<<EOD
            INSERT INTO ParticipantOnSessionHistory
                    (badgeid, sessionid, moderator, createdbybadgeid, createdts)
            VALUES (?, ?, 0, ?, NOW());
            EOD;

            $stmt = mysqli_prepare($db, $query);
            error_log("Badge id of participant is " . $p->participant->badgeId . "!");
            error_log("Badge id of user is >" . $userBadgeId . "<!");
            mysqli_stmt_bind_param($stmt, "sis", $p->participant->badgeId, $session->sessionId, $userBadgeId);

            if ($stmt->execute()) {
                mysqli_stmt_close($stmt);
            } else {
                throw new DatabaseSqlException($query . " : " . mysqli_error($db));
            }
        }

        mysqli_commit($db);
    } catch (Exception $e) {
        mysqli_rollback($db);
        throw $e;
    }
}

function collate_persons_into_sessions($sessions, $participants) {
    foreach ($sessions as $session) {
        foreach ($participants as $participant) {
            if ($participant->hasSessionRank($session->sessionId)) {
                $session->potentialParticipants[] = $participant;
            }
        }
    }
}

function sort_by_number_of_potential_participants($s1, $s2) {
    $diff = count($s1->potentialParticipants) - count($s2->potentialParticipants);
    if ($diff === 0) {
        return strcmp($s1->sessionId, $s2->sessionId);
    } else {
        return $diff;
    }
}

function review_potential_participants($session, $participants) {
    $result = array();
    foreach ($participants as $p) {
        if (count($p->assignments) < $p->schedulingPreferences->overallMax) {
            $result[] = $p;
        }
    }
    return $result;
}

function assign_all_participants($session, $participants) {
    foreach ($participants as $p) {
        $participation = new Participation();
        $participation->participant = $p;
        $session->assignedParticipants[$p->badgeId] = $participation;
        $p->assignments[] = $session;
    }
}

function sort_by_highest_ranking($i1, $i2) {
    $sessionId = $i1["session"]->sessionId;
    $rank1 = $i1["participant"]->rankForSession($sessionId);
    $rank2 = $i2["participant"]->rankForSession($sessionId);

    // top rank is weighted pretty high
    if ($rank1->rank != $rank2->rank && ($rank1->rank == 1 || $rank2->rank == 1)) {
        return $rank1->rank - $rank2->rank;
    } else {
        $luck1 = -($i1["participant"]->assignmentLuck() / sqrt($rank1->rank));
        $luck2 = -($i2["participant"]->assignmentLuck() / sqrt($rank2->rank));

        if ($luck1 != $luck2) {
            return $luck1 - $luck2;
        } else {
            return strcmp($i1["participant"]->badgeId, $i2["participant"]->badgeId);
        }
    }
}

function choose_best_matches_and_assign($session, $participants) {
    $temp = array();
    foreach ($participants as $p) {
        $temp[] = array("session" => $session, "participant" => $p);
    }

    usort($temp, "sort_by_highest_ranking");

    for ($i = 0; $i < IDEAL_NUMBER_OF_PANELISTS && $i < count($temp); $i++) {
        $winnowedList[] = $temp[$i]["participant"];
    }

    assign_all_participants($session, $winnowedList);
}

function process_participants($session, $type) {
    $session->type = $type;
    $potential = review_potential_participants($session, $type == ATTEND_IN_PERSON ? $session->potentialInPersonParticipants() : $session->potentialOnlineParticipants());
    if (count($potential) < MIN_NUMBER_OF_PANELISTS) {
        $typeText = attend_type_as_text($type);
        $session->message = "Not enough filtered $typeText participants";
    } else if (count($potential) <= IDEAL_NUMBER_OF_PANELISTS) {
        assign_all_participants($session, $potential);
    } else {
        choose_best_matches_and_assign($session, $potential);
    }
}

function create_first_pass_assignments_for_sessions($sessions, $participants) {

    $simple_sessions = array();
    foreach ($sessions as $session) {
        $simple_sessions[] = $session;
    }

    // sort sessions
    usort($simple_sessions, "sort_by_number_of_potential_participants");

    foreach ($simple_sessions as $session) {
        if (count($session->potentialParticipants) < MIN_NUMBER_OF_PANELISTS) {
            $session->message = "Not enough potential panelists to make a panel";
        } else if ($session->potentialOnlineParticipantCount() < MIN_NUMBER_OF_PANELISTS && $session->potentialInPersonParticipantCount() < MIN_NUMBER_OF_PANELISTS) {
            $session->message = "Not enough potential panelists of the same attendance type";
        } else if ($session->potentialOnlineParticipantCount() < MIN_NUMBER_OF_PANELISTS) {
            process_participants($session, ATTEND_IN_PERSON);
        } else if ($session->potentialInPersonParticipantCount() < MIN_NUMBER_OF_PANELISTS) {
            process_participants($session, ATTEND_ONLINE);
        } else {
            // determine if only online or only in-person is viable
            $potentialInPerson = review_potential_participants($session, $session->potentialInPersonParticipants());
            $potentialOnline = review_potential_participants($session, $session->potentialOnlineParticipants());
            if (count($potentialInPerson) < MIN_NUMBER_OF_PANELISTS && count($potentialOnline) < MIN_NUMBER_OF_PANELISTS) {
                $session->message = "Not enough filtered potential panelists of the same attendance type";
            } else if (count($potentialOnline) < MIN_NUMBER_OF_PANELISTS) {
                process_participants($session, ATTEND_IN_PERSON);
            } else if (count($potentialInPerson) < MIN_NUMBER_OF_PANELISTS) {
                process_participants($session, ATTEND_ONLINE);
            } else {
                // determine if online or in-person is better
                if ((count($potentialOnline) * 1.3) > count($potentialInPerson)) {
                    process_participants($session, ATTEND_ONLINE);
                } else {
                    process_participants($session, ATTEND_IN_PERSON);
                }
            }
        }
    }

    return $simple_sessions;
}

function attend_type_as_text($type) {
    if ($type == ATTEND_ONLINE) {
        return "Online";
    } else if ($type == ATTEND_IN_PERSON) {
        return "In-Person";
    } else {
        return null;
    }

}

function assign_online_timeslots($sessions, $timeslots) {
    $filteredSessions = array();
    foreach ($sessions as $s) {
        if (count($s->assignedParticipants) > 0 && $s->type == ATTEND_ONLINE) {
            $filteredSessions[] = $s;
        }
    }

    $filteredSlots = array();
    foreach ($timeslots as $s) {
        if ($s->room->isOnline) {
            $filteredSlots[] = $s;
        }
    }

    foreach ($filteredSessions as $s) {
        foreach ($filteredSlots as $ts) {
            if ($ts->session == null && $s->allParticipantsAvailable($ts)) {
                $ts->session = $s;
                $s->timeSlot = $ts;
                break;
            }
        }
    }
}

function assign_in_person_timeslots($sessions, $timeslots) {
    $filteredSessions = array();
    foreach ($sessions as $s) {
        if (count($s->assignedParticipants) > 0 && $s->type == ATTEND_IN_PERSON) {
            $filteredSessions[] = $s;
        }
    }

    $filteredSlots = array();
    foreach ($timeslots as $s) {
        if (!$s->room->isOnline) {
            $filteredSlots[] = $s;
        }
    }

    usort($filteredSlots, array("TimeSlot", "compareByRoomSizeAndPreferredTime"));
    usort($filteredSessions, array("Session", "compareByRank"));

    foreach ($filteredSessions as $s) {
        foreach ($filteredSlots as $ts) {
            if ($ts->session == null && $s->allParticipantsAvailable($ts)) {
                $ts->session = $s;
                $s->timeSlot = $ts;
                break;
            }
        }
    }
}


function assign_timeslots($db, $sessions) {
    $timeSlots = TimeSlot::findAllTimeslots($db);
    usort($timeSlots, array("TimeSlot", "compareByPreferredTime"));

    assign_online_timeslots($sessions, $timeSlots);
    assign_in_person_timeslots($sessions, $timeSlots);

    return $timeSlots;
}

start_session_if_necessary();
$db = connect_to_db(true);
$authentication = new Authentication();
try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $authentication->isProgrammingStaff()) {

        $participants = PersonData::findAllInterestedParticipants($db);
        $sessions = Session::findAllSessions($db, NUMBER_OF_SESSIONS);

        collate_persons_into_sessions($sessions, $participants);

        $result = create_first_pass_assignments_for_sessions($sessions, $participants);

        $timeSlots = assign_timeslots($db, $result);

        // create a JSON output
        $records = array();
        foreach ($result as $s) {
            if ($s->timeSlot != null) {
                $record = array("sessionId" => $s->sessionId, "count" => count($s->potentialParticipants), "message" => $s->message, "type" => attend_type_as_text($s->type));
                $assignments = array();
                foreach ($s->assignedParticipants as $p) {
                    $assignments[] = array("badgeid" => $p->participant->badgeId, "name" => $p->participant->name->getBadgeName());
                }
                $record["assignments"] = $assignments;

                $timeSlot = array("Room" => $s->timeSlot->roomName, "day" => $s->timeSlot->day, "startTime" => $s->timeSlot->startTime, "endTime" => $s->timeSlot->endTime);
                $record["timeSlot"] = $timeSlot;
                $records[] = $record;

                persist_session_data($db, $s, $_SESSION['badgeid'], $_SESSION['badgename']);
            } else if ($s->timeSlot == null && count($s->assignedParticipants) > 0) {
                error_log("There were " . count($s->assignedParticipants) . " assigned to session " . $s->sessionId);
            }
        }

        $temp = array();
        foreach ($participants as $p) {
            $record = array("participantId" => $p->badgeId, "count" => count($p->rankings), "name" => $p->name->getBadgeName(), "maxPanels" => $p->schedulingPreferences->overallMax);
            $rankings = array();
            foreach ($p->rankings as $r) {
                $rankings[] = $r->sessionId;
            }
            $record["rankings"] = $rankings;
            $temp[] = $record;
        }

        $slots = array();
        foreach ($timeSlots as $ts) {
            $record = array("room" => $ts->room->roomName, "day" => $ts->day, "start" => $ts->startTime, "end" => $ts->endTime);
            $slots[] = $record;
        }


        header('Content-type: application/json; charset=utf-8');
        $json_string = json_encode(array("sessions" => $records));
        echo $json_string;

    } else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        http_response_code(401);
    } else {
        http_response_code(405);
    }
} finally {
    $db->close();
}
?>
