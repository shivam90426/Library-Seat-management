import { Navigate, Outlet } from "react-router-dom";
import { useAuth } from "../../auth/AuthProvider";

export function ProtectedRoute({ role }) {
  const { user, loading } = useAuth();
  if (loading) return <div className="page-loading">Loading your library workspace...</div>;
  if (!user) return <Navigate to="/login" replace />;
  if (role && user.role !== role) return <Navigate to={user.role === "admin" ? "/admin/dashboard" : "/user/dashboard"} replace />;
  return <Outlet />;
}
