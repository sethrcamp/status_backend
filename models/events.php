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