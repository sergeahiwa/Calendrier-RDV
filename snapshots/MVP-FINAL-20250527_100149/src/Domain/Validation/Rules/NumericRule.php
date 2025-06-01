<?php
/**
 * Règle de validation : valeur numérique
 *
 * @package CalendrierRdv\Domain\Validation\Rules
 */

namespace CalendrierRdv\Domain\Validation\Rules;

/**
 * Règle de validation : valeur numérique
 */
class NumericRule extends AbstractValidationRule
{
    /**
     * {@inheritdoc}
     */
    public function validate($value, array $data = []): bool
    {
        if (empty($value) && $value !== '0' && $value !== 0) {
            return true; // La validation passe si le champ est vide (utiliser RequiredRule pour rendre obligatoire)
        }
        
        return is_numeric($value);
    }
    
    /**
     * {@inheritdoc}
     */
    protected function getDefaultMessage(): string
    {
        return 'Le champ {field} doit être une valeur numérique.';
    }
}
