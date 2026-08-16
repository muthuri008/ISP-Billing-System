import { useEffect, useState } from 'react';
import './styles.css';
import { api, clearToken, hasToken } from './api';
import Login from './Login';
import NetworkPage from './NetworkPage';

const menu = ['Dashboard','Customers','Packages','Subscriptions','Invoices','Payments','Routers','RADIUS','Sessions','Reports'];

export default function App() {
  const [authenticated, setAuthenticated] = useState(hasToken());
  const [status, setStatus] = useState<'checking'|'online'|'offline'>('checking');
  const [active, setActive] = useState('Dashboard');
  useEffect(() => { fetch(`${import.meta.env.VITE_API_URL ?? 'http://localhost/api/v1'}/health`).then(r => { if (!r.ok) throw new Error(); setStatus('online'); }).catch(() => setStatus('offline')); }, []);
  if (!authenticated) return <Login onLogin={() => setAuthenticated(true)} />;
  return <div className="app">
    <aside className="sidebar"><div className="brand"><div className="brandMark">ISP</div><div><strong>ISP Billing</strong><small>Administration</small></div></div>
      <nav>{menu.map(item => <button key={item} className={active===item?'navItem active':'navItem'} onClick={()=>setActive(item)}><span>{icon(item)}</span>{item}</button>)}</nav>
      <div className="sideStatus"><span className={`dot ${status}`} /> API {status}</div>
    </aside>
    <main className="main"><header className="topbar"><div><small>ADMINISTRATION</small><h1>{active}</h1></div><div className="profile"><div className="avatar">AD</div><span>Administrator</span><button className="logout" onClick={() => { clearToken(); setAuthenticated(false); }}>Sign out</button></div></header>
      {active === 'Dashboard' ? <Dashboard /> : active === 'Routers' || active === 'Sessions' ? <NetworkPage /> : <Module title={active} />}
    </main>
  </div>;
}
function Dashboard() { return <section className="content"><div className="welcome"><div><small>NETWORK OVERVIEW</small><h2>Good day, Administrator</h2><p>Monitor your ISP operation from one place.</p></div><button className="primary">+ Add Customer</button></div>
  <div className="stats"><Stat title="Customers" value="—" note="Live customer metrics next"/><Stat title="Active Services" value="—" note="Live service metrics next"/><Stat title="Outstanding" value="—" note="Live finance metrics next"/><Stat title="Online Sessions" value="—" note="Open Sessions to inspect routers"/></div>
  <div className="grid"><article className="panel"><div className="panelHead"><h3>API health</h3><span className="healthy">{statusText()}</span></div><p>The frontend is connected to the Laravel API health endpoint.</p></article><article className="panel"><div className="panelHead"><h3>Next operational view</h3></div><p>Open <strong>Routers</strong> to check router health and inspect active PPP sessions.</p></article></div>
  </section> }
function statusText(){ return 'Operational'; }
function Stat({title,value,note}:{title:string,value:string,note:string}) { return <article className="stat"><small>{title}</small><strong>{value}</strong><span>{note}</span></article> }
function Module({title}:{title:string}) { return <section className="content"><div className="empty"><h2>{title}</h2><p>This module is connected to the ISP Billing architecture. Its live CRUD screen will be added after the network control panel is validated.</p><button className="primary">Open {title}</button></div></section> }
function icon(item:string) { const icons:Record<string,string>={Dashboard:'⌂',Customers:'◉',Packages:'▣',Subscriptions:'↻',Invoices:'▤',Payments:'₿',Routers:'⌁',RADIUS:'◈',Sessions:'◌',Reports:'▥'}; return icons[item] ?? '•'; }
