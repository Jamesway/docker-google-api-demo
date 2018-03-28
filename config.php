<?php


return [

    'Psr\Container\ContainerInterface' => function (Psr\Container\ContainerInterface $container) {
        return $container;
    },

    /* Route to Controllers */
    'FastRoute\RouteParser' => DI\autowire('FastRoute\RouteParser\Std'),
    'FastRoute\DataGenerator' => DI\autowire('FastRoute\DataGenerator\GroupCountBased'),
    'FastRoute\RouteCollector' => DI\autowire('FastRoute\RouteCollector')
        /* only comma the last route */
        ->method('addRoute', 'GET', '/message/{message_id:[a-f0-9]+}', 'App\Controllers\MessageController')
        ->method('addRoute', 'GET', '/page/{page_history:[0-9\:]+}', 'App\Controllers\MailListController')
        ->method('addRoute', 'GET', '/', 'App\Controllers\MailListController'),

    'FastRoute\Dispatcher' => function (FastRoute\RouteCollector $collector) {
        return new FastRoute\Dispatcher\GroupCountBased($collector->getData());
    },

    /* twig */
    'views.path' => __DIR__ . '/resources/views',
    'Twig_LoaderInterface' => DI\create('Twig_Loader_Filesystem')->constructor(DI\get('views.path')),

    /* Secrets */
    'redis.conn' => [
        "scheme" => DI\env('REDIS_SCHEME'),
        "host" => DI\env('REDIS_HOST'),
        "port" => DI\env('REDIS_PORT'),
        "password" => DI\env('REDIS_PASSWORD')
    ],


    /* Redis */
    /*'Predis\Client' => DI\create()->constructor(DI\get('redis.conn'))*/

];
