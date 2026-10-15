#!/usr/bin/env python3
"""Consistent cold backup of one Compose project; never destroys a target."""
import argparse
import hashlib
import json
import os
from pathlib import Path
import subprocess

parser = argparse.ArgumentParser()
parser.add_argument('directory', type=Path)
parser.add_argument('--project', default='furniture-os')
args = parser.parse_args()
root = Path(__file__).resolve().parents[1]
compose = ['docker', 'compose', '--project-name', args.project, '--env-file', str(root/'infra/.env'), '-f', str(root/'infra/compose.yaml')]
args.directory.mkdir(parents=True, exist_ok=False)
os.chmod(args.directory, 0o700)
try:
    subprocess.run(compose+['stop', 'web', 'api'], check=True)
    subprocess.run(compose+['run', '--rm', '--no-deps', '-T', 'api', 'php', 'bin/console', 'app:storage:probe'], check=True)
    with (args.directory/'database.dump').open('wb') as output:
        subprocess.run(compose+['exec', '-T', 'postgres', 'pg_dump', '-U', 'furniture', '-d', 'furniture', '-Fc'], stdout=output, check=True)
    with (args.directory/'files.tar').open('wb') as output:
        subprocess.run(compose+['run', '--rm', '--no-deps', '-T', 'api', 'tar', '-C', '/var/lib/furniture/files', '-cf', '-', '.'], stdout=output, check=True)
    manifest = {name: hashlib.sha256((args.directory/name).read_bytes()).hexdigest() for name in ['database.dump', 'files.tar']}
    (args.directory/'manifest.json').write_text(json.dumps(manifest, indent=2)+'\n')
finally:
    subprocess.run(compose+['up', '-d', '--wait', 'api', 'web'], check=True)
print('Backup complete:', args.directory)
