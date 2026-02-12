# BizSocials Architecture Rules

## Core Principles

- Service-layer driven architecture
- No business logic in controllers
- No direct adapter calls outside PublishingService
- No direct config() usage inside adapters
- All credentials resolved via PlatformCredentialResolver (DB → env fallback)
- Scheduling flows must go through PostService
- Publishing flows must go through PublishingService

## Multi-Tenancy

- All queries must be tenant-scoped
- Never bypass workspace/tenant isolation
- Admin APIs are strictly separate from tenant APIs

## Background Jobs

- Jobs must not contain business logic
- Jobs delegate to services only

## Security

- No secrets in logs
- No plaintext credentials
- All OAuth flows state-protected
