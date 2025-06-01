<?php
/**
 * Exception levée lorsqu'un statut invalide est fourni
 *
 * @package CalendrierRdv\Domain\Exception
 */

namespace CalendrierRdv\Domain\Exception;

/**
 * Exception pour les statuts invalides
 */
class InvalidStatusException extends \DomainException
{
    /**
     * @param string $status Le statut invalide
     * @param array $validStatuses Liste des statuts valides
     * @param int $code Code d'erreur
     * @param \Throwable|null $previous Exception précédente
     */
    public function __construct(
        string $status,
        array $validStatuses = [],
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        $message = sprintf(
            'Statut "%s" invalide. ', 
            $status
        );
        
        if (!empty($validStatuses)) {
            $message .= sprintf(
                'Statuts valides : %s',
                implode(', ', $validStatuses)
            );
        }
        
        parent::__construct($message, $code, $previous);
    }
}
