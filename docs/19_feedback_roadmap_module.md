# Feedback & Roadmap Module Specification

## Document Information
- **Version**: 1.0.0
- **Created**: 2025-02-06
- **Module**: Feedback Collection & Product Roadmap
- **Owner**: Super Admin (Bizinso)

---

## 1. Overview

### 1.1 Purpose
Enable systematic collection of tenant feedback to build a data-driven product roadmap. This module provides:
- Feedback submission for tenants/users
- Voting system for feature prioritization
- Public roadmap visibility
- Release notes and changelog management

### 1.2 Feedback Lifecycle
```
┌─────────────────────────────────────────────────────────────────┐
│                    FEEDBACK LIFECYCLE                           │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  ┌──────────┐    ┌──────────┐    ┌──────────┐    ┌──────────┐  │
│  │  Submit  │ →  │  Review  │ →  │ Prioritize│ →  │ Roadmap  │  │
│  │ Feedback │    │  & Tag   │    │  & Plan   │    │  Publish │  │
│  └──────────┘    └──────────┘    └──────────┘    └──────────┘  │
│       ↓                                               ↓         │
│  ┌──────────┐                                   ┌──────────┐    │
│  │  Voting  │ ← ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ → │ Development│   │
│  │  System  │                                   │   Start   │    │
│  └──────────┘                                   └──────────┘    │
│                                                      ↓          │
│                                                ┌──────────┐     │
│                                                │  Release │     │
│                                                │   Notes  │     │
│                                                └──────────┘     │
└─────────────────────────────────────────────────────────────────┘
```

---

## 2. Data Model

### 2.1 Feedback Submissions
```sql
CREATE TABLE feedback (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    uuid CHAR(36) NOT NULL UNIQUE,

    -- Submitter
    tenant_id BIGINT UNSIGNED NULL,
    user_id BIGINT UNSIGNED NULL,
    submitter_email VARCHAR(255) NULL,
    submitter_name VARCHAR(100) NULL,

    -- Feedback Content
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,

    -- Classification
    feedback_type ENUM(
        'feature_request',
        'improvement',
        'bug_report',
        'integration_request',
        'ux_feedback',
        'documentation',
        'pricing_feedback',
        'other'
    ) NOT NULL,

    category ENUM(
        'publishing',
        'scheduling',
        'analytics',
        'inbox',
        'team_collaboration',
        'integrations',
        'mobile_app',
        'api',
        'billing',
        'onboarding',
        'general'
    ) NULL,

    -- Priority & Impact
    user_priority ENUM('nice_to_have', 'important', 'critical') DEFAULT 'important',
    business_impact TEXT NULL,

    -- Admin Classification
    admin_priority ENUM('low', 'medium', 'high', 'critical') NULL,
    effort_estimate ENUM('xs', 's', 'm', 'l', 'xl') NULL,

    -- Status
    status ENUM(
        'new',
        'under_review',
        'planned',
        'in_progress',
        'shipped',
        'declined',
        'duplicate',
        'archived'
    ) DEFAULT 'new',

    status_reason TEXT NULL,

    -- Voting
    vote_count INT DEFAULT 0,

    -- Linking
    roadmap_item_id BIGINT UNSIGNED NULL,
    duplicate_of_id BIGINT UNSIGNED NULL,

    -- Metadata
    source ENUM('portal', 'widget', 'email', 'support_ticket', 'internal') DEFAULT 'portal',
    browser_info JSON NULL,
    page_url VARCHAR(500) NULL,

    -- Timestamps
    reviewed_at TIMESTAMP NULL,
    reviewed_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE SET NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (duplicate_of_id) REFERENCES feedback(id) ON DELETE SET NULL,
    INDEX idx_status (status),
    INDEX idx_type (feedback_type),
    INDEX idx_votes (vote_count DESC),
    INDEX idx_tenant (tenant_id),
    FULLTEXT INDEX ft_search (title, description)
);
```

### 2.2 Feedback Votes
```sql
CREATE TABLE feedback_votes (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    feedback_id BIGINT UNSIGNED NOT NULL,

    -- Voter
    user_id BIGINT UNSIGNED NULL,
    tenant_id BIGINT UNSIGNED NULL,
    voter_email VARCHAR(255) NULL,
    session_id VARCHAR(100) NULL,

    -- Vote
    vote_type ENUM('upvote', 'downvote') DEFAULT 'upvote',

    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (feedback_id) REFERENCES feedback(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_vote (feedback_id, user_id),
    UNIQUE KEY unique_session_vote (feedback_id, session_id),
    INDEX idx_feedback (feedback_id)
);
```

### 2.3 Feedback Comments
```sql
CREATE TABLE feedback_comments (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    uuid CHAR(36) NOT NULL UNIQUE,
    feedback_id BIGINT UNSIGNED NOT NULL,

    -- Commenter
    user_id BIGINT UNSIGNED NULL,
    admin_id BIGINT UNSIGNED NULL,
    commenter_name VARCHAR(100) NULL,

    -- Content
    content TEXT NOT NULL,
    is_internal BOOLEAN DEFAULT FALSE,
    is_official_response BOOLEAN DEFAULT FALSE,

    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (feedback_id) REFERENCES feedback(id) ON DELETE CASCADE,
    INDEX idx_feedback (feedback_id)
);
```

### 2.4 Feedback Tags
```sql
CREATE TABLE feedback_tags (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL,
    slug VARCHAR(50) NOT NULL UNIQUE,
    color VARCHAR(7) DEFAULT '#6B7280',
    description TEXT NULL,
    usage_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_slug (slug)
);

CREATE TABLE feedback_tag_assignments (
    feedback_id BIGINT UNSIGNED NOT NULL,
    tag_id BIGINT UNSIGNED NOT NULL,

    PRIMARY KEY (feedback_id, tag_id),
    FOREIGN KEY (feedback_id) REFERENCES feedback(id) ON DELETE CASCADE,
    FOREIGN KEY (tag_id) REFERENCES feedback_tags(id) ON DELETE CASCADE
);
```

### 2.5 Roadmap Items
```sql
CREATE TABLE roadmap_items (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    uuid CHAR(36) NOT NULL UNIQUE,

    -- Content
    title VARCHAR(255) NOT NULL,
    description TEXT NULL,
    detailed_description LONGTEXT NULL,

    -- Classification
    category ENUM(
        'publishing',
        'scheduling',
        'analytics',
        'inbox',
        'team_collaboration',
        'integrations',
        'mobile_app',
        'api',
        'platform',
        'security',
        'performance'
    ) NOT NULL,

    -- Status
    status ENUM(
        'considering',
        'planned',
        'in_progress',
        'beta',
        'shipped',
        'cancelled'
    ) DEFAULT 'considering',

    -- Timeline
    quarter VARCHAR(10) NULL,
    target_date DATE NULL,
    shipped_date DATE NULL,

    -- Priority
    priority ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',

    -- Progress
    progress_percentage INT DEFAULT 0,

    -- Visibility
    is_public BOOLEAN DEFAULT TRUE,

    -- Metrics
    linked_feedback_count INT DEFAULT 0,
    total_votes INT DEFAULT 0,

    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_status (status),
    INDEX idx_quarter (quarter),
    INDEX idx_public (is_public, status)
);
```

### 2.6 Roadmap-Feedback Linking
```sql
CREATE TABLE roadmap_feedback_links (
    roadmap_item_id BIGINT UNSIGNED NOT NULL,
    feedback_id BIGINT UNSIGNED NOT NULL,

    PRIMARY KEY (roadmap_item_id, feedback_id),
    FOREIGN KEY (roadmap_item_id) REFERENCES roadmap_items(id) ON DELETE CASCADE,
    FOREIGN KEY (feedback_id) REFERENCES feedback(id) ON DELETE CASCADE
);
```

### 2.7 Release Notes
```sql
CREATE TABLE release_notes (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    uuid CHAR(36) NOT NULL UNIQUE,

    -- Version
    version VARCHAR(20) NOT NULL,
    version_name VARCHAR(100) NULL,

    -- Content
    title VARCHAR(255) NOT NULL,
    summary TEXT NULL,
    content LONGTEXT NOT NULL,
    content_format ENUM('markdown', 'html') DEFAULT 'markdown',

    -- Release Info
    release_type ENUM(
        'major',
        'minor',
        'patch',
        'hotfix',
        'beta',
        'alpha'
    ) NOT NULL,

    -- Status
    status ENUM('draft', 'scheduled', 'published') DEFAULT 'draft',

    -- Visibility
    is_public BOOLEAN DEFAULT TRUE,

    -- Timestamps
    scheduled_at TIMESTAMP NULL,
    published_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_version (version),
    INDEX idx_status (status),
    INDEX idx_published (published_at DESC)
);
```

### 2.8 Release Note Items
```sql
CREATE TABLE release_note_items (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    release_note_id BIGINT UNSIGNED NOT NULL,

    -- Content
    title VARCHAR(255) NOT NULL,
    description TEXT NULL,

    -- Type
    change_type ENUM(
        'new_feature',
        'improvement',
        'bug_fix',
        'security',
        'performance',
        'deprecation',
        'breaking_change'
    ) NOT NULL,

    -- Linking
    roadmap_item_id BIGINT UNSIGNED NULL,

    -- Order
    sort_order INT DEFAULT 0,

    FOREIGN KEY (release_note_id) REFERENCES release_notes(id) ON DELETE CASCADE,
    FOREIGN KEY (roadmap_item_id) REFERENCES roadmap_items(id) ON DELETE SET NULL,
    INDEX idx_release (release_note_id)
);
```

### 2.9 Changelog Subscriptions
```sql
CREATE TABLE changelog_subscriptions (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(255) NOT NULL,
    user_id BIGINT UNSIGNED NULL,
    tenant_id BIGINT UNSIGNED NULL,

    -- Preferences
    notify_major BOOLEAN DEFAULT TRUE,
    notify_minor BOOLEAN DEFAULT TRUE,
    notify_patch BOOLEAN DEFAULT FALSE,

    -- Status
    is_active BOOLEAN DEFAULT TRUE,
    unsubscribed_at TIMESTAMP NULL,

    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    UNIQUE KEY unique_email (email),
    INDEX idx_active (is_active)
);
```

---

## 3. Feedback Portal (Tenant/User View)

### 3.1 Feedback Submission Form
```vue
<template>
  <div class="feedback-portal">
    <!-- Header -->
    <header class="portal-header">
      <h1>Help us improve BizSocials</h1>
      <p>Your feedback shapes our product roadmap</p>
    </header>

    <!-- Tabs -->
    <nav class="portal-tabs">
      <button :class="{ active: activeTab === 'submit' }" @click="activeTab = 'submit'">
        Submit Feedback
      </button>
      <button :class="{ active: activeTab === 'browse' }" @click="activeTab = 'browse'">
        Browse Ideas ({{ feedbackCount }})
      </button>
      <button :class="{ active: activeTab === 'roadmap' }" @click="activeTab = 'roadmap'">
        Roadmap
      </button>
      <button :class="{ active: activeTab === 'changelog' }" @click="activeTab = 'changelog'">
        Changelog
      </button>
    </nav>

    <!-- Submit Form -->
    <section v-if="activeTab === 'submit'" class="feedback-form-section">
      <form @submit.prevent="submitFeedback">
        <!-- Feedback Type -->
        <div class="form-group">
          <label>What type of feedback is this?</label>
          <div class="type-selector">
            <TypeButton
              v-for="type in feedbackTypes"
              :key="type.value"
              :type="type"
              :selected="form.feedback_type === type.value"
              @click="form.feedback_type = type.value"
            />
          </div>
        </div>

        <!-- Title -->
        <div class="form-group">
          <label>Title</label>
          <input
            v-model="form.title"
            type="text"
            placeholder="Briefly describe your idea or issue"
            maxlength="255"
            required
          />
          <span class="char-count">{{ form.title.length }}/255</span>
        </div>

        <!-- Check for Duplicates -->
        <div v-if="similarFeedback.length" class="similar-feedback">
          <h4>Similar feedback already exists:</h4>
          <ul>
            <li v-for="item in similarFeedback" :key="item.id">
              <router-link :to="`/feedback/${item.uuid}`">
                {{ item.title }} ({{ item.vote_count }} votes)
              </router-link>
            </li>
          </ul>
        </div>

        <!-- Description -->
        <div class="form-group">
          <label>Description</label>
          <textarea
            v-model="form.description"
            placeholder="Provide more details about your feedback. What problem does this solve? How would it help you?"
            rows="6"
            required
          ></textarea>
        </div>

        <!-- Category -->
        <div class="form-group">
          <label>Category</label>
          <select v-model="form.category">
            <option value="">Select a category</option>
            <option v-for="cat in categories" :key="cat.value" :value="cat.value">
              {{ cat.label }}
            </option>
          </select>
        </div>

        <!-- Priority -->
        <div class="form-group">
          <label>How important is this to you?</label>
          <div class="priority-selector">
            <label class="priority-option">
              <input type="radio" v-model="form.user_priority" value="nice_to_have" />
              <span>Nice to have</span>
            </label>
            <label class="priority-option">
              <input type="radio" v-model="form.user_priority" value="important" />
              <span>Important</span>
            </label>
            <label class="priority-option">
              <input type="radio" v-model="form.user_priority" value="critical" />
              <span>Critical for my workflow</span>
            </label>
          </div>
        </div>

        <!-- Business Impact -->
        <div class="form-group">
          <label>How would this impact your business? (Optional)</label>
          <textarea
            v-model="form.business_impact"
            placeholder="Help us understand the business value..."
            rows="3"
          ></textarea>
        </div>

        <!-- Submit -->
        <button type="submit" class="btn btn-primary" :disabled="submitting">
          {{ submitting ? 'Submitting...' : 'Submit Feedback' }}
        </button>
      </form>
    </section>
  </div>
</template>
```

### 3.2 Browse & Vote Interface
```vue
<template>
  <section class="feedback-browse">
    <!-- Filters -->
    <div class="browse-filters">
      <div class="search-box">
        <input
          v-model="filters.search"
          type="search"
          placeholder="Search feedback..."
        />
      </div>

      <div class="filter-dropdowns">
        <select v-model="filters.type">
          <option value="">All Types</option>
          <option value="feature_request">Feature Requests</option>
          <option value="improvement">Improvements</option>
          <option value="bug_report">Bug Reports</option>
        </select>

        <select v-model="filters.category">
          <option value="">All Categories</option>
          <option v-for="cat in categories" :key="cat.value" :value="cat.value">
            {{ cat.label }}
          </option>
        </select>

        <select v-model="filters.status">
          <option value="">All Statuses</option>
          <option value="new">New</option>
          <option value="under_review">Under Review</option>
          <option value="planned">Planned</option>
          <option value="in_progress">In Progress</option>
          <option value="shipped">Shipped</option>
        </select>

        <select v-model="filters.sort">
          <option value="votes">Most Votes</option>
          <option value="recent">Most Recent</option>
          <option value="trending">Trending</option>
        </select>
      </div>
    </div>

    <!-- Feedback List -->
    <div class="feedback-list">
      <FeedbackCard
        v-for="item in feedbackItems"
        :key="item.id"
        :feedback="item"
        @vote="handleVote"
      />
    </div>

    <!-- Pagination -->
    <Pagination
      :current-page="currentPage"
      :total-pages="totalPages"
      @page-change="loadPage"
    />
  </section>
</template>
```

### 3.3 Feedback Card Component
```vue
<template>
  <div class="feedback-card" :class="{ 'has-voted': hasVoted }">
    <!-- Vote Button -->
    <div class="vote-section">
      <button
        class="vote-button"
        :class="{ voted: hasVoted }"
        @click="$emit('vote', feedback.id)"
        :disabled="hasVoted"
      >
        <ChevronUpIcon />
        <span class="vote-count">{{ feedback.vote_count }}</span>
      </button>
    </div>

    <!-- Content -->
    <div class="card-content">
      <div class="card-header">
        <router-link :to="`/feedback/${feedback.uuid}`" class="card-title">
          {{ feedback.title }}
        </router-link>
        <div class="card-meta">
          <span class="feedback-type" :class="feedback.feedback_type">
            {{ formatType(feedback.feedback_type) }}
          </span>
          <span class="feedback-status" :class="feedback.status">
            {{ formatStatus(feedback.status) }}
          </span>
        </div>
      </div>

      <p class="card-description">{{ truncate(feedback.description, 150) }}</p>

      <div class="card-footer">
        <span class="submitter">
          {{ feedback.submitter_name || 'Anonymous' }}
        </span>
        <span class="timestamp">{{ formatDate(feedback.created_at) }}</span>
        <span class="comments">
          <CommentIcon /> {{ feedback.comment_count }} comments
        </span>
      </div>
    </div>
  </div>
</template>
```

---

## 4. Public Roadmap

### 4.1 Roadmap View
```vue
<template>
  <div class="public-roadmap">
    <header class="roadmap-header">
      <h1>Product Roadmap</h1>
      <p>See what we're working on and what's coming next</p>
    </header>

    <!-- View Toggle -->
    <div class="view-toggle">
      <button :class="{ active: view === 'board' }" @click="view = 'board'">
        Board View
      </button>
      <button :class="{ active: view === 'timeline' }" @click="view = 'timeline'">
        Timeline View
      </button>
    </div>

    <!-- Board View (Kanban) -->
    <div v-if="view === 'board'" class="roadmap-board">
      <div class="board-column" v-for="column in columns" :key="column.status">
        <div class="column-header">
          <h3>{{ column.title }}</h3>
          <span class="count">{{ column.items.length }}</span>
        </div>
        <div class="column-items">
          <RoadmapCard
            v-for="item in column.items"
            :key="item.id"
            :item="item"
          />
        </div>
      </div>
    </div>

    <!-- Timeline View -->
    <div v-else class="roadmap-timeline">
      <div class="timeline-quarter" v-for="quarter in quarters" :key="quarter.id">
        <h3 class="quarter-title">{{ quarter.label }}</h3>
        <div class="quarter-items">
          <RoadmapCard
            v-for="item in quarter.items"
            :key="item.id"
            :item="item"
            layout="horizontal"
          />
        </div>
      </div>
    </div>
  </div>
</template>
```

### 4.2 Roadmap Card
```vue
<template>
  <div class="roadmap-card" @click="openDetail(item)">
    <div class="card-category">
      <span :style="{ backgroundColor: categoryColor }">
        {{ item.category }}
      </span>
    </div>

    <h4 class="card-title">{{ item.title }}</h4>
    <p class="card-description">{{ item.description }}</p>

    <!-- Progress Bar (if in progress) -->
    <div v-if="item.status === 'in_progress'" class="progress-bar">
      <div class="progress-fill" :style="{ width: `${item.progress_percentage}%` }"></div>
      <span class="progress-text">{{ item.progress_percentage }}%</span>
    </div>

    <!-- Linked Feedback -->
    <div class="linked-feedback">
      <span>{{ item.linked_feedback_count }} requests</span>
      <span>{{ item.total_votes }} votes</span>
    </div>

    <!-- Target Date -->
    <div v-if="item.target_date" class="target-date">
      Target: {{ formatDate(item.target_date) }}
    </div>
  </div>
</template>
```

---

## 5. Super Admin Management

### 5.1 Feedback Management Dashboard
```
┌─────────────────────────────────────────────────────────────────┐
│                 FEEDBACK MANAGEMENT                             │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  OVERVIEW                                                       │
│  ┌──────────────┬──────────────┬──────────────┬──────────────┐  │
│  │  New: 45     │  Review: 23  │  Planned: 67 │  Shipped: 234│  │
│  └──────────────┴──────────────┴──────────────┴──────────────┘  │
│                                                                 │
│  NEEDS ATTENTION                                                │
│  ┌─────────────────────────────────────────────────────────────┐│
│  │ ⚠️ 12 high-vote items pending review (>50 votes)            ││
│  │ ⚠️ 5 items marked critical by enterprise tenants            ││
│  │ ⚠️ 3 items older than 30 days without status update         ││
│  └─────────────────────────────────────────────────────────────┘│
│                                                                 │
│  FEEDBACK LIST                                                  │
│  ┌─────────────────────────────────────────────────────────────┐│
│  │ □ [123] API bulk scheduling endpoints (Feature Request)     ││
│  │   Votes: 89 | Tenant: Acme Corp (Enterprise) | 3 days ago   ││
│  │   Status: NEW | Priority: - | Effort: -                     ││
│  │                                                             ││
│  │ □ [122] Dark mode for mobile app (Improvement)              ││
│  │   Votes: 67 | Tenant: TechStart (SMB) | 5 days ago          ││
│  │   Status: UNDER_REVIEW | Priority: Medium | Effort: M       ││
│  └─────────────────────────────────────────────────────────────┘│
│                                                                 │
│  BULK ACTIONS: [Review] [Set Priority] [Link to Roadmap] [Tag] │
└─────────────────────────────────────────────────────────────────┘
```

### 5.2 Feedback Review Workflow
```php
<?php

namespace App\Services\Feedback;

use App\Models\Feedback\Feedback;
use App\Models\Feedback\RoadmapItem;
use App\Events\Feedback\FeedbackStatusChanged;
use Illuminate\Support\Facades\DB;

class FeedbackManagementService
{
    public function reviewFeedback(Feedback $feedback, array $data): Feedback
    {
        return DB::transaction(function () use ($feedback, $data) {
            $oldStatus = $feedback->status;

            $feedback->update([
                'status' => $data['status'],
                'status_reason' => $data['status_reason'] ?? null,
                'admin_priority' => $data['admin_priority'] ?? null,
                'effort_estimate' => $data['effort_estimate'] ?? null,
                'reviewed_at' => now(),
                'reviewed_by' => auth()->id(),
            ]);

            // Handle duplicate marking
            if ($data['status'] === 'duplicate' && isset($data['duplicate_of_id'])) {
                $feedback->duplicate_of_id = $data['duplicate_of_id'];
                $feedback->save();

                // Transfer votes to original
                $this->transferVotes($feedback, $data['duplicate_of_id']);
            }

            // Link to roadmap item if planned
            if ($data['status'] === 'planned' && isset($data['roadmap_item_id'])) {
                $this->linkToRoadmap($feedback, $data['roadmap_item_id']);
            }

            // Notify submitter of status change
            if ($oldStatus !== $data['status']) {
                event(new FeedbackStatusChanged($feedback, $oldStatus));
            }

            return $feedback->fresh();
        });
    }

    public function bulkUpdateStatus(array $feedbackIds, string $status, ?string $reason = null): int
    {
        return Feedback::whereIn('id', $feedbackIds)
            ->update([
                'status' => $status,
                'status_reason' => $reason,
                'reviewed_at' => now(),
                'reviewed_by' => auth()->id(),
            ]);
    }

    public function linkToRoadmap(Feedback $feedback, int $roadmapItemId): void
    {
        $feedback->roadmap_item_id = $roadmapItemId;
        $feedback->status = 'planned';
        $feedback->save();

        // Update roadmap item counts
        $roadmapItem = RoadmapItem::find($roadmapItemId);
        $roadmapItem->linked_feedback_count = $roadmapItem->linkedFeedback()->count();
        $roadmapItem->total_votes = $roadmapItem->linkedFeedback()->sum('vote_count');
        $roadmapItem->save();
    }

    public function createRoadmapItemFromFeedback(Feedback $feedback, array $data): RoadmapItem
    {
        return DB::transaction(function () use ($feedback, $data) {
            $roadmapItem = RoadmapItem::create([
                'uuid' => \Str::uuid(),
                'title' => $data['title'] ?? $feedback->title,
                'description' => $data['description'] ?? $feedback->description,
                'category' => $data['category'] ?? $feedback->category,
                'status' => 'considering',
                'priority' => $data['priority'] ?? 'medium',
                'quarter' => $data['quarter'] ?? null,
                'target_date' => $data['target_date'] ?? null,
                'is_public' => $data['is_public'] ?? true,
            ]);

            $this->linkToRoadmap($feedback, $roadmapItem->id);

            return $roadmapItem;
        });
    }

    private function transferVotes(Feedback $duplicate, int $originalId): void
    {
        $original = Feedback::find($originalId);
        $original->increment('vote_count', $duplicate->vote_count);
    }
}
```

### 5.3 Roadmap Management
```php
<?php

namespace App\Services\Feedback;

use App\Models\Feedback\RoadmapItem;
use App\Models\Feedback\ReleaseNote;
use App\Events\Roadmap\RoadmapItemShipped;
use Illuminate\Support\Facades\DB;

class RoadmapManagementService
{
    public function createRoadmapItem(array $data): RoadmapItem
    {
        return RoadmapItem::create([
            'uuid' => \Str::uuid(),
            'title' => $data['title'],
            'description' => $data['description'],
            'detailed_description' => $data['detailed_description'] ?? null,
            'category' => $data['category'],
            'status' => $data['status'] ?? 'considering',
            'priority' => $data['priority'] ?? 'medium',
            'quarter' => $data['quarter'] ?? null,
            'target_date' => $data['target_date'] ?? null,
            'is_public' => $data['is_public'] ?? true,
        ]);
    }

    public function updateProgress(RoadmapItem $item, int $percentage): RoadmapItem
    {
        $item->update([
            'progress_percentage' => min(100, max(0, $percentage)),
            'status' => $percentage >= 100 ? 'shipped' : 'in_progress',
        ]);

        if ($percentage >= 100) {
            $item->shipped_date = now();
            $item->save();
        }

        return $item;
    }

    public function markAsShipped(RoadmapItem $item, ?int $releaseNoteId = null): RoadmapItem
    {
        return DB::transaction(function () use ($item, $releaseNoteId) {
            $item->update([
                'status' => 'shipped',
                'progress_percentage' => 100,
                'shipped_date' => now(),
            ]);

            // Update linked feedback items
            $item->linkedFeedback()->update(['status' => 'shipped']);

            // Notify voters
            event(new RoadmapItemShipped($item));

            return $item;
        });
    }

    public function getPublicRoadmap(): array
    {
        $items = RoadmapItem::where('is_public', true)
            ->whereIn('status', ['considering', 'planned', 'in_progress', 'beta', 'shipped'])
            ->orderByRaw("FIELD(status, 'in_progress', 'beta', 'planned', 'considering', 'shipped')")
            ->orderByDesc('total_votes')
            ->get();

        return [
            'considering' => $items->where('status', 'considering')->values(),
            'planned' => $items->where('status', 'planned')->values(),
            'in_progress' => $items->whereIn('status', ['in_progress', 'beta'])->values(),
            'shipped' => $items->where('status', 'shipped')
                ->where('shipped_date', '>=', now()->subMonths(3))
                ->values(),
        ];
    }
}
```

---

## 6. Release Notes & Changelog

### 6.1 Release Note Creation
```php
<?php

namespace App\Services\Feedback;

use App\Models\Feedback\ReleaseNote;
use App\Models\Feedback\ReleaseNoteItem;
use App\Jobs\Notifications\SendChangelogNotifications;
use Illuminate\Support\Facades\DB;

class ReleaseNoteService
{
    public function createReleaseNote(array $data): ReleaseNote
    {
        return DB::transaction(function () use ($data) {
            $releaseNote = ReleaseNote::create([
                'uuid' => \Str::uuid(),
                'version' => $data['version'],
                'version_name' => $data['version_name'] ?? null,
                'title' => $data['title'],
                'summary' => $data['summary'] ?? null,
                'content' => $data['content'],
                'content_format' => $data['content_format'] ?? 'markdown',
                'release_type' => $data['release_type'],
                'status' => 'draft',
                'is_public' => $data['is_public'] ?? true,
            ]);

            // Add items
            if (!empty($data['items'])) {
                foreach ($data['items'] as $index => $item) {
                    ReleaseNoteItem::create([
                        'release_note_id' => $releaseNote->id,
                        'title' => $item['title'],
                        'description' => $item['description'] ?? null,
                        'change_type' => $item['change_type'],
                        'roadmap_item_id' => $item['roadmap_item_id'] ?? null,
                        'sort_order' => $index,
                    ]);
                }
            }

            return $releaseNote;
        });
    }

    public function publishReleaseNote(ReleaseNote $releaseNote): ReleaseNote
    {
        $releaseNote->update([
            'status' => 'published',
            'published_at' => now(),
        ]);

        // Mark linked roadmap items as shipped
        foreach ($releaseNote->items as $item) {
            if ($item->roadmapItem) {
                $item->roadmapItem->update([
                    'status' => 'shipped',
                    'shipped_date' => now(),
                ]);
            }
        }

        // Send notifications to subscribers
        dispatch(new SendChangelogNotifications($releaseNote));

        return $releaseNote;
    }

    public function scheduleReleaseNote(ReleaseNote $releaseNote, \DateTime $scheduledAt): ReleaseNote
    {
        $releaseNote->update([
            'status' => 'scheduled',
            'scheduled_at' => $scheduledAt,
        ]);

        return $releaseNote;
    }
}
```

### 6.2 Changelog Page
```vue
<template>
  <div class="changelog-page">
    <header class="changelog-header">
      <h1>Changelog</h1>
      <p>Stay up to date with the latest updates and improvements</p>

      <div class="subscribe-cta">
        <input
          v-model="email"
          type="email"
          placeholder="Enter your email"
        />
        <button @click="subscribe">Subscribe to Updates</button>
      </div>
    </header>

    <!-- Filter by Type -->
    <div class="changelog-filters">
      <button
        v-for="type in releaseTypes"
        :key="type.value"
        :class="{ active: filters.type === type.value }"
        @click="filters.type = type.value"
      >
        {{ type.label }}
      </button>
    </div>

    <!-- Release Notes -->
    <div class="release-notes">
      <article
        v-for="release in releases"
        :key="release.id"
        class="release-note"
        :id="`v${release.version}`"
      >
        <header class="release-header">
          <div class="release-version">
            <span class="version-badge" :class="release.release_type">
              {{ release.version }}
            </span>
            <span v-if="release.version_name" class="version-name">
              {{ release.version_name }}
            </span>
          </div>
          <time :datetime="release.published_at">
            {{ formatDate(release.published_at) }}
          </time>
        </header>

        <h2>{{ release.title }}</h2>

        <div v-if="release.summary" class="release-summary">
          {{ release.summary }}
        </div>

        <!-- Grouped Items -->
        <div class="release-items">
          <div
            v-for="(items, type) in groupedItems(release.items)"
            :key="type"
            class="item-group"
          >
            <h3>
              <component :is="getIcon(type)" />
              {{ formatChangeType(type) }}
            </h3>
            <ul>
              <li v-for="item in items" :key="item.id">
                <strong>{{ item.title }}</strong>
                <p v-if="item.description">{{ item.description }}</p>
              </li>
            </ul>
          </div>
        </div>

        <!-- Full Content -->
        <div
          v-if="release.content"
          class="release-content"
          v-html="renderMarkdown(release.content)"
        ></div>
      </article>
    </div>

    <!-- Load More -->
    <button
      v-if="hasMore"
      @click="loadMore"
      class="btn btn-secondary"
    >
      Load Older Releases
    </button>
  </div>
</template>
```

---

## 7. Feedback Widget

### 7.1 Embeddable Widget
```typescript
// Feedback Widget SDK
interface FeedbackWidgetConfig {
  position: 'bottom-right' | 'bottom-left' | 'right' | 'left';
  primaryColor: string;
  textColor: string;
  triggerText: string;
  showBranding: boolean;
}

class BizSocialsFeedbackWidget {
  private config: FeedbackWidgetConfig;
  private isOpen: boolean = false;

  constructor(config: Partial<FeedbackWidgetConfig> = {}) {
    this.config = {
      position: 'bottom-right',
      primaryColor: '#2563EB',
      textColor: '#FFFFFF',
      triggerText: 'Feedback',
      showBranding: true,
      ...config,
    };

    this.init();
  }

  private init(): void {
    this.injectStyles();
    this.createWidget();
    this.attachEventListeners();
  }

  private createWidget(): void {
    const widget = document.createElement('div');
    widget.id = 'bsf-widget';
    widget.innerHTML = `
      <button id="bsf-trigger" style="background: ${this.config.primaryColor}">
        <svg><!-- Feedback icon --></svg>
        <span>${this.config.triggerText}</span>
      </button>

      <div id="bsf-panel" class="bsf-hidden">
        <div class="bsf-header">
          <h3>Send Feedback</h3>
          <button id="bsf-close">&times;</button>
        </div>

        <form id="bsf-form">
          <div class="bsf-field">
            <label>Type</label>
            <select name="feedback_type" required>
              <option value="feature_request">Feature Request</option>
              <option value="bug_report">Bug Report</option>
              <option value="improvement">Improvement</option>
              <option value="other">Other</option>
            </select>
          </div>

          <div class="bsf-field">
            <label>Title</label>
            <input type="text" name="title" required placeholder="Brief summary" />
          </div>

          <div class="bsf-field">
            <label>Description</label>
            <textarea name="description" required rows="4" placeholder="Tell us more..."></textarea>
          </div>

          <div class="bsf-field">
            <label>
              <input type="checkbox" name="include_screenshot" />
              Include screenshot
            </label>
          </div>

          <button type="submit">Submit Feedback</button>
        </form>

        ${this.config.showBranding ? '<div class="bsf-branding">Powered by BizSocials</div>' : ''}
      </div>
    `;

    document.body.appendChild(widget);
  }

  public open(): void {
    this.isOpen = true;
    document.getElementById('bsf-panel')?.classList.remove('bsf-hidden');
  }

  public close(): void {
    this.isOpen = false;
    document.getElementById('bsf-panel')?.classList.add('bsf-hidden');
  }

  public async submit(data: any): Promise<void> {
    const response = await fetch('/api/v1/feedback/widget', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        ...data,
        source: 'widget',
        page_url: window.location.href,
        browser_info: this.getBrowserInfo(),
      }),
    });

    if (response.ok) {
      this.showSuccess();
    }
  }

  private getBrowserInfo(): object {
    return {
      userAgent: navigator.userAgent,
      language: navigator.language,
      platform: navigator.platform,
      screenWidth: window.screen.width,
      screenHeight: window.screen.height,
    };
  }
}

// Initialize
window.BizSocialsFeedback = new BizSocialsFeedbackWidget();
```

---

## 8. API Endpoints

### 8.1 Public/Tenant Endpoints
```
# Feedback
GET    /api/v1/feedback
POST   /api/v1/feedback
GET    /api/v1/feedback/{uuid}
POST   /api/v1/feedback/{uuid}/vote
POST   /api/v1/feedback/{uuid}/comments
GET    /api/v1/feedback/search?q={query}
GET    /api/v1/feedback/my-submissions

# Roadmap
GET    /api/v1/roadmap
GET    /api/v1/roadmap/{uuid}

# Changelog
GET    /api/v1/changelog
GET    /api/v1/changelog/{version}
POST   /api/v1/changelog/subscribe
DELETE /api/v1/changelog/unsubscribe

# Widget
POST   /api/v1/feedback/widget
```

### 8.2 Super Admin Endpoints
```
# Feedback Management
GET    /api/v1/admin/feedback
GET    /api/v1/admin/feedback/{id}
PUT    /api/v1/admin/feedback/{id}
DELETE /api/v1/admin/feedback/{id}
POST   /api/v1/admin/feedback/{id}/review
POST   /api/v1/admin/feedback/{id}/link-roadmap
POST   /api/v1/admin/feedback/bulk-update
POST   /api/v1/admin/feedback/{id}/merge

# Tags
GET    /api/v1/admin/feedback/tags
POST   /api/v1/admin/feedback/tags
PUT    /api/v1/admin/feedback/tags/{id}
DELETE /api/v1/admin/feedback/tags/{id}

# Roadmap Management
GET    /api/v1/admin/roadmap
POST   /api/v1/admin/roadmap
PUT    /api/v1/admin/roadmap/{id}
DELETE /api/v1/admin/roadmap/{id}
PUT    /api/v1/admin/roadmap/{id}/progress
POST   /api/v1/admin/roadmap/{id}/ship

# Release Notes
GET    /api/v1/admin/releases
POST   /api/v1/admin/releases
PUT    /api/v1/admin/releases/{id}
DELETE /api/v1/admin/releases/{id}
POST   /api/v1/admin/releases/{id}/publish
POST   /api/v1/admin/releases/{id}/schedule

# Analytics
GET    /api/v1/admin/feedback/analytics
GET    /api/v1/admin/feedback/trends
```

---

## 9. Notification System

### 9.1 Feedback Notifications
```php
<?php

namespace App\Notifications\Feedback;

use App\Models\Feedback\Feedback;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class FeedbackStatusUpdated extends Notification
{
    public function __construct(
        private Feedback $feedback,
        private string $oldStatus,
        private string $newStatus
    ) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        $statusMessages = [
            'under_review' => 'is now being reviewed by our team',
            'planned' => 'has been added to our roadmap',
            'in_progress' => 'is now being developed',
            'shipped' => 'has been shipped! 🎉',
            'declined' => 'has been declined',
        ];

        $message = $statusMessages[$this->newStatus] ?? 'status has been updated';

        return (new MailMessage)
            ->subject("Your feedback {$message}")
            ->greeting("Hi {$notifiable->name}!")
            ->line("Great news! Your feedback \"{$this->feedback->title}\" {$message}.")
            ->when($this->feedback->status_reason, function ($mail) {
                $mail->line("Note from our team: {$this->feedback->status_reason}");
            })
            ->action('View Details', url("/feedback/{$this->feedback->uuid}"))
            ->line('Thank you for helping us improve BizSocials!');
    }
}
```

---

## 10. Analytics & Reporting

### 10.1 Feedback Analytics Dashboard
```
┌─────────────────────────────────────────────────────────────────┐
│               FEEDBACK ANALYTICS                                │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  SUBMISSION TRENDS (Last 30 days)                               │
│  ┌─────────────────────────────────────────────────────────────┐│
│  │     ▄▄  ▄▄▄    ▄▄▄▄▄▄▄   ▄▄▄▄▄▄▄▄▄▄▄   ▄▄▄                 ││
│  │  ▄▄▄██▄▄███▄▄▄▄████████▄▄█████████████▄████▄▄              ││
│  │  Total: 234 | Avg/Day: 7.8 | Peak: 15                       ││
│  └─────────────────────────────────────────────────────────────┘│
│                                                                 │
│  BY TYPE                       BY CATEGORY                      │
│  ┌────────────────────────┐   ┌────────────────────────────┐   │
│  │ Feature Request  58%   │   │ Publishing       32%       │   │
│  │ Improvement      25%   │   │ Analytics        21%       │   │
│  │ Bug Report       12%   │   │ Integrations     18%       │   │
│  │ Other             5%   │   │ Mobile App       15%       │   │
│  └────────────────────────┘   │ Other            14%       │   │
│                               └────────────────────────────┘   │
│                                                                 │
│  RESPONSE METRICS                                               │
│  ┌─────────────────────────────────────────────────────────────┐│
│  │ Avg Time to Review: 2.3 days                                ││
│  │ Avg Time to Plan: 8.5 days                                  ││
│  │ Avg Time to Ship: 45 days                                   ││
│  │ Feedback to Feature Rate: 12%                               ││
│  └─────────────────────────────────────────────────────────────┘│
│                                                                 │
│  TOP VOTED (Not Yet Planned)                                    │
│  ┌─────────────────────────────────────────────────────────────┐│
│  │ 1. Bulk video scheduling (156 votes)                        ││
│  │ 2. AI caption generator (134 votes)                         ││
│  │ 3. Custom analytics dashboards (98 votes)                   ││
│  │ 4. Slack integration (87 votes)                             ││
│  │ 5. Story scheduling for Instagram (76 votes)                ││
│  └─────────────────────────────────────────────────────────────┘│
└─────────────────────────────────────────────────────────────────┘
```

---

## 11. Revision History

| Version | Date | Author | Changes |
|---------|------|--------|---------|
| 1.0.0 | 2025-02-06 | System | Initial specification |
