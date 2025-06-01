<?php
// ================================
// Fichier : includes/logger.php
// Rôle    : Gestion des logs applicatifs
// Auteur  : SAN Digital Solutions
// ================================

/**
 * Classe Logger
 * Gère l'enregistrement des logs applicatifs
 */
class Logger {
    // Niveaux de log
    const DEBUG = 'DEBUG';
    const INFO = 'INFO';
    const WARNING = 'WARNING';
    const ERROR = 'ERROR';
    
    // Chemin des fichiers de logs
    private $logPath;
    
    // Instance unique (pattern Singleton)
    private static $instance = null;
    
    /**
     * Constructeur privé (pattern Singleton)
     */
    private function __construct() {
        $this->logPath = dirname(__DIR__) . '/logs/';
        
        // Création du dossier logs s'il n'existe pas
        if (!file_exists($this->logPath)) {
            mkdir($this->logPath, 0755, true);
        }
        
        // Création d'un fichier .htaccess pour protéger les logs
        $htaccessPath = $this->logPath . '.htaccess';
        if (!file_exists($htaccessPath)) {
            file_put_contents($htaccessPath, "Order Deny,Allow\nDeny from all");
        }
    }
    
    /**
     * Récupère l'instance unique de Logger
     * @return Logger Instance unique
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Écrit un message dans le fichier de log approprié
     * @param string $level   Niveau de log (DEBUG, INFO, WARNING, ERROR)
     * @param string $message Message à logger
     * @param array  $context Contexte supplémentaire (optionnel)
     */
    public function log($level, $message, $context = []) {
        // Format du fichier : app-YYYY-MM-DD.log
        $date = date('Y-m-d');
        $logFile = $this->logPath . "app-{$date}.log";
        
        // Heure actuelle
        $time = date('H:i:s');
        
        // ID utilisateur si disponible
        $userId = isset($_SESSION['admin_id']) ? $_SESSION['admin_id'] : 'guest';
        
        // Adresse IP
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        
        // URL actuelle
        $url = $_SERVER['REQUEST_URI'] ?? 'unknown';
        
        // Formatage du contexte
        $contextStr = '';
        if (!empty($context)) {
            $contextStr = ' | ' . json_encode($context, JSON_UNESCAPED_UNICODE);
        }
        
        // Construction de la ligne de log
        $logLine = sprintf(
            "[%1$s] [%2$s] [%3$s] [%4$s] [%5$s] %6$s%7$s\n",
            $date,
            $time,
            $level,
            $userId,
            $ip,
            $message,
            $contextStr
        );
        
        // Écriture dans le fichier
        file_put_contents($logFile, $logLine, FILE_APPEND);
    }
    
    /**
     * Raccourci pour log niveau DEBUG
     */
    public function debug($message, $context = []) {
        $this->log(self::DEBUG, $message, $context);
    }
    
    /**
     * Raccourci pour log niveau INFO
     */
    public function info($message, $context = []) {
        $this->log(self::INFO, $message, $context);
    }
    
    /**
     * Raccourci pour log niveau WARNING
     */
    public function warning($message, $context = []) {
        $this->log(self::WARNING, $message, $context);
    }
    
    /**
     * Raccourci pour log niveau ERROR
     */
    public function error($message, $context = []) {
        $this->log(self::ERROR, $message, $context);
    }
    
    /**
     * Nettoie les anciens logs (plus de 30 jours)
     */
    public function cleanOldLogs() {
        $files = glob($this->logPath . 'app-*.log');
        $now = time();
        $maxAge = 30 * 24 * 60 * 60; // 30 jours
        
        foreach ($files as $file) {
            if (is_file($file)) {
                if ($now - filemtime($file) > $maxAge) {
                    unlink($file);
                }
            }
        }
    }
}
?>
