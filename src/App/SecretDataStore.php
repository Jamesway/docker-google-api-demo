<?php
/**
 * Created by PhpStorm.
 * User: jamesroberson
 * Date: 2/28/18
 * Time: 3:38 PM
 */

namespace App;

use Google\Cloud\Datastore\DatastoreClient;

class SecretDataStore implements ISecretStore
{

    //https://cloud.google.com/datastore/docs/concepts/entities
    protected $ds_key;
    protected $kind;
    protected $id;
    protected $datastore;

    const SECRET_PATH = "/var/www/.secrets";    //based on runtime location


    public function __construct(DatastoreClient $datastore, $kind, $id)
    {

        if ($this->isInvalidStr($kind)) {

            throw new \Exception('Google Cloud Datastore Kind required');
        }
        $this->kind = $kind;

        if ($this->isInvalidStr($id)) {

            throw new \Exception('Google Cloud Datastore ID required');
        }
        $this->id = $id;

        $this->ds_key = $datastore->key($this->kind, $this->id);

        $this->datastore = $datastore;
    }


    private function isInvalidStr($string) {

        return !is_string($string) || strlen($string) === 0;
    }

    //validate arrays aren't empty
    private function isInvalidArray($input) {

        return !is_array($input) || empty($input);
    }


    public function set($property, $new_value) {

        if ($this->isInvalidStr($property)) {

            throw new \Exception('Property name string is required');
        }

        if (!isset($new_value)) {

            throw new \Exception('Value not set');
        }

        if (is_array($new_value)) $new_value = json_encode($new_value, JSON_UNESCAPED_SLASHES);

        $transaction = $this->datastore->transaction();

        $entity = $transaction->lookup($this->ds_key);

        $entity[$property] = json_encode($new_value);

        $transaction->upsert($entity);

        $transaction->commit();

        //save
        $this->writeSecret($this->generateSecretPath($property), $new_value);
    }

//    public function set($secret_name, $secret) {
//
//        $this->writeSecret($this->generateSecretPath($secret_name), $secret);
//    }



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


    private function arraySecretFromDataStore($property) : array {

        if ($this->isInvalidStr($property)) {

            throw new \Exception('Property name string required');
        }

        $secret = $this->datastore->lookup($this->ds_key)->$property;
        $secret = json_decode($secret, true);
        //$secret = json_decode($this->datastore->get($property), true);
        if (json_last_error() !== JSON_ERROR_NONE) {

            throw new \Exception('Invalid secret json');
        }
        return $secret;
    }


    public function get($secret_name) {

        if ($this->isInvalidStr($secret_name)) {

            throw new \Exception('Secret name string is required');
        }

        //try local file cache
        if ($secret = $this->arraySecretFromFile($this->generateSecretPath($secret_name))) {

            return $secret;
        }

        //then try secret store
        if ($secret = $this->arraySecretFromDataStore($secret_name)) {

            //write to local file
            $this->writeSecret($this->generateSecretPath($secret_name), $secret);

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
}