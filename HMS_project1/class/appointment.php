<?php
class Appointment {
    private $conn;
    private $table = "appointment";

    public function __construct($db){
        $this->conn = $db;
    }

    // ✅ Generate Appointment ID (e.g. 2025-01-0000001)
    private function generateAppointmentID() {
        $year = date('Y');
        $month = date('m');

        // Count existing appointments for the current year & month
        $sql = "SELECT COUNT(*) AS count 
                FROM {$this->table}
                WHERE APPT_ID LIKE :pattern";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([":pattern" => "$year-$month%"]);
        $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'] + 1;

        // Generate sequence (7 digits)
        $sequence = str_pad($count, 7, "0", STR_PAD_LEFT);

        return "$year-$month-$sequence";
    }

    // ✅ Create new appointment
    public function create($PAT_ID, $DOC_ID, $SERV_ID, $STAT_ID, $APPT_DATE, $APPT_TIME, $APPT_NOTES){
        $APPT_ID = $this->generateAppointmentID();

        $sql = "INSERT INTO {$this->table} 
                (APPT_ID, PAT_ID, DOC_ID, SERV_ID, STAT_ID, APPT_DATE, APPT_TIME, APPT_NOTES, APPT_CREATED_AT)
                VALUES (:APPT_ID, :PAT_ID, :DOC_ID, :SERV_ID, :STAT_ID, :APPT_DATE, :APPT_TIME, :APPT_NOTES, NOW())";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ":APPT_ID" => $APPT_ID,
            ":PAT_ID" => $PAT_ID,
            ":DOC_ID" => $DOC_ID,
            ":SERV_ID" => $SERV_ID,
            ":STAT_ID" => $STAT_ID,
            ":APPT_DATE" => $APPT_DATE,
            ":APPT_TIME" => $APPT_TIME,
            ":APPT_NOTES" => $APPT_NOTES
        ]);

        return $APPT_ID; // Return generated appointment ID for display
    }

    // ✅ Search appointment by ID
    public function findByID($APPT_ID){
        $sql = "SELECT a.*, 
                       p.PAT_FNAME, p.PAT_LNAME,
                       d.DOC_FNAME, d.DOC_LNAME,
                       s.SERV_NAME, st.STAT_NAME
                FROM {$this->table} a
                JOIN patient p ON a.PAT_ID = p.PAT_ID
                JOIN doctor d ON a.DOC_ID = d.DOC_ID
                JOIN service s ON a.SERV_ID = s.SERV_ID
                JOIN status st ON a.STAT_ID = st.STAT_ID
                WHERE a.APPT_ID = :APPT_ID";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([":APPT_ID" => $APPT_ID]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    // ✅ Update appointment details
    public function update($APPT_ID, $DOC_ID, $SERV_ID, $APPT_DATE, $APPT_TIME, $APPT_NOTES){
        $sql = "UPDATE {$this->table} 
                SET DOC_ID = :DOC_ID, 
                    SERV_ID = :SERV_ID,
                    APPT_DATE = :APPT_DATE,
                    APPT_TIME = :APPT_TIME,
                    APPT_NOTES = :APPT_NOTES,
                    APPT_UPDATED_AT = NOW()
                WHERE APPT_ID = :APPT_ID";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            ":DOC_ID" => $DOC_ID,
            ":SERV_ID" => $SERV_ID,
            ":APPT_DATE" => $APPT_DATE,
            ":APPT_TIME" => $APPT_TIME,
            ":APPT_NOTES" => $APPT_NOTES,
            ":APPT_ID" => $APPT_ID
        ]);
    }

    // ✅ Cancel appointment (optional: set status to “Cancelled”)
    public function cancel($APPT_ID){
        $sql = "UPDATE {$this->table} 
                SET STAT_ID = (SELECT STAT_ID FROM status WHERE STAT_NAME = 'Cancelled' LIMIT 1),
                    APPT_UPDATED_AT = NOW()
                WHERE APPT_ID = :APPT_ID";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([":APPT_ID" => $APPT_ID]);
    }

    // ✅ Update appointment status (Scheduled, Completed, Cancelled)
    public function updateStatus($APPT_ID, $STAT_ID){
        $sql = "UPDATE {$this->table}
                SET STAT_ID = :STAT_ID,
                    APPT_UPDATED_AT = NOW()
                WHERE APPT_ID = :APPT_ID";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            ":STAT_ID" => $STAT_ID,
            ":APPT_ID" => $APPT_ID
        ]);
    }
}
?>
