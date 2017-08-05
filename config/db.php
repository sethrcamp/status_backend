<?php
class DB {
    protected function __construct(){}

    public static function getInstance(){
        global $CONFIG;

        $host = $CONFIG["server_name"];
        $db   = $CONFIG["server_db"];
        $user = $CONFIG["server_user"];
        $pass = $CONFIG["server_pass"];
        $charset = 'utf8';

        $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
        $opt = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false
        ];
        $pdo = new PDO($dsn, $user, $pass, $opt);
        return $pdo;
    }

}