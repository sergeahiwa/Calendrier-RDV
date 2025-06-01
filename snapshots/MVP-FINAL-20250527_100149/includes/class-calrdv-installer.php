<?php
// Sécurité : empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit;
}

class CalRdv_Installer {
    public static function run() {
        // TODO: Logique d’installation à ajouter ici
    }
    public static function get_instance() {
        return new self();
    }
    public function install() {
        // Future logique de création de tables
    }
    public function upgrade() {
        // Future logique de mise à jour de structure
    }
}
