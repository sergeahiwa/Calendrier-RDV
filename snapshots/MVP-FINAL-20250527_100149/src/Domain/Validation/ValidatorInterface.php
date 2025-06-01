<?php
/**
 * Interface pour les validateurs
 *
 * @package CalendrierRdv\Domain\Validation
 */

namespace CalendrierRdv\Domain\Validation;

/**
 * Interface pour les validateurs
 */
interface ValidatorInterface
{
    /**
     * Valide les données
     *
     * @param array $data Données à valider
     * @return ValidationResult Résultat de la validation
     */
    public function validate(array $data): ValidationResult;
    
    /**
     * Ajoute une règle de validation
     *
     * @param string $field Champ à valider
     * @param array $rules Règles de validation
     * @return self
     */
    public function addRules(string $field, array $rules): self;
}
