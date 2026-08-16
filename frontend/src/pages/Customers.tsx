import { useEffect, useState } from 'react';

const apiUrl = import.meta.env.VITE_API_URL ?? 'http://localhost/api/v1';

type Customer = { id:number; customer_number?:string; first_name:string; last_name:string; email?:string; phone?:string; status?:string };

export default function Customers() {
  const [customers,setCustomers]=useState<Customer[]>([]);
  const [search,setSearch]=useState('');
  const [loading,setLoading]=useState(true);
  const [error,setError]=useState('');

  useEffect(()=>{
    fetch(`${apiUrl}/customers`).then(async r=>{ if(!r.ok) throw new Error('Unable to load customers'); return r.json(); })
      .then(data=>setCustomers(data.data?.data ?? data.data ?? [])).catch(e=>setError(e.message)).finally(()=>setLoading(false));
  },[]);

  const filtered=customers.filter(c=>`${c.customer_number??''} ${c.first_name} ${c.last_name} ${c.email??''} ${c.phone??''}`.toLowerCase().includes(search.toLowerCase()));
  return <section className="content">
    <div className="pageHead"><div><small>CUSTOMER MANAGEMENT</small><h2>Customers</h2><p>Manage customers, services and billing relationships.</p></div><button className="primary">+ Add Customer</button></div>
    <div className="toolbar"><input value={search} onChange={e=>setSearch(e.target.value)} placeholder="Search customers..."/><button>Filter</button><button>Export</button></div>
    <div className="panel tablePanel">{loading?<div className="state">Loading customers…</div>:error?<div className="state error">{error}</div>:<table><thead><tr><th>Customer</th><th>Contact</th><th>Status</th><th>Customer No.</th><th></th></tr></thead><tbody>{filtered.map(c=><tr key={c.id}><td><strong>{c.first_name} {c.last_name}</strong></td><td>{c.phone||c.email||'—'}</td><td><span className={`badge ${c.status==='active'?'success':'neutral'}`}>{c.status??'active'}</span></td><td>{c.customer_number??`#${c.id}`}</td><td><button className="linkBtn">View</button></td></tr>)}</tbody></table>}</div>
  </section>;
}
