# Admin Notification System Documentation

## Overview

The Admin Notification System is a production-ready feature that allows admin users to send push notifications to campus users via Firebase Cloud Messaging (FCM). The system supports sending notifications to all users or to a specific subset of users.

## Architecture

### Components

1. **Form Request** (`app/Http/Requests/Admin/SendAdminNotificationRequest.php`)
   - Validates incoming request data
   - Ensures title and body are present and properly formatted
   - Validates user IDs against the users table

2. **Controller** (`app/Http/Controllers/Api/V1/Admin/AdminNotificationController.php`)
   - Thin controller layer
   - Validates request using Form Request
   - Delegates business logic to Service

3. **Service** (`app/Services/Admin/AdminNotificationService.php`)
   - Main business logic implementation
   - Fetches device tokens from `device_tokens` table
   - Sends push notifications via existing `FirebaseService`
   - Handles invalid tokens by removing them from database
   - Tracks success and failure counts
   - Logs all errors

## API Endpoint

### POST `/api/v1/admin/notifications/send`

**Authentication:** Required (admin middleware)

**Request Body:**
```json
{
  "title": "Campus Event Update",
  "body": "A new event has been scheduled in the library",
  "user_ids": [1, 5, 10]
}
```

**Parameters:**
- `title` (required, string, max 255): The notification title
- `body` (required, string): The notification body
- `user_ids` (optional, array): Array of user IDs to send to. If omitted or empty, sends to all users.
- `user_ids.*` (optional, integer): Must exist in users table

**Success Response (200):**
```json
{
  "success": true,
  "message": "Notification sent successfully.",
  "data": {
    "sent": 42,
    "failed": 3
  }
}
```

**Error Response (400+):**
```json
{
  "success": false,
  "message": "Validation failed.",
  "errors": {
    "title": ["The title field is required."],
    "body": ["The body field is required."]
  }
}
```

## Implementation Details

### Request Validation

The `SendAdminNotificationRequest` validates:
- **title**: Required, string, max 255 characters
- **body**: Required, string (no length limit specified in requirements)
- **user_ids**: Optional array of valid user IDs

### Token Fetching

The service fetches device tokens with these criteria:
```php
DeviceToken::query()
    ->whereIn('user_id', $userIds)  // Optional: if user_ids provided
    ->whereNotNull('token')
    ->where('token', '!=', '')
    ->get(['token', 'user_id']);
```

**Behavior:**
- If `user_ids` is provided: Only tokens for those users
- If `user_ids` is null/empty: All tokens from all users
- Always excludes null or empty token values

### Notification Payload

Each notification sent includes:
```php
[
  'title' => $title,
  'body' => $body,
  'data' => [
    'type' => 'admin_notification'
  ]
]
```

### Error Handling

The system handles two types of errors:

#### Invalid Token Detection

If Firebase returns an error matching these codes, the token is automatically deleted:
- `registration-token-not-registered`
- `invalid-argument`
- `mismatched-sender-id`
- `message-rate-exceeded`

#### Error Flow
```
1. Send notification to token
2. If exception occurs:
   - Increment failure counter
   - Log error with token and user_id
   - Check if error indicates invalid token
   - If yes: Delete token from database and log deletion
   - Continue to next token (no crash)
```

### Logging

All errors are logged to `laravel.log`:
```php
Log::error('Admin notification failed', [
    'token' => $deviceToken->token,
    'user_id' => $deviceToken->user_id,
    'error' => $e->getMessage(),
]);

Log::warning('Deleted invalid device token', [
    'token' => $token,
    'error' => $errorMessage,
]);
```

### Response Format

The endpoint returns a structured response with counts:
```php
[
    'sent' => 42,      // Number of successful sends
    'failed' => 3      // Number of failed sends
]
```

**Important:** The HTTP status code is always 200 for successful request validation. Individual notification failures do not affect the HTTP status.

## Usage Examples

### Send to All Users
```bash
curl -X POST http://localhost:8000/api/v1/admin/notifications/send \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Maintenance Notification",
    "body": "The campus map will be updated tomorrow at 2 PM"
  }'
```

### Send to Specific Users
```bash
curl -X POST http://localhost:8000/api/v1/admin/notifications/send \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Event Cancelled",
    "body": "The programming workshop scheduled for today has been postponed",
    "user_ids": [5, 10, 15, 20]
  }'
```

### Postman Collection

**Request Name:** Send Admin Notification

**Method:** POST

**URL:** `{{BASE_URL}}/api/v1/admin/notifications/send`

**Headers:**
```
Authorization: Bearer {{ACCESS_TOKEN}}
Content-Type: application/json
```

**Body (JSON):**
```json
{
  "title": "Campus Tour Announcement",
  "body": "The campus tour for new students will be held next Saturday at 10 AM",
  "user_ids": []
}
```

## Security Considerations

### Authorization
- The endpoint is protected by the `admin` middleware
- Only users with admin role can send notifications
- Authentication is required (JWT token)

### Input Validation
- Title is limited to 255 characters (reasonable for notifications)
- Body has no hard limit but Firebase may have limits
- User IDs are validated against the database to prevent sending to non-existent users

### Data Protection
- No sensitive user data is exposed in responses
- Only counts and status are returned
- Errors are logged server-side, not exposed to client

## Integration Points

### Dependencies
- **FirebaseService** (`app/Services/FirebaseService.php`): Existing service for FCM integration
- **DeviceTokenService** (`app/Services/DeviceTokenService.php`): Manages device tokens
- **DeviceToken Model** (`app/Models/DeviceToken.php`): Database table for storing tokens
- **User Model** (`app/Models/User.php`): For validating user IDs

### Database Tables
- **device_tokens** table: Contains columns (user_id, token, platform, last_used_at)
- **users** table: For validating user_ids in request

### Environment Configuration
No additional environment variables are needed. Uses existing Firebase configuration:
- `FIREBASE_PROJECT_ID`
- `FIREBASE_CREDENTIALS` or `GOOGLE_APPLICATION_CREDENTIALS`

## Testing

### Test Cases

1. **Send to all users (no user_ids)**
   ```php
   // Should send to all users with valid tokens
   ```

2. **Send to specific users**
   ```php
   // Should send only to specified user IDs
   ```

3. **Invalid user IDs**
   ```php
   // Request should fail validation
   ```

4. **Empty user_ids array**
   ```php
   // Should send to all users (same as omitting the field)
   ```

5. **Some tokens fail, others succeed**
   ```php
   // Response should show both sent and failed counts
   ```

### Example Test

```php
public function test_admin_can_send_notification_to_all_users()
{
    $admin = User::factory()->admin()->create();
    
    $token = createJwtToken($admin);
    
    $response = $this->withHeaders([
        'Authorization' => "Bearer $token"
    ])->postJson('/api/v1/admin/notifications/send', [
        'title' => 'Test Notification',
        'body' => 'This is a test notification',
    ]);
    
    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'data' => [
                'sent' => intval(),
                'failed' => intval(),
            ]
        ]);
}
```

## Monitoring & Maintenance

### Monitoring Checklist
- Monitor `laravel.log` for `Admin notification failed` errors
- Track `Deleted invalid device token` entries to identify problematic tokens
- Check Firebase quota usage (~500K notifications/min for standard Firebase)

### Maintenance Tasks
- Periodically review deleted tokens (may indicate platform issues)
- Clean up old `device_tokens` records for inactive users
- Monitor Firebase credentials expiration

## Troubleshooting

### Issue: No notifications received
1. Check if tokens exist in device_tokens table
2. Verify Firebase credentials are valid
3. Check Firebase project ID is correct
4. Review laravel.log for errors

### Issue: All notifications fail
1. Verify all tokens are invalid: Likely Firebase credential issue
2. Check Firebase service key permissions
3. Verify FIREBASE_PROJECT_ID is set correctly

### Issue: Some tokens fail
1. Check laravel.log for specific error messages
2. These are usually platform-specific (e.g., iOS token revoked)
3. System will auto-delete invalid tokens

## Future Enhancements

### Optional Features
1. **Notification History**: Store sent notifications in database with:
   - `created_by` (admin user ID)
   - `title`, `body`, `data`
   - `sent_count`, `failed_count`
   - `created_at`

2. **Notification Templates**: Create reusable templates for common notifications

3. **Scheduling**: Allow scheduling notifications for future delivery

4. **Advanced Targeting**: Filter users by:
   - Role/department
   - Platform (iOS/Android)
   - Last activity date
   - Custom attributes

Example implementation:
```php
// Notification::create([
//     'title' => $title,
//     'body' => $body,
//     'created_by' => auth()->id(),
//     'user_ids_json' => json_encode($userIds),
//     'sent_count' => $result['sent'],
//     'failed_count' => $result['failed'],
// ]);
```

## Related Documentation

- [Firebase Cloud Messaging Documentation](https://firebase.google.com/docs/cloud-messaging)
- [Laravel Form Requests Documentation](https://laravel.com/docs/requests#form-request-validation)
- [Project's FirebaseService Implementation](../../Services/FirebaseService.php)
- [Device Token Management](../../Services/DeviceTokenService.php)
