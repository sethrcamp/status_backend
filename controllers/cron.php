<?php

class CronController {
    public static function runCron($request, $response, $args) {

        $eventsToOpen = Events::getAllToOpen();

        foreach ($eventsToOpen as $event) {
            $user = Users::getById($event['user_id']);
            $user_status = UserStatus::getById($user['id']);

            $data = [
                "status_id" => $event['status_id'],
                "default_status_id" => $user_status['status_id']
            ];

            $updated_user_status = UserStatus::update($user['id'], $data);
            $status = Status::getById($updated_user_status['status_id']);

            $slack = new Slack();
            $message = "Your status has been updated to `".$status['status']."` for the event: ".$event['description']."!";
            $slack->sendMessage($message, $user['slack_handle']);

            Events::openEvent($event['id']);
        }


        $eventsToClose = Events::getAllToClose();

        foreach ($eventsToClose as $event) {
            $user = Users::getById($event['user_id']);
            $user_status = UserStatus::getById($user['id']);

            $data = [
                "status_id" => $event['status_id'],
                "default_status_id" => $user_status['status_id']
            ];

            $updated_user_status = UserStatus::update($user['id'], $data);
            $status = Status::getById($updated_user_status['status_id']);



            $slack = new Slack();
            $message = "Your status has been updated to `".$status['status']."` because the event '".$event['description']."' has ended'!";
            $slack->sendMessage($message, $user['slack_handle']);

            die("doubt it");

            Events::closeEvent($event['id']);
        }
        return $response->withJson(["error" => "no"]);
    }
}