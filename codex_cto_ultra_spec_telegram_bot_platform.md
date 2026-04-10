# CTO-LEVEL ULTRA SPEC ДЛЯ CODEX
## Проект: платформа для создания Telegram-чатботов с графическим редактором процессов
### Технологии: PHP 8.2 + MySQL 8+ + HTML5 + CSS3 + Vanilla JS
### Формат: self-hosted modular monolith
### Развертывание: VPS Contabo, запуск из корня сайта, без `public/`

---

# 1. Цель продукта

Создать полноценную платформу для:

- подключения Telegram-ботов;
- визуального проектирования логики ботов через графический редактор;
- исполнения процессов в реальном времени;
- хранения контактов, тегов, переменных, событий;
- ручной работы менеджеров с диалогами;
- построения автоворонок;
- аналитики прохождения этапов;
- ведения сделок и статусов лидов;
- использования шаблонов процессов, шаблонов сообщений и переиспользуемых блоков;
- установки шаблонов из внутреннего маркетплейса.

---

# 2. Продуктовая модель

Система — **multi-tenant**.

Иерархия:

- **Platform**
  - **Account**
    - **Users**
    - **Projects**
      - **Bots**
      - **Processes**
      - **Funnels**
      - **Pipelines**
      - **Templates**
      - **Contacts**
      - **Dialogs**
      - **Deals**
      - **Analytics**

---

# 3. Что именно должна уметь система

## 3.1. Основные функциональные контуры

1. Авторизация и роли.
2. Управление аккаунтами и проектами.
3. Подключение Telegram-ботов.
4. Установка webhook.
5. Визуальный графический редактор процессов.
6. Runtime engine для исполнения graph-процессов.
7. Контакты и CRM-слой.
8. Диалоги / inbox.
9. Рассылки.
10. Сегменты.
11. HTTP/webhook-интеграции.
12. Message templates.
13. Reusable blocks / reusable subflows.
14. Funnels и step analytics.
15. Deals CRM.
16. Marketplace templates.
17. Debug / logs / audit.
18. Installer / updater.
19. Queue / scheduler / retry / cleanup.
20. Подготовка к будущему расширению: payments, external channels, AI modules.

---

# 4. Ограничения и принципы реализации

## 4.1. Обязательные ограничения

Использовать только:

- PHP 8.2
- MySQL 8+
- HTML5
- CSS3
- Vanilla JavaScript

Запрещено использовать:

- Laravel
- Symfony
- Yii
- React
- Vue
- Angular
- jQuery
- Node.js как обязательную часть
- Redis как обязательную часть
- Docker как обязательную часть
- terminal-only deployment как обязательный путь

## 4.2. Архитектурные принципы

1. Modular monolith.
2. Четкое разделение:
   - Domain logic
   - Application services
   - Persistence
   - Transport
   - UI
3. Код должен быть предсказуемым, расширяемым, пригодным к рефакторингу.
4. Никакой “магии”.
5. Никаких скрытых зависимостей.
6. Все критичные процессы логируются.
7. Любой graph-process должен быть валидируемым, сериализуемым и исполняемым.
8. Любое входящее событие должно быть обрабатываемо идемпотентно.
9. Один контакт не должен исполняться параллельно в конфликтных транзакциях.

---

# 5. Структура проекта

Условие: **никакой `public/` директории нет**.

Проект работает из корневой папки сайта.

## 5.1. Целевая структура каталогов

```text
/
  index.php
  webhook.php
  api.php
  .htaccess

  /app
    /Core
    /Controllers
    /Services
    /Repositories
    /Modules
    /Validators
    /Policies
    /Helpers
    /Jobs
    /Runtime
    /Graph
    /Telegram
    /Integrations
    /Templates
    /Funnels
    /CRM
    /Marketplace
    /DTO
    /Middleware
    /Exceptions

  /config
    app.php
    database.php
    security.php
    queue.php
    telegram.php
    marketplace.php

  /database
    /migrations
    /seeds

  /storage
    /cache
    /compiled_graphs
    /logs
    /sessions
    /uploads
    /tmp
    /exports
    /imports

  /install
    index.php
    steps.php

  /update
    index.php
    migrations.php

  /cron
    queue_worker.php
    scheduler.php
    retry_failed_jobs.php
    cleanup.php
    stats_aggregate.php
    compile_graphs.php

  /assets
    /css
    /js
    /img
    /icons

  /views
    /layouts
    /partials
    /pages
    /components

  /docs
    ARCHITECTURE.md
    INSTALL.md
    UPDATE.md
    DATABASE_SCHEMA.md
    GRAPH_SCHEMA.md
    PROCESS_EDITOR.md
    RUNTIME_ENGINE.md
    TELEGRAM_INTEGRATION.md
    CRON_AND_QUEUE.md
    SECURITY.md
    API.md
    FUNNELS.md
    DEALS_CRM.md
    TEMPLATES_AND_MARKETPLACE.md
```

---

# 6. Входные точки приложения

## 6.1. `index.php`
Главный фронт-контроллер админки и web UI.

## 6.2. `api.php`
Точка входа для AJAX/API админки и внутренних интеграционных вызовов.

## 6.3. `webhook.php`
Отдельная точка для приема Telegram webhook.

## 6.4. `install/index.php`
Browser installer.

## 6.5. `update/index.php`
Browser updater.

---

# 7. .htaccess и защита root-структуры

Нужно реализовать `.htaccess`, который:

1. Закрывает прямой доступ к:
   - `/app`
   - `/config`
   - `/database`
   - `/storage`
   - `/docs`
   - `/cron`
2. Разрешает доступ только к:
   - `index.php`
   - `api.php`
   - `webhook.php`
   - `install/*`
   - `update/*`
   - `assets/*`
3. Запрещает листинг директорий.
4. Закрывает `.env`, `*.sql`, `*.log`, `*.md`, `composer.*`, backup-файлы.
5. Ограничивает MIME sniffing и добавляет базовые security headers.

---

# 8. Модули системы

## 8.1. Core
Базовое ядро:
- Router
- Request
- Response
- Session
- Auth
- CSRF
- Validator
- DB connection manager
- Config loader
- Error handler
- Logger

## 8.2. Telegram
Telegram integration layer:
- Bot API client
- webhook validator
- update parser
- outgoing message service
- retry rules
- response mapping

## 8.3. Graph
Логический слой визуального конструктора:
- node registry
- edge validation
- schema validator
- graph compiler
- graph serializer/deserializer

## 8.4. Runtime
Исполнение процессов:
- execution engine
- state machine
- wait states
- scheduler hooks
- step logger
- lock manager
- retry manager

## 8.5. Templates
- message templates
- reusable blocks
- process templates
- import/export

## 8.6. Funnels
- funnel entity
- funnel steps
- analytics
- event tracker

## 8.7. CRM
- contacts
- deals
- pipelines
- notes
- tasks
- assignments

## 8.8. Marketplace
- каталог шаблонов
- импорт/экспорт
- версии
- установка в аккаунт

---

# 9. Роли и доступы

## 9.1. Роли

### Super Admin
Полный доступ по всей платформе.

### Account Owner
Полный доступ внутри аккаунта.

### Admin
Почти полный доступ внутри аккаунта, без системных супер-настроек.

### Manager
Контакты, inbox, сделки, запуск процессов, частичная аналитика.

### Operator
Только inbox и работа с лидами.

### Viewer
Только просмотр.

## 9.2. Правовая модель доступа
RBAC + tenant isolation.

Каждый запрос проверяет:
- user authenticated?
- role allowed?
- account scope correct?
- project scope correct?
- resource belongs to same tenant?

---

# 10. Сущности предметной области

Основные домены:

1. Account
2. User
3. Project
4. Bot
5. Process
6. ProcessVersion
7. Node
8. Edge
9. Contact
10. Tag
11. Chat
12. Message
13. Execution
14. WaitingState
15. Job
16. Broadcast
17. MessageTemplate
18. ReusableBlock
19. Funnel
20. FunnelStep
21. Pipeline
22. Deal
23. MarketplaceItem

---

# 11. Полная схема БД: основные таблицы и поля

Ниже — обязательная схема. Типы можно адаптировать под MySQL 8+, но логика должна остаться.

## 11.1. accounts
- id BIGINT UNSIGNED PK
- name VARCHAR(190)
- slug VARCHAR(190) UNIQUE
- status ENUM('active','suspended','archived')
- timezone VARCHAR(64) DEFAULT 'UTC'
- locale VARCHAR(16) DEFAULT 'ru'
- plan_code VARCHAR(64) NULL
- max_projects INT DEFAULT 10
- max_bots INT DEFAULT 50
- max_contacts INT DEFAULT 100000
- settings_json JSON NULL
- created_at DATETIME
- updated_at DATETIME

## 11.2. users
- id BIGINT UNSIGNED PK
- email VARCHAR(190) UNIQUE
- password_hash VARCHAR(255)
- first_name VARCHAR(100)
- last_name VARCHAR(100) NULL
- phone VARCHAR(40) NULL
- avatar_path VARCHAR(255) NULL
- status ENUM('active','invited','disabled')
- last_login_at DATETIME NULL
- created_at DATETIME
- updated_at DATETIME

## 11.3. account_users
- id BIGINT UNSIGNED PK
- account_id BIGINT UNSIGNED
- user_id BIGINT UNSIGNED
- role_code VARCHAR(64)
- permissions_json JSON NULL
- status ENUM('active','disabled')
- created_at DATETIME
- updated_at DATETIME

UNIQUE:
- (account_id, user_id)

## 11.4. roles
- id BIGINT UNSIGNED PK
- code VARCHAR(64) UNIQUE
- name VARCHAR(128)
- is_system TINYINT(1)
- created_at DATETIME
- updated_at DATETIME

## 11.5. permissions
- id BIGINT UNSIGNED PK
- code VARCHAR(128) UNIQUE
- name VARCHAR(190)
- group_name VARCHAR(128)
- created_at DATETIME
- updated_at DATETIME

## 11.6. role_permissions
- id BIGINT UNSIGNED PK
- role_id BIGINT UNSIGNED
- permission_id BIGINT UNSIGNED

UNIQUE:
- (role_id, permission_id)

## 11.7. user_sessions
- id BIGINT UNSIGNED PK
- user_id BIGINT UNSIGNED
- session_token_hash VARCHAR(255)
- ip_address VARCHAR(64)
- user_agent VARCHAR(255)
- last_seen_at DATETIME
- expires_at DATETIME
- created_at DATETIME

## 11.8. password_resets
- id BIGINT UNSIGNED PK
- user_id BIGINT UNSIGNED
- token_hash VARCHAR(255)
- expires_at DATETIME
- used_at DATETIME NULL
- created_at DATETIME

## 11.9. projects
- id BIGINT UNSIGNED PK
- account_id BIGINT UNSIGNED
- name VARCHAR(190)
- slug VARCHAR(190)
- description TEXT NULL
- status ENUM('active','paused','archived')
- settings_json JSON NULL
- created_by BIGINT UNSIGNED
- created_at DATETIME
- updated_at DATETIME

UNIQUE:
- (account_id, slug)

## 11.10. bots
- id BIGINT UNSIGNED PK
- account_id BIGINT UNSIGNED
- project_id BIGINT UNSIGNED
- name VARCHAR(190)
- telegram_bot_id BIGINT NULL
- telegram_username VARCHAR(190) NULL
- token_encrypted TEXT
- webhook_secret VARCHAR(128)
- webhook_url VARCHAR(255) NULL
- webhook_status ENUM('not_set','set','error')
- status ENUM('active','paused','disabled')
- start_command_enabled TINYINT(1) DEFAULT 1
- default_parse_mode ENUM('none','html','markdown') DEFAULT 'html'
- bot_settings_json JSON NULL
- last_webhook_at DATETIME NULL
- last_error_at DATETIME NULL
- created_by BIGINT UNSIGNED
- created_at DATETIME
- updated_at DATETIME

## 11.11. bot_commands
- id BIGINT UNSIGNED PK
- bot_id BIGINT UNSIGNED
- command VARCHAR(64)
- description VARCHAR(255)
- language_code VARCHAR(16) NULL
- created_at DATETIME
- updated_at DATETIME

UNIQUE:
- (bot_id, command, language_code)

## 11.12. bot_webhooks
- id BIGINT UNSIGNED PK
- bot_id BIGINT UNSIGNED
- url VARCHAR(255)
- secret_token VARCHAR(128)
- status ENUM('active','failed','disabled')
- last_result_code INT NULL
- last_result_body TEXT NULL
- installed_at DATETIME NULL
- last_checked_at DATETIME NULL
- created_at DATETIME
- updated_at DATETIME

---

# 12. Процессы и graph-слой

## 12.1. processes
- id BIGINT UNSIGNED PK
- account_id BIGINT UNSIGNED
- project_id BIGINT UNSIGNED
- bot_id BIGINT UNSIGNED
- name VARCHAR(190)
- slug VARCHAR(190)
- description TEXT NULL
- folder VARCHAR(190) NULL
- category VARCHAR(190) NULL
- status ENUM('draft','published','archived')
- active_version_id BIGINT UNSIGNED NULL
- start_mode ENUM('manual','triggered','scheduled','hybrid')
- is_template_based TINYINT(1) DEFAULT 0
- created_by BIGINT UNSIGNED
- created_at DATETIME
- updated_at DATETIME

UNIQUE:
- (project_id, slug)

## 12.2. process_versions
- id BIGINT UNSIGNED PK
- process_id BIGINT UNSIGNED
- version_number INT
- status ENUM('draft','published','archived')
- graph_json JSON
- compiled_graph_json JSON NULL
- graph_hash CHAR(64)
- validation_status ENUM('valid','invalid','warning')
- validation_errors_json JSON NULL
- editor_meta_json JSON NULL
- published_at DATETIME NULL
- created_by BIGINT UNSIGNED
- created_at DATETIME
- updated_at DATETIME

UNIQUE:
- (process_id, version_number)

## 12.3. process_nodes
- id BIGINT UNSIGNED PK
- process_version_id BIGINT UNSIGNED
- node_uuid CHAR(36)
- node_type VARCHAR(64)
- title VARCHAR(190)
- pos_x INT
- pos_y INT
- width INT DEFAULT 260
- height INT DEFAULT 96
- color VARCHAR(32) NULL
- config_json JSON
- meta_json JSON NULL
- created_at DATETIME
- updated_at DATETIME

UNIQUE:
- (process_version_id, node_uuid)

## 12.4. process_edges
- id BIGINT UNSIGNED PK
- process_version_id BIGINT UNSIGNED
- edge_uuid CHAR(36)
- source_node_uuid CHAR(36)
- source_port VARCHAR(64) NULL
- target_node_uuid CHAR(36)
- target_port VARCHAR(64) NULL
- condition_key VARCHAR(64) NULL
- sort_order INT DEFAULT 0
- meta_json JSON NULL
- created_at DATETIME
- updated_at DATETIME

UNIQUE:
- (process_version_id, edge_uuid)

## 12.5. process_validation_errors
- id BIGINT UNSIGNED PK
- process_version_id BIGINT UNSIGNED
- node_uuid CHAR(36) NULL
- edge_uuid CHAR(36) NULL
- severity ENUM('error','warning')
- code VARCHAR(64)
- message TEXT
- meta_json JSON NULL
- created_at DATETIME

---

# 13. Graph schema: формальный JSON-формат

Каждый `process_version.graph_json` обязан соответствовать schema.

## 13.1. Структура graph_json

```json
{
  "schema_version": "1.0.0",
  "process_meta": {
    "name": "Welcome flow",
    "bot_id": 12,
    "project_id": 4
  },
  "editor": {
    "zoom": 1,
    "offset_x": 0,
    "offset_y": 0,
    "grid_enabled": true
  },
  "nodes": [
    {
      "uuid": "11111111-1111-1111-1111-111111111111",
      "type": "start",
      "title": "Start /start",
      "position": { "x": 100, "y": 80 },
      "size": { "w": 260, "h": 96 },
      "ports": {
        "out": ["next"]
      },
      "config": {
        "trigger_type": "command",
        "command": "/start"
      },
      "meta": {
        "color": "#3B82F6"
      }
    }
  ],
  "edges": [
    {
      "uuid": "22222222-2222-2222-2222-222222222222",
      "from": {
        "node_uuid": "11111111-1111-1111-1111-111111111111",
        "port": "next"
      },
      "to": {
        "node_uuid": "33333333-3333-3333-3333-333333333333",
        "port": "in"
      },
      "condition_key": null,
      "sort_order": 0
    }
  ],
  "comments": [],
  "groups": []
}
```

## 13.2. Правила валидации graph_json

1. Должен быть минимум 1 start node или иной trigger node.
2. Все node UUID уникальны.
3. Все edge UUID уникальны.
4. Все связи должны ссылаться на существующие nodes.
5. Недопустимы dangling edges.
6. Для node type, требующего обязательный config, config не может быть пустым.
7. У published version graph должен быть `validation_status = valid`.
8. Циклы допустимы только при наличии loop guard.
9. Не должно быть недостижимых published entry nodes.
10. Не должно быть бесконечных безвыходных циклов без wait/guard.

---

# 14. Типы узлов и точные конфиги

## 14.1. Start node
`type = start`

### config:
```json
{
  "trigger_type": "command|message|callback|tag|webhook|schedule|deep_link|manual",
  "command": "/start",
  "callback_data": null,
  "tag_code": null,
  "schedule_expression": null,
  "deep_link_param": null
}
```

## 14.2. Send Text
`type = send_text`

### config:
```json
{
  "text": "Здравствуйте, {{first_name}}",
  "parse_mode": "html",
  "disable_preview": true,
  "save_message_id_to": "last_welcome_message_id",
  "buttons": [
    {
      "type": "callback",
      "text": "Продолжить",
      "value": "go_next"
    }
  ],
  "reply_keyboard": [],
  "typing_delay_ms": 800
}
```

## 14.3. Wait Input
`type = wait_input`

### config:
```json
{
  "input_type": "text|number|phone|email|date|contact|location|file|choice",
  "save_to": "client_name",
  "required": true,
  "timeout_seconds": 86400,
  "max_attempts": 3,
  "regex": null,
  "min_length": null,
  "max_length": null,
  "custom_error_text": "Неверный формат",
  "accept_button_choices_only": false
}
```

### output ports:
- success
- invalid
- timeout
- exhausted

## 14.4. Condition
`type = condition`

### config:
```json
{
  "operator": "eq|neq|gt|gte|lt|lte|contains|not_contains|exists|not_exists|in|not_in|regex|has_tag|not_has_tag",
  "left": "{{score}}",
  "right": "10",
  "cast": "string|int|float|bool|date"
}
```

### output ports:
- true
- false

## 14.5. Set Variable
`type = set_variable`

### config:
```json
{
  "scope": "contact|execution|global",
  "key": "lead_source",
  "value_mode": "literal|template|expression",
  "value": "telegram_start"
}
```

## 14.6. Add Tag
`type = add_tag`

### config:
```json
{
  "tag_code": "qualified_lead"
}
```

## 14.7. Delay
`type = delay`

### config:
```json
{
  "delay_mode": "relative|absolute",
  "seconds": 3600,
  "run_at": null,
  "respect_working_hours": false
}
```

## 14.8. HTTP Request
`type = http_request`

### config:
```json
{
  "method": "POST",
  "url": "https://example.com/webhook",
  "headers": {
    "Authorization": "Bearer {{token}}"
  },
  "query": {},
  "body_type": "json",
  "body_json": {
    "contact_id": "{{contact.id}}",
    "phone": "{{phone}}"
  },
  "timeout_seconds": 15,
  "retry_count": 2,
  "save_response_to": "http_response"
}
```

### output ports:
- success
- error
- timeout

## 14.9. Create Deal
`type = create_deal`

### config:
```json
{
  "pipeline_id": 5,
  "stage_id": 17,
  "title_template": "Лид {{first_name}}",
  "amount": null,
  "currency": "USD",
  "save_deal_id_to": "deal_id",
  "assign_manager_id": null
}
```

## 14.10. Move Deal Stage
`type = move_deal_stage`

### config:
```json
{
  "deal_id_source": "{{deal_id}}",
  "target_stage_id": 21
}
```

## 14.11. Set Funnel Step
`type = set_funnel_step`

### config:
```json
{
  "funnel_id": 3,
  "step_id": 11,
  "create_entry_if_missing": true
}
```

## 14.12. Insert Message Template
`type = insert_message_template`

### config:
```json
{
  "template_id": 44,
  "version_mode": "latest_published|fixed",
  "version_id": null,
  "override_vars": {
    "cta_text": "Записаться"
  }
}
```

## 14.13. Insert Reusable Block
`type = insert_reusable_block`

### config:
```json
{
  "reusable_block_id": 13,
  "version_mode": "latest_published|fixed",
  "version_id": null,
  "insertion_mode": "linked|copied"
}
```

---

# 15. Полный список обязательных блоков редактора

## Trigger blocks
- Start
- Start by command
- Start by message
- Start by callback
- Start by webhook
- Start by schedule
- Start by tag
- Start by deep link
- Manual start

## Message blocks
- Send Text
- Send Photo
- Send Video
- Send Audio
- Send Voice
- Send Document
- Send Media Group
- Edit Message
- Delete Message
- Answer Callback
- Send Chat Action

## Input blocks
- Wait Input
- Wait Number
- Wait Phone
- Wait Email
- Wait Date
- Wait File
- Wait Contact
- Wait Location
- Choice Input

## Logic blocks
- Condition
- Switch
- Compare
- Random Split
- Percentage Split
- A/B Split
- Loop Guard
- Anti-spam limiter

## Action blocks
- Set Variable
- Increment Variable
- Math Operation
- String Operation
- Date Operation
- Add Tag
- Remove Tag
- Save Contact Field
- Create Note
- Assign Manager
- Start Process
- Start Subprocess
- Stop Process
- End
- HTTP Request
- Trigger Internal Event

## Time blocks
- Delay
- Wait Until
- Working Hours Filter
- Schedule Step
- Reminder After No Reply

## Template blocks
- Insert Message Template
- Insert Reusable Block
- Template-based Sequence

## Funnel blocks
- Enter Funnel
- Set Funnel Step
- Track Event
- Exit Funnel

## Deal blocks
- Create Deal
- Update Deal
- Move Deal Stage
- Set Deal Amount
- Assign Deal Manager
- Close Deal Won
- Close Deal Lost
- Create Deal Note
- Create Deal Task

## Service blocks
- Comment
- Annotation
- Label
- Jump
- Debug Marker

---

# 16. Runtime engine: формальная модель

## 16.1. Главный принцип
Graph-process не исполняется “визуально”.  
Он должен быть **compiled** в нормализованную исполняемую модель.

## 16.2. Execution context

Для каждого запуска создается context:

```json
{
  "execution_id": 1001,
  "account_id": 1,
  "project_id": 4,
  "bot_id": 9,
  "process_id": 22,
  "process_version_id": 57,
  "contact_id": 777,
  "trigger": {
    "type": "message",
    "telegram_update_id": 123456789,
    "payload": {}
  },
  "vars": {
    "contact": {},
    "execution": {},
    "system": {}
  },
  "current_node_uuid": "....",
  "visited_nodes": [],
  "step_count": 0,
  "status": "running"
}
```

## 16.3. Статусы execution
- running
- waiting
- scheduled
- completed
- failed
- cancelled

## 16.4. Правила исполнения
1. Найти trigger.
2. Найти process version.
3. Загрузить compiled graph.
4. Захватить lock на contact.
5. Создать execution.
6. Войти в start node.
7. Выполнять блоки последовательно.
8. На каждом шаге писать `execution_steps`.
9. Если блок требует ожидание — создать `waiting_state`, завершить текущий run.
10. Если delay — создать `scheduled_job`.
11. Если ошибка — логировать, выставлять failed/retry.
12. При завершении — completed.

## 16.5. Loop guard
У каждого execution:
- max_steps_per_run = 500
- max_same_node_hits = 20
- max_runtime_seconds = 30 за один синхронный проход

Если лимит достигнут:
- execution = failed
- ошибка code = `LOOP_GUARD_TRIGGERED`

## 16.6. Wait states
Если процесс ждет ответ пользователя, создается `waiting_state`.

Поля:
- waiting_for_input_type
- expected_node_uuid
- expires_at
- attempt_count
- max_attempts
- save_to_key
- validation_rules_json
- timeout_transition_port

## 16.7. Resume logic
При новом входящем update:
1. Определить bot.
2. Определить contact.
3. Проверить active waiting states.
4. Если есть подходящий waiting state — резюмировать execution.
5. Иначе искать новые triggers.

---

# 17. Locking и concurrency control

Одновременная обработка одного контакта не должна ломать состояние.

## 17.1. Lock strategy
Использовать DB-based locking.

### Таблица `locks`:
- id
- lock_key
- owner_token
- expires_at
- created_at
- updated_at

`lock_key` формат:
- `contact:{contact_id}`
- `bot:{bot_id}`
- `execution:{execution_id}`

## 17.2. Правила
1. Перед запуском execution брать `contact:{contact_id}`.
2. TTL lock = 60 секунд.
3. Продление lock при длинном run.
4. Если lock занят — событие можно:
   - положить в queue delayed retry;
   - либо отклонить как duplicate/concurrent.
5. Locks должны иметь cleanup.

---

# 18. Idempotency и duplicate protection

Telegram updates нужно обрабатывать идемпотентно.

## 18.1. inbound_updates
- id BIGINT UNSIGNED PK
- bot_id BIGINT UNSIGNED
- telegram_update_id BIGINT
- update_type VARCHAR(64)
- payload_json JSON
- payload_hash CHAR(64)
- received_at DATETIME
- processed_at DATETIME NULL
- status ENUM('received','processed','ignored','failed')
- process_result_json JSON NULL

UNIQUE:
- (bot_id, telegram_update_id)

## 18.2. Правила idempotency
1. Сначала записать update в `inbound_updates`.
2. Если UNIQUE conflict по `(bot_id, telegram_update_id)`:
   - считать update duplicate;
   - не исполнять повторно.
3. Для внутренних API-запросов использовать `Idempotency-Key`.
4. Для scheduled jobs использовать unique execution key.

---

# 19. Очередь задач

Нужна MySQL-based queue.

## 19.1. job_queue
- id BIGINT UNSIGNED PK
- account_id BIGINT UNSIGNED NULL
- project_id BIGINT UNSIGNED NULL
- queue_name VARCHAR(64)
- job_type VARCHAR(64)
- payload_json JSON
- unique_key VARCHAR(190) NULL
- status ENUM('pending','reserved','running','completed','failed','cancelled')
- attempts INT DEFAULT 0
- max_attempts INT DEFAULT 5
- available_at DATETIME
- reserved_at DATETIME NULL
- completed_at DATETIME NULL
- failed_at DATETIME NULL
- last_error TEXT NULL
- created_at DATETIME
- updated_at DATETIME

UNIQUE nullable:
- unique_key

## 19.2. Типы jobs
- process_resume
- process_start
- send_message
- broadcast_send
- delay_resume
- http_retry
- compile_graph
- analytics_aggregate
- cleanup
- marketplace_import
- template_install

---

# 20. Failed jobs

## failed_jobs
- id BIGINT UNSIGNED PK
- job_queue_id BIGINT UNSIGNED
- job_type VARCHAR(64)
- payload_json JSON
- attempts INT
- error_code VARCHAR(64) NULL
- error_message TEXT
- stack_trace TEXT NULL
- failed_at DATETIME

---

# 21. Scheduler

## scheduled_jobs
- id BIGINT UNSIGNED PK
- account_id BIGINT UNSIGNED
- project_id BIGINT UNSIGNED
- bot_id BIGINT UNSIGNED NULL
- contact_id BIGINT UNSIGNED NULL
- execution_id BIGINT UNSIGNED NULL
- job_type VARCHAR(64)
- payload_json JSON
- run_at DATETIME
- status ENUM('scheduled','queued','completed','cancelled','failed')
- created_at DATETIME
- updated_at DATETIME

---

# 22. Контакты и CRM-слой

## 22.1. contacts
- id BIGINT UNSIGNED PK
- account_id BIGINT UNSIGNED
- project_id BIGINT UNSIGNED
- bot_id BIGINT UNSIGNED
- telegram_user_id BIGINT NULL
- telegram_chat_id BIGINT NULL
- username VARCHAR(190) NULL
- first_name VARCHAR(190) NULL
- last_name VARCHAR(190) NULL
- phone VARCHAR(50) NULL
- email VARCHAR(190) NULL
- language_code VARCHAR(16) NULL
- timezone VARCHAR(64) NULL
- source VARCHAR(128) NULL
- status ENUM('active','blocked','archived')
- assigned_manager_id BIGINT UNSIGNED NULL
- current_process_id BIGINT UNSIGNED NULL
- current_process_version_id BIGINT UNSIGNED NULL
- current_node_uuid CHAR(36) NULL
- last_activity_at DATETIME NULL
- last_message_at DATETIME NULL
- contact_fields_json JSON NULL
- created_at DATETIME
- updated_at DATETIME

INDEX:
- (bot_id, telegram_user_id)
- (project_id, phone)
- (project_id, email)

## 22.2. contact_fields
- id BIGINT UNSIGNED PK
- account_id BIGINT UNSIGNED
- project_id BIGINT UNSIGNED
- code VARCHAR(128)
- name VARCHAR(190)
- field_type ENUM('text','textarea','number','boolean','date','datetime','json','enum')
- settings_json JSON NULL
- is_system TINYINT(1) DEFAULT 0
- created_at DATETIME
- updated_at DATETIME

UNIQUE:
- (project_id, code)

## 22.3. contact_custom_values
- id BIGINT UNSIGNED PK
- contact_id BIGINT UNSIGNED
- field_id BIGINT UNSIGNED
- value_text TEXT NULL
- value_number DECIMAL(18,4) NULL
- value_bool TINYINT(1) NULL
- value_date DATE NULL
- value_datetime DATETIME NULL
- value_json JSON NULL
- updated_at DATETIME

UNIQUE:
- (contact_id, field_id)

## 22.4. tags
- id BIGINT UNSIGNED PK
- account_id BIGINT UNSIGNED
- project_id BIGINT UNSIGNED
- code VARCHAR(128)
- name VARCHAR(190)
- color VARCHAR(32) NULL
- created_at DATETIME
- updated_at DATETIME

UNIQUE:
- (project_id, code)

## 22.5. contact_tags
- id BIGINT UNSIGNED PK
- contact_id BIGINT UNSIGNED
- tag_id BIGINT UNSIGNED
- created_at DATETIME
- created_by BIGINT UNSIGNED NULL

UNIQUE:
- (contact_id, tag_id)

---

# 23. Чаты и сообщения

## 23.1. chats
- id BIGINT UNSIGNED PK
- account_id BIGINT UNSIGNED
- project_id BIGINT UNSIGNED
- bot_id BIGINT UNSIGNED
- contact_id BIGINT UNSIGNED
- telegram_chat_id BIGINT
- mode ENUM('auto','manual','hybrid')
- unread_count INT DEFAULT 0
- last_message_at DATETIME NULL
- created_at DATETIME
- updated_at DATETIME

UNIQUE:
- (bot_id, telegram_chat_id)

## 23.2. chat_messages
- id BIGINT UNSIGNED PK
- chat_id BIGINT UNSIGNED
- contact_id BIGINT UNSIGNED
- bot_id BIGINT UNSIGNED
- direction ENUM('in','out','system','operator')
- telegram_message_id BIGINT NULL
- telegram_update_id BIGINT NULL
- message_type VARCHAR(64)
- text_content TEXT NULL
- media_json JSON NULL
- buttons_json JSON NULL
- payload_json JSON NULL
- status ENUM('sent','delivered','failed','received','read')
- created_at DATETIME
- updated_at DATETIME

INDEX:
- (chat_id, created_at)

## 23.3. outbound_messages
- id BIGINT UNSIGNED PK
- bot_id BIGINT UNSIGNED
- contact_id BIGINT UNSIGNED
- chat_id BIGINT UNSIGNED
- execution_id BIGINT UNSIGNED NULL
- node_uuid CHAR(36) NULL
- telegram_message_id BIGINT NULL
- message_type VARCHAR(64)
- request_json JSON
- response_json JSON NULL
- send_status ENUM('pending','sent','failed')
- error_code VARCHAR(64) NULL
- error_message TEXT NULL
- sent_at DATETIME NULL
- created_at DATETIME
- updated_at DATETIME

---

# 24. Executions

## 24.1. executions
- id BIGINT UNSIGNED PK
- account_id BIGINT UNSIGNED
- project_id BIGINT UNSIGNED
- bot_id BIGINT UNSIGNED
- process_id BIGINT UNSIGNED
- process_version_id BIGINT UNSIGNED
- contact_id BIGINT UNSIGNED
- parent_execution_id BIGINT UNSIGNED NULL
- trigger_type VARCHAR(64)
- trigger_ref VARCHAR(190) NULL
- trigger_payload_json JSON NULL
- current_node_uuid CHAR(36) NULL
- status ENUM('running','waiting','scheduled','completed','failed','cancelled')
- step_count INT DEFAULT 0
- context_json JSON
- started_at DATETIME
- finished_at DATETIME NULL
- created_at DATETIME
- updated_at DATETIME

## 24.2. execution_steps
- id BIGINT UNSIGNED PK
- execution_id BIGINT UNSIGNED
- process_version_id BIGINT UNSIGNED
- node_uuid CHAR(36)
- node_type VARCHAR(64)
- status ENUM('entered','completed','waiting','failed','skipped')
- input_json JSON NULL
- output_json JSON NULL
- error_code VARCHAR(64) NULL
- error_message TEXT NULL
- duration_ms INT NULL
- created_at DATETIME

INDEX:
- (execution_id, created_at)

## 24.3. waiting_states
- id BIGINT UNSIGNED PK
- execution_id BIGINT UNSIGNED
- account_id BIGINT UNSIGNED
- project_id BIGINT UNSIGNED
- bot_id BIGINT UNSIGNED
- contact_id BIGINT UNSIGNED
- node_uuid CHAR(36)
- input_type VARCHAR(64)
- save_to_key VARCHAR(128)
- validation_rules_json JSON NULL
- timeout_port VARCHAR(64) NULL
- invalid_port VARCHAR(64) NULL
- success_port VARCHAR(64) NULL
- attempt_count INT DEFAULT 0
- max_attempts INT DEFAULT 3
- expires_at DATETIME NULL
- status ENUM('active','resolved','expired','cancelled')
- created_at DATETIME
- updated_at DATETIME

INDEX:
- (contact_id, status)

---

# 25. Broadcasts

## 25.1. broadcasts
- id BIGINT UNSIGNED PK
- account_id BIGINT UNSIGNED
- project_id BIGINT UNSIGNED
- bot_id BIGINT UNSIGNED
- name VARCHAR(190)
- segment_json JSON
- message_payload_json JSON
- status ENUM('draft','scheduled','running','paused','completed','cancelled','failed')
- scheduled_at DATETIME NULL
- started_at DATETIME NULL
- finished_at DATETIME NULL
- created_by BIGINT UNSIGNED
- created_at DATETIME
- updated_at DATETIME

## 25.2. broadcast_recipients
- id BIGINT UNSIGNED PK
- broadcast_id BIGINT UNSIGNED
- contact_id BIGINT UNSIGNED
- status ENUM('pending','sent','failed','skipped')
- error_message TEXT NULL
- sent_at DATETIME NULL
- created_at DATETIME
- updated_at DATETIME

UNIQUE:
- (broadcast_id, contact_id)

---

# 26. Message templates

## 26.1. message_templates
- id BIGINT UNSIGNED PK
- account_id BIGINT UNSIGNED
- project_id BIGINT UNSIGNED NULL
- name VARCHAR(190)
- slug VARCHAR(190)
- category_id BIGINT UNSIGNED NULL
- template_type VARCHAR(64)
- description TEXT NULL
- status ENUM('draft','published','archived')
- active_version_id BIGINT UNSIGNED NULL
- is_system TINYINT(1) DEFAULT 0
- is_marketplace_item TINYINT(1) DEFAULT 0
- created_by BIGINT UNSIGNED
- created_at DATETIME
- updated_at DATETIME

UNIQUE:
- (account_id, slug)

## 26.2. message_template_versions
- id BIGINT UNSIGNED PK
- template_id BIGINT UNSIGNED
- version_number INT
- payload_json JSON
- preview_text TEXT NULL
- changelog TEXT NULL
- status ENUM('draft','published','archived')
- created_by BIGINT UNSIGNED
- created_at DATETIME
- updated_at DATETIME

UNIQUE:
- (template_id, version_number)

---

# 27. Reusable blocks

## 27.1. reusable_blocks
- id BIGINT UNSIGNED PK
- account_id BIGINT UNSIGNED
- project_id BIGINT UNSIGNED NULL
- name VARCHAR(190)
- slug VARCHAR(190)
- description TEXT NULL
- category_id BIGINT UNSIGNED NULL
- status ENUM('draft','published','archived')
- active_version_id BIGINT UNSIGNED NULL
- created_by BIGINT UNSIGNED
- created_at DATETIME
- updated_at DATETIME

UNIQUE:
- (account_id, slug)

## 27.2. reusable_block_versions
- id BIGINT UNSIGNED PK
- reusable_block_id BIGINT UNSIGNED
- version_number INT
- graph_json JSON
- compiled_graph_json JSON NULL
- input_contract_json JSON NULL
- output_contract_json JSON NULL
- changelog TEXT NULL
- status ENUM('draft','published','archived')
- created_by BIGINT UNSIGNED
- created_at DATETIME
- updated_at DATETIME

UNIQUE:
- (reusable_block_id, version_number)

## 27.3. template_usages
- id BIGINT UNSIGNED PK
- account_id BIGINT UNSIGNED
- project_id BIGINT UNSIGNED
- entity_type ENUM('message_template','reusable_block','process_template')
- entity_id BIGINT UNSIGNED
- entity_version_id BIGINT UNSIGNED NULL
- used_in_type ENUM('process','message_node','marketplace_item')
- used_in_id BIGINT UNSIGNED
- insertion_mode ENUM('linked','copied')
- created_at DATETIME

---

# 28. Process templates

## process_template_library
- id BIGINT UNSIGNED PK
- account_id BIGINT UNSIGNED
- project_id BIGINT UNSIGNED NULL
- name VARCHAR(190)
- slug VARCHAR(190)
- description TEXT NULL
- category_id BIGINT UNSIGNED NULL
- graph_json JSON
- compiled_graph_json JSON NULL
- meta_json JSON NULL
- status ENUM('draft','published','archived')
- version_number INT DEFAULT 1
- created_by BIGINT UNSIGNED
- created_at DATETIME
- updated_at DATETIME

---

# 29. Funnels и аналитика

## 29.1. funnels
- id BIGINT UNSIGNED PK
- account_id BIGINT UNSIGNED
- project_id BIGINT UNSIGNED
- bot_id BIGINT UNSIGNED NULL
- name VARCHAR(190)
- slug VARCHAR(190)
- description TEXT NULL
- status ENUM('draft','published','archived')
- version_number INT DEFAULT 1
- settings_json JSON NULL
- created_by BIGINT UNSIGNED
- created_at DATETIME
- updated_at DATETIME

UNIQUE:
- (project_id, slug)

## 29.2. funnel_steps
- id BIGINT UNSIGNED PK
- funnel_id BIGINT UNSIGNED
- step_code VARCHAR(128)
- name VARCHAR(190)
- step_order INT
- trigger_type ENUM('node','tag','event','field','manual')
- trigger_config_json JSON
- is_entry_step TINYINT(1) DEFAULT 0
- is_final_step TINYINT(1) DEFAULT 0
- created_at DATETIME
- updated_at DATETIME

UNIQUE:
- (funnel_id, step_code)

## 29.3. funnel_entries
- id BIGINT UNSIGNED PK
- funnel_id BIGINT UNSIGNED
- contact_id BIGINT UNSIGNED
- source VARCHAR(128) NULL
- current_step_id BIGINT UNSIGNED NULL
- entered_at DATETIME
- completed_at DATETIME NULL
- status ENUM('active','completed','dropped','archived')
- created_at DATETIME
- updated_at DATETIME

UNIQUE:
- (funnel_id, contact_id)

## 29.4. funnel_progress
- id BIGINT UNSIGNED PK
- funnel_entry_id BIGINT UNSIGNED
- funnel_id BIGINT UNSIGNED
- contact_id BIGINT UNSIGNED
- step_id BIGINT UNSIGNED
- entered_at DATETIME
- exited_at DATETIME NULL
- duration_seconds INT NULL
- event_ref VARCHAR(190) NULL
- created_at DATETIME

INDEX:
- (funnel_id, step_id, entered_at)

## 29.5. funnel_step_events
- id BIGINT UNSIGNED PK
- funnel_id BIGINT UNSIGNED
- step_id BIGINT UNSIGNED
- contact_id BIGINT UNSIGNED
- event_type VARCHAR(64)
- event_payload_json JSON NULL
- created_at DATETIME

## 29.6. daily_stats
- id BIGINT UNSIGNED PK
- account_id BIGINT UNSIGNED
- project_id BIGINT UNSIGNED NULL
- bot_id BIGINT UNSIGNED NULL
- stat_date DATE
- metric_code VARCHAR(128)
- metric_value DECIMAL(18,4)
- meta_json JSON NULL
- created_at DATETIME

UNIQUE:
- (account_id, project_id, bot_id, stat_date, metric_code)

---

# 30. Mini-CRM сделок

## 30.1. pipelines
- id BIGINT UNSIGNED PK
- account_id BIGINT UNSIGNED
- project_id BIGINT UNSIGNED
- name VARCHAR(190)
- slug VARCHAR(190)
- description TEXT NULL
- status ENUM('active','archived')
- created_by BIGINT UNSIGNED
- created_at DATETIME
- updated_at DATETIME

## 30.2. pipeline_stages
- id BIGINT UNSIGNED PK
- pipeline_id BIGINT UNSIGNED
- code VARCHAR(128)
- name VARCHAR(190)
- sort_order INT
- probability_percent INT DEFAULT 0
- is_won TINYINT(1) DEFAULT 0
- is_lost TINYINT(1) DEFAULT 0
- color VARCHAR(32) NULL
- created_at DATETIME
- updated_at DATETIME

UNIQUE:
- (pipeline_id, code)

## 30.3. deals
- id BIGINT UNSIGNED PK
- account_id BIGINT UNSIGNED
- project_id BIGINT UNSIGNED
- bot_id BIGINT UNSIGNED NULL
- contact_id BIGINT UNSIGNED
- pipeline_id BIGINT UNSIGNED
- stage_id BIGINT UNSIGNED
- title VARCHAR(255)
- amount DECIMAL(18,2) NULL
- currency VARCHAR(16) DEFAULT 'USD'
- status ENUM('open','won','lost','archived')
- source VARCHAR(128) NULL
- manager_id BIGINT UNSIGNED NULL
- probability_percent INT DEFAULT 0
- expected_close_date DATE NULL
- won_at DATETIME NULL
- lost_at DATETIME NULL
- loss_reason VARCHAR(255) NULL
- metadata_json JSON NULL
- created_by BIGINT UNSIGNED NULL
- created_at DATETIME
- updated_at DATETIME

## 30.4. deal_notes
- id BIGINT UNSIGNED PK
- deal_id BIGINT UNSIGNED
- author_user_id BIGINT UNSIGNED
- note_text TEXT
- created_at DATETIME
- updated_at DATETIME

## 30.5. deal_tasks
- id BIGINT UNSIGNED PK
- deal_id BIGINT UNSIGNED
- assigned_user_id BIGINT UNSIGNED NULL
- title VARCHAR(190)
- description TEXT NULL
- due_at DATETIME NULL
- status ENUM('open','completed','cancelled')
- completed_at DATETIME NULL
- created_by BIGINT UNSIGNED
- created_at DATETIME
- updated_at DATETIME

## 30.6. deal_activities
- id BIGINT UNSIGNED PK
- deal_id BIGINT UNSIGNED
- activity_type VARCHAR(64)
- payload_json JSON NULL
- created_by BIGINT UNSIGNED NULL
- created_at DATETIME

## 30.7. deal_status_history
- id BIGINT UNSIGNED PK
- deal_id BIGINT UNSIGNED
- from_stage_id BIGINT UNSIGNED NULL
- to_stage_id BIGINT UNSIGNED NULL
- old_status VARCHAR(64) NULL
- new_status VARCHAR(64) NULL
- comment TEXT NULL
- changed_by BIGINT UNSIGNED NULL
- created_at DATETIME

---

# 31. Marketplace

## 31.1. marketplace_items
- id BIGINT UNSIGNED PK
- account_id BIGINT UNSIGNED NULL
- owner_user_id BIGINT UNSIGNED NULL
- item_type ENUM('process_template','reusable_block','message_template','funnel_template','pipeline_template')
- source_entity_id BIGINT UNSIGNED
- name VARCHAR(190)
- slug VARCHAR(190)
- short_description VARCHAR(255) NULL
- full_description TEXT NULL
- category_id BIGINT UNSIGNED NULL
- visibility ENUM('private','account','system')
- status ENUM('draft','published','archived')
- latest_version_id BIGINT UNSIGNED NULL
- icon_path VARCHAR(255) NULL
- preview_image_path VARCHAR(255) NULL
- install_count INT DEFAULT 0
- created_at DATETIME
- updated_at DATETIME

## 31.2. marketplace_item_versions
- id BIGINT UNSIGNED PK
- marketplace_item_id BIGINT UNSIGNED
- version_number INT
- package_json JSON
- compatibility_json JSON NULL
- changelog TEXT NULL
- status ENUM('draft','published','archived')
- created_at DATETIME
- updated_at DATETIME

## 31.3. marketplace_installs
- id BIGINT UNSIGNED PK
- marketplace_item_id BIGINT UNSIGNED
- marketplace_item_version_id BIGINT UNSIGNED
- account_id BIGINT UNSIGNED
- project_id BIGINT UNSIGNED NULL
- installed_entity_type VARCHAR(64)
- installed_entity_id BIGINT UNSIGNED
- installed_at DATETIME
- created_at DATETIME

## 31.4. marketplace_categories
- id BIGINT UNSIGNED PK
- code VARCHAR(128)
- name VARCHAR(190)
- item_type VARCHAR(64) NULL
- sort_order INT DEFAULT 0
- created_at DATETIME
- updated_at DATETIME

---

# 32. API tokens и интеграции

## api_tokens
- id BIGINT UNSIGNED PK
- account_id BIGINT UNSIGNED
- project_id BIGINT UNSIGNED NULL
- name VARCHAR(190)
- token_hash VARCHAR(255)
- scopes_json JSON
- expires_at DATETIME NULL
- last_used_at DATETIME NULL
- created_by BIGINT UNSIGNED
- created_at DATETIME
- updated_at DATETIME

## webhook_logs
- id BIGINT UNSIGNED PK
- bot_id BIGINT UNSIGNED
- endpoint_type ENUM('telegram_in','external_in','external_out')
- request_headers_json JSON NULL
- request_body_json JSON NULL
- response_code INT NULL
- response_body TEXT NULL
- status ENUM('ok','error')
- created_at DATETIME

## http_request_logs
- id BIGINT UNSIGNED PK
- account_id BIGINT UNSIGNED
- project_id BIGINT UNSIGNED
- execution_id BIGINT UNSIGNED NULL
- node_uuid CHAR(36) NULL
- method VARCHAR(16)
- url VARCHAR(500)
- request_headers_json JSON NULL
- request_body_json JSON NULL
- response_code INT NULL
- response_headers_json JSON NULL
- response_body TEXT NULL
- duration_ms INT NULL
- status ENUM('success','error','timeout')
- created_at DATETIME

---

# 33. Файлы и медиа

## uploaded_files
- id BIGINT UNSIGNED PK
- account_id BIGINT UNSIGNED
- project_id BIGINT UNSIGNED NULL
- uploader_user_id BIGINT UNSIGNED NULL
- original_name VARCHAR(255)
- stored_name VARCHAR(255)
- mime_type VARCHAR(128)
- size_bytes BIGINT
- storage_path VARCHAR(255)
- sha256 CHAR(64)
- created_at DATETIME

## media_library
- id BIGINT UNSIGNED PK
- account_id BIGINT UNSIGNED
- project_id BIGINT UNSIGNED
- title VARCHAR(190)
- type VARCHAR(64)
- file_id BIGINT UNSIGNED
- tags_json JSON NULL
- meta_json JSON NULL
- created_at DATETIME
- updated_at DATETIME

---

# 34. Audit log

## audit_logs
- id BIGINT UNSIGNED PK
- account_id BIGINT UNSIGNED NULL
- project_id BIGINT UNSIGNED NULL
- user_id BIGINT UNSIGNED NULL
- entity_type VARCHAR(64)
- entity_id BIGINT UNSIGNED NULL
- action VARCHAR(64)
- before_json JSON NULL
- after_json JSON NULL
- ip_address VARCHAR(64) NULL
- user_agent VARCHAR(255) NULL
- created_at DATETIME

---

# 35. Editor UI: точная спецификация

## 35.1. Layout
- левая колонка: palette blocks
- центральная область: canvas
- правая колонка: inspector/properties
- верхняя панель: save/publish/undo/redo/zoom/search/validate
- нижняя панель: validation + debug

## 35.2. Возможности
- drag-and-drop блоков
- соединение линиями
- удаление линий
- copy/paste
- duplicate
- multiselect
- align
- auto-layout basics
- zoom in/out
- pan
- minimap
- autosave
- node search
- node comments
- groups
- version selector
- publish button
- validation report

## 35.3. Техническая реализация
- nodes: HTML elements
- edges: SVG overlay
- state: JS store in memory
- autosave: debounce 800–1200 ms
- drag events: native pointer/mouse events
- no framework

---

# 36. Compiled graph model

`compiled_graph_json` должен содержать нормализованный graph для runtime.

## Формат:
```json
{
  "version": "1.0.0",
  "entrypoints": [
    {
      "trigger_type": "command",
      "key": "/start",
      "node_uuid": "..."
    }
  ],
  "nodes": {
    "node_uuid_1": {
      "type": "send_text",
      "config": {},
      "next": [
        {
          "port": "next",
          "target": "node_uuid_2"
        }
      ]
    }
  },
  "guards": {
    "max_steps": 500,
    "max_same_node_hits": 20
  }
}
```

## Что делает compiler
1. Проверяет graph.
2. Нормализует узлы.
3. Индексирует entrypoints.
4. Индексирует transitions.
5. Проверяет contracts reusable blocks.
6. Формирует runtime-friendly JSON.
7. Пишет hash.
8. Кладет копию в `/storage/compiled_graphs`.

---

# 37. Process publishing flow

1. Пользователь редактирует draft version.
2. Нажимает Validate.
3. Graph validator возвращает:
   - errors
   - warnings
4. Если ошибок нет — можно Publish.
5. При Publish:
   - version.status = published
   - processes.active_version_id = version.id
   - compiled graph обновляется
   - старые waiting states не ломаются:
     - новые входы идут в новую версию
     - старые executions продолжают свою version

---

# 38. Message templates: payload contract

`message_template_versions.payload_json`

```json
{
  "type": "text",
  "parse_mode": "html",
  "text": "Здравствуйте, {{first_name}}",
  "buttons": [
    { "type": "callback", "text": "Продолжить", "value": "continue" }
  ],
  "reply_keyboard": [],
  "media": null,
  "vars": [
    "first_name"
  ]
}
```

---

# 39. Reusable blocks: contract

`reusable_block_versions.input_contract_json`

```json
{
  "required_vars": ["contact.id"],
  "optional_vars": ["offer_id"],
  "settings": [
    { "key": "cta_text", "type": "string", "required": false }
  ]
}
```

`output_contract_json`

```json
{
  "exports": [
    "phone",
    "client_name",
    "quiz_score"
  ],
  "ports": ["success", "invalid", "timeout"]
}
```

---

# 40. Marketplace package format

`marketplace_item_versions.package_json`

```json
{
  "package_version": "1.0.0",
  "item_type": "process_template",
  "title": "Welcome funnel",
  "description": "Базовая приветственная воронка",
  "compatibility": {
    "engine_min": "1.0.0",
    "engine_max": "2.x"
  },
  "content": {
    "graph_json": {},
    "compiled_graph_json": {},
    "meta": {}
  },
  "assets": [],
  "dependencies": []
}
```

---

# 41. Funnel analytics model

## 41.1. События
Каждое прохождение этапа должно создавать запись в `funnel_progress`.

## 41.2. Метрики
В системе должны считаться:
- entries
- completions
- drop-offs
- conversion from step A to step B
- avg time to next step
- bottleneck step

## 41.3. Формула конверсии
`conversion(A→B) = unique_contacts_on_B / unique_contacts_on_A`

---

# 42. Deal CRM: UI представления

## 42.1. Kanban
Колонки = pipeline stages.

В карточке сделки:
- title
- contact
- amount
- manager
- last activity
- tags
- due tasks

## 42.2. Table view
Поля:
- title
- contact
- pipeline
- stage
- manager
- amount
- expected close
- status
- created
- updated

---

# 43. Inbox mode switching

У чата должен быть режим:
- `auto`
- `manual`
- `hybrid`

## Правила:
- `auto`: работает только бот
- `manual`: process sending отключен, менеджер отвечает вручную
- `hybrid`: бот и менеджер могут работать одновременно по правилам

---

# 44. Telegram integration layer: точные требования

## 44.1. Поддержать методы
- getMe
- setWebhook
- deleteWebhook
- getWebhookInfo
- sendMessage
- sendPhoto
- sendVideo
- sendAudio
- sendVoice
- sendDocument
- sendMediaGroup
- editMessageText
- deleteMessage
- answerCallbackQuery
- sendChatAction
- setMyCommands
- getFile

## 44.2. Поддержать update types
- message
- edited_message
- callback_query
- my_chat_member
- chat_member

Архитектурно подготовить:
- inline_query
- pre_checkout_query
- successful_payment

## 44.3. TelegramService API
Каждый метод:
- принимает DTO/array payload
- валидирует payload
- делает HTTP request
- пишет лог
- возвращает structured response

---

# 45. Безопасность

## 45.1. Пароли
Использовать `password_hash()` и `password_verify()`.

## 45.2. SQL
Использовать только PDO prepared statements.

## 45.3. Обязательные меры
- CSRF tokens
- output escaping
- input validation
- file MIME whitelist
- file size limits
- encryption for bot tokens
- secret masking in logs
- session rotation after login
- rate limiting for login and API
- webhook secret validation
- audit trail
- deny-by-default RBAC
- tenant boundary checks
- installer lock after installation

---

# 46. Установка и обновление

## 46.1. Installer
`/install/index.php`

Шаги:
1. Проверка PHP 8.2+
2. Проверка расширений:
   - pdo
   - pdo_mysql
   - mbstring
   - openssl
   - json
   - fileinfo
   - curl
3. Проверка прав директорий
4. Ввод настроек БД
5. Создание config.local.php
6. Запуск миграций
7. Seed system roles/permissions
8. Создание super admin
9. Создание account owner
10. Установка флага installed

## 46.2. Updater
`/update/index.php`

Функции:
- показать текущую версию
- сравнить миграции
- применить pending migrations
- очистить cache
- recompilation compiled graphs
- лог обновления

---

# 47. Cron scripts

## 47.1. `queue_worker.php`
- берет pending jobs
- исполняет
- обновляет статус
- переносит ошибки в failed_jobs

## 47.2. `scheduler.php`
- ищет scheduled_jobs, где run_at <= now
- превращает их в queue jobs

## 47.3. `retry_failed_jobs.php`
- по retry policy возвращает retryable jobs в queue

## 47.4. `cleanup.php`
- удаляет expired locks
- чистит temp/log rotation
- архивирует старые записи

## 47.5. `stats_aggregate.php`
- агрегирует funnel/deal/broadcast metrics в daily_stats

## 47.6. `compile_graphs.php`
- компилирует pending modified draft/published graphs

---

# 48. API endpoints: минимальный обязательный набор

## Auth
- POST `/api/auth/login`
- POST `/api/auth/logout`
- POST `/api/auth/forgot-password`
- POST `/api/auth/reset-password`

## Projects
- GET `/api/projects`
- POST `/api/projects`
- GET `/api/projects/{id}`
- PUT `/api/projects/{id}`

## Bots
- POST `/api/bots`
- POST `/api/bots/{id}/verify`
- POST `/api/bots/{id}/set-webhook`
- POST `/api/bots/{id}/delete-webhook`

## Processes
- GET `/api/processes`
- POST `/api/processes`
- GET `/api/processes/{id}`
- POST `/api/processes/{id}/versions`
- PUT `/api/process-versions/{id}`
- POST `/api/process-versions/{id}/validate`
- POST `/api/process-versions/{id}/publish`

## Contacts
- GET `/api/contacts`
- GET `/api/contacts/{id}`
- PUT `/api/contacts/{id}`
- POST `/api/contacts/{id}/tags`
- DELETE `/api/contacts/{id}/tags/{tagId}`

## Inbox
- GET `/api/chats`
- GET `/api/chats/{id}/messages`
- POST `/api/chats/{id}/send-message`
- POST `/api/chats/{id}/mode`

## Templates
- CRUD message templates
- CRUD reusable blocks
- install template
- export/import template

## Funnels
- CRUD funnels
- GET funnel analytics

## Deals
- CRUD pipelines
- CRUD deals
- move deal stage
- add note
- add task

## Marketplace
- GET items
- GET item details
- POST install
- POST export
- POST import

---

# 49. Demo templates, которые Codex обязан создать

1. Welcome funnel
2. Lead capture with phone
3. 3-question quiz
4. Reminder after 1 day
5. External webhook start
6. Consultation booking flow
7. Sales follow-up flow
8. Lead qualification reusable block
9. Deal creation after qualified answer
10. Mini onboarding sequence via message template

---

# 50. Acceptance criteria: CTO-level

Система считается выполненной, если:

1. Устанавливается из браузера.
2. Работает из корня сайта без `public/`.
3. Можно создать account, project, bot.
4. Можно подключить Telegram bot token.
5. `setWebhook` работает.
6. Telegram update сохраняется идемпотентно.
7. Можно создать process и draft version.
8. Можно рисовать graph.
9. Можно валидировать graph.
10. Можно публиковать version.
11. `/start` запускает process.
12. `send_text` реально отправляет сообщение.
13. `wait_input` реально ждет ответ.
14. Ответ сохраняется в variable/contact field.
15. `condition` корректно ветвится.
16. `delay` создает scheduled job.
17. `http_request` реально вызывает внешний API.
18. Все execution steps логируются.
19. Есть active wait state.
20. Есть resumed execution.
21. Есть reusable block, который можно сохранить и вставить в другой process.
22. Есть message template и вставка его в node.
23. Есть funnel с учетом step transitions.
24. Видна конверсия по funnel steps.
25. Есть pipeline.
26. Есть deal.
27. Deal создается из process.
28. Deal двигается по stage.
29. Kanban работает.
30. Marketplace item можно установить в проект.
31. Экспорт/импорт template package работает.
32. Audit log пишет критичные действия.
33. Locking не допускает двойной конфликтной обработки одного контакта.
34. Failed jobs и retry policy работают.

---

# 51. Что должен сделать Codex в первой поставке

Codex должен не писать “план проекта”, а создать реальный кодовый каркас и работающий MVP foundation.

### Обязательный первый результат:
1. структура проекта;
2. router/core/auth/session/csrf/db;
3. installer/updater;
4. migration system;
5. Telegram client;
6. webhook receiver;
7. inbound_updates dedup;
8. projects/bots CRUD;
9. process + process_versions CRUD;
10. working graph editor MVP;
11. graph validator;
12. graph compiler;
13. runtime engine MVP;
14. contacts/chats/messages;
15. message sending;
16. wait state resume;
17. templates foundation;
18. funnel foundation;
19. deals foundation;
20. docs.

---

# 52. Готовый мастер-промт для Codex

```text
Создай полноценную self-hosted платформу для создания Telegram-чатботов с визуальным графическим редактором процессов.

Это должен быть функциональный аналог класса Salebot для Telegram-направления, но полностью реализованный с нуля, без копирования чужого кода, интерфейса, брендинга и проприетарных решений.

СТРОГОЕ ТЕХНИЧЕСКОЕ УСЛОВИЕ:
- PHP 8.2
- MySQL 8+
- HTML5
- CSS3
- Vanilla JavaScript
- без любых фреймворков
- без jQuery
- без public директории
- проект запускается из корневой папки сайта
- установка и обновление через PHP-скрипты в браузере
- развертывание на VPS Contabo
- без обязательного терминала

АРХИТЕКТУРА:
Нужен modular monolith с папками:

/
  index.php
  webhook.php
  api.php
  .htaccess
  /app
    /Core
    /Controllers
    /Services
    /Repositories
    /Modules
    /Validators
    /Policies
    /Helpers
    /Jobs
    /Runtime
    /Graph
    /Telegram
    /Integrations
    /Templates
    /Funnels
    /CRM
    /Marketplace
    /DTO
    /Middleware
    /Exceptions
  /config
  /database
    /migrations
    /seeds
  /storage
    /cache
    /compiled_graphs
    /logs
    /sessions
    /uploads
    /tmp
    /exports
    /imports
  /install
  /update
  /cron
  /assets
  /views
  /docs

ОБЯЗАТЕЛЬНЫЕ МОДУЛИ:
1. Auth / RBAC / Accounts / Users
2. Projects / Bots / Webhooks
3. Visual Process Editor
4. Graph Validator
5. Graph Compiler
6. Runtime Engine
7. Contacts CRM layer
8. Inbox / Chats / Messages
9. Broadcasts / Segments
10. HTTP/Webhook integrations
11. Message Templates
12. Reusable Blocks
13. Funnels + step analytics
14. Deals CRM + pipelines + kanban
15. Marketplace templates
16. Installer / Updater
17. Queue / Scheduler / Retry / Cleanup
18. Audit / Logs / Debug

ВАЖНО:
- не делать заглушки
- не делать игрушечный редактор
- runtime engine должен быть рабочим сразу
- любой published process должен реально исполняться
- Telegram webhook должен реально принимать updates
- idempotency и duplicate protection обязательны
- locking per contact обязателен
- prepared statements only
- password_hash/password_verify
- tenant isolation обязательно

НУЖНЫЕ ТАБЛИЦЫ:
accounts
users
account_users
roles
permissions
role_permissions
user_sessions
password_resets
projects
bots
bot_commands
bot_webhooks
processes
process_versions
process_nodes
process_edges
process_validation_errors
contacts
contact_fields
contact_custom_values
tags
contact_tags
chats
chat_messages
outbound_messages
inbound_updates
executions
execution_steps
waiting_states
job_queue
failed_jobs
scheduled_jobs
broadcasts
broadcast_recipients
message_templates
message_template_versions
reusable_blocks
reusable_block_versions
process_template_library
template_usages
funnels
funnel_steps
funnel_entries
funnel_progress
funnel_step_events
pipelines
pipeline_stages
deals
deal_notes
deal_tasks
deal_activities
deal_status_history
marketplace_items
marketplace_item_versions
marketplace_installs
marketplace_categories
api_tokens
webhook_logs
http_request_logs
uploaded_files
media_library
audit_logs
daily_stats
locks

GRAPH JSON FORMAT:
Каждый process_version.graph_json должен хранить:
- schema_version
- process_meta
- editor
- nodes[]
- edges[]
- comments[]
- groups[]

Каждый node:
- uuid
- type
- title
- position {x,y}
- size {w,h}
- ports
- config
- meta

Каждый edge:
- uuid
- from {node_uuid, port}
- to {node_uuid, port}
- condition_key
- sort_order

ОБЯЗАТЕЛЬНЫЕ NODE TYPES:
Trigger:
- start
- start_command
- start_message
- start_callback
- start_webhook
- start_schedule
- start_tag
- start_deep_link
- start_manual

Message:
- send_text
- send_photo
- send_video
- send_audio
- send_voice
- send_document
- send_media_group
- edit_message
- delete_message
- answer_callback
- send_chat_action

Input:
- wait_input
- wait_number
- wait_phone
- wait_email
- wait_date
- wait_file
- wait_contact
- wait_location
- wait_choice

Logic:
- condition
- switch
- compare
- random_split
- percentage_split
- ab_split
- loop_guard
- anti_spam_limiter

Actions:
- set_variable
- increment_variable
- math_operation
- string_operation
- date_operation
- add_tag
- remove_tag
- save_contact_field
- create_note
- assign_manager
- start_process
- start_subprocess
- stop_process
- end
- http_request
- trigger_event

Time:
- delay
- wait_until
- working_hours_filter
- schedule_step
- reminder_no_reply

Templates:
- insert_message_template
- insert_reusable_block
- template_sequence

Funnels:
- enter_funnel
- set_funnel_step
- track_event
- exit_funnel

Deals:
- create_deal
- update_deal
- move_deal_stage
- set_deal_amount
- assign_deal_manager
- close_deal_won
- close_deal_lost
- create_deal_note
- create_deal_task

Service:
- comment
- annotation
- label
- jump
- debug_marker

RUNTIME ENGINE:
Нужен полноценный engine исполнения compiled graph.

Execution flow:
1. принять trigger
2. найти bot/contact/process version
3. взять lock contact:{contact_id}
4. создать execution
5. пройти start node
6. выполнять шаги последовательно
7. писать execution_steps
8. при wait_input создать waiting_state и завершить current run
9. при delay создать scheduled_job
10. при ошибке логировать и применять retry policy
11. при завершении ставить completed

Execution limits:
- max_steps_per_run = 500
- max_same_node_hits = 20
- max_runtime_seconds_per_run = 30

WAITING STATES:
Должны хранить:
- execution_id
- contact_id
- node_uuid
- input_type
- save_to_key
- validation_rules_json
- attempt_count
- max_attempts
- expires_at
- success_port
- invalid_port
- timeout_port
- status

IDEMPOTENCY:
Telegram updates must be deduplicated by:
- UNIQUE(bot_id, telegram_update_id)
If duplicate exists:
- do not process again

LOCKING:
Нужен DB lock table:
- lock_key
- owner_token
- expires_at
Использовать contact:{contact_id} lock перед execution.

QUEUE:
Нужна MySQL queue:
- pending
- reserved
- running
- completed
- failed
- cancelled

Типы jobs:
- process_start
- process_resume
- send_message
- broadcast_send
- delay_resume
- http_retry
- compile_graph
- analytics_aggregate
- cleanup
- marketplace_import
- template_install

VISUAL EDITOR:
Нужен реальный визуальный редактор:
- drag and drop
- SVG edges
- node properties inspector
- zoom
- pan
- minimap
- autosave
- validate
- publish
- version selector
- comments
- groups
- search
- copy/paste
- multiselect

TEMPLATES:
Нужно реализовать:
1. Message Templates
2. Reusable Blocks
3. Process Templates

Message Template:
- name
- type
- category
- payload_json
- versions
- publish/archive
- insert into message nodes

Reusable Block:
- group of nodes saved as reusable entity
- versioning
- input_contract_json
- output_contract_json
- insert as linked or copied
- import/export JSON

FUNNELS:
Нужно реализовать:
- funnels
- funnel_steps
- funnel_entries
- funnel_progress
- step analytics
- conversion analytics
- avg time between steps
- filters by date/source/project/bot

DEALS CRM:
Нужно реализовать:
- pipelines
- pipeline stages
- deals
- deal notes
- deal tasks
- deal activities
- deal status history
- kanban board
- table view
- auto-create deal from process
- move stage from process nodes
- close won/lost

MARKETPLACE:
Нужно реализовать:
- marketplace_items
- marketplace_item_versions
- marketplace_installs
- marketplace_categories
- process templates
- reusable blocks
- message templates
- funnel templates
- pipeline templates
- import/export
- install into account/project
- changelog
- compatibility rules

TELEGRAM:
Обязательно поддержать:
Methods:
- getMe
- setWebhook
- deleteWebhook
- getWebhookInfo
- sendMessage
- sendPhoto
- sendVideo
- sendAudio
- sendVoice
- sendDocument
- sendMediaGroup
- editMessageText
- deleteMessage
- answerCallbackQuery
- sendChatAction
- setMyCommands
- getFile

Updates:
- message
- edited_message
- callback_query
- my_chat_member
- chat_member

Architecturally prepare:
- inline_query
- pre_checkout_query
- successful_payment

SECURITY:
- password_hash/password_verify
- prepared statements only
- CSRF protection
- XSS-safe escaping
- rate limiting
- file upload validation
- token encryption
- secret masking in logs
- session rotation
- audit logs
- installer lock after installation

INSTALLER:
Создать /install/index.php:
- env checks
- DB setup
- config.local.php generation
- migrations
- seeds
- super admin creation
- account owner creation
- install lock

UPDATER:
Создать /update/index.php:
- current version check
- pending migrations
- run migrations
- clear caches
- recompile graphs
- update log

CRON:
Создать:
- /cron/queue_worker.php
- /cron/scheduler.php
- /cron/retry_failed_jobs.php
- /cron/cleanup.php
- /cron/stats_aggregate.php
- /cron/compile_graphs.php

DOCS:
Создать:
- docs/ARCHITECTURE.md
- docs/INSTALL.md
- docs/UPDATE.md
- docs/DATABASE_SCHEMA.md
- docs/GRAPH_SCHEMA.md
- docs/PROCESS_EDITOR.md
- docs/RUNTIME_ENGINE.md
- docs/TELEGRAM_INTEGRATION.md
- docs/CRON_AND_QUEUE.md
- docs/SECURITY.md
- docs/API.md
- docs/FUNNELS.md
- docs/DEALS_CRM.md
- docs/TEMPLATES_AND_MARKETPLACE.md

DEMO TEMPLATES:
Создать системные demo templates:
1. welcome funnel
2. lead capture with phone
3. 3-question quiz
4. reminder after 1 day
5. external webhook start
6. consultation booking flow
7. sales follow-up flow
8. lead qualification reusable block
9. create deal after qualification
10. onboarding sequence template

ACCEPTANCE CRITERIA:
Система считается выполненной, если:
- ставится из браузера
- работает из корня без public
- создается account/project/bot
- Telegram bot verifies and webhook sets
- updates deduplicate correctly
- graph editor saves valid process
- process version validates and publishes
- /start triggers flow
- send_text sends actual Telegram message
- wait_input resumes on user reply
- condition branches correctly
- delay schedules resume
- http_request works
- execution logs exist
- reusable blocks work
- message templates work
- funnel analytics works
- deal auto-creation works
- kanban works
- marketplace install works
- audit logs work
- lock mechanism prevents conflicting contact processing

Сначала создай:
1. полную файловую структуру
2. migration system
3. core framework layer
4. Telegram integration layer
5. graph schema + validator
6. working visual editor MVP
7. graph compiler
8. runtime engine MVP
9. templates foundation
10. funnels foundation
11. deals CRM foundation

Не пиши только план. Сразу создавай реальный код, файлы, структуру, миграции, документы и работающий foundation.
```

---

# 53. Последнее критичное указание для Codex

Отдельно добавьте в начало проекта внутренний документ:

## `docs/IMPLEMENTATION_ORDER.md`

Где жестко указать порядок разработки:

1. Core
2. DB + migrations
3. Auth/RBAC
4. Projects/Bots
5. Telegram webhook/client
6. Graph schema
7. Editor MVP
8. Runtime MVP
9. Contacts/Chats/Messages
10. Templates
11. Funnels
12. Deals
13. Marketplace
14. Installer/Updater
15. Debug/Logs/Audit
16. Polish & hardening

Это сильно снижает риск того, что Codex начнет распыляться по второстепенным направлениям.
