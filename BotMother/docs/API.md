# API

## Auth
- `POST /api/auth/login`
- `POST /api/auth/logout`

## Projects
- `GET /api/projects`
- `POST /api/projects`
- `GET /api/projects/{id}`
- `PUT /api/projects/{id}`

## Bots
- `POST /api/bots`
- `POST /api/bots/{id}/verify`
- `POST /api/bots/{id}/set-webhook`
- `POST /api/bots/{id}/delete-webhook`

## Processes / Versions
- `GET /api/processes`
- `POST /api/processes`
- `POST /api/processes/{id}/versions`
- `PUT /api/process-versions/{id}`
- `POST /api/process-versions/{id}/validate`
- `POST /api/process-versions/{id}/publish`

## Contacts
- `GET /api/contacts`
- `GET /api/contacts/{id}`
- `PUT /api/contacts/{id}`
- `POST /api/contacts/{id}/tags`
- `DELETE /api/contacts/{id}/tags/{tagId}`

## Inbox
- `GET /api/chats`
- `GET /api/chats/{id}/messages`
- `POST /api/chats/{id}/send-message`
- `POST /api/chats/{id}/mode`

## Funnels
- `GET /api/funnels`
- `POST /api/funnels`
- `GET /api/funnels/{id}/analytics`

## Deals / Pipelines
- `GET /api/pipelines`
- `POST /api/pipelines`
- `GET /api/deals`
- `POST /api/deals`
- `POST /api/deals/{id}/move-stage`
- `POST /api/deals/{id}/notes`
- `POST /api/deals/{id}/tasks`

## Templates
- `GET /api/templates/message`
- `POST /api/templates/message`
- `GET /api/templates/reusable`
- `POST /api/templates/reusable`
- `GET /api/templates/message/{id}/export`
- `POST /api/templates/message/import`

## Marketplace
- `GET /api/marketplace/items`
- `GET /api/marketplace/items/{id}`
- `POST /api/marketplace/items/{id}/install`
- `GET /api/marketplace/items/{id}/export`
- `POST /api/marketplace/import`


## Process Templates
- `GET /api/process-templates`
- `POST /api/process-templates`
