# ABSTRACT
This project presents a smart campus navigator and event management system designed to support academic communication, service delivery, and administrative coordination through a secure mobile platform connected to a Laravel backend and a Flutter client. The purpose of the system is to provide a centralized digital environment for campus users to authenticate securely, search campus resources, manage events, receive announcements, report and claim lost items, and obtain timely notifications through role-based access control. The system also addresses common operational challenges in campus applications, including duplicate registration, event capacity control, notification delivery, and consistent handling of user permissions.

The methodology adopted for the project follows a service-oriented software engineering approach. Backend logic is organized through controllers, form requests, DTOs, services, policies, observers, and jobs to separate concerns and improve maintainability. Authentication is implemented with JWT, while email verification and password reset flows are managed through OTP-based mechanisms. Event registration is executed within transactional logic to prevent duplicate signups and over-capacity enrollment. The notification subsystem uses Firebase Cloud Messaging for push delivery, a database-backed notification model for tracking read and delivery states, and device token management for reliable communication. In addition, the system includes global search functionality, RBAC with dynamic permissions, and Supabase-based image storage to support persistent media uploads in deployment environments where local storage is non-persistent.

The findings of the implementation indicate that the architecture is suitable for a campus platform requiring both user-facing functionality and administrative control. The modular structure improves code clarity and supports future extension, while the use of database constraints, transactions, and policy-based authorization increases reliability and security. The search subsystem provides fast access to events, buildings, rooms, and lost items, and the notification layer allows targeted communication with users according to their roles and preferences. These outcomes suggest that the system can reduce manual coordination tasks and improve the efficiency of campus information management.

The implications of this project are significant for institutions seeking a scalable and maintainable digital campus solution. By combining secure authentication, structured administration, and persistent communication tools, the system supports better user engagement and more organized service delivery. In conclusion, the project demonstrates that a well-structured Laravel backend paired with a mobile frontend can provide a practical and extensible solution for modern campus operations, with strong support for notifications, access control, and transactional event handling.

# Project Title:
Smart Campus Navigator and Event Management System with Notification and Role-Based Access Control

# Description:
This is a Laravel-based backend API for a mobile campus platform. It powers the Flutter frontend with authentication, campus search, event management, lost-and-found, news, announcements, push notifications, and admin tools.

# Core Features:
- OTP-based email verification and password reset flow
- JWT authentication for API access
- Pre-registration with duplicate prevention and race-condition-safe validation
- Event management with capacity checks, registration, and cancellation
- News and announcements management
- Lost-and-found item posting and item claim workflow
- Global cross-model search with filtering and ranked relevance
- Firebase Cloud Messaging (FCM) push notifications
- Device token registration and cleanup
- Notification preferences and unread/read tracking
- Role-based access control with super_admin, admin, sub_admin, and user roles
- Dynamic permissions and policy-based authorization
- Admin dashboard and management endpoints
- Supabase-backed image storage for persistent uploads
- Background jobs and scheduled event reminders

# Architecture:
- Laravel 12 REST API
- JWT-based auth using `php-open-source-saver/jwt-auth`
- Service-oriented backend structure with Controllers, Form Requests, DTOs, Services, Policies, Observers, Jobs, and Resources
- MySQL/PostgreSQL-ready relational database layer with migrations, seeders, factories, and pivot tables
- Firebase Cloud Messaging for push delivery
- Supabase Storage for persistent image uploads instead of local-only disk storage
- Redis-backed search cache and application caching
- Transactional business logic for sensitive flows like registration, event signup, and notification delivery
- Policy-driven RBAC and permission checks for admin actions

# Deployment:
- Backend is prepared for Docker-based deployment on Render
- `Dockerfile`, `docker-entrypoint.sh`, and `render.yaml` support containerized production runs
- Render health check is exposed at `/api/health`
- Render storage is treated as non-persistent, so uploaded images are stored in Supabase
- Firebase credentials and database credentials are configured through environment variables
- Redis is used for cache and queue performance in production

# Important Notes:
- Duplicate registration is handled defensively with validation and database uniqueness constraints
- Event registration is protected against duplicate signups and full-capacity edge cases
- Notifications are tracked in the database with recipient, read, and delivery status
- Invalid device tokens are removed automatically during push delivery failures
- The codebase follows a clean layered approach, with business rules pushed into services rather than controllers
- RBAC is backward compatible with the legacy `users.role` field while supporting dynamic role-permission management
- Search behavior is centralized and shared across buildings, rooms, events, and lost items