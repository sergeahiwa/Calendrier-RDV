<?php
/**
 * Règle de validation : email valide
 *
 * @package CalendrierRdv\Domain\Validation\Rules
 */

namespace CalendrierRdv\Domain\Validation\Rules;

/**
 * Règle de validation : email valide
 */
class EmailRule extends AbstractValidationRule
{
    /**
     * {@inheritdoc}
     */
    public function validate($value, array $data = []): bool
    {
        if (empty($value) && $value !== '0') {
            return true; // La validation passe si le champ est vide (utiliser RequiredRule pour rendre obligatoire)
        }
        
        return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    /**
     * {@inheritdoc}
     */
    protected function getDefaultMessage(): string
    {
        return 'Le champ {field} doit être une adresse email valide.';
    }
}
