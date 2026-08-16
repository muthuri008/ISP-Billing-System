import { useState } from 'react';

const API = import.meta.env.VITE_API_URL || 'http://localhost:8000/api/v1';

export default function CustomerLogin({ onLogin }) {
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState('');
  const [busy, setBusy] = useState(false);

  async function submit(event) {
    event.preventDefault(); setBusy(true); setError('');
    try {
      const response = await fetch(`${API}/auth/login`, { method: 'POST', headers: {'Content-Type':'application/json', Accept:'application/json'}, body: JSON.stringify({email,password}) });
      const payload = await response.json();
      if (!response.ok) throw new Error(payload.message || 'Login failed');
      localStorage.setItem('isp_token', payload.token || payload.data?.token);
      onLogin?.();
    } catch (err) { setError(err.message); } finally { setBusy(false); }
  }

  return <main className="login-page"><form className="login-card" onSubmit={submit}><div className="brand-mark">IS</div><span className="eyebrow">CUSTOMER PORTAL</span><h1>Welcome back</h1><p>Sign in to manage your ISP account.</p>{error && <div className="alert error">{error}</div>}<label>Email<input type="email" value={email} onChange={e=>setEmail(e.target.value)} required autoComplete="email" /></label><label>Password<input type="password" value={password} onChange={e=>setPassword(e.target.value)} required autoComplete="current-password" /></label><button className="primary" disabled={busy}>{busy ? 'Signing in…' : 'Sign in'}</button></form></main>;
}
