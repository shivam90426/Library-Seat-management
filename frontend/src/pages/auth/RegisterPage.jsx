import { useState } from "react";
import { Link, useNavigate } from "react-router-dom";
import { apiClient } from "../../api/client";

export function RegisterPage() {
  const [form, setForm] = useState({ name: "", email: "", password: "" });
  const [message, setMessage] = useState(""); const [error, setError] = useState(""); const [saving, setSaving] = useState(false);
  const navigate = useNavigate();
  async function submit(event) {
    event.preventDefault(); setSaving(true); setError("");
    try { const { data } = await apiClient.post("/auth/register", form); setMessage(data.message); setTimeout(() => navigate("/login"), 1000); }
    catch (requestError) { setError(requestError.response?.data?.message || "Registration could not be completed."); }
    finally { setSaving(false); }
  }
  return <main className="auth-page"><section className="auth-photo" /><section className="auth-panel"><form className="auth-card" onSubmit={submit}>
    <h2>Create Account</h2><p>Set up your library membership.</p>
    {error && <div className="notice error">{error}</div>}{message && <div className="notice success">{message}</div>}
    <label>Full name<input value={form.name} onChange={(event) => setForm({ ...form, name: event.target.value })} required /></label>
    <label>Email<input type="email" value={form.email} onChange={(event) => setForm({ ...form, email: event.target.value })} required /></label>
    <label>Password<input type="password" minLength="8" value={form.password} onChange={(event) => setForm({ ...form, password: event.target.value })} required /></label>
    <button className="primary-button" disabled={saving}>{saving ? "Creating account..." : "Register"}</button>
    <p className="auth-switch">Already registered? <Link to="/login">Login here</Link></p>
  </form></section></main>;
}
