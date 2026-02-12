# BizSocials — Architecture Decisions (FINALIZED)

**Purpose:** Finalized architecture decisions for development
**Date:** February 2026
**Status:** ALL DECISIONS CONFIRMED

---

## Decision Summary

| # | Decision | Final Decision | Status |
|---|----------|----------------|:------:|
| 1 | Tenant → Workspace hierarchy | New hierarchy confirmed | ✅ |
| 2 | Social platforms for Phase-1 | LinkedIn, Facebook, Instagram, Twitter/X | ✅ |
| 3 | Payment gateway | Razorpay only | ✅ |
| 4 | Pricing tiers and limits | 5 tiers (Free → Enterprise) | ✅ |
| 5 | White-labeling scope | Business+ plans, Phase-1 | ✅ |
| 6 | Tenant API access | Professional+ plans | ✅ |
| 7 | Support model | L0/L1 automated, L2/L3 human | ✅ |
| 8 | Data localization | India primary, Singapore DR | ✅ |
| 9 | Onboarding verification | Email only, GST optional | ✅ |
| 10 | Free tier strategy | Limited free forever | ✅ |
| 11 | AI features scope | All basic AI features | ✅ |
| 12 | Mobile app | Flutter (iOS + Android) | ✅ |
| 13 | Compliance certifications | GDPR + DPDP at launch | ✅ |
| 14 | Analytics depth | Full platform + usage analytics | ✅ |

**Core Principle:** System completely automated with minimal manual intervention.

---

## 1. Platform Hierarchy ✅ CONFIRMED

```
FINAL HIERARCHY:
SuperAdmin (Bizinso) → Tenant → User → Workspace → Resources
```

- **Tenant** = Billing entity (company/individual who pays)
- **Workspace** = Organizational units within a tenant
- **User** = Individual team members within a tenant

---

## 2. Social Media Platforms ✅ CONFIRMED

### Phase-1 (Launch)
| Platform | Priority | Status |
|----------|:--------:|:------:|
| LinkedIn | Must-have | ✅ |
| Facebook | Must-have | ✅ |
| Instagram | Must-have | ✅ |
| Twitter/X | Must-have | ✅ |

### Phase-2 (3-6 months post-launch)
| Platform | Priority |
|----------|:--------:|
| YouTube | High |
| TikTok | High |
| Pinterest | Medium |
| Threads | Medium |
| Google Business Profile | Medium |

### Phase-3 (6-12 months post-launch)
| Platform | Priority |
|----------|:--------:|
| WhatsApp Business | Medium |
| Telegram | Low |
| Snapchat | Low |
| Reddit | Low |

---

## 3. Payment Gateway ✅ CONFIRMED

**Decision:** Razorpay Only

**Rationale:**
- Excellent India support (UPI, NetBanking, Wallets, Cards)
- International payments supported
- Native GST handling
- Single integration to maintain

**Configuration:**
- Razorpay Subscriptions for recurring billing
- Razorpay Checkout for one-time payments
- Webhook integration for real-time updates
- Auto-invoice generation with GST

---

## 4. Subscription Plans & Pricing ✅ CONFIRMED

### Plan Structure

| Plan | INR/month | USD/month | Annual Discount |
|------|:---------:|:---------:|:---------------:|
| Free | ₹0 | $0 | - |
| Starter | ₹999 | $15 | 20% |
| Professional | ₹2,499 | $35 | 20% |
| Business | ₹4,999 | $75 | 20% |
| Enterprise | Custom | Custom | Negotiable |

### Plan Limits

| Feature | Free | Starter | Professional | Business | Enterprise |
|---------|:----:|:-------:|:------------:|:--------:|:----------:|
| Users | 1 | 2 | 5 | 15 | Unlimited |
| Workspaces | 1 | 2 | 5 | 10 | Unlimited |
| Social Accounts | 2 | 5 | 15 | 50 | Unlimited |
| Posts/month | 30 | 150 | 500 | Unlimited | Unlimited |
| Scheduled Posts | 10 | 50 | 200 | Unlimited | Unlimited |
| Media Storage | 500MB | 2GB | 10GB | 50GB | Unlimited |
| AI Credits/month | 20 | 50 | 200 | 500 | Unlimited |
| Analytics History | 7 days | 30 days | 90 days | 1 year | Unlimited |
| Support | KB only | Email | Email | Priority | Dedicated |

### Trial Period
- **Duration:** 14 days
- **Features:** Full Professional plan access
- **No credit card required** for trial

---

## 5. White-Labeling ✅ CONFIRMED

**Decision:** Phase-1 feature for Business+ plans

| Feature | Business | Enterprise |
|---------|:--------:|:----------:|
| Custom logo on reports | ✅ | ✅ |
| Custom email branding | ✅ | ✅ |
| Remove "Powered by BizSocials" | ❌ | ✅ |
| Custom domain | ❌ | ✅ |
| Custom color theme | ❌ | ✅ |

---

## 6. Tenant API Access ✅ CONFIRMED

**Decision:** Available for Professional+ plans

| Plan | API Access | Rate Limit | Webhooks |
|------|:----------:|:----------:|:--------:|
| Free | ❌ | - | ❌ |
| Starter | ❌ | - | ❌ |
| Professional | Read-only | 100 req/hour | ✅ |
| Business | Full access | 1,000 req/hour | ✅ |
| Enterprise | Full access | Custom | ✅ |

**API Features:**
- RESTful API with OpenAPI documentation
- JWT authentication
- Rate limiting per plan
- Webhook subscriptions for events

---

## 7. Support Model ✅ CONFIRMED

**Decision:** L0/L1 Automated, L2/L3 Human

### Support Levels

| Level | Handling | Channel |
|-------|----------|---------|
| L0 | Self-service Knowledge Base | In-app, KB portal |
| L1 | Automated troubleshooting, AI chatbot | In-app |
| L2 | Bizinso technical support | Email, Ticket |
| L3 | Engineering team | Escalation only |

### Response Time SLAs

| Plan | Initial Response | Resolution Target |
|------|:----------------:|:-----------------:|
| Free | 72 hours | Best effort |
| Starter | 48 hours | 5 business days |
| Professional | 24 hours | 3 business days |
| Business | 8 hours | 1 business day |
| Enterprise | 4 hours | Same day |

### Support Channels

| Plan | Knowledge Base | Email | Priority Queue | Phone | Dedicated Manager |
|------|:--------------:|:-----:|:--------------:|:-----:|:-----------------:|
| Free | ✅ | ❌ | ❌ | ❌ | ❌ |
| Starter | ✅ | ✅ | ❌ | ❌ | ❌ |
| Professional | ✅ | ✅ | ❌ | ❌ | ❌ |
| Business | ✅ | ✅ | ✅ | ❌ | ❌ |
| Enterprise | ✅ | ✅ | ✅ | ✅ | ✅ |

---

## 8. Data Localization ✅ CONFIRMED

**Decision:** India primary, Singapore DR

### Infrastructure

| Component | Primary Region | DR Region |
|-----------|:-------------:|:---------:|
| Application Servers | India (Bangalore) | Singapore |
| Database | India (Bangalore) | Singapore (replica) |
| File Storage | India (Bangalore) | Singapore (replicated) |
| CDN | Global (Cloudflare) | - |
| Backups | India | Singapore |

### Compliance

- **India DPDP Act:** Compliant (data stored in India)
- **GDPR:** Compliant (data processing agreements in place)
- **Data Residency:** Indian tenant data never leaves India region

---

## 9. Onboarding Verification ✅ CONFIRMED

**Decision:** Email verification only, GST optional

### Verification Requirements

| Tenant Type | Email | Phone | GST | Company Docs |
|-------------|:-----:|:-----:|:---:|:------------:|
| Individual | Required | Optional | ❌ | ❌ |
| Influencer | Required | Optional | ❌ | ❌ |
| B2B SMB | Required | Optional | Optional | ❌ |
| B2B Enterprise | Required | Optional | Encouraged | Optional |
| Non-Profit | Required | Optional | Optional | Optional |
| International | Required | Optional | N/A | ❌ |

### GST Handling
- **Optional at signup:** Tenants can add GST later
- **Benefits of adding GST:**
  - GST-compliant invoices
  - Input tax credit eligibility
  - Business verification badge
- **No blocking:** Missing GST does not block any features

### Automated Verification
- Email: OTP verification (automated)
- GST: API verification via government portal (optional, automated)
- No manual verification required

---

## 10. Free Tier Strategy ✅ CONFIRMED

**Decision:** Limited free forever

### Free Tier Limits

| Feature | Limit |
|---------|:-----:|
| Users | 1 |
| Workspaces | 1 |
| Social Accounts | 2 |
| Posts/month | 30 |
| Scheduled Posts | 10 |
| Media Storage | 500MB |
| AI Credits | 20/month |
| Analytics History | 7 days |
| Support | Knowledge Base only |

### Upgrade Prompts
- **Frequency:** Non-intrusive
- **Triggers:**
  - Approaching limits (80% usage)
  - Attempting restricted features
  - Weekly summary email with usage stats
- **No forced upgrades:** Free tier works indefinitely within limits

### Free Tier Restrictions
- No API access
- No white-labeling
- No team collaboration
- No approval workflows
- No custom reports
- BizSocials branding on shared content

---

## 11. AI Features ✅ CONFIRMED

**Decision:** All basic AI features included

### Phase-1 AI Features

| Feature | Description | Free | Starter | Pro | Business | Enterprise |
|---------|-------------|:----:|:-------:|:---:|:--------:|:----------:|
| Caption Generation | Generate post captions | 10/mo | 50/mo | 200/mo | 500/mo | Unlimited |
| Hashtag Suggestions | Suggest relevant hashtags | 10/mo | 50/mo | 200/mo | 500/mo | Unlimited |
| Best Time to Post | Optimal posting time analysis | ❌ | ❌ | ✅ | ✅ | ✅ |
| Content Repurposing | Adapt content for platforms | ❌ | ❌ | 50/mo | 200/mo | Unlimited |
| Alt Text Generation | Generate image alt text | 10/mo | 50/mo | 200/mo | 500/mo | Unlimited |
| Tone Adjustment | Rephrase content tone | ❌ | ❌ | 50/mo | 200/mo | Unlimited |
| Reply Suggestions | Suggest inbox replies | ❌ | ❌ | 100/mo | 300/mo | Unlimited |
| Sentiment Analysis | Analyze comment sentiment | ❌ | ❌ | ❌ | ✅ | ✅ |
| Performance Prediction | Predict post performance | ❌ | ❌ | ❌ | ✅ | ✅ |
| Content Moderation | Auto-moderate content | ❌ | ❌ | ❌ | ✅ | ✅ |

### AI Provider
- **Primary:** OpenAI (GPT-4)
- **Fallback:** Anthropic Claude
- **Cost management:** Token-based usage tracking per tenant

---

## 12. Mobile App ✅ CONFIRMED

**Decision:** Flutter for iOS & Android (Phase-1)

### Specifications

| Aspect | Decision |
|--------|----------|
| Framework | Flutter 3.x |
| iOS Minimum | iOS 14.0 |
| Android Minimum | Android 7.0 (SDK 24) |
| Architecture | BLoC pattern |
| State Management | flutter_bloc |
| Local Storage | Drift (SQLite) |
| Push Notifications | Firebase Cloud Messaging |

### Phase-1 Mobile Features
- ✅ Authentication (email + biometric)
- ✅ Dashboard with quick stats
- ✅ Create & schedule posts
- ✅ Content calendar view
- ✅ Social inbox (messages, comments)
- ✅ Push notifications
- ✅ Basic analytics
- ✅ Offline draft support

### Phase-2 Mobile Features
- AI caption generation
- Media library
- Team collaboration
- Approval workflows
- Detailed analytics
- Widget support

---

## 13. Compliance Certifications ✅ CONFIRMED

**Decision:** GDPR + India DPDP at launch

### Compliance Roadmap

| Certification | Priority | Timeline | Status |
|---------------|:--------:|:--------:|:------:|
| GDPR Compliance | Must-have | Launch | Phase-1 |
| India DPDP Act | Must-have | Launch | Phase-1 |
| SOC 2 Type I | Should-have | Year 1 | Planned |
| SOC 2 Type II | Should-have | Year 2 | Planned |
| ISO 27001 | Nice-to-have | Year 2-3 | Future |

### Launch Compliance Features
- Privacy Policy & Terms of Service
- Data Processing Agreement (DPA)
- Cookie consent management
- Data export (right to portability)
- Data deletion (right to erasure)
- Consent tracking
- Audit logging
- Encryption at rest and in transit

---

## 14. Analytics Depth ✅ CONFIRMED

**Decision:** Full platform analytics + usage analytics

### Social Media Analytics (from platforms)

| Metric Category | Included |
|-----------------|:--------:|
| Impressions & Reach | ✅ |
| Engagement (likes, comments, shares, saves) | ✅ |
| Click metrics (link, profile, media) | ✅ |
| Video metrics (views, watch time, completion) | ✅ |
| Follower growth & demographics | ✅ |
| Audience insights (location, age, gender) | ✅ |
| Best posting times | ✅ |
| Content type performance | ✅ |

### Usage Analytics (platform activity)

| Metric | Description |
|--------|-------------|
| User activity | Login frequency, feature usage |
| Content metrics | Posts created, scheduled, published |
| Team productivity | Response times, approval turnaround |
| Feature adoption | Which features are used most |
| Error tracking | Failed posts, connection issues |

### Reporting Features

| Feature | Free | Starter | Pro | Business | Enterprise |
|---------|:----:|:-------:|:---:|:--------:|:----------:|
| Dashboard analytics | ✅ | ✅ | ✅ | ✅ | ✅ |
| Post analytics | 7 days | 30 days | 90 days | 1 year | Unlimited |
| Custom date ranges | ❌ | ✅ | ✅ | ✅ | ✅ |
| Export (CSV) | ❌ | ✅ | ✅ | ✅ | ✅ |
| Export (PDF) | ❌ | ❌ | ✅ | ✅ | ✅ |
| Scheduled reports | ❌ | ❌ | ✅ | ✅ | ✅ |
| Custom reports | ❌ | ❌ | ❌ | ✅ | ✅ |
| White-label reports | ❌ | ❌ | ❌ | ✅ | ✅ |
| API access to analytics | ❌ | ❌ | ✅ | ✅ | ✅ |

---

## Automation Principle ✅ CONFIRMED

**Decision:** System completely automated with minimal manual intervention

### Automation Coverage

| Process | Automation Level | Manual Intervention |
|---------|:----------------:|:-------------------:|
| Tenant Registration | 100% | None |
| Tenant Onboarding | 100% | None |
| Payment Processing | 100% | None (Razorpay) |
| Invoice Generation | 100% | None |
| Social Account OAuth | 100% | None |
| Token Refresh | 100% | None |
| Post Publishing | 100% | None |
| Post Retry (failures) | 100% | None |
| Analytics Collection | 100% | None |
| Email Notifications | 100% | None |
| Push Notifications | 100% | None |
| L1 Support | 95% | 5% (KB gaps) |
| Dunning/Payment Recovery | 100% | None |
| Data Retention | 100% | None |
| Security Monitoring | 90% | 10% (investigation) |

### Manual Intervention Required Only For
- L2/L3 support issues (complex technical problems)
- Security incident investigation
- Compliance audits
- Product development and improvements
- Enterprise custom implementations

---

## Documentation Index

All specifications are complete and ready for development:

| # | Document | Description |
|---|----------|-------------|
| 1 | `11_platform_architecture_expansion.md` | Architecture overview |
| 2 | `12_architecture_decisions_required.md` | This document (all decisions finalized) |
| 3 | `13_expanded_data_model.md` | Complete data model (40+ entities) |
| 4 | `14_super_admin_module.md` | Bizinso super admin features |
| 5 | `15_tenant_onboarding.md` | Self-service onboarding flows |
| 6 | `16_razorpay_billing.md` | Complete Razorpay integration |
| 7 | `17_social_platforms.md` | All social platform specifications |
| 8 | `18_knowledge_base_module.md` | Self-service knowledge base |
| 9 | `19_feedback_roadmap_module.md` | Feedback & roadmap management |
| 10 | `20_data_security_trust.md` | Security & compliance framework |
| 11 | `21_ai_features_module.md` | All AI features specification |
| 12 | `22_flutter_mobile_app.md` | Flutter mobile app specification |
| 13 | `23_automation_self_service.md` | Complete automation specification |
| 14 | `24_detailed_analytics.md` | Analytics & reporting specification |

---

## Ready for Development

All architecture decisions are now finalized. The documentation is complete and ready for Codex to begin development.

**Next Steps:**
1. Review this finalized document
2. Confirm no changes needed
3. Begin Phase-1 development with Codex
