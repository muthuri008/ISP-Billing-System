import { useEffect, useState } from 'react';
import { api } from './api';

type Router = { id:number; name:string; hostname:string; status:string; last_seen_at?:string|null };

export default function NetworkPage() {
  const [routers, setRouters] = useState<Router[]>([]);
  const [selected, setSelected] = useState<number | null>(null);
  const [sessions, setSessions] = useState<any[]>([]);
  const [message, setMessage] = useState('');
  const load = async () => { const { data } = await api.get('/network/routers'); setRouters(data.data ?? data); };
  useEffect(() => { load().catch(() => setMessage('Unable to load routers. Check your permissions and API.')); }, []);
  async function health(router: Router) { const { data } = await api.get(`/network/routers/${router.id}/health`); setRouters(rs => rs.map(r => r.id === router.id ? data.router : r)); }
  async function showSessions(router: Router) { setSelected(router.id); const { data } = await api.get(`/network/routers/${router.id}/sessions`); setSessions(data.data ?? []); }
  async function disconnect(router: Router, sessionId: string) { await api.post(`/network/routers/${router.id}/sessions/disconnect`, { session_id: sessionId }); setMessage('Session disconnected.'); await showSessions(router); }
  return <section className="content"><div className="panel"><div className="panelHead"><h3>Routers</h3><button onClick={() => load()}>Refresh</button></div>{message && <p>{message}</p>}<div className="tableWrap"><table><thead><tr><th>Name</th><th>Host</th><th>Status</th><th>Actions</th></tr></thead><tbody>{routers.map(r => <tr key={r.id}><td>{r.name}</td><td>{r.hostname}</td><td><span className={`status ${r.status}`}>{r.status}</span></td><td><button onClick={() => health(r)}>Check</button> <button onClick={() => showSessions(r)}>Sessions</button></td></tr>)}</tbody></table></div></div>{selected && <div className="panel"><div className="panelHead"><h3>Active sessions</h3></div>{sessions.length === 0 ? <p>No active sessions reported by this router.</p> : <div className="tableWrap"><table><thead><tr><th>Session</th><th>User</th><th>IP</th><th>Action</th></tr></thead><tbody>{sessions.map(s => <tr key={s.session_id}><td>{s.session_id}</td><td>{s.username ?? '—'}</td><td>{s.address ?? s.ip_address ?? '—'}</td><td><button onClick={() => { const r = routers.find(x => x.id === selected); if (r) disconnect(r, s.session_id); }}>Disconnect</button></td></tr>)}</tbody></table></div>}</div>}</section>;
}
