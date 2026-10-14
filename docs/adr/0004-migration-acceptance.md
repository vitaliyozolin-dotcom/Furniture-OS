# ADR-0004: отчёт проверки технической миграции

Дата: 15 октября 2026 года.

Статус: реализация подготовлена к рассмотрению; миграция НЕ принята владельцем. Product development не начат.

## Что доступно для проверки

В текущем рабочем окружении полный стек открыт на http://127.0.0.1:18080. Исходный экран, цена, срок, предупреждения и открытие фасадов сохранены. Расчёт загружается через `/api/prototype/v0/calculate`; TypeScript engine изолирован в compatibility-каталоге за выключенным migration-флагом; default production build исключает его из bundle. При отказе API интерфейс показывает ошибку без локальной цены.

- `apps/api`: PHP 8.4, Symfony 7.4 LTS, Domain/Application/Infrastructure/Presentation; prototype-v0 геометрия и отдельная десятичная Pricing-реализация.
- OpenAPI, generated frontend types и typed HTTP gateway. JSON errors (включая Nginx 413), HTTP Allow и correlation ID; health/live и PostgreSQL health/ready.
- PostgreSQL + Doctrine, две forward-only технические миграции. Продуктовых таблиц нет.
- FileStorage application-port, Flysystem local adapter, in-memory adapter, атомарная публикация, checksum, идемпотентная запись, защита opaque keys. ORM file metadata отделены от доменной модели. Проверка missing/corrupt/orphan объектов доступна через CLI.
- Отдельные production web/API images и persistent volumes, same-origin Nginx, security headers, ограничение размера запросов. Dev и test не используют production БД/файлы.
- Быстрый CI, integration/E2E/restore job и периодический mutation job. Реальный запуск GitHub Actions на удалённом сервере в этой сессии не выполнялся; перечисленные команды проверены локально.

## Выполненные проверки

| Проверка | Результат |
| --- | --- |
| Frontend unit/component | 36 тестов, успешно |
| Frontend lint / typecheck / production build | успешно |
| OpenAPI lint и актуальность generated types | успешно |
| Frontend/межконтекстные архитектурные проверки, Deptrac | успешно, нарушений нет |
| PHPStan level 6 / PSR-12 / Symfony container и YAML lint | успешно |
| PHPUnit domain/application/integration/API | 40 тестов, успешно |
| Characterization | 13 конфигураций; полное сравнение JSON, числовой допуск указан ниже |
| PostgreSQL | миграции пустой базы и заполненной предыдущей схемы; schema:validate успешно |
| Storage contract | одинаковые binary/zero-byte/duplicate/missing/checksum/delete/key-safety проверки для in-memory и local |
| Playwright | 6 desktop/mobile сценариев через настоящий Nginx + Symfony + PostgreSQL, включая API outage |
| Visual regression | исходные desktop/mobile snapshots прошли без обновления |
| Frontend coverage | строки 99,13%; ветви 93,22%; функции 100%; statements 98,60% |
| Backend coverage (Xdebug) | строки 93,42%; ветви 88,17% |
| Calculation Domain coverage | строки 100%; ветви 90,38% |
| Mutation testing Calculation Domain | 291 из 315 мутаций обнаружены, MSI 92,38% |
| npm audit / composer audit | известных уязвимостей не найдено |
| API smoke, 100 запросов, concurrency 4 | до restore p95 около 2,8 мс; после restore около 3,5 мс; только локальное измерение |
| Backup/restore drill | согласованный PostgreSQL dump + files tar + SHA-256 manifest; восстановление в отдельный пустой Compose project; probe row и checksum подтверждены; 13 конфигураций/100 запросов после restore успешны; перезапись непустого target отклонена |
| git diff --check | успешно |

Измеренные baseline закреплены в Vitest и `scripts/check-coverage.php`, общий минимум плана превышен. Mutation gate — 90%. Xdebug path coverage потребовал 1 GiB memory limit; вложенные генераторы обхода storage заменены простым списком, после чего полный branch coverage выполняется успешно.

## Открытые критерии, мешающие объявить миграцию завершённой

1. **Parity требует решения владельца.** Десятичное pricing отличается от IEEE-754 TypeScript на максимум `1.4551915228366852e-11` рубля. Структура, предупреждения, формулы и отображаемая цена сохранены. Harness допускает абсолютную ошибку `1e-9`, но утверждённый план требует считать любое отличие дефектом. Это расхождение явно не объявляется согласованным. Владелец должен либо принять этот числовой допуск с обновлением соответствующего пункта плана, либо определить совместимую политику точности. [Подробности](0003-migration-runtime-boundaries.md).
2. **Удаление временного rollback.** `VITE_ENABLE_PROTOTYPE_V0_FALLBACK` выключен по умолчанию и должен быть удалён отдельным изменением после подписанной parity. До этого соответствующий критерий миграции не закрыт.
3. **Приёмка UI и окружения.** Владелец должен отдельно принять текущий экран и результаты локального deployment/restore. Этот ADR фиксирует доказательства, а не подменяет приёмку.
4. **Внешний production.** Не выбраны российский провайдер/домен, доступы, TLS termination, персональные данные, backup retention и RPO/RTO. Внешний deploy, расписание удалённых backup и подключение внешнего error alerting не выполнялись. Локальные JSON error logs, healthchecks и restore проверены; скрипты и инструкции готовы.
5. **Repository settings.** Branch protection/required checks не менялись; их следует включить владельцу в GitHub после запуска workflow.

## Что намеренно относится к следующему продуктовому плану

Identity, проекты и immutable revisions, forms/undo UI, подтверждённый ruleset-v1, SVG, JSON/PDF ТЗ, пользовательская загрузка/авторизованная выдача файлов, поставщики/RFQ/offers и AI не добавлены. Не подтверждались интервью, пилотный город, участие производителей или производственная корректность формул.

## Решение при приёмке

После явного решения владельца о parity сначала обновляется утверждённый план, затем выполняется необходимая корректировка тестового контракта. После отдельной приёмки всех применимых критериев статус этого ADR меняется на «принято» и начинается первый вертикальный срез продуктового плана. До этого текущая работа остаётся технической миграцией.
