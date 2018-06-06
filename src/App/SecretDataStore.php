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
    protected $datastore;

    protected $entity_key;

    protected $secrets_path;

    protected $prefix;      // property/filename prefix


    private function __construct(DatastoreClient $datastore, $secrets_path, $prefix)
    {

        $this->prefix = $prefix;

        $this->datastore = $datastore;

        $this->secrets_path = $secrets_path;
    }


    private function isInvalidStr($string) {

        return !is_string($string) || strlen(trim($string)) === 0;
    }


    //validate arrays aren't empty
    private function isInvalidArray($input) {

        return !is_array($input) || empty($input);
    }


    private function generatePrefixedSecretName($secret_name) {

        if ($this->isInvalidStr($secret_name)) {

            throw new \Exception('Secret property name string required');
        }

        //no valid prefix
        if ($this->isInvalidStr($this->prefix)) {

            return $secret_name;
        }

        return $this->prefix . '_' . $secret_name;
    }


    //used for secret filenames and secretstore property names
    private function sanitizeSecretName($secret_name) {

        $pattern = '/^[\w\d\s\-_]{4,}$/';
        if ($this->isInvalidStr($secret_name) || !preg_match($pattern, $secret_name)) {

            throw new \Exception("Valid secret name string is required: " . $pattern);
        }

        //append the suffix, trade spaces for underscores and lower
        return strtolower(str_replace(' ', '_', $secret_name));
    }


    private function generateSecretPath($secret_name)
    {
        if ($this->isInvalidStr($secret_name)) {

            throw new \Exception('Secret name string is required');
        }

        return $this->secrets_path . '/' . $secret_name . '.json';
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


    private function arraySecretFromDataStore($secret_name) : array {

        if ($this->isInvalidStr($secret_name)) {

            throw new \Exception('Secret property name string required');
        }

        $secret = $this->datastore->lookup($this->entity_key)->$secret_name;
        $secret = json_decode($secret, true);
        //$secret = json_decode($this->datastore->get($property), true);
        if (json_last_error() !== JSON_ERROR_NONE) {

            throw new \Exception('Invalid secret json');
        }
        return $secret;
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

            throw new \Exception('Secrets path does not exist');
        }

        //write the string to the path
        if (!file_put_contents($secret_path, $secret)) {

            throw new \Exception('Unable to write secret to secret path');
        }
    }


    public function get($secret_name) {

        if ($this->isInvalidStr($secret_name)) {

            throw new \Exception('Secret name string is required');
        }

        //clean-up and prefix
        $secret_name = $this->sanitizeSecretName($this->generatePrefixedSecretName($secret_name));

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


    public function set($secret_name, $new_secret) {

        if ($this->isInvalidStr($secret_name)) {

            throw new \Exception('Secret property name string is required');
        }

        //clean-up and prefix
        $secret_name = $this->sanitizeSecretName($this->generatePrefixedSecretName($secret_name));

        if (!isset($new_secret)) {

            throw new \Exception('New value not set');
        }

        //string it
        if (is_array($new_secret)) $new_value = json_encode($new_secret, JSON_UNESCAPED_SLASHES);

        $transaction = $this->datastore->transaction();

        if(!$entity = $transaction->lookup($this->entity_key)) {

            throw new \Exception('no entity for this key');
        }

        $entity[$secret_name] = json_encode($new_secret);

        $transaction->upsert($entity);

        $transaction->commit();

        //save
        $this->writeSecret($this->generateSecretPath($secret_name), $new_secret);
    }


    public function setKey($kind, $id) {

        if ($this->isInvalidStr($kind)) {

            throw new \Exception('Google Cloud Datastore entity kind is required');
        }

        if ($this->isInvalidStr($id)) {

            throw new \Exception('Google Cloud Datastore entity id is required');
        }

        $this->entity_key = $this->datastore->key($kind, $id);
    }

    
    public static function create(DatastoreClient $datastore, $secrets_path, $prefix = NULL) {
        
        if (!file_exists(dirname($secrets_path))) {

            throw new \Exception('Secrets path does not exist');
        }
        
        if ($prefix !== NULL && (!is_string($prefix) || strlen($prefix) === 0)) {
            
            throw  new \Exception('Invalid prefix value');
        }

        return new static($datastore, $secrets_path, $prefix);
    }
}