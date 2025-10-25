<?php
class Patient {
    private $conn;
    private $table = "patient";

    public function __construct($db) {
        $this->conn = $db;
    }

    // Add new patient
    public function add($fname, $midInit, $lname, $dob, $gender, $contact, $email, $address) {
        $stmt = $this->conn->prepare("INSERT INTO {$this->table} 
            (pat_first_name, pat_middle_init, pat_last_name, pat_dob, pat_gender, pat_contact_num, pat_email, pat_address, pat_created_at) 
            VALUES (:fname, :midInit, :lname, :dob, :gender, :contact, :email, :address, NOW())");
        return $stmt->execute([
            ':fname' => $fname,
            ':midInit' => $midInit,
            ':lname' => $lname,
            ':dob' => $dob,
            ':gender' => $gender,
            ':contact' => $contact,
            ':email' => $email,
            ':address' => $address
        ]);
    }

    // Get last inserted ID
    public function getLastInsertId() {
        return $this->conn->lastInsertId();
    }

    // Search patient by first or last name
    public function searchByName($keyword) {
        $sql = "SELECT * FROM {$this->table} 
                WHERE pat_first_name LIKE :keyword OR pat_last_name LIKE :keyword
                ORDER BY pat_last_name ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([":keyword" => "%$keyword%"]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // View all patients
    public function viewAll() {
        $sql = "SELECT * FROM {$this->table} ORDER BY pat_created_at DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Delete patient by ID
    public function delete($PAT_ID) {
        $sql = "DELETE FROM {$this->table} WHERE pat_id = :PAT_ID";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([":PAT_ID" => $PAT_ID]);
    }

    // Update patient
    public function update($PAT_ID, $PAT_FNAME, $PAT_MID_INIT, $PAT_LNAME, $PAT_DOB, $PAT_GENDER, $PAT_CONTACT, $PAT_EMAIL, $PAT_ADDRESS) {
        $sql = "UPDATE {$this->table}
                SET pat_first_name = :PAT_FNAME,
                    pat_middle_init = :PAT_MID_INIT,
                    pat_last_name = :PAT_LNAME,
                    pat_dob = :PAT_DOB,
                    pat_gender = :PAT_GENDER,
                    pat_contact_num = :PAT_CONTACT,
                    pat_email = :PAT_EMAIL,
                    pat_address = :PAT_ADDRESS,
                    pat_updated_at = NOW()
                WHERE pat_id = :PAT_ID";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            ":PAT_FNAME" => $PAT_FNAME,
            ":PAT_MID_INIT" => $PAT_MID_INIT,
            ":PAT_LNAME" => $PAT_LNAME,
            ":PAT_DOB" => $PAT_DOB,
            ":PAT_GENDER" => $PAT_GENDER,
            ":PAT_CONTACT" => $PAT_CONTACT,
            ":PAT_EMAIL" => $PAT_EMAIL,
            ":PAT_ADDRESS" => $PAT_ADDRESS,
            ":PAT_ID" => $PAT_ID
        ]);
    }
}
?>
