<?php
class payment_method{
    private $conn;
    private $table = "payment_method";

public function __construct($db){
    $this->conn = $db;
}
public function add($PYMT_METH_NAME){
    $sql = "INSERT INTO{$this->table} (PYMT_METH_NAME, PYM_METH_CREATED_AT) 
            VALUES (:PYMT_METH_NAME, NOW())";
    $stmt = $this->conn->prepare($sql);
    return $stmt->execute([":PYMT_METH_NAME" => $PYMT_METH_NAME]);
}

    // ✅ 2. Edit payment method (Get details of one method for editing)
    public function edit($PYMT_METH_ID){
        $sql = "SELECT * FROM {$this->table} WHERE PYMT_METH_ID = :PYMT_METH_ID";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([":PYMT_METH_ID" => $PYMT_METH_ID]);
        return $stmt->fetch(PDO::FETCH_ASSOC); // returns one row
    }

    // ✅ 3. Update payment method
    public function update($PYMT_METH_ID, $PYMT_METH_NAME){
        $sql = "UPDATE {$this->table} 
                SET PYMT_METH_NAME = :PYMT_METH_NAME, 
                    PYMT_METH_UPDATED_AT = NOW()
                WHERE PYMT_METH_ID = :PYMT_METH_ID";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            ":PYMT_METH_NAME" => $PYMT_METH_NAME,
            ":PYMT_METH_ID" => $PYMT_METH_ID
        ]);
    }

    // ✅ 4. Delete payment method
    public function delete($PYMT_METH_ID){
        $sql = "DELETE FROM {$this->table} WHERE PYMT_METH_ID = :PYMT_METH_ID";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            ":PYMT_METH_ID" => $PYMT_METH_ID
        ]);
    }

    // ✅ 5. View all payment methods
    public function viewAll(){
        $sql = "SELECT * FROM {$this->table} ORDER BY PYMT_METH_ID ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC); // returns all rows
    }
}
?>