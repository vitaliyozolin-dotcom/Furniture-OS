#!/bin/sh
set -eu
cd /app
lock_hash=$(sha256sum package.json package-lock.json apps/web/package.json | sha256sum | cut -d ' ' -f 1)
if [ ! -f node_modules/.furniture-lock ] || [ "$(cat node_modules/.furniture-lock)" != "$lock_hash" ]; then
    npm ci --no-audit --no-fund
    printf '%s\n' "$lock_hash" > node_modules/.furniture-lock
fi
exec npm run dev --workspace @furniture-os/web -- --port 5173 --strictPort
