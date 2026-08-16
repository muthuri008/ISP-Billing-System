import { useEffect, useState } from 'react';

const API = import.meta.env.VITE_API_URL || 'http://localhost:8000/api/v1';

export default function CustomerDashboard() {
  const [data, setData] = useState(null);
  const [error, setError] = useState('');

  useEffect(() => {
    const token = localStorage.getItem('isp_token');
    fetch(`${API}/customer-portal/dashboard`, {
      headers: { Accept: 'application/json', Authorization: `Bearer ${token}` },
    })
      .then(async (response) => {
        if (!response.ok) throw new Error((await response.json()).message || 'Unable to load dashboard');
        return response.json();
      })
      .then((payload) => setData(payload.data))
      .catch((err) => setError(err.message));
  }, []);

  if (error) return <main className="portal"><div className="alert error">{error}</div></main>;
  if (!data) return <main className="portal"><div className="loading">Loading your account…</div></main>;

  return (
    <main className="portal">
      <header className="portal-header">
        <div><span className="eyebrow">Customer portal</span><h1>Welcome, {data.customer.name}</h1><p>{data.customer.customer_number}</p></div>
        <span className={`status ${data.customer.status}`}>{data.customer.status}</span>
      </header>

      <section className="stats">
        <article><span>Outstanding</span><strong>KES {Number(data.billing.outstanding).toLocaleString()}</strong></article>
        <article><span>Overdue</span><strong>KES {Number(data.billing.overdue).toLocaleString()}</strong></article>
        <article><span>Open invoices</span><strong>{data.billing.open_invoices}</strong></article>
      </section>

      <section className="portal-grid">
        <article className="panel"><h2>Services</h2>{data.services.length ? data.services.map((service) => <div className="row" key={service.id}><div><strong>{service.username}</strong><small>Router #{service.router_id || '—'}</small></div><span className={`status ${service.status}`}>{service.status}</span></div>) : <p>No active services found.</p>}</article>
        <article className="panel"><h2>Recent payments</h2>{data.recent_payments.length ? data.recent_payments.map((payment) => <div className="row" key={payment.id}><div><strong>{payment.payment_number}</strong><small>{payment.method} · {payment.transaction_reference || 'Pending reference'}</small></div><strong>KES {Number(payment.amount).toLocaleString()}</strong></div>) : <p>No payments yet.</p>}</article>
      </section>

      <section className="panel"><div className="panel-title"><h2>Recent invoices</h2><a href="#invoices">View all</a></div>{data.recent_invoices.map((invoice) => <div className="row" key={invoice.id}><div><strong>{invoice.invoice_number}</strong><small>Due {invoice.due_date || '—'}</small></div><div className="invoice-amount"><strong>KES {Number(invoice.amount_due).toLocaleString()}</strong><span className={`status ${invoice.status}`}>{invoice.status}</span></div></div>)}</section>
    </main>
  );
}
