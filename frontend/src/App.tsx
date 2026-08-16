import { useEffect, useState } from 'react';

const apiUrl = import.meta.env.VITE_API_URL ?? 'http://localhost/api/v1';

export default function App() {
  const [status, setStatus] = useState<'checking' | 'online' | 'offline'>('checking');

  useEffect(() => {
    fetch(`${apiUrl}/health`)
      .then((response) => {
        if (!response.ok) throw new Error('API unavailable');
        setStatus('online');
      })
      .catch(() => setStatus('offline'));
  }, []);

  return (
    <main className="shell">
      <section className="card">
        <p className="eyebrow">ISP BILLING SYSTEM</p>
        <h1>Network & billing platform</h1>
        <p className="muted">
          The professional administration platform is being built module by module.
        </p>
        <div className={`status ${status}`}>
          <span className="dot" />
          API: {status}
        </div>
      </section>
    </main>
  );
}
