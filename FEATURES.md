# Medicine-AI Feature Documentation

This document describes the high-value features implemented in the Medicine-AI system, explaining how each feature works and the business value it delivers.

---

## Table of Contents

1. [Remote Therapeutic Monitoring (RTM)](#1-remote-therapeutic-monitoring-rtm)
2. [AI-Powered Waitlist Filler](#2-ai-powered-waitlist-filler)
3. [Google Review AI Responses](#3-google-review-ai-responses)
4. [Provider Compensation Management](#4-provider-compensation-management)
5. [RCM Services (Revenue Cycle Management)](#5-rcm-services-revenue-cycle-management)

---

## 1. Remote Therapeutic Monitoring (RTM)

### Overview

Remote Therapeutic Monitoring (RTM) enables healthcare providers to monitor patient progress between visits using symptom tracking, device data integration, and automated alerting for clinical deterioration.

### How It Works

#### Core Components

**RtmSession Model** (`app/Models/RtmSession.php`)
- Represents an active monitoring session between a doctor and patient
- Tracks session type: `initial`, `follow_up`, or `monitoring`
- Manages status lifecycle: `active` → `paused` → `completed`/`discharged`
- Stores monitoring parameters including custom threshold configurations
- Computes `days_remaining` for active monitoring periods

**RtmMetric Model** (`app/Models/RtmMetric.php`)
- Records individual metric readings (pain_level, function_score, adherence, etc.)
- Auto-calculates trend (`increasing`, `decreasing`, `stable`, `new`) by comparing to previous reading
- Caches trend value per instance to avoid repeated queries

**RtmAlert Model** (`app/Models/RtmAlert.php`)
- Created automatically when metric values breach configured thresholds
- Severity levels: `low`, `medium`, `high`, `critical`
- Status workflow: `active` → `acknowledged` → `resolved`

#### API Endpoints (`app/Http/Controllers/Api/RtmController.php`)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/rtm/sessions` | List doctor's RTM sessions |
| POST | `/api/rtm/sessions` | Create new monitoring session |
| GET | `/api/rtm/sessions/{id}` | Get session details with metrics summary |
| PATCH | `/api/rtm/sessions/{id}/status` | Update session status (pause/resume/complete/discharge) |
| POST | `/api/rtm/metrics` | Record a metric reading |
| GET | `/api/rtm/sessions/{id}/metrics` | Get session metrics with filtering |
| GET | `/api/rtm/alerts` | List alerts for doctor |
| POST | `/api/rtm/alerts/{id}/acknowledge` | Acknowledge an alert |
| POST | `/api/rtm/alerts/{id}/resolve` | Resolve an alert |
| GET | `/api/rtm/dashboard` | Get dashboard stats |

#### Threshold Alert System

```php
// Example monitoring_parameters configuration
[
    'thresholds' => [
        'pain_level' => [
            'max' => 7,
            'min' => 0,
            'max_severity' => 'high',
            'min_severity' => 'low'
        ],
        'adherence' => [
            'min' => 80,
            'min_severity' => 'high'
        ]
    ]
]
```

### Business Value

- **Improved Patient Outcomes**: Early detection of clinical deterioration through automated alerting
- **Reduced Readmissions**: Continuous monitoring between visits prevents condition worsening
- **Efficient Resource Use**: Doctors prioritize patients who need attention via alert system
- **Billing Opportunities**: RTM codes (99457, 99458) generate additional revenue per patient per month
- **Patient Engagement**: Patients feel connected to their care team between visits

---

## 2. AI-Powered Waitlist Filler

### Overview

Automatically fills cancelled appointment slots by matching waitlisted patients with available appointments using AI scoring that considers time preferences, service needs, priority, and wait time.

### How It Works

#### Core Components

**WaitlistMatchOffer Model** (`app/Models/WaitlistMatchOffer.php`)
- Represents a matched slot offer to a patient
- Status workflow: `sent` → `accepted`/`declined` → `expired`/`booked`
- Enforces valid state transitions (cannot accept an expired offer)
- Stores matching score and offer metadata

**AiWaitlistMatcher Service** (`app/Services/AiWaitlistMatcher.php`)
- Intelligent matching algorithm with named scoring constants:
  - `SCORE_NEUTRAL = 0.1` - baseline score
  - `SCORE_PERFECT_TIME = 0.3` - exact time preference match
  - `SCORE_PARTIAL_TIME = 0.15` - partial time preference match
  - `SCORE_PERFECT_DAY = 0.2` - exact day preference match
  - `SCORE_SERVICE_MATCH = 0.15` - required service available
  - `SCORE_SERVICE_FALLBACK = 0.1` - related service available
  - `SCORE_PRIORITY_*` - urgency multipliers (0.05-0.15)
  - `SCORE_MAX_WAIT = 0.2` - wait time bonus (0.002 per day)
- Score calculation formula: `baseScore + timeScore + dayScore + serviceScore + priorityScore + waitTimeScore`

**WaitlistOfferNotification** (`app/Notifications/WaitlistOfferNotification.php`)
- Multi-channel notification (database, email, SMS, broadcast)
- Respects patient notification preferences
- Checks quiet hours before sending
- Uses `WaitlistAccessControl` middleware for security

#### API Endpoints (`app/Http/Controllers/Api/WaitlistFillerController.php`)

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/waitlist-filler/find-matches` | Find matching patients for a slot |
| POST | `/api/waitlist-filler/send-offer` | Send offer to matched patient |
| POST | `/api/waitlist-filler/offers/{id}/accept` | Accept the offer |
| POST | `/api/waitlist-filler/offers/{id}/decline` | Decline the offer |
| GET | `/api/waitlist-filler/pending-offers` | List pending offers |
| GET | `/api/waitlist-filler/analytics` | Get fill rate analytics |
| GET | `/api/waitlist-filler/settings` | Get AI matcher settings |
| PUT | `/api/waitlist-filler/settings` | Update AI matcher settings |

#### Scoring Algorithm

```php
public function calculateMatchScore(WaitlistEntry $entry, AppointmentSlot $slot): float
{
    $score = self::SCORE_NEUTRAL;

    // Time preference matching (0.3 for exact, 0.15 for partial)
    if ($entry->preferred_time === $slot->time) {
        $score += self::SCORE_PERFECT_TIME;
    } elseif ($this->timeRangesOverlap($entry->preferred_time, $slot->time)) {
        $score += self::SCORE_PARTIAL_TIME;
    }

    // Day preference matching (0.2 for exact)
    if ($entry->preferred_days && in_array($slot->day_of_week, $entry->preferred_days)) {
        $score += self::SCORE_PERFECT_DAY;
    }

    // Service matching (0.15 exact, 0.1 fallback)
    if ($entry->required_service_id === $slot->service_id) {
        $score += self::SCORE_SERVICE_MATCH;
    } elseif ($this->servicesRelated($entry->required_service_id, $slot->service_id)) {
        $score += self::SCORE_SERVICE_FALLBACK;
    }

    // Priority multiplier (higher urgency = higher score)
    $score += $this->getPriorityScore($entry->priority);

    // Wait time bonus (accumulates over time)
    $score += min($entry->wait_days * self::WAIT_TIME_MULTIPLIER, self::SCORE_MAX_WAIT);

    return $score;
}
```

### Business Value

- **Reduced No-Shows**: Patients on waitlist are pre-matched and responsive
- **Increased Revenue**: Filled cancelled slots that would otherwise be lost
- **Improved Patient Satisfaction**: Shorter wait times through proactive matching
- **Operational Efficiency**: Automated matching reduces manual staff work
- **Data-Driven Optimization**: Analytics track fill rates and improve algorithm over time

---

## 3. Google Review AI Responses

### Overview

AI-powered system that generates thoughtful, personalized responses to Google reviews, allowing doctors to maintain their online reputation efficiently while ensuring quality control through an approval workflow.

### How It Works

#### Core Components

**GoogleReviewResponse Model** (`app/Models/GoogleReviewResponse.php`)
- Stores AI-generated responses linked to Google reviews
- Tracks approval status: `pending` → `approved`/`rejected`
- Records tone selection and response metadata
- Audit trail: `generated_at`, `approved_at`, `posted_at`

**GoogleReviewController** (`app/Http/Controllers/Api/GoogleReviewController.php`)
- Generates AI responses using `AiReviewResponseGenerator` service
- Provides approval workflow for human review before posting
- Manages settings for response tone and preferences

#### API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/review-ai/generate` | Generate AI response for a review |
| GET | `/api/review-ai/pending` | List pending responses for approval |
| POST | `/api/review-ai/responses/{id}/approve` | Approve a response |
| POST | `/api/review-ai/responses/{id}/reject` | Reject and regenerate |
| POST | `/api/review-ai/responses/{id}/mark-posted` | Mark response as posted to Google |
| GET | `/api/review-ai/settings` | Get response settings |
| PUT | `/api/review-ai/settings` | Update response settings |

#### Tone Options

- `professional`: Formal, clinical tone appropriate for medical practice
- `friendly`: Warm, approachable language
- `empathetic`: Focused on understanding patient concerns
- `thankful`: Expresses gratitude for positive reviews
- `apologetic`: Appropriate for negative reviews

### Business Value

- **Time Savings**: Doctors respond to hundreds of reviews with minimal effort
- **Consistent Branding**: All responses maintain practice voice and tone
- **Quality Control**: Approval workflow ensures no inappropriate responses go live
- **Reputation Management**: Prompt responses to negative reviews show commitment to patient satisfaction
- **Patient Engagement**: Patients see thoughtful responses, encouraging continued engagement

---

## 4. Provider Compensation Management

### Overview

Comprehensive system for tracking and managing provider salaries, bonuses, commissions, and compensation plans. Supports multiple compensation models and generates reports for payroll processing.

### How It Works

#### Core Components

**CompensationPlan Model** (`app/Models/CompensationPlan.php`)
- Template for compensation structures
- Supports different plan types (salary, hourly, commission-based, hybrid)
- Configurable bonus rules and commission rates
- Effective date tracking for plan changes

**ProviderCompensation Model**
- Tracks actual compensation payments to providers
- Links to compensation plan and provider
- Records payment status: `pending` → `approved` → `paid`
- Supports effective date tracking for retroactive adjustments

**ProviderBonus Model**
- Tracks bonus payments separate from base compensation
- Links to compensation plan and triggering event
- Approval workflow for bonus payments
- Notes field for bonus rationale

**CompensationController** (`app/Http/Controllers/Api/CompensationController.php`)
- Full CRUD for compensation plans
- Compensation and bonus management
- Approval workflows
- Summary reporting

#### API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/compensation/plans` | List compensation plans |
| POST | `/api/compensation/plans` | Create compensation plan |
| PUT | `/api/compensation/plans/{id}` | Update compensation plan |
| DELETE | `/api/compensation/plans/{id}` | Delete compensation plan |
| GET | `/api/compensation/compensations` | List compensations |
| POST | `/api/compensation/compensations` | Create compensation record |
| POST | `/api/compensation/compensations/{id}/approve` | Approve compensation |
| POST | `/api/compensation/compensations/{id}/mark-paid` | Mark as paid |
| GET | `/api/compensation/bonuses` | List bonuses |
| POST | `/api/compensation/bonuses` | Create bonus record |
| POST | `/api/compensation/bonuses/{id}/approve` | Approve bonus |
| GET | `/api/compensation/summary` | Get compensation summary |

#### Compensation Plan Types

1. **Salary-Based**: Fixed periodic payment regardless of production
2. **Hourly-Based**: Payment based on hours worked
3. **Commission-Based**: Percentage of revenue generated
4. **Hybrid**: Combination of base salary + commission/bonus

### Business Value

- **Transparency**: Clear compensation structures for providers
- **Payroll Efficiency**: Automated tracking reduces payroll processing time
- **Performance Incentives**: Bonus structures tied to productivity or quality metrics
- **Compliance**: Audit trail for all compensation decisions
- **Reporting**: Summary views for financial planning and budgeting

---

## 5. RCM Services (Revenue Cycle Management)

### Overview

Full revenue cycle management system for tracking claims, managing denials, and automating collections workflows. Assigns dedicated RCM managers to practices and provides a portal for claim management.

### How It Works

#### Core Components

**RcmAccount Model**
- Represents RCM account for a practice
- Links practice to dedicated RCM manager
- Tracks account status and performance metrics
- Stores contract terms and fee schedules

**RcmClaim Model**
- Claims assigned to RCM management
- Tracks claim status through lifecycle
- Links to original claim and payment records
- Records denial information and appeal status

**RcmController**
- Portal functionality for RCM team
- Claim assignment and tracking
- Collections workflow management
- Performance reporting

#### Features

- **Claim Tracking**: Monitor claim status from submission to payment
- **Denial Management**: Track denials, reasons, and appeal status
- **Collections Automation**: Automated follow-up on unpaid claims
- **Performance Analytics**: RCM team productivity and recovery rates
- **Manager Assignment**: Assign dedicated RCM managers to practices

### Business Value

- **Revenue Protection**: Professional management reduces claim denials and losses
- **Cash Flow Improvement**: Faster claim resolution and payment posting
- **Operational Efficiency**: RCM team has dedicated portal for all claim needs
- **Transparency**: Practice owners see real-time RCM performance
- **Cost Savings**: Professional management reduces internal billing overhead

---

## Technical Architecture

### Database Tables

| Feature | Tables |
|---------|--------|
| RTM | `rtm_sessions`, `rtm_metrics`, `rtm_alerts` |
| Waitlist Filler | `waitlists`, `waitlist_entries`, `waitlist_patient_preferences`, `waitlist_match_offers`, `waitlist_ai_settings` |
| Google Review AI | `google_review_responses`, `google_review_settings` |
| Compensation | `compensation_plans`, `provider_compensations`, `provider_bonuses` |
| RCM | `rcm_accounts`, `rcm_claims`, `rcm_transactions` |

### Service Layer

All features follow Laravel best practices with:
- **Service Classes**: Business logic encapsulation
- **Model Methods**: Status transitions, threshold checking, scoring algorithms
- **Notification Classes**: Multi-channel patient communication
- **Middleware**: Security and access control
- **Jobs**: Async processing for heavy operations

### Security

- **WaitlistAccessControl Middleware**: Role-based access for waitlist operations
- **Authorization Policies**: Model policies for each resource type
- **Input Validation**: Request validation on all endpoints
- **State Machine Validation**: Prevents invalid status transitions

### Testing

Each feature includes comprehensive test coverage:
- Unit tests for models and services
- Feature tests for API endpoints
- Integration tests for workflows
- Performance tests for high-traffic scenarios

---

## API Authentication

All API endpoints require authentication via Laravel Sanctum. Include the bearer token in requests:

```
Authorization: Bearer {token}
```

Role-based access control:
- `doctor`: RTM, Compensation, Waitlist management
- `patient`: Waitlist self-service, RTM metrics
- `admin`: Full access to all features
- `rcm_manager`: RCM portal access

---

## Getting Started

### Enable a Feature

1. Run migrations: `php artisan migrate`
2. Configure feature settings via API or admin panel
3. Assign appropriate roles to users

### Configure Thresholds (RTM Example)

```php
$session->update([
    'monitoring_parameters' => [
        'thresholds' => [
            'pain_level' => ['max' => 7, 'min' => 0],
            'adherence' => ['min' => 80]
        ]
    ]
]);
```

### Set Up AI Response Tones

```php
GoogleReviewSetting::updateOrCreate(
    ['doctor_id' => $doctorId],
    ['default_tone' => 'professional', 'auto_approve_positive' => true]
);
```

---

## Future Enhancements

- **RTM Device Integration**: API connections for wearable devices
- **Waitlist Predictive Analytics**: ML-based wait time predictions
- **RCM ML Denial Prediction**: Predict denials before claim submission
- **Compensation Scenario Planning**: What-if analysis for compensation changes
