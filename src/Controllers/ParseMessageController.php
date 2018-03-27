<?php
/**
 * Created by PhpStorm.
 * User: jamesroberson
 * Date: 2/27/18
 * Time: 2:00 PM
 */

namespace App\Controllers;


use RDCloud\App\GmailService;
use RDCloud\App\SecretDataStore;
use RDCloud\App\GoogleClientBuilder;

class ParseMessageController implements IController
{

    public function execute(array $params)
    {


        echo time(), "<br>";

        echo "step 1) check for api credentials for cloud datastore access<br>";

        if (!getenv('GOOGLE_CLOUD_PROJECT')) {

            throw new \Exception('Google Cloud Project Id not found', true);
        }

        if (!getenv('GOOGLE_APPLICATION_CREDENTIALS', true)) {

            throw new \Exception('Google Application credentials path not found');
        }
        echo "using ENV for service credentials<br>";
        echo "step 2) create an authenticated and scoped Google Client from datastore credentials or local credentials<br>";


        echo "step 3) gmail service";

        $scopes = [\Google_Service_Gmail::GMAIL_READONLY];
        $builder = new GoogleClientBuilder(new \Google_Client(), SecretDataStore::create());
        $builder->authenticateClient("test app", $scopes, 'GMAIL_READONLY');
        $gclient = $builder->getClient();

        $gmail = GmailService::create($gclient, 'me');

        echo "step 4) sync";

        //https://developers.google.com/gmail/api/guides/sync
        //check kv store for history id, if none get the list (with pagination) to seed the history id

        $redis = new \Predis\Client([
            "scheme" => "tcp",
            "host" => getenv("REDIS_HOST"),
            "port" => getenv("REDIS_PORT"),
            "password" => getenv("REDIS_PASSWORD")
        ]);



        echo "<pre>";
        $messages = [];
        $page_token = NULL;

        do {

            if ($history_id = $redis->get('HISTORY_ID')) {

                echo "<br>get history list: ", $history_id, "<br>";
                if ($result = $gmail->getHistoryList($history_id, $page_token)) {

                    $messages = array_merge($messages, $result['messages']);
                    $page_token = $result['nextPageToken'];
                }

            } else {

                echo "<br>get list<br>";
                if ($result = $gmail->getList($page_token)) {
                    $messages = array_merge($messages, $result['messages']);
                    $page_token = $result['nextPageToken'];
                }
            }

        } while ($page_token);

        print_r($messages);

        echo "</pre>";

        //$redis->set('HISTORY_ID', 0, 'ex', 30);
        $redis->set('HISTORY_ID', $gmail->getHistoryId(), 'ex', 600);

        echo $gmail->getHistoryId();

//        //echo "message 1624ee3b26d829dc<br>";
//        echo "list messages<br>";
//        echo "<pre>";
//
//        $page_token = NULL;
//        $messages = [];
//        do {
//
//            if ($result = $gmail->getList($page_token)) {
//                $messages = array_merge($messages, $result['messages']);
//                $page_token = $result['nextPageToken'];
//            }
//        } while ($page_token);
//
//        print_r($messages);
//        echo "</pre>";
//
//        $history_id = $gmail->getHistoryId();
//        echo "history id: ", $history_id, "<br>";
//
//        $redis->set('HISTORY_ID', '15a98d0fac9415da', "ex", 3600);
//
//        //if ($redis->get('1624ee3b26d829dc1') > 0) echo "here";
//        //else echo "there";
//
//
//        echo $gmail->getHistoryId(), "<br>";
//
//        echo "history list<br>";
//
//        if ($history_id = $redis->get('HISTORY_ID')) {
//
//            echo "<pre>";
//
//            $messages = [];
//            $page_token = NULL;
//            if ($result = $gmail->getHistoryList($gmail->getHistoryId(), $page_token)) {
//
//                $messages = array_merge($messages, $result['messages']);
//
//            }
//
//            print_r($messages);
//
//            echo "</pre>";
//        }

    }



}