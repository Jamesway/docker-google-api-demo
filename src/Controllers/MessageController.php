<?php
/**
 * Created by PhpStorm.
 * User: jamesroberson
 * Date: 3/27/18
 * Time: 2:39 PM
 */

namespace App\Controllers;

use App\GmailService;
use App\SecretDataStore;
use App\GoogleClientBuilder;

class MessageController implements IController
{

    public function execute(array $params)
    {

        try {

            //check the environment for service credentials
            if (!getenv('GOOGLE_CLOUD_PROJECT', true)) {

                throw new \Exception('Google Cloud Project Id not found');
            }

            if (!getenv('GOOGLE_APPLICATION_CREDENTIALS', true)) {

                throw new\Exception('Google Application credentials path not found');
            }

            if (!$params['message_id']) {

                throw new \Exception('Message id is required');
            }

            //build the builder
            $builder = new GoogleClientBuilder(new \Google_Client(), SecretDataStore::create());
            $scopes = [\Google_Service_Gmail::GMAIL_READONLY];
            $builder->authenticateClient("test app", $scopes, 'GMAIL_READONLY');

            //authenticated google client
            $gclient = $builder->getClient();

            //authenicated google service
            $gmail = GmailService::create($gclient, 'me');

            echo json_encode($gmail->getMessage($params['message_id']));

        } catch (\Exception $e) {

            echo $e->getMessage();
        }
    }

}