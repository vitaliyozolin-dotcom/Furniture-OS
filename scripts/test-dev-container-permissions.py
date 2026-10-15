#!/usr/bin/env python3
"""Regression: a non-1000 checkout owner can create Composer/runtime directories."""
import json
import os
from pathlib import Path
import shutil
import subprocess
import tempfile

root = Path(__file__).resolve().parents[1]
with tempfile.TemporaryDirectory(prefix='furniture-ci-permissions-') as directory:
    checkout = Path(directory)
    checkout.chmod(0o755)
    (checkout/'apps/api').mkdir(parents=True)
    shutil.copytree(root/'infra', checkout/'infra', ignore=shutil.ignore_patterns('.env'))
    shutil.copyfile(root/'infra/.env.example', checkout/'infra/.env')
    compose = ['docker', 'compose', '--project-name', f'furniture-permissions-{os.getpid()}',
               '-f', str(checkout/'infra/compose.yaml'), '-f', str(checkout/'infra/compose.dev.yaml')]
    environment = {**os.environ, 'LOCAL_UID': '1001', 'LOCAL_GID': '1001'}
    helper = ['docker', 'run', '--rm', '--network=none', '--user', '0:0',
              '-v', f'{checkout}:/checkout', 'furniture-api-dev']
    subprocess.run(helper+['chown', '-R', '1001:1001', '/checkout'], check=True)
    try:
        resolved = json.loads(subprocess.check_output(compose+['config', '--format', 'json'], env=environment))
        probe = '''foreach (["vendor", "var/cache", "var/log", "var/test-files"] as $path) {
            if (!mkdir($path, 0775, true)) { exit(1); }
            if (file_put_contents($path."/write-probe", "ok") === false) { exit(1); }
        }'''
        subprocess.run(compose+['run', '--rm', '--no-deps', '-T', 'api', 'php', '-r', probe], env=environment, check=True)
        assert resolved['services']['api']['user'] == '1001:1001', 'Compose ignored checkout identity'
        for path in ['vendor', 'var/cache', 'var/log', 'var/test-files']:
            assert (checkout/'apps/api'/path/'write-probe').read_text() == 'ok'
        print('Non-1000 checkout: Composer and runtime directories are writable.')
    finally:
        subprocess.run(compose+['down', '--volumes'], env=environment, check=False)
        subprocess.run(helper+['chown', '-R', f'{os.getuid()}:{os.getgid()}', '/checkout'], check=True)
