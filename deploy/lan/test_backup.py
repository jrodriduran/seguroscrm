"""Failure-path tests for the Linux backup runner; never touch Docker or real data."""

import gzip
import importlib.util
import json
import os
from pathlib import Path
import subprocess
import sys
import tarfile
import tempfile
import unittest
from datetime import timedelta
from unittest.mock import patch

spec = importlib.util.spec_from_file_location('backup', Path(__file__).with_name('backup.py'))
backup = importlib.util.module_from_spec(spec)
spec.loader.exec_module(backup)


class BackupTests(unittest.TestCase):
    def setUp(self):
        self.temporary = tempfile.TemporaryDirectory()
        self.addCleanup(self.temporary.cleanup)
        self.root = Path(self.temporary.name)
        self.project = self.root / 'project'
        (self.project / 'storage/app/public').mkdir(parents=True)
        (self.project / '.env').write_text('APP_KEY=synthetic-key\n')
        (self.project / 'storage/app/public/document.txt').write_text('synthetic document')
        self.destination = self.root / 'backups'
        self.destination.mkdir(mode=0o700)

    @staticmethod
    def fake_dump(container, target):
        with gzip.open(target, 'wb') as output:
            output.write(b'-- synthetic SQL dump\n' * 10)

    def create(self):
        with patch.object(backup, 'dump_database', self.fake_dump):
            return backup.create_backup(self.project, self.destination, 'fake-db', 14)

    def mark_success(self, directory):
        backup.write_json(self.destination / 'last-run.json', {'status': 'success', 'backup': directory.name})

    def test_complete_backup_includes_documents_config_and_checksums(self):
        directory = self.create()
        self.mark_success(directory)
        self.assertEqual(backup.check(self.destination, 26), directory)
        with tarfile.open(directory / 'storage-and-config.tar.gz') as archive:
            self.assertEqual(archive.extractfile('.env').read(), b'APP_KEY=synthetic-key\n')
            self.assertEqual(archive.extractfile('storage/app/public/document.txt').read(), b'synthetic document')
        self.assertTrue(all(path.stat().st_mode & 0o077 == 0 for path in directory.iterdir()))

    def test_failed_dump_does_not_replace_previous_backup(self):
        previous = self.create()
        with patch.object(backup, 'dump_database', side_effect=RuntimeError('database dump failed')):
            with self.assertRaises(RuntimeError):
                backup.create_backup(self.project, self.destination, 'fake-db', 14)
        self.assertEqual(json.loads((self.destination / 'latest.json').read_text())['backup'], previous.name)
        self.assertEqual(list(self.destination.glob('.incomplete-*')), [])

    def test_subprocess_failure_is_not_a_successful_compressed_backup(self):
        original = subprocess.Popen
        def failing_process(*args, **kwargs):
            return original([sys.executable, '-c', "print('partial output'); raise SystemExit(2)"], **kwargs)
        with patch.object(backup.subprocess, 'Popen', side_effect=failing_process):
            with self.assertRaisesRegex(RuntimeError, 'dump failed'):
                backup.dump_database('fake-db', self.root / 'failed.gz')

    def test_successful_but_empty_dump_is_rejected(self):
        original = subprocess.Popen
        with patch.object(backup.subprocess, 'Popen', side_effect=lambda *a, **k: original([sys.executable, '-c', 'pass'], **k)):
            with self.assertRaisesRegex(RuntimeError, 'empty'):
                backup.dump_database('fake-db', self.root / 'empty.gz')

    def test_modified_backup_fails_health_check(self):
        directory = self.create()
        self.mark_success(directory)
        (directory / 'database.sql.gz').write_bytes(b'corrupted')
        with self.assertRaisesRegex(RuntimeError, 'checksum'):
            backup.check(self.destination, 26)

    def test_failed_last_attempt_is_reported_even_with_fresh_previous_backup(self):
        self.create()
        backup.write_json(self.destination / 'last-run.json', {'status': 'failed'})
        with self.assertRaisesRegex(RuntimeError, 'attempt failed'):
            backup.check(self.destination, 26)

    def test_stale_backup_fails_health_check(self):
        directory = self.create()
        self.mark_success(directory)
        with patch.object(backup, 'now', return_value=backup.now() + timedelta(hours=27)):
            with self.assertRaisesRegex(RuntimeError, 'stale'):
                backup.check(self.destination, 26)

    def test_retention_preserves_latest_unknown_directories_and_external_symlinks(self):
        oldest = self.create()
        manifest_path = oldest / 'manifest.json'
        manifest = json.loads(manifest_path.read_text())
        manifest['created_at'] = (backup.now() - timedelta(days=20)).isoformat()
        backup.write_json(manifest_path, manifest)
        outside = self.root / 'outside'
        outside.mkdir()
        (outside / 'keep.txt').write_text('keep')
        (self.destination / 'backup-external').symlink_to(outside)
        unknown = self.destination / 'backup-unknown'
        unknown.mkdir()
        latest = self.create()
        self.assertFalse(oldest.exists())
        self.assertTrue(latest.exists())
        self.assertTrue(unknown.exists())
        self.assertTrue((outside / 'keep.txt').exists())

    def test_health_check_rejects_a_redirected_latest_backup(self):
        outside = self.root / 'outside'
        outside.mkdir()
        (self.destination / 'backup-external').symlink_to(outside)
        backup.write_json(self.destination / 'last-run.json', {'status': 'success'})
        backup.write_json(self.destination / 'latest.json', {'backup': 'backup-external'})
        with self.assertRaisesRegex(RuntimeError, 'invalid backup location'):
            backup.check(self.destination, 26)


if __name__ == '__main__':
    unittest.main()
