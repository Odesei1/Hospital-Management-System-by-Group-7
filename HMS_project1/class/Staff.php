<?php
class Patient {
    private $conn;
    private $table_name = "patient";

    // Table columns as properties
    public $PAT_ID;
    public $PAT_FIRSTNAME;
    public $PAT_LASTNAME;
    public $PAT_EMAIL;
    public $PAT_CONTACT;
    public $PAT_DOB;
    public $PAT_GENDER;
    public $PAT_ADDRESS;

    public function __construct($db) {
        $this->conn = $db;
    }

    // CREATE
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                  (PAT_FIRSTNAME, PAT_LASTNAME, PAT_EMAIL, PAT_CONTACT, PAT_DOB, PAT_GENDER, PAT_ADDRESS)
                  VALUES (:PAT_FIRSTNAME, :PAT_LASTNAME, :PAT_EMAIL, :PAT_CONTACT, :PAT_DOB, :PAT_GENDER, :PAT_ADDRESS)";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":PAT_FIRSTNAME", $this->PAT_FIRSTNAME);
        $stmt->bindParam(":PAT_LASTNAME", $this->PAT_LASTNAME);
        $stmt->bindParam(":PAT_EMAIL", $this->PAT_EMAIL);
        $stmt->bindParam(":PAT_CONTACT", $this->PAT_CONTACT);
        $stmt->bindParam(":PAT_DOB", $this->PAT_DOB);
        $stmt->bindParam(":PAT_GENDER", $this->PAT_GENDER);
        $stmt->bindParam(":PAT_ADDRESS", $this->PAT_ADDRESS);

        return $stmt->execute();
    }

    // READ ALL
    public function read() {
        $query = "SELECT * FROM " . $this->table_name . " ORDER BY PAT_ID DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    // UPDATE
    public function update() {
        $query = "UPDATE " . $this->table_name . "
                  SET PAT_FIRSTNAME = :PAT_FIRSTNAME,
                      PAT_LASTNAME = :PAT_LASTNAME,
                      PAT_EMAIL = :PAT_EMAIL,
                      PAT_CONTACT = :PAT_CONTACT,
                      PAT_DOB = :PAT_DOB,
                      PAT_GENDER = :PAT_GENDER,
                      PAT_ADDRESS = :PAT_ADDRESS
                  WHERE PAT_ID = :PAT_ID";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":PAT_FIRSTNAME", $this->PAT_FIRSTNAME);
        $stmt->bindParam(":PAT_LASTNAME", $this->PAT_LASTNAME);
        $stmt->bindParam(":PAT_EMAIL", $this->PAT_EMAIL);
        $stmt->bindParam(":PAT_CONTACT", $this->PAT_CONTACT);
        $stmt->bindParam(":PAT_DOB", $this->PAT_DOB);
        $stmt->bindParam(":PAT_GENDER", $this->PAT_GENDER);
        $stmt->bindParam(":PAT_ADDRESS", $this->PAT_ADDRESS);
        $stmt->bindParam(":PAT_ID", $this->PAT_ID);

        return $stmt->execute();
    }

    // DELETE
    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE PAT_ID = :PAT_ID";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":PAT_ID", $this->PAT_ID);
        return $stmt->execute();
    }
}
?>
