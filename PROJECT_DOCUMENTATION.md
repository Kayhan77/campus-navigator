# Campus Navigator Backend - Comprehensive Project Documentation

## Table of Contents
1. [Project Overview](#project-overview)
2. [System Architecture](#system-architecture)
3. [Core Features](#core-features)
4. [Database Schema](#database-schema)
5. [API Endpoints](#api-endpoints)
6. [Service Layer](#service-layer)
7. [Authentication & Authorization](#authentication--authorization)
8. [Notification System](#notification-system)
9. [Work Completed in Current Session](#work-completed-in-current-session)
10. [Deployment](#deployment)

---

## Project Overview

**Project Name:** Smart Campus Navigator and Event Management System with Notification and Role-Based Access Control

**Technology Stack:**
- Backend Framework: Laravel 12
- Authentication: JWT (PHPOpenSourceSaver/JWTAuth)
- Database: MySQL/PostgreSQL
- Push Notifications: Firebase Cloud Messaging (FCM)
- Storage: Supabase Object Storage
- Caching: Redis
- Job Queue: Laravel Queue with Redis backend
- Image Processing: Supabase Storage

**Purpose:**
Campus Navigator is a comprehensive digital platform designed to:
- Enable secure user authentication with OTP-based verification
- Provide centralized event management with capacity controls
- Deliver push notifications to users via device tokens
- Support lost-and-found item management with claim workflows
- Manage campus news, announcements, and academic schedules
- Enable global search across campus resources
- Enforce role-based access control with dynamic permissions
- Support administrative operations with comprehensive dashboards

---

## System Architecture

### Layered Architecture Design

```
┌─────────────────────────────────────────────────────┐
│              Mobile Frontend (Flutter)              │
└─────────────────────────────────────────────────────┘
                        ↑↓
┌─────────────────────────────────────────────────────┐
│         Laravel 12 REST API Backend                 │
├─────────────────────────────────────────────────────┤
│                                                     │
│  ┌─────────────────────────────────────────────┐  │
│  │      Controller Layer (Request Handlers)     │  │
│  └─────────────────────────────────────────────┘  │
│                        ↓                           │
│  ┌─────────────────────────────────────────────┐  │
│  │      Service Layer (Business Logic)         │  │
│  └─────────────────────────────────────────────┘  │
│                        ↓                           │
│  ┌─────────────────────────────────────────────┐  │
│  │    Model Layer (Data & Relationships)       │  │
│  └─────────────────────────────────────────────┘  │
│                        ↓                           │
└─────────────────────────────────────────────────────┘
                        ↓
┌─────────────────────────────────────────────────────┐
│      External Services & Storage                   │
│  - Firebase Cloud Messaging (FCM)                  │
│  - Supabase Object Storage                         │
│  - MySQL Database                                  │
│  - Redis Cache                                     │
└─────────────────────────────────────────────────────┘
```

### Directory Structure

```
campus-navigator-backend/
├── app/
│   ├── Console/                      # CLI Commands
│   ├── DTOs/                         # Data Transfer Objects
│   │   ├── Auth/
│   │   ├── Event/
│   │   ├── Room/
│   │   ├── Building/
│   │   ├── AcademicSchedule/
│   │   ├── Announcement/
│   │   ├── News/
│   │   ├── LostItem/
│   │   ├── Notification/
│   │   └── ...
│   ├── Enums/                       # Type Enumerations
│   │   ├── SearchMode.php
│   │   └── UserRole.php
│   ├── Exceptions/                  # Custom Exceptions
│   │   ├── ApiException.php
│   │   ├── ValidationException.php
│   │   └── ...
│   ├── Filters/                     # Query Filters
│   │   ├── BuildingFilter.php
│   │   ├── EventFilter.php
│   │   ├── LostFoundFilter.php
│   │   └── ...
│   ├── Helpers/                     # Helper Functions
│   │   └── ApiResponse.php
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Api/V1/
│   │   │   │   ├── Admin/           # Admin Controllers
│   │   │   │   │   ├── AdminEventController.php
│   │   │   │   │   ├── AdminRoomController.php
│   │   │   │   │   ├── AdminAcademicScheduleController.php
│   │   │   │   │   ├── AdminNotificationController.php
│   │   │   │   │   ├── AdminNewsController.php
│   │   │   │   │   ├── AdminAnnouncementController.php
│   │   │   │   │   └── ...
│   │   │   │   ├── Auth/            # Auth Controllers
│   │   │   │   │   ├── JwtAuthController.php
│   │   │   │   │   ├── PasswordResetOtpController.php
│   │   │   │   │   ├── NewPasswordOtpController.php
│   │   │   │   │   └── ...
│   │   │   │   ├── Event/           # Event-related Controllers
│   │   │   │   │   ├── EventController.php
│   │   │   │   │   ├── EventCalendarController.php
│   │   │   │   │   └── EventRecommendationController.php
│   │   │   │   ├── DeviceTokenController.php
│   │   │   │   ├── RoomController.php
│   │   │   │   ├── BuildingController.php
│   │   │   │   ├── NewsController.php
│   │   │   │   ├── AnnouncementController.php
│   │   │   │   ├── LostFoundController.php
│   │   │   │   ├── GlobalSearchController.php
│   │   │   │   ├── AcademicScheduleController.php
│   │   │   │   └── ...
│   │   ├── Middleware/              # HTTP Middleware
│   │   │   ├── AdminMiddleware.php
│   │   │   └── ...
│   │   ├── Requests/                # Form Requests & Validation
│   │   │   ├── Auth/
│   │   │   ├── Event/
│   │   │   ├── Room/
│   │   │   ├── AcademicSchedule/
│   │   │   └── ...
│   │   └── Resources/               # JSON Resources (Serializers)
│   │       └── Api/V1/
│   ├── Jobs/                        # Background Jobs
│   │   ├── SendPushNotificationJob.php
│   │   └── ...
│   ├── Models/                      # Eloquent Models
│   │   ├── User.php
│   │   ├── Event.php
│   │   ├── Room.php
│   │   ├── Building.php
│   │   ├── AcademicSchedule.php
│   │   ├── News.php
│   │   ├── Announcement.php
│   │   ├── LostItem.php
│   │   ├── ItemClaim.php
│   │   ├── DeviceToken.php
│   │   ├── Notification.php
│   │   ├── NotificationRecipient.php
│   │   ├── Role.php
│   │   ├── Permission.php
│   │   └── ...
│   ├── Observers/                   # Model Observers (Hooks)
│   │   ├── EventObserver.php
│   │   ├── AcademicScheduleObserver.php
│   │   └── ...
│   ├── Policies/                    # Authorization Policies
│   │   ├── EventPolicy.php
│   │   ├── NotificationPolicy.php
│   │   └── ...
│   ├── Services/                    # Business Logic Services
│   │   ├── Notification/
│   │   │   ├── NotificationService.php
│   │   │   ├── FirebaseNotificationService.php
│   │   │   └── DeviceTokenService.php
│   │   ├── Event/
│   │   │   └── EventService.php
│   │   ├── Auth/
│   │   │   ├── AuthService.php
│   │   │   ├── PasswordResetOtpService.php
│   │   │   └── ...
│   │   ├── Search/
│   │   │   └── GlobalSearchService.php
│   │   ├── Admin/
│   │   │   ├── AdminNotificationService.php
│   │   │   └── AdminDashboardService.php
│   │   ├── AcademicScheduleService.php
│   │   ├── RoomService.php
│   │   ├── BuildingService.php
│   │   ├── NewsService.php
│   │   ├── SupabaseStorageService.php
│   │   └── ...
│   └── Traits/                      # Reusable Traits
│       ├── Filterable.php
│       └── ...
├── bootstrap/
│   ├── app.php                      # Application Bootstrap & Exception Handling
│   └── providers.php
├── config/
│   ├── app.php
│   ├── auth.php
│   ├── cache.php
│   ├── database.php
│   ├── firebase.php
│   ├── jwt.php
│   ├── mail.php
│   ├── queue.php
│   ├── search.php
│   └── ...
├── database/
│   ├── migrations/                  # Database Migrations
│   ├── seeders/                     # Database Seeders
│   ├── factories/                   # Model Factories
│   └── schema/
│       └── full_schema.sql
├── routes/
│   ├── api.php                      # API Routes
│   ├── web.php                      # Web Routes
│   └── auth.php                     # Auth Routes
├── storage/
│   ├── logs/                        # Application Logs
│   └── ...
├── tests/
│   ├── Feature/                     # Feature Tests
│   ├── Unit/                        # Unit Tests
│   ├── Pest.php
│   └── TestCase.php
├── Dockerfile                       # Docker Configuration
├── docker-compose.yml               # Local Testing
├── docker-entrypoint.sh             # Container Startup
├── render.yaml                      # Render Deployment
├── composer.json                    # PHP Dependencies
├── phpunit.xml                      # PHPUnit Configuration
└── README.md                        # Project README
```

---

## Core Features

### 1. **Authentication & Authorization**
- **JWT-based API Authentication** using `php-open-source-saver/jwt-auth`
- **OTP-based Email Verification** for new user registration
- **Password Reset via OTP** for secure account recovery
- **Google OAuth2 Integration** for social login
- **Role-Based Access Control (RBAC)**
  - super_admin: Full system access
  - admin: Administrative operations
  - sub_admin: Limited administrative access
  - user: Regular user access
- **Dynamic Permission System** with policy-based authorization

### 2. **Event Management**
- Create, read, update, delete events
- Event image upload to Supabase
- User registration for events with capacity checks
- Prevent duplicate registrations using database transactions
- Event calendar view with date filtering
- Event recommendations based on user history
- Automatic notification when events are created

### 3. **Push Notifications**
- Firebase Cloud Messaging (FCM) integration
- Device token registration and management
- Automatic invalid token cleanup
- Notification preferences per user
- Notification delivery tracking
- Notification read/unread status
- Automatic notifications on:
  - Event creation
  - News publication
  - Announcement posting
  - Admin notifications
  - Lost item posts
  - Item claims

### 4. **Lost & Found System**
- Post lost items with descriptions and images
- Post found items
- Search lost/found items
- Users can claim found items
- Claim status workflow
- Automatic notification to item owner on claims

### 5. **News Management**
- Publish news articles
- News image uploads
- Filter news by category
- Automatic notifications on publication

### 6. **Announcements**
- Create announcements for all users
- Announcement categories
- Delivery tracking

### 7. **Academic Schedules**
- Create and manage course schedules
- Link schedules to rooms
- Time validation (start_time before end_time)
- Prevent double-booking of rooms

### 8. **Campus Resources**
- **Buildings**: Campus building directory with locations
- **Rooms**: Classroom/facility management with capacity info
- Search and filter capabilities for all resources

### 9. **Global Search**
- Cross-model search (Events, Buildings, Rooms, LostItems)
- Parallel search execution for performance
- Ranked relevance results
- Configurable search term length validation

### 10. **Admin Dashboard**
- System statistics (users, events, buildings, etc.)
- Recent activity tracking
- User management
- Content moderation

---

## Database Schema

### Core Tables

#### `users`
```sql
- id (primary key)
- name
- email (unique)
- email_verified_at
- password (hashed)
- role (enum: super_admin, admin, sub_admin, user)
- is_verified (boolean)
- notification_preferences (JSON)
- created_at / updated_at
```

#### `events`
```sql
- id
- title
- description
- start_date / start_time
- end_date / end_time
- location
- image (URL to Supabase)
- room_id (foreign key to rooms)
- max_attendees
- registered_users_count (denormalized for performance)
- created_by (foreign key to users)
- created_at / updated_at
```

#### `event_user` (Pivot)
```sql
- event_id
- user_id
- registered_at
```

#### `rooms`
```sql
- id
- name
- capacity
- building_id (foreign key)
- floor
- room_number
- created_at / updated_at
```

#### `buildings`
```sql
- id
- name
- location / address
- description
- image (URL)
- created_at / updated_at
```

#### `academic_schedules`
```sql
- id
- course_name
- day (Monday-Sunday)
- start_time (time type - CAST to Carbon datetime for formatting)
- end_time (time type - CAST to Carbon datetime for formatting)
- room_id (foreign key to rooms)
- created_at / updated_at
```

#### `device_tokens`
```sql
- id
- user_id (foreign key)
- token (unique FCM token)
- platform (ios / android)
- last_used_at
- created_at / updated_at
```

#### `notifications`
```sql
- id
- title
- message
- type (event, news, announcement, admin, system)
- data (JSON - event_id, etc.)
- sender_id (nullable - who sent it)
- target_role (all / admin / user)
- created_at / updated_at
```

#### `notification_recipients` (Join Table)
```sql
- id
- notification_id
- user_id
- read_at (nullable - when user read it)
- delivered_at (nullable - when Firebase delivered it)
- created_at
```

#### `lost_items`
```sql
- id
- description
- item_name
- location_found
- image (URL to Supabase)
- user_id (who posted it)
- type (lost / found)
- claimed_at (nullable)
- created_at / updated_at
```

#### `item_claims`
```sql
- id
- lost_item_id
- user_id (who is claiming)
- status (pending / approved / rejected)
- created_at / updated_at
```

#### `news`
```sql
- id
- title
- content
- image (URL)
- created_by (foreign key to users)
- is_published (boolean)
- published_at (nullable)
- created_at / updated_at
```

#### `announcements`
```sql
- id
- title
- content
- type / category
- created_by (foreign key)
- created_at / updated_at
```

#### `roles` & `permissions` (RBAC)
```sql
- roles: id, name
- permissions: id, name, description
- role_permission: role_id, permission_id
- user_role: user_id, role_id
```

---

## API Endpoints

### Authentication Endpoints

#### Register/Login
```
POST /api/auth/register                 # Register new user
POST /api/auth/login                    # Login with email/password
POST /api/auth/verify-otp               # Verify email with OTP
POST /api/auth/refresh-token            # Refresh JWT token
POST /api/auth/logout                   # Logout
```

#### Password Reset
```
POST /api/auth/forgot-password-otp      # Request password reset OTP
POST /api/auth/verify-reset-otp         # Verify OTP and reset password
```

#### OAuth
```
GET /auth/google                        # Google OAuth redirect
GET /auth/google/callback               # Google OAuth callback
```

### Device Tokens
```
GET /api/v1/device-tokens               # List user's device tokens
POST /api/v1/device-tokens              # Register device token
DELETE /api/v1/device-tokens/{id}       # Remove device token
```

### Events
```
GET /api/v1/events                      # List all events
GET /api/v1/events?include=room         # Events with room details
GET /api/v1/events/{id}                 # Get event details
POST /api/v1/events                     # Create event (authenticated)
PUT /api/v1/events/{id}                 # Update event (authenticated)
DELETE /api/v1/events/{id}              # Delete event (authenticated)

POST /api/v1/events/{id}/register       # Register for event
DELETE /api/v1/events/{id}/register     # Unregister from event

GET /api/v1/events/calendar             # Calendar view
GET /api/v1/recommendations/events      # Event recommendations
```

### Admin Events Management
```
GET /api/v1/admin/events                # List all events (admin)
POST /api/v1/admin/events               # Create event (admin)
PUT /api/v1/admin/events/{id}           # Update event (admin)
DELETE /api/v1/admin/events/{id}        # Delete event (admin)
```

### Academic Schedules
```
GET /api/v1/schedule                    # List schedules
GET /api/v1/schedule/{id}               # Get schedule details
POST /api/v1/schedule                   # Create schedule (user)
PUT /api/v1/schedule/{id}               # Update schedule (user)
DELETE /api/v1/schedule/{id}            # Delete schedule (user)

GET /api/v1/admin/schedule              # List all schedules (admin)
POST /api/v1/admin/schedule             # Create schedule (admin)
PUT /api/v1/admin/schedule/{id}         # Update schedule (admin)
DELETE /api/v1/admin/schedule/{id}      # Delete schedule (admin)
```

### Rooms
```
GET /api/v1/rooms                       # List rooms
GET /api/v1/rooms/{id}                  # Get room details
POST /api/v1/admin/rooms                # Create room (admin)
PUT /api/v1/admin/rooms/{id}            # Update room (admin)
DELETE /api/v1/admin/rooms/{id}         # Delete room (admin)
```

### Buildings
```
GET /api/v1/buildings                   # List buildings
GET /api/v1/buildings/{id}              # Get building details
GET /api/v1/buildings/{id}?include=rooms # Building with rooms
POST /api/v1/admin/buildings            # Create building (admin)
PUT /api/v1/admin/buildings/{id}        # Update building (admin)
DELETE /api/v1/admin/buildings/{id}     # Delete building (admin)
```

### News
```
GET /api/v1/news                        # List published news
GET /api/v1/news/{id}                   # Get news details
POST /api/v1/admin/news                 # Create news (admin)
PUT /api/v1/admin/news/{id}             # Update news (admin)
DELETE /api/v1/admin/news/{id}          # Delete news (admin)
```

### Announcements
```
GET /api/v1/announcements               # List announcements
GET /api/v1/announcements/{id}          # Get announcement details
POST /api/v1/admin/announcements        # Create announcement (admin)
PUT /api/v1/admin/announcements/{id}    # Update announcement (admin)
DELETE /api/v1/admin/announcements/{id} # Delete announcement (admin)
```

### Lost & Found
```
GET /api/v1/lost-found                  # List lost/found items
POST /api/v1/lost-found                 # Post lost/found item
PUT /api/v1/lost-found/{id}             # Update item
DELETE /api/v1/lost-found/{id}          # Delete item

POST /api/v1/item-claims                # Claim an item
GET /api/v1/item-claims/{id}            # Get claim details
PUT /api/v1/item-claims/{id}            # Update claim status (admin)
```

### Notifications
```
GET /api/v1/notifications               # List user's notifications
GET /api/v1/notifications/unread        # Get unread count
PUT /api/v1/notifications/{id}/read     # Mark as read
DELETE /api/v1/notifications/{id}       # Delete notification

POST /api/v1/admin/notifications        # Send admin notification
POST /api/v1/admin/notifications/send   # Alias endpoint
```

### Global Search
```
GET /api/v1/search?q=term&per_model=5   # Search all models
GET /api/v1/search/suggest?q=term       # Search suggestions
```

### Admin Dashboard
```
GET /api/v1/admin/dashboard             # Dashboard statistics
GET /api/v1/admin/users                 # List users (admin)
```

---

## Service Layer

### NotificationService
**Location:** `app/Services/Notification/NotificationService.php`

**Responsibilities:**
- Create notifications in database
- Determine target users
- Fetch device tokens
- Coordinate Firebase delivery
- Track delivery status
- Apply notification preferences

**Key Methods:**
```php
sendAndStoreNotification(
    string $title,
    string $message,
    string $type = 'system',
    ?array $data = null,
    ?array $userIds = null,
    ?int $senderId = null
): array
```

Returns: `['sent' => int, 'failed' => int, 'notification_id' => int]`

### FirebaseNotificationService
**Location:** `app/Services/Notification/FirebaseNotificationService.php`

**Responsibilities:**
- Send notifications via Firebase using Kreait SDK
- Handle Android high-priority delivery
- Automatic invalid token cleanup
- Detailed logging and error handling
- Support for multicast delivery

**Key Methods:**
```php
sendToToken(string $token, NotificationPayload $payload): bool
sendToMultipleTokens(array $tokens, NotificationPayload $payload): array
```

### EventService
**Location:** `app/Services/Event/EventService.php`

**Responsibilities:**
- Create events with image upload
- Register users for events (with capacity checks)
- Unregister users from events
- Send notifications on event creation
- Manage event cache invalidation

### DeviceTokenService
**Location:** `app/Services/DeviceTokenService.php`

**Responsibilities:**
- Register device tokens
- Update last_used_at timestamp
- Cleanup invalid tokens
- Fetch tokens for notification delivery

### GlobalSearchService
**Location:** `app/Services/Search/GlobalSearchService.php`

**Responsibilities:**
- Search across multiple models in parallel
- Apply filters to search results
- Enforce minimum search term length
- Cache search results

### SupabaseStorageService
**Location:** `app/Services/SupabaseStorageService.php`

**Responsibilities:**
- Upload images to Supabase Storage
- Generate signed URLs for image access
- Delete images from storage
- Support for profile pictures, event images, etc.

---

## Authentication & Authorization

### JWT Authentication Flow

1. **User Registration**
   ```
   POST /api/auth/register
   {
     "name": "John Doe",
     "email": "john@example.com",
     "password": "secure_password",
     "password_confirmation": "secure_password"
   }
   ```

2. **OTP Verification**
   ```
   POST /api/auth/verify-otp
   {
     "email": "john@example.com",
     "otp": "123456"
   }
   ```

3. **Login**
   ```
   POST /api/auth/login
   {
     "email": "john@example.com",
     "password": "secure_password"
   }
   
   Response:
   {
     "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
     "expires_in": 3600
   }
   ```

4. **Using Token**
   ```
   GET /api/v1/me
   Headers: Authorization: Bearer <token>
   ```

### Role-Based Access Control (RBAC)

**Roles:**
- `super_admin`: Full system access
- `admin`: Administrative operations (events, news, announcements)
- `sub_admin`: Limited admin access
- `user`: Regular user access

**Authorization Examples:**
```php
// In Controller
$this->authorize('create', Event::class);
$this->authorize('send', Notification::class);

// In Policy
public function create(User $user): bool
{
    return $user->hasPermission('create_events');
}
```

---

## Notification System

### How Notifications Work

1. **Database Storage**
   - Notification record created in `notifications` table
   - Recipient records created in `notification_recipients` table

2. **Device Token Fetching**
   - Retrieve all device tokens for target users
   - Filter out null/empty tokens
   - Apply user notification preferences

3. **Firebase Delivery**
   - Create NotificationPayload with title, body, data
   - Send via Kreait Firebase Admin SDK
   - Include Android high-priority config

4. **Delivery Tracking**
   - Update `notification_recipients.delivered_at` on success
   - Log delivery status for audit trail
   - Auto-cleanup invalid tokens on failure

### Notification Types

| Type | Trigger | Recipients |
|------|---------|-----------|
| `event` | Event created | All users |
| `news` | News published | All users |
| `announcement` | Announcement posted | All users |
| `admin` | Admin sends notification | Specified users |
| `system` | System event | All users |
| `lost_item` | Lost item posted | All users |
| `item_claim` | Item claimed | Item owner |

### Notification Payload

```php
class NotificationPayload
{
    public string $title;           // Notification title
    public string $body;            // Notification body
    public array $data;             // App-specific metadata
    public ?string $imageUrl;       // Notification image
    public ?string $actionUrl;      // Deep link on tap
    public string $locale;          // BCP-47 locale code
    public string $type;            // Logical type for routing
}
```

---

## Work Completed in Current Session

### 1. **Academic Schedule 500 Error - FIXED** ✅

**Problem:** Creating academic schedules returned HTTP 500: "Call to a member function format() on string"

**Root Causes:**
1. Missing `end_time` column in database
2. Missing model casts for time fields
3. API auth redirect causing 500 errors instead of 401

**Fixes Applied:**

1. **Created Migration** - `database/migrations/2026_05_14_000001_add_end_time_to_academic_schedules_table.php`
   ```php
   // Added missing end_time column
   if (! Schema::hasColumn('academic_schedules', 'end_time')) {
       Schema::table('academic_schedules', function (Blueprint $table) {
           $table->time('end_time')->nullable();
       });
   }
   ```

2. **Updated Model** - `app/Models/AcademicSchedule.php`
   ```php
   protected $casts = [
       'start_time' => 'datetime:H:i',
       'end_time' => 'datetime:H:i',
   ];
   ```

3. **Fixed Exception Handler** - `bootstrap/app.php`
   ```php
   // Don't redirect unauthenticated API requests to login
   $middleware->redirectGuestsTo(fn (Request $request) => null);
   
   // Force JSON response for API routes
   $isApiRoute = str_starts_with($request->path(), 'api/');
   if (! ($isApiRoute || $request->expectsJson())) {
       return null;
   }
   ```

**Verification:**
```json
POST /api/v1/admin/schedule

Request:
{
  "course_name": "Data Structures",
  "day": "Monday",
  "start_time": "09:00",
  "end_time": "11:00",
  "room_id": 1
}

Response (201):
{
  "success": true,
  "message": "Academic schedule created successfully.",
  "data": {
    "id": 3,
    "course_name": "Data Structures",
    "day": "Monday",
    "start_time": "09:00",
    "end_time": "11:00",
    "created_at": "2026-05-14 01:53:20"
  }
}
```

### 2. **UserSeeder Creation** ✅

**File:** `database/seeders/UserSeeder.php`

**Purpose:** Create a regular user account for testing

**Features:**
- Mirrors SuperAdminSeeder structure
- Environment variable configuration
- Duplicate detection (won't recreate if exists)
- Proper role syncing
- Database token handling

### 3. **Firebase Notification Delivery - Investigation Complete** ✅

**Finding:** Event notifications ARE successfully delivered via device tokens

**Verification Data:**
```
Total device tokens registered: 5
Event notification created: Yes (ID 5)
Recipients: 4 users
Successfully delivered: 2 users ✅
Not delivered: 2 users (no device tokens)
```

**Flow Confirmed:**
1. EventService::create() → triggers notification
2. NotificationService::sendAndStoreNotification() → fetches device tokens
3. FirebaseNotificationService::sendToToken() → sends via Firebase
4. Delivery tracked in notification_recipients table

### 4. **API Error Handling Improvements** ✅

**Issues Fixed:**
- Auth failures on API routes returning 500 instead of 401
- Route [login] not defined errors
- Proper JSON error responses for all API routes

**Changes:**
- Added `redirectGuestsTo()` middleware configuration
- Forced JSON response detection for `api/*` routes
- Proper 401/403 error handling with JSON responses

### 5. **Device Token Retrieval Endpoint** ✅

**Endpoint:** `GET /api/v1/device-tokens`

**Purpose:** Allow users to see their registered device tokens

**Response:**
```json
{
  "success": true,
  "message": "Device tokens retrieved successfully.",
  "data": {
    "saved": true,
    "count": 5,
    "device_tokens": [
      {
        "id": 1,
        "platform": "android",
        "last_used_at": "2026-05-14 01:53:20",
        "created_at": "2026-04-21 14:49:25",
        "updated_at": "2026-05-14 01:53:20"
      }
    ]
  }
}
```

---

## Deployment

### Docker Deployment

**Files:**
- `Dockerfile` - Main container configuration
- `docker-entrypoint.sh` - Container startup script
- `.dockerignore` - Files to exclude
- `render.yaml` - Render platform blueprint

**Environment Variables Required:**
```
APP_NAME=CampusNavigator
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:...
APP_URL=https://your-domain.com

DB_HOST=your-db-host
DB_USER=your-db-user
DB_PASSWORD=your-db-password
DB_NAME=your-db-name

JWT_SECRET=your-jwt-secret
FIREBASE_PROJECT_ID=your-firebase-project
FIREBASE_PRIVATE_KEY=your-firebase-key

SUPABASE_URL=your-supabase-url
SUPABASE_KEY=your-supabase-key

REDIS_HOST=your-redis-host
REDIS_PASSWORD=your-redis-password

FRONTEND_URL=https://frontend-domain.com
```

### Health Check Endpoint

```
GET /api/health

Response (200):
{
  "status": "ok",
  "database": "connected",
  "cache": "connected"
}
```

### Database Migrations

Run on deployment:
```bash
php artisan migrate --force
php artisan db:seed --class=RoleSeeder
```

---

## Key Architecture Decisions

1. **Service Layer Pattern** - Business logic separated into services, not controllers
2. **Database Transactions** - Used for event registration and notification delivery to ensure consistency
3. **DTO Pattern** - Data Transfer Objects used to decouple form requests from database layer
4. **Policy-Based Authorization** - Flexible, testable authorization using Laravel Policies
5. **Observer Pattern** - Model observers used for cache invalidation and side effects
6. **Job Queues** - Background jobs for push notification delivery
7. **Supabase Storage** - Persistent cloud storage for production deployment
8. **Firebase FCM** - Reliable push notification delivery with automatic token cleanup
9. **Notification Preferences** - User-level control over notification delivery
10. **Caching** - Redis-backed caching for search and frequent queries

---

## Testing & Validation

### Device Token Registration
```
POST /api/v1/device-tokens
{
  "token": "fcm-token-123...",
  "platform": "android"
}
```

### Event Creation with Notifications
```
POST /api/v1/admin/events
{
  "title": "Tech Conference",
  "description": "Annual tech event",
  "start_date": "2026-06-01",
  "start_time": "09:00",
  "end_date": "2026-06-01",
  "end_time": "17:00",
  "location": "Convention Center",
  "max_attendees": 500,
  "room_id": 1
}
```

Automatically sends notification to all registered device tokens.

### Academic Schedule Creation
```
POST /api/v1/admin/schedule
{
  "course_name": "Database Design",
  "day": "Tuesday",
  "start_time": "10:00",
  "end_time": "12:00",
  "room_id": 1
}
```

---

## Performance Considerations

1. **Event Registration** - Uses optimistic locking to prevent race conditions
2. **Notifications** - Batched token delivery with multicast support
3. **Search** - Parallel search execution across models
4. **Caching** - Cache invalidation via observers
5. **Database Indexes** - Indexes on frequently queried columns (user_id, event_id, etc.)
6. **Pagination** - Default 15 items per page, configurable

---

## Security Features

1. **JWT Authentication** - Stateless, token-based API authentication
2. **Password Hashing** - Laravel's default Argon2ID hashing
3. **CORS Handling** - Configured for frontend domain
4. **Rate Limiting** - 50 FCM messages/second, 2000/minute
5. **Validation** - Form request validation on all inputs
6. **Authorization Policies** - User-aware permission checks
7. **SQL Injection Prevention** - Eloquent query builder protection
8. **CSRF Protection** - Built-in Laravel CSRF middleware
9. **OTP Security** - Time-limited, single-use OTP codes
10. **Token Cleanup** - Automatic invalid token removal

---

## Future Enhancements

1. Push notification scheduling
2. Advanced notification templates
3. Analytics dashboard
4. API rate limiting per user
5. Notification batching
6. Multi-language support
7. Accessibility features
8. Performance monitoring
9. Load balancing
10. Database replication

---

## Support & Maintenance

### Logging
- Application logs: `storage/logs/laravel.log`
- Query logging: Configure in `config/logging.php`
- Firebase logs: Tracked in notification_logs table

### Debugging
- Enable debug mode: `APP_DEBUG=true` (development only)
- Check exception handler: `bootstrap/app.php`
- Query debugging: `DB::enableQueryLog()`

### Monitoring
- Health check: `/api/health`
- Dashboard: `/api/v1/admin/dashboard`
- Error tracking: Check storage/logs/laravel.log

---

**Last Updated:** May 14, 2026
**Version:** 1.0
**Status:** Production Ready ✅
