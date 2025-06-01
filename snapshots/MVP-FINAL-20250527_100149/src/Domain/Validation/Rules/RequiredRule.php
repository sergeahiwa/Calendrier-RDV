<?php
/**
 * Règle de validation : champ requis
 *
 * @package CalendrierRdv\Domain\Validation\Rules
 */

namespace CalendrierRdv\Domain\Validation\Rules;

/**
 * Règle de validation : champ requis
 */
class RequiredRule extends AbstractValidationRule
{
    /**
     * {@inheritdoc}
     */
    public function validate($value, array $data = []): bool
    {
        if (is_string($value)) {
            $value = trim($value);
        }
        
        return !empty($value) || $value === '0' || $value === 0;
    }
    
    /**
     * {@inheritdoc}
     */
    protected function getDefaultMessage(): string
    {
        return 'Le champ {field} est requis.';
    }
}
