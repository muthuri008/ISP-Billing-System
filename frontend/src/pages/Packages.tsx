import { useEffect, useState } from 'react';

const apiUrl = import.meta.env.VITE_API_URL ?? 'http://localhost/api/v1';
type Package = { id:number; name:string; price?:number|string; download_speed?:string; upload_speed?:string; status?:string; billing_cycle?:string };

export default function Packages(){
 const [items,setItems]=useState<Package[]>([]); const [loading,setLoading]=useState(true); const [error,setError]=useState(''); const [search,setSearch]=useState('');
 useEffect(()=>{fetch(`${apiUrl}/packages`).then(async r=>{if(!r.ok)throw new Error('Unable to load packages');return r.json()}).then(d=>setItems(d.data?.data??d.data??[])).catch(e=>setError(e.message)).finally(()=>setLoading(false))},[]);
 const filtered=items.filter(p=>`${p.name} ${p.download_speed??''} ${p.upload_speed??''}`.toLowerCase().includes(search.toLowerCase()));
 return <section className="content"><div className="pageHead"><div><small>PRODUCT CATALOGUE</small><h2>Packages</h2><p>Define ISP plans, pricing and network speed policies.</p></div><button className="primary">+ Add Package</button></div>
 <div className="toolbar"><input value={search} onChange={e=>setSearch(e.target.value)} placeholder="Search packages..."/><button>Filter</button></div>
 <div className="panel tablePanel">{loading?<div className="state">Loading packages…</div>:error?<div className="state error">{error}</div>:<table><thead><tr><th>Package</th><th>Download</th><th>Upload</th><th>Price</th><th>Billing cycle</th><th>Status</th></tr></thead><tbody>{filtered.map(p=><tr key={p.id}><td><strong>{p.name}</strong></td><td>{p.download_speed??'—'}</td><td>{p.upload_speed??'—'}</td><td>{p.price!==undefined?`KES ${p.price}`:'—'}</td><td>{p.billing_cycle??'—'}</td><td><span className={`badge ${p.status==='active'?'success':'neutral'}`}>{p.status??'active'}</span></td></tr>)}</tbody></table>}</div></section>
}
