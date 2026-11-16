import { FormEvent, useState } from 'react';
import { api, setToken } from './api';

type LoginResponse = { token: string };

export default function Login({ onLogin }: { onLogin: () => void }) {
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState('');
  const [busy, setBusy] = useState(false);

  async function submit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setError('');
    setBusy(true);
    try {
      const result = await api<LoginResponse>('/auth/login', {
        method: 'POST',
        body: JSON.stringify({ email, password }),
      });
      if (!result?.token) throw new Error('The API did not return an access token.');
      setToken(result.token);
      onLogin();
    } catch (e: unknown) {
      setError(e instanceof Error ? e.message : 'Login failed. Check your credentials.');
    } finally {
      setBusy(false);
    }
  }

  return <main className="loginPage"><form className="loginCard" onSubmit={submit}>
    <div className="brandMark">ST</div><h1>Stellar Technologies</h1><p>Administrator sign in</p>
    <label>Email<input type="email" autoComplete="username" value={email} onChange={e => setEmail(e.target.value)} required /></label>
    <label>Password<input type="password" autoComplete="current-password" value={password} onChange={e => setPassword(e.target.value)} required /></label>
    {error && <div className="error" role="alert">{error}</div>}
    <button className="primary" disabled={busy}>{busy ? 'Signing in…' : 'Sign in'}</button>
  </form></main>;
}
