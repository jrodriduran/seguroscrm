#!/usr/bin/env python3
"""Private backups for the existing single-instance Docker installation (Linux)."""

import argparse
import fcntl
import gzip
import hashlib
import json
import os
from pathlib import Path
import shutil
import subprocess
import sys
import tarfile
import tempfile
from datetime import datetime, timedelta, timezone


def now():
    return datetime.now(timezone.utc)


def write_json(path, value):
    temporary = path.with_suffix('.tmp')
    temporary.write_text(json.dumps(value, indent=2) + '\n')
    os.chmod(temporary, 0o600)
    os.replace(temporary, path)


def digest(path):
    with path.open('rb') as stream:
        return hashlib.file_digest(stream, 'sha256').hexdigest()


def dump_database(container, target):
    # Credentials remain inside the database container; never put values in argv.
    command = [
        'docker', 'exec', container, 'sh', '-c',
        ': "${MYSQL_ROOT_PASSWORD:?missing root password}"; '
        ': "${MYSQL_DATABASE:?missing database name}"; '
        'export MYSQL_PWD="$MYSQL_ROOT_PASSWORD"; '
        'exec mysqldump --user=root --single-transaction --quick '
        '--no-tablespaces --set-gtid-purged=OFF --routines --events --triggers "$MYSQL_DATABASE"',
    ]
    # Do not include database diagnostics in logs: they can contain client data.
    with tempfile.TemporaryFile() as errors:
        process = subprocess.Popen(command, stdout=subprocess.PIPE, stderr=errors)
        try:
            with gzip.open(target, 'wb') as output:
                shutil.copyfileobj(process.stdout, output)
            process.stdout.close()
            if process.wait() != 0:
                raise RuntimeError('database dump failed')
        finally:
            if process.poll() is None:
                process.kill()
                process.wait()
    with gzip.open(target, 'rb') as stream:
        size = 0
        for block in iter(lambda: stream.read(1024 * 1024), b''):
            size += len(block)
        if size < 100:
            raise RuntimeError('database dump is empty or incomplete')


def verify(directory):
    manifest = json.loads((directory / 'manifest.json').read_text())
    if manifest.get('application') != 'seguroscrm-lan':
        raise RuntimeError('unrecognized backup')
    for name in ('database.sql.gz', 'storage-and-config.tar.gz'):
        path = directory / name
        if path.is_symlink() or digest(path) != manifest['sha256'][name]:
            raise RuntimeError('backup checksum mismatch')
    return manifest


def prune(destination, keep_days, latest):
    cutoff = now() - timedelta(days=keep_days)
    for candidate in destination.glob('backup-*'):
        if candidate == latest or candidate.is_symlink() or not candidate.is_dir():
            continue
        try:
            manifest = json.loads((candidate / 'manifest.json').read_text())
            created = datetime.fromisoformat(manifest['created_at'])
            if manifest.get('application') != 'seguroscrm-lan' or created >= cutoff:
                continue
        except (OSError, ValueError, KeyError):
            continue
        # Only complete, recognized backups directly within this destination.
        if candidate.resolve().parent == destination:
            shutil.rmtree(candidate)


def create_backup(project, destination, container, keep_days):
    if not (project / '.env').is_file() or (project / '.env').is_symlink():
        raise RuntimeError('project needs a regular .env file')
    if not (project / 'storage/app').is_dir():
        raise RuntimeError('project storage is missing')
    created = now()
    staging = Path(tempfile.mkdtemp(prefix='.incomplete-', dir=destination))
    complete = destination / ('backup-' + created.strftime('%Y%m%dT%H%M%S%fZ'))
    try:
        dump_database(container, staging / 'database.sql.gz')
        with tarfile.open(staging / 'storage-and-config.tar.gz', 'w:gz') as archive:
            archive.add(project / '.env', arcname='.env')
            archive.add(project / 'storage/app', arcname='storage/app')
        with tarfile.open(staging / 'storage-and-config.tar.gz', 'r:gz') as archive:
            if not {'.env', 'storage/app'}.issubset(archive.getnames()):
                raise RuntimeError('backup archive is incomplete')
        manifest = {
            'application': 'seguroscrm-lan',
            'created_at': created.isoformat(),
            'sha256': {name: digest(staging / name) for name in ('database.sql.gz', 'storage-and-config.tar.gz')},
        }
        write_json(staging / 'manifest.json', manifest)
        for child in staging.iterdir():
            os.chmod(child, 0o600)
        verify(staging)
        os.replace(staging, complete)
        write_json(destination / 'latest.json', {'backup': complete.name, 'created_at': manifest['created_at']})
        prune(destination, keep_days, complete)
        return complete
    finally:
        if staging.exists():
            shutil.rmtree(staging)


def check(destination, max_age_hours):
    status = json.loads((destination / 'last-run.json').read_text())
    if status.get('status') != 'success':
        raise RuntimeError('last backup attempt failed')
    latest = json.loads((destination / 'latest.json').read_text())
    directory = destination / latest['backup']
    if directory.is_symlink() or directory.resolve().parent != destination:
        raise RuntimeError('invalid backup location')
    manifest = verify(directory)
    age = now() - datetime.fromisoformat(manifest['created_at'])
    if age < timedelta(0) or age > timedelta(hours=max_age_hours):
        raise RuntimeError('backup is stale or clock is incorrect')
    return directory


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--project', type=Path, default=Path('/home/admin1/seguroscrm'))
    parser.add_argument('--destination', type=Path, default=Path('/home/admin1/seguroscrm-backups'))
    parser.add_argument('--container', default='seguroscrm_db')
    parser.add_argument('--keep-days', type=int, default=14)
    parser.add_argument('--check', action='store_true')
    parser.add_argument('--max-age-hours', type=int, default=26)
    args = parser.parse_args()
    os.umask(0o077)
    project, destination = args.project.resolve(), args.destination.resolve()
    if args.keep_days < 1 or args.max_age_hours < 1 or destination == Path('/') or destination.is_relative_to(project):
        parser.error('use positive retention/age and a destination outside the project')
    destination.mkdir(parents=True, exist_ok=True, mode=0o700)
    os.chmod(destination, 0o700)
    with (destination / '.lock').open('a') as lock:
        if not args.check:
            try:
                fcntl.flock(lock, fcntl.LOCK_EX | fcntl.LOCK_NB)
            except BlockingIOError:
                print(json.dumps({'status': 'skipped', 'reason': 'backup already running'}))
                return 0
        try:
            directory = check(destination, args.max_age_hours) if args.check else create_backup(project, destination, args.container, args.keep_days)
            if not args.check:
                write_json(destination / 'last-run.json', {'status': 'success', 'checked_at': now().isoformat(), 'backup': directory.name})
            print(json.dumps({'status': 'healthy' if args.check else 'success', 'backup': directory.name}))
            return 0
        except Exception as error:
            # Persist failures, without raw database/process output or secrets.
            message = str(error) if isinstance(error, RuntimeError) else type(error).__name__
            if not args.check:
                write_json(destination / 'last-run.json', {'status': 'failed', 'checked_at': now().isoformat(), 'reason': message})
            print(json.dumps({'status': 'failed', 'reason': message}), file=sys.stderr)
            return 1


if __name__ == '__main__':
    sys.exit(main())
