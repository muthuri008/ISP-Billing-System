import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';
import { BrowserRouter } from 'react-router-dom';
import App from './App';
import CustomerPortalApp from './CustomerPortalApp';
import './styles.css';

const isCustomerPortal = window.location.pathname.startsWith('/customer');

createRoot(document.getElementById('root')!).render(
  <StrictMode>
    <BrowserRouter>
      {isCustomerPortal ? <CustomerPortalApp /> : <App />}
    </BrowserRouter>
  </StrictMode>,
);
