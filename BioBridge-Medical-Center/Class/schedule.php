<?php
class Schedule {
    private $conn;
    private $table = "schedule";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function add($doc_id, $days, $start, $end) {
        $sql = "INSERT INTO {$this->table} (doc_id, sched_days, sched_start_time, sched_end_time, sched_created_at)
                VALUES (:doc, :days, :start, :end, NOW())";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            ':doc' => $doc_id,
            ':days' => $days,
            ':start' => $start,
            ':end' => $end
        ]);
    }

    public function getByDoctor($doc_id) {
        $stmt = $this->conn->prepare("SELECT * FROM {$this->table} WHERE doc_id = :doc ORDER BY sched_created_at DESC");
        $stmt->execute([':doc' => $doc_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function delete($sched_id) {
        $stmt = $this->conn->prepare("DELETE FROM {$this->table} WHERE sched_id = :id");
        return $stmt->execute([':id' => $sched_id]);
    }
}
?>
