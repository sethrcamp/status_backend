<?php

require_once __DIR__."/../config/db.php";

class UserStatus {
    public static function getAll () {
        $db = DB::getInstance();
        $statement = $db->prepare("SELECT * FROM users_status");
        $statement->execute();
        $user_statuses = $statement->fetchAll();
        return $user_statuses;
    }
    public static function getById ($id) {
        $db = DB::getInstance();
        $statement = $db->prepare("SELECT * FROM users_status WHERE user_id = ?");
        $statement->execute([$id]);
        $user_status = $statement->fetch();
        return $user_status;
    }
    public static function create ($data) {
        $db = DB::getInstance();
        $statement = $db->prepare("INSERT INTO users_status (user_id, status_id, default_status_id) VALUES (?,?,?)");
        $params = [
            $data['user_id'],
            $data['status_id'],
            $data['default_status_id']
        ];
        $statement->execute($params);
        $user_status = UserStatus::getById($data['user_id']);
        return $user_status;
    }

    public static function update($id, $data) {
        $db = DB::getInstance();
        $statement = $db->prepare("UPDATE users_status SET status_id = ?, default_status_id = ? WHERE user_id = ?");
        $params = [
            $data['status_id'],
            $data['default_status_id'],
            $id
        ];
        $statement->execute($params);
        return UserStatus::getById($id);
    }

    public static function delete ($id) {
        $user_status = UserStatus::getById($id);
        $db = DB::getInstance();
        $statement = $db->prepare("DELETE FROM users_status WHERE id = ?");
        $statement->execute([$id]);
        return $user_status;
    }
}