import axios from 'axios';

const api = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL || 'http://127.0.0.1:8000/api',
  headers: { Accept: 'application/json' }
});

api.interceptors.request.use(c => {
  const t = sessionStorage.getItem('vet_token');
  if (t) c.headers.Authorization = `Bearer ${t}`;
  return c;
});

api.interceptors.response.use(
  response => response,
  error => {
    if (error.response?.status === 401) {
      sessionStorage.removeItem('vet_token');
      window.location.reload();
    }
    return Promise.reject(error);
  }
);

export default api;
