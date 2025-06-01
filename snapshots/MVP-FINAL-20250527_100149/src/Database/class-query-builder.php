<?php
/**
 * Gestion des requêtes SQL sécurisées
 *
 * @package CalendrierRdv\Database
 */

namespace CalendrierRdv\Database;

/**
 * Classe utilitaire pour construire et exécuter des requêtes SQL sécurisées
 */
class QueryBuilder {
    /**
     * Instance de la base de données WordPress
     *
     * @var \wpdb
     */
    private $wpdb;

    /**
     * Nom de la table (sans préfixe)
     *
     * @var string
     */
    private $table;

    /**
     * Préfixe des tables
     *
     * @var string
     */
    private $prefix;

    /**
     * Requête SQL en cours de construction
     *
     * @var string
     */
    private $query = '';

    /**
     * Paramètres pour la requête préparée
     *
     * @var array
     */
    private $params = [];

    /**
     * Types des paramètres pour la requête préparée
     *
     * @var array
     */
    private $param_types = [];

    /**
     * Constructeur
     *
     * @param string $table Nom de la table (sans préfixe)
     */
    public function __construct($table) {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table = $table;
        $this->prefix = $wpdb->prefix . 'cal_rdv_';
    }

    /**
     * Débute une requête SELECT
     *
     * @param array $columns Colonnes à sélectionner
     * @return self
     */
    public function select($columns = ['*']) {
        $columns = is_array($columns) ? $columns : [$columns];
        $this->query = 'SELECT ' . implode(', ', $columns) . ' FROM ' . $this->prefix . $this->table;
        return $this;
    }

    /**
     * Ajoute une clause WHERE
     *
     * @param string $column Colonne
     * @param mixed $value Valeur
     * @param string $operator Opérateur (=, <, >, LIKE, etc.)
     * @return self
     */
    public function where($column, $value, $operator = '=') {
        $placeholder = $this->addParam($value);
        
        if (strpos($this->query, 'WHERE') === false) {
            $this->query .= ' WHERE ';
        } else {
            $this->query .= ' AND ';
        }
        
        $this->query .= "$column $operator $placeholder";
        return $this;
    }

    /**
     * Ajoute une clause OR WHERE
     *
     * @param string $column Colonne
     * @param mixed $value Valeur
     * @param string $operator Opérateur
     * @return self
     */
    public function orWhere($column, $value, $operator = '=') {
        $placeholder = $this->addParam($value);
        
        if (strpos($this->query, 'WHERE') === false) {
            $this->query .= ' WHERE ';
        } else {
            $this->query .= ' OR ';
        }
        
        $this->query .= "$column $operator $placeholder";
        return $this;
    }

    /**
     * Ajoute une clause ORDER BY
     *
     * @param string $column Colonne
     * @param string $direction Direction (ASC ou DESC)
     * @return self
     */
    public function orderBy($column, $direction = 'ASC') {
        $direction = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';
        $this->query .= " ORDER BY $column $direction";
        return $this;
    }

    /**
     * Ajoute une clause LIMIT
     *
     * @param int $limit Nombre maximum de résultats
     * @param int $offset Décalage
     * @return self
     */
    public function limit($limit, $offset = 0) {
        $limit = max(0, (int)$limit);
        $offset = max(0, (int)$offset);
        
        $this->query .= $wpdb->prepare(" LIMIT %d, %d", $offset, $limit);
        return $this;
    }

    /**
     * Exécute la requête et retourne les résultats
     *
     * @param string $output_type Type de sortie (OBJECT, ARRAY_A, ARRAY_N)
     * @return array|object|null
     */
    public function get($output_type = OBJECT) {
        $query = $this->prepareQuery();
        return $this->wpdb->get_results($query, $output_type);
    }

    /**
     * Exécute la requête et retourne la première ligne
     *
     * @param string $output_type Type de sortie (OBJECT, ARRAY_A, ARRAY_N)
     * @return object|array|null
     */
    public function first($output_type = OBJECT) {
        $query = $this->prepareQuery();
        return $this->wpdb->get_row($query, $output_type);
    }

    /**
     * Insère une nouvelle ligne
     *
     * @param array $data Données à insérer
     * @return int|false L'ID de la nouvelle ligne ou false en cas d'échec
     */
    public function insert($data) {
        $data = $this->sanitizeData($data);
        $result = $this->wpdb->insert(
            $this->prefix . $this->table,
            $data['data'],
            $data['formats']
        );
        
        return $result ? $this->wpdb->insert_id : false;
    }

    /**
     * Met à jour des lignes
     *
     * @param array $data Données à mettre à jour
     * @param array $where Conditions WHERE
     * @return int|false Nombre de lignes mises à jour ou false en cas d'échec
     */
    public function update($data, $where) {
        $data = $this->sanitizeData($data);
        $where = $this->sanitizeData($where);
        
        return $this->wpdb->update(
            $this->prefix . $this->table,
            $data['data'],
            $where['data'],
            $data['formats'],
            $where['formats']
        );
    }

    /**
     * Supprime des lignes
     *
     * @param array $where Conditions WHERE
     * @return int|false Nombre de lignes supprimées ou false en cas d'échec
     */
    public function delete($where) {
        $where = $this->sanitizeData($where);
        
        return $this->wpdb->delete(
            $this->prefix . $this->table,
            $where['data'],
            $where['formats']
        );
    }

    /**
     * Compte le nombre de lignes correspondant aux conditions
     *
     * @return int
     */
    public function count() {
        $query = str_replace('SELECT *', 'SELECT COUNT(*)', $this->query);
        return (int)$this->wpdb->get_var($this->prepareQuery($query));
    }

    /**
     * Ajoute un paramètre à la requête préparée
     *
     * @param mixed $value Valeur du paramètre
     * @return string Placeholder pour la requête préparée
     */
    private function addParam($value) {
        $this->params[] = $value;
        
        // Détermine le type du paramètre
        if (is_int($value)) {
            $this->param_types[] = '%d';
        } elseif (is_float($value)) {
            $this->param_types[] = '%f';
        } else {
            $this->param_types[] = '%s';
        }
        
        return end($this->param_types);
    }

    /**
     * Prépare la requête SQL avec les paramètres
     *
     * @param string $query Requête SQL (optionnel)
     * @return string Requête préparée
     */
    private function prepareQuery($query = null) {
        $query = $query ?: $this->query;
        
        if (!empty($this->params)) {
            return $this->wpdb->prepare($query, $this->params);
        }
        
        return $query;
    }

    /**
     * Nettoie et formate les données pour les requêtes
     *
     * @param array $data Données à nettoyer
     * @return array Données nettoyées avec leurs formats
     */
    private function sanitizeData($data) {
        $sanitized = [
            'data' => [],
            'formats' => []
        ];
        
        foreach ($data as $key => $value) {
            // Échappe les noms de colonnes
            $column = preg_replace('/[^a-zA-Z0-9_]/', '', $key);
            
            // Nettoie la valeur en fonction de son type
            if (is_int($value)) {
                $sanitized['data'][$column] = (int)$value;
                $sanitized['formats'][] = '%d';
            } elseif (is_float($value)) {
                $sanitized['data'][$column] = (float)$value;
                $sanitized['formats'][] = '%f';
            } elseif (is_bool($value)) {
                $sanitized['data'][$column] = $value ? 1 : 0;
                $sanitized['formats'][] = '%d';
            } elseif (is_null($value)) {
                $sanitized['data'][$column] = null;
                $sanitized['formats'][] = '%s';
            } elseif (is_array($value) || is_object($value)) {
                $sanitized['data'][$column] = maybe_serialize($value);
                $sanitized['formats'][] = '%s';
            } else {
                $sanitized['data'][$column] = sanitize_text_field($value);
                $sanitized['formats'][] = '%s';
            }
        }
        
        return $sanitized;
    }

    /**
     * Retourne la dernière requête exécutée
     *
     * @return string
     */
    public function getLastQuery() {
        return $this->wpdb->last_query;
    }

    /**
     * Retourne la dernière erreur SQL
     *
     * @return string
     */
    public function getLastError() {
        return $this->wpdb->last_error;
    }

    /**
     * Exécute une requête SQL brute
     *
     * @param string $sql Requête SQL
     * @param array $params Paramètres pour la requête préparée
     * @return array|object|null
     */
    public static function raw($sql, $params = []) {
        global $wpdb;
        
        if (!empty($params)) {
            $sql = $wpdb->prepare($sql, $params);
        }
        
        return $wpdb->get_results($sql);
    }
}
