<?php
/**
 * Created by PhpStorm.
 * User: jamesroberson
 * Date: 3/27/18
 * Time: 2:39 PM
 */

namespace App\Controllers;

use App\GmailService;

class MessageController implements IController
{

    //authenticated google client
    protected $gclient;

    public function __construct(\Google_Client $gclient)
    {

        $this->gclient = $gclient;
    }

    public function execute(array $params)
    {

        try {

//            //check the environment for service credentials
//            if (!getenv('GOOGLE_CLOUD_PROJECT', true)) {
//
//                throw new \Exception('Google Cloud Project Id not found');
//            }
//
//            if (!getenv('GOOGLE_APPLICATION_CREDENTIALS', true)) {
//
//                throw new\Exception('Google Application credentials path not found');
//            }

            if (!$params['message_id']) {

                throw new \Exception('Message id is required');
            }



            //authenicated google service
            $gmail = GmailService::create($this->gclient);

            echo json_encode($gmail->getMessage($params['message_id']));

        } catch (\Exception $e) {

            echo $e->getMessage();
        }
    }

}