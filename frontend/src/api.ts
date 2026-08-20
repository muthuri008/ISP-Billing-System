import axios, { type AxiosRequestConfig } from 'axios';

export type ApiRequestOptions = Omit<AxiosRequestConfig, 'data' | 'method'> & {
  method?: AxiosRequestConfig['method'];
  body?: unknown;
};

const client = axios.create({
  baseURL: import.meta.env.VITE_API_URL ?? 'http://localhost/api/v1',
  headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
});

client.interceptors.request.use((config) => {
  const token = localStorage.getItem('isp_token');
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

export async function api<T = unknown>(url: string, options: ApiRequestOptions = {}): Promise<T> {
  const { body, ...config } = options;
  const response = await client.request<T>({
    url,
    ...config,
    data: body,
  });
  return response.data;
}

export function setToken(token: string) {
  localStorage.setItem('isp_token', token);
}

export function clearToken() {
  localStorage.removeItem('isp_token');
}

export function hasToken() {
  return Boolean(localStorage.getItem('isp_token'));
}
