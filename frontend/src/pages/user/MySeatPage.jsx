import { useEffect, useState } from "react";
import { Link } from "react-router-dom";
import { apiClient } from "../../api/client";
export function MySeatPage() {
  const [seat, setSeat] = useState(undefined); useEffect(() => { apiClient.get("/user/my-seat").then(({ data }) => setSeat(data.seat)); }, []);
  if (seat === undefined) return <div className="page-loading">Loading your seat...</div>;
  if (!seat) return <section className="workspace empty-state"><h2>No Current Seat</h2><p>Choose an available seat once your subscription is active.</p><Link className="primary-button compact" to="/user/book-seat">Book a Seat</Link></section>;
  return <section className="workspace"><header className="page-header"><div><p className="eyebrow">Your assigned space</p><h2>My Current Seat</h2></div></header><article className="seat-banner"><span>Seat Number</span><strong>{seat.seat_no}</strong><em>{seat.booking_type === "fixed" ? "Fixed booking" : "Today's daily booking"}</em></article><div className="detail-grid">{[["Seat Type",seat.seat_type],["Section",seat.section_name],["Booking Date",seat.booking_date],["Booking Start",seat.booking_start],["Plan Start",seat.start_date],["Plan End",seat.end_date]].map(([label,value]) => <article key={label}><span>{label}</span><strong>{value || "-"}</strong></article>)}</div></section>;
}
