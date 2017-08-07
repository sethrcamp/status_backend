<?php
require_once __DIR__."/../config/db.php";


class Notifications {
    public static function getAll () {
        $db = DB::getInstance();
        $statement = $db->prepare("SELECT * FROM notifications");
        $statement->execute();
        $notifications = $statement->fetchAll();
        return $notifications;
    }
    public static function getById ($id) {
        $db = DB::getInstance();
        $statement = $db->prepare("SELECT * FROM notifications WHERE id = ?");
        $statement->execute([$id]);
        $notification = $statement->fetch();
        return $notification;
    }
    public static function getAllApplicable($user_id) {
        $db = DB::getInstance();
        $statement = $db->prepare("
            SELECT users.* 
            FROM users 
            INNER JOIN notifications
            ON users.id = notifications.to_user_id
            WHERE notifications.from_user_id = ?
            AND notifications.start_time < unix_timestamp(now())
            AND notifications.end_time > unix_timestamp(now())
        ");
        $statement->execute([$user_id]);
        $notifications = $statement->fetchAll();
        return $notifications;
    }
    public static function create ($data) {
        $db = DB::getInstance();


        $statement = $db->prepare("INSERT INTO notifications (to_user_id, from_user_id, start_time, end_time, both_users_free) VALUES (?,?,?,?,?)");

        $params = [
            $data['to_user_id'],
            $data['from_user_id'],
            $data['start_time'],
            $data['end_time'],
            $data['both_users_free']
        ];
        $statement->execute($params);

        $notifications = Notifications::getById($db->lastInsertId());
        return $notifications;
    }

    public static function deleteAllApplicable($user_id) {
        $db = DB::getInstance();
        $statement = $db->prepare("
            DELETE FROM notifications
            WHERE from_user_id = ?
            AND start_time < unix_timestamp(now())
            AND end_time > unix_timestamp(now())
        ");
        $statement->execute([$user_id]);
    }

    public static function delete ($id) {
        $notification = Notifications::getById($id);
        $db = DB::getInstance();
        $statement = $db->prepare("DELETE FROM notifications WHERE id = ?");
        $statement->execute([$id]);
        return $notification;
    }
}