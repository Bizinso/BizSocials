# AI Features Module Specification

## Document Information
- **Version**: 1.0.0
- **Created**: 2025-02-06
- **Module**: AI-Powered Features
- **AI Provider**: OpenAI/Anthropic/Self-hosted

---

## 1. Overview

### 1.1 Purpose
Provide AI-powered features to enhance content creation, scheduling optimization, analytics insights, and overall platform productivity for all tenants.

### 1.2 AI Features Scope
```
┌─────────────────────────────────────────────────────────────────┐
│                    AI FEATURES OVERVIEW                         │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  CONTENT CREATION                                               │
│  ├── Caption Generator                                          │
│  ├── Hashtag Suggestions                                        │
│  ├── Content Repurposing                                        │
│  ├── Image Alt Text Generator                                   │
│  └── Content Tone Adjustment                                    │
│                                                                 │
│  SCHEDULING & OPTIMIZATION                                      │
│  ├── Best Time to Post                                          │
│  ├── Posting Frequency Recommendations                          │
│  └── Content Calendar Suggestions                               │
│                                                                 │
│  ANALYTICS & INSIGHTS                                           │
│  ├── Performance Predictions                                    │
│  ├── Trend Detection                                            │
│  ├── Competitor Analysis Insights                               │
│  └── Audience Sentiment Analysis                                │
│                                                                 │
│  ENGAGEMENT                                                     │
│  ├── Reply Suggestions                                          │
│  ├── Comment Sentiment Classification                           │
│  └── Priority Inbox Scoring                                     │
│                                                                 │
│  AUTOMATION                                                     │
│  ├── Auto-categorization                                        │
│  ├── Content Moderation                                         │
│  └── Spam Detection                                             │
└─────────────────────────────────────────────────────────────────┘
```

---

## 2. Data Model

### 2.1 AI Usage Tracking
```sql
CREATE TABLE ai_usage_logs (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    uuid CHAR(36) NOT NULL UNIQUE,

    -- Context
    tenant_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    workspace_id BIGINT UNSIGNED NULL,

    -- AI Request
    feature_type ENUM(
        'caption_generation',
        'hashtag_suggestion',
        'content_repurpose',
        'alt_text_generation',
        'tone_adjustment',
        'best_time_suggestion',
        'performance_prediction',
        'sentiment_analysis',
        'reply_suggestion',
        'content_moderation'
    ) NOT NULL,

    -- Input/Output
    input_tokens INT NOT NULL,
    output_tokens INT NOT NULL,
    input_data JSON NULL,
    output_data JSON NULL,

    -- Provider
    ai_provider ENUM('openai', 'anthropic', 'self_hosted') NOT NULL,
    model_used VARCHAR(50) NOT NULL,

    -- Performance
    latency_ms INT NOT NULL,
    status ENUM('success', 'failed', 'rate_limited') NOT NULL,
    error_message TEXT NULL,

    -- Cost (in smallest currency unit - paisa/cents)
    cost_tokens INT DEFAULT 0,

    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    INDEX idx_tenant (tenant_id, created_at),
    INDEX idx_feature (feature_type, created_at),
    INDEX idx_user (user_id, created_at)
);
```

### 2.2 AI Credits/Quotas
```sql
CREATE TABLE ai_quotas (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    tenant_id BIGINT UNSIGNED NOT NULL,

    -- Quota Info
    plan_type ENUM('starter', 'professional', 'business', 'enterprise') NOT NULL,
    monthly_token_limit INT NOT NULL,
    tokens_used INT DEFAULT 0,

    -- Feature-specific limits
    caption_generations_limit INT NULL,
    caption_generations_used INT DEFAULT 0,

    -- Period
    period_start DATE NOT NULL,
    period_end DATE NOT NULL,

    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    UNIQUE KEY unique_tenant_period (tenant_id, period_start),
    INDEX idx_period (period_start, period_end)
);
```

### 2.3 AI Templates
```sql
CREATE TABLE ai_prompt_templates (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    uuid CHAR(36) NOT NULL UNIQUE,

    -- Scope
    tenant_id BIGINT UNSIGNED NULL,
    is_system BOOLEAN DEFAULT FALSE,

    -- Template Info
    name VARCHAR(100) NOT NULL,
    description TEXT NULL,
    feature_type VARCHAR(50) NOT NULL,

    -- Prompt
    system_prompt TEXT NOT NULL,
    user_prompt_template TEXT NOT NULL,

    -- Settings
    temperature DECIMAL(2,1) DEFAULT 0.7,
    max_tokens INT DEFAULT 500,

    -- Status
    is_active BOOLEAN DEFAULT TRUE,

    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_tenant (tenant_id),
    INDEX idx_feature (feature_type)
);
```

---

## 3. Content Creation Features

### 3.1 Caption Generator
```php
<?php

namespace App\Services\AI\Content;

use App\Services\AI\AIProviderService;
use App\Models\AI\AIUsageLog;

class CaptionGeneratorService
{
    private AIProviderService $aiProvider;

    public function __construct(AIProviderService $aiProvider)
    {
        $this->aiProvider = $aiProvider;
    }

    public function generateCaption(array $params): array
    {
        $prompt = $this->buildPrompt($params);

        $response = $this->aiProvider->complete([
            'messages' => [
                [
                    'role' => 'system',
                    'content' => $this->getSystemPrompt($params['platform']),
                ],
                [
                    'role' => 'user',
                    'content' => $prompt,
                ],
            ],
            'temperature' => 0.7,
            'max_tokens' => $this->getMaxTokens($params['platform']),
        ]);

        // Log usage
        $this->logUsage($params, $response);

        return [
            'caption' => $response['content'],
            'variations' => $response['variations'] ?? [],
            'tokens_used' => $response['tokens_used'],
        ];
    }

    private function getSystemPrompt(string $platform): string
    {
        $platformLimits = [
            'linkedin' => 3000,
            'twitter' => 280,
            'instagram' => 2200,
            'facebook' => 63206,
            'tiktok' => 2200,
        ];

        $limit = $platformLimits[$platform] ?? 2000;

        return <<<PROMPT
You are a social media content expert. Generate engaging, platform-appropriate captions.

Platform: {$platform}
Character Limit: {$limit}

Guidelines:
- Match the platform's tone and style
- Include relevant emojis where appropriate
- Write compelling hooks that grab attention
- Include clear calls-to-action
- Optimize for engagement

Do NOT include hashtags in the caption - those will be added separately.
PROMPT;
    }

    private function buildPrompt(array $params): string
    {
        $prompt = "Generate a caption for the following content:\n\n";

        if (!empty($params['topic'])) {
            $prompt .= "Topic: {$params['topic']}\n";
        }

        if (!empty($params['key_points'])) {
            $prompt .= "Key Points: " . implode(', ', $params['key_points']) . "\n";
        }

        if (!empty($params['tone'])) {
            $prompt .= "Tone: {$params['tone']}\n";
        }

        if (!empty($params['target_audience'])) {
            $prompt .= "Target Audience: {$params['target_audience']}\n";
        }

        if (!empty($params['include_cta'])) {
            $prompt .= "Include CTA: {$params['include_cta']}\n";
        }

        if (!empty($params['brand_voice'])) {
            $prompt .= "Brand Voice: {$params['brand_voice']}\n";
        }

        $prompt .= "\nGenerate 3 caption variations.";

        return $prompt;
    }

    public function rephraseCaption(string $caption, string $style): string
    {
        $styles = [
            'professional' => 'Make it more professional and formal',
            'casual' => 'Make it more casual and conversational',
            'enthusiastic' => 'Make it more enthusiastic and energetic',
            'informative' => 'Make it more informative and educational',
            'humorous' => 'Add appropriate humor while keeping the message',
            'shorter' => 'Make it more concise without losing key points',
            'longer' => 'Expand with more details and context',
        ];

        $instruction = $styles[$style] ?? 'Improve the caption';

        $response = $this->aiProvider->complete([
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are a social media copywriter. Rephrase captions while preserving the core message.',
                ],
                [
                    'role' => 'user',
                    'content' => "Original caption:\n{$caption}\n\nInstruction: {$instruction}",
                ],
            ],
            'temperature' => 0.6,
            'max_tokens' => 1000,
        ]);

        return $response['content'];
    }
}
```

### 3.2 Hashtag Suggestions
```php
<?php

namespace App\Services\AI\Content;

class HashtagSuggestionService
{
    private AIProviderService $aiProvider;

    public function suggestHashtags(array $params): array
    {
        $response = $this->aiProvider->complete([
            'messages' => [
                [
                    'role' => 'system',
                    'content' => $this->getSystemPrompt(),
                ],
                [
                    'role' => 'user',
                    'content' => $this->buildPrompt($params),
                ],
            ],
            'temperature' => 0.5,
            'max_tokens' => 300,
        ]);

        return $this->parseHashtags($response['content'], $params['platform']);
    }

    private function getSystemPrompt(): string
    {
        return <<<PROMPT
You are a social media hashtag strategist. Suggest relevant, effective hashtags.

For each hashtag, provide:
1. The hashtag
2. Estimated reach (high/medium/low)
3. Competition level (high/medium/low)

Mix of:
- Popular hashtags (high reach, high competition)
- Niche hashtags (medium reach, low competition)
- Branded/unique hashtags if applicable

Return as JSON array.
PROMPT;
    }

    private function buildPrompt(array $params): string
    {
        $prompt = "Suggest hashtags for:\n\n";
        $prompt .= "Platform: {$params['platform']}\n";
        $prompt .= "Content: {$params['content']}\n";

        if (!empty($params['industry'])) {
            $prompt .= "Industry: {$params['industry']}\n";
        }

        if (!empty($params['location'])) {
            $prompt .= "Location: {$params['location']}\n";
        }

        $count = $params['count'] ?? 15;
        $prompt .= "\nSuggest {$count} hashtags.";

        return $prompt;
    }

    private function parseHashtags(string $content, string $platform): array
    {
        $hashtags = json_decode($content, true) ?? [];

        // Platform-specific limits
        $limits = [
            'instagram' => 30,
            'twitter' => 5,
            'linkedin' => 5,
            'tiktok' => 100,
            'facebook' => 30,
        ];

        $limit = $limits[$platform] ?? 30;

        return array_slice($hashtags, 0, $limit);
    }

    public function analyzeHashtagPerformance(array $hashtags, int $tenantId): array
    {
        // Get historical performance data
        $performance = \DB::table('post_analytics')
            ->join('posts', 'post_analytics.post_id', '=', 'posts.id')
            ->where('posts.tenant_id', $tenantId)
            ->whereJsonContains('posts.hashtags', $hashtags)
            ->select(
                \DB::raw('AVG(engagement_rate) as avg_engagement'),
                \DB::raw('AVG(reach) as avg_reach'),
                \DB::raw('COUNT(*) as usage_count')
            )
            ->first();

        return [
            'avg_engagement' => $performance->avg_engagement ?? 0,
            'avg_reach' => $performance->avg_reach ?? 0,
            'usage_count' => $performance->usage_count ?? 0,
        ];
    }
}
```

### 3.3 Content Repurposing
```php
<?php

namespace App\Services\AI\Content;

class ContentRepurposeService
{
    public function repurpose(array $params): array
    {
        $originalPlatform = $params['source_platform'];
        $targetPlatforms = $params['target_platforms'];
        $content = $params['content'];

        $results = [];

        foreach ($targetPlatforms as $platform) {
            $results[$platform] = $this->convertForPlatform($content, $originalPlatform, $platform);
        }

        return $results;
    }

    private function convertForPlatform(string $content, string $from, string $to): array
    {
        $platformSpecs = [
            'twitter' => [
                'max_length' => 280,
                'style' => 'concise, punchy, thread-friendly',
                'features' => 'Can use threads for longer content',
            ],
            'linkedin' => [
                'max_length' => 3000,
                'style' => 'professional, thought-leadership',
                'features' => 'Supports rich formatting, articles',
            ],
            'instagram' => [
                'max_length' => 2200,
                'style' => 'visual-first, storytelling',
                'features' => 'Caption for visual content',
            ],
            'facebook' => [
                'max_length' => 63206,
                'style' => 'conversational, community-focused',
                'features' => 'Supports various content types',
            ],
            'tiktok' => [
                'max_length' => 2200,
                'style' => 'trendy, Gen-Z friendly, entertaining',
                'features' => 'Video-first platform',
            ],
        ];

        $toSpec = $platformSpecs[$to];

        $response = $this->aiProvider->complete([
            'messages' => [
                [
                    'role' => 'system',
                    'content' => "You are a content strategist. Adapt content for different social media platforms while maintaining the core message.",
                ],
                [
                    'role' => 'user',
                    'content' => <<<PROMPT
Original content from {$from}:
{$content}

Convert this for {$to} platform:
- Maximum length: {$toSpec['max_length']} characters
- Style: {$toSpec['style']}
- Platform features: {$toSpec['features']}

Maintain the core message but optimize for the target platform's audience and format.
PROMPT,
                ],
            ],
            'temperature' => 0.7,
            'max_tokens' => 1000,
        ]);

        return [
            'content' => $response['content'],
            'platform' => $to,
            'character_count' => strlen($response['content']),
            'within_limit' => strlen($response['content']) <= $toSpec['max_length'],
        ];
    }
}
```

### 3.4 Image Alt Text Generator
```php
<?php

namespace App\Services\AI\Content;

class AltTextGeneratorService
{
    public function generateAltText(string $imageUrl, array $context = []): string
    {
        // Use vision-capable model
        $response = $this->aiProvider->complete([
            'model' => 'gpt-4-vision-preview',
            'messages' => [
                [
                    'role' => 'user',
                    'content' => [
                        [
                            'type' => 'text',
                            'text' => $this->buildPrompt($context),
                        ],
                        [
                            'type' => 'image_url',
                            'image_url' => [
                                'url' => $imageUrl,
                            ],
                        ],
                    ],
                ],
            ],
            'max_tokens' => 150,
        ]);

        return $response['content'];
    }

    private function buildPrompt(array $context): string
    {
        $prompt = "Generate an accessible alt text for this image. ";
        $prompt .= "The alt text should be concise (under 125 characters), descriptive, and useful for screen readers. ";

        if (!empty($context['brand'])) {
            $prompt .= "Brand context: {$context['brand']}. ";
        }

        if (!empty($context['post_topic'])) {
            $prompt .= "This image is for a post about: {$context['post_topic']}. ";
        }

        $prompt .= "Do NOT start with 'Image of' or 'Picture of'.";

        return $prompt;
    }
}
```

---

## 4. Scheduling Optimization

### 4.1 Best Time to Post
```php
<?php

namespace App\Services\AI\Scheduling;

class BestTimeService
{
    public function suggestBestTimes(int $socialAccountId, string $contentType = 'general'): array
    {
        // Get historical performance data
        $historicalData = $this->getHistoricalPerformance($socialAccountId);

        // Get audience activity data (if available from platform)
        $audienceData = $this->getAudienceActivity($socialAccountId);

        // Use AI to analyze patterns
        $analysis = $this->analyzeWithAI($historicalData, $audienceData, $contentType);

        return [
            'best_times' => $analysis['recommended_times'],
            'avoid_times' => $analysis['avoid_times'],
            'insights' => $analysis['insights'],
            'confidence_score' => $analysis['confidence'],
        ];
    }

    private function getHistoricalPerformance(int $socialAccountId): array
    {
        return \DB::table('posts')
            ->join('post_analytics', 'posts.id', '=', 'post_analytics.post_id')
            ->where('posts.social_account_id', $socialAccountId)
            ->where('posts.published_at', '>=', now()->subMonths(3))
            ->select(
                \DB::raw('DAYOFWEEK(posts.published_at) as day_of_week'),
                \DB::raw('HOUR(posts.published_at) as hour'),
                \DB::raw('AVG(post_analytics.engagement_rate) as avg_engagement'),
                \DB::raw('AVG(post_analytics.reach) as avg_reach'),
                \DB::raw('COUNT(*) as post_count')
            )
            ->groupBy('day_of_week', 'hour')
            ->get()
            ->toArray();
    }

    private function analyzeWithAI(array $historical, array $audience, string $contentType): array
    {
        $response = $this->aiProvider->complete([
            'messages' => [
                [
                    'role' => 'system',
                    'content' => <<<PROMPT
You are a social media analytics expert. Analyze posting time performance data and suggest optimal posting times.

Consider:
- Historical engagement patterns
- Audience activity windows
- Content type (promotional, educational, entertaining)
- Day of week patterns
- Time zone considerations

Return JSON with:
- recommended_times: array of {day, hour, reason, expected_engagement_lift}
- avoid_times: array of {day, hour, reason}
- insights: array of key observations
- confidence: 0-100 based on data quality
PROMPT,
                ],
                [
                    'role' => 'user',
                    'content' => json_encode([
                        'historical_performance' => $historical,
                        'audience_activity' => $audience,
                        'content_type' => $contentType,
                    ]),
                ],
            ],
            'temperature' => 0.3,
            'max_tokens' => 1000,
        ]);

        return json_decode($response['content'], true);
    }

    public function getOptimalSchedule(int $workspaceId, int $postsPerWeek): array
    {
        $socialAccounts = $this->getWorkspaceSocialAccounts($workspaceId);

        $schedule = [];

        foreach ($socialAccounts as $account) {
            $bestTimes = $this->suggestBestTimes($account->id);

            // Distribute posts across best times
            $schedule[$account->id] = $this->distributePosting(
                $bestTimes['best_times'],
                $postsPerWeek,
                $bestTimes['avoid_times']
            );
        }

        return $schedule;
    }
}
```

---

## 5. Analytics & Insights

### 5.1 Performance Prediction
```php
<?php

namespace App\Services\AI\Analytics;

class PerformancePredictionService
{
    public function predictPostPerformance(array $postData): array
    {
        // Get similar historical posts
        $similarPosts = $this->findSimilarPosts($postData);

        // AI analysis
        $prediction = $this->analyzeWithAI($postData, $similarPosts);

        return [
            'predicted_engagement_rate' => $prediction['engagement_rate'],
            'predicted_reach' => $prediction['reach'],
            'confidence' => $prediction['confidence'],
            'improvement_suggestions' => $prediction['suggestions'],
            'similar_posts_performance' => $similarPosts,
        ];
    }

    private function analyzeWithAI(array $postData, array $similarPosts): array
    {
        $response = $this->aiProvider->complete([
            'messages' => [
                [
                    'role' => 'system',
                    'content' => <<<PROMPT
You are a social media analytics expert. Predict post performance based on content analysis and historical data.

Analyze:
- Content quality and relevance
- Hashtag effectiveness
- Posting time optimization
- Visual appeal (if applicable)
- Call-to-action strength
- Similar post performance

Return JSON with predictions and improvement suggestions.
PROMPT,
                ],
                [
                    'role' => 'user',
                    'content' => json_encode([
                        'post' => $postData,
                        'historical_similar_posts' => $similarPosts,
                    ]),
                ],
            ],
            'temperature' => 0.3,
            'max_tokens' => 800,
        ]);

        return json_decode($response['content'], true);
    }
}
```

### 5.2 Sentiment Analysis
```php
<?php

namespace App\Services\AI\Analytics;

class SentimentAnalysisService
{
    public function analyzeComments(array $comments): array
    {
        $batchSize = 50;
        $results = [];

        foreach (array_chunk($comments, $batchSize) as $batch) {
            $batchResults = $this->analyzeBatch($batch);
            $results = array_merge($results, $batchResults);
        }

        return [
            'analyzed_comments' => $results,
            'summary' => $this->summarizeSentiment($results),
        ];
    }

    private function analyzeBatch(array $comments): array
    {
        $response = $this->aiProvider->complete([
            'messages' => [
                [
                    'role' => 'system',
                    'content' => <<<PROMPT
Analyze the sentiment of each comment. Return JSON array with:
- comment_id
- sentiment: positive/neutral/negative
- sentiment_score: -1 to 1
- emotion: joy/anger/sadness/surprise/fear/neutral
- requires_response: boolean (true if needs attention)
- priority: high/medium/low
PROMPT,
                ],
                [
                    'role' => 'user',
                    'content' => json_encode($comments),
                ],
            ],
            'temperature' => 0.2,
            'max_tokens' => 2000,
        ]);

        return json_decode($response['content'], true);
    }

    private function summarizeSentiment(array $results): array
    {
        $positive = collect($results)->where('sentiment', 'positive')->count();
        $neutral = collect($results)->where('sentiment', 'neutral')->count();
        $negative = collect($results)->where('sentiment', 'negative')->count();
        $total = count($results);

        return [
            'total_analyzed' => $total,
            'positive_percentage' => $total > 0 ? round(($positive / $total) * 100, 1) : 0,
            'neutral_percentage' => $total > 0 ? round(($neutral / $total) * 100, 1) : 0,
            'negative_percentage' => $total > 0 ? round(($negative / $total) * 100, 1) : 0,
            'requires_attention' => collect($results)->where('requires_response', true)->count(),
            'high_priority' => collect($results)->where('priority', 'high')->count(),
        ];
    }
}
```

---

## 6. Engagement Features

### 6.1 Reply Suggestions
```php
<?php

namespace App\Services\AI\Engagement;

class ReplySuggestionService
{
    public function suggestReplies(array $message, array $context = []): array
    {
        $response = $this->aiProvider->complete([
            'messages' => [
                [
                    'role' => 'system',
                    'content' => $this->getSystemPrompt($context),
                ],
                [
                    'role' => 'user',
                    'content' => $this->buildPrompt($message, $context),
                ],
            ],
            'temperature' => 0.7,
            'max_tokens' => 500,
        ]);

        return json_decode($response['content'], true);
    }

    private function getSystemPrompt(array $context): string
    {
        $brandVoice = $context['brand_voice'] ?? 'professional and friendly';

        return <<<PROMPT
You are a social media community manager. Generate reply suggestions that match the brand voice.

Brand Voice: {$brandVoice}

Guidelines:
- Be helpful and empathetic
- Address the specific concern or question
- Include call-to-action when appropriate
- Keep responses concise but complete
- Maintain professionalism even with negative comments

Return JSON array with 3 reply options, each with:
- reply: the suggested response
- tone: the tone used (friendly/formal/empathetic/informative)
- use_case: when to use this reply
PROMPT;
    }

    private function buildPrompt(array $message, array $context): string
    {
        $prompt = "Generate reply suggestions for this message:\n\n";
        $prompt .= "Platform: {$message['platform']}\n";
        $prompt .= "Message Type: {$message['type']}\n"; // comment, mention, DM
        $prompt .= "Content: {$message['content']}\n";

        if (!empty($message['sentiment'])) {
            $prompt .= "Sentiment: {$message['sentiment']}\n";
        }

        if (!empty($context['previous_interactions'])) {
            $prompt .= "Previous interactions: " . json_encode($context['previous_interactions']) . "\n";
        }

        return $prompt;
    }
}
```

### 6.2 Inbox Prioritization
```php
<?php

namespace App\Services\AI\Engagement;

class InboxPrioritizationService
{
    public function prioritizeInbox(array $items): array
    {
        $response = $this->aiProvider->complete([
            'messages' => [
                [
                    'role' => 'system',
                    'content' => <<<PROMPT
You are an inbox prioritization assistant. Score and categorize social media messages.

Scoring criteria:
- Urgency (complaint, question, opportunity)
- Sentiment (negative needs faster response)
- Engagement potential
- Influencer/VIP status
- Business opportunity signals

Return JSON array with each item having:
- id
- priority_score: 1-100
- priority_level: urgent/high/medium/low
- category: complaint/question/praise/opportunity/spam
- suggested_response_time: in hours
- key_signals: why this priority was assigned
PROMPT,
                ],
                [
                    'role' => 'user',
                    'content' => json_encode($items),
                ],
            ],
            'temperature' => 0.2,
            'max_tokens' => 2000,
        ]);

        return json_decode($response['content'], true);
    }
}
```

---

## 7. Automation Features

### 7.1 Content Moderation
```php
<?php

namespace App\Services\AI\Moderation;

class ContentModerationService
{
    public function moderateContent(string $content, string $type = 'user_generated'): array
    {
        $response = $this->aiProvider->complete([
            'messages' => [
                [
                    'role' => 'system',
                    'content' => <<<PROMPT
You are a content moderator. Analyze content for policy violations.

Check for:
- Spam indicators
- Hate speech or discrimination
- Violence or threats
- Adult/explicit content
- Misinformation indicators
- Self-harm content
- Personal information exposure
- Platform policy violations

Return JSON with:
- is_safe: boolean
- confidence: 0-100
- categories: array of detected issues
- severity: none/low/medium/high/critical
- action_recommended: approve/flag/reject/escalate
- explanation: brief reasoning
PROMPT,
                ],
                [
                    'role' => 'user',
                    'content' => "Content type: {$type}\n\nContent:\n{$content}",
                ],
            ],
            'temperature' => 0.1,
            'max_tokens' => 300,
        ]);

        return json_decode($response['content'], true);
    }

    public function autoModerateComments(int $postId): array
    {
        $comments = $this->getUnmoderatedComments($postId);

        $results = [];

        foreach ($comments as $comment) {
            $moderation = $this->moderateContent($comment->content, 'comment');

            if ($moderation['action_recommended'] === 'reject') {
                $this->hideComment($comment->id);
            } elseif ($moderation['action_recommended'] === 'flag') {
                $this->flagForReview($comment->id);
            }

            $results[] = [
                'comment_id' => $comment->id,
                'moderation' => $moderation,
            ];
        }

        return $results;
    }
}
```

---

## 8. API Endpoints

### 8.1 AI Feature Endpoints
```
# Content Generation
POST   /api/v1/ai/caption/generate
POST   /api/v1/ai/caption/rephrase
POST   /api/v1/ai/hashtags/suggest
POST   /api/v1/ai/content/repurpose
POST   /api/v1/ai/alt-text/generate

# Scheduling
GET    /api/v1/ai/scheduling/best-times/{social_account_id}
GET    /api/v1/ai/scheduling/optimal-schedule/{workspace_id}

# Analytics
POST   /api/v1/ai/analytics/predict-performance
POST   /api/v1/ai/analytics/sentiment
GET    /api/v1/ai/analytics/insights/{social_account_id}

# Engagement
POST   /api/v1/ai/engagement/suggest-reply
POST   /api/v1/ai/engagement/prioritize-inbox

# Moderation
POST   /api/v1/ai/moderation/check
POST   /api/v1/ai/moderation/batch

# Usage
GET    /api/v1/ai/usage
GET    /api/v1/ai/quota
```

---

## 9. Plan Limits

### 9.1 AI Feature Availability by Plan
```
┌─────────────────────────────────────────────────────────────────┐
│              AI FEATURES BY PLAN                                │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  Feature              │ Starter │ Pro  │ Business │ Enterprise │
│  ─────────────────────┼─────────┼──────┼──────────┼────────────│
│  Caption Generator    │  50/mo  │ 200  │   500    │  Unlimited │
│  Hashtag Suggestions  │  50/mo  │ 200  │   500    │  Unlimited │
│  Content Repurpose    │   -     │  50  │   200    │  Unlimited │
│  Alt Text Generator   │  50/mo  │ 200  │   500    │  Unlimited │
│  Best Time Suggestion │   -     │  ✓   │    ✓     │     ✓      │
│  Performance Predict  │   -     │  -   │    ✓     │     ✓      │
│  Sentiment Analysis   │   -     │  -   │    ✓     │     ✓      │
│  Reply Suggestions    │   -     │ 100  │   300    │  Unlimited │
│  Content Moderation   │   -     │  -   │    ✓     │     ✓      │
│  ─────────────────────┼─────────┼──────┼──────────┼────────────│
│  Monthly Token Limit  │  10K    │ 50K  │   200K   │  Unlimited │
└─────────────────────────────────────────────────────────────────┘
```

---

## 10. Frontend Components

### 10.1 AI Caption Generator UI
```vue
<template>
  <div class="ai-caption-generator">
    <div class="generator-form">
      <h3>AI Caption Generator</h3>

      <div class="form-group">
        <label>What's your post about?</label>
        <textarea
          v-model="topic"
          placeholder="Describe your post topic, key message, or paste your content..."
          rows="4"
        ></textarea>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label>Platform</label>
          <select v-model="platform">
            <option value="linkedin">LinkedIn</option>
            <option value="instagram">Instagram</option>
            <option value="twitter">Twitter/X</option>
            <option value="facebook">Facebook</option>
            <option value="tiktok">TikTok</option>
          </select>
        </div>

        <div class="form-group">
          <label>Tone</label>
          <select v-model="tone">
            <option value="professional">Professional</option>
            <option value="casual">Casual</option>
            <option value="enthusiastic">Enthusiastic</option>
            <option value="informative">Informative</option>
            <option value="humorous">Humorous</option>
          </select>
        </div>
      </div>

      <div class="form-group">
        <label>Include CTA</label>
        <input
          v-model="cta"
          type="text"
          placeholder="e.g., Visit our website, Book a demo..."
        />
      </div>

      <button
        @click="generateCaption"
        :disabled="generating || !topic"
        class="btn btn-primary"
      >
        <SparklesIcon v-if="!generating" />
        <LoadingSpinner v-else />
        {{ generating ? 'Generating...' : 'Generate Captions' }}
      </button>

      <div class="usage-info">
        {{ usageRemaining }} generations remaining this month
      </div>
    </div>

    <!-- Results -->
    <div v-if="captions.length" class="caption-results">
      <h4>Generated Captions</h4>

      <div
        v-for="(caption, index) in captions"
        :key="index"
        class="caption-card"
      >
        <div class="caption-content">{{ caption }}</div>

        <div class="caption-meta">
          <span class="char-count">{{ caption.length }} characters</span>
        </div>

        <div class="caption-actions">
          <button @click="useCaption(caption)" class="btn btn-sm btn-primary">
            Use This
          </button>
          <button @click="rephrase(caption)" class="btn btn-sm btn-secondary">
            Rephrase
          </button>
          <button @click="copyToClipboard(caption)" class="btn btn-sm btn-ghost">
            Copy
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
```

---

## 11. Revision History

| Version | Date | Author | Changes |
|---------|------|--------|---------|
| 1.0.0 | 2025-02-06 | System | Initial specification |
