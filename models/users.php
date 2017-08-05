<?php

require_once __DIR__."/../config/db.php";


class Users {
    public static function getAll () {
        $db = DB::getInstance();
        $statement = $db->prepare("SELECT * FROM users");
        $statement->execute();
        $users = $statement->fetchAll();
        return $users;
    }
    public static function getById ($id) {
        $db = DB::getInstance();
        $statement = $db->prepare("SELECT * FROM users WHERE id = ?");
        $statement->execute([$id]);
        $user = $statement->fetch();
        return $user;
    }
    public static function getByName ($name) {
        $db = DB::getInstance();
        $statement = $db->prepare("SELECT * FROM users WHERE slack_handle = ?");
        $statement->execute([$name]);
        $user = $statement->fetch();
        return $user;
    }
    public static function create ($data) {
        $db = DB::getInstance();
        $statement = $db->prepare("INSERT INTO users (user_id, status_id, start_time, end_time, description, opened, closed) VALUES (?,?,?,?,?,?,?)");
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
        $users = Users::getById($db->lastInsertId());
        return $users;
    }
    public static function delete ($id) {
        $user = Users::getById($id);
        $db = DB::getInstance();
        $statement = $db->prepare("DELETE FROM users WHERE id = ?");
        $statement->execute([$id]);
        return $user;
    }
}