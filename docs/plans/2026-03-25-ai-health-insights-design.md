# AI Health Insights Engine

## Context

The patient portal now has daily symptom journaling and medication adherence tracking. However, patients have no automated way to understand patterns in their health data. This feature adds an on-demand AI insights engine — when a patient clicks "Get AI Insights", the system analyzes their recent health journals, medication logs, HEP progress, and diagnoses to surface patterns, warnings, and recommendations.

## Design

### Architecture & Data Model

**New table:** `health_insights`

| Column | Type | Purpose |
|---|---|---|
| id | bigint | Primary key |
| user_id | bigint | FK → users |
| insight_type | string | `symptom_trend`, `medication_adherence`, `combined` |
| summary | text | Short headline (e.g., "3 patterns detected") |
| content | json | Full AI response: sections, warnings, recommendations |
| created_at | timestamp | Generated at |
| expires_at | timestamp | When insight becomes stale (24h for combined) |

**New model:** `HealthInsight` — belongs to User, stores content as JSON, soft deletes.

**New service:** `HealthInsightsService` — single method `generateInsights(User $patient, bool $force = false): HealthInsight`

**Routes** (under existing `/patient/health` hub):

| Route | Method | Purpose |
|---|---|---|
| `/patient/health/insights` | GET | Insights page |
| `/patient/health/insights/generate` | POST | Generate/regenerate insights |

### AI Prompt Strategy & Content Structure

`HealthInsightsService::generateInsights()` gathers data from:

1. **Symptom trends** — Last 14 days of `health_journals`: symptoms, severity, frequency
2. **Medication adherence** — Last 14 days from `health_medication_logs` + `health_medication_schedules`: taken/skipped per medication
3. **Recent diagnoses** — Last 3 diagnoses from `diagnoses` table

**Prompt output format** — AI returns JSON:
```json
{
  "summary": "3 notable patterns detected",
  "patterns": [
    {
      "type": "symptom_trend|medication_adherence|hep_progress|positive",
      "title": "Increasing headache frequency",
      "description": "Headaches reported 4 times this week vs 1 time last week",
      "severity": "info|warning|alert",
      "recommendation": "Consider tracking triggers in your journal notes"
    }
  ],
  "medication_insight": {
    "adherence_rate": 85,
    "missed_doses": 3,
    "overall_status": "good|concerning|excellent",
    "medications": [{"Name": "Aspirin", "adherence_rate": 85, "status": "good", "note": "Taken 6/7 days"}]
  },
  "overall_assessment": "You're doing well overall. One area to watch.",
  "next_steps": ["Tip: Try logging potential headache triggers", "Action: Consider a follow-up if headaches worsen"]
}
```

**Caching:** Check for non-expired insight before generating. On explicit "Regenerate", delete old insights and generate fresh.

### Components & Views

**New blade views:**

| View | Purpose |
|---|---|
| `patient/health/insights/index.blade.php` | Main page — latest insight, past insights list, generate button |
| `patient/health/insights/components/insight-card.blade.php` | Reusable insight rendering component |

**Dashboard update:** `patient/health/dashboard.blade.php` — new AI Insights summary panel at top linking to insights page.

### Data Flow & Error Handling

1. Patient visits `/patient/health/insights` or clicks "Generate"
2. Controller calls `HealthInsightsService::generateInsights(Auth::user(), force)`
3. Service gathers patient data, builds structured prompt, calls OpenAI GPT-4o-mini
4. Response parsed from JSON → stored in `health_insights`
5. On generation failure: `RuntimeException` → 422 JSON; generic errors → 500 JSON; "no data" → exception with helpful message

**Reuse existing AI infrastructure:**
- `OpenAI::chat()->create()` via Laravel OpenAI facade
- Model: `gpt-4o-mini` for low cost
- Config: reuse `config/openai.php`

## Verification

- Run `php artisan migrate` to apply new `health_insights` table migration
- Navigate to `/patient/health/insights` as a patient — empty state shows if no data
- Log symptoms + medications, then click "Generate Insights" — verify JSON response stored and rendered
- Click "Regenerate" — verify old insight is replaced
- As non-patient — verify 403 redirect
- Check Laravel log for any OpenAI errors on generation failure
