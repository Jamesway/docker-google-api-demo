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

    /*
     * $secret_name + SECRET_TYPE_SUFFIX become secretstore property names and secret filenames
     * make sure your secretstore properties match what's generated from $secret_name + SECRET_TYPE_SUFFIX, change accordingly
     * eg $secret_name = "gmail_test";
     *    client_id secretstore property -> gmail_test_client_id
     *    client_id secret filename -> gmail_test_client_id.json
     * 
     *    credentials secretstore property -> gmail_test_credentials
     *    credentials secret filename -> gmail_test_credentials.json
     */
    const CLIENT_ID_SUFFIX = "client_id";
    const CREDENTIALS_SUFFIX = "credentials";

    const SECRET_PATH = "/var/www/.secrets";    //based on runtime location

    protected $gclient;

    protected $secret_store;

    public function __construct(\Google_Client $gclient, ISecretStore $secret_store = NULL)
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


    private function arraySecretFromFile($secret_path) : array {

        if ($this->isInvalidStr($secret_path)) {

            throw new \Exception('Secret path string required');
        }

        if (file_exists($secret_path)) {

            $secret = json_decode(file_get_contents($secret_path), true);
            if (json_last_error() !== JSON_ERROR_NONE) {

                throw new \Exception('Invalid secret json');
            }
            return $secret;
        }
        return [];
    }


    private function arraySecretFromSecretStore($property) : array {

        if ($this->isInvalidStr($property)) {

            throw new \Exception('Property name string required');
        }

        if ($this->secret_store) {

            $secret = json_decode($this->secret_store->get($property), true);
            if (json_last_error() !== JSON_ERROR_NONE) {

                throw new \Exception('Invalid secret json');
            }
            return $secret;
        }
        return [];
    }


    //writes a secret string, secret can be array or json
    private function writeSecret($secret_path, $secret) {

        if ($this->isInvalidStr($secret_path)) {

            throw new \Exception('Secret path string is required');
        }

        if ($this->isInvalidStr($secret) && $this->isInvalidArray($secret)) {

            throw new \Exception('Secret must be string or array');
        }

        //json encode the array without escaping quotes
        if (is_array($secret)) {

            $secret = json_encode($secret, JSON_UNESCAPED_SLASHES);
        }

        //create the dir if necessary
        if (!file_exists(dirname($secret_path))) {

            if (!mkdir(dirname($secret_path), 0700, true)) {

                throw new \Exception('Unable to create directory ' . dirname($secret_path));
            }
        }

        //write the string to the path
        if (!file_put_contents($secret_path, $secret)) {

            throw new \Exception('Unable to write secret to ' . $secret_path);
        }
    }


    //convenience method
    private function generateSecretPath($secret_name)
    {
        if ($this->isInvalidStr($secret_name)) {

            throw new \Exception('Secret name string is required');
        }

        return self::SECRET_PATH . '/' . $secret_name . '.json';
    }


    //facade for getting secret arrays
    private function getSecret($secret_name) {

        if ($this->isInvalidStr($secret_name)) {

            throw new \Exception('Secret name string is required');
        }

        //try local file cache
        if ($secret = $this->arraySecretFromFile($this->generateSecretPath($secret_name))) {

            return $secret;
        }

        //then try secret store
        if ($secret = $this->arraySecretFromSecretStore($secret_name)) {

            //write to local file
            $this->writeSecret($this->generateSecretPath($secret_name), $secret);

            return $secret;
        }

        return NULL;
    }


    private function refreshCredentials() : array {

        //TODO I don't think it's necessary to update the secret store since the refresh token should be the same
        if ($this->gclient->isAccessTokenExpired()) {

            $tmp = 1;

            //returns an array of new credentials
            return $this->gclient->fetchAccessTokenWithRefreshToken($this->gclient->getRefreshToken());
        }
        return [];
    }

    //used for secret filenames and secretstore property names
    private function generateSecretName($name, $suffix) {

        $pattern = '/^[\w\d\s\-_]{4,}$/';
        if ($this->isInvalidStr($name) || !preg_match($pattern, $name)) {

            throw new \Exception("Valid secret name string is required: " . $pattern);
        }

        if ($this->isInvalidStr($suffix)) {

            throw new \Exception('A suffix string is required');
        }

        //append the suffix, trade spaces for underscores and lower
        return strtolower(str_replace(' ', '_', $name) . '_' . $suffix);
    }


    //secret name is the name of the file and/or datastore property
    public function authenticateClient($app_name, array $scopes, $secret_name) {

        if ($this->isInvalidStr($app_name)) {

            throw new \Exception('Application name string is required');
        }

        if ($this->isInvalidArray($scopes)) {

            throw new \Exception('An array of scope(s) is required: ');
        }

        if ($this->isInvalidStr($secret_name)) {

            throw new \Exception('Secret name string is required');
        }

        //TODO does app_name affect anything
        //application name
        $this->gclient->setApplicationName($app_name);

        //access type
        $this->gclient->setAccessType('offline');

        //scopes
        $this->gclient->setScopes(implode(' ', $scopes));
        
        
        //this needs to match up with secretstore property and the json filename
        //eg property: gmail_test_client_id / filename: gmail_test_client_id.json
        $client_id_name = $this->generateSecretName($secret_name , self::CLIENT_ID_SUFFIX);
        $this->gclient->setAuthConfig($this->getSecret($client_id_name));       //setAuthConfig takes an array or a file path to json

        //this needs to match up with the secretstore property and the json filename
        //eg property: gmail_test_creds / filename: gmail_test_creds.json
        $credentials_name = $this->generateSecretName($secret_name,  self::CREDENTIALS_SUFFIX);
        $this->gclient->setAccessToken($this->getSecret($credentials_name));

        //refresh credentials if they need it
        if (!empty($new_credentials = $this->refreshCredentials())) {
            
            //store new creds locally
            $this->writeSecret($this->generateSecretPath($credentials_name), $new_credentials);
        }
    }


    public function getClient() {

        return $this->gclient;
    }

}