# BizSocials — Social Media Platforms Specification

**Version:** 1.0
**Date:** February 2026
**Status:** Draft
**Purpose:** Complete specification for all supported social media platforms

---

## 1. Platform Overview

### 1.1 Platform Matrix

| Platform | Category | OAuth | API | Status | Priority |
|----------|----------|-------|-----|--------|----------|
| LinkedIn | Professional | 2.0 | Marketing API | ✅ Phase-1 | Critical |
| Facebook | General | 2.0 | Graph API | ✅ Phase-1 | Critical |
| Instagram | Visual | 2.0 | Graph API | ✅ Phase-1 | Critical |
| Twitter/X | Microblog | 2.0 PKCE | API v2 | ✅ Phase-1 | High |
| YouTube | Video | 2.0 | Data API v3 | ⏳ Phase-2 | High |
| TikTok | Short Video | 2.0 | Marketing API | ⏳ Phase-2 | Medium |
| Pinterest | Visual Discovery | 2.0 | API v5 | ⏳ Phase-2 | Medium |
| Threads | Microblog | 2.0 | Threads API | ⏳ Phase-2 | Medium |
| Google Business | Local | 2.0 | Business API | ⏳ Phase-2 | Medium |
| WhatsApp Business | Messaging | Cloud API | Business API | ⏳ Phase-3 | Low |
| Telegram | Messaging | Bot API | Bot API | ⏳ Phase-3 | Low |

### 1.2 Account Types by Platform

| Platform | Personal | Page/Business | Creator | Channel |
|----------|:--------:|:-------------:|:-------:|:-------:|
| LinkedIn | ✓ | ✓ Company | - | - |
| Facebook | - | ✓ Page | - | - |
| Instagram | - | ✓ Business | ✓ Creator | - |
| Twitter/X | ✓ | ✓ Business | - | - |
| YouTube | - | - | - | ✓ Channel |
| TikTok | - | ✓ Business | ✓ Creator | - |
| Pinterest | - | ✓ Business | - | - |

---

## 2. LinkedIn Integration

### 2.1 Overview

| Aspect | Details |
|--------|---------|
| API | LinkedIn Marketing API |
| OAuth | OAuth 2.0 (3-legged) |
| Account Types | Personal Profile, Company Page |
| Content Types | Text, Images, Videos, Articles, Documents, Polls |

### 2.2 Required Scopes

```
# Basic Profile
openid
profile
email

# Organization Access
w_member_social          # Post on behalf of user
r_organization_social    # Read org posts
w_organization_social    # Post on behalf of org
rw_organization_admin    # Manage org pages

# Analytics (optional)
r_organization_analytics # Read org analytics
```

### 2.3 OAuth Flow

```
1. Authorization URL:
   https://www.linkedin.com/oauth/v2/authorization
   ?response_type=code
   &client_id={CLIENT_ID}
   &redirect_uri={REDIRECT_URI}
   &state={STATE}
   &scope=openid profile email w_member_social r_organization_social w_organization_social

2. Token Exchange:
   POST https://www.linkedin.com/oauth/v2/accessToken
   grant_type=authorization_code
   &code={CODE}
   &redirect_uri={REDIRECT_URI}
   &client_id={CLIENT_ID}
   &client_secret={CLIENT_SECRET}

3. Token Response:
   {
     "access_token": "...",
     "expires_in": 5184000,  // 60 days
     "refresh_token": "...",
     "refresh_token_expires_in": 31536000  // 1 year
   }
```

### 2.4 Content Specifications

| Content Type | Max Length | Media Limits | Notes |
|--------------|------------|--------------|-------|
| Text Post | 3,000 chars | - | Supports mentions, hashtags |
| Image Post | 3,000 chars | 1 image, max 5MB | JPG, PNG, GIF |
| Multi-Image | 3,000 chars | Up to 20 images | Carousel format |
| Video Post | 3,000 chars | Max 200MB, 10 min | MP4, MOV |
| Article | 125,000 chars | Cover image | Long-form content |
| Document | 3,000 chars | PDF, PPT, max 100MB | Carousel document |
| Poll | 140 chars/option | 2-4 options | 1-14 day duration |

### 2.5 API Endpoints Used

```
# Post to Personal Profile
POST https://api.linkedin.com/v2/ugcPosts

# Post to Company Page
POST https://api.linkedin.com/v2/ugcPosts
(with author = organization URN)

# Get Company Pages
GET https://api.linkedin.com/v2/organizationalEntityAcls
?q=roleAssignee
&role=ADMINISTRATOR

# Upload Image
POST https://api.linkedin.com/v2/assets
?action=registerUpload

# Get Post Analytics
GET https://api.linkedin.com/v2/organizationalEntityShareStatistics
```

### 2.6 Rate Limits

| Endpoint | Limit |
|----------|-------|
| Share creation | 100/day per member |
| Organization shares | 100/day per org |
| API calls general | 100/day |

---

## 3. Facebook Integration

### 3.1 Overview

| Aspect | Details |
|--------|---------|
| API | Facebook Graph API v18.0 |
| OAuth | OAuth 2.0 |
| Account Types | Page |
| Content Types | Text, Images, Videos, Links, Stories, Reels |

### 3.2 Required Permissions

```
# Page Access
pages_show_list           # List pages user manages
pages_read_engagement     # Read page content & engagement
pages_manage_posts        # Create/edit/delete posts
pages_manage_engagement   # Respond to comments

# Media
pages_read_user_content   # Read user content on page

# Analytics
read_insights             # Read page insights

# Instagram (linked)
instagram_basic           # Basic Instagram access
instagram_content_publish # Publish to Instagram
instagram_manage_comments # Manage Instagram comments
instagram_manage_insights # Instagram analytics
```

### 3.3 OAuth Flow

```
1. Authorization URL:
   https://www.facebook.com/v18.0/dialog/oauth
   ?client_id={APP_ID}
   &redirect_uri={REDIRECT_URI}
   &state={STATE}
   &scope=pages_show_list,pages_read_engagement,pages_manage_posts,...

2. Exchange for Access Token:
   GET https://graph.facebook.com/v18.0/oauth/access_token
   ?client_id={APP_ID}
   &redirect_uri={REDIRECT_URI}
   &client_secret={APP_SECRET}
   &code={CODE}

3. Get Long-Lived Token (60 days):
   GET https://graph.facebook.com/v18.0/oauth/access_token
   ?grant_type=fb_exchange_token
   &client_id={APP_ID}
   &client_secret={APP_SECRET}
   &fb_exchange_token={SHORT_LIVED_TOKEN}

4. Get Page Access Token:
   GET https://graph.facebook.com/v18.0/me/accounts
   ?access_token={USER_ACCESS_TOKEN}
```

### 3.4 Content Specifications

| Content Type | Max Length | Media Limits | Notes |
|--------------|------------|--------------|-------|
| Text Post | 63,206 chars | - | Rich formatting |
| Photo Post | 63,206 chars | Up to 10 photos | JPG, PNG, GIF |
| Video Post | 63,206 chars | Max 4GB, 240 min | MP4, MOV |
| Link Post | 63,206 chars | Link preview auto | OG tags supported |
| Story | - | Image or 20s video | 24-hour expiry |
| Reel | 2,200 chars | 3-90 seconds | Vertical video |

### 3.5 API Endpoints Used

```
# Create Post
POST https://graph.facebook.com/v18.0/{page-id}/feed
?message={TEXT}
&access_token={PAGE_ACCESS_TOKEN}

# Create Photo Post
POST https://graph.facebook.com/v18.0/{page-id}/photos
?url={IMAGE_URL}
&caption={TEXT}
&access_token={PAGE_ACCESS_TOKEN}

# Create Video Post
POST https://graph.facebook.com/v18.0/{page-id}/videos
?file_url={VIDEO_URL}
&description={TEXT}
&access_token={PAGE_ACCESS_TOKEN}

# Get Page Posts
GET https://graph.facebook.com/v18.0/{page-id}/posts
?fields=id,message,created_time,attachments,insights

# Get Comments
GET https://graph.facebook.com/v18.0/{post-id}/comments

# Reply to Comment
POST https://graph.facebook.com/v18.0/{comment-id}/comments
?message={REPLY_TEXT}
```

### 3.6 Rate Limits

| Limit Type | Value |
|------------|-------|
| App-level | 200 calls/user/hour |
| Page-level | 4800 calls/day |
| Posting | Platform limits apply |

---

## 4. Instagram Integration

### 4.1 Overview

| Aspect | Details |
|--------|---------|
| API | Instagram Graph API (via Facebook) |
| OAuth | OAuth 2.0 (via Facebook) |
| Account Types | Business, Creator |
| Content Types | Feed Posts, Stories, Reels, Carousels |

### 4.2 Requirements

- Must be Business or Creator account
- Must be connected to a Facebook Page
- OAuth goes through Facebook

### 4.3 Content Specifications

| Content Type | Max Length | Media Limits | Notes |
|--------------|------------|--------------|-------|
| Feed Photo | 2,200 chars | 1 image, 8MB max | Square/Portrait/Landscape |
| Feed Video | 2,200 chars | 3s-60s, 100MB max | Feed videos |
| Carousel | 2,200 chars | 2-10 items | Mixed media |
| Story | - | Image/15s video | 24-hour expiry |
| Reel | 2,200 chars | 3s-90s | Vertical 9:16 |

### 4.4 API Endpoints Used

```
# Get Instagram Account
GET https://graph.facebook.com/v18.0/{page-id}
?fields=instagram_business_account

# Create Media Container
POST https://graph.facebook.com/v18.0/{ig-user-id}/media
?image_url={URL}
&caption={TEXT}
&access_token={TOKEN}

# Publish Media
POST https://graph.facebook.com/v18.0/{ig-user-id}/media_publish
?creation_id={CONTAINER_ID}
&access_token={TOKEN}

# Create Carousel
POST https://graph.facebook.com/v18.0/{ig-user-id}/media
?media_type=CAROUSEL
&caption={TEXT}
&children={CONTAINER_IDS}

# Get Comments
GET https://graph.facebook.com/v18.0/{media-id}/comments

# Reply to Comment
POST https://graph.facebook.com/v18.0/{comment-id}/replies
?message={TEXT}
```

### 4.5 Media Requirements

| Format | Feed | Story | Reel |
|--------|------|-------|------|
| Aspect Ratio | 1:1, 4:5, 1.91:1 | 9:16 | 9:16 |
| Min Resolution | 320px | 720px | 720px |
| Max Resolution | 1080px | 1080x1920 | 1080x1920 |
| Image Formats | JPG, PNG | JPG, PNG | - |
| Video Formats | MP4, MOV | MP4, MOV | MP4, MOV |
| Video Codec | H.264 | H.264 | H.264 |
| Audio Codec | AAC | AAC | AAC |

---

## 5. Twitter/X Integration

### 5.1 Overview

| Aspect | Details |
|--------|---------|
| API | Twitter API v2 |
| OAuth | OAuth 2.0 with PKCE |
| Account Types | Personal, Business |
| Content Types | Tweets, Threads, Polls, Spaces |

### 5.2 API Tiers

| Tier | Cost | Tweet Reads | Tweet Writes | Notes |
|------|------|-------------|--------------|-------|
| Free | $0 | 10K/month | 1.5K/month | Very limited |
| Basic | $100/mo | 10K/month | 3K/month | Small apps |
| Pro | $5K/mo | 1M/month | 300K/month | Commercial use |

### 5.3 Required Scopes

```
tweet.read        # Read tweets
tweet.write       # Create/delete tweets
users.read        # Read user info
offline.access    # Refresh tokens

# Optional
tweet.moderate    # Hide replies
follows.read      # Read follows
follows.write     # Follow/unfollow
```

### 5.4 OAuth Flow (PKCE)

```
1. Generate Code Verifier & Challenge:
   code_verifier = random(43-128 chars)
   code_challenge = base64url(sha256(code_verifier))

2. Authorization URL:
   https://twitter.com/i/oauth2/authorize
   ?response_type=code
   &client_id={CLIENT_ID}
   &redirect_uri={REDIRECT_URI}
   &scope=tweet.read tweet.write users.read offline.access
   &state={STATE}
   &code_challenge={CODE_CHALLENGE}
   &code_challenge_method=S256

3. Token Exchange:
   POST https://api.twitter.com/2/oauth2/token
   Content-Type: application/x-www-form-urlencoded

   code={CODE}
   &grant_type=authorization_code
   &client_id={CLIENT_ID}
   &redirect_uri={REDIRECT_URI}
   &code_verifier={CODE_VERIFIER}

4. Token Response:
   {
     "access_token": "...",
     "token_type": "bearer",
     "expires_in": 7200,  // 2 hours
     "refresh_token": "...",
     "scope": "..."
   }
```

### 5.5 Content Specifications

| Content Type | Max Length | Media Limits | Notes |
|--------------|------------|--------------|-------|
| Tweet | 280 chars (4,000 for Blue) | - | Basic tweet |
| Tweet + Images | 280 chars | Up to 4 images | GIF counts as 1 |
| Tweet + Video | 280 chars | 140s, 512MB | MP4 only |
| Tweet + GIF | 280 chars | 1 GIF | Animated |
| Thread | 280/tweet | Per tweet | Up to 25 tweets |
| Poll | 25 chars/option | 2-4 options | 5min-7day duration |

### 5.6 API Endpoints Used

```
# Create Tweet
POST https://api.twitter.com/2/tweets
{
  "text": "Hello World!"
}

# Create Tweet with Media
POST https://api.twitter.com/2/tweets
{
  "text": "Check this out!",
  "media": {
    "media_ids": ["1234567890"]
  }
}

# Upload Media (v1.1 - still required)
POST https://upload.twitter.com/1.1/media/upload.json

# Create Thread
POST https://api.twitter.com/2/tweets
{
  "text": "First tweet",
  "reply": {
    "in_reply_to_tweet_id": "previous_tweet_id"
  }
}

# Get Mentions
GET https://api.twitter.com/2/users/{id}/mentions

# Get Tweet Metrics
GET https://api.twitter.com/2/tweets/{id}
?tweet.fields=public_metrics
```

### 5.7 Rate Limits

| Endpoint | Limit |
|----------|-------|
| Post tweet | 100/15 min per user |
| Upload media | 615/15 min |
| User mentions | 450/15 min |
| Tweet lookup | 300/15 min (Basic) |

---

## 6. YouTube Integration

### 6.1 Overview

| Aspect | Details |
|--------|---------|
| API | YouTube Data API v3 |
| OAuth | OAuth 2.0 |
| Account Types | Channel |
| Content Types | Videos, Shorts, Community Posts |

### 6.2 Required Scopes

```
https://www.googleapis.com/auth/youtube                 # Full access
https://www.googleapis.com/auth/youtube.upload          # Upload videos
https://www.googleapis.com/auth/youtube.readonly        # Read-only
https://www.googleapis.com/auth/youtube.force-ssl       # Manage videos
https://www.googleapis.com/auth/yt-analytics.readonly   # Analytics
```

### 6.3 Content Specifications

| Content Type | Max Length | Media Limits | Notes |
|--------------|------------|--------------|-------|
| Video Title | 100 chars | - | Required |
| Description | 5,000 chars | - | Supports links |
| Tags | 500 chars total | - | Comma separated |
| Video | - | 12 hours, 256GB | Standard upload |
| Short | - | Up to 60 seconds | Vertical 9:16 |
| Thumbnail | - | 2MB, 1280x720 | JPG, PNG |
| Community Post | 10,000 chars | Images, polls | Channel feature |

### 6.4 API Endpoints Used

```
# Upload Video
POST https://www.googleapis.com/upload/youtube/v3/videos
?uploadType=resumable
&part=snippet,status

# Set Video Details
{
  "snippet": {
    "title": "Video Title",
    "description": "Description",
    "tags": ["tag1", "tag2"],
    "categoryId": "22"
  },
  "status": {
    "privacyStatus": "public",
    "publishAt": "2026-03-01T10:00:00Z"
  }
}

# Get Channel Videos
GET https://www.googleapis.com/youtube/v3/search
?part=snippet
&channelId={CHANNEL_ID}
&type=video

# Get Video Analytics
GET https://youtubeanalytics.googleapis.com/v2/reports
?ids=channel=={CHANNEL_ID}
&startDate=2026-01-01
&endDate=2026-02-01
&metrics=views,likes,comments
```

### 6.5 Quota System

YouTube uses a quota system (10,000 units/day default):

| Operation | Cost |
|-----------|------|
| Read operation | 1 unit |
| Video upload | 1,600 units |
| Playlist/channel update | 50 units |
| Search | 100 units |

---

## 7. TikTok Integration

### 7.1 Overview

| Aspect | Details |
|--------|---------|
| API | TikTok Content Posting API |
| OAuth | OAuth 2.0 |
| Account Types | Business, Creator |
| Content Types | Videos |

### 7.2 Required Scopes

```
user.info.basic    # Basic user info
video.publish      # Publish videos
video.list         # List user videos
```

### 7.3 Content Specifications

| Content Type | Max Length | Media Limits | Notes |
|--------------|------------|--------------|-------|
| Caption | 2,200 chars | - | Supports hashtags, mentions |
| Video | - | 60s-10min, 287MB | Standard |
| Video (API) | - | 60s max via API | API limitation |
| Aspect Ratio | - | 9:16, 1:1, 16:9 | Vertical preferred |

### 7.4 API Endpoints Used

```
# Upload Video (Step 1: Initialize)
POST https://open.tiktokapis.com/v2/post/publish/video/init/

{
  "post_info": {
    "title": "Video title",
    "privacy_level": "PUBLIC_TO_EVERYONE",
    "disable_comment": false,
    "disable_duet": false,
    "disable_stitch": false
  },
  "source_info": {
    "source": "FILE_UPLOAD",
    "video_size": 50000000
  }
}

# Upload Video (Step 2: Upload chunks)
PUT {upload_url}
Content-Type: video/mp4
Content-Range: bytes 0-49999999/50000000

# Get User Videos
GET https://open.tiktokapis.com/v2/video/list/
?max_count=20
```

### 7.5 Rate Limits

| Endpoint | Limit |
|----------|-------|
| Video upload | 3/day per user |
| Video list | 60/min |

---

## 8. Pinterest Integration

### 8.1 Overview

| Aspect | Details |
|--------|---------|
| API | Pinterest API v5 |
| OAuth | OAuth 2.0 |
| Account Types | Business |
| Content Types | Pins, Idea Pins, Boards |

### 8.2 Required Scopes

```
boards:read          # Read boards
boards:write         # Create/edit boards
pins:read            # Read pins
pins:write           # Create/edit pins
user_accounts:read   # Read user info
```

### 8.3 Content Specifications

| Content Type | Max Length | Media Limits | Notes |
|--------------|------------|--------------|-------|
| Pin Title | 100 chars | - | Required |
| Description | 500 chars | - | SEO important |
| Standard Pin | - | 1 image, 20MB | JPEG, PNG |
| Video Pin | - | 2GB, 4s-15min | MP4, MOV |
| Idea Pin | - | Up to 20 pages | Mixed media |
| Image Size | - | 1000x1500 recommended | 2:3 ratio |

### 8.4 API Endpoints Used

```
# Create Pin
POST https://api.pinterest.com/v5/pins
{
  "board_id": "123456789",
  "media_source": {
    "source_type": "image_url",
    "url": "https://example.com/image.jpg"
  },
  "title": "Pin Title",
  "description": "Pin description",
  "link": "https://example.com"
}

# Get Boards
GET https://api.pinterest.com/v5/boards

# Get Pin Analytics
GET https://api.pinterest.com/v5/pins/{pin_id}/analytics
?start_date=2026-01-01
&end_date=2026-02-01
&metric_types=IMPRESSION,SAVE,PIN_CLICK
```

---

## 9. Google Business Profile Integration

### 9.1 Overview

| Aspect | Details |
|--------|---------|
| API | Google Business Profile API |
| OAuth | OAuth 2.0 |
| Account Types | Business Location |
| Content Types | Posts, Photos, Q&A, Reviews |

### 9.2 Required Scopes

```
https://www.googleapis.com/auth/business.manage
```

### 9.3 Content Specifications

| Content Type | Max Length | Notes |
|--------------|------------|-------|
| Post Summary | 1,500 chars | What's New, Event, Offer |
| Post CTA | - | Optional button |
| Photo | 10MB max | JPG, PNG |
| Post Duration | 7 days | Auto-archived |

### 9.4 API Endpoints Used

```
# Create Post
POST https://mybusiness.googleapis.com/v4/accounts/{account}/locations/{location}/localPosts
{
  "summary": "Check out our special offer!",
  "callToAction": {
    "actionType": "LEARN_MORE",
    "url": "https://example.com"
  },
  "media": [{
    "mediaFormat": "PHOTO",
    "sourceUrl": "https://example.com/photo.jpg"
  }],
  "topicType": "OFFER"
}

# Get Reviews
GET https://mybusiness.googleapis.com/v4/accounts/{account}/locations/{location}/reviews

# Reply to Review
POST https://mybusiness.googleapis.com/v4/accounts/{account}/locations/{location}/reviews/{review}/reply
{
  "comment": "Thank you for your feedback!"
}
```

---

## 10. Platform Status Handling

### 10.1 Token Management

```php
class TokenManager
{
    /**
     * Check and refresh tokens that are expiring soon
     */
    public function refreshExpiringTokens(): void
    {
        $accounts = SocialAccount::query()
            ->where('status', 'ACTIVE')
            ->where('token_expires_at', '<', now()->addDays(7))
            ->get();

        foreach ($accounts as $account) {
            try {
                $this->refreshToken($account);
            } catch (Exception $e) {
                $account->update([
                    'status' => 'EXPIRED',
                    'last_error' => $e->getMessage(),
                ]);

                // Notify workspace admins
                $this->notifyTokenExpired($account);
            }
        }
    }

    /**
     * Refresh token for a specific account
     */
    public function refreshToken(SocialAccount $account): void
    {
        $service = $this->getServiceForPlatform($account->platform_code);

        $newTokens = $service->refreshAccessToken($account->refresh_token);

        $account->update([
            'access_token' => encrypt($newTokens['access_token']),
            'refresh_token' => isset($newTokens['refresh_token'])
                ? encrypt($newTokens['refresh_token'])
                : $account->refresh_token,
            'token_expires_at' => now()->addSeconds($newTokens['expires_in']),
            'last_sync_at' => now(),
        ]);
    }
}
```

### 10.2 Error Handling

| Error Type | Action | User Notification |
|------------|--------|-------------------|
| Token expired | Auto-refresh | None (if successful) |
| Refresh failed | Mark EXPIRED | Email + in-app |
| Rate limited | Backoff retry | Show warning |
| Permission revoked | Mark REVOKED | Email + in-app |
| API error | Log + retry | Show if persistent |

---

## 11. Publishing Queue

### 11.1 Queue Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                    PUBLISHING QUEUE FLOW                         │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  ┌─────────────┐                                               │
│  │  Scheduled  │                                               │
│  │    Posts    │                                               │
│  └──────┬──────┘                                               │
│         │                                                       │
│         ▼                                                       │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │              SCHEDULER (runs every minute)               │   │
│  │  - Finds posts where scheduled_at <= now                 │   │
│  │  - Creates PublishPostJob for each                       │   │
│  └─────────────────────────────────────────────────────────┘   │
│         │                                                       │
│         ▼                                                       │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │                    QUEUE WORKERS                         │   │
│  │  ┌──────────┐  ┌──────────┐  ┌──────────┐              │   │
│  │  │ Worker 1 │  │ Worker 2 │  │ Worker 3 │              │   │
│  │  └────┬─────┘  └────┬─────┘  └────┬─────┘              │   │
│  │       │             │             │                      │   │
│  │       ▼             ▼             ▼                      │   │
│  │  ┌────────────────────────────────────────────────┐     │   │
│  │  │           Platform-Specific Publishers          │     │   │
│  │  │  LinkedIn │ Facebook │ Instagram │ Twitter     │     │   │
│  │  └────────────────────────────────────────────────┘     │   │
│  └─────────────────────────────────────────────────────────┘   │
│         │                                                       │
│         ▼                                                       │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │                    RESULT HANDLING                       │   │
│  │  Success → Mark PUBLISHED, store external_id            │   │
│  │  Failure → Retry (3x) → Mark FAILED, notify             │   │
│  └─────────────────────────────────────────────────────────┘   │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

### 11.2 Publishing Job

```php
class PublishPostJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60; // seconds

    public function __construct(
        public PostTarget $target
    ) {}

    public function handle(): void
    {
        $post = $this->target->post;
        $account = $this->target->socialAccount;

        // Validate account is still active
        if ($account->status !== 'ACTIVE') {
            $this->target->update([
                'status' => 'FAILED',
                'error_message' => 'Social account is not active',
            ]);
            return;
        }

        // Get platform publisher
        $publisher = app(PublisherFactory::class)
            ->getPublisher($account->platform_code);

        try {
            $this->target->update(['status' => 'PUBLISHING']);

            $result = $publisher->publish($post, $account);

            $this->target->update([
                'status' => 'PUBLISHED',
                'external_post_id' => $result['id'],
                'external_post_url' => $result['url'],
                'published_at' => now(),
            ]);

            // Check if all targets published
            $this->checkPostCompletion($post);

        } catch (RateLimitException $e) {
            // Retry with backoff
            $this->release($e->getRetryAfter());

        } catch (Exception $e) {
            $this->target->update([
                'retry_count' => $this->target->retry_count + 1,
                'error_message' => $e->getMessage(),
            ]);

            throw $e; // Let Laravel handle retry
        }
    }

    public function failed(Throwable $exception): void
    {
        $this->target->update([
            'status' => 'FAILED',
            'error_code' => $exception->getCode(),
            'error_message' => $exception->getMessage(),
        ]);

        // Notify user
        Notification::send(
            $this->target->post->createdBy,
            new PostPublishFailed($this->target)
        );
    }
}
```

---

## 12. Analytics Collection

### 12.1 Metrics by Platform

| Platform | Metrics Collected |
|----------|-------------------|
| LinkedIn | Impressions, clicks, engagement, shares, comments |
| Facebook | Reach, impressions, engagement, reactions, shares |
| Instagram | Reach, impressions, likes, comments, saves, shares |
| Twitter | Impressions, engagements, likes, retweets, replies |
| YouTube | Views, watch time, likes, comments, shares |
| TikTok | Views, likes, comments, shares |
| Pinterest | Impressions, saves, clicks |

### 12.2 Collection Schedule

```
┌─────────────────────────────────────────────────────────────────┐
│               ANALYTICS COLLECTION SCHEDULE                      │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  Post Metrics:                                                  │
│  • 1 hour after publish                                         │
│  • 24 hours after publish                                       │
│  • 7 days after publish                                         │
│  • 30 days after publish                                        │
│                                                                 │
│  Account Metrics:                                               │
│  • Daily (follower counts, page metrics)                        │
│  • Weekly (detailed analytics)                                  │
│                                                                 │
│  Inbox Sync:                                                    │
│  • Every 15 minutes (comments, mentions)                        │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

---

**Document Version:** 1.0
**Last Updated:** February 2026
**Status:** Draft
