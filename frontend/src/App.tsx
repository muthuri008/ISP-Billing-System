import { useEffect, useState } from 'react';
import './admin.css';
import { clearToken, hasToken } from './api';

const menu = [
  'Dashboard',
  'Customers',
  'Packages',
  'Subscriptions',
  'Invoices',
  'Payments',
  'Settlement',
  'Service Accounts',
  'Routers',
  'RADIUS',
  'Sessions',
  'Reports',
];

type ApiStatus = 'checking' | 'online' | 'offline';

export default function App() {
  const [authenticated, setAuthenticated] = useState(hasToken());
  const [status, setStatus] = useState<ApiStatus>('checking');
  const [active, setActive] = useState('Dashboard');

  useEffect(() => {
    const baseUrl = import.meta.env.VITE_API_URL ?? 'http://localhost/api/v1';
    fetch(`${baseUrl.replace(/\/$/, '')}/health`)
      .then((response) => {
        if (!response.ok) throw new Error('Health check failed');
        setStatus('online');
      })
      .catch(() => setStatus('offline'));
  }, []);

  if (!authenticated) {
    return <Login onLogin={() => setAuthenticated(true)} />;
  }

  return (
    <div className="app">
      <aside className="sidebar">
        <div className="brand">
          <div className="brandMark">ST</div>
          <div>
            <strong>Stellar Technologies</strong>
            <small>ISP Billing Administration</small>
          </div>
        </div>

        <nav aria-label="Main navigation">
          {menu.map((item) => (
            <button
              key={item}
              type="button"
              className={active === item ? 'navItem active' : 'navItem'}
              onClick={() => setActive(item)}
            >
              <span aria-hidden="true">{icon(item)}</span>
              {item}
            </button>
          ))}
        </nav>

        <div className="sideStatus">
          <span className={`dot ${status}`} />
          API {status}
        </div>
      </aside>

      <main className="main">
        <header className="topbar">
          <div>
            <small>STELLAR TECHNOLOGIES</small>
            <h1>{active}</h1>
          </div>

          <div className="profile">
            <div className="avatar">ST</div>
            <span>Administrator</span>
            <button
              type="button"
              className="logout"
              onClick={() => {
                clearToken();
                setAuthenticated(false);
              }}
            >
              Sign out
            </button>
          </div>
        </header>

        {active === 'Dashboard' ? (
          <Dashboard status={status} />
        ) : (
          <Module title={active} />
        )}
      </main>
    </div>
  );
}

function Login({ onLogin }: { onLogin: () => void }) {
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState('');

  async function submit(event: React.FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setError('');

    try {
      const baseUrl = import.meta.env.VITE_API_URL ?? 'http://localhost/api/v1';
      const response = await fetch(`${baseUrl.replace(/\/$/, '')}/login`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          Accept: 'application/json',
        },
        body: JSON.stringify({ email, password }),
      });

      if (!response.ok) throw new Error('Invalid credentials');

      const payload = (await response.json()) as { token?: string; data?: { token?: string } };
      const token = payload.token ?? payload.data?.token;

      if (!token) throw new Error('Login succeeded but no API token was returned');

      localStorage.setItem('isp_token', token);
      onLogin();
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Unable to sign in');
    }
  }

  return (
    <main className="loginPage">
      <form className="loginCard" onSubmit={submit}>
        <div className="brandMark">ST</div>
        <h1>Stellar Technologies</h1>
        <p>ISP Billing Administration</p>

        {error && <div className="error">{error}</div>}

        <label>
          Email
          <input
            type="email"
            value={email}
            onChange={(event) => setEmail(event.target.value)}
            autoComplete="username"
            required
          />
        </label>

        <label>
          Password
          <input
            type="password"
            value={password}
            onChange={(event) => setPassword(event.target.value)}
            autoComplete="current-password"
            required
          />
        </label>

        <button className="primary" type="submit">
          Sign in
        </button>
      </form>
    </main>
  );
}

function Dashboard({ status }: { status: ApiStatus }) {
  return (
    <section className="content">
      <div className="welcome">
        <div>
          <small>STELLAR TECHNOLOGIES</small>
          <h2>Good day, Administrator</h2>
          <p>Manage ISP billing and network operations from one place.</p>
        </div>
      </div>

      <div className="stats">
        <Stat title="Customers" value="—" note="Live records will appear here" />
        <Stat title="Active Services" value="—" note="Live subscription metrics" />
        <Stat title="Outstanding" value="—" note="Live finance metrics" />
        <Stat title="Online Sessions" value="—" note="Live network metrics" />
      </div>

      <div className="grid">
        <article className="panel">
          <div className="panelHead">
            <h3>API health</h3>
            <span className="healthy">{status}</span>
          </div>
          <p>
            The frontend is using the configured Laravel API base URL. Configure
            <code> VITE_API_URL </code> per environment instead of changing application code.
          </p>
        </article>

        <article className="panel">
          <div className="panelHead">
            <h3>Phase 0</h3>
          </div>
          <p>Frontend foundation stabilized. Billing and network modules can now be added incrementally.</p>
        </article>
      </div>
    </section>
  );
}

function Stat({ title, value, note }: { title: string; value: string; note: string }) {
  return (
    <article className="stat">
      <small>{title}</small>
      <strong>{value}</strong>
      <span>{note}</span>
    </article>
  );
}

function Module({ title }: { title: string }) {
  return (
    <section className="content">
      <div className="panel">
        <h2>{title}</h2>
        <p>
          This navigation entry is reserved for the corresponding Stellar Technologies
          service module. The Phase 0 goal is a clean, buildable foundation without fake
          production metrics.
        </p>
      </div>
    </section>
  );
}

function icon(item: string) {
  const icons: Record<string, string> = {
    Dashboard: '⌂',
    Customers: '◉',
    Packages: '▣',
    Subscriptions: '↻',
    Invoices: '▤',
    Payments: '₿',
    Settlement: '✓',
    'Service Accounts': '♙',
    Routers: '⌁',
    RADIUS: '◈',
    Sessions: '◌',
    Reports: '▥',
  };

  return icons[item] ?? '•';
}
