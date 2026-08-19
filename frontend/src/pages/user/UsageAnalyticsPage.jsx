import { useEffect, useState } from "react";
import { apiClient } from "../../api/client";
export function UsageAnalyticsPage() {
  const [data, setData] = useState(null); useEffect(() => { apiClient.get("/user/analytics").then(({ data: response }) => setData(response)); }, []);
  if (!data) return <div className="page-loading">Loading analytics...</div>;
  const summary = data.summary || {};
  return <section className="workspace"><header className="page-header"><div><p className="eyebrow">Study insights</p><h2>Usage Analytics</h2></div><span className="plan-pill">{data.subscription?.seat_type || "No active plan"}</span></header><div className="stat-grid"><article><span>Total Recorded Hours</span><strong>{summary.total_hours || 0}</strong></article><article><span>Hours This Month</span><strong>{summary.monthly_hours || 0}</strong></article><article><span>Active Study Days</span><strong>{summary.active_days || 0}</strong></article></div><div className="content-grid"><UsagePanel title="Daily Usage This Month" data={data.daily} label="day" /><UsagePanel title="Monthly Usage This Year" data={data.monthly} label="month_number" /></div></section>;
}
function UsagePanel({ title, data, label }) { const highest = Math.max(1, ...data.map((item) => Number(item.hours))); return <article className="panel"><h3>{title}</h3><div className="bar-chart">{data.map((item) => <div key={item[label]}><i style={{ height: `${(Number(item.hours) / highest) * 100}%` }} /><span>{item[label]}</span></div>)}</div></article>; }
