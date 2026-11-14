#!/usr/bin/env python3
"""Disposable archive/receiver tests. Never accesses production, SSH, or a database."""
import hashlib
import importlib.util
import io
import os
from pathlib import Path
import shutil
import subprocess
import tarfile
import tempfile

SCRIPTS = Path(__file__).resolve().parent
REVISION = 'a' * 40
spec = importlib.util.spec_from_file_location('archive', SCRIPTS / 'ci-archive.py')
archive = importlib.util.module_from_spec(spec)
spec.loader.exec_module(archive)


def bundle(path, extra=None, revision=REVISION):
    files = {name: b'synthetic' for name in archive.REQUIRED}
    files['.release-revision'] = (revision + '\n').encode()
    files['public/new.js'] = b'new asset'
    with tarfile.open(path, 'w:gz') as release:
        for name, data in files.items():
            member = tarfile.TarInfo(name)
            member.size = len(data)
            release.addfile(member, io.BytesIO(data))
        if extra is not None:
            release.addfile(extra, io.BytesIO(b'x' * extra.size))


def main():
    passed = 0
    with tempfile.TemporaryDirectory(prefix='repair-cicd-check-') as tmp:
        root = Path(tmp)
        package = root / 'release.tar.gz'
        for name in ['../escape', '/tmp/escape', '.env', 'storage/customer',
                     'public/storage/logo.png', 'bootstrap/cache/config.php', 'public/../.env']:
            bundle(package, tarfile.TarInfo(name))
            target = root / 'extract'
            target.mkdir()
            try:
                archive.extract(package, target, REVISION)
            except ValueError:
                assert not list(target.iterdir())
            else:
                raise AssertionError('Unsafe archive accepted: ' + name)
            target.rmdir()
            passed += 1
        for kind in [tarfile.SYMTYPE, tarfile.LNKTYPE, tarfile.FIFOTYPE]:
            member = tarfile.TarInfo('public/escape')
            member.type, member.linkname = kind, '../../.env'
            bundle(package, member)
            target.mkdir()
            try:
                archive.extract(package, target, REVISION)
            except ValueError:
                assert not list(target.iterdir())
            else:
                raise AssertionError('Nonregular entry accepted')
            target.rmdir()
            passed += 1
        bundle(package, revision='b' * 40)
        target.mkdir()
        try:
            archive.extract(package, target, REVISION)
        except ValueError:
            assert not list(target.iterdir())
        else:
            raise AssertionError('Wrong revision accepted')
        target.rmdir()
        passed += 1
        bundle(package)
        target.mkdir()
        archive.extract(package, target, REVISION)
        assert (target / 'public/new.js').read_bytes() == b'new asset'
        assert not (target / '.env').exists()
        passed += 1

        if b'GNU tar' in subprocess.run(['tar', '--version'], capture_output=True).stdout:
            fixture = root / 'package-source'
            fixture.mkdir()
            for name in archive.REQUIRED - {'.release-revision'}:
                file = fixture / name
                file.parent.mkdir(parents=True, exist_ok=True)
                file.write_text('synthetic')
            for name in ['app', 'config', 'database', 'resources', 'routes']:
                (fixture / name).mkdir()
                (fixture / name / '.gitkeep').touch()
            (fixture / 'bootstrap/cache').mkdir(parents=True)
            for name in ['.env', 'bootstrap/cache/config.php', 'public/storage/private-logo']:
                file = fixture / name
                file.parent.mkdir(parents=True, exist_ok=True)
                file.write_text('synthetic-private')
            subprocess.run(['git', 'init', '-q'], cwd=fixture, check=True)
            subprocess.run(['git', 'add', '.'], cwd=fixture, check=True)
            subprocess.run(['git', '-c', 'user.name=CI', '-c', 'user.email=ci@example.invalid',
                            '-c', 'commit.gpgsign=false', 'commit', '-qm', 'fixture'], cwd=fixture, check=True)
            pinned = subprocess.check_output(['git', 'rev-parse', 'HEAD'], cwd=fixture, text=True).strip()
            output = root / 'package-built.tar.gz'
            subprocess.run(['bash', str(SCRIPTS / 'ci-package.sh'), pinned, str(output)], cwd=fixture, check=True)
            extracted = root / 'package-extracted'
            extracted.mkdir()
            archive.extract(output, extracted, pinned)
            assert not (extracted / '.env').exists()
            assert not (extracted / 'bootstrap/cache').exists()
            assert not (extracted / 'public/storage').exists()
            passed += 1
        else:
            print('Production packager NOT_RUN: requires GNU tar; CI runs this case.')

        if shutil.which('flock') and shutil.which('rsync'):
            for failure in ['', 'command', 'digest', 'maintenance', 'platform', 'migrate', 'health']:
                case = root / ('case-' + (failure or 'success'))
                current, ops = case / 'app/current', case / 'ops'
                current.mkdir(parents=True)
                ops.mkdir()
                for name, value in {'.env': 'synthetic-private', 'storage/customer': 'keep-data',
                                    'bootstrap/cache/keep': 'keep-cache', '.git/HEAD': 'keep-git',
                                    'public/obsolete.js': 'remove-old-asset',
                                    'storage/app/public/logo.png': 'keep-logo'}.items():
                    file = current / name
                    file.parent.mkdir(parents=True, exist_ok=True)
                    file.write_text(value)
                (current / 'public/storage').symlink_to('../storage/app/public')
                tools = case / 'bin'
                tools.mkdir()
                php = tools / 'php'
                php.write_text('#!/usr/bin/env bash\nset -eu\nprintf "%s\\n" "$*" >> "$CALLS"\n'
                               '[[ $FAIL != platform || $1 != /usr/local/bin/composer ]] || exit 12\n'
                               '[[ $FAIL != migrate || ${2:-} != migrate ]] || exit 13\n')
                php.chmod(0o700)
                curl = tools / 'curl'
                curl.write_text('#!/usr/bin/env bash\n[[ $FAIL != health ]] || exit 22\nprintf 200\n')
                curl.chmod(0o700)
                shutil.copyfile(SCRIPTS / 'ci-archive.py', ops / 'ci-archive.py')
                source = (SCRIPTS / 'ci-remote.sh').read_text()
                source = source.replace('export PATH=/usr/local/bin:/usr/bin:/bin',
                                        'export PATH="' + str(tools) + ':$PATH"')
                source = source.replace('base=/var/www/demo-repair-app', 'base=' + str(case / 'app'))
                source = source.replace('ops=/home/muzib/deploy-demo-repair-app/cicd', 'ops=' + str(ops))
                source = source.replace('php=/usr/bin/php8.3', 'php=' + str(php)).replace('sleep 3', ':')
                receiver = case / 'receiver.sh'
                receiver.write_text(source)
                digest = hashlib.sha256(package.read_bytes()).hexdigest()
                command = f'deploy {REVISION} {digest}'
                if failure == 'command':
                    command = 'sh -c whoami'
                if failure == 'digest':
                    command = f'deploy {REVISION} ' + '0' * 64
                marker = current.parent / '.deploy-pending'
                if failure == 'maintenance':
                    marker.touch()
                calls = case / 'calls'
                env = dict(os.environ, SSH_ORIGINAL_COMMAND=command, FAIL=failure, CALLS=str(calls))
                result = subprocess.run(['bash', str(receiver)], input=package.read_bytes(),
                                        env=env, capture_output=True)
                assert (result.returncode == 0) == (not failure), (failure, result.stderr)
                assert b'synthetic-private' not in result.stdout + result.stderr
                for name, expected in [('.env', 'synthetic-private'), ('storage/customer', 'keep-data'),
                                       ('bootstrap/cache/keep', 'keep-cache'), ('.git/HEAD', 'keep-git'),
                                       ('public/storage/logo.png', 'keep-logo')]:
                    assert (current / name).read_text() == expected, (failure, name)
                assert (current / 'public/storage').is_symlink()
                assert marker.exists() == (failure in ['maintenance', 'migrate', 'health'])
                assert not list(ops.glob('.incoming.*'))
                if not failure:
                    assert not (current / 'public/obsolete.js').exists()
                    assert (current / '.release-revision').read_text().strip() == REVISION
                    assert (ops / 'last-success.json').is_file()
                    seen = calls.read_text()
                    assert 'migrate --force' in seen and 'config:cache' in seen
                    assert 'db:seed' not in seen and 'key:generate' not in seen
                else:
                    assert not (ops / 'last-success.json').exists()
                passed += 1
        else:
            print('Receiver integration NOT_RUN: requires Linux flock and rsync; CI runs these cases.')
    print(f'PASS: {passed} deployment safety cases')


if __name__ == '__main__':
    main()
