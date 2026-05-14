# Global Search Algorithm - Comprehensive Explanation

## Overview

The Campus Navigator global search system is a **multi-model, weighted relevance ranking system** that searches across 4 different models (Events, Buildings, Rooms, Lost Items) simultaneously using intelligent keyword parsing and database-level scoring.

---

## How It Works - Step by Step

### Step 1: Input Parsing

When a user types a search query like: **"tech conference 2026"**

The system **parses** the input into meaningful keywords:

```
Raw Input: "tech conference 2026"
           ↓
Normalize: Remove extra whitespace, convert to lowercase
           "tech conference 2026"
           ↓
Tokenize: Split into individual words
           ["tech", "conference", "2026"]
           ↓
Filter: Remove stop words (a, the, and, of, on, in, to, for)
           ["tech", "conference", "2026"]
           ↓
Final Keywords: ["tech", "conference", "2026"]
```

**Stop Words Filtered:**
```
'of', 'the', 'and', 'a', 'an', 'in', 'on', 'to', 'for'
```

These are removed because they don't add search value.

### Step 2: Keyword Filtering (AND Logic)

For each model, the system applies a **mandatory AND logic**: **ALL keywords must appear in at least one searchable field**.

**SQL Structure (Conceptual):**
```sql
WHERE 
  (field_1 LIKE '%tech%' OR field_2 LIKE '%tech%' OR field_3 LIKE '%tech%')
  AND
  (field_1 LIKE '%conference%' OR field_2 LIKE '%conference%' OR field_3 LIKE '%conference%')
  AND
  (field_1 LIKE '%2026%' OR field_2 LIKE '%2026%' OR field_3 LIKE '%2026%')
```

This means:
- ✅ **Matches:** Event with title "Tech Conference 2026" (all 3 keywords in title)
- ✅ **Matches:** Event with title "Tech" and description "Conference 2026" (all keywords across fields)
- ❌ **Doesn't match:** Event with title "Tech" only (missing "conference")

### Step 3: Relevance Scoring Algorithm

For each matching record, the system calculates a **weighted relevance score** using multiple scoring rules:

```
Total Score = Σ(Weight × Match Type)
```

#### Scoring Rules

| Match Type | Search Field | Weight | Example |
|-----------|-------------|--------|---------|
| Exact match (full term) | Primary Field | 120 | Title = "Tech Conference" |
| Starts with (full term) | Primary Field | 80 | Title = "Tech Conference..." |
| Contains (full term) | Primary Field | 45 | Title = "The Tech Conference" |
| Exact match (keyword) | Primary Field | 24 | Title = "tech" (exact) |
| Starts with (keyword) | Primary Field | 14 | Title = "technology..." |
| Contains (keyword) | Primary Field | 8 | Title = "technical" |
| Contains (keyword) | Medium Field | 20 | Location = "...tech..." |
| Contains (keyword) | Low Field | 8 | Description = "...tech..." |

#### Example Scoring

**Search Term:** "tech conference"  
**Event 1 Title:** "Tech Conference 2026"  
**Event 1 Description:** "Annual technology conference"

```
Scoring for Event 1:
- "tech" exact match in title (primary): +24
- "tech" starts with in title (primary): +14
- "tech" contains in title (primary): +8
- "tech" starts with in location (medium): +20
- "tech" contains in description (low): +8
- "conference" exact match in title: +24
- "conference" starts with in title: +14
- "conference" contains in title: +8
- "conference" contains in location: +20
- "conference" contains in description: +8
────────────────────────────────
Total Relevance Score: 148
```

### Step 4: Results Assembly

Results are returned in this priority order:

1. **Sort by relevance score** (highest first)
2. **Sort by name/title** (alphabetically)
3. **Sort by created_at** (newest first)

```php
// SQL ORDER BY
ORDER BY 
  relevance DESC,      // Primary sort: score
  title ASC,           // Secondary: name
  created_at DESC      // Tertiary: date
```

---

## Models & Searchable Fields

### 1. Events
| Field | Priority | Searchable |
|-------|----------|-----------|
| title | PRIMARY | ✅ YES |
| description | LOW | ✅ YES |
| location | MEDIUM | ✅ YES |

### 2. Buildings
| Field | Priority | Searchable |
|-------|----------|-----------|
| name | PRIMARY | ✅ YES |
| description | LOW | ✅ YES |

### 3. Rooms
| Field | Priority | Searchable |
|-------|----------|-----------|
| room_number | PRIMARY | ✅ YES |
| type | MEDIUM | ✅ YES |
| building.name | Related | ✅ YES (via JOIN) |
| building.description | Related | ✅ YES (via JOIN) |

### 4. Lost Items
| Field | Priority | Searchable |
|-------|----------|-----------|
| title | PRIMARY | ✅ YES |
| location | MEDIUM | ✅ YES |
| description | LOW | ✅ YES |

---

## Algorithm Implementation Details

### Pattern Generation

The system uses SQL `LIKE` patterns with proper escaping:

```php
// startsWith pattern: "tech" → "tech%"
private function startsWithPattern(string $value): string {
    return $this->escapeLike($value) . '%';
}

// contains pattern: "tech" → "%tech%"
private function containsPattern(string $value): string {
    return '%' . $this->escapeLike($value) . '%';
}

// SQL escape special characters: % _ \
private function escapeLike(string $value): string {
    return addcslashes($value, '%_\\');
}
```

### SQL WHERE Clause Structure

```sql
-- For single keyword "tech"
WHERE (
  (title LIKE '%tech%' OR description LIKE '%tech%' OR location LIKE '%tech%')
  AND
  (building.name LIKE '%tech%' OR related_fields...)
)
```

### SQL CASE Scoring

The actual SQL uses nested CASE statements:

```sql
SELECT events.*,
  (
    CASE WHEN LOWER(title) = 'tech conference' THEN 120 ELSE 0 END +
    CASE WHEN LOWER(title) LIKE 'tech conference%' THEN 80 ELSE 0 END +
    CASE WHEN LOWER(title) LIKE '%tech conference%' THEN 45 ELSE 0 END +
    CASE WHEN LOWER(title) = 'tech' THEN 24 ELSE 0 END +
    CASE WHEN LOWER(title) LIKE 'tech%' THEN 14 ELSE 0 END +
    CASE WHEN LOWER(title) LIKE '%tech%' THEN 8 ELSE 0 END +
    CASE WHEN LOWER(location) LIKE '%tech%' THEN 20 ELSE 0 END +
    CASE WHEN LOWER(description) LIKE '%tech%' THEN 8 ELSE 0 END +
    CASE WHEN LOWER(title) = 'conference' THEN 24 ELSE 0 END +
    CASE WHEN LOWER(title) LIKE 'conference%' THEN 14 ELSE 0 END +
    CASE WHEN LOWER(title) LIKE '%conference%' THEN 8 ELSE 0 END +
    CASE WHEN LOWER(location) LIKE '%conference%' THEN 20 ELSE 0 END +
    CASE WHEN LOWER(description) LIKE '%conference%' THEN 8 ELSE 0 END
  ) AS relevance
FROM events
ORDER BY relevance DESC, title ASC, created_at DESC
```

---

## API Usage

### Search Endpoint
```
GET /api/v1/search?q=term&per_model=5

Query Parameters:
  - q          (required) Search term (min 2 chars, max 100 chars)
  - per_model  (optional) Results per model (default: 5, max: 20)
```

### Request Example
```bash
curl -X GET "http://localhost:8000/api/v1/search?q=tech+conference&per_model=5" \
  -H "Authorization: Bearer <token>" \
  -H "Content-Type: application/json"
```

### Response Structure
```json
{
  "success": true,
  "message": "Search results for \"tech conference\".",
  "data": {
    "query": "tech conference",
    "events": [
      {
        "id": 1,
        "title": "Tech Conference 2026",
        "description": "Annual technology conference",
        "location": "Convention Center",
        "start_date": "2026-06-01",
        "created_at": "2026-05-10T14:30:00Z"
      }
    ],
    "buildings": [
      {
        "id": 2,
        "name": "Tech Building",
        "description": "Houses technology labs and conference rooms"
      }
    ],
    "rooms": [
      {
        "id": 5,
        "room_number": "Tech-101",
        "building": {
          "id": 2,
          "name": "Tech Building"
        }
      }
    ],
    "lost_items": [],
    "counts": {
      "events": 1,
      "buildings": 1,
      "rooms": 1,
      "lost_items": 0
    }
  }
}
```

### Autocomplete/Suggestions Endpoint
```
GET /api/v1/search/suggest?q=term&limit=5

Query Parameters:
  - q     (required) Partial search term
  - limit (optional) Max suggestions (default: 5, max: 10)
```

### Suggestions Response
```json
{
  "success": true,
  "data": [
    {
      "type": "event",
      "id": 1,
      "label": "Tech Conference 2026",
      "score": 100
    },
    {
      "type": "building",
      "id": 2,
      "label": "Tech Building",
      "score": 75
    }
  ]
}
```

---

## Configuration

### Config File: `config/search.php`

```php
'cache_ttl' => 300,                      // Cache results for 5 minutes
'min_search_length' => 2,                // Minimum query length (chars)
'max_search_length' => 100,              // Maximum query length (chars)
'min_keyword_length' => 2,               // Minimum keyword length (chars)
'max_per_page' => 50,                    // Hard ceiling on results
'default_per_page' => 15,                // Default results per page
'admin_no_cache' => true,                // Admins can bypass cache

'stop_words' => [                        // Words to ignore
  'of', 'the', 'and', 'a', 'an', 'in', 'on', 'to', 'for'
],

'relevance_weights' => [                 // Scoring weights
  'exact' => 120,
  'starts_with' => 80,
  'contains' => 45,
  'keyword_exact' => 24,
  'keyword_starts_with' => 14,
  'keyword_contains' => 8,
  'medium_field_contains' => 20,
  'low_field_contains' => 8,
]
```

### Environment Variables
```
SEARCH_CACHE_TTL=300
SEARCH_MIN_LENGTH=2
SEARCH_MAX_LENGTH=100
SEARCH_MIN_KEYWORD_LENGTH=2
SEARCH_WEIGHT_EXACT=120
SEARCH_WEIGHT_STARTS_WITH=80
```

---

## Performance Optimizations

### 1. Caching
- Search results cached for **5 minutes (300 seconds)** in Redis
- Cache key includes search term and per_model parameter
- Admins can bypass cache with `?no_cache=1`

### 2. Database Indexes
```sql
-- Recommended indexes for search performance
CREATE INDEX idx_events_title ON events(title);
CREATE INDEX idx_events_location ON events(location);
CREATE INDEX idx_buildings_name ON buildings(name);
CREATE INDEX idx_rooms_room_number ON rooms(room_number);
CREATE INDEX idx_lost_items_title ON lost_items(title);
```

### 3. Query Limits
- Max 5 results per model by default
- Hard ceiling of 20 results per model
- Prevents memory exhaustion from oversized requests

### 4. Parallel Search
- Each model searched independently
- Results collected and formatted in parallel
- Response includes counts for each model

---

## Examples

### Example 1: Simple Single-Keyword Search

**Query:** "tech"

```
Parsed Keywords: ["tech"]

For Events:
  WHERE (title LIKE '%tech%' OR description LIKE '%tech%' OR location LIKE '%tech%')

Results found:
  1. Event: "Tech Conference 2026" (score: 156)
  2. Event: "Technical Seminar" (score: 89)
```

### Example 2: Multi-Keyword AND Search

**Query:** "tech conference"

```
Parsed Keywords: ["tech", "conference"]

For Events:
  WHERE (title LIKE '%tech%' OR ... )
    AND (title LIKE '%conference%' OR ...)

Results found:
  1. Event: "Tech Conference 2026" ✅ (has both keywords)
  2. Event: "Technical Seminar" ❌ (missing "conference")
```

### Example 3: Stop Words Filtering

**Query:** "the tech conference"

```
Raw Keywords: ["the", "tech", "conference"]
After Stop Word Removal: ["tech", "conference"]
Effective Search: Same as Example 2
```

### Example 4: Partial Name Search

**Query:** "room 1"

```
Parsed Keywords: ["room", "1"]

For Rooms:
  WHERE (room_number LIKE '%room%' OR ... )
    AND (room_number LIKE '%1%' OR ...)

Results found:
  1. Room: "101" in "Building A"
  2. Room: "110" in "Building B"
  3. Room: "Room-1" in "Tech Building"
```

---

## Weighted Relevance Ranking Algorithm

```
Algorithm: Weighted TF-IDF-like Scoring

1. Parse Input
   ├─ Normalize: lowercase, trim, collapse whitespace
   ├─ Tokenize: split into keywords
   └─ Filter: remove stop words

2. For Each Model:
   ├─ Filter: WHERE all_keywords_present
   ├─ Score: CASE statements for each match type
   └─ Rank: ORDER BY score DESC, name ASC, date DESC

3. Assemble Results:
   ├─ Group by model type
   ├─ Format with resources
   └─ Return with counts
```

### Scoring Logic Tree

```
For each keyword in parsed query:
  ├─ Check Primary Field (weight 120/24):
  │  ├─ Exact match? → +120
  │  ├─ Starts with? → +80
  │  └─ Contains? → +45
  │
  ├─ Check Primary Field (keyword level):
  │  ├─ Exact match? → +24
  │  ├─ Starts with? → +14
  │  └─ Contains? → +8
  │
  ├─ Check Medium Fields (weight 20):
  │  └─ Contains? → +20
  │
  └─ Check Low Fields (weight 8):
     └─ Contains? → +8
```

---

## Security Features

1. **SQL Injection Prevention**
   - Use parameterized queries with `?` placeholders
   - Proper LIKE escaping with `addcslashes()`

2. **Minimum Query Length**
   - Prevents full-table scans on empty/tiny queries
   - Default: 2 characters minimum

3. **Maximum Query Length**
   - Prevents abuse via enormous patterns
   - Default: 100 characters maximum

4. **Rate Limiting**
   - Laravel rate limiting middleware
   - Configurable per endpoint

5. **Input Validation**
   - Form request validation on all parameters
   - Type hints and range checks

---

## Why This Algorithm Works Well

✅ **Precision:** AND logic ensures all keywords present  
✅ **Relevance:** Weighted scoring ranks best matches first  
✅ **Performance:** Database-level scoring, minimal PHP processing  
✅ **Flexibility:** Configurable weights and stop words  
✅ **Usability:** Autocomplete suggestions with high-scoring candidates  
✅ **Reliability:** Cached results reduce database load  
✅ **Simplicity:** SQL LIKE patterns work on any database  

---

## Comparison with Other Algorithms

| Algorithm | Pros | Cons | Used Here |
|-----------|------|------|-----------|
| Full-text Search | Very fast, optimized | Complex setup | ❌ No |
| LIKE Pattern | Simple, portable | Slower on large datasets | ✅ YES |
| Elasticsearch | Powerful, scalable | Heavy infrastructure | ❌ No |
| Regex | Flexible | Unsafe, slow | ❌ No |

---

**Last Updated:** May 14, 2026
**Version:** 1.0
