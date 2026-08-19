import { useEffect, useState } from "react";
import { apiClient } from "../../api/client";
export function BookSeatPage() {
  const [seats, setSeats] = useState([]); const [message, setMessage] = useState("");
  const load = () => apiClient.get("/user/seats").then(({ data }) => setSeats(data));
  useEffect(() => { load(); }, []);
  async function book(id) { try { const { data } = await apiClient.post(`/user/seats/${id}/book`); setMessage(`Seat booked (${data.bookingType}).`); load(); } catch (error) { setMessage(error.response?.data?.message || "Seat could not be booked."); } }
  return <section className="workspace"><header className="page-header"><div><p className="eyebrow">Seat booking</p><h2>Choose Your Seat</h2></div></header>{message && <div className="notice">{message}</div>}<div className="seat-grid">{seats.map((seat) => <button key={seat.id} className={`seat ${seat.status}`} disabled={seat.status !== "available"} onClick={() => book(seat.id)}><strong>{seat.seat_no}</strong><span>{seat.seat_type}</span><small>{seat.status === "available" ? "Available" : seat.status === "mine" ? "Your seat" : seat.status}</small></button>)}</div></section>;
}
