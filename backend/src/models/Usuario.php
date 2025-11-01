<?php
// No necesitamos incluir db.php aquí, lo haremos en el controlador.

class Usuario {
    
    private $conn; 
    private $table_name = "usuario";

    /**
     * 
     * 
     * @param PDO $db
     */
    public function __construct($db) {
        $this->conn = $db; 
    }

    /**
     * 
     * @param string $email 
     * @return array|false 
     */
    public function findByEmail($email) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE correo = :correo AND activo = 1 LIMIT 0,1";

        $stmt = $this->conn->prepare($query);

       
        $email = htmlspecialchars(strip_tags($email));

       
        $stmt->bindParam(':correo', $email);

        
        $stmt->execute();

       
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            return $row; 
        }

        return false;
    }
}
?>