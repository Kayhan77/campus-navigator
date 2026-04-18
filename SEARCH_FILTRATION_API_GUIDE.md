# Search & Filtration API Documentation

Campus Navigator Backend provides powerful search and filtering capabilities across all major models.

---

## **1. GLOBAL CROSS-MODEL SEARCH**

Search across **Events**, **Buildings**, **Rooms**, and **Lost Items** simultaneously.

### Endpoint
```
GET /api/v1/search
```

### Query Parameters
| Parameter | Type | Required | Default | Max |
|-----------|------|----------|---------|-----|
| q | string | Yes | - | 100 chars |
| per_model | integer | No | 5 | 20 |

### Example Request
```
GET /api/v1/search?q=library&per_model=10
```

### Response
```json
{
  "query": "library",
  "events": [
    {
      "id": 1,
      "title": "Library Orientation",
      "location": "Main Library",
      ...
    }
  ],
  "buildings": [
    {
      "id": 2,
      "name": "Library Building",
      ...
    }
  ],
  "rooms": [
    {
      "id": 5,
      "room_number": "LIB-101",
      ...
    }
  ],
  "lost_items": [
    {
      "id": 3,
      "title": "Lost notebook at library",
      ...
    }
  ],
  "counts": {
    "events": 1,
    "buildings": 1,
    "rooms": 1,
    "lost_items": 1
  }
}
```

### Notes
- Searches: `title`, `description`, `location`, `name`, `room_number` (depending on model)
- Minimum search length: 2 characters
- Results ordered by `latest` (created_at DESC)
- Maximum 20 results per model type

---

## **2. LOST & FOUND FILTERING**

List and filter lost items with multiple search and filter options.

### Endpoint
```
GET /api/v1/lost-found
Authorization: Bearer {token}
```

### Query Parameters
| Parameter | Type | Options | Example |
|-----------|------|---------|---------|
| q | string | - | `?q=backpack` |
| status | string | `lost`, `found` | `?status=lost` |
| location | string | - | `?location=library` |
| date_from | date | YYYY-MM-DD | `?date_from=2026-04-01` |
| date_to | date | YYYY-MM-DD | `?date_to=2026-04-18` |
| sort_by | string | `created_at`, `title`, `status` | `?sort_by=title` |
| sort_dir | string | `asc`, `desc` | `?sort_dir=desc` |
| page | integer | - | `?page=2` |
| per_page | integer | - | `?per_page=15` |

### Example Requests
```
# Search for "backpack" in lost items only
GET /api/v1/lost-found?q=backpack&status=lost

# Find items reported at the library after April 1st
GET /api/v1/lost-found?location=library&date_from=2026-04-01&sort_by=created_at&sort_dir=desc

# Paginated results sorted by title
GET /api/v1/lost-found?sort_by=title&page=1&per_page=20
```

### Searchable Fields
- title
- description
- location

---

## **3. EVENT FILTERING**

List and filter events with comprehensive filtering options.

### Endpoint
```
GET /api/v1/events
```

### Query Parameters
| Parameter | Type | Options | Example |
|-----------|------|---------|---------|
| q | string | - | `?q=campus` |
| location | string | - | `?location=Hall+A` |
| date_from | date | YYYY-MM-DD | `?date_from=2026-04-20` |
| date_to | date | YYYY-MM-DD | `?date_to=2026-06-30` |
| date_field | string | `start_time`, `end_time`, `created_at` | `?date_field=start_time` |
| sort_by | string | `start_time`, `end_time`, `title`, `created_at` | `?sort_by=start_time` |
| sort_dir | string | `asc`, `desc` | `?sort_dir=asc` |
| page | integer | - | `?page=1` |
| per_page | integer | - | `?per_page=10` |

### Example Requests
```
# Upcoming events sorted by start time
GET /api/v1/events?sort_by=start_time&sort_dir=asc

# Events in Hall A between specific dates
GET /api/v1/events?location=Hall+A&date_from=2026-04-20&date_to=2026-05-20

# Search for "workshop" events
GET /api/v1/events?q=workshop
```

### Searchable Fields
- title
- description
- location

---

## **4. BUILDINGS FILTERING**

List and filter buildings.

### Endpoint
```
GET /api/v1/buildings
```

### Query Parameters
| Parameter | Type | Example |
|-----------|------|---------|
| q | string | `?q=main` |
| sort_by | string | `?sort_by=name` |
| sort_dir | string | `?sort_dir=asc` |
| page | integer | `?page=1` |
| per_page | integer | `?per_page=15` |

### Example Requests
```
# Search for buildings with "library" in name
GET /api/v1/buildings?q=library&sort_dir=asc

# Get paginated buildings list
GET /api/v1/buildings?page=2&per_page=20
```

### Searchable Fields
- name
- description

---

## **5. ROOMS - ADVANCED SEARCH**

Specialized room search with building and floor filters.

### Endpoint
```
GET /api/v1/rooms/search
Authorization: Bearer {token}
```

### Query Parameters
| Parameter | Type | Example | Notes |
|-----------|------|---------|-------|
| q | string | `?q=101` | Search room number |
| building_id | integer | `?building_id=3` | Filter by building |
| floor | integer | `?floor=2` | Exact floor match |
| page | integer | `?page=1` | - |
| per_page | integer | `?per_page=25` | - |

### Example Requests
```
# Find all rooms on floor 2
GET /api/v1/rooms/search?floor=2

# Find room 101 in building 3
GET /api/v1/rooms/search?q=101&building_id=3

# List all rooms in a building
GET /api/v1/rooms/search?building_id=5&per_page=50
```

### Searchable Fields
- room_number

---

## **6. REGULAR ROOMS FILTERING**

Standard room list with basic filtering.

### Endpoint
```
GET /api/v1/rooms
```

### Query Parameters
| Parameter | Type | Example |
|-----------|------|---------|
| q | string | `?q=101` |
| building_id | integer | `?building_id=3` |
| floor | integer | `?floor=2` |
| sort_by | string | `?sort_by=room_number` |
| sort_dir | string | `?sort_dir=asc` |
| page | integer | `?page=1` |
| per_page | integer | `?per_page=15` |

---

## **7. ACADEMIC SCHEDULE FILTERING**

Filter course schedules by day, room, and search terms.

### Endpoint
```
GET /api/v1/schedule
```

### Query Parameters
| Parameter | Type | Options | Example |
|-----------|------|---------|---------|
| q | string | - | `?q=physics` |
| day | string | Monday-Sunday | `?day=Monday` |
| room_id | integer | - | `?room_id=5` |
| sort_by | string | `course_name`, `day`, `start_time`, `created_at` | `?sort_by=start_time` |
| sort_dir | string | `asc`, `desc` | `?sort_dir=asc` |
| page | integer | - | `?page=1` |
| per_page | integer | - | `?per_page=20` |

### Example Requests
```
# All physics courses
GET /api/v1/schedule?q=physics

# All classes meeting on Monday
GET /api/v1/schedule?day=Monday&sort_by=start_time

# All classes in room 5 on Wednesday
GET /api/v1/schedule?room_id=5&day=Wednesday

# Day names are case-insensitive
GET /api/v1/schedule?day=monday  (works same as ?day=Monday)
```

### Searchable Fields
- course_name

---

## **8. NEWS FILTERING**

Filter published news articles.

### Endpoint
```
GET /api/v1/news
```

### Query Parameters
| Parameter | Type | Example |
|-----------|------|---------|
| sort_by | string | `?sort_by=created_at` |
| sort_dir | string | `?sort_dir=desc` |
| page | integer | `?page=1` |
| per_page | integer | `?per_page=10` |

### Sortable Fields
- created_at
- published_at

---

## **9. ANNOUNCEMENTS FILTERING**

Filter published announcements.

### Endpoint
```
GET /api/v1/announcements
```

### Query Parameters
| Parameter | Type | Example |
|-----------|------|---------|
| sort_by | string | `?sort_by=created_at` |
| sort_dir | string | `?sort_dir=desc` |
| page | integer | `?page=1` |
| per_page | integer | `?per_page=15` |

---

## **COMMON QUERY PATTERNS**

### Combining Multiple Filters
```
# Lost items reported in library, currently lost, in the last 7 days
GET /api/v1/lost-found?location=library&status=lost&date_from=2026-04-11&sort_by=created_at&sort_dir=desc

# Events next month at specific location
GET /api/v1/events?location=Auditorium&date_from=2026-05-01&date_to=2026-05-31&sort_by=start_time

# All Monday physics courses in room 5, sorted by time
GET /api/v1/schedule?q=physics&day=Monday&room_id=5&sort_by=start_time
```

### Pagination
```
# Get page 2 with 50 items per page
GET /api/v1/events?page=2&per_page=50

# Default is typically page 1, per_page varies by endpoint
```

### Sorting
```
# Ascending (oldest first)
GET /api/v1/lost-found?sort_by=created_at&sort_dir=asc

# Descending (newest first, default for many endpoints)
GET /api/v1/lost-found?sort_by=created_at&sort_dir=desc
```

---

## **IMPORTANT NOTES**

✅ **Authentication:**
- Most list endpoints are public (no auth needed)
- Personal filters (like user's own items) require auth
- Pass `Authorization: Bearer {token}` header when using authenticated endpoints

✅ **Date Format:**
- All dates must be in `YYYY-MM-DD` format (ISO 8601)
- Example: `2026-04-18`, not `04-18-2026` or `18/04/2026`

✅ **Special Characters:**
- URL encode spaces: `Hall A` → `Hall+A` or `Hall%20A`
- LIKE searches escape special chars automatically
- Search term `%` remains literal (not wildcard)

✅ **Performance:**
- Minimum search length: 2 characters (prevents huge result sets)
- Use `per_page` to limit results
- Pagination prevents timeout on large datasets
- Combine specific filters for better performance

✅ **Response Structure:**
```json
{
  "success": true,
  "data": [... results ...],
  "meta": {
    "from": 1,
    "to": 15,
    "total": 240,
    "path": "/api/v1/endpoint",
    "current_page": 1,
    "last_page": 16,
    "per_page": 15
  },
  "message": "..."
}
```

---

## **ERROR RESPONSES**

### Invalid Parameters
```json
{
  "success": false,
  "errors": {
    "date_from": ["The date from must be before date to."]
  }
}
```
Status: `422 Unprocessable Entity`

### Unauthorized
```json
{
  "message": "Unauthenticated."
}
```
Status: `401 Unauthorized`

---

## **QUICK REFERENCE TABLE**

| Endpoint | Search | Filters | Sort | Auth |
|----------|--------|---------|------|------|
| `/search` | Events, Buildings, Rooms, LostItems | - | latest | No |
| `/lost-found` | title, desc, location | status, location, date | created_at, title, status | Yes |
| `/events` | title, desc, location | location, date | start_time, end_time, title, created_at | No |
| `/buildings` | name, description | - | name, created_at | No |
| `/rooms` | room_number | building_id, floor | room_number, floor, created_at | No |
| `/rooms/search` | room_number | building_id, floor | room_number, floor, created_at | Yes |
| `/schedule` | course_name | day, room_id | course_name, day, start_time, created_at | No |
| `/news` | - | - | created_at, published_at | No |
| `/announcements` | - | - | created_at | No |

