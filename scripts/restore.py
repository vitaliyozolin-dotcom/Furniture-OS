#!/usr/bin/env python3
"""Restore only into an empty, explicitly named Compose project."""
import argparse
import hashlib
import json
from pathlib import Path
import subprocess

parser = argparse.ArgumentParser()
parser.add_argument('directory', type=Path)
parser.add_argument('--project', required=True)
args = parser.parse_args()
root = Path(__file__).resolve().parents[1]
compose = ['docker', 'compose', '--project-name', args.project, '--env-file', str(root/'infra/.env'), '-f', str(root/'infra/compose.yaml')]
manifest = json.loads((args.directory/'manifest.json').read_text())
for name in ['database.dump', 'files.tar']:
    if hashlib.sha256((args.directory/name).read_bytes()).hexdigest() != manifest[name]:
        raise SystemExit('Backup checksum mismatch: '+name)
subprocess.run(compose+['up', '-d', '--wait', 'postgres'], check=True)
check = subprocess.check_output(compose+['exec', '-T', 'postgres', 'psql', '-U', 'furniture', '-d', 'furniture', '-Atc', "SELECT count(*) FROM information_schema.tables WHERE table_schema='public'"])
if check.strip() != b'0':
    raise SystemExit('Refusing to restore into a non-empty database. Use a new Compose project.')
subprocess.run(compose+['stop', 'api', 'web'], check=True)
# The destination files volume must also be empty. No overwrite/delete flags.
subprocess.run(compose+['run', '--rm', '--no-deps', '-T', 'api', 'sh', '-ec', 'test -z "$(ls -A /var/lib/furniture/files)"'], check=True)
with (args.directory/'database.dump').open('rb') as source:
    subprocess.run(compose+['exec', '-T', 'postgres', 'pg_restore', '-U', 'furniture', '-d', 'furniture', '--no-owner', '--exit-on-error'], stdin=source, check=True)
with (args.directory/'files.tar').open('rb') as source:
    subprocess.run(compose+['run', '--rm', '--no-deps', '-T', 'api', 'tar', '-C', '/var/lib/furniture/files', '-xf', '-'], stdin=source, check=True)
subprocess.run(compose+['up', '-d', '--wait', 'api', 'web'], check=True)
subprocess.run(compose+['exec', '-T', 'api', 'php', 'bin/console', 'app:storage:probe'], check=True)
print('Restore and metadata checksum verification complete.')
