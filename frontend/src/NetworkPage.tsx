import { FormEvent, useEffect, useState } from 'react';
import { api } from './api';

type Router = { id:number; name:string; hostname:string; status:string; rest_scheme?:string; rest_port?:number; last_seen_at?:string|null };
type Session = { session_id:string; username?:string; address?:string; ip_address?:string };
type RouterDraft = { name:string; hostname:string; rest_scheme:'https'|'http'; rest_port:string; api_username:string; api_password:string; radius_secret:string };

const emptyRouter: RouterDraft = {
  name: '', hostname: '', rest_scheme: 'https', rest_port: '443',
  api_username: '', api_password: '', radius_secret: '',
};

export default function NetworkPage() {
  const [routers, setRouters] = useState<Router[]>([]);
  const [selected, setSelected] = useState<number | null>(null);
  const [sessions, setSessions] = useState<Session[]>([]);
  const [message, setMessage] = useState('');
  const [busy, setBusy] = useState(false);
  const [showForm, setShowForm] = useState(false);
  const [saving, setSaving] = useState(false);
  const [draft, setDraft] = useState<RouterDraft>(emptyRouter);

  const load = async () => {
    const response = await api<{data:Router[]}>('/network/routers');
    setRouters(Array.isArray(response.data) ? response.data : []);
  };

  useEffect(() => {
    load().catch(() => setMessage('Unable to load routers. Check your permissions and API.'));
  }, []);

  async function createRouter(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setSaving(true);
    setMessage('');
    try {
      await api('/network/routers', {
        method: 'POST',
        body: JSON.stringify({ ...draft, rest_port: Number(draft.rest_port) }),
      });
      setDraft(emptyRouter);
      setShowForm(false);
      setMessage('Router configuration saved. Run Check to verify the connection.');
      await load();
    } catch (error: unknown) {
      const apiMessage = error && typeof error === 'object' && 'response' in error
        ? (error as {response?:{data?:{message?:string}}}).response?.data?.message
        : undefined;
      setMessage(apiMessage ?? (error instanceof Error ? error.message : 'Unable to save router.'));
    } finally {
      setSaving(false);
    }
  }

  async function health(router: Router) {
    try {
      const result = await api<{router?:Router;online?:boolean}>('/network/routers/' + router.id + '/health');
      if (result.router) setRouters(rs => rs.map(r => r.id === router.id ? result.router! : r));
      setMessage(result.online ? router.name + ' is reachable.' : 'No successful response from ' + router.name + '.');
    } catch {
      setMessage('Health check failed for ' + router.name + '.');
    }
  }

  async function showSessions(router: Router) {
    setSelected(router.id);
    setBusy(true);
    try {
      const response = await api<{data:Session[]}>('/network/routers/' + router.id + '/sessions');
      setSessions(Array.isArray(response.data) ? response.data : []);
      setMessage('');
    } catch {
      setSessions([]);
      setMessage('Unable to load sessions for ' + router.name + '.');
    } finally {
      setBusy(false);
    }
  }

  async function disconnect(router: Router, sessionId: string) {
    try {
      await api('/network/routers/' + router.id + '/sessions/disconnect', {
        method: 'POST',
        body: JSON.stringify({ session_id: sessionId }),
      });
      setMessage('Disconnect request completed.');
      await showSessions(router);
    } catch {
      setMessage('Unable to disconnect this session.');
    }
  }

  return <section className="content">
    <div className="welcome">
      <div><small>NETWORK MANAGEMENT</small><h2>Routers</h2><p>Configure MikroTik REST access and monitor connected routers.</p></div>
      <button className="primary" onClick={() => setShowForm(value => !value)}>{showForm ? 'Cancel' : '+ Add Router'}</button>
    </div>
    {showForm && <form className="panel formGrid" onSubmit={createRouter}>
      <h3>New MikroTik router</h3>
      <label>Router name<input required maxLength={120} value={draft.name} onChange={e => setDraft({...draft,name:e.target.value})} /></label>
      <label>Hostname or IP<input required maxLength={255} placeholder="192.168.88.1 or router.example.com" value={draft.hostname} onChange={e => setDraft({...draft,hostname:e.target.value})} /></label>
      <label>REST protocol<select value={draft.rest_scheme} onChange={e => setDraft({...draft,rest_scheme:e.target.value as RouterDraft['rest_scheme']})}><option value="https">HTTPS (recommended)</option><option value="http">HTTP (development only)</option></select></label>
      <label>REST port<input required type="number" min="1" max="65535" value={draft.rest_port} onChange={e => setDraft({...draft,rest_port:e.target.value})} /></label>
      <label>API username<input required autoComplete="off" value={draft.api_username} onChange={e => setDraft({...draft,api_username:e.target.value})} /></label>
      <label>API password<input required type="password" autoComplete="new-password" value={draft.api_password} onChange={e => setDraft({...draft,api_password:e.target.value})} /></label>
      <label className="full">RADIUS shared secret (optional)<input type="password" autoComplete="new-password" value={draft.radius_secret} onChange={e => setDraft({...draft,radius_secret:e.target.value})} /></label>
      <div className="formActions"><button type="button" onClick={() => {setShowForm(false);setDraft(emptyRouter);}}>Cancel</button><button className="primary" disabled={saving}>{saving ? 'Saving…' : 'Save router'}</button></div>
      <p className="full">Use RouterOS v7 REST API with HTTPS enabled and a trusted certificate. In production, HTTP connections are rejected.</p>
    </form>}
    <div className="panel">
      <div className="panelHead"><h3>Configured routers</h3><button onClick={() => load().catch(() => setMessage('Unable to refresh routers.'))}>Refresh</button></div>
      {message && <p role="status">{message}</p>}
      <div className="tableWrap"><table><thead><tr><th>Name</th><th>Host</th><th>REST endpoint</th><th>Status</th><th>Actions</th></tr></thead><tbody>
        {routers.map(r => <tr key={r.id}><td>{r.name}</td><td>{r.hostname}</td><td>{(r.rest_scheme ?? 'https') + ':' + (r.rest_port ?? 443)}</td><td><span className={'status ' + r.status}>{r.status}</span></td><td><button onClick={() => health(r)}>Check</button> <button onClick={() => showSessions(r)}>Sessions</button></td></tr>)}
        {routers.length === 0 && <tr><td colSpan={5}>No routers configured yet.</td></tr>}
      </tbody></table></div>
    </div>
    {selected !== null && <div className="panel">
      <div className="panelHead"><h3>Active sessions</h3></div>
      {busy ? <p>Loading sessions…</p> : sessions.length === 0 ? <p>No active sessions reported by this router.</p> : <div className="tableWrap"><table><thead><tr><th>Session</th><th>User</th><th>IP</th><th>Action</th></tr></thead><tbody>
        {sessions.map(s => <tr key={s.session_id}><td>{s.session_id}</td><td>{s.username ?? '—'}</td><td>{s.address ?? s.ip_address ?? '—'}</td><td><button onClick={() => { const r = routers.find(x => x.id === selected); if (r) disconnect(r, s.session_id); }}>Disconnect</button></td></tr>)}
      </tbody></table></div>}
    </div>}
  </section>;
}
