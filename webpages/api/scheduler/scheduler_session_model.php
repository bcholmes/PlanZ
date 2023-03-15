<?php

class Session {
    public $sessionId;
    public $rank;
    public $title;
    public $timeSlot;
    public $potentialParticipants;
    public $assignedParticipants;
    public $message;
    public $type;
    public $attendCountInPerson;
    public $attendCountEither;
    public $attendCountOnline;

    function potentialOnlineParticipants() {
        $result = array();
        foreach ($this->potentialParticipants as $p) {
            $rank = $p->rankForSession($this->sessionId);
            if ($rank && ($rank->howAttend == ATTEND_ONLINE || $rank->howAttend == ATTEND_EITHER)) {
                $result[] = $p;
            }
        }
        return $result;
    }

    function potentialOnlineParticipantCount() {
        return count($this->potentialOnlineParticipants());
    }

    function potentialInPersonParticipants() {
        $result = array();
        foreach ($this->potentialParticipants as $p) {
            $rank = $p->rankForSession($this->sessionId);
            if ($rank && ($rank->howAttend == ATTEND_IN_PERSON || $rank->howAttend == ATTEND_EITHER)) {
                $result[] = $p;
            }
        }
        return $result;
    }

    function potentialInPersonParticipantCount() {
        return count($this->potentialInPersonParticipants());
    }

    function allParticipantsAvailable($timeSlot) {
        $result = true;
        foreach ($this->assignedParticipants as $p) {
            $result &= $p->participant->isAvailable($timeSlot);
        }
        return $result;
    }

    function compareByRank($s1, $s2) {
        if ($s1->rank === $s2->rank) {
            // pick something to make the sort deterministic
            return strcmp($s1->sessionId, $s2->sessionId);
        } else {
            return $s2->rank - $s1->rank;
        }
    }

    function asJson() {
        return array("sessionId" => $this->sessionId, "title" => $this->title, "rank" => $this->rank, "attending" =>
            array("inPerson" => $this->attendCountInPerson, "either" => $this->attendCountEither, "online" => $this->attendCountOnline));
    }


    static function findAllSessions($db, $numberOfSessions = -1) {
        $limitClause = ($numberOfSessions > 0) ? "LIMIT $numberOfSessions" : "";
        $query = <<<EOD
            select sessionid, title,
                sum(attend1 * 1.2 + attend2 + attend3 * 0.5) as rank,
                sum(attend_type1) as attend_type1,
                sum(attend_type2) as attend_type2,
                sum(attend_type3) as attend_type3
            from
            (SELECT
                    S.sessionid, S.title, T.trackname, T.display_order,
                    case PSI.attend when 1 then 1 else 0 end as attend1,
                    case PSI.attend when 2 then 1 else 0 end as attend2,
                    case PSI.attend when 3 then 1 else 0 end as attend3,
                    case PSI.attend_type when 1 then 1 else 0 end as attend_type1,
                    case PSI.attend_type when 2 then 1 else 0 end as attend_type2,
                    case PSI.attend_type when 3 then 1 else 0 end as attend_type3,
                    P.badgeid
                FROM
                    Sessions S
                    JOIN Tracks T USING (trackid)
                    JOIN Types Ty USING (typeid)
                LEFT JOIN ParticipantSessionInterest PSI USING (sessionid)
                LEFT JOIN Participants P ON PSI.badgeid = P.badgeid AND P.interested = 1
                WHERE
                    S.statusid IN (2,3,7)
                    AND S.invitedguest = 0
                    AND S.divisionid in (select divisionid from Divisions where is_part_session_interest_allowed = 1)) FB
            GROUP BY
                sessionid, title
            ORDER BY rank desc, sessionid
            $limitClause
EOD;

        $sessions = array();
        $stmt = mysqli_prepare($db, $query);
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            while ($row = mysqli_fetch_object($result)) {
                $sessionId = $row->sessionid;
                $session = new Session();
                $session->sessionId = $sessionId;
                $session->title = $row->title;
                $session->rank = $row->rank;
                $session->potentialParticipants = array();
                $session->assignedParticipants = array();

                $session->attendCountInPerson = $row->attend_type1;
                $session->attendCountOnline = $row->attend_type2;
                $session->attendCountEither = $row->attend_type3;
                $sessions[] = $session;
            }

            mysqli_stmt_close($stmt);
            return $sessions;
         } else {
             throw new DatabaseSqlException("Query could not be executed: $query");
         }
    }
}

?>