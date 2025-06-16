import json
import os

REPORT_PATH = os.environ.get('GITLEAKS_REPORT', 'gitleaks-report.json')

REPLACEMENT_COMMENT = (
    "# ⚠️ Secret détecté automatiquement par Gitleaks et supprimé. "
    "Remplacez par une variable d'environnement ou une gestion sécurisée."
)

def replace_secret_in_file(filename, secret, start_line):
    """
    Commente la ligne contenant le secret dans le fichier concerné et ajoute un rappel de sécurisation.
    """
    try:
        with open(filename, 'r', encoding='utf-8') as f:
            lines = f.readlines()
        # Remplacer la ligne contenant le secret
        for i, line in enumerate(lines):
            if i == start_line - 1 and secret in line:
                lines[i] = f"# {line.strip()}  {REPLACEMENT_COMMENT}\n"
        with open(filename, 'w', encoding='utf-8') as f:
            f.writelines(lines)
        print(f"Secret supprimé dans {filename} (ligne {start_line})")
    except Exception as e:
        print(f"Erreur lors du traitement de {filename}: {e}")

def main():
    """
    Analyse le rapport Gitleaks (format liste) et commente chaque secret détecté dans le code source.
    """
    if not os.path.exists(REPORT_PATH):
        print(f"Rapport Gitleaks introuvable: {REPORT_PATH}")
        return
    with open(REPORT_PATH, 'r', encoding='utf-8') as f:
        findings = json.load(f)
    if not isinstance(findings, list):
        print("Format du rapport inattendu : doit être une liste de findings.")
        return
    for finding in findings:
        file = finding.get('file')
        secret = finding.get('secret')
        start_line = finding.get('startLine')
        if file and secret and start_line:
            replace_secret_in_file(file, secret, start_line)

if __name__ == '__main__':
    main()
