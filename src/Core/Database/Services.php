<?php

namespace CalendrierRdv\Core\Database;

use WP_REST_Request;
use WP_REST_Response;
use CalendrierRdv\Database\QueryBuilder;

class Services {
    /**
     * Instance du QueryBuilder pour la table des services
     *
     * @var QueryBuilder
     */
    private static $query_builder;
    
    /**
     * Initialise le QueryBuilder
     */
    private static function init_query_builder() {
        if (!self::$query_builder) {
            self::$query_builder = new QueryBuilder('services');
        }
    }
    /**
     * Récupérer la liste des services
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public static function getServices(WP_REST_Request $request): WP_REST_Response {
        self::init_query_builder();
        
        // Récupérer les services actifs
        $services = self::$query_builder
            ->select(['id', 'name', 'description', 'duration', 'price', 'active'])
            ->where('active', 1)
            ->orderBy('name')
            ->get(ARRAY_A);
        
        // Ajouter les métadonnées
        if (is_array($services)) {
            foreach ($services as &$service) {
                $service['metadata'] = get_post_meta($service['id']);
            }
        }
        
        return new WP_REST_Response($services ?: [], 200);
    }

    /**
     * Créer un nouveau service
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public static function createService(WP_REST_Request $request): WP_REST_Response {
        // Vérifier les permissions
        if (!current_user_can('manage_options')) {
            return new WP_REST_Response(
                /* translators: Error message when a user without 'manage_options' capability tries to create a service. */
                ['message' => __('Permission refusée', 'calendrier-rdv')],
                403
            );
        }
        
        // Récupérer les données
        $data = $request->get_json_params();
        
        // Validation des données
        $required_fields = ['name', 'duration', 'price'];
        foreach ($required_fields as $field) {
            if (!isset($data[$field])) {
                return new WP_REST_Response(
                    /* translators: %s: Name of the required field. Error message for a missing field when creating/updating a service. */
                    ['message' => sprintf(__('Le champ %s est requis', 'calendrier-rdv'), $field)],
                    400
                );
            }
        }
        
        // Préparer les données
        $service_data = [
            'name' => sanitize_text_field($data['name']),
            'description' => sanitize_textarea_field($data['description'] ?? ''),
            'duration' => intval($data['duration']),
            'price' => floatval($data['price']),
            'active' => isset($data['active']) ? filter_var($data['active'], FILTER_VALIDATE_BOOLEAN) : true,
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        ];
        
        // Initialiser le QueryBuilder
        self::init_query_builder();
        
        // Insérer le service
        $service_id = self::$query_builder->insert($service_data);
        
        if ($service_id === false) {
            return new WP_REST_Response(
                /* translators: Generic error message when creating a new service fails at the database level. */
                ['message' => __('Erreur lors de la création du service', 'calendrier-rdv')],
                500
            );
        }
        
        // Préparer la réponse
        $response = [
            /* translators: Success message displayed after a new service is successfully created. */
            'message' => __('Service créé avec succès', 'calendrier-rdv'),
            'data' => array_merge(
                $service_data,
                ['id' => $service_id]
            )
        ];
        
        return new WP_REST_Response($response, 201);
    }

    /**
     * Mettre à jour un service
     *
     * @param WP_REST_Request $request
     * @param int $id
     * @return WP_REST_Response
     */
    public static function updateService(WP_REST_Request $request, int $id): WP_REST_Response {
        // Vérifier les permissions
        if (!current_user_can('manage_options')) {
            return new WP_REST_Response(
                /* translators: Error message when a user without 'manage_options' capability tries to update a service. */
                ['message' => __('Permission refusée', 'calendrier-rdv')],
                403
            );
        }
        
        // Récupérer les données
        $data = $request->get_json_params();
        
        // Validation des données
        $required_fields = ['name', 'duration', 'price'];
        foreach ($required_fields as $field) {
            if (!isset($data[$field])) {
                return new WP_REST_Response(
                    /* translators: %s: Name of the required field. Error message for a missing field when creating/updating a service. */
                    ['message' => sprintf(__('Le champ %s est requis', 'calendrier-rdv'), $field)],
                    400
                );
            }
        }
        
        // Préparer les données
        $service_data = [
            'name' => sanitize_text_field($data['name']),
            'description' => sanitize_textarea_field($data['description'] ?? ''),
            'duration' => intval($data['duration']),
            'price' => floatval($data['price']),
            'active' => isset($data['active']) ? filter_var($data['active'], FILTER_VALIDATE_BOOLEAN) : true,
            'updated_at' => current_time('mysql')
        ];
        
        // Initialiser le QueryBuilder
        self::init_query_builder();
        
        // Mettre à jour le service
        $result = self::$query_builder
            ->where('id', $id)
            ->update($service_data);
        
        if ($result === false) {
            return new WP_REST_Response(
                /* translators: Generic error message when updating an existing service fails at the database level. */
                ['message' => __('Erreur lors de la mise à jour du service', 'calendrier-rdv')],
                500
            );
        }
        
        // Préparer la réponse
        $response = [
            'message' => $result > 0 
                /* translators: Success message displayed after an existing service is successfully updated. */
                ? __('Service mis à jour avec succès', 'calendrier-rdv') 
                /* translators: Message displayed if an update operation on a service completed successfully but resulted in no actual changes to the data (e.g., submitted data was identical to existing data). */
                : __('Aucune modification effectuée', 'calendrier-rdv'),
            'data' => array_merge($service_data, ['id' => $id])
        ];
        
        return new WP_REST_Response($response, 200);
    }

    /**
     * Supprimer un service
     *
     * @param WP_REST_Request $request
     * @param int $id
     * @return WP_REST_Response
     */
    public static function deleteService(WP_REST_Request $request, int $id): WP_REST_Response {
        // Vérifier les permissions
        if (!current_user_can('manage_options')) {
            return new WP_REST_Response(
                /* translators: Error message when a user without 'manage_options' capability tries to delete a service. */
                ['message' => __('Permission refusée', 'calendrier-rdv')],
                403
            );
        }
        
        // Initialiser le QueryBuilder
        self::init_query_builder();
        
        // Supprimer le service
        $result = self::$query_builder
            ->where('id', $id)
            ->delete();
        
        if ($result === false) {
            return new WP_REST_Response(
                /* translators: Generic error message when deleting an existing service fails at the database level. */
                ['message' => __('Erreur lors de la suppression du service', 'calendrier-rdv')],
                500
            );
        }
        
        $message = $result > 0 
            /* translators: Success message displayed after an existing service is successfully deleted. */
            ? __('Service supprimé avec succès', 'calendrier-rdv')
            /* translators: Error message displayed when trying to delete a service that does not exist (based on ID). */
            : __('Aucun service trouvé avec cet ID', 'calendrier-rdv');
        
        return new WP_REST_Response(
            ['message' => $message, 'deleted' => $result > 0],
            $result > 0 ? 200 : 404
        );
    }
}
