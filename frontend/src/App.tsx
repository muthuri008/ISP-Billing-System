import { useEffect, useState } from 'react';
import './admin.css';
import { clearToken, hasToken } from './api';
import Login from './Login';
import NetworkPage from './NetworkPage';
import CustomersPage from './CustomersPage';
import PackagesPage from './PackagesPage';
import SubscriptionsPage from './SubscriptionsPage';
import InvoicesPage from './InvoicesPage';
import PaymentsPage from './PaymentsPage';
import PaymentAllocationPage from './PaymentAllocationPage';
import ServiceAccountsPage from './ServiceAccountsPage';
import MpesaReconciliationPage from './MpesaReconciliationPage';
import NetworkAccountingPage from './NetworkAccountingPage';

const menu = ['Dashboard', 'Customers', 'Packages', 'Subscriptions', 'Invoices', 'Payments', 'Settlement', 'M-Pesa Reconciliation', 'Service Accounts', 'Routers', 'RADIUS', 'Sessions', 'Network Accounting', 'Reports'];

export default function App() {
  const [authenticated, setAuthenticated] = useState(hasToken());
  const [status, setStatus] = useState<'checking' | 'online' | 'offline'>('checking');
  const [active, setActive] = useState('Dashboard');

  useEffect(() => {
    fetch(`${import.meta.env.VITE_API_URL ?? '/api/v1'}/health`)
      .then(response => {
        if (!response.ok) throw new Error('API unavailable');
        setStatus('online');
      })
      .catch(() => setStatus('offline'));
  }, []);

  if (!authenticated) return <Login onLogin={() => setAuthenticated(true)} />;

  return <div className="app">
    <aside className="sidebar">
      <div className="brand"><div className="brandMark">ST</div><div><strong>Stellar Technologies</strong><small>ISP Billing Administration</small></div></div>
      <nav>{menu.map(item => <button key={item} className={active === item ? 'navItem active' : 'navItem'} onClick={() => setActive(item)}><span>{icon(item)}</span>{item}</button>)}</nav>
      <div className="sideStatus"><span className={`dot ${status}`} /> API {status}</div>
    </aside>
    <main className="main">
      <header className="topbar"><div><small>STELLAR TECHNOLOGIES</small><h1>{active}</h1></div><div className="profile"><div className="avatar">ST</div><span>Administrator</span><button className="logout" onClick={() => { clearToken(); setAuthenticated(false); }}>Sign out</button></div></header>
      {active === 'Dashboard' ? <Dashboard status={status} />
        : active === 'Customers' ? <CustomersPage />
        : active === 'Packages' ? <PackagesPage />
        : active === 'Subscriptions' ? <SubscriptionsPage />
        : active === 'Invoices' ? <InvoicesPage />
        : active === 'Payments' ? <PaymentsPage />
        : active === 'Settlement' ? <PaymentAllocationPage />
        : active === 'M-Pesa Reconciliation' ? <MpesaReconciliationPage />
        : active === 'Service Accounts' ? <ServiceAccountsPage />
        : active === 'Routers' ? <NetworkPage />
        : active === 'Sessions' || active === 'Network Accounting' ? <NetworkAccountingPage />
        : <Module title={active} />}
    </main>
  </div>;
}

function Dashboard({ status }: { status: string }) {
  return <section className="content">
    <div className="welcome"><div><small>STELLAR TECHNOLOGIES</small><h2>Good day, Administrator</h2><p>Manage your ISP billing and network operations from one place.</p></div></div>
    <div className="stats">
      <Stat title="Customers" value="—" note="Open Customers for live records" />
      <Stat title="Active Services" value="—" note="Open Subscriptions for live records" />
      <Stat title="Outstanding" value="—" note="Live finance metrics are not yet configured" />
      <Stat title="Online Sessions" value="—" note="Open Network Accounting for records" />
    </div>
    <div className="grid">
      <article className="panel"><div className="panelHead"><h3>API health</h3><span className="healthy">{status}</span></div><p>Connection status for the Laravel API health endpoint.</p></article>
      <article className="panel"><div className="panelHead"><h3>Network control</h3></div><p>Inspect configured routers, check connectivity and review session accounting.</p></article>
    </div>
  </section>;
}

function Stat({ title, value, note }: { title: string; value: string; note: string }) {
  return <article className="stat"><small>{title}</small><strong>{value}</strong><span>{note}</span></article>;
}

function Module({ title }: { title: string }) {
  return <section className="content"><div className="panel"><h2>{title}</h2><p>This module is not yet implemented. Do not use it for operational decisions until its live data and workflows are completed and tested.</p></div></section>;
}

function icon(item: string) {
  const icons: Record<string, string> = {
    Dashboard: '⌂', Customers: '◉', Packages: '▣', Subscriptions: '↻', Invoices: '▤',
    Payments: '₿', Settlement: '✓', 'M-Pesa Reconciliation': '↔', 'Service Accounts': '♙',
    Routers: '⌁', RADIUS: '◈', Sessions: '◌', 'Network Accounting': '⇅', Reports: '▥',
  };
  return icons[item] ?? '•';
}
