import { useEffect, useState } from 'react';
import { api } from './api';

type Customer = { id:number|string; customer_number:string; first_name:string; last_name:string; phone:string; email?:string|null; status:string; billing_type:string };

type Props = { onOpen?: (customer: Customer) => void };

export default function CustomersPage({ onOpen }: Props) {
  const [customers, setCustomers] = useState<Customer[]>([]);
  const [search, setSearch] = useState('');
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  async function load(term = search) {
    setLoading(true); setError('');
    try {
      const qs = term ? `?search=${encodeURIComponent(term)}` : '';
      const data = await api<{data: Customer[]}>('/customers' + qs);
      setCustomers(data.data ?? []);
    } catch (e) { setError(e instanceof Error ? e.message : 'Unable to load customers.'); }
    finally { setLoading(false); }
  }

  useEffect(() => { load(''); }, []);

  return <section className="content">
    <div className="welcome"><div><small>CUSTOMER MANAGEMENT</small><h2>Customers</h2><p>Live customer records from the billing database.</p></div><button className="primary" onClick={() => alert('Customer creation form is the next CRUD step.')}>+ Add Customer</button></div>
    <div className="panel">
      <div className="toolbar"><input value={search} onChange={e => setSearch(e.target.value)} onKeyDown={e => e.key === 'Enter' && load()} placeholder="Search number, name, phone or email"/><button onClick={() => load()}>Search</button></div>
      {error && <p className="error">{error}</p>}
      {loading ? <p>Loading customers…</p> : customers.length === 0 ? <p>No customers found.</p> : <div className="tableWrap"><table><thead><tr><th>Number</th><th>Customer</th><th>Phone</th><th>Billing</th><th>Status</th><th></th></tr></thead><tbody>{customers.map(c => <tr key={c.id}><td>{c.customer_number}</td><td>{c.first_name} {c.last_name}</td><td>{c.phone}</td><td>{c.billing_type}</td><td><span className="statusBadge">{c.status}</span></td><td><button className="linkButton" onClick={() => onOpen?.(c)}>View</button></td></tr>)}</tbody></table></div>}
    </div>
  </section>;
}
