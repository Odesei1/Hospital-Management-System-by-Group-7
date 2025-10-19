<?php
     class Database {
          private $host = "localhost";
          private $dbname = "hospital_db";
          private $username = "root";
          private $password = "";
          private $conn;

          public function connect() {
               if ($this->conn == null) {
                    try {
                         $this->conn = new PDO("mysql:host={$this->host};dbname={$this->dbname}",
                                        $this->username, $this->password);
                         $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                         echo "Connected Successfully";
                    }catch(PDOException $e) {
                         echo "Connected failed: " . $e->getMessage();
                    }
               }

               return $this->conn;
          }
     }
 ?>
