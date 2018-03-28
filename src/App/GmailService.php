<?php
/**
 * Created by PhpStorm.
 * User: jamesroberson
 * Date: 2/27/18
 * Time: 5:38 PM
 */
// Google_Service_Gmail decorator

namespace App;


class GmailService
{
    protected $gmail;

    protected $user_id;

    private function __construct(\Google_Service_Gmail $gmail, $user_id)
    {

        $this->gmail = $gmail;

        $this->user_id = $user_id;
    }




    //https://developers.google.com/gmail/api/v1/reference/users/messages/list
    public function getList($page_token = NULL, $limit = 10) {

        $options = [
            "maxResults" => $limit,
            "pageToken" => $page_token
        ];

        $result = $this->gmail->users_messages->listUsersMessages($this->user_id, $options);

        if ($messages = $result->getMessages()) {

            return [
                "messages" => $messages,
                "nextPageToken" => $result->getNextPageToken(),
                "resultSizeEstimate" => $result->getResultSizeEstimate()
            ];
        }
        return [];
    }

    public function getHistoryList($history_id, $page_token = NULL, $limit = 10) {

        $options = [
            "startHistoryId" => $history_id,
            "maxResults" => $limit,
            "pageToken" => $page_token,
            "historyTypes" => ["messageAdded"]
        ];

        $result = $this->gmail->users_history->listUsersHistory($this->user_id, $options);

        if ($messages = $result->getHistory()) {

            return [
                "messages" => $result->getHistory(),
                "nextPageToken" => $result->getNextPageToken(),
                "historyId" => $result->getHistoryId()
            ];
        }
        return [];
    }


    public function getHistoryId() {

        return $this->gmail->users->getProfile($this->user_id)->getHistoryId();
    }


    public function getMessage($message_id) {

        return $this->gmail->users_messages->get($this->user_id, $message_id);
    }


    public function getMeta($message_id) {

        $options = [
            "format" => "metadata",
            "metadataHeaders" => ["From",  "Date", "Subject"]
        ];

        return $this->gmail->users_messages->get($this->user_id, $message_id, $options);
    }


    //needs to be called on $page_history array before its altered
    public function paginator(array $page_history, $page_token) : array {

        //prev page
        //generating the previous link is a little tricky since page 2 has no token for page 1, but we need to generate a previous page link
        switch (true) {

            //1st page
            case count($page_history) === 0:

                $prev = NULL;
                break;

            //page 2
            case count($page_history) === 1:

                $prev = "first";    //placeholder for no page 1 token
                break;


            //page 3 through last
            default:

                $prev = implode(':', array_slice($page_history, 0, -1));
        }

        //next page
        //tack on the page_token to the existing history or null for no next page
        $next = $page_token ? trim(implode(':', $page_history) . ':' . $page_token, ':') : NULL;

        return ["prev" => $prev, "next" => $next];
    }


    /*public function parseMessage(IParser $parser, $raw_message) {


    }*/


    public function watch() {


    }


    public static function create(\Google_Client $gclient, $user_id = 'me') {


        return new static(new \Google_Service_Gmail($gclient), $user_id);

    }
}