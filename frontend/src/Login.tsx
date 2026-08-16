import { FormEvent, useState } from 'react';
import { api, setToken } from './api';

export default function Login({ onLogin }: { onLogin: () => void }) {
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState('');
  const [busy, setBusy] = useState(false);

  async function submit(event: FormEvent) {
    event.preventDefault(); setError(''); setBusy(true);
    try {
      const { data } = await api.post('/auth/login', { email, password });
      setToken(data.data.token); onLogin();
    } catch (e: any) {
      setError(e?.response?.data?.message ?? 'Login failed. Check your credentials.');
    } finally { setBusy(false); }
  }

  return <main className="loginPage"><form className="loginCard" onSubmit={submit}>
    <div className="brandMark">ISP</div><h1>ISP Billing</h1><p>Administrator sign in</p>
    <label>Email<input type="email" value={email} onChange={e => setEmail(e.target.value)} required /></label>
    <label>Password<input type="password" value={password} onChange={e => setPassword(e.target.value)} required /></label>
    {error && <div className="error">{error}</div>}
    <button className="primary" disabled={busy}>{busy ? 'Signing in…' : 'Sign in'}</button>
  </form></main>;
}
