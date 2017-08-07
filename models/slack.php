<?php


    class Slack {

        private $client;
      
//        private $auth;

      
        function __construct() {
            $this->client = new GuzzleHttp\Client(['base_uri' => 'http://localhost:8080']);

            $this->apiPath = '/status_slackbot';
//            $this->auth = "xoxb-84119933168-y36eTU2g5izK0Kn8H8SLo0qZ";
        }

        function sendMessage($message, $toUser) {
          try {
            $response = $this->client->request('POST', $this->apiPath . '/message', [
              'json' => [
                'user' => $toUser,
                'message' => $message
              ]
            ]);
            return $response;
          } catch (Exception $e) {
            //do nothing with the exception
            return;
          }
        }
    }