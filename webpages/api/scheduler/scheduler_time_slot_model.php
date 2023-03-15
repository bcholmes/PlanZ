<?php

class TimeSlot {
    public $day;
    public $startTime;
    public $endTime;
    public $roomId;
    public $room;
    public $session;

    function startTimeIndex() {
        return $this->day * 96 + time_to_row_index($this->startTime);
    }

    function endTimeIndex() {
        return $this->day * 96 + time_to_row_index($this->endTime);
    }

    function overlaps($timeSlot) {
        if ($this->startTimeIndex() <= $timeSlot->startTimeIndex() &&
            $timeSlot->startTimeIndex() <= $this->endTimeIndex()) {
            // we contain the other start time
            return true;
        } else if ($this->startTimeIndex() <= $timeSlot->endTimeIndex() &&
            $timeSlot->endTimeIndex() <= $this->endTimeIndex()) {
            // we contain the other end time
            return true;
        } else if ($timeSlot->startTimeIndex() <= $this->startTimeIndex() &&
            $this->endTimeIndex() <= $timeSlot->endTimeIndex()) {
            // the other timeslot encompasses us
            return true;
        } else {
            return false;
        }
    }

    function absoluteStartTime() {
        $index = $this->startTimeIndex();
        $hour = floor($index / 4);
        $minute = $index % 4 * 15;
        return ($hour < 10 ? str_pad($hour, 2, "0", STR_PAD_LEFT) : "$hour") . ":" . str_pad($minute, 2, "0", STR_PAD_LEFT) . ":00";
    }

    // TODO: figure out how to generalize this
    function roomSizeCategory() {
        if ($this->room->roomName === 'Wisconsin') {
            return 1; // Big
        } else if ($this->room->roomName === 'Assembly' ||
                $this->room->roomName === 'Capital A' ||
                $this->room->roomName === 'Capital B') {
            return 2; // Medium
        } else {
            return 3; // Small
        }
    }

    static function compareByRoomSizeAndPreferredTime($ts1, $ts2) {
        return TimeSlot::compare($ts1, $ts2, true);
    }

    static function compareByPreferredTime($ts1, $ts2) {
        return TimeSlot::compare($ts1, $ts2, false);
    }
    static function compare($ts1, $ts2, $useRoomSize) {
        $time1 = time_to_row_index($ts1->startTime);
        $time2 = time_to_row_index($ts2->startTime);

        if ($time1 < 40 && $time2 >= 40) {
            return 1;
        } else if ($time2 < 40 && $time1 >= 40) {
            return -1;
        } else if ($time1 < 40 && $time2 < 40) {
            if ($ts1->day == $ts2->day) {
                return $ts1->roomId - $ts2->roomId;
            } else {
                return $ts1->day - $ts2->day;
            }
        } else if ($time1 > 88 && $time2 > 88) {
            if ($time1 != $time2) {
                return $time1 - $time2;
            } else if ($ts1->day != $ts2->day) {
                return $ts1->day - $ts2->day;
            } else {
                return $ts1->roomId - $ts2->roomId;
            }
        } else if ($time1 > 88) {
            return 1;
        } else if ($time2 > 88) {
            return -1;
        } else if ($useRoomSize && $ts1->roomSizeCategory() != $ts2->roomSizeCategory()) {
            return $ts1->roomSizeCategory() - $ts2->roomSizeCategory();
        } else if ($time1 > 60 && $time2 > 60) {
            if ($time1 != $time2) {
                return $time1 - $time2;
            } else if ($ts1->day != $ts2->day) {
                return $ts1->day - $ts2->day;
            } else {
                return $ts1->roomId - $ts2->roomId;
            }
        } else if ($time1 > 60) {
            return 1;
        } else if ($time2 > 60) {
            return -1;
        } else if ($time1 != $time2) {
            return $time1 - $time2;
        } else if ($ts1->day != $ts2->day) {
            return $ts1->day - $ts2->day;
        } else {
            return $ts1->roomId - $ts2->roomId;
        }
    }

    static function findAllTimeslots($db) {
        $query = <<<EOD
        SELECT r.roomid, r2a.day, s.start_time, s.end_time, r.roomname, r.is_online
          FROM Rooms r,
               room_to_availability r2a,
               room_availability_schedule a,
               room_availability_slot s,
               Divisions d
        WHERE r.is_scheduled = 1
          AND r.roomid = r2a.roomid
          AND r2a.availability_id = a.id
          AND s.availability_schedule_id = a.id
          AND d.divisionid = s.divisionid
          AND d.divisionname = 'Panels';
EOD;

        $rooms = array();
        $slots = array();
        $stmt = mysqli_prepare($db, $query);
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            while ($row = mysqli_fetch_object($result)) {
                $slot = new TimeSlot();
                $slot->roomId = $row->roomid;

                if (array_key_exists($slot->roomId, $rooms)) {
                    $slot->room = $rooms[$slot->roomId];
                } else {
                    $room = new Room();
                    $room->roomId = $row->roomid;
                    $room->roomName = $row->roomname;
                    $room->isOnline = $row->is_online == 'Y' ? true : false;
                    $slot->room = $room;
                    $rooms[$slot->roomId] = $room;
                }

                $slot->day = $row->day;
                $slot->startTime = $row->start_time;
                $slot->endTime = $row->end_time;
                $slots[] = $slot;
            }

            mysqli_stmt_close($stmt);
            return $slots;
         } else {
             throw new DatabaseSqlException("Query could not be executed: $query");
         }
    }

}

