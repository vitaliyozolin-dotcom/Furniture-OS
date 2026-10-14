FROM node:22-bookworm-slim AS build
WORKDIR /app
COPY package.json package-lock.json ./
COPY apps/web/package.json apps/web/package.json
RUN npm ci
COPY apps/web apps/web
COPY contracts contracts
ARG VITE_ENABLE_PROTOTYPE_V0_FALLBACK=false
ENV VITE_ENABLE_PROTOTYPE_V0_FALLBACK=$VITE_ENABLE_PROTOTYPE_V0_FALLBACK
RUN npm run build
FROM nginx:1.28-alpine
COPY infra/docker/nginx.conf /etc/nginx/conf.d/default.conf
COPY --from=build /app/apps/web/dist /usr/share/nginx/html
COPY apps/api/public/index.php /app/apps/api/public/index.php
