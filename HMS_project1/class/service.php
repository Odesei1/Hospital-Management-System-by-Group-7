<?php
class Service {
    private $conn;
    private $table = "service";

    public function __construct($db){
        $this->conn = $db;
    }

    // ✅ Add new service
    public function add($SERV_NAME, $SERV_DESC){
        $sql = "INSERT INTO {$this->table} (SERV_NAME, SERV_DESC, SERV_CREATED_AT)
                VALUES (:SERV_NAME, :SERV_DESC, NOW())";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            ":SERV_NAME" => $SERV_NAME,
            ":SERV_DESC" => $SERV_DESC
        ]);
    }

    // ✅ View all services
    public function all(){
        $sql = "SELECT * FROM {$this->table} ORDER BY SERV_NAME ASC";
        $stmt = $this->conn->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ✅ View appointments by service
    public function getAppointmentsByService($SERV_ID){
        $sql = "SELECT a.APPT_ID, a.APPT_DATE, a.APPT_TIME, 
                       p.PAT_FNAME, p.PAT_LNAME, 
                       d.DOC_FNAME, d.DOC_LNAME, 
                       s.SERV_NAME, st.STAT_NAME
                FROM appointment a
                JOIN patient p ON a.PAT_ID = p.PAT_ID
                JOIN doctor d ON a.DOC_ID = d.DOC_ID
                JOIN service s ON a.SERV_ID = s.SERV_ID
                JOIN status st ON a.STAT_ID = st.STAT_ID
                WHERE s.SERV_ID = :SERV_ID
                ORDER BY a.APPT_DATE DESC, a.APPT_TIME DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([":SERV_ID" => $SERV_ID]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ✅ Update service
    public function update($SERV_ID, $SERV_NAME, $SERV_DESC){
        $sql = "UPDATE {$this->table}
                SET SERV_NAME = :SERV_NAME,
                    SERV_DESC = :SERV_DESC,
                    SERV_UPDATED_AT = NOW()
                WHERE SERV_ID = :SERV_ID";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            ":SERV_NAME" => $SERV_NAME,
            ":SERV_DESC" => $SERV_DESC,
            ":SERV_ID" => $SERV_ID
        ]);
    }

    // ✅ Delete service
    public function delete($SERV_ID){
        $sql = "DELETE FROM {$this->table} WHERE SERV_ID = :SERV_ID";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            ":SERV_ID" => $SERV_ID
        ]);
    }
}
?>
