<?php
/**
 * Fabrique de règles de validation
 *
 * @package CalendrierRdv\Domain\Validation
 */

namespace CalendrierRdv\Domain\Validation;

use CalendrierRdv\Domain\Validation\Rules\AfterDateRule;
use CalendrierRdv\Domain\Validation\Rules\DateRule;
use CalendrierRdv\Domain\Validation\Rules\EmailRule;
use CalendrierRdv\Domain\Validation\Rules\MaxLengthRule;
use CalendrierRdv\Domain\Validation\Rules\MinLengthRule;
use CalendrierRdv\Domain\Validation\Rules\NumericRule;
use CalendrierRdv\Domain\Validation\Rules\RequiredRule;
use CalendrierRdv\Domain\Validation\Rules\ValidationRuleInterface;

/**
 * Fabrique de règles de validation
 */
class ValidationRuleFactory
{
    /**
     * Crée une règle de validation
     *
     * @param string $rule Nom de la règle
     * @param mixed ...$args Arguments de la règle
     * @return ValidationRuleInterface
     * @throws \InvalidArgumentException Si la règle n'existe pas
     */
    public static function create(string $rule, ...$args): ValidationRuleInterface
    {
        $rules = [
            'required' => RequiredRule::class,
            'email' => EmailRule::class,
            'min' => [MinLengthRule::class, 'int'],
            'max' => [MaxLengthRule::class, 'int'],
            'numeric' => NumericRule::class,
            'date' => [DateRule::class, 'string'],
            'after' => [AfterDateRule::class, 'string', 'string'],
        ];
        
        if (!isset($rules[$rule])) {
            throw new \InvalidArgumentException(sprintf('Règle de validation inconnue : %s', $rule));
        }
        
        $ruleClass = $rules[$rule];
        $params = [];
        
        // Gestion des règles avec paramètres
        if (is_array($ruleClass)) {
            $class = array_shift($ruleClass);
            $params = $args;
            
            // Vérification des types de paramètres
            foreach ($ruleClass as $i => $type) {
                if (!isset($params[$i])) {
                    continue;
                }
                
                switch ($type) {
                    case 'int':
                        $params[$i] = (int) $params[$i];
                        break;
                    case 'string':
                        $params[$i] = (string) $params[$i];
                        break;
                    case 'bool':
                        $params[$i] = (bool) $params[$i];
                        break;
                    case 'float':
                        $params[$i] = (float) $params[$i];
                        break;
                }
            }
        } else {
            $class = $ruleClass;
        }
        
        return new $class(...$params);
    }
    
    /**
     * Crée plusieurs règles de validation
     * 
     * @param array $rules Tableau de règles au format [règle => [param1, param2, ...]]
     * @return ValidationRuleInterface[]
     */
    public static function createMany(array $rules): array
    {
        $result = [];
        
        foreach ($rules as $rule => $params) {
            if (is_numeric($rule)) {
                $rule = $params;
                $params = [];
            }
            
            if (!is_array($params)) {
                $params = [$params];
            }
            
            $result[] = self::create($rule, ...$params);
        }
        
        return $result;
    }
}
