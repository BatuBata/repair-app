#!/usr/bin/env python3
"""Validate a CI release before extracting anything; private/live paths are forbidden."""
from pathlib import Path, PurePosixPath
import re
import sys
import tarfile

TOP = {'app', 'bootstrap', 'config', 'database', 'resources', 'routes', 'public', 'vendor',
       'artisan', 'composer.json', 'composer.lock', '.release-revision'}
PROTECTED = ('bootstrap/cache', 'public/storage')
REQUIRED = {'artisan', 'composer.json', 'composer.lock', 'vendor/autoload.php',
            'public/index.php', 'public/build/manifest.json', '.release-revision'}


def extract(archive, destination, revision):
    if not re.fullmatch(r'[0-9a-f]{40}', revision) or any(destination.iterdir()):
        raise ValueError('Invalid revision or nonempty extraction directory')
    with tarfile.open(archive, 'r:gz') as release:
        members = release.getmembers()
        names = set()
        total = 0
        for member in members:
            name = member.name.rstrip('/')
            path = PurePosixPath(name)
            if (not name or path.is_absolute() or name != path.as_posix() or '..' in path.parts
                    or path.parts[0] not in TOP or name in names
                    or any(name == p or name.startswith(p + '/') for p in PROTECTED)
                    or not (member.isfile() or member.isdir())):
                raise ValueError('Unsafe archive entry')
            names.add(name)
            total += member.size
            member.mode = 0o750 if member.isdir() or member.mode & 0o111 else 0o640
        if len(members) > 100000 or total > 512 * 1024 * 1024 or not REQUIRED <= names:
            raise ValueError('Release incomplete or oversized')
        stamp = release.extractfile('.release-revision')
        if stamp is None or stamp.read(100).decode().strip() != revision:
            raise ValueError('Archive revision mismatch')
        release.extractall(destination, members=members)


if __name__ == '__main__':
    try:
        extract(sys.argv[1], Path(sys.argv[2]), sys.argv[3])
    except (ValueError, OSError, tarfile.TarError, UnicodeError, IndexError):
        sys.exit('Release archive validation failed; live files untouched')
