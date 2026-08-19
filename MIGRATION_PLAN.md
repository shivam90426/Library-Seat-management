# Migration Plan

Target stack:

- Frontend: React
- Backend: Spring Boot
- Database: MySQL

## Suggested migration order

1. Auth
2. Admin dashboard
3. Payments and subscriptions
4. Users
5. Seat builder and booking
6. Attendance, timer, and analytics
7. Diary features

## Current PHP to new frontend mapping

| Current file | New React page |
| --- | --- |
| `login.php` | `frontend/src/pages/auth/LoginPage.jsx` |
| `register.php` | `frontend/src/pages/auth/RegisterPage.jsx` |
| `admin/dashboard.php` | `frontend/src/pages/admin/AdminDashboardPage.jsx` |
| `admin/payments.php` | `frontend/src/pages/admin/AdminPaymentsPage.jsx` |
| `admin/users.php` | `frontend/src/pages/admin/AdminUsersPage.jsx` |
| `admin/subscriptions.php` | `frontend/src/pages/admin/AdminSubscriptionsPage.jsx` |
| `admin/layout-builder.php` | `frontend/src/pages/admin/AdminSeatBuilderPage.jsx` |
| `admin/section-builder.php` | `frontend/src/pages/admin/AdminSeatBuilderPage.jsx` |
| `user/dashboard.php` | `frontend/src/pages/user/UserDashboardPage.jsx` |
| `user/book-seat.php` | `frontend/src/pages/user/BookSeatPage.jsx` |
| `user/my-seat.php` | `frontend/src/pages/user/MySeatPage.jsx` |
| `user/payment-history.php` | `frontend/src/pages/user/PaymentHistoryPage.jsx` |
| `user/usage-analytics.php` | `frontend/src/pages/user/UsageAnalyticsPage.jsx` |

## Current PHP to new backend mapping

| Current file | New Spring Boot module |
| --- | --- |
| `config/db.php` | `application.properties` datasource config |
| `login.php` | `AuthController`, auth service, Spring Security |
| `register.php` | auth/user registration controller |
| `admin/api/*.php` | admin controllers, services, repositories |
| `user/api/*.php` | user controllers, services, repositories |
| `includes/auth_check.php` | Spring Security and JWT filters |
| `includes/diary_helpers.php` | diary service layer |
| `admin/includes/plan_helpers.php` | subscription service helpers |
| `admin/includes/layout_helpers.php` | seat layout service helpers |

## Suggested Spring Boot modules

- `auth`
- `user`
- `payment`
- `subscription`
- `seat`
- `attendance`
- `analytics`
- `diary`

## Suggested database-first approach

1. Keep current MySQL database
2. Create JPA entities for existing tables
3. Build read-only APIs first
4. Move write operations after UI routes are ready
5. Remove PHP page by page instead of all at once

## Notes

- `backend/pom.xml` is added but Maven is not installed yet in this environment.
- `frontend/package.json` is added but Node.js and npm are not ready in this environment.
- Once tools are installed, we can implement module by module.
