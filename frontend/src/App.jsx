import { useState } from 'react'
import './styles.css'

const stats = [
  ['Customers', '1,248', '+8.2%'],
  ['Active services', '1,109', '+4.6%'],
  ['Outstanding', 'KES 384,650', '-2.1%'],
  ['Online sessions', '327', '+12.4%'],
]

export default function App() {
  const [active, setActive] = useState('Dashboard')
  const navigation = ['Dashboard', 'Customers', 'Packages', 'Subscriptions', 'Invoices', 'Payments', 'Routers', 'RADIUS', 'Sessions', 'Reports']
  return <div className="app">
    <aside className="sidebar">
      <div className="brand"><span className="brand-mark">IS</span><div><strong>ISP Billing</strong><small>Administration</small></div></div>
      <nav>{navigation.map(item => <button className={active === item ? 'nav active' : 'nav'} onClick={() => setActive(item)} key={item}>{item}</button>)}</nav>
      <div className="sidebar-footer">System online<br/><span>Production ready architecture</span></div>
    </aside>
    <main className="main">
      <header><div><p className="eyebrow">ISP MANAGEMENT</p><h1>{active}</h1></div><div className="user">Administrator <span>AD</span></div></header>
      {active === 'Dashboard' ? <>
        <section className="cards">{stats.map(([label,value,change]) => <article className="card" key={label}><span>{label}</span><strong>{value}</strong><em>{change}</em></article>)}</section>
        <section className="grid"><article className="panel"><div className="panel-head"><h2>Service health</h2><span className="status">All systems operational</span></div><div className="health"><div><strong>98.7%</strong><span>Network availability</span></div><div><strong>147</strong><span>Active customers today</span></div><div><strong>6</strong><span>Suspended services</span></div></div></article><article className="panel"><div className="panel-head"><h2>Recent activity</h2></div><ul className="activity"><li><b>Payment received</b><span>KES 1,500 · M-Pesa</span></li><li><b>Service restored</b><span>Customer account activated</span></li><li><b>Router online</b><span>Core-Router-01</span></li></ul></article></section>
      </> : <section className="panel empty"><h2>{active}</h2><p>This module is connected to the platform architecture and will be populated with its management interface in the next frontend build stage.</p></section>}
    </main>
  </div>
}
