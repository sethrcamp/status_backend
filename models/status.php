<?php

require_once __DIR__."/../config/db.php";

class Status {
    public static function getAll () {
        $db = DB::getInstance();
        $statement = $db->prepare("SELECT * FROM status");
        $statement->execute();
        $statuses = $statement->fetchAll();
        return $statuses;
    }
    public static function getById ($id) {
        $db = DB::getInstance();
        $statement = $db->prepare("SELECT * FROM status WHERE id = ?");
        $statement->execute([$id]);
        $status = $statement->fetch();
        return $status;
    }
    public static function getByName ($id) {
        $db = DB::getInstance();
        $statement = $db->prepare("SELECT * FROM status WHERE status = ?");
        $statement->execute([$id]);
        $status = $statement->fetchAll();
        return $status;
    }
    public static function create ($data) {
        $db = DB::getInstance();
        $statement = $db->prepare("INSERT INTO status (user_id, status_id, start_time, end_time, description, opened, closed) VALUES (?,?,?,?,?,?,?)");
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
        $statuses = Status::getById($db->lastInsertId());
        return $statuses;
    }

    public static function delete ($id) {
        $status = Status::getById($id);
        $db = DB::getInstance();
        $statement = $db->prepare("DELETE FROM status WHERE id = ?");
        $statement->execute([$id]);
        return $status;
    }
}