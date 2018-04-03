<?php
/**
 * Created by PhpStorm.
 * User: jamesroberson
 * Date: 3/19/18
 * Time: 5:36 PM
 */

namespace App;


class GoogleClientBuilder
{

    protected $gclient;

    protected $secret_store;

    public function __construct(\Google_Client $gclient, ISecretStore $secret_store)
    {
        $this->gclient = $gclient;

        $this->secret_store = $secret_store;
    }


    //validate strings aren't empty
    private function isInvalidStr($input) {

        return !is_string($input) || strlen($input) === 0;
    }


    //validate arrays aren't empty
    private function isInvalidArray($input) {

        return !is_array($input) || empty($input);
    }


    private function refreshCredentials() {

        //returns an array of new credentials
        return $this->gclient->fetchAccessTokenWithRefreshToken($this->gclient->getRefreshToken());
    }


    //used for secret filenames and secretstore property names
    private function sanitizeSecretName($name) {

        $pattern = '/^[\w\d\s\-_]{4,}$/';
        if ($this->isInvalidStr($name) || !preg_match($pattern, $name)) {

            throw new \Exception("Valid secret name string is required: " . $pattern);
        }

        //append the suffix, trade spaces for underscores and lower
        return strtolower(str_replace(' ', '_', $name));
    }


    //secret name is the name of the file and/or datastore property
    public function authenticateClient($app_name, array $scopes, $client_id_name, $credentials_name) {

        if ($this->isInvalidStr($app_name)) {

            throw new \Exception('Application name string is required');
        }

        if ($this->isInvalidArray($scopes)) {

            throw new \Exception('An array of scope(s) is required: ');
        }

        if ($this->isInvalidStr($client_id_name)) {

            throw new \Exception('Client Id name string is required');
        }
        $client_id_name = $this->sanitizeSecretName($client_id_name);

        if ($this->isInvalidStr($credentials_name)) {

            throw new \Exception('Credentials name string is required');
        }
        $credentials_name = $this->sanitizeSecretName($credentials_name);


        //TODO does app_name affect anything
        //application name
        $this->gclient->setApplicationName($app_name);

        //access type
        $this->gclient->setAccessType('offline');

        //scopes
        $this->gclient->setScopes(implode(' ', $scopes));

        //client id
        $this->gclient->setAuthConfig($this->secret_store->get($client_id_name));

        //credentials
        $this->gclient->setAccessToken($this->secret_store->get($credentials_name));


        //refresh credentials if they need it
        if ($this->gclient->isAccessTokenExpired()) {

            $this->secret_store->set($credentials_name, $this->refreshCredentials());
        }
    }


    public function getClient() {

        return $this->gclient;
    }

}