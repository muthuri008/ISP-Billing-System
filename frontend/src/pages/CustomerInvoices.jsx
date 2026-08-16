import { useEffect, useState } from 'react';

const API = import.meta.env.VITE_API_URL || 'http://localhost:8000/api/v1';
const auth = () => ({ Accept: 'application/json', Authorization: `Bearer ${localStorage.getItem('isp_token')}` });

export default function CustomerInvoices() {
  const [invoices, setInvoices] = useState(null);
  const [phone, setPhone] = useState('');
  const [selected, setSelected] = useState(null);
  const [message, setMessage] = useState('');

  useEffect(() => { fetch(`${API}/customer-portal/invoices`, { headers: auth() }).then(r => r.json()).then(setInvoices); }, []);

  async function pay(invoice) {
    setMessage('');
    const response = await fetch(`${API}/customer-portal/invoices/${invoice.id}/pay`, { method: 'POST', headers: { ...auth(), 'Content-Type': 'application/json' }, body: JSON.stringify({ phone }) });
    const payload = await response.json();
    if (!response.ok) return setMessage(payload.message || 'Unable to start payment.');
    setMessage('STK Push sent. Check your phone and complete the M-Pesa payment.');
    setSelected(null);
  }

  if (!invoices) return <div className="loading">Loading invoices…</div>;
  const rows = invoices.data || [];
  return <main className="portal"><header className="portal-header"><div><span className="eyebrow">Billing</span><h1>Invoices</h1><p>View your bills and pay outstanding balances.</p></div></header>{message && <div className="alert">{message}</div>}<section className="panel"><div className="table-wrap"><table><thead><tr><th>Invoice</th><th>Due date</th><th>Status</th><th>Amount due</th><th></th></tr></thead><tbody>{rows.map(invoice => <tr key={invoice.id}><td><strong>{invoice.invoice_number}</strong></td><td>{invoice.due_date || '—'}</td><td><span className={`status ${invoice.status}`}>{invoice.status}</span></td><td>KES {Number(invoice.amount_due).toLocaleString()}</td><td>{Number(invoice.amount_due) > 0 && invoice.status !== 'paid' && <button className="primary small" onClick={() => setSelected(invoice)}>Pay now</button>}</td></tr>)}</tbody></table></div></section>{selected && <div className="modal-backdrop"><form className="modal" onSubmit={e => { e.preventDefault(); pay(selected); }}><h2>Pay {selected.invoice_number}</h2><p>Amount: <strong>KES {Number(selected.amount_due).toLocaleString()}</strong></p><label>M-Pesa phone number<input value={phone} onChange={e=>setPhone(e.target.value)} placeholder="2547XXXXXXXX" required /></label><button className="primary">Send STK Push</button><button type="button" onClick={()=>setSelected(null)}>Cancel</button></form></div>}</main>;
}
