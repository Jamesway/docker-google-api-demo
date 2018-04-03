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

        //authenticated google client
        $this->gclient = $gclient;
    }

    public function execute(array $params)
    {

        try {

            if (!$params['message_id']) {

                throw new \Exception('Message id is required');
            }

            //authenicated gmail service
            $gmail = GmailService::create($this->gclient);

            echo json_encode($gmail->getMessage($params['message_id']));

        } catch (\Exception $e) {

            echo $e->getMessage();
        }
    }

}