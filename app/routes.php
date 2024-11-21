<?php

declare(strict_types=1);

use App\Application\Actions\User\ListUsersAction;
use App\Application\Actions\User\ViewUserAction;
use App\Application\Actions\Home\ViewHomeAction;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

use Slim\App;
use Slim\Interfaces\RouteCollectorProxyInterface as Group;

if ( !defined('BASE_PATH'))
    define('BASE_PATH', '');
// define('BASE_PATH', '/lotoquest/public');

return function (App $app) {
    // $app->options('/{routes:.*}', function (Request $request, Response $response) {
    //     // CORS Pre-Flight OPTIONS Request Handler
    //     return $response;
    // });

    if ( '' != BASE_PATH)
        $app->setBasePath(BASE_PATH);

    $app->get('/', function (Request $request, Response $response) {
        $home = new ViewHomeAction;
        return $home($request, $response);
    });

    $app->group('/users', function (Group $group) {
        $group->get('', ListUsersAction::class);
        $group->get('/{id}', ViewUserAction::class);
    });

    $app->get('/outro', function (Request $request, Response $response) {
        $home = (new ViewHomeAction)->outro();
        return $home($request, $response);
    });

    $app->get('/reset', function (Request $request, Response $response) {
        $home = (new ViewHomeAction)->reset($request);
        return $home($request, $response);
    });

    $app->get('/{loto}/{partie_id}/{round_name}/{random}', function (Request $request, Response $response, array $args = []) {
        $home = new ViewHomeAction;
        return $home($request, $response, $args);
    });

    $app->get('/{loto}/{partie_id}/{round_name}', function (Request $request, Response $response, array $args = []) {
        $home = new ViewHomeAction;
        return $home($request, $response, $args);
    });

    $app->get('/{loto}/{partie_id}', function (Request $request, Response $response, array $args = []) {
        $home = new ViewHomeAction;
        return $home($request, $response, $args);
    });

    $app->get('/{loto}', function (Request $request, Response $response, array $args = []) {
        $home = new ViewHomeAction;
        return $home($request, $response, $args);
    });
};
