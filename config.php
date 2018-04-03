<?php

use Psr\Container\ContainerInterface as ContainerInterface;

return [

    /***********************************/
    /* change these in .env to suit your needs */

    /* local secrets cache dir */
    'secrets.path' => getenv("SECRETS_PATH"),

    /* Google Cloud Datastore entity values */
    'secrets_store.kind' => getenv("GOOGLE_CLOUD_DATASTORE_ENTITY_KIND"),
    'secrets_store.id' => getenv("GOOGLE_CLOUD_DATASTORE_ENTITY_ID"),

    /* prefix for secret files/properties */
    'secrets_store.prefix' => getenv("SECRETS_STORE_PREFIX"),


    /* Google Client */
    'gclient.app_name' => getenv("APP_NAME"),
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

        $secrets_store =  App\SecretDataStore::create(
            new Google\Cloud\Datastore\DatastoreClient(),
            $c->get('secrets.path'),
            $c->get('secrets_store.prefix')
        );

        $secrets_store->setKey($c->get('secrets_store.kind'), $c->get('secrets_store.id'));

        return $secrets_store;
    },


    /* Google Client Builder */
    'App\GoogleClientBuilder' => function (ContainerInterface $c) {

        /* If I let DI handle this, the container will try to inject the Google_Client definition below, when I want a new Google_Client */
        return new App\GoogleClientBuilder(new \Google_Client(), $c->get('App\ISecretStore'));
    },


    /* Google Client */
    'Google_Client' => function (ContainerInterface $c) {

        $builder = $c->get('App\GoogleClientBuilder');
        $builder->authenticateClient(
            $c->get('gclient.app_name'),
            $c->get('gclient.scopes')
        );
        return $builder->getClient();
    },


    /* twig */
    'Twig_LoaderInterface' => DI\create('Twig_Loader_Filesystem')
        ->constructor(__DIR__ . '/resources/views'),
];
