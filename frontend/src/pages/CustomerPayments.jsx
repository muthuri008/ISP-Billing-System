import { useEffect, useState } from 'react';

const API = import.meta.env.VITE_API_URL || 'http://localhost:8000/api/v1';

export default function CustomerPayments() {
  const [payments, setPayments] = useState(null);
  useEffect(() => { fetch(`${API}/customer-portal/payments`, { headers: { Accept: 'application/json', Authorization: `Bearer ${localStorage.getItem('isp_token')}` } }).then(r=>r.json()).then(setPayments); }, []);
  if (!payments) return <div className="loading">Loading payments…</div>;
  return <main className="portal"><header className="portal-header"><div><span className="eyebrow">Billing</span><h1>Payments</h1><p>Your recent payment activity.</p></div></header><section className="panel"><div className="table-wrap"><table><thead><tr><th>Payment</th><th>Method</th><th>Reference</th><th>Amount</th><th>Status</th><th>Date</th></tr></thead><tbody>{(payments.data||[]).map(payment=><tr key={payment.id}><td><strong>{payment.payment_number}</strong></td><td>{payment.method}</td><td>{payment.transaction_reference || '—'}</td><td>KES {Number(payment.amount).toLocaleString()}</td><td><span className={`status ${payment.status}`}>{payment.status}</span></td><td>{payment.paid_at || payment.created_at || '—'}</td></tr>)}</tbody></table></div></section></main>;
}
