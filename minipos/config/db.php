<?php
/**
 * Configuración de la conexión a la base de datos
 */
class Database {
    private $host = 'localhost';
    private $db_name = 'minipos_db';
    private $username = 'root';
    private $password = '';
    private $conn;

    // Método para conectar a la base de datos
    public function connect() {
        $this->conn = null;

        try {
            $this->conn = new PDO(
                'mysql:host=' . $this->host . ';dbname=' . $this->db_name,
                $this->username,
                $this->password,
                array(
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'
                )
            );
        } catch(PDOException $e) {
            echo 'Error de conexión: ' . $e->getMessage();
        }

        return $this->conn;
    }
}