# BizSocials — Platform Architecture Expansion

**Version:** 1.0
**Date:** February 2026
**Purpose:** Expanded architecture for enterprise-grade multi-tenant SaaS platform
**Status:** Draft - Pending Review

---

## 1. Platform Hierarchy

```
┌─────────────────────────────────────────────────────────────────────────────────┐
│                           BIZSOCIALS PLATFORM HIERARCHY                          │
├─────────────────────────────────────────────────────────────────────────────────┤
│                                                                                 │
│  ┌─────────────────────────────────────────────────────────────────────────┐   │
│  │                        SUPER ADMIN (BIZINSO)                             │   │
│  │  ├── Platform Configuration                                              │   │
│  │  ├── Tenant Management (Create/Suspend/Terminate)                        │   │
│  │  ├── Subscription Plans Management                                       │   │
│  │  ├── Global Feature Flags                                                │   │
│  │  ├── Platform Analytics & Revenue Dashboard                              │   │
│  │  ├── Feedback & Roadmap Management                                       │   │
│  │  ├── Knowledge Base Administration                                       │   │
│  │  ├── Support Ticket Escalation (L2/L3)                                   │   │
│  │  └── Compliance & Audit Management                                       │   │
│  └─────────────────────────────────────────────────────────────────────────┘   │
│                                      │                                          │
│                    ┌─────────────────┼─────────────────┐                       │
│                    ▼                 ▼                 ▼                       │
│  ┌──────────────────────┐ ┌──────────────────────┐ ┌──────────────────────┐   │
│  │     TENANT A         │ │     TENANT B         │ │     TENANT C         │   │
│  │  (Marketing Agency)  │ │  (E-commerce Brand)  │ │   (Influencer)       │   │
│  │                      │ │                      │ │                      │   │
│  │  ├── Tenant Admin    │ │  ├── Tenant Admin    │ │  ├── Tenant Admin    │   │
│  │  ├── Workspaces      │ │  ├── Workspaces      │ │  ├── Workspaces      │   │
│  │  ├── Team Members    │ │  ├── Team Members    │ │  ├── Team Members    │   │
│  │  ├── Social Accounts │ │  ├── Social Accounts │ │  ├── Social Accounts │   │
│  │  ├── Content         │ │  ├── Content         │ │  ├── Content         │   │
│  │  ├── Analytics       │ │  ├── Analytics       │ │  ├── Analytics       │   │
│  │  └── Billing         │ │  └── Billing         │ │  └── Billing         │   │
│  └──────────────────────┘ └──────────────────────┘ └──────────────────────┘   │
│                                                                                 │
│  DATA ISOLATION: Complete separation - Tenants CANNOT access each other's data │
│                                                                                 │
└─────────────────────────────────────────────────────────────────────────────────┘
```

---

## 2. Tenant Types & Onboarding Flows

### 2.1 Tenant Categories

| Category | Sub-Types | Characteristics |
|----------|-----------|-----------------|
| **B2B Enterprise** | Agencies, Consultancies, SaaS Companies | Multiple workspaces, large teams, API access |
| **B2B SMB** | Small Businesses, Startups | 1-3 workspaces, small teams |
| **B2C Brands** | E-commerce, Retail, FMCG, D2C | Focus on Instagram, Facebook, high volume |
| **Individuals** | Freelancers, Solopreneurs | Single user, limited features |
| **Influencers** | Content Creators, KOLs | Personal branding, analytics focus |
| **Non-Profit** | NGOs, Educational Institutions | Special pricing, specific needs |

### 2.2 Onboarding Flow Matrix

```
┌─────────────────────────────────────────────────────────────────────────────────┐
│                         TENANT ONBOARDING FLOWS                                  │
├─────────────────────────────────────────────────────────────────────────────────┤
│                                                                                 │
│  STEP 1: REGISTRATION                                                           │
│  ├── Email verification                                                         │
│  ├── Business type selection (B2B/B2C/Individual/Influencer)                   │
│  └── Basic profile (Name, Company, Country)                                     │
│                                                                                 │
│  STEP 2: BUSINESS PROFILING (varies by type)                                    │
│  ├── B2B Enterprise:                                                            │
│  │   ├── Company size                                                           │
│  │   ├── Industry vertical                                                      │
│  │   ├── Number of clients/brands managed                                       │
│  │   ├── Team size                                                              │
│  │   ├── GST/Tax ID (for India) or VAT/Tax ID (International)                  │
│  │   └── Billing address                                                        │
│  │                                                                              │
│  ├── B2C Brand:                                                                 │
│  │   ├── Business type (E-commerce, Retail, D2C, etc.)                         │
│  │   ├── Product category                                                       │
│  │   ├── Target audience                                                        │
│  │   ├── GST/Tax ID                                                             │
│  │   └── Billing address                                                        │
│  │                                                                              │
│  ├── Individual/Freelancer:                                                     │
│  │   ├── Profession                                                             │
│  │   ├── Services offered                                                       │
│  │   └── PAN (India) or Tax ID (International)                                 │
│  │                                                                              │
│  └── Influencer:                                                                │
│      ├── Niche/Category                                                         │
│      ├── Primary platforms                                                      │
│      ├── Follower range                                                         │
│      └── Monetization goals                                                     │
│                                                                                 │
│  STEP 3: PLAN SELECTION                                                         │
│  ├── View available plans for their category                                    │
│  ├── Feature comparison                                                         │
│  ├── Trial option (if applicable)                                               │
│  └── Payment via Razorpay                                                       │
│                                                                                 │
│  STEP 4: INITIAL CONFIGURATION WIZARD                                           │
│  ├── Create first workspace                                                     │
│  ├── Connect first social account                                               │
│  ├── Invite team members (optional)                                             │
│  └── Complete guided tour                                                       │
│                                                                                 │
│  STEP 5: KNOWLEDGE BASE PROMPT                                                  │
│  ├── Show "Getting Started" guide                                               │
│  ├── Platform tour video                                                        │
│  └── Link to full documentation                                                 │
│                                                                                 │
└─────────────────────────────────────────────────────────────────────────────────┘
```

---

## 3. Comprehensive Social Media Platform Support

### 3.1 Platform Matrix

| Platform | Type | OAuth Version | API Tier | B2B | B2C | Influencer | Key Features |
|----------|------|---------------|----------|:---:|:---:|:----------:|--------------|
| **LinkedIn** | Professional | OAuth 2.0 | Marketing API | ✓ | - | ✓ | Company pages, Personal profiles, Articles |
| **Facebook** | General | OAuth 2.0 | Graph API | ✓ | ✓ | ✓ | Pages, Groups, Messenger |
| **Instagram** | Visual | OAuth 2.0 | Graph API | ✓ | ✓ | ✓ | Business, Creator, Feed, Stories, Reels |
| **Twitter/X** | Microblog | OAuth 2.0 | API v2 | ✓ | ✓ | ✓ | Tweets, Threads, Spaces |
| **YouTube** | Video | OAuth 2.0 | Data API v3 | ✓ | ✓ | ✓ | Channels, Videos, Shorts, Community |
| **TikTok** | Short Video | OAuth 2.0 | Marketing API | - | ✓ | ✓ | Videos, Business accounts |
| **Pinterest** | Visual Discovery | OAuth 2.0 | API v5 | - | ✓ | ✓ | Pins, Boards, Business accounts |
| **Threads** | Microblog | OAuth 2.0 | Threads API | ✓ | ✓ | ✓ | Posts, Replies |
| **Google Business** | Local | OAuth 2.0 | Business Profile | ✓ | ✓ | - | Posts, Reviews, Q&A |
| **WhatsApp Business** | Messaging | Cloud API | Business API | ✓ | ✓ | - | Broadcasts, Catalogs |
| **Telegram** | Messaging | Bot API | Bot API | ✓ | ✓ | ✓ | Channels, Groups |
| **Snapchat** | Visual | OAuth 2.0 | Marketing API | - | ✓ | ✓ | Stories, Ads |
| **Reddit** | Community | OAuth 2.0 | API | ✓ | ✓ | - | Posts, Comments |
| **Mastodon** | Decentralized | OAuth 2.0 | REST API | ✓ | - | ✓ | Toots, Boosts |
| **Bluesky** | Decentralized | AT Protocol | AT Protocol | ✓ | - | ✓ | Posts |

### 3.2 Platform Connection Requirements

```
┌─────────────────────────────────────────────────────────────────────────────────┐
│                     SOCIAL PLATFORM CONNECTION DETAILS                           │
├─────────────────────────────────────────────────────────────────────────────────┤
│                                                                                 │
│  LINKEDIN                                                                        │
│  ├── Requirements:                                                              │
│  │   ├── LinkedIn Developer App                                                 │
│  │   ├── Company Page Admin access                                              │
│  │   └── Marketing Developer Platform access (for ads)                          │
│  ├── Scopes: r_organization_social, w_organization_social, r_ads               │
│  ├── Token Expiry: 60 days (refresh available)                                  │
│  └── Rate Limits: 100 calls/day for most endpoints                              │
│                                                                                 │
│  FACEBOOK / INSTAGRAM                                                            │
│  ├── Requirements:                                                              │
│  │   ├── Facebook Developer App                                                 │
│  │   ├── Business Manager account                                               │
│  │   ├── Page Admin access                                                      │
│  │   └── Instagram Business/Creator account linked                              │
│  ├── Scopes: pages_manage_posts, pages_read_engagement, instagram_basic,        │
│  │           instagram_content_publish, business_management                      │
│  ├── Token Expiry: 60 days (long-lived tokens)                                  │
│  └── Rate Limits: 200 calls/hour per user                                       │
│                                                                                 │
│  TWITTER/X                                                                       │
│  ├── Requirements:                                                              │
│  │   ├── Twitter Developer Account (Basic/Pro/Enterprise)                       │
│  │   └── OAuth 2.0 with PKCE                                                    │
│  ├── Scopes: tweet.read, tweet.write, users.read, offline.access               │
│  ├── Token Expiry: 2 hours (refresh available)                                  │
│  └── Rate Limits: Varies by tier (15-300 requests/15 min)                       │
│                                                                                 │
│  YOUTUBE                                                                         │
│  ├── Requirements:                                                              │
│  │   ├── Google Cloud Project                                                   │
│  │   ├── YouTube Data API v3 enabled                                            │
│  │   └── Channel ownership verification                                         │
│  ├── Scopes: youtube.upload, youtube.readonly, youtube.force-ssl               │
│  ├── Token Expiry: 1 hour (refresh available)                                   │
│  └── Rate Limits: 10,000 units/day                                              │
│                                                                                 │
│  TIKTOK                                                                          │
│  ├── Requirements:                                                              │
│  │   ├── TikTok Developer Account                                               │
│  │   ├── TikTok Business Account                                                │
│  │   └── Content Posting API access (approval required)                         │
│  ├── Scopes: user.info.basic, video.publish                                     │
│  ├── Token Expiry: 24 hours (refresh available)                                 │
│  └── Rate Limits: 1000 requests/day                                             │
│                                                                                 │
│  PINTEREST                                                                       │
│  ├── Requirements:                                                              │
│  │   ├── Pinterest Developer Account                                            │
│  │   └── Business Account                                                       │
│  ├── Scopes: boards:read, pins:read, pins:write                                │
│  ├── Token Expiry: 30 days (refresh available)                                  │
│  └── Rate Limits: 1000 requests/min                                             │
│                                                                                 │
│  GOOGLE BUSINESS PROFILE                                                         │
│  ├── Requirements:                                                              │
│  │   ├── Google Cloud Project                                                   │
│  │   ├── Business Profile API enabled                                           │
│  │   └── Verified business location                                             │
│  ├── Scopes: business.manage                                                    │
│  ├── Token Expiry: 1 hour (refresh available)                                   │
│  └── Rate Limits: Based on quota                                                │
│                                                                                 │
│  WHATSAPP BUSINESS                                                               │
│  ├── Requirements:                                                              │
│  │   ├── Meta Business Account                                                  │
│  │   ├── WhatsApp Business Platform access                                      │
│  │   ├── Verified business                                                      │
│  │   └── Phone number verification                                              │
│  ├── Authentication: System User Token or OAuth                                 │
│  ├── Token Expiry: 60 days                                                      │
│  └── Rate Limits: Messaging limits based on tier                                │
│                                                                                 │
└─────────────────────────────────────────────────────────────────────────────────┘
```

### 3.3 Platform-Specific Content Requirements

| Platform | Max Text | Image Specs | Video Specs | Special Formats |
|----------|----------|-------------|-------------|-----------------|
| LinkedIn | 3,000 chars | 1200x627px, <5MB | 10min, <200MB | Articles, Documents, Polls |
| Facebook | 63,206 chars | 1200x630px, <4MB | 240min, <4GB | Stories, Reels, Live |
| Instagram | 2,200 chars | 1080x1080px, <8MB | 60sec feed, 90sec reels | Stories, Reels, Carousels |
| Twitter/X | 280 chars (4,000 Blue) | 1600x900px, <5MB | 140sec, <512MB | Threads, Polls, Spaces |
| YouTube | 5,000 chars desc | Thumbnail 1280x720px | 12 hours, <256GB | Shorts (<60sec), Community |
| TikTok | 2,200 chars | - | 60sec-10min, <287MB | Duets, Stitches |
| Pinterest | 500 chars | 1000x1500px, <20MB | 60sec, <2GB | Idea Pins, Story Pins |
| Threads | 500 chars | 1440x1920px | 5min | - |

---

## 4. Knowledge Base & Help Center Module

### 4.1 Knowledge Base Structure

```
┌─────────────────────────────────────────────────────────────────────────────────┐
│                           KNOWLEDGE BASE STRUCTURE                               │
├─────────────────────────────────────────────────────────────────────────────────┤
│                                                                                 │
│  1. GETTING STARTED                                                             │
│     ├── 1.1 Platform Overview                                                   │
│     ├── 1.2 Account Setup                                                       │
│     ├── 1.3 Quick Start Guide (by tenant type)                                 │
│     │   ├── For Agencies                                                        │
│     │   ├── For Brands                                                          │
│     │   ├── For Individuals                                                     │
│     │   └── For Influencers                                                     │
│     ├── 1.4 Platform Navigation                                                 │
│     └── 1.5 Video Tutorials                                                     │
│                                                                                 │
│  2. WORKSPACE CONFIGURATION                                                      │
│     ├── 2.1 Creating Workspaces                                                 │
│     ├── 2.2 Workspace Settings                                                  │
│     │   ├── General Settings                                                    │
│     │   ├── Timezone Configuration                                              │
│     │   ├── Branding & Appearance                                               │
│     │   └── Approval Workflows                                                  │
│     ├── 2.3 Team Management                                                     │
│     │   ├── Inviting Team Members                                               │
│     │   ├── Role Permissions Explained                                          │
│     │   └── Managing Access                                                     │
│     └── 2.4 Best Practices                                                      │
│                                                                                 │
│  3. SOCIAL ACCOUNT CONNECTIONS                                                   │
│     ├── 3.1 Supported Platforms Overview                                        │
│     ├── 3.2 Connection Guides (per platform)                                    │
│     │   ├── How to Connect LinkedIn                                             │
│     │   ├── How to Connect Facebook/Instagram                                   │
│     │   ├── How to Connect Twitter/X                                            │
│     │   ├── How to Connect YouTube                                              │
│     │   ├── How to Connect TikTok                                               │
│     │   ├── How to Connect Pinterest                                            │
│     │   ├── How to Connect Google Business                                      │
│     │   └── How to Connect WhatsApp Business                                    │
│     ├── 3.3 Managing Connected Accounts                                         │
│     ├── 3.4 Token Refresh & Reconnection                                        │
│     └── 3.5 Common Connection Issues                                            │
│                                                                                 │
│  4. CONTENT CREATION & SCHEDULING                                                │
│     ├── 4.1 Creating Posts                                                      │
│     ├── 4.2 Media Upload Guidelines                                             │
│     ├── 4.3 Scheduling Posts                                                    │
│     ├── 4.4 Calendar View                                                       │
│     ├── 4.5 Bulk Scheduling                                                     │
│     ├── 4.6 Platform-Specific Tips                                              │
│     └── 4.7 AI Content Assistance                                               │
│                                                                                 │
│  5. APPROVAL WORKFLOWS                                                           │
│     ├── 5.1 Setting Up Approvals                                                │
│     ├── 5.2 Submitting for Approval                                             │
│     ├── 5.3 Reviewing & Approving Content                                       │
│     └── 5.4 Approval Best Practices                                             │
│                                                                                 │
│  6. ENGAGEMENT & INBOX                                                           │
│     ├── 6.1 Understanding the Inbox                                             │
│     ├── 6.2 Responding to Comments                                              │
│     ├── 6.3 Managing Mentions                                                   │
│     ├── 6.4 Inbox Filters & Organization                                        │
│     └── 6.5 Response Templates                                                  │
│                                                                                 │
│  7. ANALYTICS & REPORTING                                                        │
│     ├── 7.1 Dashboard Overview                                                  │
│     ├── 7.2 Understanding Metrics                                               │
│     ├── 7.3 Platform-Specific Analytics                                         │
│     ├── 7.4 Generating Reports                                                  │
│     └── 7.5 Exporting Data                                                      │
│                                                                                 │
│  8. BILLING & SUBSCRIPTION                                                       │
│     ├── 8.1 Understanding Plans                                                 │
│     ├── 8.2 Upgrading/Downgrading                                               │
│     ├── 8.3 Payment Methods                                                     │
│     ├── 8.4 Invoices & Receipts                                                 │
│     ├── 8.5 GST/Tax Information                                                 │
│     └── 8.6 Cancellation Policy                                                 │
│                                                                                 │
│  9. TROUBLESHOOTING                                                              │
│     ├── 9.1 Connection Issues                                                   │
│     │   ├── "Token Expired" - What to do                                        │
│     │   ├── "Permission Denied" - How to fix                                    │
│     │   ├── Account disconnected automatically                                  │
│     │   └── Platform-specific troubleshooting                                   │
│     ├── 9.2 Publishing Issues                                                   │
│     │   ├── Post failed to publish                                              │
│     │   ├── Scheduled post not posting                                          │
│     │   ├── Media upload failures                                               │
│     │   └── Rate limit errors                                                   │
│     ├── 9.3 Account Issues                                                      │
│     │   ├── Cannot login                                                        │
│     │   ├── Password reset not working                                          │
│     │   └── Account locked                                                      │
│     ├── 9.4 Billing Issues                                                      │
│     │   ├── Payment failed                                                      │
│     │   ├── Incorrect invoice                                                   │
│     │   └── Refund requests                                                     │
│     └── 9.5 Performance Issues                                                  │
│         ├── Slow loading                                                        │
│         ├── App not responding                                                  │
│         └── Browser compatibility                                               │
│                                                                                 │
│  10. FAQs                                                                        │
│      ├── General FAQs                                                           │
│      ├── Platform-Specific FAQs                                                 │
│      ├── Billing FAQs                                                           │
│      └── Technical FAQs                                                         │
│                                                                                 │
│  11. RELEASE NOTES & UPDATES                                                     │
│      ├── Latest Updates                                                         │
│      ├── Version History                                                        │
│      └── Upcoming Features                                                      │
│                                                                                 │
│  12. CONTACT SUPPORT                                                             │
│      ├── L2/L3 Support Ticket Submission                                        │
│      ├── Expected Response Times                                                │
│      └── Escalation Process                                                     │
│                                                                                 │
└─────────────────────────────────────────────────────────────────────────────────┘
```

### 4.2 Knowledge Base Features

| Feature | Description |
|---------|-------------|
| **Search** | Full-text search across all articles |
| **Categories** | Hierarchical organization |
| **Tags** | Cross-category tagging |
| **Related Articles** | AI-suggested related content |
| **Video Embedding** | Embedded tutorial videos |
| **Interactive Guides** | Step-by-step walkthroughs |
| **Feedback** | "Was this helpful?" on each article |
| **Versioning** | Article revision history |
| **Multi-language** | Support for multiple languages |
| **Print/PDF** | Export articles as PDF |

---

## 5. Feedback & Roadmap Module

### 5.1 Feedback Collection System

```
┌─────────────────────────────────────────────────────────────────────────────────┐
│                         FEEDBACK & ROADMAP MODULE                                │
├─────────────────────────────────────────────────────────────────────────────────┤
│                                                                                 │
│  TENANT VIEW                                                                     │
│  ┌─────────────────────────────────────────────────────────────────────────┐   │
│  │  SUBMIT FEEDBACK                                                         │   │
│  │  ├── Feature Request                                                     │   │
│  │  │   ├── Title                                                           │   │
│  │  │   ├── Description                                                     │   │
│  │  │   ├── Use Case                                                        │   │
│  │  │   ├── Priority (Nice to have / Important / Critical)                 │   │
│  │  │   └── Category (Content, Analytics, Billing, etc.)                   │   │
│  │  │                                                                       │   │
│  │  ├── Bug Report                                                          │   │
│  │  │   ├── Description                                                     │   │
│  │  │   ├── Steps to Reproduce                                              │   │
│  │  │   ├── Expected vs Actual Behavior                                     │   │
│  │  │   ├── Screenshots/Recordings                                          │   │
│  │  │   └── Browser/Device Info                                             │   │
│  │  │                                                                       │   │
│  │  └── General Feedback                                                    │   │
│  │      ├── Satisfaction Rating (1-5)                                       │   │
│  │      ├── What do you like?                                               │   │
│  │      ├── What could be improved?                                         │   │
│  │      └── Would you recommend? (NPS)                                      │   │
│  └─────────────────────────────────────────────────────────────────────────┘   │
│                                                                                 │
│  ┌─────────────────────────────────────────────────────────────────────────┐   │
│  │  VIEW ROADMAP (Public)                                                   │   │
│  │  ├── Planned Features                                                    │   │
│  │  │   └── Vote on features (upvote)                                       │   │
│  │  ├── In Progress                                                         │   │
│  │  ├── Recently Launched                                                   │   │
│  │  └── Under Consideration                                                 │   │
│  └─────────────────────────────────────────────────────────────────────────┘   │
│                                                                                 │
│  ┌─────────────────────────────────────────────────────────────────────────┐   │
│  │  MY FEEDBACK                                                             │   │
│  │  ├── Submitted feedback history                                          │   │
│  │  ├── Status updates on my requests                                       │   │
│  │  └── Notifications when status changes                                   │   │
│  └─────────────────────────────────────────────────────────────────────────┘   │
│                                                                                 │
│  SUPER ADMIN VIEW                                                                │
│  ┌─────────────────────────────────────────────────────────────────────────┐   │
│  │  FEEDBACK MANAGEMENT                                                     │   │
│  │  ├── All Feedback Dashboard                                              │   │
│  │  │   ├── Filter by category, status, priority, tenant type              │   │
│  │  │   ├── Sort by votes, date, priority                                   │   │
│  │  │   └── Bulk actions                                                    │   │
│  │  │                                                                       │   │
│  │  ├── Feedback Item Actions                                               │   │
│  │  │   ├── Change status (New → Reviewing → Planned → In Progress → Done) │   │
│  │  │   ├── Merge duplicates                                                │   │
│  │  │   ├── Add internal notes                                              │   │
│  │  │   ├── Assign to team member                                           │   │
│  │  │   └── Link to roadmap item                                            │   │
│  │  │                                                                       │   │
│  │  └── Analytics                                                           │   │
│  │      ├── Top requested features                                          │   │
│  │      ├── Feedback trends                                                 │   │
│  │      ├── NPS over time                                                   │   │
│  │      └── Feedback by tenant type                                         │   │
│  └─────────────────────────────────────────────────────────────────────────┘   │
│                                                                                 │
│  ┌─────────────────────────────────────────────────────────────────────────┐   │
│  │  ROADMAP MANAGEMENT                                                      │   │
│  │  ├── Create Roadmap Items                                                │   │
│  │  │   ├── Title                                                           │   │
│  │  │   ├── Description                                                     │   │
│  │  │   ├── Target Quarter                                                  │   │
│  │  │   ├── Linked Feedback Items                                           │   │
│  │  │   └── Status                                                          │   │
│  │  │                                                                       │   │
│  │  ├── Roadmap Visibility                                                  │   │
│  │  │   ├── Public (all tenants can see)                                    │   │
│  │  │   ├── Private (internal only)                                         │   │
│  │  │   └── Select Plans (only certain plans can see)                       │   │
│  │  │                                                                       │   │
│  │  └── Release Notes Generation                                            │   │
│  │      └── Auto-generate from completed roadmap items                      │   │
│  └─────────────────────────────────────────────────────────────────────────┘   │
│                                                                                 │
└─────────────────────────────────────────────────────────────────────────────────┘
```

---

## 6. Subscription & Billing with Razorpay

### 6.1 Razorpay Integration Architecture

```
┌─────────────────────────────────────────────────────────────────────────────────┐
│                      RAZORPAY BILLING ARCHITECTURE                               │
├─────────────────────────────────────────────────────────────────────────────────┤
│                                                                                 │
│  PAYMENT FLOWS                                                                   │
│                                                                                 │
│  ┌────────────────────────────────────────────────────────────────────────┐    │
│  │  DOMESTIC (INDIA)                                                       │    │
│  │  ├── UPI                                                                │    │
│  │  ├── Credit/Debit Cards                                                 │    │
│  │  ├── Net Banking                                                        │    │
│  │  ├── Wallets (Paytm, PhonePe, etc.)                                    │    │
│  │  ├── EMI Options                                                        │    │
│  │  └── NACH/eMandate (Recurring)                                          │    │
│  │                                                                         │    │
│  │  GST Handling:                                                          │    │
│  │  ├── 18% GST on subscription fees                                       │    │
│  │  ├── GST Invoice generation                                             │    │
│  │  ├── GSTIN validation                                                   │    │
│  │  └── HSN/SAC codes                                                      │    │
│  └────────────────────────────────────────────────────────────────────────┘    │
│                                                                                 │
│  ┌────────────────────────────────────────────────────────────────────────┐    │
│  │  INTERNATIONAL                                                          │    │
│  │  ├── International Cards (Visa, Mastercard, Amex)                       │    │
│  │  ├── PayPal (via Razorpay)                                              │    │
│  │  ├── Multi-currency support (USD, EUR, GBP, etc.)                       │    │
│  │  └── Dynamic currency conversion                                        │    │
│  │                                                                         │    │
│  │  Tax Handling:                                                          │    │
│  │  ├── No GST for exports                                                 │    │
│  │  ├── Tax ID collection                                                  │    │
│  │  └── Country-specific tax compliance                                    │    │
│  └────────────────────────────────────────────────────────────────────────┘    │
│                                                                                 │
│  SUBSCRIPTION PLANS                                                              │
│                                                                                 │
│  ┌────────────────────────────────────────────────────────────────────────┐    │
│  │  PLAN STRUCTURE                                                         │    │
│  │                                                                         │    │
│  │  FREE TIER                                                              │    │
│  │  ├── Price: ₹0 / $0                                                     │    │
│  │  ├── 1 Workspace                                                        │    │
│  │  ├── 2 Social Accounts                                                  │    │
│  │  ├── 1 Team Member                                                      │    │
│  │  ├── 30 Posts/month                                                     │    │
│  │  ├── Basic Analytics                                                    │    │
│  │  └── Community Support Only                                             │    │
│  │                                                                         │    │
│  │  STARTER                                                                │    │
│  │  ├── Price: ₹999/month or $15/month                                     │    │
│  │  ├── 3 Workspaces                                                       │    │
│  │  ├── 10 Social Accounts                                                 │    │
│  │  ├── 5 Team Members                                                     │    │
│  │  ├── 150 Posts/month                                                    │    │
│  │  ├── Standard Analytics                                                 │    │
│  │  ├── AI Assist (Basic)                                                  │    │
│  │  └── Email Support                                                      │    │
│  │                                                                         │    │
│  │  PROFESSIONAL                                                           │    │
│  │  ├── Price: ₹2,499/month or $35/month                                   │    │
│  │  ├── 10 Workspaces                                                      │    │
│  │  ├── 25 Social Accounts                                                 │    │
│  │  ├── 15 Team Members                                                    │    │
│  │  ├── Unlimited Posts                                                    │    │
│  │  ├── Advanced Analytics                                                 │    │
│  │  ├── AI Assist (Full)                                                   │    │
│  │  ├── Approval Workflows                                                 │    │
│  │  ├── Priority Support                                                   │    │
│  │  └── API Access (Basic)                                                 │    │
│  │                                                                         │    │
│  │  BUSINESS                                                               │    │
│  │  ├── Price: ₹4,999/month or $75/month                                   │    │
│  │  ├── Unlimited Workspaces                                               │    │
│  │  ├── 100 Social Accounts                                                │    │
│  │  ├── Unlimited Team Members                                             │    │
│  │  ├── Unlimited Posts                                                    │    │
│  │  ├── Custom Analytics                                                   │    │
│  │  ├── White-label Reports                                                │    │
│  │  ├── Full API Access                                                    │    │
│  │  ├── Dedicated Support                                                  │    │
│  │  └── SLA Guarantee                                                      │    │
│  │                                                                         │    │
│  │  ENTERPRISE                                                             │    │
│  │  ├── Price: Custom                                                      │    │
│  │  ├── Everything in Business                                             │    │
│  │  ├── Custom Integrations                                                │    │
│  │  ├── On-premise Option                                                  │    │
│  │  ├── Custom SLA                                                         │    │
│  │  ├── Dedicated Account Manager                                          │    │
│  │  └── Training & Onboarding                                              │    │
│  └────────────────────────────────────────────────────────────────────────┘    │
│                                                                                 │
│  BILLING FEATURES                                                                │
│                                                                                 │
│  ├── Subscription Management                                                    │
│  │   ├── Monthly/Annual billing cycles                                         │
│  │   ├── Annual discount (2 months free)                                       │
│  │   ├── Proration on upgrades                                                 │
│  │   ├── Schedule downgrades for next cycle                                    │
│  │   └── Trial periods (14 days for paid plans)                                │
│  │                                                                              │
│  ├── Invoice Management                                                         │
│  │   ├── Automated invoice generation                                          │
│  │   ├── GST-compliant invoices                                                │
│  │   ├── PDF download                                                          │
│  │   ├── Email delivery                                                        │
│  │   └── Credit notes for refunds                                              │
│  │                                                                              │
│  ├── Payment Recovery                                                           │
│  │   ├── Automatic retry (3 attempts)                                          │
│  │   ├── Dunning emails                                                        │
│  │   ├── Grace period (7 days)                                                 │
│  │   └── Account suspension after grace period                                 │
│  │                                                                              │
│  └── Super Admin Billing Dashboard                                              │
│      ├── Revenue metrics (MRR, ARR, churn)                                     │
│      ├── Payment success rate                                                   │
│      ├── Failed payments report                                                 │
│      ├── Subscription analytics                                                 │
│      └── Razorpay reconciliation                                               │
│                                                                                 │
└─────────────────────────────────────────────────────────────────────────────────┘
```

### 6.2 Razorpay Webhook Events

| Event | Action |
|-------|--------|
| `subscription.activated` | Activate tenant subscription |
| `subscription.charged` | Record payment, extend period |
| `subscription.pending` | Mark pending, send reminder |
| `subscription.halted` | Start grace period, notify |
| `subscription.cancelled` | Schedule downgrade |
| `subscription.completed` | Handle subscription end |
| `payment.authorized` | Pre-authorize for future |
| `payment.captured` | Confirm payment |
| `payment.failed` | Log failure, retry logic |
| `refund.created` | Process refund |
| `invoice.generated` | Store invoice reference |

---

## 7. Data Security & Trust Framework

### 7.1 Data Isolation Guarantees

```
┌─────────────────────────────────────────────────────────────────────────────────┐
│                        DATA SECURITY & TRUST FRAMEWORK                           │
├─────────────────────────────────────────────────────────────────────────────────┤
│                                                                                 │
│  TENANT DATA ISOLATION                                                           │
│                                                                                 │
│  ┌─────────────────────────────────────────────────────────────────────────┐   │
│  │  TECHNICAL GUARANTEES                                                    │   │
│  │                                                                          │   │
│  │  1. Database Level                                                       │   │
│  │     ├── Every table has tenant_id column                                │   │
│  │     ├── Row-level security policies                                      │   │
│  │     ├── All queries MUST include tenant_id in WHERE clause              │   │
│  │     ├── Database views with tenant filtering                             │   │
│  │     └── Audit logs for cross-tenant query attempts                       │   │
│  │                                                                          │   │
│  │  2. Application Level                                                    │   │
│  │     ├── Global scope automatically adds tenant filter                    │   │
│  │     ├── Middleware validates tenant access on every request             │   │
│  │     ├── Service layer enforces tenant boundaries                         │   │
│  │     ├── Background jobs carry tenant context                             │   │
│  │     └── API responses filtered by tenant                                 │   │
│  │                                                                          │   │
│  │  3. Storage Level                                                        │   │
│  │     ├── Separate S3/Storage folders per tenant                          │   │
│  │     ├── Signed URLs with tenant validation                               │   │
│  │     └── Backup segregation                                               │   │
│  │                                                                          │   │
│  │  4. Cache Level                                                          │   │
│  │     ├── Tenant-prefixed cache keys                                       │   │
│  │     └── Isolated Redis databases (optional)                              │   │
│  │                                                                          │   │
│  └─────────────────────────────────────────────────────────────────────────┘   │
│                                                                                 │
│  DATA PRIVACY COMMITMENTS                                                        │
│                                                                                 │
│  ┌─────────────────────────────────────────────────────────────────────────┐   │
│  │  PRIVACY POLICY HIGHLIGHTS                                               │   │
│  │                                                                          │   │
│  │  ✓ Your data belongs to you                                             │   │
│  │  ✓ We never share tenant data with other tenants                        │   │
│  │  ✓ We never sell your data to third parties                             │   │
│  │  ✓ We never use your content for AI training (without consent)          │   │
│  │  ✓ We never access your accounts without permission                      │   │
│  │  ✓ Data is encrypted at rest and in transit                             │   │
│  │  ✓ You can export all your data anytime                                 │   │
│  │  ✓ You can delete all your data anytime                                 │   │
│  │  ✓ Regular security audits                                               │   │
│  │  ✓ SOC 2 Type II compliance (roadmap)                                   │   │
│  │  ✓ GDPR compliance                                                       │   │
│  │  ✓ India DPDP Act compliance                                             │   │
│  │                                                                          │   │
│  └─────────────────────────────────────────────────────────────────────────┘   │
│                                                                                 │
│  DATA PROCESSING AGREEMENT (DPA)                                                 │
│                                                                                 │
│  ┌─────────────────────────────────────────────────────────────────────────┐   │
│  │  DPA COMPONENTS                                                          │   │
│  │                                                                          │   │
│  │  1. Data Controller vs Processor                                         │   │
│  │     ├── Tenant is Data Controller                                        │   │
│  │     └── Bizinso is Data Processor                                        │   │
│  │                                                                          │   │
│  │  2. Data Processing Purposes                                             │   │
│  │     ├── Provide platform services                                        │   │
│  │     ├── Maintain and improve services                                    │   │
│  │     ├── Customer support                                                 │   │
│  │     └── Legal compliance                                                 │   │
│  │                                                                          │   │
│  │  3. Sub-processors                                                       │   │
│  │     ├── List of all sub-processors                                       │   │
│  │     ├── Notification of changes                                          │   │
│  │     └── Right to object                                                  │   │
│  │                                                                          │   │
│  │  4. Security Measures                                                    │   │
│  │     ├── Technical measures detailed                                      │   │
│  │     ├── Organizational measures                                          │   │
│  │     └── Incident response procedures                                     │   │
│  │                                                                          │   │
│  │  5. Data Subject Rights                                                  │   │
│  │     ├── Access                                                           │   │
│  │     ├── Rectification                                                    │   │
│  │     ├── Erasure                                                          │   │
│  │     ├── Portability                                                      │   │
│  │     └── Objection                                                        │   │
│  │                                                                          │   │
│  │  6. Breach Notification                                                  │   │
│  │     ├── 72-hour notification                                             │   │
│  │     ├── Details provided                                                 │   │
│  │     └── Mitigation steps                                                 │   │
│  │                                                                          │   │
│  └─────────────────────────────────────────────────────────────────────────┘   │
│                                                                                 │
│  TRUST CENTER (Public Page)                                                      │
│                                                                                 │
│  ┌─────────────────────────────────────────────────────────────────────────┐   │
│  │  PUBLIC TRUST CENTER CONTENT                                             │   │
│  │                                                                          │   │
│  │  ├── Security Overview                                                   │   │
│  │  │   ├── Infrastructure security                                         │   │
│  │  │   ├── Application security                                            │   │
│  │  │   ├── Data encryption                                                 │   │
│  │  │   └── Access controls                                                 │   │
│  │  │                                                                       │   │
│  │  ├── Compliance                                                          │   │
│  │  │   ├── GDPR                                                            │   │
│  │  │   ├── India DPDP Act                                                  │   │
│  │  │   ├── ISO 27001 (roadmap)                                             │   │
│  │  │   └── SOC 2 Type II (roadmap)                                         │   │
│  │  │                                                                       │   │
│  │  ├── Privacy                                                             │   │
│  │  │   ├── Privacy Policy                                                  │   │
│  │  │   ├── Cookie Policy                                                   │   │
│  │  │   └── Data Processing Agreement                                       │   │
│  │  │                                                                       │   │
│  │  ├── Uptime & Reliability                                                │   │
│  │  │   ├── Status Page (live)                                              │   │
│  │  │   ├── Historical uptime                                               │   │
│  │  │   ├── Incident history                                                │   │
│  │  │   └── SLA details                                                     │   │
│  │  │                                                                       │   │
│  │  └── Security Reports (on request)                                       │   │
│  │      ├── Penetration test summary                                        │   │
│  │      ├── Vulnerability assessment                                        │   │
│  │      └── Security questionnaire                                          │   │
│  │                                                                          │   │
│  └─────────────────────────────────────────────────────────────────────────┘   │
│                                                                                 │
└─────────────────────────────────────────────────────────────────────────────────┘
```

---

## 8. Super Admin Module

### 8.1 Super Admin Dashboard

```
┌─────────────────────────────────────────────────────────────────────────────────┐
│                           SUPER ADMIN DASHBOARD                                  │
├─────────────────────────────────────────────────────────────────────────────────┤
│                                                                                 │
│  OVERVIEW METRICS                                                                │
│  ┌─────────────────────────────────────────────────────────────────────────┐   │
│  │  ┌─────────────┐ ┌─────────────┐ ┌─────────────┐ ┌─────────────┐        │   │
│  │  │ Total       │ │ Active      │ │ MRR         │ │ Churn Rate  │        │   │
│  │  │ Tenants     │ │ Tenants     │ │             │ │             │        │   │
│  │  │    1,234    │ │    1,156    │ │  ₹12.5L     │ │    2.3%     │        │   │
│  │  └─────────────┘ └─────────────┘ └─────────────┘ └─────────────┘        │   │
│  │                                                                          │   │
│  │  ┌─────────────┐ ┌─────────────┐ ┌─────────────┐ ┌─────────────┐        │   │
│  │  │ Total       │ │ Posts       │ │ API Calls   │ │ Avg         │        │   │
│  │  │ Users       │ │ Today       │ │ Today       │ │ NPS         │        │   │
│  │  │    5,678    │ │   23,456    │ │   456K      │ │    42       │        │   │
│  │  └─────────────┘ └─────────────┘ └─────────────┘ └─────────────┘        │   │
│  └─────────────────────────────────────────────────────────────────────────┘   │
│                                                                                 │
│  MODULES                                                                         │
│                                                                                 │
│  ├── Tenant Management                                                          │
│  │   ├── View all tenants                                                       │
│  │   ├── Create new tenant                                                      │
│  │   ├── Search/Filter tenants                                                  │
│  │   ├── Tenant details                                                         │
│  │   │   ├── Profile & Business info                                            │
│  │   │   ├── Subscription & Billing                                             │
│  │   │   ├── Usage metrics                                                      │
│  │   │   ├── Workspaces & Users                                                 │
│  │   │   ├── Social accounts                                                    │
│  │   │   └── Activity logs                                                      │
│  │   ├── Suspend tenant                                                         │
│  │   ├── Terminate tenant                                                       │
│  │   └── Impersonate tenant (for support)                                       │
│  │                                                                              │
│  ├── Subscription Management                                                    │
│  │   ├── Plan configuration                                                     │
│  │   │   ├── Create/Edit plans                                                  │
│  │   │   ├── Set pricing (INR/USD)                                              │
│  │   │   ├── Define limits                                                      │
│  │   │   └── Feature flags per plan                                             │
│  │   ├── Coupon/Discount management                                             │
│  │   ├── Revenue reports                                                        │
│  │   ├── Razorpay dashboard integration                                         │
│  │   └── Failed payment recovery                                                │
│  │                                                                              │
│  ├── Platform Configuration                                                     │
│  │   ├── Global settings                                                        │
│  │   ├── Feature flags                                                          │
│  │   ├── Maintenance mode                                                       │
│  │   ├── Email templates                                                        │
│  │   ├── Social platform configurations                                         │
│  │   └── AI provider settings                                                   │
│  │                                                                              │
│  ├── Knowledge Base Management                                                  │
│  │   ├── Article management                                                     │
│  │   ├── Category management                                                    │
│  │   ├── Video tutorials                                                        │
│  │   └── Analytics (most viewed, search terms)                                  │
│  │                                                                              │
│  ├── Feedback & Roadmap                                                         │
│  │   ├── Feedback dashboard                                                     │
│  │   ├── Roadmap management                                                     │
│  │   ├── Release notes                                                          │
│  │   └── Feature voting results                                                 │
│  │                                                                              │
│  ├── Support Management                                                         │
│  │   ├── Ticket queue (L2/L3)                                                   │
│  │   ├── Escalation management                                                  │
│  │   ├── SLA tracking                                                           │
│  │   └── Support analytics                                                      │
│  │                                                                              │
│  ├── Analytics & Reports                                                        │
│  │   ├── Platform usage                                                         │
│  │   ├── Revenue analytics                                                      │
│  │   ├── User behavior                                                          │
│  │   ├── Feature adoption                                                       │
│  │   ├── Error rates                                                            │
│  │   └── Performance metrics                                                    │
│  │                                                                              │
│  ├── Security & Audit                                                           │
│  │   ├── Audit logs                                                             │
│  │   ├── Security incidents                                                     │
│  │   ├── Access logs                                                            │
│  │   └── Compliance reports                                                     │
│  │                                                                              │
│  └── System Health                                                              │
│      ├── Infrastructure status                                                  │
│      ├── Background job status                                                  │
│      ├── Third-party API status                                                 │
│      ├── Error monitoring                                                       │
│      └── Performance dashboards                                                 │
│                                                                                 │
└─────────────────────────────────────────────────────────────────────────────────┘
```

---

## 9. Additional Considerations Identified

### 9.1 Must-Have Features for Enterprise Readiness

| Feature | Priority | Description |
|---------|----------|-------------|
| **White-labeling** | High | Allow agencies to white-label reports with their branding |
| **API Access for Tenants** | High | Tenants can build custom integrations |
| **Custom Domains** | Medium | Enterprise tenants can use their domain |
| **SSO/SAML** | Medium | Enterprise authentication options |
| **Audit Logging** | Critical | Comprehensive audit trail for compliance |
| **Data Export** | Critical | Full data export for portability |
| **Data Retention Policies** | High | Configurable retention periods |
| **Role Customization** | Medium | Custom roles beyond standard four |
| **Webhook Notifications** | High | Real-time event notifications to tenants |
| **Scheduled Reports** | Medium | Auto-email analytics reports |
| **Multi-language Support** | Medium | UI in multiple languages |
| **Timezone per User** | High | Individual timezone settings |
| **Status Page** | High | Public status page for uptime |
| **Backup & Recovery** | Critical | Documented backup procedures |
| **Disaster Recovery** | Critical | DR plan and RTO/RPO targets |

### 9.2 Compliance Requirements

| Regulation | Applicability | Requirements |
|------------|---------------|--------------|
| **GDPR** | EU tenants | DPA, data portability, erasure |
| **India DPDP Act** | Indian tenants | Data localization, consent management |
| **CCPA** | California tenants | Privacy rights, opt-out |
| **SOC 2 Type II** | Enterprise tenants | Security controls audit |
| **ISO 27001** | Enterprise tenants | Information security management |

### 9.3 Platform Limits by Consideration

| Limit Type | Why Important |
|------------|---------------|
| API rate limits | Prevent abuse, ensure fair usage |
| Storage limits | Cost management |
| Team member limits | Tier differentiation |
| Workspace limits | Tier differentiation |
| Post scheduling limits | Resource management |
| Report generation limits | Prevent heavy compute abuse |

---

## 10. Updated Data Model Additions

### 10.1 New Entities Required

```
NEW ENTITIES FOR EXPANDED ARCHITECTURE

┌─────────────────────────────────────────────────────────────────┐
│  SUPER ADMIN DOMAIN                                              │
├─────────────────────────────────────────────────────────────────┤
│  - SuperAdminUser                                                │
│  - TenantOnboarding (onboarding progress tracking)              │
│  - PlatformConfig (global configuration)                         │
│  - FeatureFlag (global and per-plan flags)                      │
│  - PlanDefinition (subscription plans)                          │
│  - PlanLimit (limits per plan)                                  │
│  - AuditLog (platform-wide audit)                               │
└─────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────┐
│  TENANT DOMAIN (Extended)                                        │
├─────────────────────────────────────────────────────────────────┤
│  - Tenant (top-level, above workspace)                          │
│  - TenantProfile (business details)                             │
│  - TenantBilling (Razorpay subscription)                        │
│  - TenantInvoice                                                │
│  - TenantUsage (usage tracking)                                 │
│  - TenantSettings (tenant-wide settings)                        │
│  - TenantAuditLog (tenant-specific audit)                       │
└─────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────┐
│  KNOWLEDGE BASE DOMAIN                                           │
├─────────────────────────────────────────────────────────────────┤
│  - KBCategory                                                    │
│  - KBArticle                                                     │
│  - KBArticleVersion                                              │
│  - KBTag                                                         │
│  - KBFeedback (article ratings)                                 │
│  - KBSearchLog (search analytics)                               │
└─────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────┐
│  FEEDBACK & ROADMAP DOMAIN                                       │
├─────────────────────────────────────────────────────────────────┤
│  - Feedback                                                      │
│  - FeedbackVote                                                  │
│  - FeedbackComment (internal notes)                             │
│  - RoadmapItem                                                   │
│  - RoadmapItemFeedback (links)                                  │
│  - ReleaseNote                                                   │
└─────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────┐
│  SUPPORT DOMAIN                                                  │
├─────────────────────────────────────────────────────────────────┤
│  - SupportTicket                                                 │
│  - TicketMessage                                                 │
│  - TicketAttachment                                              │
│  - TicketCategory                                                │
│  - SLAPolicy                                                     │
└─────────────────────────────────────────────────────────────────┘
```

---

## 11. Updated Hierarchy

```
PREVIOUS (Phase-1):
User → Workspace → Resources

UPDATED:
SuperAdmin → Tenant → User → Workspace → Resources

Where:
- SuperAdmin: Bizinso platform administrators
- Tenant: A paying customer organization (billing entity)
- User: Individual users within a tenant
- Workspace: Organizational units within tenant
- Resources: Posts, Social Accounts, etc.
```

---

## 12. Implementation Priority

### Phase 1A (Foundation) - 4 weeks
1. Super Admin authentication & basic dashboard
2. Tenant entity and management
3. Updated User model with tenant association
4. Razorpay integration (basic)
5. Plan definitions and limits

### Phase 1B (Onboarding) - 3 weeks
1. Tenant onboarding flows (all types)
2. Initial configuration wizard
3. Knowledge base foundation
4. Basic support ticket system

### Phase 1C (Core Features) - 6 weeks
1. All social platform connections
2. Content engine
3. Analytics
4. Approval workflows

### Phase 1D (Enterprise) - 3 weeks
1. Feedback & Roadmap module
2. Advanced billing features
3. Data export/security features
4. Trust center

---

**Document Status:** Draft - Pending Review
**Next Steps:** Review with stakeholder and confirm priorities
