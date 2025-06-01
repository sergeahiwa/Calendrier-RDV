<?php
/**
 * Interface pour les règles de validation
 *
 * @package CalendrierRdv\Domain\Validation\Rules
 */

namespace CalendrierRdv\Domain\Validation\Rules;

/**
 * Interface pour les règles de validation
 */
interface ValidationRuleInterface
{
    /**
     * Vérifie si la valeur est valide selon la règle
     *
     * @param mixed $value Valeur à valider
     * @param array $data Données complètes du formulaire
     * @return bool True si la valeur est valide, false sinon
     */
    public function validate($value, array $data = []): bool;
    
    /**
     * Retourne le message d'erreur par défaut
     *
     * @return string
     */
    public function getErrorMessage(): string;
}
