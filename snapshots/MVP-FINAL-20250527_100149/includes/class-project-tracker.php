<?php
/**
 * Gestion avancée du suivi de projet
 * @package CalendrierRdv
 */

class Calendrier_RDV_Project_Tracker {
    private $tracking_file;
    private $log_file;
    private $stats_file;
    private $version = '2.0.0';

    public function __construct() {
        $upload_dir = wp_upload_dir();
        $this->tracking_file = plugin_dir_path(dirname(__FILE__)) . 'suivi-projet.md';
        $this->log_file = $upload_dir['basedir'] . '/calendrier-rdv/logs/project-tracker.log';
        $this->stats_file = $upload_dir['basedir'] . '/calendrier-rdv/stats/project-stats.json';
        
        $this->init_files();
        $this->init_hooks();
    }

    private function init_files() {
        // Créer les répertoires nécessaires
        $dirs = [
            dirname($this->log_file),
            dirname($this->stats_file)
        ];

        foreach ($dirs as $dir) {
            if (!file_exists($dir)) {
                wp_mkdir_p($dir);
            }
        }

        // Initialiser le fichier de suivi s'il n'existe pas
        if (!file_exists($this->tracking_file)) {
            $this->init_tracking_file();
        }
    }

    private function init_tracking_file() {
        $content = "# 📊 Tableau de Bord du Projet\n\n";
        $content .= "## 🔍 Aperçu\n";
        $content .= "- **Version du Plugin** : " . CALENDRIER_RDV_VERSION . "\n";
        $content .= "- **Dernière Mise à Jour** : " . date('Y-m-d H:i:s') . "\n\n";
        
        $content .= "## 📈 Métriques Clés\n";
        $content .= "- Tâches Complétées : 0%\n";
        $content .= "- Progression Globale : 0%\n";
        $content .= "- Prochaine Échéance : Non définie\n\n";

        $content .= "## 📝 Activités Récentes\n\n";
        $content .= "## ✅ Tâches en Cours\n\n";
        $content .= "## 📅 Calendrier des Livrables\n\n";
        $content .= "## 📊 Statistiques\n\n";
        $content .= "## 📋 Notes de Version\n\n";

        file_put_contents($this->tracking_file, $content);
    }

    private function init_hooks() {
        // Planification des tâches
        add_action('calendrier_rdv_daily_event', array($this, 'update_project_tracking'));
        add_action('admin_init', array($this, 'schedule_events'));
        
        // Hooks personnalisés pour le suivi
        add_action('calendrier_rdv_feature_added', array($this, 'track_feature_added'), 10, 2);
        add_action('calendrier_rdv_issue_resolved', array($this, 'track_issue_resolved'), 10, 2);
    }

    public function schedule_events() {
        if (!wp_next_scheduled('calendrier_rdv_daily_event')) {
            wp_schedule_event(time(), 'daily', 'calendrier_rdv_daily_event');
        }
    }

    public function update_project_tracking() {
        try {
            if (!file_exists($this->tracking_file)) {
                $this->init_tracking_file();
            }

            $content = file_get_contents($this->tracking_file);
            $today = date('Y-m-d H:i:s');
            
            // Mettre à jour les métriques
            $content = $this->update_metrics($content);
            
            // Mettre à jour les activités récentes
            $content = $this->update_recent_activities($content, 'Mise à jour automatique du tableau de bord');
            
            // Mettre à jour les statistiques
            $content = $this->update_statistics($content);
            
            // Mettre à jour la date de dernière modification
            $content = preg_replace(
                '/Dernière Mise à Jour : .*/',
                'Dernière Mise à Jour : ' . $today,
                $content
            );

            // Sauvegarder
            if (file_put_contents($this->tracking_file, $content) !== false) {
                $this->log_activity('Tableau de bord mis à jour avec succès');
                return true;
            } else {
                throw new Exception('Échec de l\'écriture du fichier de suivi');
            }
        } catch (Exception $e) {
            $this->log_error($e->getMessage());
            return false;
        }
    }

    private function update_metrics($content) {
        // Récupérer les statistiques des tâches
        $completed_tasks = $this->count_completed_tasks($content);
        $total_tasks = $this->count_total_tasks($content);
        $progress = $total_tasks > 0 ? round(($completed_tasks / $total_tasks) * 100) : 0;

        // Mettre à jour les métriques
        $metrics = [
            'Tâches Complétées' => "$progress%",
            'Progression Globale' => "$progress%",
            'Prochaine Échéance' => $this->get_next_deadline()
        ];

        foreach ($metrics as $key => $value) {
            $content = preg_replace(
                "/- \*\*$key\*\* : .*/",
                "- **$key** : $value",
                $content
            );
        }

        return $content;
    }

    private function update_recent_activities($content, $activity) {
        $new_entry = "- **" . date('Y-m-d H:i') . "** : " . $activity . "\n";
        
        // Garder seulement les 10 dernières activités
        $activities = explode("\n", $content);
        $activity_section = [];
        $found = false;
        $count = 0;
        
        foreach ($activities as $line) {
            if (strpos($line, '## 📝 Activités Récentes') !== false) {
                $found = true;
                $activity_section[] = $line;
                $activity_section[] = ''; // Ligne vide après le titre
                $activity_section[] = $new_entry;
                continue;
            }
            
            if ($found && $count < 10 && strpos($line, '- **') === 0) {
                $activity_section[] = $line;
                $count++;
            }
        }
        
        if ($found) {
            $content = preg_replace(
                '/## 📝 Activités Récentes[\s\S]*?(?=## ✅|$)/',
                implode("\n", $activity_section),
                $content
            );
        }
        
        return $content;
    }

    private function update_statistics($content) {
        $stats = [
            'total_features' => $this->count_section_items($content, '## ✅ Tâches en Cours'),
            'completed_tasks' => $this->count_completed_tasks($content),
            'total_tasks' => $this->count_total_tasks($content),
            'last_updated' => date('Y-m-d H:i:s')
        ];

        // Sauvegarder les statistiques
        file_put_contents($this->stats_file, json_encode($stats, JSON_PRETTY_PRINT));
        
        // Mettre à jour la section statistiques
        $stats_content = "\n";
        $stats_content .= "- **Tâches Actives** : " . $stats['total_features'] . "\n";
        $stats_content .= "- **Tâches Complétées** : " . $stats['completed_tasks'] . "/" . $stats['total_tasks'] . "\n";
        $stats_content .= "- **Taux de Réussite** : " . ($stats['total_tasks'] > 0 ? round(($stats['completed_tasks'] / $stats['total_tasks']) * 100) : 0) . "%\n";

        return preg_replace(
            '/## 📊 Statistiques\n\n[\s\S]*?(?=## 📋|$)/',
            "## 📊 Statistiques\n\n" . $stats_content . "\n",
            $content
        );
    }

    // Méthodes utilitaires
    private function count_section_items($content, $section) {
        $lines = explode("\n", $content);
        $count = 0;
        $in_section = false;
        
        foreach ($lines as $line) {
            if (strpos($line, $section) !== false) {
                $in_section = true;
                continue;
            }
            
            if ($in_section) {
                if (strpos($line, '## ') === 0) {
                    break;
                }
                if (strpos(trim($line), '- ') === 0) {
                    $count++;
                }
            }
        }
        
        return $count;
    }

    private function count_completed_tasks($content) {
        preg_match_all('/- \[x\]/i', $content, $matches);
        return count($matches[0]);
    }

    private function count_total_tasks($content) {
        preg_match_all('/- \[x\]|- \[ \]/i', $content, $matches);
        return count($matches[0]);
    }

    private function get_next_deadline() {
        // Implémenter la logique pour récupérer la prochaine échéance
        return 'Non définie';
    }

    // Méthodes de suivi
    public function track_feature_added($feature, $details = '') {
        $message = "Nouvelle fonctionnalité ajoutée : $feature";
        if (!empty($details)) {
            $message .= " ($details)";
        }
        $this->add_activity($message);
    }

    public function track_issue_resolved($issue_id, $details = '') {
        $message = "Problème #$issue_id résolu";
        if (!empty($details)) {
            $message .= " ($details)";
        }
        $this->add_activity($message);
    }

    private function add_activity($message) {
        if (!file_exists($this->tracking_file)) {
            $this->init_tracking_file();
        }
        
        $content = file_get_contents($this->tracking_file);
        $content = $this->update_recent_activities($content, $message);
        file_put_contents($this->tracking_file, $content);
    }

    // Gestion des logs
    private function log_activity($message) {
        $this->log('INFO', $message);
    }

    private function log_error($message) {
        $this->log('ERROR', $message);
    }

    private function log($level, $message) {
        $log_dir = dirname($this->log_file);
        if (!file_exists($log_dir)) {
            wp_mkdir_p($log_dir);
        }
        
        $log_message = sprintf(
            "[%1$s] %2$s: %3$s\n",
            date('Y-m-d H:i:s'),
            $level,
            $message
        );
        
        file_put_contents($this->log_file, $log_message, FILE_APPEND);
    }
}

// Initialisation
function init_calendrier_rdv_project_tracker() {
    new Calendrier_RDV_Project_Tracker();
}
add_action('plugins_loaded', 'init_calendrier_rdv_project_tracker');
