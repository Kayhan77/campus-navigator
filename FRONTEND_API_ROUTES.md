# Frontend API Routes

This document lists the HTTP routes exposed in `routes/api.php` for frontend integration.

## Base URL

- All routes in this file are served under the API prefix: `/api`.
- Versioned routes use the `/api/v1` prefix.
- Authenticated routes require `Authorization: Bearer <jwt>`.
- Admin routes require both `auth:api` and `admin` middleware.

## Health and debug (non-versioned)

- `GET /api/health` - Health check.
- `GET /api/test-groq` - Test Groq API connectivity.
- `GET /api/test-connection` - Test SMTP connection.
- `GET /api/test-mail` - Send a test email.
- `POST /api/test-supabase/upload-image` - Upload an image to Supabase Storage (multipart `image`, optional `bucket`).
- `GET /api/test-supabase/image/{path}?bucket=...` - Fetch image bytes from Supabase Storage.
- `POST /api/debug/clean-registrations` - Truncate pending registrations and OTP tables.
- `GET /api/test-fcm` - Send a test push notification (auth required).

Note: These endpoints are intended for debugging and should be gated or disabled in production.

## Public routes (v1)

### Auth

- `GET /api/v1/auth/google` - Start Google OAuth.
- `GET /api/v1/auth/google/callback` - Google OAuth callback.
- `POST /api/v1/pre-register` - Start pre-registration.
- `POST /api/v1/verify-otp` - Verify registration OTP.
- `POST /api/v1/resend-otp` - Resend OTP (throttle: 5/min).
- `POST /api/v1/login` - Login and obtain JWT.
- `POST /api/v1/forgot-password` - Send password reset OTP (throttle: 5/min).
- `POST /api/v1/reset-password` - Reset password with OTP.

### Buildings

- `GET /api/v1/buildings` - List buildings.
- `GET /api/v1/buildings/{building}` - Get building details.

### Rooms

- `GET /api/v1/rooms` - List rooms.
- `GET /api/v1/rooms/{room}` - Get room details.

### Events

- `GET /api/v1/events` - List events.
- `GET /api/v1/events/{event}` - Get event details.
- `POST /api/v1/events/recommendations` - Event recommendations.
- `GET /api/v1/calendar/events` - Calendar events.

### News

- `GET /api/v1/news` - List news.
- `GET /api/v1/news/{news}` - Get news details.

### Announcements

- `GET /api/v1/announcements` - List announcements.
- `GET /api/v1/announcements/{announcement}` - Get announcement details.

### Academic schedule

- `GET /api/v1/schedule` - List schedule entries.
- `GET /api/v1/schedule/{academicSchedule}` - Get schedule entry details.

### Global search

- `GET /api/v1/search` - Cross-model search.
- `GET /api/v1/search/suggestions` - Search suggestions.

## Authenticated routes (v1)

### Session

- `GET /api/v1/me` - Get current user.
- `POST /api/v1/logout` - Logout.
- `POST /api/v1/refresh` - Refresh JWT.

### Event registration

- `POST /api/v1/events/{event}/register` - Register for an event.
- `DELETE /api/v1/events/{event}/register` - Unregister from an event.

### Lost and found

- `GET /api/v1/lost-found` - List lost items.
- `POST /api/v1/lost-found` - Create a lost item.

### Item claims

- `POST /api/v1/item-claims` - Create a claim.
- `GET /api/v1/lost-found/{lostItem}/claims` - List claims for a lost item.
- `PATCH /api/v1/item-claims/{claim}/accept` - Accept a claim.
- `PATCH /api/v1/item-claims/{claim}/reject` - Reject a claim.

### Device tokens

- `POST /api/v1/device-tokens` - Register a device token (throttle: 10/min).
- `DELETE /api/v1/device-tokens` - Remove a device token (throttle: 10/min).

### Notification preferences

- `GET /api/v1/notification-preferences` - Get preferences.
- `PATCH /api/v1/notification-preferences` - Update preferences.
- `DELETE /api/v1/notification-preferences` - Delete preferences.

### Notifications

- `GET /api/v1/notifications` - List notifications.
- `GET /api/v1/notifications/unread-count` - Get unread count.
- `GET /api/v1/notifications/{notification}` - Get notification.
- `PATCH /api/v1/notifications/{notification}/read` - Mark as read.
- `POST /api/v1/notifications/mark-all-as-read` - Mark all as read.

## Admin routes (v1/admin)

### Dashboard

- `GET /api/v1/admin/dashboard` - Admin dashboard summary.

### Users and roles

- `GET /api/v1/admin/users` - List users.
- `GET /api/v1/admin/users/{user}` - Get user details.
- `PATCH /api/v1/admin/users/{user}/role` - Update user role.
- `POST /api/v1/admin/users/{user}/assign-role` - Assign RBAC role (super_admin).
- `PUT /api/v1/admin/roles/{role}/permissions` - Sync role permissions (super_admin).

### Events

- `GET /api/v1/admin/events` - List events.
- `GET /api/v1/admin/events/{event}` - Get event.
- `POST /api/v1/admin/events` - Create event.
- `PUT /api/v1/admin/events/{event}` - Update event.
- `DELETE /api/v1/admin/events/{event}` - Delete event.

### Buildings

- `GET /api/v1/admin/buildings` - List buildings.
- `GET /api/v1/admin/buildings/{building}` - Get building.
- `POST /api/v1/admin/buildings` - Create building.
- `PUT /api/v1/admin/buildings/{building}` - Update building.
- `DELETE /api/v1/admin/buildings/{building}` - Delete building.

### Rooms

- `GET /api/v1/admin/rooms` - List rooms.
- `GET /api/v1/admin/rooms/{room}` - Get room.
- `POST /api/v1/admin/rooms` - Create room.
- `PUT /api/v1/admin/rooms/{room}` - Update room.
- `DELETE /api/v1/admin/rooms/{room}` - Delete room.

### Academic schedule

- `GET /api/v1/admin/schedule` - List schedule entries.
- `GET /api/v1/admin/schedule/{academicSchedule}` - Get schedule entry.
- `POST /api/v1/admin/schedule` - Create schedule entry.
- `PUT /api/v1/admin/schedule/{academicSchedule}` - Update schedule entry.
- `DELETE /api/v1/admin/schedule/{academicSchedule}` - Delete schedule entry.

### Announcements

- `GET /api/v1/admin/announcements` - List announcements.
- `POST /api/v1/admin/announcements` - Create announcement.
- `GET /api/v1/admin/announcements/{announcement}` - Get announcement.
- `PUT /api/v1/admin/announcements/{announcement}` - Update announcement.
- `PATCH /api/v1/admin/announcements/{announcement}` - Update announcement.
- `DELETE /api/v1/admin/announcements/{announcement}` - Delete announcement.

### News

- `GET /api/v1/admin/news` - List news.
- `POST /api/v1/admin/news` - Create news.
- `GET /api/v1/admin/news/{news}` - Get news.
- `PUT /api/v1/admin/news/{news}` - Update news.
- `PATCH /api/v1/admin/news/{news}` - Update news.
- `DELETE /api/v1/admin/news/{news}` - Delete news.

### Admin notifications

- `POST /api/v1/admin/notifications` - Send a notification.
- `POST /api/v1/admin/notifications/send` - Send a notification (alias).
