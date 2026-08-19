import { Navigate, Route, Routes } from "react-router-dom";
import { AppShell } from "../components/layout/AppShell";
import { ProtectedRoute } from "../components/layout/ProtectedRoute";
import { LoginPage } from "../pages/auth/LoginPage";
import { RegisterPage } from "../pages/auth/RegisterPage";
import { AdminDashboardPage } from "../pages/admin/AdminDashboardPage";
import { AdminPaymentsPage } from "../pages/admin/AdminPaymentsPage";
import { AdminUsersPage } from "../pages/admin/AdminUsersPage";
import { AdminSubscriptionsPage } from "../pages/admin/AdminSubscriptionsPage";
import { AdminSeatBuilderPage } from "../pages/admin/AdminSeatBuilderPage";
import { UserDashboardPage } from "../pages/user/UserDashboardPage";
import { BookSeatPage } from "../pages/user/BookSeatPage";
import { MySeatPage } from "../pages/user/MySeatPage";
import { PaymentHistoryPage } from "../pages/user/PaymentHistoryPage";
import { UsageAnalyticsPage } from "../pages/user/UsageAnalyticsPage";

export function AppRoutes() {
  return (
    <Routes>
      <Route path="/" element={<Navigate to="/login" replace />} />
      <Route path="/login" element={<LoginPage />} />
      <Route path="/register" element={<RegisterPage />} />

      <Route element={<ProtectedRoute role="admin" />}>
      <Route element={<AppShell />}>
        <Route path="/admin/dashboard" element={<AdminDashboardPage />} />
        <Route path="/admin/payments" element={<AdminPaymentsPage />} />
        <Route path="/admin/users" element={<AdminUsersPage />} />
        <Route path="/admin/subscriptions" element={<AdminSubscriptionsPage />} />
        <Route path="/admin/seat-builder" element={<AdminSeatBuilderPage />} />
      </Route>
      </Route>

      <Route element={<ProtectedRoute role="user" />}>
      <Route element={<AppShell />}>
        <Route path="/user/dashboard" element={<UserDashboardPage />} />
        <Route path="/user/book-seat" element={<BookSeatPage />} />
        <Route path="/user/my-seat" element={<MySeatPage />} />
        <Route path="/user/payment-history" element={<PaymentHistoryPage />} />
        <Route path="/user/usage-analytics" element={<UsageAnalyticsPage />} />
      </Route>
      </Route>
    </Routes>
  );
}
