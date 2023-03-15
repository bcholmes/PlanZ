<?php

require_once(__DIR__ . "/../../name.php");

class PersonData {
    public $badgeId;
    public $name;
    public $schedulingPreferences;
    public $rankings;
    public $availability;
    public $assignments;

    function hasSessionRank($sessionId) {
        return array_key_exists($sessionId, $this->rankings);
    }
    function rankForSession($sessionId) {
        return $this->hasSessionRank($sessionId) ? $this->rankings[$sessionId] : null;
    }
    function assignmentLuck() {
        $luck = 0;
        $numberOfAssignments = 0;
        foreach ($this->assignments as $a) {
            $rank = $this->rankForSession($a->sessionId);
            $luck += ($rank) ? ($rank->rank * $rank->rank) : 0;
            $numberOfAssignments += ($rank) ? 1 : 0;
        }
        return $luck / sqrt($this->schedulingPreferences->overallMax - $numberOfAssignments);
    }

    function isOnlineOnly() {
        if (count($this->rankings) == 0) {
            return false;
        } else {
            $result = true;

            foreach ($this->rankings as $r) {
                $result = $result && ($r->howAttend == 2);
            }

            return $result;
        }
    }

    // TODO: consider already assigned items
    function isAvailable($timeSlot) {
        if (count($this->availability) == 0) {
            return true;
        } else if ($this->schedulingPreferences->maxSessionsForDay($timeSlot->day) <= $this->sessionCountForDay($timeSlot->day)) {
            return false;
        } else if ($this->overlapsExistingAssignments($timeSlot)) {
            return false;
        } else {
            $result = false;
            foreach ($this->availability as $a) {
                $result = $result || $a->contains($timeSlot->day * 96 + time_to_row_index($timeSlot->startTime), $timeSlot->day * 96 + time_to_row_index($timeSlot->endTime));
            }
            return $result;
        }
    }

    function overlapsExistingAssignments($timeSlot) {
        $result = false;
        foreach ($this->assignments as $a) {
            if ($a->timeSlot) {
                $result = $result || $a->timeSlot->overlaps($timeSlot);
            }
        }
        return $result;
    }

    function sessionCountForDay($day) {
        $count = 0;
        foreach ($this->assignments as $a) {
            if ($a->timeSlot != null) {
                $count += ($a->timeSlot->day == $day) ? 1 : 0;
            }
        }
        return $count;
    }

    function asJson() {
        return array("badgeId" => $this->badgeId,
            "name" => $this->name->asArray(),
            "isOnlineOnly" => $this->isOnlineOnly());
    }

    static function findAllInterestedParticipants($db) {
        $query = <<<EOD
         select P.badgeid, PA.maxprog as overall_max,
                P.pubsname, CD.badgename, CD.firstname, CD.lastname,
                PAD.day, PAD.maxprog as day_max
            FROM Participants P
            JOIN CongoDump CD using (badgeid)
            LEFT OUTER JOIN ParticipantAvailability PA using (badgeid)
            LEFT OUTER JOIN ParticipantAvailabilityDays PAD using (badgeid)
           WHERE P.interested = 1
             AND P.badgeid in (select badgeid from ParticipantSessionInterest where rank in (1, 2, 3) or willmoderate = 1)
           ORDER BY P.badgeid, PAD.day;
    EOD;
        $stmt = mysqli_prepare($db, $query);
        if (mysqli_stmt_execute($stmt)) {
            $participants = array();
            $current = null;
            $result = mysqli_stmt_get_result($stmt);
            while ($row = mysqli_fetch_object($result)) {
                $badgeId = $row->badgeid;
                if ($current == null || $current->badgeId != $badgeId) {
                    $current = new PersonData();
                    $current->badgeId = $badgeId;
                    $name = new PersonName();
                    $name->pubsName = $row->pubsname;
                    $name->badgeName = $row->badgename;
                    $name->firstName = $row->firstname;
                    $name->lastName = $row->lastname;
                    $current->name = $name;
                    $current->schedulingPreferences = new SchedulingPreferences();
                    $current->rankings = array();
                    $current->availability = array();
                    $current->assignments = array();
                    if ($row->overall_max) {
                        $current->schedulingPreferences->overallMax = $row->overall_max;
                    } else {
                        $current->schedulingPreferences->overallMax = PREF_TTL_SESNS_LMT;
                    }
                    $current->schedulingPreferences->dailyMaxes = array();
                    $participants[$badgeId] = $current;
                }
                if ($row->day != null && $row->day_max != null) {
                    $participants[$badgeId]->schedulingPreferences->dailyMaxes[$row->day] = $row->day_max;
                }
            }
            mysqli_stmt_close($stmt);

            return PersonData::findAvailability($db, PersonData::findAllRankings($db, $participants));
        } else {
            throw new DatabaseSqlException("Query could not be executed: $query");
        }
    }

    private static function findAllRankings($db, $participants) {
        $query = <<<EOD
          SELECT PSI.badgeid, PSI.sessionid, PSI.rank, PSI.willmoderate, PSI.attend_type
            FROM Sessions s
            JOIN ParticipantSessionInterest PSI USING (sessionid)
            JOIN SessionStatuses ss USING (statusid)
            JOIN PubStatuses ps USING (pubstatusid)
           WHERE ss.may_be_scheduled = 1
             AND (PSI.rank in (1, 2, 3) or PSI.willmoderate = 1)
             AND ps.pubstatusname = 'Public'
             AND s.divisionid in (select divisionid from Divisions where is_part_session_interest_allowed = 1)
EOD;

       $stmt = mysqli_prepare($db, $query);
       if (mysqli_stmt_execute($stmt)) {
           $result = mysqli_stmt_get_result($stmt);
           while ($row = mysqli_fetch_object($result)) {
               $badgeId = $row->badgeid;
               $ranking = new Ranking();
               $ranking->sessionId = $row->sessionid;
               $ranking->rank = $row->rank;
               if ($ranking->rank == null) {
                   $ranking->rank = 3; // some people specify that they will moderate, but don't specify a rank
               }
               $ranking->willModerate = ($row->willmoderate == 1) ? true : false;
               $ranking->howAttend = $row->attend_type;

               $participant = array_key_exists($badgeId, $participants) ? $participants[$badgeId] : null;
               if ($participant) {
                   $participant->rankings[$ranking->sessionId] = $ranking;
               } else {
                   error_log("Badge id $badgeId not found.");
               }
           }

           mysqli_stmt_close($stmt);
           return $participants;
        } else {
            throw new DatabaseSqlException("Query could not be executed: $query");
        }
    }

    private static function findAvailability($db, $participants) {
        $query = <<<EOD
          SELECT PAT.badgeid, PAT.starttime, PAT.endtime
            FROM ParticipantAvailabilityTimes PAT
            JOIN Participants P USING (badgeid)
           WHERE PAT.badgeid in (select badgeid from ParticipantSessionInterest where rank in (1, 2, 3) or willmoderate = 1)
             AND P.interested = 1
           ORDER BY PAT.badgeid, PAT.availabilitynum
       EOD;

       $stmt = mysqli_prepare($db, $query);
       if (mysqli_stmt_execute($stmt)) {
           $result = mysqli_stmt_get_result($stmt);
           while ($row = mysqli_fetch_object($result)) {
               $badgeId = $row->badgeid;
               $availability = new Availability();
               $availability->start = $row->starttime;
               $availability->end = $row->endtime;

               $participant = $participants[$badgeId];
               if ($participant) {
                   $participant->availability[] = $availability;
               }
           }

           mysqli_stmt_close($stmt);
           return $participants;
        } else {
            throw new DatabaseSqlException("Query could not be executed: $query");
        }
    }
}

class SchedulingPreferences {
    public $overallMax;
    public $dailyMaxes;

    function maxSessionsForDay($day) {
        if (count($this->dailyMaxes) === 0) {
            return PREF_DLY_SESNS_LMT;
        } else {
            $result = PREF_DLY_SESNS_LMT;
            $hasRealValue = false;
            foreach ($this->dailyMaxes as $max) {
                if ($max != null && $max != 0) {
                    $hasRealValue;
                }
            }
            if ($hasRealValue) {
                $temp = array_key_exists($day, $this->dailyMaxes) ? $this->dailyMaxes[$day] : null;
                return ($temp == null) ? PREF_DLY_SESNS_LMT : $temp;
            } else {
                return $result;
            }
        }
    }

}

class Ranking {
    public $sessionId;
    public $rank;
    public $willModerate;
    public $howAttend;
}

class Availability {
    public $start;
    public $end;

    function contains($startIndex, $endIndex) {
        $thisStartIndex = time_to_row_index($this->start);
        $thisEndIndex = time_to_row_index($this->end);

        return ($thisStartIndex <= $startIndex && $thisEndIndex >= $startIndex
            && $thisStartIndex <= $endIndex && $thisEndIndex >= $endIndex);
    }
}

?>