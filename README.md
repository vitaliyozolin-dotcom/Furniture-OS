# Furniture OS

UI-прототип распашного шкафа на React с серверным compatibility-расчётом на PHP 8.4/Symfony 7.4 LTS. Техническая миграция подготовлена к приёмке; продуктовый MVP ещё не реализован. Формулы `prototype-v0` не подтверждены мебельным технологом.

- [Утверждённая архитектура](docs/project-plan.md)
- [План технической миграции](docs/technology-migration-plan.md)
- [Продуктовый план после отдельной приёмки](docs/product-development-plan.md)
- [Результаты проверок и открытые пункты приёмки](docs/adr/0004-migration-acceptance.md)
- [Границы runtime и точность compatibility pricing](docs/adr/0003-migration-runtime-boundaries.md)
- [Словарь](docs/domain-glossary.md), [context map](docs/context-map-prototype-v0.md)
- [Пилот: вход, конструктор и публикация шкафа](docs/cabinet-pilot.md)

## Полный стек

Требуются Docker с Compose v2.24.4+ и Python 3 для эксплуатационных скриптов. Для локальной разработки через корневой Compose Node.js и PHP на хосте не требуются.

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

## Разработка в Docker

Из корня репозитория:

```sh
./scripts/dev.sh up --build -d --wait
```

Открыть http://127.0.0.1:5174. Скрипт автоматически передаёт UID/GID владельца checkout. При UID/GID 1000:1000 можно также использовать `docker compose up --build -d --wait`. Если порт занят: `LOCAL_WEB_PORT=15174 ./scripts/dev.sh up --build -d --wait`.

Корневой `compose.yaml` запускает отдельный проект `furniture-os-local`: Vite с горячей перезагрузкой, PHP 8.4/Symfony и PostgreSQL. При старте API устанавливаются Composer-зависимости и применяются миграции; frontend-зависимости поставляются в dev image и отдельных named volumes. При изменении package manifests/lockfile frontend повторно выполняет `npm ci` при перезапуске. БД доступна только внутри Docker, API — через Vite proxy `/api` и `/health` на `http://api:8000`. Production-конфигурация `infra/.env` для этого запуска не нужна.

Исходники frontend и backend подключены через bind mounts: изменения видны без сборки images. Для изменений зависимостей/скриптов запуска повторить команду с `--build`. Если меняется UID/GID, frontend volumes старого владельца потребуется отдельно перенести или пересоздать; обычный `down` данные не удаляет.

```sh
./scripts/dev.sh logs -f api web
./scripts/dev.sh exec -T api composer quality
./scripts/dev.sh exec -T web npm run typecheck
./scripts/dev.sh exec -T web npm test
./scripts/dev.sh exec -T api php bin/console app:ai:render --help
./scripts/dev.sh down
```

Локальный AI-ключ читается API из `apps/api/.env.local`; подробности — [локальная AI-интеграция](docs/local-ai.md). Node-контейнеру каталог backend с ключом не подключается. БД сохраняется в отдельном volume, файлы — в `apps/api/var/local-files`, тестовые файлы — в `apps/api/var/local-test-files`, AI-артефакты — в `apps/api/var/ai-renders`. Все эти каталоги исключены из Git. Остановка через `down` их сохраняет.

Предыдущий backend-only запуск для CI и разработки с Node.js 22 на хосте остаётся доступен через `infra/compose.yaml` + `infra/compose.dev.yaml` (проект `furniture-os-dev`, API на `127.0.0.1:8000`). Он требует `infra/.env`, отдельного `composer install`, миграций и `npm run dev` на хосте. После изменения OpenAPI выполнить `npm run api:generate`; generated-файл вручную не редактируется.

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

Локальный Docker bootstrap с чистой копией checkout проверяется командой `python3 scripts/test-local-docker.py`; конфигурация — `python3 scripts/test-local-compose.py`. Первый тест создаёт собственный временный Compose-проект и удаляет только его тестовые volumes.

CI проверяет локальный Docker bootstrap, frontend lint/typecheck/tests/coverage/build, OpenAPI lint/generation/breaking changes, backend PSR-12/PHPStan/Deptrac/tests, PostgreSQL migrations с пустой и заполненной предыдущей схемы, storage contracts, настоящий Nginx/API/PostgreSQL E2E и backup/restore. Mutation tests запускаются отдельно каждую неделю и вручную. Настройка branch protection в GitHub остаётся действием владельца репозитория: workflow сам по себе не запрещает обход проверок администратором.

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

По согласованному исключению [ADR-0006](docs/adr/0006-cabinet-builder-pilot.md) добавлен пилот: вход/регистрация, конструктор распашного шкафа, личные проекты и публичная заявка по ссылке. В конструкторе можно менять размеры, секции, наполнение и материалы, открывать фасады и отменять изменения. Расчёт выполняется backend по демонстрационному `prototype-v0`; производственную пригодность и цену подтверждает изготовитель. Публикация фиксирует выбранную ревизию; ссылкой автор делится самостоятельно. Автоматической отправки производителям, предложений, документов, полноценного RFQ, продуктового AI-сценария и 3D пока нет.

Техническая миграция ещё ожидает отдельной приёмки. До подписанной parity оставлен выключенный флаг `VITE_ENABLE_PROTOTYPE_V0_FALLBACK`: для явного технического rollback можно собрать web с `--build-arg VITE_ENABLE_PROTOTYPE_V0_FALLBACK=true`. При обычной сборке движок исключается из bundle; отказ API не включает его автоматически. После приёмки parity флаг и compatibility runtime удаляются отдельным изменением. Локальный CLI-эксперимент AI разрешён отдельно по ADR-0005.
