#!/usr/bin/env python3
"""Check the standalone local stack without loading production credentials."""
import json
import os
from pathlib import Path
import subprocess

root = Path(__file__).resolve().parents[1]
environment = {**os.environ, 'COMPOSE_DISABLE_ENV_FILE': '1', 'LOCAL_UID': '1001', 'LOCAL_GID': '1001', 'LOCAL_WEB_PORT': '15174'}
config = json.loads(subprocess.check_output(
    ['docker', 'compose', '-f', str(root/'compose.yaml'), 'config', '--format', 'json'], env=environment))
services = config['services']
assert set(services) == {'web', 'api', 'postgres'}
assert services['api']['user'] == '1001:1001'
assert services['web']['user'] == '1001:1001'
assert services['api']['build']['target'] == 'development'
assert services['web']['environment']['API_PROXY_TARGET'] == 'http://api:8000'
assert services['web']['ports'][0]['host_ip'] == '127.0.0.1'
assert services['web']['ports'][0]['published'] == '15174'
assert not services['postgres'].get('ports')
assert not services['api'].get('ports')
assert services['api']['depends_on']['postgres']['condition'] == 'service_healthy'
assert services['web']['depends_on']['api']['condition'] == 'service_healthy'
assert services['api']['environment']['APP_ENV'] == 'dev'
assert services['api']['environment']['COMPOSER_HOME'] == '/tmp/composer'
assert services['api']['environment']['STORAGE_ROOT'] == '/app/apps/api/var/local-files'
assert services['api']['environment']['TEST_STORAGE_ROOT'] == '/app/apps/api/var/local-test-files'
assert 'POLZA_API_KEY' not in services['web']['environment']
web_mounts = services['web']['volumes']
assert any(mount['target'] == '/app/node_modules' and mount['type'] == 'volume' for mount in web_mounts)
assert any(mount['target'] == '/app/apps/web' and mount['type'] == 'bind' for mount in web_mounts)
assert not any(mount.get('target') in ['/app', '/app/apps/api'] for mount in web_mounts)
assert services['web']['build']['args']['LOCAL_UID'] == '1001'
assert services['web']['build']['args']['LOCAL_GID'] == '1001'
print('Local Compose: isolated dev services, checkout identity, private DB/API and Vite proxy OK.')
