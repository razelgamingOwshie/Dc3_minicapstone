import { useState, useEffect } from 'react';
import api from '../api';

export default function PetSelector({ value, onChange, label, required, includeNone = false }) {
  const [pets, setPets] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    api.get('/pets')
      .then(r => setPets(r.data.data || []))
      .catch(() => {})
      .finally(() => setLoading(false));
  }, []);

  return (
    <label>
      {label}
      {loading ? (
        <input type="text" disabled value="Loading pets…" />
      ) : (
        <select
          value={value ?? ''}
          onChange={e => onChange(e.target.value ? Number(e.target.value) : '')}
          required={required}
        >
          <option value="">{includeNone ? 'None' : (pets.length ? 'Select a pet' : 'No pets available')}</option>
          {pets.map(p => (
            <option key={p.id} value={p.id}>
              {p.name} ({p.species})
            </option>
          ))}
        </select>
      )}
      {!loading && pets.length === 0 && <small>Add a pet before booking or recording care.</small>}
    </label>
  );
}
