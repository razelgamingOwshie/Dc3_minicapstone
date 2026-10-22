import { useEffect, useState } from 'react';
import api from '../api';

export default function OwnerSelector({ value, onChange, label = 'Pet owner', required = true }) {
  const [owners, setOwners] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  useEffect(() => {
    let active = true;
    api.get('/owners', { params: { per_page: 100 } })
      .then(response => { if (active) setOwners(response.data.data || []); })
      .catch(() => { if (active) setError('Could not load pet owners.'); })
      .finally(() => { if (active) setLoading(false); });
    return () => { active = false; };
  }, []);

  return (
    <label>
      {label}
      <select value={value ?? ''} onChange={event => onChange(event.target.value ? Number(event.target.value) : '')} required={required} disabled={loading || Boolean(error)}>
        <option value="">{loading ? 'Loading pet owners…' : error || 'Select a pet owner'}</option>
        {owners.map(owner => <option key={owner.id} value={owner.id}>{owner.name} ({owner.email})</option>)}
      </select>
      {!loading && !error && owners.length === 0 && <small>No pet-owner accounts exist yet. Register an owner first.</small>}
    </label>
  );
}
