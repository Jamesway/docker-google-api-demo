<?php

use Psr\Container\ContainerInterface as ContainerInterface;

return [

    /***********************************/
    /* change these to suit your needs */

    /* Google Cloud Datastore Entity Values */
    'secretstore.kind' => 'config',
    'secretstore.id' => '5629499534213120',

    /* Google Client */
    'gclient.app_name' => 'JMail',
    'gclient.client_id_name' => 'gmail_readonly_client_id',
    'gclient.credentials_name' => 'gmail_readonly_credentials',
    'gclient.scopes' => [\Google_Service_Gmail::GMAIL_READONLY],

    /***********************************/


    /* DI Container */
    'Psr\Container\ContainerInterface' => function (ContainerInterface $container) {
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


    /* Secret Store */
    'App\ISecretStore' => function (ContainerInterface $c) {

        return new App\SecretDataStore(
            new Google\Cloud\Datastore\DatastoreClient(),
            $c->get('secretstore.kind'),
            $c->get('secretstore.id')
        );
    },
    

    /* Google Client Builder */
    'App\GoogleClientBuilder' => function (ContainerInterface $c) {

        return new App\GoogleClientBuilder(new \Google_Client(), $c->get('App\ISecretStore'));
    },


    /* Google Client */
    'Google_Client' => function (ContainerInterface $c) {

        $builder = $c->get('App\GoogleClientBuilder');
        $builder->authenticateClient(
            $c->get('gclient.app_name'),
            $c->get('gclient.scopes'),
            $c->get('gclient.client_id_name'),
            $c->get('gclient.credentials_name')
        );
        return $builder->getClient();
    },


    /* twig */
    'Twig_LoaderInterface' => DI\create('Twig_Loader_Filesystem')
        ->constructor(__DIR__ . '/resources/views'),
];
