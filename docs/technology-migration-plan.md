# Furniture OS — пошаговый план перехода на целевой стек

Дата: 25 августа 2026 года.

Статус: утверждённый исполнимый план миграции. Архитектурные решения и границы продукта определены в [`project-plan.md`](project-plan.md).

## 0. Граница плана

Сначала выполняется только техническая миграция существующего прототипа. Новый пользовательский функционал начинается после отдельной приёмки миграции.

В техническую миграцию входят:

- сохранение текущего внешнего вида и поведения экрана;
- перестройка frontend и репозитория;
- настройка Symfony, PostgreSQL, Docker и CI/CD;
- перенос существующего предварительного расчёта за API с обязательной фиксацией его статуса как прототипного;
- DDD-границы, OpenAPI, тестовая инфраструктура и quality gates;
- локальный storage-порт и адаптер как техническая основа, без добавления пользовательского сценария документов;
- production-ready окружение и проверка deployment/backup/restore.

До завершения миграции не добавляются:

- регистрация, вход, организации и роли;
- сохранение проектов и история ревизий;
- новый пошаговый конфигуратор;
- новые мебельные правила, материалы или категории;
- PDF/JSON ТЗ и пользовательская загрузка файлов;
- кабинеты производителей, RFQ и предложения;
- AI, 3D, платежи и другие продуктовые возможности.

Такие функции описаны отдельно в [`product-development-plan.md`](product-development-plan.md) и не входят в критерий завершения перехода на новый стек.

## 1. Цель миграции

Перевести текущий UI-прототип на целевую платформу без одномоментного переписывания и без потери проверяемого поведения:

- сохранить React, TypeScript и Vite;
- превратить текущий экран в структурированный frontend;
- добавить PHP 8.4, Symfony 7.4 LTS, PostgreSQL и Doctrine;
- перенести авторитетную валидацию, расчёт и цену на backend;
- строить backend как DDD-модульный монолит;
- хранить файлы локально через сменяемый application-порт;
- подготовить бесшовную замену локального адаптера российским S3-совместимым адаптером;
- сделать тесты и архитектурные проверки обязательным условием merge;
- двигаться вертикальными пользовательскими сценариями, сохраняя рабочее приложение после каждого этапа.

## 2. Исходная точка

На момент составления плана репозиторий содержит:

- один React-экран `src/App.tsx`;
- CSS прототипа;
- локальную TypeScript-функцию `calculateCabinet`;
- TypeScript-типы конфигурации и результата;
- три unit-теста расчёта;
- Vite/Vitest/TypeScript и воспроизводимый `package-lock.json`;
- публикацию статического прототипа через GitHub Pages;
- без backend, PostgreSQL, Docker, OpenAPI, маршрутизации, управления состоянием и E2E-тестов.

Текущий TypeScript-расчёт считается характеристикой прототипа, а не подтверждённой производственной моделью. Его нельзя механически объявить авторитетным или дословно перенести на PHP без согласования fixtures с мебельным технологом.

## 3. Стратегия перехода

Используется постепенная migration/strangler strategy:

1. Сначала зафиксировать наблюдаемое поведение текущего прототипа.
2. Переместить frontend в целевую структуру без изменения UI.
3. Зафиксировать доменный словарь, схемы и эталонные примеры.
4. Добавить backend рядом с работающим frontend.
5. Реализовать через API только уже существующий сценарий предварительного расчёта.
6. Временно сравнивать TypeScript- и PHP-результаты на characterization fixtures.
7. Переключить текущий экран на API и удалить production-вызов локального расчёта.
8. Принять миграцию отдельно и лишь затем начинать продуктовый roadmap.

Запрещён большой PR, одновременно меняющий структуру frontend, формулы, API, базу данных и внешний вид.

## 4. Целевая структура репозитория

```text
Furniture-OS/
├── apps/
│   ├── web/
│   │   ├── src/
│   │   │   ├── app/
│   │   │   ├── pages/
│   │   │   ├── features/
│   │   │   ├── entities/
│   │   │   ├── shared/
│   │   │   └── test/
│   │   ├── e2e/
│   │   └── package.json
│   └── api/
│       ├── config/
│       ├── migrations/
│       ├── public/
│       ├── src/
│       │   ├── Identity/
│       │   ├── Project/
│       │   ├── Cabinet/
│       │   ├── Catalog/
│       │   ├── Calculation/
│       │   ├── Pricing/
│       │   ├── Marketplace/
│       │   ├── Rendering/
│       │   ├── Document/
│       │   └── Shared/
│       └── tests/
├── contracts/
│   ├── openapi.yaml
│   └── fixtures/
├── docs/
│   └── adr/
├── infra/
│   ├── docker/
│   └── compose.yaml
├── .github/workflows/
├── AGENTS.md
└── README.md
```

Не нужно создавать пустые каталоги всех будущих модулей заранее. Модуль появляется вместе с первым реальным use case.

## 5. Общие правила выполнения

Для каждого шага:

1. PR решает одну архитектурную или вертикальную задачу.
2. До изменения поведения добавляется тест, описывающий ожидаемый результат.
3. Миграции БД выполняются только Doctrine Migrations и проверяются на пустой и заполненной тестовой базе.
4. Публичный API сначала меняется в OpenAPI, затем реализуется backend и генерируется frontend-клиент.
5. Domain не импортирует Symfony, Doctrine, HTTP, Flysystem или SDK провайдеров.
6. Процент покрытия не заменяет тестирование границ, ошибок, прав доступа и идемпотентности.
7. После каждого PR приложение должно собираться и запускаться локально по README.
8. Временный compatibility-код получает условие и отдельный PR удаления.

## 6. Этап 0 — зафиксировать baseline

### Шаг 0.1. Зафиксировать документацию

- Добавить в Git `AGENTS.md`, `docs/project-plan.md` и этот документ.
- Обновить README ссылками на архитектурный и миграционный планы.
- Указать реальный статус продукта: UI-прототип, а не готовая производственная система.

Проверки: Markdown-ссылки существуют; `git diff --check` проходит.

Критерий выхода: любой новый агент начинает с одинакового утверждённого контекста.

### Шаг 0.2. Зафиксировать технический baseline

- Подтвердить версию Node.js в `.nvmrc` или `.tool-versions` и `engines`.
- Сохранить точные версии зависимостей и `package-lock.json`.
- Разделить команды `test`, `test:coverage`, `typecheck`, `build`.
- Включить отчёт покрытия Vitest, пока без искусственного требования 80% к существующему UI.
- Проверить clean clone: `npm ci`, тесты, typecheck и build.

Проверки: существующие unit-тесты; TypeScript; production build.

Критерий выхода: baseline воспроизводится локально и в CI.

### Шаг 0.3. Защитить текущее поведение

- Добавить React Testing Library.
- Проверить рендер цены, срока, предупреждения и переключение фасадов.
- Добавить один Playwright smoke-тест основной страницы.
- Зафиксировать минимальный visual snapshot ключевых viewport, не тестируя каждый пиксель CSS.
- Сохранить текущие результаты `calculateCabinet` как characterization fixtures с пометкой `prototype-v0`.

Критерий выхода: структурные изменения frontend обнаруживают случайную поломку текущего прототипа.

Точка отката: только тесты и CI; runtime-поведение не меняется.

## 7. Этап 1 — преобразовать репозиторий в monorepo

### Шаг 1.1. Переместить frontend

- Переместить существующее Vite-приложение в `apps/web` без рефакторинга компонентов и формул.
- Настроить корневые npm scripts как прокси к web-командам либо npm workspaces.
- Сохранить текущий base path GitHub Pages на переходный период.
- Исправить пути CI, build output и README.

Проверки: characterization, smoke E2E, typecheck, build; визуальный результат не изменён.

Критерий выхода: прототип работает из `apps/web`, корень готов принять backend.

### Шаг 1.2. Разделить frontend по ответственностям

- Создать app bootstrap и router.
- Разделить `App.tsx` на страницу, preview, карточку цены, прогресс и навигацию.
- Вынести форматирование денег и UI-примитивы в `shared`.
- Оставить расчёт в отдельном compatibility adapter, не размазывать его по компонентам.
- Добавить error boundary и явные состояния loading/error/empty для будущего API.

Проверки: unit/component tests каждого извлечённого поведения; visual regression.

Критерий выхода: UI остаётся прежним, компоненты не знают деталей расчётного движка.

### Шаг 1.3. Ввести состояние frontend

- Подключить Zustand только для редактируемого draft, шагов и undo/redo.
- Подключить TanStack Query только для серверного состояния.
- Не копировать API-объекты целиком в Zustand.
- Ввести интерфейс `CalculationGateway`: сначала его реализует локальный prototype adapter.
- Подключить runtime-проверку API-ответов через Zod либо Valibot; выбрать один вариант ADR.

Критерий выхода: frontend готов заменить локальный gateway HTTP-адаптером одной конфигурацией.

Точка отката этапа: frontend продолжает работать на локальном gateway, backend не требуется.

## 8. Этап 2 — формализовать только существующий расчёт

### Шаг 2.1. Провести domain discovery

- Описать текущие термины конфигурации и предварительного расчёта без расширения модели.
- Выделить минимальные границы Cabinet input, Calculation и Pricing, необходимые для переноса текущего кода.
- Зафиксировать существующие value objects, входные поля и выходной DTO.
- Не проектировать Project, Revision, Catalog, Identity или Marketplace в рамках миграции.

Артефакты: `docs/domain-glossary.md`, context map и ADR границ модулей.

### Шаг 2.2. Зафиксировать версионированные контракты

- Описать `PrototypeCabinetInputV0` и `PrototypeCalculationResultV0` ровно по текущему интерфейсу.
- Пометить контракт как migration/compatibility contract, не пригодный для обещания производственной точности.
- Не вводить новые поля, версии каталога, ревизии или snapshots.
- Денежные и размерные типы целевого `ruleset-v1` проектируются уже в продуктовом плане.

### Шаг 2.3. Создать эталонные fixtures

- Преобразовать существующие тестовые случаи в `prototype-v0` characterization fixtures.
- Добавить только необходимые граничные случаи существующей функции: ширина секции, глубина со штангой, высота, число секций, ящики и текущие материалы.
- Для каждого примера зафиксировать фактические вход и результат TypeScript без исправления формул.
- `ruleset-v1` и 10–15 подтверждённых технологом golden fixtures создаются после миграции по продуктовому плану.

Критерий выхода этапа: compatibility-контракт и fixtures полностью описывают поведение текущего экрана; новый доменный функционал не спроектирован и не реализован.

## 9. Этап 3 — создать Symfony foundation

### Шаг 3.1. Создать приложение

- Создать `apps/api` на PHP 8.4 и Symfony 7.4 LTS.
- Зафиксировать `composer.lock`, PHP extensions и Composer platform config.
- Установить только необходимые компоненты: Framework, Serializer, Validator, Security, UID, Doctrine ORM/Migrations, Messenger, Workflow и Flysystem integration.
- Добавить `/health/live` без внешних зависимостей и `/health/ready` с проверкой PostgreSQL.

Проверки: PHPUnit smoke; Symfony container lint; config lint; static analysis.

### Шаг 3.2. Поднять локальную инфраструктуру

- Добавить Docker Compose с `web`, `api`, `postgres` и позднее `worker` profiles.
- Хранить PostgreSQL и файлы в именованных persistent volumes.
- Не помещать пользовательские файлы в публичный web-root или внутрь эфемерного container layer.
- Добавить `.env.example` без секретов и документировать bootstrap-команды.
- Добавить healthchecks и контролируемый порядок старта.

Проверки: запуск с нуля, миграции, readiness, сохранность БД и файла после перезапуска контейнера.

### Шаг 3.3. Ввести DDD-каркас на существующем расчёте

- Начать с `Calculation`, не создавать пустые шаблоны остальных модулей.
- Domain: value objects входной конфигурации и прототипные расчётные правила.
- Application: `CalculatePrototypeCabinet` и DTO входа/выхода.
- Infrastructure: конфигурация версий правил и технические реализации портов, если они понадобятся.
- Presentation: HTTP controller и error mapping.
- PostgreSQL на этом шаге проверяется инфраструктурно, но пользовательские проекты и результаты ещё не сохраняются.

Проверки: чистые domain unit tests; application tests с in-memory repository; PostgreSQL integration tests; architecture dependency tests.

Критерий выхода: существующий расчёт выполняется через чистый application use case, а архитектурные тесты запрещают обратные зависимости.

### Шаг 3.4. Настроить качество backend

- PHPUnit для unit/application/integration suites.
- PHPStan или Psalm на согласованном строгом уровне.
- PHP-CS-Fixer либо ECS с одной конфигурацией.
- Deptrac или эквивалент для DDD-границ.
- Infection mutation testing сначала только для критической Domain-логики и периодически, а не обязательно в каждом быстром PR.
- Покрытие с отдельными порогами Domain и общего backend.

Критерий выхода этапа: backend reproducibly проходит install, lint, static analysis, migrations и tests.

## 10. Этап 4 — OpenAPI и переключение существующего экрана

### Шаг 4.1. Создать контракт API

- Добавить `contracts/openapi.yaml` как version-controlled source of truth.
- Описать health, прототипный endpoint расчёта и problem details ошибок.
- Зафиксировать соглашения об ID, времени, пагинации, idempotency key и correlation ID.
- Настроить lint OpenAPI и обнаружение breaking changes.

### Шаг 4.2. Сгенерировать frontend-клиент

- Генерировать TypeScript types/client из OpenAPI в отдельный generated-каталог.
- Запретить ручное редактирование generated-файлов.
- Обернуть клиент в feature gateways, чтобы UI не зависел от конкретного генератора.
- Настроить Vite proxy для локальной разработки и same-origin `/api` для production.

### Шаг 4.3. Переключить существующий сценарий

- Реализовать получение текущего предварительного расчёта через TanStack Query.
- Добавить loading, retry policy, validation errors и недоступность API.
- Сохранить локальный gateway за feature flag только на период shadow comparison.

Проверки: API contract tests; component tests с mock server; E2E web+API+PostgreSQL.

Критерий выхода: текущий экран выглядит и ведёт себя как до миграции, но получает расчёт из Symfony API.

Точка отката: feature flag временно возвращает TypeScript gateway; миграции БД остаются forward-only.

## 11. Этап 5 — подтвердить parity и завершить перенос расчёта

### Шаг 5.1. Реализовать входную модель существующего экрана

- Добавить value objects размеров, секций и материалов без новых пользовательских полей.
- Сохранить контракт `prototype-v0`, явно не называя его производственно подтверждённым.
- Не добавлять проекты, сохранение draft и immutable revisions в рамках миграции.

### Шаг 5.2. Реализовать Validation

- Сначала перенести characterization-поведение `prototype-v0`.
- Возвращать стабильные коды, severity, field path и параметры сообщения.
- Текст локализуется на границе presentation/frontend, код правила остаётся стабильным.
- Проверить characterization fixtures и существующие boundary cases.
- Подтверждённый `ruleset-v1` создаётся отдельной продуктовой задачей после миграции.

### Шаг 5.3. Реализовать Calculation и Pricing

- Отделить геометрию/BOM от цен.
- Расчёт принимает текущую конфигурацию и возвращает предварительный результат без сохранения.
- `CabinetRevision` и immutable `CalculationSnapshot` добавляются после миграции вместе с функцией проектов.
- Денежная арифметика не использует float.

### Шаг 5.4. Выполнить shadow comparison

- HTTP `CalculationGateway` становится основным источником результата.
- Frontend или тестовый harness сравнивает PHP и TypeScript на `prototype-v0` fixtures.
- Любое отличие в рамках миграции считается дефектом переноса и устраняется без изменения формул.
- Исправление самих формул выполняется отдельным PR после приёмки миграции и утверждения `ruleset-v1`.

### Шаг 5.5. Удалить локальную авторитетность

- Удалить использование TypeScript-расчёта из production UI.
- При недоступности API показывать состояние ошибки, а не выдавать локальную цену как окончательную.
- При необходимости оставить локальный preview только для геометрии и явно назвать его неавторитетным.
- Сохранить prototype fixtures в истории/архиве для объяснимости миграции.

Проверки: characterization fixtures 100%; unit/application/integration/API; generated client current; E2E текущего экрана.

Критерий выхода этапа: текущая конфигурация даёт идентичный результат через Symfony API, production UI больше не вызывает TypeScript-расчёт, новый функционал не добавлен.

## 12. Этап 6 — заложить локальное файловое хранилище без продуктовых функций

### Шаг 6.1. Определить модель файла

- Domain/Application оперируют `FileId`, назначением и метаданными.
- В PostgreSQL хранить owner, purpose, MIME type, size, checksum, storage key, backend, status и timestamps.
- Ограничить размер и разрешённые MIME type; не доверять расширению имени.

### Шаг 6.2. Объявить порт

- Application-порт поддерживает write/read/delete/exists и выдачу ограниченного download response/reference.
- Порт не возвращает абсолютный путь и не содержит терминов S3 bucket/region.
- Определить семантику ошибок, идемпотентность и checksum.

### Шаг 6.3. Реализовать LocalFileStorage

- Использовать Flysystem local adapter за application-портом.
- Записывать сначала во временный объект, проверять checksum и атомарно публиковать storage key.
- Защититься от path traversal и коллизий имён.
- Отдавать приватные файлы через авторизованный controller, не напрямую web server.

### Шаг 6.4. Создать contract suite

- Один набор contract tests обязан выполняться для in-memory/test и local adapters.
- Проверить round trip, binary data, zero-byte policy, duplicate key, missing object, checksum, delete и запрещённые ключи.
- В будущем без изменений запускать тот же suite для S3 adapter.

### Шаг 6.5. Добавить эксплуатацию

- Включить volume в backup.
- Документировать restore БД вместе с файловым каталогом.
- Добавить периодическую проверку metadata ↔ object consistency.
- Провести тестовое восстановление и зафиксировать результат.

В рамках миграции адаптер проверяется только contract/integration-тестами и технической probe-командой. Пользовательская загрузка, PDF/JSON ТЗ и скачивание через UI не реализуются.

Критерий выхода: локальный адаптер проходит единый storage contract suite, volume участвует в backup/restore, а application-контракт не зависит от файловой системы или S3.

## 13. Этап 7 — принять миграцию и настроить окружение

До начала нового функционала необходимо:

- развернуть полный стек web/API/PostgreSQL в Docker;
- проверить frontend и API на staging-подобном окружении;
- подтвердить одинаковое поведение текущего экрана до и после переключения;
- выполнить тест миграций БД, backup/restore PostgreSQL и файлового volume;
- проверить logs, healthchecks, error tracking и correlation ID;
- удалить временный TypeScript fallback и migration feature flags;
- зафиксировать baseline покрытия и включить запрет его снижения;
- обновить README командами запуска и диагностики;
- создать migration completion ADR с результатами проверок и известными ограничениями.

Критерий выхода: целевой стек воспроизводимо запускается, текущий функционал сохранён, старый runtime-путь отключён, владелец продукта отдельно принял миграцию.

После приёмки миграции работа продолжается только по продуктовому плану.

## 14. Production deployment в России

- Подготовить отдельные images/processes web и API; worker image добавляется после появления фоновых задач.
- Размещать web и API за reverse proxy на одном домене.
- Использовать managed PostgreSQL либо документированный PostgreSQL с backup/PITR.
- Хранить secrets вне Git и image layers.
- Выполнять миграции отдельным release job до переключения трафика.
- Добавить structured logs, correlation ID, error tracking и healthchecks.
- Настроить TLS, security headers, ограничения upload body и таймауты.
- Провести restore drill БД и файлового volume до пилота.

Стратегия выпуска:

1. staging на production-подобной инфраструктуре;
2. smoke/E2E на staging;
3. backup перед миграцией;
4. backward-compatible DB migration;
5. deploy API/worker/web;
6. smoke checks;
7. переключение feature flag;
8. наблюдение ошибок и бизнес-метрик;
9. rollback приложения при проблеме, без destructive rollback миграции.

Критерий выхода: воспроизводимый deploy и проверенное восстановление, а не только успешно запущенный контейнер.

## 15. CI/CD-порядок внедрения

### Быстрый контур каждого PR

- frontend lint/typecheck/unit/coverage/build;
- backend code style/static analysis/unit/application/coverage;
- OpenAPI lint и breaking-change check;
- architecture dependency tests;
- `git diff --check`.

### Интеграционный контур

- PostgreSQL integration tests;
- Doctrine migration from empty database;
- migration from previous supported schema fixture;
- storage contract tests;
- API tests;
- Playwright основного затронутого сценария.

### Периодический тяжёлый контур

- полный visual regression;
- mutation testing критического Domain;
- dependency/security scanning;
- backup/restore drill;
- performance tests существующего расчёта и технической storage probe.

Порог покрытия вводится поэтапно: сначала измерить baseline, затем запретить его снижение, после появления целевой структуры довести общий показатель до 80%, а критические Domain-модули — до 90% строк и ветвей.

## 16. Рекомендуемая последовательность pull request

1. `docs: commit architecture and migration plans`.
2. `test: establish frontend characterization baseline`.
3. `chore: move React app to apps/web`.
4. `refactor: split prototype UI without behavior changes`.
5. `feat: introduce frontend state and calculation gateway`.
6. `docs: define prototype v0 compatibility contract and fixtures`.
7. `chore: add Symfony application and backend quality gates`.
8. `chore: add PostgreSQL and local Docker Compose`.
9. `feat: implement prototype calculation vertical slice`.
10. `feat: publish calculation OpenAPI and generated web client`.
11. `test: compare PHP and TypeScript prototype fixtures`.
12. `feat: switch current web calculation gateway to API`.
13. `feat: implement local file storage port and adapter contracts`.
14. `chore: verify deployment backup and restore`.
15. `refactor: remove TypeScript runtime calculation and migration flags`.
16. `docs: record migration acceptance`.

На PR 16 техническая миграция завершена. Только после отдельной приёмки начинается продуктовый roadmap: проекты и ревизии → подтверждённый ruleset → документы → Identity → RFQ.

PR можно дополнительно дробить, но нельзя объединять соседние шаги, если это скрывает изменение поведения или делает review непроверяемым.

## 17. Контрольные точки миграции

До начала соответствующего этапа требуется явно подтвердить:

- до production: российский провайдер серверов, требования по персональным данным, backup retention и RPO/RTO;
- до удаления TypeScript fallback: подписанный результат parity-проверки;
- до закрытия миграции: отдельная приёмка владельцем текущего UI и окружения.

## 18. Определение завершённой миграции

Переход на целевые технологии завершён, когда:

- frontend находится в `apps/web` и не содержит авторитетной цены/BOM;
- Symfony API в `apps/api` реализует DDD-модули и проходит архитектурные проверки;
- PostgreSQL подключён, миграции и backup/restore проверены; продуктовые таблицы проектов и ревизий могут появиться после миграции;
- OpenAPI является источником сгенерированного frontend-клиента;
- локальное файловое хранилище доступно только через сменяемый порт;
- существующий пользовательский экран и расчёт покрыты E2E;
- characterization fixtures `prototype-v0` проходят на 100%;
- общее покрытие достигло 80%, критические модули — 90% строк и ветвей;
- Docker-окружение, CI, deployment и backup/restore воспроизводимы;
- старый TypeScript-расчёт не используется production-интерфейсом как источник истины;
- каждый временный compatibility flag миграции удалён;
- не добавлен новый пользовательский функционал, а начало продуктового roadmap оформлено отдельным решением.
