import { useState } from "react";
import { Link, useNavigate } from "react-router-dom";
import { apiClient } from "../../api/client";
import { useAuth } from "../../auth/AuthProvider";

export function LoginPage() {
  const [role, setRole] = useState("user");
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [error, setError] = useState("");
  const [saving, setSaving] = useState(false);
  const navigate = useNavigate();
  const { setUser } = useAuth();

  async function submit(event) {
    event.preventDefault(); setSaving(true); setError("");
    try {
      const { data } = await apiClient.post("/auth/login", { email, password, role });
      setUser(data.user);
      navigate(data.user.role === "admin" ? "/admin/dashboard" : "/user/dashboard");
    } catch (requestError) {
      setError(requestError.response?.data?.message || "Invalid email, password, or role.");
    } finally { setSaving(false); }
  }

  return <main className="auth-page"><section className="auth-photo" /><section className="auth-panel"><form className="auth-card" onSubmit={submit}>
    <h2>Library System Login</h2><p>Continue to your study workspace.</p>
    {error && <div className="notice error">{error}</div>}
    <div className="role-toggle"><button type="button" className={role === "user" ? "selected" : ""} onClick={() => setRole("user")}>User</button><button type="button" className={role === "admin" ? "selected" : ""} onClick={() => setRole("admin")}>Admin</button></div>
    <label>Email<input type="email" value={email} onChange={(event) => setEmail(event.target.value)} required /></label>
    <label>Password<input type="password" value={password} onChange={(event) => setPassword(event.target.value)} required /></label>
    <button className="primary-button" disabled={saving}>{saving ? "Logging in..." : "Login"}</button>
    <p className="auth-switch">New user? <Link to="/register">Register here</Link></p>
  </form></section></main>;
}
