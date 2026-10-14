#!/usr/bin/env python3
"""API schema/parity checks and bounded calculation load smoke."""
import argparse
from concurrent.futures import ThreadPoolExecutor
import json
from pathlib import Path
from statistics import median
from time import perf_counter
from urllib.request import Request, urlopen
from urllib.error import HTTPError
parser = argparse.ArgumentParser()
parser.add_argument('url')
args = parser.parse_args()
root = Path(__file__).resolve().parents[1]
fixtures = json.loads((root/'contracts/fixtures/prototype-v0.json').read_text())
max_delta = 0

def compare(expected, actual, path=''):
    global max_delta
    if isinstance(expected, dict):
        assert set(expected) == set(actual), path
        for key in expected: compare(expected[key], actual[key], path+'.'+key)
    elif isinstance(expected, list):
        assert len(expected) == len(actual), path
        for i, value in enumerate(expected): compare(value, actual[i], path+f'[{i}]')
    elif isinstance(expected, (int, float)):
        delta = abs(expected-actual)
        max_delta = max(max_delta, delta)
        assert delta <= 1e-9, (path, expected, actual, delta)
    else: assert expected == actual, (path, expected, actual)

def calculate(fixture):
    started = perf_counter()
    request = Request(args.url.rstrip('/')+'/api/prototype/v0/calculate', data=json.dumps(fixture['input']).encode(), headers={'Content-Type':'application/json','X-Correlation-ID':'api-smoke'})
    with urlopen(request, timeout=15) as response:
        assert response.status == 200
        assert response.headers['X-Correlation-ID'] == 'api-smoke'
        compare(fixture['expected'], json.load(response))
    return 1000*(perf_counter()-started)
for fixture in fixtures: calculate(fixture)
try:
    oversized = Request(args.url.rstrip('/')+'/api/prototype/v0/calculate', data=b'x'*65537, headers={'Content-Type':'application/json'})
    urlopen(oversized, timeout=15)
    raise AssertionError('Oversized request was accepted')
except HTTPError as error:
    assert error.code == 413
    assert error.headers.get_content_type() == 'application/problem+json'
    assert json.load(error)['status'] == 413
with ThreadPoolExecutor(max_workers=4) as pool:
    elapsed = sorted(pool.map(calculate, [fixtures[i % len(fixtures)] for i in range(100)]))
print(json.dumps({'fixtures': len(fixtures), 'requests':100, 'medianMs':round(median(elapsed),2), 'p95Ms':round(elapsed[94],2), 'maximumNumericDelta':max_delta}, indent=2))
