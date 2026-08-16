import { useState } from 'react';
import CustomerLogin from './pages/CustomerLogin';
import CustomerDashboard from './pages/CustomerDashboard';

export default function CustomerPortalApp() {
  const [authenticated, setAuthenticated] = useState(Boolean(localStorage.getItem('isp_token')));
  const [page, setPage] = useState('dashboard');

  function logout() {
    localStorage.removeItem('isp_token');
    setAuthenticated(false);
  }

  if (!authenticated) return <CustomerLogin onLogin={() => setAuthenticated(true)} />;

  return (
    <div className="customer-shell">
      <aside className="customer-sidebar">
        <div className="brand"><span className="brand-mark">IS</span><div><strong>ISP Billing</strong><small>Customer Portal</small></div></div>
        <nav>
          <button className={page === 'dashboard' ? 'nav active' : 'nav'} onClick={() => setPage('dashboard')}>Dashboard</button>
          <button className={page === 'invoices' ? 'nav active' : 'nav'} onClick={() => setPage('invoices')}>Invoices</button>
          <button className={page === 'payments' ? 'nav active' : 'nav'} onClick={() => setPage('payments')}>Payments</button>
        </nav>
        <button className="nav logout" onClick={logout}>Sign out</button>
      </aside>
      <section className="customer-content">
        {page === 'dashboard' && <CustomerDashboard />}
        {page === 'invoices' && <CustomerDashboard />}
        {page === 'payments' && <CustomerDashboard />}
      </section>
    </div>
  );
}
