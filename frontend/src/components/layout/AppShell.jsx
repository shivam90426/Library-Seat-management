import { NavLink, Outlet, useNavigate } from "react-router-dom";
import { useAuth } from "../../auth/AuthProvider";

const adminLinks = [{ to: "/admin/dashboard", label: "Dashboard" }, { to: "/admin/payments", label: "Payments" }, { to: "/admin/users", label: "Users" }, { to: "/admin/subscriptions", label: "Subscriptions" }, { to: "/admin/seat-builder", label: "Seat Builder" }];
const userLinks = [{ to: "/user/dashboard", label: "Dashboard" }, { to: "/user/book-seat", label: "Book Seat" }, { to: "/user/my-seat", label: "My Seat" }, { to: "/user/usage-analytics", label: "Usage Analytics" }, { to: "/user/payment-history", label: "Payments" }];

export function AppShell() {
  const { user, logout } = useAuth();
  const navigate = useNavigate();
  const links = user?.role === "admin" ? adminLinks : userLinks;

  async function handleLogout() { await logout(); navigate("/login"); }

  return (
    <div className="shell">
      <aside className="shell-sidebar">
        <div><h1>Library System</h1><p>{user?.role === "admin" ? "Administration" : "Study Workspace"}</p></div>
        <nav>
          {links.map((link) => (
            <NavLink
              key={link.to}
              to={link.to}
              className={({ isActive }) => (isActive ? "active" : "")}
            >
              {link.label}
            </NavLink>
          ))}
        </nav>
        <div className="sidebar-footer"><span>{user?.name}</span><button onClick={handleLogout}>Log out</button></div>
      </aside>
      <main className="shell-main">
        <Outlet />
      </main>
    </div>
  );
}
