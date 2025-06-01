<?php
// FPDF minimal loader
// Pour la version complète, télécharger depuis https://github.com/rospdf/fpdf ou http://www.fpdf.org
// Ce fichier est un point d'intégration pour la génération de PDF dans le plugin.
// Placez ici la classe FPDF complète pour production.

// Placeholder : avertit si la vraie classe n'est pas présente
if (!class_exists('FPDF')) {
    class FPDF {
        function AddPage() {}
        function SetFont($fam, $style='', $size=12) {}
        function Cell($w, $h=0, $txt='', $border=0, $ln=0, $align='', $fill=false, $link='') {}
        function Output($dest='', $name='', $isUTF8=false) { return ''; }
        function Image($file, $x=null, $y=null, $w=0, $h=0, $type='', $link='') {}
        function Ln($h=null) {}
        function SetTextColor($r, $g=null, $b=null) {}
        function SetFillColor($r, $g=null, $b=null) {}
        function SetDrawColor($r, $g=null, $b=null) {}
        function SetLineWidth($width) {}
        function SetXY($x, $y) {}
        function SetX($x) {}
        function SetY($y) {}
        function MultiCell($w, $h, $txt, $border=0, $align='J', $fill=false) {}
    }
}
