#!/usr/bin/env python3
"""Disposable native-client backup check; isolated Colima container, no network."""
import gzip
import hashlib
import json
import os
from pathlib import Path
import secrets
import shutil
import subprocess
import tempfile
import time

ROOT = Path(__file__).resolve().parents[5]
OUT = Path(__file__).resolve().parent
DOCKER = ['/opt/homebrew/bin/docker', '--context', 'colima']
TRANSPORT = '''#!/usr/bin/env python3
import json, os, subprocess, sys
with open(os.environ['S008_CALLS'], 'a') as f:
    f.write(json.dumps(sys.argv[1:]) + '\\n')
base = ['/opt/homebrew/bin/docker', '--context', 'colima']
if sys.argv[1].startswith('--defaults-file='):
    cnf = sys.argv[1].split('=', 1)[1]
    subprocess.run([*base, 'cp', cnf, os.environ['S008_CONTAINER'] + ':' + cnf], check=True, capture_output=True)
os.execv(base[0], [*base, 'exec', '-i', os.environ['S008_CONTAINER'], '/usr/bin/mariadb-dump', *sys.argv[1:]])
'''


def main():
    runtime = Path(tempfile.mkdtemp(prefix='saas-s008-a2-'))
    runtime.chmod(0o700)
    container = 'saas-s008-a2-' + secrets.token_hex(4)
    root_secret, backup_secret = secrets.token_hex(24), secrets.token_hex(24)
    created = False
    result = {'base_revision': '4584b8f947418b099603de20bbbe94835e0f2df4',
              'container': container, 'context': 'colima', 'network': 'none',
              'image': 'mariadb:10.11.14', 'commands': []}

    def docker(*args, input=None, check=True):
        result['commands'].append([*DOCKER, *args])
        return subprocess.run([*DOCKER, *args], input=input, text=True,
                              capture_output=True, check=check)

    try:
        image = json.loads(docker('image', 'inspect', 'mariadb:10.11.14').stdout)[0]
        result['image_id'] = image['Id']
        result['repo_digests'] = image['RepoDigests']
        envfile = runtime / 'container.env'
        envfile.write_text('MARIADB_ROOT_PASSWORD=' + root_secret + '\n')
        envfile.chmod(0o600)
        docker('run', '--detach', '--rm', '--pull=never', '--name', container,
               '--network', 'none', '--mount', 'type=tmpfs,destination=/var/lib/mysql',
               '--env-file', str(envfile), 'mariadb:10.11.14')
        created = True
        ready = False
        for _ in range(40):
            probe = docker('exec', container, 'healthcheck.sh', '--connect', '--innodb_initialized', check=False)
            if probe.returncode == 0:
                ready = True
                break
            time.sleep(0.5)
        assert ready, 'Disposable MariaDB did not become ready'
        root_cnf = runtime / 'root.cnf'
        root_cnf.write_text('[client]\nuser=root\npassword=' + root_secret + '\n')
        root_cnf.chmod(0o600)
        docker('cp', str(root_cnf), container + ':/tmp/s008-root.cnf')
        bootstrap = ("CREATE DATABASE saas_s008_a2_source;\n"
                     "CREATE DATABASE saas_s008_a2_restore;\n"
                     f"CREATE USER 's008'@'localhost' IDENTIFIED BY '{backup_secret}';\n"
                     "GRANT SELECT, SHOW VIEW, TRIGGER ON saas_s008_a2_source.* TO 's008'@'localhost';\n"
                     "CREATE TABLE saas_s008_a2_source.synthetic (id INT PRIMARY KEY, payload BLOB);\n"
                     "INSERT INTO saas_s008_a2_source.synthetic VALUES (7, X'0001FEFF');\n"
                     "USE saas_s008_a2_source;\n"
                     "CREATE TRIGGER synthetic_insert BEFORE INSERT ON synthetic FOR EACH ROW SET NEW.id = NEW.id;\n")
        docker('exec', '-i', container, 'mariadb', '--defaults-file=/tmp/s008-root.cnf', input=bootstrap)
        result['server_version'] = docker('exec', container, 'mariadb', '--defaults-file=/tmp/s008-root.cnf', '--batch', '--skip-column-names', '-e', 'SELECT VERSION()').stdout.strip()
        result['dump_version'] = docker('exec', container, '/usr/bin/mariadb-dump', '--no-defaults', '--version').stdout.strip()
        assert '10.11.14-MariaDB' in result['server_version']
        assert '10.11.14-MariaDB' in result['dump_version']
        cnf = runtime / 'client.cnf'
        cnf.write_text('[client]\nuser=s008\npassword=' + backup_secret + '\n')
        cnf.chmod(0o600)
        docker('exec', container, 'mkdir', '-p', str(runtime))
        tools = runtime / 'bin'
        tools.mkdir(mode=0o700)
        transport = tools / 'mysqldump'
        transport.write_text(TRANSPORT)
        transport.chmod(0o700)
        # Offsite tools must never run for the pending-destination branch.
        for name in ('ssh', 'scp'):
            p = tools / name
            p.write_text('#!/bin/sh\nexit 91\n')
            p.chmod(0o700)
        backup = runtime / 'backup'
        backup.mkdir(mode=0o700)
        calls = runtime / 'calls.jsonl'
        env = dict(os.environ, PATH=str(tools) + ':' + os.environ['PATH'],
                   S008_CONTAINER=container, S008_CALLS=str(calls))
        args = ['bash', str(ROOT / 'deploy/backup.sh'), str(cnf), 'saas_s008_a2_source', str(backup), '-', '-']
        result['commands'].append(args)
        completed = subprocess.run(args, env=env, capture_output=True, text=True)
        result['backup_exit'] = completed.returncode
        result['backup_stdout'] = completed.stdout
        result['backup_stderr'] = completed.stderr.strip()
        expected = 'Local archive saved; Q-04 off-VPS destination missing; retention skipped (NOT a successful daily backup)'
        assert completed.returncode == 1 and completed.stderr.strip() == expected
        result['dump_calls'] = [json.loads(line) for line in calls.read_text().splitlines()]
        assert result['dump_calls'][0] == ['--no-defaults', '--version']
        dump_args = result['dump_calls'][1]
        assert dump_args[0] == '--defaults-file=' + str(cnf)
        assert dump_args[-1] == 'saas_s008_a2_source'
        assert not any(arg.startswith(('--set-gtid-purged', '--column-statistics', '--databases')) for arg in dump_args)
        archives = list(backup.glob('*.sql.gz'))
        assert len(archives) == 1
        archive = archives[0]
        sql = gzip.decompress(archive.read_bytes())
        assert b'CREATE TABLE `synthetic`' in sql and b'0x0001FEFF' in sql
        assert b'TRIGGER' in sql and b'CREATE DATABASE' not in sql
        assert not any(line.startswith(b'USE ') for line in sql.splitlines())
        assert not list(backup.glob('.work-*')) and not list(backup.glob('*.lock'))
        result['archive'] = {'name': archive.name, 'sha256': hashlib.sha256(archive.read_bytes()).hexdigest(),
                             'bytes': archive.stat().st_size, 'sql_bytes': len(sql),
                             'gzip_valid': True, 'explicit_target': True, 'triggers': True, 'hex_blob': True}
        docker('exec', '-i', container, 'mariadb', '--defaults-file=/tmp/s008-root.cnf', '--database=saas_s008_a2_restore', input=sql.decode())
        restored = docker('exec', container, 'mariadb', '--defaults-file=/tmp/s008-root.cnf', '--batch', '--skip-column-names', '--database=saas_s008_a2_restore', '-e', 'SELECT id, HEX(payload) FROM synthetic').stdout.strip()
        assert restored == '7\t0001FEFF'
        result['restored_row_matches'] = True
        result['verdict'] = 'pass_native_backup_expected_offsite_gate'
    except subprocess.CalledProcessError as error:
        result['error'] = {'exit': error.returncode, 'stderr': error.stderr.replace(root_secret, '[REDACTED]').replace(backup_secret, '[REDACTED]')}
        raise
    finally:
        cleanup = {'container': container}
        if created:
            removed = docker('rm', '--force', container, check=False)
            cleanup['container_remove_exit'] = removed.returncode
            assert removed.returncode == 0
        remaining = docker('ps', '--all', '--filter', 'name=^/' + container + '$', '--format', '{{.Names}}')
        cleanup['container_absent'] = not remaining.stdout.strip()
        shutil.rmtree(runtime)
        cleanup['private_runtime_removed'] = not runtime.exists()
        result['cleanup'] = cleanup
        content = json.dumps(result, indent=2) + '\n'
        assert root_secret not in content and backup_secret not in content
        (OUT / 'mariadb-result.json').write_text(content)
    print('PASS exact MariaDB 10.11.14 candidate backup, valid archive, expected offsite gate, scratch restore, cleanup')


if __name__ == '__main__':
    main()
