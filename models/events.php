<?php

require_once __DIR__."/../config/db.php";

class Events {
    public static function getAll () {
        $db = DB::getInstance();
        $statement = $db->prepare("SELECT * FROM events");
        $statement->execute();
        $events = $statement->fetchAll();
        return $events;
    }
    public static function getById ($id) {
        $db = DB::getInstance();
        $statement = $db->prepare("SELECT * FROM events WHERE id = ?");
        $statement->execute([$id]);
        $event = $statement->fetchAll();
        return $event;
    }

    public static function openEvent($id) {
        $db = DB::getInstance();
        $statement = $db->prepare("UPDATE events SET opened = 1 WHERE id = ?");
        $statement->execute([$id]);
        $event = $statement->fetch();
        return $event;
    }

    public static function closeEvent($id) {
        $db = DB::getInstance();
        $statement = $db->prepare("UPDATE events SET closed = 1 WHERE id = ?");
        $statement->execute([$id]);
        $event = $statement->fetch();
        return $event;
    }

    public static function getAllToOpen() {
        $db = DB::getInstance();
        $statement = $db->prepare("SELECT * FROM events WHERE start_time < unix_timestamp(now()) AND end_time > unix_timestamp(now()) AND opened = 0");
        $statement->execute();
        $event = $statement->fetchAll();
        return $event;
    }

    public static function getAllToClose() {
        $db = DB::getInstance();
        $statement = $db->prepare("SELECT * FROM events WHERE end_time < unix_timestamp(now()) AND closed = 0");
        $statement->execute();
        $event = $statement->fetchAll();
        return $event;
    }

    public static function create ($data) {
        $db = DB::getInstance();
        $statement = $db->prepare("INSERT INTO events (user_id, status_id, start_time, end_time, description, opened, closed) VALUES (?,?,?,?,?,?,?)");


        $params = [
            $data['user_id'],
            $data['status_id'],
            $data['start_time'],
            $data['end_time'],
            $data['description'],
            0,
            0
        ];

        $statement->execute($params);

        $events = Events::getById($db->lastInsertId());
        return $events;
    }
    public static function delete ($id) {
        $event = Events::getById($id);
        $db = DB::getInstance();
        $statement = $db->prepare("DELETE FROM events WHERE id = ?");
        $statement->execute([$id]);
        return $event;
    }
}