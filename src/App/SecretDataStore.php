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

    const GCDS_KIND = 'config';
    const GCDS_ID = '5629499534213120';

    protected $ds_key;

    protected $datastore;


    private function __construct($datastore, $kind, $id)
    {

        if ($this->isInvalidStr($kind)) {

            throw new \Exception('Google Cloud Datastore Kind required');
        }

        if ($this->isInvalidStr($id)) {

            throw new \Exception('Google Cloud Datastore ID required');
        }

        $this->ds_key = $datastore->key(self::GCDS_KIND, self::GCDS_ID);

        $this->datastore = $datastore;
    }


    private function isInvalidStr($string) {

        return !is_string($string) || strlen($string) === 0;
    }


    public function get($property) {

        if ($this->isInvalidStr($property)) {

            throw new \Exception('Property name string is required');
        }

        if ($secret = $this->datastore->lookup($this->ds_key)->$property) {

            return $secret;
        }

        return NULL;
    }


    public function set($property, $new_value) {

        if ($this->isInvalidStr($property)) {

            throw new \Exception('Property name string is required');
        }

        if (!isset($new_value)) {

            throw new \Exception('Value not set');
        }

        $transaction = $this->datastore->transaction();

        $entity = $transaction->lookup($this->ds_key);

        $entity[$property] = $new_value;

        $transaction->upsert($entity);

        $transaction->commit();
    }

    public static function create($kind, $id) {

//        return new static(new DatastoreClient(['projectId' => getenv('GOOGLE_CLOUD_PROJECT')]));

        return new static(new DatastoreClient(), $kind, $id);   //get the project id and service key from the env
    }

}