import { useEffect, useState } from 'react';
import api from '../api';

export default function AppointmentSelector({ value, onChange, petId, label = 'Completed appointment', required = false }) {
  const [appointments, setAppointments] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  useEffect(() => {
    let active = true;
    api.get('/appointments', { params: { status: 'completed', per_page: 100 } })
      .then(response => { if (active) setAppointments(response.data.data || []); })
      .catch(() => { if (active) setError('Could not load completed appointments.'); })
      .finally(() => { if (active) setLoading(false); });
    return () => { active = false; };
  }, []);

  const visibleAppointments = appointments.filter(appointment => !petId || Number(appointment.pet_id) === Number(petId));

  return (
    <label>
      {label}
      <select value={value ?? ''} onChange={event => onChange(event.target.value ? Number(event.target.value) : '')} required={required} disabled={loading || Boolean(error)}>
        <option value="">{loading ? 'Loading appointments…' : error || 'No linked appointment (optional)'}</option>
        {visibleAppointments.map(appointment => (
          <option key={appointment.id} value={appointment.id}>
            #{appointment.id} · {appointment.pet?.name || 'Pet'} · {appointment.service?.name || 'Service'} · {new Date(appointment.appointment_date).toLocaleString()}
          </option>
        ))}
      </select>
      {!loading && !error && visibleAppointments.length === 0 && <small>Complete an appointment first if you want to link this record.</small>}
    </label>
  );
}
