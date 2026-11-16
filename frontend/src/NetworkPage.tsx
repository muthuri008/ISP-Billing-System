import { useEffect, useState } from 'react';
import { api } from './api';

type Router = { id:number; name:string; hostname:string; status:string; last_seen_at?:string|null };
type Session = { session_id:string; username?:string; address?:string; ip_address?:string };

export default function NetworkPage() {
  const [routers, setRouters] = useState<Router[]>([]);
  const [selected, setSelected] = useState<number | null>(null);
  const [sessions, setSessions] = useState<Session[]>([]);
  const [message, setMessage] = useState('');
  const [busy, setBusy] = useState(false);

  const load = async () => {
    const result = await api<Router[]>('/network/routers');
    setRouters(Array.isArray(result) ? result : []);
  };

  useEffect(() => {
    load().catch(() => setMessage('Unable to load routers. Check your permissions and API.'));
  }, []);

  async function health(router: Router) {
    try {
      const result = await api<{router?:Router}>(`/network/routers/${router.id}/health`);
      if (result.router) setRouters(rs => rs.map(r => r.id === router.id ? result.router! : r));
      setMessage('Router health check completed.');
    } catch {
      setMessage(`Health check failed for ${router.name}.`);
    }
  }

  async function showSessions(router: Router) {
    setSelected(router.id);
    setBusy(true);
    try {
      const result = await api<Session[]>(`/network/routers/${router.id}/sessions`);
      setSessions(Array.isArray(result) ? result : []);
      setMessage('');
    } catch {
      setSessions([]);
      setMessage(`Unable to load sessions for ${router.name}.`);
    } finally {
      setBusy(false);
    }
  }

  async function disconnect(router: Router, sessionId: string) {
    try {
      await api(`/network/routers/${router.id}/sessions/disconnect`, {
        method: 'POST',
        body: JSON.stringify({ session_id: sessionId }),
      });
      setMessage('Disconnect request completed.');
      await showSessions(router);
    } catch {
      setMessage('Unable to disconnect this session.');
    }
  }

  return <section className="content"><div className="panel"><div className="panelHead"><h3>Routers</h3><button onClick={() => load().catch(() => setMessage('Unable to refresh routers.'))}>Refresh</button></div>{message && <p role="status">{message}</p>}<div className="tableWrap"><table><thead><tr><th>Name</th><th>Host</th><th>Status</th><th>Actions</th></tr></thead><tbody>{routers.map(r => <tr key={r.id}><td>{r.name}</td><td>{r.hostname}</td><td><span className={`status ${r.status}`}>{r.status}</span></td><td><button onClick={() => health(r)}>Check</button> <button onClick={() => showSessions(r)}>Sessions</button></td></tr>)}</tbody></table></div></div>{selected !== null && <div className="panel"><div className="panelHead"><h3>Active sessions</h3></div>{busy ? <p>Loading sessions…</p> : sessions.length === 0 ? <p>No active sessions reported by this router.</p> : <div className="tableWrap"><table><thead><tr><th>Session</th><th>User</th><th>IP</th><th>Action</th></tr></thead><tbody>{sessions.map(s => <tr key={s.session_id}><td>{s.session_id}</td><td>{s.username ?? '—'}</td><td>{s.address ?? s.ip_address ?? '—'}</td><td><button onClick={() => { const r = routers.find(x => x.id === selected); if (r) disconnect(r, s.session_id); }}>Disconnect</button></td></tr>)}</tbody></table></div>}</div>}</section>;
}
