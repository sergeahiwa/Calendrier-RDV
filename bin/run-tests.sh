#!/bin/bash

# Couleurs pour la sortie
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Fonction pour afficher un message d'en-tête
print_header() {
    echo -e "\n${YELLOW}=== $1 ===${NC}"
}

# Vérifier que PHPUnit est installé
if ! command -v ./vendor/bin/phpunit &> /dev/null; then
    echo -e "${RED}PHPUnit n'est pas installé. Exécutez 'composer install' d'abord.${NC}"
    exit 1
fi

# Exécuter les tests unitaires
print_header "Exécution des tests unitaires"
./vendor/bin/phpunit --testsuite Unit
UNIT_EXIT_CODE=$?

# Exécuter les tests d'intégration
print_header "Exécution des tests d'intégration"
./vendor/bin/phpunit --testsuite Integration
INTEGRATION_EXIT_CODE=$?

# Exécuter les tests de performance
print_header "Exécution des tests de performance"
./vendor/bin/phpunit --testsuite Performance
PERFORMANCE_EXIT_CODE=$?

# Exécuter les tests de sécurité
print_header "Exécution des tests de sécurité"
./vendor/bin/phpunit --testsuite Security
SECURITY_EXIT_CODE=$?

# Exécuter les tests d'accessibilité
print_header "Exécution des tests d'accessibilité"
./vendor/bin/phpunit --testsuite Accessibility
ACCESSIBILITY_EXIT_CODE=$?

# Afficher un résumé des résultats
print_header "Résumé des tests"

echo -e "Tests unitaires: $([[ $UNIT_EXIT_CODE -eq 0 ]] && echo -e "${GREEN}RÉUSSI${NC}" || echo -e "${RED}ÉCHEC${NC}")"
echo -e "Tests d'intégration: $([[ $INTEGRATION_EXIT_CODE -eq 0 ]] && echo -e "${GREEN}RÉUSSI${NC}" || echo -e "${RED}ÉCHEC${NC}")"
echo -e "Tests de performance: $([[ $PERFORMANCE_EXIT_CODE -eq 0 ]] && echo -e "${GREEN}RÉUSSI${NC}" || echo -e "${RED}ÉCHEC${NC}")"
echo -e "Tests de sécurité: $([[ $SECURITY_EXIT_CODE -eq 0 ]] && echo -e "${GREEN}RÉUSSI${NC}" || echo -e "${RED}ÉCHEC${NC}")"
echo -e "Tests d'accessibilité: $([[ $ACCESSIBILITY_EXIT_CODE -eq 0 ]] && echo -e "${GREEN}RÉUSSI${NC}" || echo -e "${RED}ÉCHEC${NC}")"

# Renvoyer un code d'erreur si un des tests a échoué
if [ $UNIT_EXIT_CODE -ne 0 ] || [ $INTEGRATION_EXIT_CODE -ne 0 ] || [ $PERFORMANCE_EXIT_CODE -ne 0 ] || [ $SECURITY_EXIT_CODE -ne 0 ] || [ $ACCESSIBILITY_EXIT_CODE -ne 0 ]; then
    echo -e "\n${RED}Certains tests ont échoué. Veuillez vérifier les logs ci-dessus pour plus de détails.${NC}"
    exit 1
else
    echo -e "\n${GREEN}Tous les tests ont réussi !${NC}"
    exit 0
fi
