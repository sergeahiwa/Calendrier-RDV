# Système AJAX Sécurisé

Ce document explique comment utiliser le système AJAX sécurisé du plugin Calendrier RDV.

## Vue d'ensemble

Le système AJAX utilise une approche sécurisée avec des nonces et des capacités utilisateur pour protéger contre les attaques CSRF et l'accès non autorisé.

## Fonctionnalités principales

- Gestion centralisée des appels AJAX
- Vérification des nonces pour chaque requête
- Vérification des capacités utilisateur
- Gestion des erreurs standardisée
- Support pour les requêtes publiques et privées

## Utilisation de base

### 1. Déclarer un gestionnaire AJAX

Pour ajouter un nouveau point de terminaison AJAX, utilisez la méthode `AjaxSecurity::register_ajax_handler` :

```php
use CalendrierRdv\Core\Security\AjaxSecurity;

// Dans votre méthode d'initialisation
AjaxSecurity::register_ajax_handler(
    'votre_action',           // Nom de l'action AJAX
    [$this, 'votre_callback'], // Callback à appeler
    'edit_posts',             // Capacité requise (optionnel)
    true                      // Public (optionnel, false par défaut)
);
```

### 2. Implémenter le callback

Votre callback recevra un objet `WP_REST_Request` et devra utiliser les méthodes de réponse standard :

```php
public function votre_callback(WP_REST_Request $request) {
    // Récupérer les paramètres
    $params = $request->get_params();
    
    try {
        // Votre logique métier ici
        $resultat = $this->votre_methode_metier($params);
        
        // Réponse de succès
        wp_send_json_success([
            'message' => __('Opération réussie', 'calendrier-rdv'),
            'data' => $resultat
        ]);
        
    } catch (\Exception $e) {
        // Gestion des erreurs
        wp_send_json_error([
            'message' => $e->getMessage(),
            'code' => $e->getCode()
        ], 500);
    }
}
```

## Côté client (JavaScript)

### 1. Effectuer une requête AJAX

Utilisez l'objet global `CalRdvAjax` pour effectuer des appels AJAX :

```javascript
// Requête simple
CalRdvAjax.call('votre_action', {
    param1: 'valeur1',
    param2: 'valeur2'
}, function(response) {
    // Succès
    console.log('Réussite:', response);
}, function(error) {
    // Erreur
    console.error('Erreur:', error);
});

// Méthodes pratiques
CalRdvAjax.getAppointments(params, success, error);
CalRdvAjax.createAppointment(data, success, error);
CalRdvAjax.checkAvailability(params, success, error);
```

### 2. Gestion des erreurs

Les erreurs sont gérées de manière centralisée mais peuvent être surchargées :

```javascript
CalRdvAjax.call('votre_action', params, 
    function(response) {
        // Succès personnalisé
    },
    function(error) {
        // Gestion d'erreur personnalisée
        console.error('Erreur personnalisée:', error);
    }
);
```

## Bonnes pratiques

1. **Validation des entrées** : Toujours valider et assainir les données d'entrée
2. **Capacités** : Définissez des capacités appropriées pour chaque point de terminaison
3. **Nonces** : Utilisez toujours des nonces pour les actions modifiant des données
4. **Réponses** : Utilisez les méthodes de réponse standard (`wp_send_json_success`/`wp_send_json_error`)
5. **Journalisation** : Enregistrez les erreurs importantes pour le débogage

## Exemple complet

### Côté serveur (PHP)

```php
// Dans votre classe d'initialisation
public function init() {
    AjaxSecurity::register_ajax_handler(
        'cal_rdv_get_services',
        [$this, 'handle_get_services'],
        'read' // Lecture seule
    );
}

// Votre gestionnaire
public function handle_get_services(WP_REST_Request $request) {
    try {
        // Récupérer les services depuis la base de données
        $services = $this->service_repository->get_active_services();
        
        wp_send_json_success([
            'services' => $services
        ]);
        
    } catch (\Exception $e) {
        wp_send_json_error([
            'message' => __('Impossible de charger les services', 'calendrier-rdv'),
            'code' => 'service_load_error'
        ], 500);
    }
}
```

### Côté client (JavaScript)

```javascript
// Charger les services
function loadServices() {
    const $select = $('#service-select');
    $select.prop('disabled', true);
    
    CalRdvAjax.call('cal_rdv_get_services', {}, 
        function(response) {
            // Vider et remplir le select
            $select.empty().append(
                '<option value="">' + calRdvVars.i18n.selectService + '</option>'
            );
            
            response.data.services.forEach(function(service) {
                $select.append(
                    `<option value="${service.id}">${service.name}</option>`
                );
            });
            
            $select.prop('disabled', false);
        },
        function(error) {
            console.error('Erreur:', error);
            alert(calRdvVars.i18n.errorLoadingServices);
            $select.prop('disabled', false);
        }
    );
}
```

## Dépannage

### Erreur de nonce invalide
- Vérifiez que le nonce est correctement localisé dans le JavaScript
- Assurez-vous que le même nonce est utilisé pour la vérification

### Accès refusé
- Vérifiez les capacités de l'utilisateur
- Assurez-vous que l'utilisateur est connecté si nécessaire

### Erreur 400/500
- Vérifiez les logs d'erreur PHP
- Assurez-vous que tous les paramètres requis sont fournis
- Vérifiez les types de données des paramètres

## Sécurité

- Toutes les requêtes nécessitent un nonce valide
- Les capacités utilisateur sont vérifiées pour chaque requête
- Les entrées sont validées et les sorties sont échappées
- Les messages d'erreur ne révèlent pas d'informations sensibles
