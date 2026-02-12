# BizSocials — Phase-1 Architecture Documents

**Product:** BizSocials (B2B Social Media Management Platform)
**Phase:** 1 (MVP)
**Status:** Ready for Development
**Last Updated:** February 2026

---

## Document Index

| # | Document | Version | Purpose |
|:-:|----------|:-------:|---------|
| 01 | [Product Constitution](./PHASE-1-PRODUCT-CONSTITUTION.md) | 1.0 | Defines what Phase-1 is and is NOT. Single source of truth for scope. |
| 03 | [Data Model](./03_phase1_data_model.md) | 1.1 | All entities, attributes, relationships, and lifecycle states. |
| 04 | [API Contract](./04_phase1_api_contract.md) | 1.1 | Complete REST API specification (75 endpoints). |
| 05 | [Implementation Plan](./05_phase1_implementation_plan.md) | 1.1 | Build order, milestones, and execution sequence. |
| 06 | [Requirements Gap Analysis](./06_requirements_gap_analysis.md) | 1.0 | Maps BRD against Phase-1 scope. |
| 07 | [Tenancy Enforcement](./07_saas_tenancy_enforcement.md) | 1.1 | **MANDATORY READ** — SaaS multi-tenant isolation rules. |
| 08 | [SDLC Comprehensive Plan](./08_sdlc_comprehensive_plan.md) | 1.0 | Complete SDLC: CI/CD, Docker, Testing, APM, Security. |
| 09 | [Digital Ocean Infrastructure](./09_digital_ocean_infrastructure.md) | 1.0 | Production infrastructure on Digital Ocean. |
| 10 | [Development Workflow](./10_development_workflow.md) | 1.0 | Claude + Codex collaboration workflow. |
| 11 | [Test Cases](./test-cases/) | 1.0 | Comprehensive test case documentation. |

---

## Reading Order

### For Founders / Product Owners
1. **Product Constitution** — Understand scope boundaries
2. **Implementation Plan** — Review milestones and timeline

### For Backend Developers
1. **Tenancy Enforcement** — Read FIRST before any code
2. **Data Model** — Understand entities and relationships
3. **API Contract** — Implement endpoints per spec
4. **Implementation Plan** — Follow build sequence

### For Frontend Developers
1. **API Contract** — Understand all available endpoints
2. **Data Model** — Understand response structures
3. **Product Constitution** — Know what features exist

### For DevOps / Platform Engineers
1. **SDLC Comprehensive Plan** — CI/CD, Docker, Kubernetes, APM setup
2. **Tenancy Enforcement** — Understand security requirements
3. **Implementation Plan** — Understand deployment milestones

### For QA Engineers
1. **SDLC Comprehensive Plan** — Testing strategy, test patterns
2. **API Contract** — Understand API test requirements
3. **Tenancy Enforcement** — Multi-tenant isolation test scenarios

---

## Quick Reference

### Phase-1 Scope Summary

| In Scope | Out of Scope |
|----------|--------------|
| LinkedIn, Facebook, Instagram | Twitter/X, TikTok, YouTube |
| Single-level approvals | Multi-level approvals |
| Comments & mentions inbox | DMs, story replies |
| Basic analytics | Advanced attribution |
| AI caption/hashtag suggestions | AI auto-scheduling |
| Per-workspace billing | Usage-based billing |
| Email/password auth | SSO, 2FA |

### Core Domains (8)

```
┌─────────────────┐  ┌─────────────────┐  ┌─────────────────┐  ┌─────────────────┐
│   Identity &    │  │   Workspace     │  │    Social       │  │    Content      │
│     Access      │  │   Management    │  │   Accounts      │  │    Engine       │
└─────────────────┘  └─────────────────┘  └─────────────────┘  └─────────────────┘
┌─────────────────┐  ┌─────────────────┐  ┌─────────────────┐  ┌─────────────────┐
│   Engagement    │  │   Analytics &   │  │   Billing &     │  │   AI Assist     │
│     Inbox       │  │    Reports      │  │    Plans        │  │                 │
└─────────────────┘  └─────────────────┘  └─────────────────┘  └─────────────────┘
```

### Key Numbers

| Metric | Count |
|--------|:-----:|
| Data Entities | 21 |
| API Endpoints | 75 |
| User Roles | 4 |
| Supported Platforms | 3 |
| Development Milestones | 10 |
| Target Timeline | 14 weeks |

---

## The Three Laws of BizSocials Tenancy

```
1. A workspace's data SHALL NOT be accessible to other workspaces

2. Every query SHALL include workspace scope, unless explicitly
   user-scoped or system-scoped

3. Every background job SHALL carry and validate workspace_id
```

---

## Tech Stack (Phase-1)

| Layer | Technology |
|-------|------------|
| Backend | Laravel 11.x (PHP 8.3) |
| Frontend | Vue 3 + TypeScript |
| Database | MySQL 8.0 |
| Cache/Queue | Redis 7.x |
| Storage | S3-compatible |
| Payments | Stripe |
| AI | OpenAI API |
| Containerization | Docker + Kubernetes |
| CI/CD | GitHub Actions |
| APM | Datadog / New Relic |
| Error Tracking | Sentry |
| Logging | ELK Stack / CloudWatch |

---

## Document Versioning

All documents follow semantic versioning:
- **Major** (1.0 → 2.0): Breaking changes to scope or structure
- **Minor** (1.0 → 1.1): Additions or clarifications

Current versions are locked for Phase-1 development. Changes require review.

---

## Questions?

If something is unclear or seems contradictory:
1. Check the **Product Constitution** first — it's the source of truth
2. Check the **Tenancy Enforcement** doc for security questions
3. If still unclear, document the question and escalate

---

*This index was generated as part of Phase-1 architecture completion.*
