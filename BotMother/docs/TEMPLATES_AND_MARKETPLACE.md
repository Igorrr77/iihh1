# TEMPLATES_AND_MARKETPLACE

Implemented foundation:

## Message templates
- create/list message templates
- each template creates initial version record
- template export package endpoint (`/api/templates/message/{id}/export`)
- template import endpoint (`/api/templates/message/import`)

## Reusable blocks
- create/list reusable blocks
- each block stores initial graph + input/output contracts

## Marketplace
- list published items
- get item details
- install latest published item version into account/project
- install counters tracked via `marketplace_items.install_count`
