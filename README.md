# Laravel Template

A documentation-driven scaffolding template for AI agents and developers creating full-stack applications
with **Laravel 12 + Angular 20**.

> **This repository contains no runnable application.** It is a reference template: copy what you need,
> adapt it to your project, and delete the rest.

---

## What's Inside

| Folder | Purpose |
|--------|---------|
| `documentation/` | 20+ pattern guides covering every architectural decision |
| `templates/` | Copy-paste starter files: PHP classes, configs, CI, Docker |
| `docs/` | Internal planning documents |

---

## Stack

**Backend**
- PHP 8.3+ / Laravel 12.x
- PostgreSQL 17
- Eloquent ORM (with custom repositories)
- Laravel Horizon (queue management)
- Laravel Sanctum (authentication)
- Pest PHP 3.x (testing)
- PHPStan Level 8 + Laravel Pint (code quality)

**Frontend**
- Angular 20 + PrimeNG 18
- TypeScript (strict mode)
- pnpm 10.x
- Vitest (unit) + Cypress (integration) + Playwright (E2E)
- openapi-typescript (codegen from Laravel API)

**Infrastructure**
- Docker / Docker Compose
- GitLab CI/CD
- Kubernetes (reference deployment)

---

## Architecture

```
app/
├── Domain/          Pure PHP — value objects, aggregates, domain events
├── Application/     Actions (write) + Queries (read) = CQRS equivalent
├── Infrastructure/  Eloquent repositories, external services
└── Http/            Controllers, Form Requests, API Resources
```

The dependency direction is strictly enforced:
`Http → Application → Domain` and `Infrastructure → Application → Domain`

---

## First Steps

1. Read [`CLAUDE.md`](CLAUDE.md) end-to-end (10 min) — it is the AI agent entry point
2. Read [`documentation/WorkedExample.md`](documentation/WorkedExample.md) — keep it open while building
3. Start with [`templates/README.md`](templates/README.md) for the list of copy-paste starter files

---

## Documentation Index

| File | What It Covers |
|------|---------------|
| [GettingStarted.md](documentation/GettingStarted.md) | Setup commands, first run |
| [ArchitectureDecisions.md](documentation/ArchitectureDecisions.md) | 13 ADRs with alternatives considered |
| [WorkedExample.md](documentation/WorkedExample.md) | Product vertical slice (every layer) |
| [Glossary.md](documentation/Glossary.md) | Precise term definitions |
| [DatabaseAndPersistence.md](documentation/DatabaseAndPersistence.md) | Eloquent, repositories, Unit of Work |
| [ActionHandlers.md](documentation/ActionHandlers.md) | CQRS write path via Action classes |
| [QueryHandlers.md](documentation/QueryHandlers.md) | CQRS read path via Query classes |
| [Permissions.md](documentation/Permissions.md) | Ownership-based access via Laravel Policies |
| [QueuePattern.md](documentation/QueuePattern.md) | Reliable event delivery via Laravel Queues |
| [DomainEvents.md](documentation/DomainEvents.md) | Domain events + Laravel event system |
| [SpecificationPattern.md](documentation/SpecificationPattern.md) | Composable query filters |
| [Testing.md](documentation/Testing.md) | Pest PHP, factories, test strategy |
| [Frontend.md](documentation/Frontend.md) | Angular 20 patterns |
| [API.md](documentation/API.md) | REST endpoints, API Resources |
| [Configuration.md](documentation/Configuration.md) | .env, config files, options |
| [Observability.md](documentation/Observability.md) | Logging, Telescope, metrics |
| [SeedData.md](documentation/SeedData.md) | Factories, seeders, reference data |
| [DevelopmentWorkflows.md](documentation/DevelopmentWorkflows.md) | Artisan, migrations, git hooks, CI |
| [architectureDiagram.md](documentation/architectureDiagram.md) | Layer boundary diagrams |
