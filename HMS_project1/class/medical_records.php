<?php
require_once __DIR__ . "/../config/database.php";

class MedicalRecords {
    private $conn;
    private $table = "medical_records";

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function addRecord($data){
        $sql = "INSERT INTO medical_record(med_rec_diagnosis, med_rec_prescription, med_rec_visit_date, appt_id) 
                VALUES(?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            $data['med_rec_diagnosis'],
            $data['med_rec_prescription'],
            $data['med_rec_visit_date'],
            $data['appt_id']
        ]);
    }

    public function getAllRecords(){
        $sql = "SELECT 
                   m.med_rec_id,
                   m.med_rec_diagnosis,
                   m.med_rec_prescription,
                   m.med_rec_visit_date,
                   a.appt_id,
                FROM medical_record
                LEFT JOIN appointment a ON m.appt_id = a.appt_id ";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt;
    }

    public function updateRecord($id, $data){
        $sql = "UPDATE medical_record
                SET
                    med_rec_diagnosis = ?,
                    med_rec_prescription = ?,
                    med_rec_visit_date = ?,
                    appt_id = ?
                WHERE med_rec_id = ?";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            $data['med_rec_diagnosis'],
            $data['med_rec_prescription'],
            $data['med_rec_visit_date'],
            $data['appt_id'],
            $id
        ]);
    }

    public function deleteRecord($id){
        $sql = "DELETE FROM medical_record WHERE med_rec_id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();           
    }
}
?>