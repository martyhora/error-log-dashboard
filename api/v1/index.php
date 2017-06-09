<?php
use DbModel\ErrorLogModel;
use DbModel\ErrorLogStatusModel;
use Medoo\Medoo;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

require 'vendor/autoload.php';

require 'config.php';

$weeksBack = 5;

$app = new \Slim\App;

$app->get('/projects', function (Request $request, Response $response, $args) use ($config) {
    return $response->withJson(array_keys($config));
});

$app->get('/errors/by-date/{project}/{logType}/{hideResolved}/{groupRows}', function (Request $request, Response $response, $args) use ($config, $weeksBack) {
    $errorLogModel = new ErrorLogModel(new Medoo($config[$args['project']]['db']));

    $mutedErrors = $config[$args['project']]['mutedErrors'];

    $data = $errorLogModel->getErrorsByDate($args['logType'], $weeksBack, $args['hideResolved'], $mutedErrors, $args['groupRows']);

    return $response->withJson([
        'errors'     => array_values($data),
        'errorTypes' => array_values($errorLogModel->getErrorsByType($args['logType'], $weeksBack, $args['hideResolved'], $mutedErrors, $args['groupRows'])),
    ]);
});

$app->get('/errors/by-day/{project}/{logType}/{hideResolved}/{groupRows}/{date}', function (Request $request, Response $response, $args) use ($config, $weeksBack) {
    $errorLogModel = new ErrorLogModel(new Medoo($config[$args['project']]['db']));

    $mutedErrors = $config[$args['project']]['mutedErrors'];

    $data = $errorLogModel->getErrorsByDay($args['logType'], $args['date'], null, $args['hideResolved'], $mutedErrors, null, $args['groupRows']);

    return $response->withJson(array_values($data));
});

$app->get('/errors/by-last-days/{project}/{logType}/{hideResolved}/{groupRows}/{errorType}', function (Request $request, Response $response, $args) use ($config, $weeksBack) {
    $errorLogModel = new ErrorLogModel(new Medoo($config[$args['project']]['db']));

    $mutedErrors = $config[$args['project']]['mutedErrors'];

    $data = $errorLogModel->getErrorsByDay($args['logType'], null, $weeksBack * 7, $args['hideResolved'], $mutedErrors, isset($args['errorType']) ? $args['errorType'] : null, $args['groupRows']);

    return $response->withJson(array_values($data));
});

$app->put('/errors/resolve/{project}/{errorHash}', function (Request $request, Response $response, $args) use ($config) {
    $errorLogStatusModel = new ErrorLogStatusModel(new Medoo($config[$args['project']]['db']));

    $rowId = $errorLogStatusModel->fetchByErrorHash($args['errorHash']);

    if ($rowId)
    {
        $errorLogStatusModel->updateById(['resolved_date' => date('Y-m-d H:i:s')], $rowId);
    }
    else
    {
        $errorLogStatusModel->insert(['error_log_hash' => $args['errorHash'], 'resolved_date' => date('Y-m-d H:i:s')]);
    }
});

$app->run();