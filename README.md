# Furniture OS

UI-прототип распашного шкафа на React с серверным compatibility-расчётом на PHP 8.4/Symfony 7.4 LTS. Техническая миграция подготовлена к приёмке; продуктовый MVP ещё не реализован. Формулы `prototype-v0` не подтверждены мебельным технологом.

- [Утверждённая архитектура](docs/project-plan.md)
- [План технической миграции](docs/technology-migration-plan.md)
- [Продуктовый план после отдельной приёмки](docs/product-development-plan.md)
- [Результаты проверок и открытые пункты приёмки](docs/adr/0004-migration-acceptance.md)
- [Границы runtime и точность compatibility pricing](docs/adr/0003-migration-runtime-boundaries.md)
- [Словарь](docs/domain-glossary.md), [context map](docs/context-map-prototype-v0.md)

## Полный стек

Требуются Docker с Compose v2.24.4+ и Python 3 для эксплуатационных скриптов. Node.js 22/npm 10 нужны для frontend-разработки. PHP на хосте не требуется.

```sh
cp infra/.env.example infra/.env
# Заменить APP_SECRET и POSTGRES_PASSWORD перед внешним размещением.
docker compose -f infra/compose.yaml build api web
docker compose -f infra/compose.yaml up -d --wait postgres
docker compose -f infra/compose.yaml run --rm --no-deps api php bin/console doctrine:migrations:migrate --no-interaction
docker compose -f infra/compose.yaml up -d --wait
```

Открыть http://127.0.0.1:8080. Если порт занят, задать `WEB_PORT` в `infra/.env` либо перед командами Compose. В текущей проверочной сессии используется http://127.0.0.1:18080.

Web и `/api` работают на одном origin за Nginx; БД и FPM не публикуются наружу. Данные PostgreSQL и приватные файлы живут в именованных volumes. Worker не запускается: фоновых пользовательских задач пока нет. GitHub Pages заменён проверками Docker-стека; новый frontend требует Symfony API.

## Разработка

Dev имеет отдельное имя Compose-проекта `furniture-os-dev`, отдельный PostgreSQL volume и каталог файлов. Тесты используют ещё одну БД с суффиксом `_test` и `apps/api/var/test-files`. UID/GID dev-контейнера задаются через `LOCAL_UID`/`LOCAL_GID` (по умолчанию 1000:1000). В GitHub Actions они берутся из runner, чтобы Composer и Symfony могли писать в checkout.

```sh
export LOCAL_UID="$(id -u)" LOCAL_GID="$(id -g)"
docker compose -f infra/compose.yaml -f infra/compose.dev.yaml build api
docker compose -f infra/compose.yaml -f infra/compose.dev.yaml run --rm --no-deps api composer install --no-interaction --prefer-dist
docker compose -f infra/compose.yaml -f infra/compose.dev.yaml up -d --wait postgres api
docker compose -f infra/compose.yaml -f infra/compose.dev.yaml exec -T api php bin/console doctrine:migrations:migrate --no-interaction
npm ci
npm run dev
```

Vite проксирует `/api` на http://127.0.0.1:8000. Можно переопределить `API_PROXY_TARGET`. После изменения OpenAPI выполнить `npm run api:generate`; generated-файл вручную не редактируется.

## Проверки

Локальная AI-интеграция (общий provider port и Nano Banana через Polza.AI): [настройка ключа и CLI-пример](docs/local-ai.md). Она выключена по умолчанию; ключ задаётся в игнорируемом `apps/api/.env.local`. Это согласованный внутренний эксперимент по [ADR-0005](docs/adr/0005-local-ai-integration.md).

```sh
npm run api:check
npm run check
npm run test:coverage
E2E_BASE_URL=http://127.0.0.1:8080/ npm run test:e2e
python3 scripts/api-smoke.py http://127.0.0.1:8080

docker compose -f infra/compose.yaml -f infra/compose.dev.yaml exec -T api composer quality
docker compose -f infra/compose.yaml -f infra/compose.dev.yaml exec -T api composer test:coverage
docker compose -f infra/compose.yaml -f infra/compose.dev.yaml exec -T -e XDEBUG_MODE=coverage api php -d memory_limit=1G -d pcov.enabled=0 vendor/bin/infection --threads=4 --no-progress
git diff --check
```

Перед первым E2E: `npx playwright install --with-deps chromium`. Без `E2E_BASE_URL` Playwright поднимает Vite; backend dev должен работать для `/api` и readiness. Для такого запуска Vite проксирует также `/health`.

Команда `composer test:coverage` очищает Symfony test cache без warmup перед измерением. Это включает ветви загружаемых при компиляции CLI-команд независимо от состояния локального кеша; CI использует ту же команду.

CI проверяет frontend lint/typecheck/tests/coverage/build, OpenAPI lint/generation/breaking changes, backend PSR-12/PHPStan/Deptrac/tests, PostgreSQL migrations с пустой и заполненной предыдущей схемы, storage contracts, настоящий Nginx/API/PostgreSQL E2E и backup/restore. Mutation tests запускаются отдельно каждую неделю и вручную. Настройка branch protection в GitHub остаётся действием владельца репозитория: workflow сам по себе не запрещает обход проверок администратором.

## Файлы, backup и restore

Публичного download/upload endpoint нет. Technical probe проверяет metadata → object size/checksum и object → metadata. Содержимое файла не хранится в БД; локальный адаптер заменяем через application-порт.

```sh
docker compose -f infra/compose.yaml exec -T api php bin/console app:storage:probe --write=manual-probe
docker compose -f infra/compose.yaml exec -T api php bin/console app:storage:probe
python3 scripts/backup.py /absolute/new/backup-directory
WEB_PORT=8081 python3 scripts/restore.py /absolute/new/backup-directory --project furniture-restore
```

Backup останавливает только web/API выбранного проекта, сохраняет согласованную пару БД/файлов и SHA-256 manifest, затем запускает сервисы. Указывать новый каталог вне Git; хранить копии на отдельном защищённом носителе. Restore сверяет manifest, отказывается от непустой БД/файлового volume, восстанавливает в явный Compose project и проверяет checksum. Использовать тот же release образов, что создал backup, затем при необходимости выполнить forward migrations. Повторный restore в уже заполненный target запрещён.

Для обновления существующей установки: backup → build новых images → отдельный job миграций → `up -d --wait` → API smoke/E2E. БД откатывается восстановлением в новый target; destructive down-миграций нет. При rollback приложения использовать сохранённые предыдущие images с совместимой схемой.

## Диагностика и внешнее размещение

```sh
docker compose -f infra/compose.yaml ps
docker compose -f infra/compose.yaml logs --tail 100 api web
curl -f http://127.0.0.1:8080/health/live
curl -f http://127.0.0.1:8080/health/ready
```

API возвращает `X-Correlation-ID`; ошибки имеют `application/problem+json`, внутренние сообщения 500 не раскрываются. Monolog пишет структурированные JSON logs, включая события ошибок. При внешнем размещении подключить сбор/оповещение по error logs, TLS reverse proxy на одном домене и согласованный backup schedule. Домен, российский провайдер, секреты, retention, RPO/RTO и доступы к внешнему окружению не выбраны; реальный production deploy не выполнен. Тестовый Compose не заменяет эти решения.

## Текущие продуктовые границы

Экран, открытие фасадов и кнопка продолжения сохранены. До подписанной parity оставлен выключенный флаг `VITE_ENABLE_PROTOTYPE_V0_FALLBACK`: для явного технического rollback можно собрать web с `--build-arg VITE_ENABLE_PROTOTYPE_V0_FALLBACK=true`. При обычной сборке движок исключается из bundle; отказ API не включает его автоматически. После приёмки parity флаг и compatibility runtime удаляются отдельным изменением. Форм размеров, входа, проектов/ревизий, документов, RFQ, предложений производителей, продуктового AI-сценария и 3D пока нет. Они реализуются по продуктовому плану после отдельной приёмки миграции и утверждения необходимых продуктовых правил. Отдельно разрешён и реализован локальный CLI-эксперимент AI; он не объявляет миграцию принятой.
