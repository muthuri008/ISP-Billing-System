import { useState } from 'react';
// @ts-ignore JSX pages are intentionally kept alongside the existing frontend while the portal is migrated incrementally.
import CustomerLogin from './pages/CustomerLogin';
// @ts-ignore
import CustomerDashboard from './pages/CustomerDashboard';
// @ts-ignore
import CustomerInvoices from './pages/CustomerInvoices';
// @ts-ignore
import CustomerPayments from './pages/CustomerPayments';

export default function CustomerPortalApp() {
  const [authenticated, setAuthenticated] = useState(Boolean(localStorage.getItem('isp_token')));
  const [page, setPage] = useState('dashboard');
  if (!authenticated) return <CustomerLogin onLogin={() => setAuthenticated(true)} />;
  function logout() { localStorage.removeItem('isp_token'); setAuthenticated(false); }
  return <div className="customer-shell">
    <aside className="customer-sidebar"><div className="brand"><span className="brand-mark">IS</span><div><strong>ISP Billing</strong><small>Customer Portal</small></div></div>
      <nav>
        <button className={page === 'dashboard' ? 'nav active' : 'nav'} onClick={() => setPage('dashboard')}>Dashboard</button>
        <button className={page === 'invoices' ? 'nav active' : 'nav'} onClick={() => setPage('invoices')}>Invoices</button>
        <button className={page === 'payments' ? 'nav active' : 'nav'} onClick={() => setPage('payments')}>Payments</button>
      </nav>
      <button className="nav logout" onClick={logout}>Sign out</button>
    </aside>
    <section className="customer-content">{page === 'dashboard' && <CustomerDashboard />}{page === 'invoices' && <CustomerInvoices />}{page === 'payments' && <CustomerPayments />}</section>
  </div>;
}
