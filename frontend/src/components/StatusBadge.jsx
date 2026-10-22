import { useState, useEffect } from 'react';
import api from '../api';

export default function StatusBadge({ status }) {
  const colors = { pending: 'badge-warn', approved: 'badge-ok', completed: 'badge-info', cancelled: 'badge-err' };
  const labels = { pending: 'Pending', approved: 'Approved', completed: 'Completed', cancelled: 'Cancelled' };
  return (
    <span className={`badge ${colors[status] || ''}`}>
      {labels[status] || status}
    </span>
  );
}
