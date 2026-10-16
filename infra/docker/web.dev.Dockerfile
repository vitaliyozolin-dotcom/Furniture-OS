FROM node:22-bookworm-slim
WORKDIR /app
ARG LOCAL_UID=1000
ARG LOCAL_GID=1000
COPY package.json package-lock.json ./
COPY apps/web/package.json apps/web/package.json
RUN mkdir -p /app/apps/web/node_modules /tmp/npm \
    && chown "$LOCAL_UID:$LOCAL_GID" /app /app/apps /app/apps/web /app/apps/web/node_modules /tmp/npm
USER $LOCAL_UID:$LOCAL_GID
ENV NPM_CONFIG_CACHE=/tmp/npm
RUN npm ci --no-audit --no-fund \
    && sha256sum package.json package-lock.json apps/web/package.json | sha256sum | cut -d ' ' -f 1 > node_modules/.furniture-lock
COPY infra/docker/web-dev.sh /usr/local/bin/furniture-web-dev
EXPOSE 5173
CMD ["sh", "/usr/local/bin/furniture-web-dev"]
