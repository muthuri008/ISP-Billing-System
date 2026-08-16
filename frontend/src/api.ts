import axios from 'axios';

export const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL ?? 'http://localhost/api/v1',
});

api.interceptors.request.use((config) => {
  const token = localStorage.getItem('isp_token');
  if (token) config.headers.Authorization = `Bearer ${token}`;
  return config;
});

export function setToken(token: string) { localStorage.setItem('isp_token', token); }
export function clearToken() { localStorage.removeItem('isp_token'); }
export function hasToken() { return Boolean(localStorage.getItem('isp_token')); }
