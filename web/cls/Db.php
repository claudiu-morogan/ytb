<?php

class DataBase {
    protected $username;
    protected $password;
    protected $host;
    protected $database;
    protected $connection;

    public function __construct() {
        $this->username = $_ENV['db_user'];
        $this->password = $_ENV['db_password'];
        $this->host = $_ENV['db_host'];
        $this->database = $_ENV['db_name'];        

        $this->connection = $this->connect();
    }

    public function connect() {
        $conn = new mysqli($this->host, $this->username, $this->password, $this->database);
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }
        return $conn;
    }

    public function query($sql) {
        $result = $this->connection->query($sql);
        if ($result === false) {
            die("Error: " . $sql . "<br>" . $this->connection->error);
        }
        return $result;
    }

    public function execute($sql)
    {
        return $this->connection->query($sql);        
    }

    public function close() {
        $this->connection->close();
    }
}
