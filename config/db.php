<?php

/*
*  @brief: This will connect with database and return the connection object
*
*/
class DB {

    //properties
    private $dbHost = 'localhost';
    private $dbUser = 'root';
    private $dbPass = '';
    private $dbName = 'books';

    public function connect() {
        //Form connection string
        $connStr = "mysql:host=$this->dbHost;dbname=$this->dbName";
        //connect with db using PDO
        $dbConn = new PDO($connStr, $this->dbUser, $this->dbPass);
        //Set database attributes
        $dbConn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        return $dbConn;
    }

}