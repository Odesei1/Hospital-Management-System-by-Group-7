<?php
class Status {
    private $conn;
    private $table = "status";

    public function __construct($db){
        $this->conn = $db;
    }

    // ✅ Add new status
    public function add($STAT_NAME){
        $sql = "INSERT INTO {$this->table} (STAT_NAME, STAT_CREATED_AT)
                VALUES (:STAT_NAME, NOW())";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            ":STAT_NAME" => $STAT_NAME
        ]);
    }

    // ✅ View all status
    public function all(){
        $sql = "SELECT * FROM {$this->table} ORDER BY STAT_NAME ASC";
        $stmt = $this->conn->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ✅ Update status
    public function update($STAT_ID, $STAT_NAME){
        $sql = "UPDATE {$this->table}
                SET STAT_NAME = :STAT_NAME,
                    STAT_UPDATED_AT = NOW()
                WHERE STAT_ID = :STAT_ID";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            ":STAT_NAME" => $STAT_NAME,
            ":STAT_ID" => $STAT_ID
        ]);
    }

    // ✅ Delete status
    public function delete($STAT_ID){
        $sql = "DELETE FROM {$this->table} WHERE STAT_ID = :STAT_ID";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            ":STAT_ID" => $STAT_ID
        ]);
    }
}
?>
