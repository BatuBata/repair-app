"""Run the unchanged PHP inventory block from the pinned runbook against QA only."""
import hashlib
import json
import os
from pathlib import Path
import subprocess

runtime = Path('/private/tmp/saas-s009-a1-20261117')
secret = json.loads((runtime / 'private.json').read_text())
code = Path('docs/SAAS-MIGRATION-RUNBOOK.md').read_text().split('```php\n')[1].split('```')[0]
(runtime / 'inventory.php').write_text(code)
(runtime / 'inventory.php').chmod(0o600)
base = dict(os.environ, APP_ENV='testing', APP_CONFIG_CACHE=str(runtime / 'unused-config.php'),
            DB_CONNECTION='mysql', DB_HOST='127.0.0.1', DB_PORT='13319', DB_USERNAME='s009',
            DB_PASSWORD=secret['password'], DB_URL='', DB_SOCKET='', CACHE_STORE='array', SESSION_DRIVER='array')
records = []

def check(label, database, mode, expected_db, path, expected_exit):
    env = dict(base, DB_DATABASE=database, EXPECTED_DB=expected_db, MODE=mode, INVENTORY=str(path))
    process = subprocess.run(['/opt/homebrew/opt/php@8.3/bin/php', str(runtime / 'inventory.php')],
                             env=env, text=True, capture_output=True)
    assert process.returncode == expected_exit, (label, process.returncode)
    assert process.stdout.strip() in ('', 'BASELINE SAVED', 'ALL LEGACY FIELDS IDENTICAL'), label
    assert process.stderr.strip() in ('', 'Inventory failed; keep maintenance and inspect private inputs.'), label
    records.append(dict(case=label, exit=process.returncode, stdout=process.stdout.strip(),
                        stderr=process.stderr.strip(), verdict='pass'))

inventory = runtime / 'runbook-baseline.json'
check('capture-source', 'saas_s009_a1_source', 'capture', 'saas_s009_a1_source', inventory, 0)
check('verify-restored-migrated-recovery', 'saas_s009_a1_recovery', 'verify', 'saas_s009_a1_recovery', inventory, 0)
check('reject-wrong-explicit-db', 'saas_s009_a1_recovery', 'verify', 'saas_s009_a1_source', inventory, 1)
bad = json.loads(inventory.read_text())
first = next(iter(bad))
bad[first]['rows'] += 1
corrupt = runtime / 'false-baseline.json'
corrupt.write_text(json.dumps(bad))
corrupt.chmod(0o600)
check('reject-false-baseline', 'saas_s009_a1_recovery', 'verify', 'saas_s009_a1_recovery', corrupt, 1)
print(json.dumps(dict(runbook_php_sha256=hashlib.sha256(code.encode()).hexdigest(),
                      baseline_sha256=hashlib.sha256(inventory.read_bytes()).hexdigest(), cases=records), indent=2))
