import { useEffect, useState } from "react";
import { Link } from "react-router-dom";
import { apiClient } from "../../api/client";

export function UserDashboardPage() {
  const [data, setData] = useState(null); const [weekly, setWeekly] = useState(null); const [error, setError] = useState("");
  const load = () => Promise.all([apiClient.get("/user/dashboard"), apiClient.get("/user/weekly-usage")]).then(([dashboard, usage]) => { setData(dashboard.data); setWeekly(usage.data); }).catch(() => setError("Unable to load your dashboard."));
  useEffect(() => { load(); }, []);
  async function timer(action) { try { await apiClient.post(`/user/timer/${action}`); load(); } catch (requestError) { setError(requestError.response?.data?.message || "Timer action could not be completed."); } }
  async function saveDiary(event) { event.preventDefault(); const content = new FormData(event.currentTarget).get("content"); await apiClient.post("/user/diary", { content }); load(); }
  if (!data) return <div className="page-loading">Loading dashboard...</div>;
  const subscription = data.subscription;
  return <section className="workspace"><header className="page-header"><div><p className="eyebrow">Study workspace</p><h2>Welcome back</h2></div><Link className="primary-button compact" to="/user/book-seat">Book a Seat</Link></header>
    {error && <div className="notice error">{error}</div>}
    <div className="stat-grid"><article><span>Active Plan</span><strong>{subscription?.seat_type || "No active plan"}</strong></article><article><span>Today</span><strong>{data.todayHours}h</strong></article><article><span>This Month</span><strong>{data.monthHours}h</strong></article><article><span>Current Seat</span><strong>{data.activeSeat?.seat_no || "Not booked"}</strong></article></div>
    <div className="content-grid"><article className="panel"><h3>Study Timer</h3><p>{data.activeTimer ? "Your timer is running." : "Start your study session when you enter."}</p><button className="primary-button" onClick={() => timer(data.activeTimer ? "stop" : "start")} disabled={!subscription}>{data.activeTimer ? "Stop Timer" : "Start Timer"}</button></article>
      <article className="panel"><h3>This Week</h3><div className="bar-chart">{weekly?.hours.map((hours, index) => <div key={weekly.labels[index]}><i style={{ height: `${Math.min(100, (Number(hours) / weekly.max) * 100)}%` }} /><span>{weekly.labels[index]}</span></div>)}</div></article></div>
    <article className="panel diary-panel"><h3>Today's Diary</h3><form onSubmit={saveDiary}><textarea name="content" defaultValue={data.diary?.content || ""} placeholder="Write a short note about today's study..." maxLength="5000" /><button className="primary-button compact">Save Note</button></form></article>
  </section>;
}
