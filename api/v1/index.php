<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

require 'vendor/autoload.php';

require 'config.php';

$weeksBack = 5;

class Db
{
    private static function getMutedErrors($database, $mutedErrors)
    {
        $errs = [];

        foreach ($mutedErrors as $item)
        {
            $errs[] = "erl.error NOT LIKE {$database->quote("%{$item}%")}";
        }

        return implode(' AND ', $errs);
    }

    public static function getErrorsByDate($database, $logType, $weeksBack, $hideResolved, $mutedErrors, $groupRows = true)
    {
        $cond = $hideResolved ? " AND (resolved_date IS NULL OR resolved_date < datetime) AND (" . self::getMutedErrors($database, $mutedErrors) . ")" : "";

        $sql = "SELECT COUNT(*) cnt, COUNT(DISTINCT error) uniqCnt, DATE_FORMAT(DATE(datetime), '%d.%m.') date, log_type logType, DATE(datetime) datetime
                FROM error_log erl
                LEFT JOIN error_log_status erls ON erls.error_log_hash = erl.error_hash
                WHERE log_type = {$database->quote($logType)} AND datetime >= DATE_SUB(NOW(), INTERVAL {$database->quote($weeksBack)} WEEK) {$cond}
                " . ($groupRows ? " GROUP BY DATE(datetime) " : " GROUP BY erl.id ");

        return $database->query($sql)->fetchAll();
    }

    public static function getErrorsByDay($database, $logType, $day = null, $lastDays = null, $hideResolved, $mutedErrors, $errorType = null, $groupRows = true)
    {
        $cond = " AND " . ($day ? " DATE_FORMAT(DATE(datetime), '%d.%m.') = '{$day}'" : " datetime >= DATE_SUB(NOW(), INTERVAL {$database->quote($lastDays)} DAY)");

        $cond .= $hideResolved ? " AND (resolved_date IS NULL OR resolved_date < datetime) AND (" . self::getMutedErrors($database, $mutedErrors) . ")" : "";

        $cond .= $errorType && $errorType !== 'false' ? " AND error_type = {$database->quote($errorType)}" : "";
		
		$order = "cnt DESC, erl.datetime DESC";

		if ($day === date('d.m.'))
		{
			$order = "erl.datetime DESC, cnt DESC";
		}

        $sql = "SELECT COUNT(*) cnt, SUBSTRING(error, LOCATE(':', error) + 2) error, error_type errorType,  DATE_FORMAT(datetime, '%d.%m.') date, DATE_FORMAT(MAX(datetime), '%H:%i:%s') time, url,
                       error_hash errorHash, DATE(erls.resolved_date) resolvedDate, log_type logType, DATE(datetime) datetime,
                       DATE_FORMAT(first_occurence_datetime, '%d.%m.%Y') firstOccurenceDatetime, DATE_FORMAT(last_occurence_datetime, '%d.%m.%Y') lastOccurenceDatetime,
					   erls.id errId, error_file errorFile
                FROM error_log erl
                LEFT JOIN error_log_status erls ON erls.error_log_hash = erl.error_hash
                WHERE log_type = {$database->quote($logType)} {$cond}
                " . ($groupRows ? " GROUP BY error_hash " : " GROUP BY erl.id ") . "
                ORDER BY {$database->quote($order)}, erl.id";

        return $database->query($sql)->fetchAll();
    }

    public static function getErrorsByType($database, $logType, $weeksBack, $hideResolved, $mutedErrors, $groupRows = true)
    {
        $cond = $hideResolved ? " AND (resolved_date IS NULL OR resolved_date < datetime) AND (" . self::getMutedErrors($database, $mutedErrors) . ")" : "";

        $sql = "SELECT COUNT(*) cnt, error_type errorType
                FROM error_log erl
                LEFT JOIN error_log_status erls ON erls.error_log_hash = erl.error_hash
                WHERE log_type = {$database->quote($logType)} AND datetime >= DATE_SUB(NOW(), INTERVAL {$database->quote($weeksBack)} WEEK) {$cond}
                " . ($groupRows ? " GROUP BY erl.error_type " : " GROUP BY erl.id ");

        return $database->query($sql)->fetchAll();
    }
}

$app = new \Slim\App;

$app->get('/projects', function (Request $request, Response $response, $args) use ($config) {
    return $response->withJson(array_keys($config));
});

$app->get('/errors/by-date/{project}/{logType}/{hideResolved}/{groupRows}', function (Request $request, Response $response, $args) use ($config, $weeksBack) {
    $database = new \Medoo\Medoo($config[$args['project']]['db']);

    $mutedErrors = $config[$args['project']]['mutedErrors'];

    $data = Db::getErrorsByDate($database, $args['logType'], $weeksBack, $args['hideResolved'], $mutedErrors, $args['groupRows']);

    return $response->withJson([
        'errors'     => array_values($data),
        'errorTypes' => array_values(Db::getErrorsByType($database, $args['logType'], $weeksBack, $args['hideResolved'], $mutedErrors, $args['groupRows'])),
    ]);
});

$app->get('/errors/by-day/{project}/{logType}/{hideResolved}/{groupRows}/{date}', function (Request $request, Response $response, $args) use ($config, $weeksBack) {
    $database = new \Medoo\Medoo($config[$args['project']]['db']);

    $mutedErrors = $config[$args['project']]['mutedErrors'];

    $data = Db::getErrorsByDay($database, $args['logType'], $args['date'], null, $args['hideResolved'], $mutedErrors, null, $args['groupRows']);

    return $response->withJson(array_values($data));
});

$app->get('/errors/by-last-days/{project}/{logType}/{hideResolved}/{groupRows}/{errorType}', function (Request $request, Response $response, $args) use ($config, $weeksBack) {
    $database = new \Medoo\Medoo($config[$args['project']]['db']);

    $mutedErrors = $config[$args['project']]['mutedErrors'];

    $data = Db::getErrorsByDay($database, $args['logType'], null, $weeksBack * 7, $args['hideResolved'], $mutedErrors, isset($args['errorType']) ? $args['errorType'] : null, $args['groupRows']);

    return $response->withJson(array_values($data));
});

$app->put('/errors/resolve/{errorHash}', function (Request $request, Response $response, $args) use ($config) {
    $database = new \Medoo\Medoo($config[$args['project']]['db']);

    $sql = "SELECT id FROM error_log_status WHERE error_log_hash = '{$database->quote($args['errorHash'])})'";

    $row = $database->query($sql)->fetch();

    if (isset($row['id']))
    {
        $database->update('error_log_status', ['resolved_date' => date('Y-m-d H:i:s')], ['id' => $row['id']]);
    }
    else
    {
        $database->insert('error_log_status', ['error_log_hash' => $args['errorHash'], 'resolved_date' => date('Y-m-d H:i:s')]);
    }
});

$app->run();