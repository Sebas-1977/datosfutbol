<?php

declare(strict_types=1);

namespace Controllers;

use MVC\Router;

class PlantelController
{
    public static function index(Router $router): void
    {
        $router->render('plantel/index', ['titulo' => 'Plantel']);
    }
}