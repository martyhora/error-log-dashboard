<?php

namespace DbModel;

class ErrorLogModel extends \DbLib\DbTableModel
{
    protected $tableName = 'error_log';

    private function getMutedErrors(array $mutedErrors)
    {
        $errs = [];

        foreach ($mutedErrors as $item)
        {
            $errs[] = "erl.error NOT LIKE {$this->database->quote("%{$item}%")}";
        }

        return implode(' AND ', $errs);
    }

    public function getErrorsByDate($logType, $weeksBack, $hideResolved, $mutedErrors, $groupRows = true)
    {
        $cond = $hideResolved ? " AND (resolved_date IS NULL OR resolved_date < datetime) AND (" . $this->getMutedErrors($mutedErrors) . ")" : "";

        $sql = "SELECT COUNT(*) cnt, COUNT(DISTINCT error) uniqCnt, DATE_FORMAT(DATE(datetime), '%d.%m.') date, log_type logType, DATE(datetime) datetime
                FROM {$this->tableName} erl
                LEFT JOIN error_log_status erls ON erls.error_log_hash = erl.error_hash
                WHERE log_type = {$this->database->quote($logType)} AND datetime >= DATE_SUB(NOW(), INTERVAL {$this->database->quote($weeksBack)} WEEK) {$cond}
                " . ($groupRows ? " GROUP BY DATE(datetime) " : " GROUP BY erl.id ");

        return $this->database->query($sql)->fetchAll();
    }

    public function getErrorsByDay($logType, $day = null, $lastDays = null, $hideResolved, $mutedErrors, $errorType = null, $groupRows = true)
    {
        $cond = " AND " . ($day ? " DATE_FORMAT(DATE(datetime), '%d.%m.') = '{$day}'" : " datetime >= DATE_SUB(NOW(), INTERVAL {$this->database->quote($lastDays)} DAY)");

        $cond .= $hideResolved ? " AND (resolved_date IS NULL OR resolved_date < datetime) AND (" . $this->getMutedErrors($mutedErrors) . ")" : "";

        $cond .= $errorType && $errorType !== 'false' ? " AND error_type = {$this->database->quote($errorType)}" : "";

        $order = "cnt DESC, erl.datetime DESC";

        if ($day === date('d.m.'))
        {
            $order = "erl.datetime DESC, cnt DESC";
        }

        $sql = "SELECT COUNT(*) cnt, SUBSTRING(error, LOCATE(':', error) + 2) error, error_type errorType,  DATE_FORMAT(datetime, '%d.%m.') date, DATE_FORMAT(MAX(datetime), '%H:%i:%s') time, url,
                       error_hash errorHash, DATE(erls.resolved_date) resolvedDate, log_type logType, DATE(datetime) datetime,
                       DATE_FORMAT(first_occurence_datetime, '%d.%m.%Y') firstOccurenceDatetime, DATE_FORMAT(last_occurence_datetime, '%d.%m.%Y') lastOccurenceDatetime,
					   erls.id errId, error_file errorFile
                FROM {$this->tableName} erl
                LEFT JOIN error_log_status erls ON erls.error_log_hash = erl.error_hash
                WHERE log_type = {$this->database->quote($logType)} {$cond}
                " . ($groupRows ? " GROUP BY error_hash " : " GROUP BY erl.id ") . "
                ORDER BY {$this->database->quote($order)}, erl.id";

        return $this->database->query($sql)->fetchAll();
    }

    public function getErrorsByType($logType, $weeksBack, $hideResolved, $mutedErrors, $groupRows = true)
    {
        $cond = $hideResolved ? " AND (resolved_date IS NULL OR resolved_date < datetime) AND (" . $this->getMutedErrors($mutedErrors) . ")" : "";

        $sql = "SELECT COUNT(*) cnt, error_type errorType
                FROM {$this->tableName} erl
                LEFT JOIN error_log_status erls ON erls.error_log_hash = erl.error_hash
                WHERE log_type = {$this->database->quote($logType)} AND datetime >= DATE_SUB(NOW(), INTERVAL {$this->database->quote($weeksBack)} WEEK) {$cond}
                " . ($groupRows ? " GROUP BY erl.error_type " : " GROUP BY erl.id ");

        return $this->database->query($sql)->fetchAll();
    }
}