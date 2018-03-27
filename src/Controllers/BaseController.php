<?php
/**
 * Created by PhpStorm.
 * User: jamesroberson
 * Date: 2/27/18
 * Time: 2:00 PM
 */

namespace App\Controllers;


use App\GmailService;
use App\SecretDataStore;
use App\GoogleClientBuilder;

class BaseController implements IController
{

    protected $twig;

    public function __construct(\Twig_Environment $twig)
    {

        $this->twig = $twig;
    }

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

            //build the builder
            $builder = new GoogleClientBuilder(new \Google_Client(), SecretDataStore::create());
            $scopes = [\Google_Service_Gmail::GMAIL_READONLY];
            $builder->authenticateClient("test app", $scopes, 'GMAIL_READONLY');

            //authenticated google client
            $gclient = $builder->getClient();

            //authenicated google service
            $gmail = GmailService::create($gclient, 'me');

            //this reads all messages
            //to be more useful:
            //list all messages
            //get the history id and store it
            //look up the messages using the stored history id
            //store the new history id
            //repeat
            $messages = [];

            //since there's no "previous" pageToken method in gmail api, use a delim string/array of page history, not pretty since the links will be long
            $page_history = isset($params['page_history']) && $params['page_history'] ? explode(':', $params['page_history']) : [];

            //page_token is the last item in page_history
            $page_token = end($page_history);

            if ($result = $gmail->getList($page_token)) {

                $messages = array_merge($messages, $result['messages']);
                $page_token = $result['nextPageToken'];
            }

            //batch getting the emails from the ids
            $gclient->setUseBatch(true);
            $batch = new \Google_Http_Batch($gclient);

            $gmail = new \Google_Service_Gmail($gclient);

            foreach ($messages as $msg) {

                $request = $gmail->users_messages->get('me', $msg['id']);
                $batch->add($request, $msg['id']);

            }
            $messages = $batch->execute();


            //use the page history array to generate next and previous links
            //prev page
            $prev_page = implode(':', array_slice($page_history, 0, -1));

            //next page
            //trim : when prev_page = '' eg first page
            $next_page = $page_token ? trim($prev_page . ':' . $page_token, ':') : '';

            echo $this->twig->render('gmail.html.twig',
                [
                    "title" => "Gmail Demo",
                    "messages" => $messages,
                    "prevPage" => $prev_page,
                    "nextPage" => $next_page
                ]
            );

        } catch (\Exception $e) {

            echo $e->getMessage();

            echo $this->twig->render('gmail.html.twig', ["title" => "Gmail Demo - Error", "error" => "error"]);

        }
    }



}
