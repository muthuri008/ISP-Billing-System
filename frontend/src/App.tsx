import { useEffect, useState } from 'react';
import './styles.css';

const apiUrl = import.meta.env.VITE_API_URL ?? 'http://localhost/api/v1';
const menu = ['Dashboard','Customers','Packages','Subscriptions','Invoices','Payments','Routers','RADIUS','Sessions','Reports'];

export default function App() {
  const [status, setStatus] = useState<'checking'|'online'|'offline'>('checking');
  const [active, setActive] = useState('Dashboard');
  useEffect(() => { fetch(`${apiUrl}/health`).then(r => { if (!r.ok) throw new Error(); setStatus('online'); }).catch(() => setStatus('offline')); }, []);
  return <div className="app">
    <aside className="sidebar"><div className="brand"><div className="brandMark">ISP</div><div><strong>ISP Billing</strong><small>Administration</small></div></div>
      <nav>{menu.map(item => <button key={item} className={active===item?'navItem active':'navItem'} onClick={()=>setActive(item)}><span>{icon(item)}</span>{item}</button>)}</nav>
      <div className="sideStatus"><span className={`dot ${status}`} /> API {status}</div>
    </aside>
    <main className="main"><header className="topbar"><div><small>ADMINISTRATION</small><h1>{active}</h1></div><div className="profile"><div className="avatar">AD</div><span>Administrator</span></div></header>
      {active === 'Dashboard' ? <Dashboard /> : <Module title={active} />}
    </main>
  </div>;
}
function Dashboard() { return <section className="content"><div className="welcome"><div><small>NETWORK OVERVIEW</small><h2>Good day, Administrator</h2><p>Monitor your ISP operation from one place.</p></div><button className="primary">+ Add Customer</button></div>
  <div className="stats"><Stat title="Customers" value="1,248" note="+8.2% this month"/><Stat title="Active Services" value="1,109" note="88.9% of customers"/><Stat title="Outstanding" value="KES 384,650" note="Awaiting payment"/><Stat title="Online Sessions" value="327" note="Across all routers"/></div>
  <div className="grid"><article className="panel"><div className="panelHead"><h3>Service health</h3><span className="healthy">Operational</span></div><div className="health"><Health name="Network availability" value="99.98%"/><Health name="Active services" value="1,109"/><Health name="Suspended services" value="139"/></div></article><article className="panel"><div className="panelHead"><h3>Recent activity</h3><button>View all</button></div><ul className="activity"><li><b>M-Pesa payment</b><span>Customer #C-10482 · KES 2,500</span><em>2 min ago</em></li><li><b>Service restored</b><span>Customer #C-10391 · PPPoE</span><em>8 min ago</em></li><li><b>Router online</b><span>MikroTik — Core Router 01</span><em>14 min ago</em></li></ul></article></div>
  </section> }
function Stat({title,value,note}:{title:string,value:string,note:string}) { return <article className="stat"><small>{title}</small><strong>{value}</strong><span>{note}</span></article> }
function Health({name,value}:{name:string,value:string}) { return <div className="healthRow"><span className="ok"/><span>{name}</span><strong>{value}</strong></div> }
function Module({title}:{title:string}) { return <section className="content"><div className="empty"><h2>{title}</h2><p>This module is connected to the ISP Billing architecture and will be implemented with live API data next.</p><button className="primary">Create {title.slice(0,-1) || title}</button></div></section> }
function icon(item:string) { const icons:Record<string,string>={Dashboard:'⌂',Customers:'◉',Packages:'▣',Subscriptions:'↻',Invoices:'▤',Payments:'₿',Routers:'⌁',RADIUS:'◈',Sessions:'◌',Reports:'▥'}; return icons[item] ?? '•'; }
