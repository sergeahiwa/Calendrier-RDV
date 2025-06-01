<?php
/**
 * Service d'exportation CSV et Excel pour les rendez-vous
 */
namespace CalendrierRdv\Infrastructure\Export;

class ExportService
{
    /**
     * Exporte les rendez-vous au format CSV
     * @param array $appointments
     * @return string CSV
     */
    public function exportCsv(array $appointments): string
    {
        $output = fopen('php://temp', 'r+');
        fputcsv($output, ['Date', 'Client', 'Email', 'Service', 'Prestataire', 'Statut']);
        foreach ($appointments as $a) {
            fputcsv($output, [
                $a['date'] ?? '',
                $a['client'] ?? '',
                $a['email'] ?? '',
                $a['service'] ?? '',
                $a['provider'] ?? '',
                $a['status'] ?? ''
            ]);
        }
        rewind($output);
        return stream_get_contents($output);
    }

    /**
     * Exporte les rendez-vous au format Excel (XLSX simplifié)
     * @param array $appointments
     * @return string Données tabulaires (XLSX minimal)
     */
    public function exportExcel(array $appointments): string
    {
        // Pour MVP : format TSV compatible Excel
        $lines = [];
        $lines[] = "Date\tClient\tEmail\tService\tPrestataire\tStatut";
        foreach ($appointments as $a) {
            $lines[] = implode("\t", [
                $a['date'] ?? '',
                $a['client'] ?? '',
                $a['email'] ?? '',
                $a['service'] ?? '',
                $a['provider'] ?? '',
                $a['status'] ?? ''
            ]);
        }
        return implode("\r\n", $lines);
    }
}
