<?php

namespace CalendrierRdv\Core\Database;

use WP_REST_Request;
use function get_post_meta;
use WP_REST_Response;

class Providers {
    /**
     * Instance de la base de données
     *
     * @var \wpdb
     */
    protected static $wpdb;
    
    /**
     * Initialiser la connexion à la base de données
     */
    protected static function initDB() {
        if (!isset(self::$wpdb)) {
            global $wpdb;
            self::$wpdb = $wpdb;
        }
    }
    
    /**
     * Récupérer la liste des prestataires
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public static function getProviders(WP_REST_Request $request): WP_REST_Response {
        self::initDB();
        // Préparer la requête
        $query = self::$wpdb->prepare(
            "SELECT id, name, email, phone, address, active 
             FROM " . self::$wpdb->prefix . "rdv_providers 
             WHERE active = %d 
             ORDER BY name",
            1
        );
        
        // Exécuter la requête
        $providers = self::$wpdb->get_results($query, ARRAY_A);
        
        // Ajouter les métadonnées
        foreach ($providers as &$provider) {
            $provider['metadata'] = get_post_meta($provider['id']);
        }
        
        return new WP_REST_Response($providers, 200);
    }

    /**
     * Créer un nouveau prestataire
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public static function createProvider(WP_REST_Request $request): WP_REST_Response {
        self::initDB();
        
        // Vérifier les permissions
        if (!current_user_can('manage_options')) {
            return new WP_REST_Response(
                /* translators: Error message when a user without 'manage_options' capability tries to create a provider. */
                array('message' => __('Permission refusée', 'calendrier-rdv')),
                403
            );
        }
        
        // Récupérer les données
        $data = $request->get_json_params();
        
        // Validation des données
        $required_fields = ['name', 'email'];
        foreach ($required_fields as $field) {
            if (!isset($data[$field])) {
                return new WP_REST_Response(
                    /* translators: %s: Name of the required field. Error message for a missing field when creating/updating a provider. */
                    array('message' => sprintf(__('Le champ %s est requis', 'calendrier-rdv'), $field)),
                    400
                );
            }
        }
        
        // Préparer les données
        $provider_data = array(
            'name' => sanitize_text_field($data['name']),
            'email' => sanitize_email($data['email']),
            'phone' => sanitize_text_field($data['phone'] ?? ''),
            'address' => sanitize_textarea_field($data['address'] ?? ''),
            'active' => isset($data['active']) ? filter_var($data['active'], FILTER_VALIDATE_BOOLEAN) : true,
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        );
        
        // Insérer le prestataire
        $result = self::$wpdb->insert(
            self::$wpdb->prefix . 'rdv_providers',
            $provider_data
        );
        
        if ($result === false) {
            return new WP_REST_Response(
                /* translators: Generic error message when creating a new provider fails at the database level. */
                array('message' => __('Erreur lors de la création du prestataire', 'calendrier-rdv')),
                500
            );
        }
        
        // Préparer la réponse
        $response = array(
            /* translators: Success message displayed after a new provider is successfully created. */
            'message' => __('Prestataire créé avec succès', 'calendrier-rdv'),
            'data' => array_merge(
                $provider_data,
                array('id' => self::$wpdb->insert_id)
            )
        );
        
        return new WP_REST_Response($response, 201);
    }

    /**
     * Mettre à jour un prestataire
     *
     * @param WP_REST_Request $request
     * @param int $id
     * @return WP_REST_Response
     */
    public static function updateProvider(WP_REST_Request $request, int $id): WP_REST_Response {
        self::initDB();
        
        // Vérifier les permissions
        if (!current_user_can('manage_options')) {
            return new WP_REST_Response(
                /* translators: Error message when a user without 'manage_options' capability tries to update a provider. */
                array('message' => __('Permission refusée', 'calendrier-rdv')),
                403
            );
        }
        
        // Récupérer les données
        $data = $request->get_json_params();
        
        // Validation des données
        $required_fields = ['name', 'email'];
        foreach ($required_fields as $field) {
            if (!isset($data[$field])) {
                return new WP_REST_Response(
                    /* translators: %s: Name of the required field. Error message for a missing field when creating/updating a provider. */
                    array('message' => sprintf(__('Le champ %s est requis', 'calendrier-rdv'), $field)),
                    400
                );
            }
        }
        
        // Préparer les données
        $provider_data = array(
            'name' => sanitize_text_field($data['name']),
            'email' => sanitize_email($data['email']),
            'phone' => sanitize_text_field($data['phone'] ?? ''),
            'address' => sanitize_textarea_field($data['address'] ?? ''),
            'active' => isset($data['active']) ? filter_var($data['active'], FILTER_VALIDATE_BOOLEAN) : true,
            'updated_at' => current_time('mysql')
        );
        
        // Mettre à jour le prestataire
        $result = self::$wpdb->update(
            self::$wpdb->prefix . 'rdv_providers',
            $provider_data,
            array('id' => $id)
        );
        
        if ($result === false) {
            return new WP_REST_Response(
                /* translators: Generic error message when updating an existing provider fails at the database level. */
                array('message' => __('Erreur lors de la mise à jour du prestataire', 'calendrier-rdv')),
                500
            );
        }
        
        // Préparer la réponse
        $response = array(
            /* translators: Success message displayed after an existing provider is successfully updated. */
            'message' => __('Prestataire mis à jour avec succès', 'calendrier-rdv'),
            'data' => array_merge($provider_data, array('id' => $id))
        );
        
        return new WP_REST_Response($response, 200);
    }

    /**
     * Supprimer un prestataire
     *
     * @param WP_REST_Request $request
     * @param int $id
     * @return WP_REST_Response
     */
    public static function deleteProvider(WP_REST_Request $request, int $id): WP_REST_Response {
        self::initDB();
        
        // Vérifier les permissions
        if (!current_user_can('manage_options')) {
            return new WP_REST_Response(
                /* translators: Error message when a user without 'manage_options' capability tries to delete a provider. */
                array('message' => __('Permission refusée', 'calendrier-rdv')),
                403
            );
        }
        
        // Supprimer le prestataire
        $result = self::$wpdb->delete(
            self::$wpdb->prefix . 'rdv_providers',
            array('id' => $id)
        );
        
        if ($result === false) {
            return new WP_REST_Response(
                /* translators: Generic error message when deleting an existing provider fails at the database level. */
                array('message' => __('Erreur lors de la suppression du prestataire', 'calendrier-rdv')),
                500
            );
        }
        
        return new WP_REST_Response(
            /* translators: Success message displayed after an existing provider is successfully deleted. */
            array('message' => __('Prestataire supprimé avec succès', 'calendrier-rdv')),
            200
        );
    }
}
