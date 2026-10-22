import { useState, useEffect } from 'react';
import api from '../api';

export default function ServiceSelector({ value, onChange, label, required }) {
  const [services, setServices] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    api.get('/services')
      .then(r => setServices(r.data.data || []))
      .catch(() => {})
      .finally(() => setLoading(false));
  }, []);

  const availableServices = services.filter(service => service.is_available);

  return (
    <label>
      {label}
      {loading ? (
        <input type="text" disabled value="Loading services…" />
      ) : (
        <select
          value={value ?? ''}
          onChange={e => onChange(e.target.value ? Number(e.target.value) : '')}
          required={required}
        >
          <option value="">{availableServices.length ? 'Select a service' : 'No active services available'}</option>
        {availableServices.map(s => (
            <option key={s.id} value={s.id}>
              {s.name} — ₱{s.price} ({s.duration_minutes} min)
            </option>
          ))}
        </select>
      )}
      {!loading && availableServices.length === 0 && <small>Contact the clinic if you need help booking a service.</small>}
    </label>
  );
}
