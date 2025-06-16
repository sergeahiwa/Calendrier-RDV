import json
import os
import re

REPORT_PATH = os.environ.get('GITLEAKS_REPORT', 'gitleaks-report.json')

# Patterns à remplacer par une variable d'environnement
REPLACEMENT_COMMENT = (
    "# ⚠️ Secret détecté automatiquement par Gitleaks et supprimé. "
    "Remplacez par une variable d'environnement ou une gestion sécurisée."
)


def replace_secret_in_file(filename, secret, start_line):
    try:
        with open(filename, 'r', encoding='utf-8') as f:
            lines = f.readlines()
        # Remplacer la ligne contenant le secret
        for i, line in enumerate(lines):
            if i == start_line - 1 and secret in line:
                # Commenter la ligne et ajouter un rappel
                lines[i] = f"# {line.strip()}  {REPLACEMENT_COMMENT}\n"
        with open(filename, 'w', encoding='utf-8') as f:
            f.writelines(lines)
        print(f"Secret supprimé dans {filename} (ligne {start_line})")
    except Exception as e:
        print(f"Erreur lors du traitement de {filename}: {e}")


def main():
    if not os.path.exists(REPORT_PATH):
        print(f"Rapport Gitleaks introuvable: {REPORT_PATH}")
        return
    with open(REPORT_PATH, 'r', encoding='utf-8') as f:
        report = json.load(f)
    for finding in report.get('findings', []):
        file = finding.get('file')
        secret = finding.get('secret')
        start_line = finding.get('startLine')
        if file and secret and start_line:
            replace_secret_in_file(file, secret, start_line)

if __name__ == '__main__':
    main()
