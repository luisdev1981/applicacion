<?php
// sistema-inces/configuraciones/bd.php

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'inces_db');
define('DB_CHARSET', 'utf8mb4');

class Database {
    private static $instancia = null;
    private $pdo;

    private function __construct() {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        $opciones = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $this->pdo = new PDO($dsn, DB_USER, DB_PASS, $opciones);
        } catch (PDOException $e) {
            // En un entorno de producción, no mostrarías el error detallado.
            // Lo registrarías en un archivo de log.
            error_log('Error de conexión a la base de datos: ' . $e->getMessage());
            die('Error: No se pudo conectar a la base de datos. Por favor, contacte al administrador.');
        }
    }

    public static function obtenerInstancia() {
        if (self::$instancia === null) {
            self::$instancia = new Database();
        }
        return self::$instancia;
    }

    public function obtenerConexion() {
        return $this->pdo;
    }

    // Prevenir la clonación y deserialización de la instancia (patrón Singleton)
    private function __clone() { }
    public function __wakeup() { }
}

// Función global para acceder fácilmente a la conexión
function getDbConexion() {
    return Database::obtenerInstancia()->obtenerConexion();
}

?>
