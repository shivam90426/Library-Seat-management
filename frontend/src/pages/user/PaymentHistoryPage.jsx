import { useEffect, useState } from "react";
import { apiClient } from "../../api/client";
export function PaymentHistoryPage() {
  const [payments, setPayments] = useState([]); useEffect(() => { apiClient.get("/user/payments").then(({ data }) => setPayments(data)); }, []);
  return <section className="workspace"><header className="page-header"><div><p className="eyebrow">Payment records</p><h2>Payment History</h2></div></header><div className="table-panel"><table><thead><tr><th>Amount</th><th>Transaction ID</th><th>Status</th><th>Date</th></tr></thead><tbody>{payments.map((payment) => <tr key={payment.id}><td>Rs {payment.amount}</td><td>{payment.transaction_id}</td><td><span className={`status ${payment.status}`}>{payment.status}</span></td><td>{payment.created_at}</td></tr>)}{!payments.length && <tr><td colSpan="4">No payment records yet.</td></tr>}</tbody></table></div></section>;
}
