<?php
// ================================
// Fichier : includes/env_loader.php
// Rôle    : Chargement des variables d'environnement
// Auteur  : SAN Digital Solutions
// ================================

/**
 * Classe simple pour charger les variables d'environnement depuis un fichier .env
 */
class EnvLoader {
    /**
     * Charge les variables d'environnement depuis un fichier .env
     * @param string $path Chemin vers le fichier .env
     */
    public static function load($path) {
        if (!file_exists($path)) {
            throw new Exception("Le fichier .env n'existe pas: $path");
        }
        
        // Lire le fichier ligne par ligne
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        foreach ($lines as $line) {
            // Ignorer les commentaires
            if (strpos(trim($line), '#') === 0) {
                continue;
            }
            
            // Analyser la ligne pour extraire nom=valeur
            if (strpos($line, '=') !== false) {
                list($name, $value) = explode('=', $line, 2);
                $name = trim($name);
                $value = trim($value);
                
                // Supprimer les guillemets si présents
                if (strpos($value, '"') === 0 && strrpos($value, '"') === strlen($value) - 1) {
                    $value = substr($value, 1, -1);
                }
                
                // Définir la variable d'environnement
                putenv("$name=$value");
                $_ENV[$name] = $value;
                $_SERVER[$name] = $value;
            }
        }
    }
    
    /**
     * Récupère une variable d'environnement avec une valeur par défaut
     * @param string $key     Nom de la variable
     * @param mixed  $default Valeur par défaut si la variable n'existe pas
     * @return mixed Valeur de la variable ou valeur par défaut
     */
    public static function get($key, $default = null) {
        return isset($_ENV[$key]) ? $_ENV[$key] : (getenv($key) ?: $default);
    }
}
?>
