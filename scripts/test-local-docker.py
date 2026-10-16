#!/usr/bin/env python3
"""Boot a clean local checkout, verify live source/proxy and restart persistence."""
import json
import os
from pathlib import Path
import shutil
import socket
import subprocess
import tempfile
from urllib.request import urlopen

root = Path(__file__).resolve().parents[1]
with socket.socket() as port_probe:
    port_probe.bind(('127.0.0.1', 0))
    port = port_probe.getsockname()[1]

with tempfile.TemporaryDirectory(prefix='furniture-local-docker-') as temporary:
    checkout = Path(temporary)

    def ignore(directory, names):
        excluded = {'node_modules', 'vendor', 'var', 'dist', '.env.local', '.env.local.php',
                    'coverage', 'test-results', 'playwright-report', '__pycache__', '.php-cs-fixer.cache'}
        if Path(directory).name == 'infra':
            excluded.add('.env')
        return excluded.intersection(names)

    for name in ['apps', 'contracts', 'infra', 'scripts']:
        shutil.copytree(root/name, checkout/name, ignore=ignore)
    for name in ['package.json', 'package-lock.json', 'compose.yaml', '.dockerignore']:
        shutil.copyfile(root/name, checkout/name)
    assert not (checkout/'apps/api/vendor').exists()
    assert not (checkout/'node_modules').exists()
    assert not (checkout/'infra/.env').exists()
    (checkout/'apps/api/.env.local').write_text('AI_ENABLED=0\nPOLZA_API_KEY=local-test-not-a-key\n')
    project = f'furniture-local-smoke-{os.getpid()}'
    environment = {**os.environ, 'COMPOSE_DISABLE_ENV_FILE': '1', 'LOCAL_WEB_PORT': str(port),
                   'LOCAL_UID': str(os.getuid()), 'LOCAL_GID': str(os.getgid())}
    compose = ['docker', 'compose', '--project-name', project, '-f', str(checkout/'compose.yaml')]

    def run(*arguments, capture=False):
        return subprocess.run(compose+list(arguments), env=environment, cwd=checkout, check=True,
                              text=True, stdout=subprocess.PIPE if capture else None)

    def get(path):
        with urlopen(f'http://127.0.0.1:{port}{path}', timeout=15) as response:
            assert response.status == 200
            return response.read().decode()

    try:
        run('up', '--build', '-d', '--wait', '--wait-timeout', '300')
        assert '<div id="root">' in get('/')
        assert 'WebSocket' in get('/@vite/client')
        assert json.loads(get('/health/ready'))['status'] == 'ready'
        subprocess.run(['python3', str(root/'scripts/api-smoke.py'), f'http://127.0.0.1:{port}'], check=True)
        source = checkout/'apps/web/src/shared/ui/AppHeader.tsx'
        source.write_text(source.read_text()+"\nexport const LOCAL_DOCKER_PROBE = 'local-docker-bind-probe';\n")
        assert 'local-docker-bind-probe' in get('/src/shared/ui/AppHeader.tsx')
        run('exec', '-T', 'api', 'php', '-r', 'require "vendor/autoload.php"; (new Symfony\\Component\\Dotenv\\Dotenv())->bootEnv(".env"); exit($_ENV["POLZA_API_KEY"] === "local-test-not-a-key" ? 0 : 1);')
        run('exec', '-T', 'api', 'php', 'bin/console', 'app:ai:render', '--help', capture=True)
        run('exec', '-T', 'api', 'php', 'bin/console', 'app:storage:probe', '--write=local-docker-smoke')
        run('down')  # Keep this test's database volume for the restart check.
        run('up', '-d', '--wait', '--wait-timeout', '300')
        assert json.loads(get('/health/ready'))['status'] == 'ready'
        probe = run('exec', '-T', 'api', 'php', 'bin/console', 'app:storage:probe', capture=True)
        assert json.loads(probe.stdout)['status'] == 'ok'
        print('Clean local checkout: dependency installation, migrations, Vite source updates, API proxy, local AI env and restart persistence OK.')
    except BaseException:
        subprocess.run(compose+['logs', '--no-color', '--tail', '60'], env=environment, cwd=checkout, check=False)
        raise
    finally:
        # Only resources owned by this disposable test project are removed.
        subprocess.run(compose+['down', '--volumes', '--remove-orphans'], env=environment, cwd=checkout, check=False)
