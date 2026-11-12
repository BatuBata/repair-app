#!/usr/bin/env python3
"""Host-only safety assertions with disposable stubs; never connects to MySQL/SSH."""
import gzip
import json
import os
from pathlib import Path
import shutil
import subprocess
import tempfile
import time

SCRIPTS = Path(__file__).resolve().parent
STUB = '''#!/usr/bin/env python3
import json, os, pathlib, subprocess, sys
name = pathlib.Path(sys.argv[0]).name
with open(os.environ['CALLS'], 'a') as f:
    f.write(json.dumps([name, *sys.argv[1:]]) + '\\n')
if os.environ.get('FAIL') == name:
    print('synthetic private error', file=sys.stderr)
    sys.exit(19)
if name == 'mysqldump':
    print('CREATE TABLE synthetic (id INT); INSERT INTO synthetic VALUES (7);')
elif name == 'mysql':
    pathlib.Path(os.environ['IMPORTED']).write_bytes(sys.stdin.buffer.read())
elif name == 'gzip':
    sys.exit(subprocess.call([os.environ['REAL_GZIP'], *sys.argv[1:]]))
elif name == 'git':
    if sys.argv[1] == 'rev-parse': print('a' * 40)
    elif os.environ.get('DIRTY'): print(' M synthetic')
elif name == 'php':
    if os.environ.get('PHP_FAIL') == sys.argv[2]: sys.exit(18)
'''


def main():
    passed = 0
    with tempfile.TemporaryDirectory(prefix='repair-T020-check-') as tmp:
        root = Path(tmp)
        tools = root / 'bin'
        tools.mkdir()
        for tool in ('mysqldump', 'mysql', 'gzip', 'scp', 'ssh', 'git', 'php'):
            p = tools / tool
            p.write_text(STUB)
            p.chmod(0o700)
        cnf = root / 'client.cnf'
        cnf.write_text('[client]\nuser=synthetic\npassword=synthetic-secret\n')
        cnf.chmod(0o600)
        calls = root / 'calls'
        imported = root / 'imported'
        env = dict(os.environ, PATH=f'{tools}:{os.environ["PATH"]}',
                   CALLS=str(calls), IMPORTED=str(imported), REAL_GZIP=shutil.which('gzip'))

        def run(script, args, ok, fail='', cwd=None, extra=None):
            nonlocal passed
            calls.write_text('')
            result = subprocess.run(['bash', str(SCRIPTS / script), *map(str, args)],
                                    env=dict(env, FAIL=fail, **(extra or {})), cwd=cwd,
                                    capture_output=True, text=True)
            assert (result.returncode == 0) == ok, (script, result.stdout, result.stderr)
            assert 'synthetic-secret' not in result.stdout + result.stderr + calls.read_text()
            passed += 1
            return [json.loads(line) for line in calls.read_text().splitlines()]

        for fail in ('mysqldump', 'gzip', 'scp', 'ssh', 'pending', ''):
            directory = root / ('backup-' + (fail or 'success'))
            directory.mkdir(mode=0o700)
            old = directory / 'repair_test-20200101T030000Z-ABC123.sql.gz'
            old.write_bytes(gzip.compress(b'old'))
            os.utime(old, (time.time() - 15 * 86400,) * 2)
            recent = directory / 'repair_test-20200101T030000Z-ABC124.sql.gz'
            recent.write_bytes(gzip.compress(b'recent'))
            os.utime(recent, (time.time() - 13 * 86400,) * 2)
            unrelated = directory / 'another.sql.gz'
            unrelated.write_text('keep')
            remote = ['-', '-'] if fail == 'pending' else ['offsite', '/private/backups']
            seen = run('backup.sh', [cnf, 'repair_test', directory, *remote], not fail, fail)
            assert old.exists() == bool(fail), fail
            assert recent.exists() and unrelated.exists()
            assert not (directory / '.backup-repair_test.lock').exists()
            assert not list(directory.glob('.work-*'))
            if fail in ('mysqldump', 'gzip'):
                assert not any(x[0] in ('scp', 'ssh') for x in seen)
                assert len(list(directory.glob('*.sql.gz'))) == 3
            else:
                assert len(list(directory.glob('*.sql.gz'))) == (3 if not fail else 4)
            if not fail:
                dump = next(x for x in seen if x[0] == 'mysqldump')
                assert dump[1] == f'--defaults-file={cnf}'
                assert '--databases' not in dump and '--set-gtid-purged=OFF' in dump
                assert '--triggers' in dump
                assert any(x[0] == 'ssh' for x in seen)

        directory = root / 'backup-success'
        for db, alias, remote_dir in [('bad*', 'offsite', '/safe'),
                                       ('repair_test', '-option', '/safe'),
                                       ('repair_test', 'offsite', '/bad/../path'),
                                       ('repair_test', 'offsite', '/bad;touch-x'),
                                       ('repair_test', 'offsite', '/')]:
            assert not run('backup.sh', [cnf, db, directory, alias, remote_dir], False)
        cnf.chmod(0o644)
        assert not run('backup.sh', [cnf, 'repair_test', directory, '-', '-'], False)
        cnf.chmod(0o600)
        lock = directory / '.backup-repair_test.lock'
        lock.mkdir()
        assert not run('backup.sh', [cnf, 'repair_test', directory, '-', '-'], False)
        lock.rmdir()

        archive = root / 'synthetic.sql.gz'
        archive.write_bytes(gzip.compress(b'CREATE TABLE synthetic (id INT);'))
        base = [cnf, 'repair_test', archive, 'RESTORE:repair_test']
        seen = run('restore.sh', base, True)
        assert imported.read_bytes() == gzip.decompress(archive.read_bytes())
        assert seen[-1][0] == 'mysql' and seen[-1][-1] == '--database=repair_test'
        imported.unlink()
        run('restore.sh', base, False, 'mysql')
        assert not imported.exists()
        for db, confirm in [('repair_test', 'RESTORE:other'), ('mysql', 'RESTORE:mysql'),
                            ('MySQL', 'RESTORE:MySQL'),
                            ('bad*', 'RESTORE:bad*')]:
            assert not run('restore.sh', [cnf, db, archive, confirm], False)
        for content in (b'corrupt', gzip.compress(b'') , archive.read_bytes()[:-3]):
            archive.write_bytes(content)
            seen = run('restore.sh', base, False)
            assert not any(x[0] == 'mysql' for x in seen)
        assert not run('deploy.sh', [], False)
        assert not run('deploy.sh', ['/bad', 'not-a-revision', archive, 'APPLY:x'], False)
        release = root / 'release'
        release.mkdir()
        for name in ('artisan', 'vendor/autoload.php', 'public/build/manifest.json',
                     '.env', 'storage/framework/down'):
            file = release / name
            file.parent.mkdir(parents=True, exist_ok=True)
            file.touch()
        archive.write_bytes(gzip.compress(b'synthetic'))
        args = [tools / 'php', 'a' * 40, archive, 'APPLY:' + 'a' * 40]
        seen = run('deploy.sh', args, True, cwd=release)
        assert [x[2] for x in seen if x[0] == 'php'] == [
            'migrate', 'config:cache', 'route:cache', 'view:cache']
        assert (release / 'storage/framework/down').exists()
        for action in ('migrate', 'config:cache', 'route:cache', 'view:cache'):
            seen = run('deploy.sh', args, False, cwd=release, extra={'PHP_FAIL': action})
            assert [x[2] for x in seen if x[0] == 'php'][-1] == action
            assert (release / 'storage/framework/down').exists()
        seen = run('deploy.sh', args, False, cwd=release, extra={'DIRTY': '1'})
        assert not any(x[0] == 'php' for x in seen)
        (release / 'storage/framework/down').unlink()
        seen = run('deploy.sh', args, False, cwd=release)
        assert not any(x[0] == 'php' for x in seen)
    print(f'PASS {passed} host safety cases (stub tools only; NOT a MySQL backup/restore PASS)')


if __name__ == '__main__':
    main()
