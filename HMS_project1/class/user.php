<?php
class User {
    private $conn;
    private $table = "user";

    public function __construct($db) {
        $this->conn = $db;
    }

    // ✅ Create new user (default: not superadmin)
    public function create($username, $password, $pat_id = null, $staff_id = null, $doc_id = null, $is_superadmin = false) {
        $sql = "INSERT INTO {$this->table} 
                (user_name, user_password, user_is_superadmin, user_created_at, pat_id, staff_id, doc_id)
                VALUES (:user_name, :user_password, :is_superadmin, NOW(), :pat_id, :staff_id, :doc_id)";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            ":user_name"      => $username,
            ":user_password"  => $password, // ❗plain password (no hashing)
            ":is_superadmin"  => $is_superadmin ? 1 : 0,
            ":pat_id"         => $pat_id,
            ":staff_id"       => $staff_id,
            ":doc_id"         => $doc_id
        ]);
    }

    // ✅ View one user by ID
    public function view($user_id) {
        $sql = "SELECT * FROM {$this->table} WHERE user_id = :user_id";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':user_id' => $user_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // ✅ View all users
    public function viewAll() {
        $sql = "SELECT * FROM {$this->table}";
        $stmt = $this->conn->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ✅ View all doctor users
    public function viewDoctors() {
        $sql = "SELECT * FROM {$this->table} WHERE doc_id IS NOT NULL AND pat_id IS NULL AND staff_id IS NULL";
        $stmt = $this->conn->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ✅ View all patient users
    public function viewPatients() {
        $sql = "SELECT * FROM {$this->table} WHERE pat_id IS NOT NULL AND doc_id IS NULL AND staff_id IS NULL";
        $stmt = $this->conn->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ✅ View all staff users
    public function viewStaff() {
        $sql = "SELECT * FROM {$this->table} WHERE staff_id IS NOT NULL AND pat_id IS NULL AND doc_id IS NULL";
        $stmt = $this->conn->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ✅ Identify user role (doctor, staff, patient, or superadmin)
    public function getRole($user) {
        if ($user['USER_IS_SUPERADMIN']) {
            return "Superadmin";
        } elseif (!empty($user['DOC_ID'])) {
            return "Doctor";
        } elseif (!empty($user['STAFF_ID'])) {
            return "Staff";
        } elseif (!empty($user['PAT_ID'])) {
            return "Patient";
        } else {
            return "Unknown";
        }
    }

public function updateLastLogin($user_id) {
    $sql = "UPDATE {$this->table} 
            SET user_last_login = NOW() 
            WHERE user_id = :user_id";
    $stmt = $this->conn->prepare($sql);
    $stmt->execute([':user_id' => $user_id]);
}

}
?>
