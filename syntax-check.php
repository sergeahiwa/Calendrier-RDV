<?php
/**
 * Script de vérification de la syntaxe du plugin Calendrier RDV
 */

// Vérifier la syntaxe du fichier principal
$file = 'calendrier-rdv.php';
echo "Vérification de la syntaxe de $file...\n";

// Utiliser php -l pour vérifier la syntaxe
$output = shell_exec("php -l $file 2>&1");
echo $output;

// Vérifier les includes principaux
$includes = [
    'includes/functions.php',
    'includes/class-appointment-manager.php',
    'includes/class-timezone-handler.php'
];

foreach ($includes as $include) {
    if (file_exists($include)) {
        echo "Vérification de la syntaxe de $include...\n";
        $output = shell_exec("php -l $include 2>&1");
        echo $output;
    } else {
        echo "Fichier $include non trouvé\n";
    }
}

echo "\nVérification terminée.\n";
