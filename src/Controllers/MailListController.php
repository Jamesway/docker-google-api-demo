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

class MailListController implements IController
{

    protected $container;
    protected $twig;

    public function __construct(\Google_Client $gclient, \Twig_Environment $twig)
    {

        $this->twig = $twig;

        $this->gclient = $gclient;
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



//            //build the builder
//            $builder = new GoogleClientBuilder(new \Google_Client(), SecretDataStore::create());
//            $scopes = [\Google_Service_Gmail::GMAIL_READONLY];
//            $builder->authenticateClient("test app", $scopes, 'GMAIL_READONLY');
//
//            //authenticated google client
//            $gclient = $builder->getClient();

            //authenicated google service
            $gmail = GmailService::create($this->gclient, 'me');

            $messages = [];

            //since there's no "previous" pageToken method in gmail api, use a delim string/array of page history, not pretty since the links will be long
            $page_history = isset($params['page_history']) && $params['page_history'] ? explode(':', $params['page_history']) : [];

            //page_token is the last item in page_history
            $current_page_token = end($page_history);
            $next_page_token = NULL;


            //we're reading all the messages in the inbox
            //were we actually making a gmail client,
            //we should initially read all the messages in the inbox (users.messages.list)
            //then store the history id
            //then use users.history.list to list messages since the stored id
            //then store the new history id
            //wash, rinse, repeat
            if ($result = $gmail->getList($current_page_token)) {

                $messages = array_merge($messages, $result['messages']);
                $next_page_token = $result['nextPageToken'];
            }

            //built a paginator because generating previous links is tricky since 1st page hase no page token
            $pagination = $gmail->paginator($page_history, $next_page_token);

            //list gives us the ids, but we need to get the messages for more data
            //to get the meta we need to batch gmail->get()
            $this->gclient->setUseBatch(true);
            $batch = new \Google_Http_Batch($this->gclient);
            $batch_gmail = GmailService::create($this->gclient, 'me');    //batch enable service

            //get subject and from
            foreach ($messages as $msg) {

                $batch->add($batch_gmail->getMeta($msg['id']), $msg['id']); //the second param is for identifying the individual request
            }
            $messages = $batch->execute();


            //render template
            echo $this->twig->render('gmail.html.twig',
                [
                    "title" => "Gmail Demo",
                    "messages" => $messages,
                    "prevPage" => $pagination['prev'],
                    "nextPage" => $pagination['next']
                ]
            );

        } catch (\Exception $e) {

            echo $e->getMessage();

            echo $this->twig->render('gmail.html.twig', ["title" => "Oh Snap!", "error" => "error"]);

        }
    }



}
