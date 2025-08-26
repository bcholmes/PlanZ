<?php

require_once(__DIR__ . '/../con_info.php');

class SchedulerTimeRange {
    public $startTime;
    public $endTime;
    public $timeZone;

    function __construct($startTime, $endTime, $timeZone) {
        $this->startTime = $startTime;
        $this->endTime = $endTime;
        $this->timeZone = $timeZone;
    }

    public function asJson() {
        return array("startTime" => $this->startTime, "endTime" => $this->endTime, "timeZone" => $this->timeZone);
    }
}

class SchedulerDay {

    public $date;
    public $timeRange;
    public $blocks;

    static function findAllForCurrentCon($db) {
        $con = ConInfo::findCurrentCon($db);
        $days = $con->allConDays();

        $blockLength = convert_time_period_to_minutes(STANDARD_BLOCK_LENGTH);
        $programItemLength = convert_time_period_to_minutes(DEFAULT_DURATION);

        $result = array();
        foreach ($days as $index=>$day) {
            $schedulerDay = new SchedulerDay();
            $schedulerDay->date = $day;
            if ($index == 0) {
                $schedulerDay->timeRange = new SchedulerTimeRange(FIRST_DAY_START_TIME, OTHER_DAY_STOP_TIME, PHP_DEFAULT_TIMEZONE);
            } else if ($index == count($days) - 1) {
                $schedulerDay->timeRange = new SchedulerTimeRange(OTHER_DAY_START_TIME, LAST_DAY_STOP_TIME, PHP_DEFAULT_TIMEZONE);
            } else {
                $schedulerDay->timeRange = new SchedulerTimeRange(OTHER_DAY_START_TIME, OTHER_DAY_STOP_TIME, PHP_DEFAULT_TIMEZONE);
            }
            $blocks = array();

            $startTimeInitialString = $schedulerDay->date->format('Y-m-d') . ' ' . $schedulerDay->timeRange->startTime;
            $startTime = DateTime::createFromFormat('Y-m-d G:i', $startTimeInitialString);

            $endOfDayInitialString = $schedulerDay->date->format('Y-m-d') . ' 00:00';
            $endOfDay = DateTime::createFromFormat('Y-m-d H:i', $endOfDayInitialString);
            $endOfDay->modify('+' . convert_time_period_to_minutes($schedulerDay->timeRange->endTime) . ' minutes');

            $time = clone $startTime;
            while ($time < $endOfDay) {
                $startTimeAsString = $time->format('H:i');
                error_log('startTimeAsString : ' . $startTimeAsString);
                $time->modify('+' . $programItemLength . ' minutes');
                $endTimeAsString = $time->format('H:i');

                $blocks[] = new SchedulerTimeRange($startTimeAsString, $endTimeAsString, PHP_DEFAULT_TIMEZONE);

                $startTime->modify('+' . $blockLength . ' minutes');
                $time = clone $startTime;
            }

            $schedulerDay->blocks = $blocks;
            $result[] = $schedulerDay;
        }
        return $result;
    }

    public function asJson() {
        $result = array(
            "date" => $this->date->format('Y-m-d'),
            "timeRange" => $this->timeRange->asJson()
        );
        $blocksArray = array();
        foreach ($this->blocks as $block) {
            $blocksArray[] = $block->asJson();
        }
        $result["blocks"] = $blocksArray;
        return $result;
    }

    public static function asJsonList($days) {
        $result = array();
        foreach ($days as $day) {
            $result[] = $day->asJson();
        }
        return array("days" => $result);
    }
}

?>