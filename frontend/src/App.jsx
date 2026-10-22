import { useEffect, useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import api from './api';
import StatusBadge from './components/StatusBadge.jsx';
import LoadingState from './components/LoadingState.jsx';
import EmptyState from './components/EmptyState.jsx';
import PetSelector from './components/PetSelector.jsx';
import ServiceSelector from './components/ServiceSelector.jsx';
import OwnerSelector from './components/OwnerSelector.jsx';
import AppointmentSelector from './components/AppointmentSelector.jsx';

import './AdminDashboard.css';
import './PetOwnerDashboard.css';

const labels = {
  pets: 'Pets',
  services: 'Clinic services',
  appointments: 'Appointments',
  'medical-records': 'Medical records',
  vaccinations: 'Vaccinations',
  owners: 'Pet owners'
};

const fields = {
  pets: [['user_id', 'Pet owner', 'owner'], ['name', 'Pet name', 'text', true], ['species', 'Species', 'text', true], ['breed', 'Breed', 'text'], ['sex', 'Sex', 'select'], ['birth_date', 'Birth date', 'date'], ['weight', 'Weight (kg)', 'number'], ['notes', 'Notes', 'textarea']],
  services: [['name', 'Service name', 'text', true], ['description', 'Description', 'textarea'], ['price', 'Price (PHP)', 'number', true], ['duration_minutes', 'Duration (minutes)', 'number', true], ['is_available', 'Available', 'boolean']],
  appointments: [['pet_id', 'Pet', 'number', true], ['service_id', 'Veterinary service', 'number', true], ['appointment_date', 'Date and time', 'datetime-local', true], ['reason', 'Reason', 'textarea']],
  'medical-records': [['pet_id', 'Pet', 'number', true], ['appointment_id', 'Related appointment (optional)', 'appointment'], ['diagnosis', 'Diagnosis', 'textarea', true], ['treatment', 'Treatment', 'textarea'], ['notes', 'Notes', 'textarea'], ['record_date', 'Record date', 'date', true]],
  vaccinations: [['pet_id', 'Pet', 'number', true], ['vaccine_name', 'Vaccine name', 'text', true], ['date_administered', 'Date administered', 'date'], ['next_due_date', 'Next due date', 'date'], ['notes', 'Notes', 'textarea']],
  owners: [['name', 'Owner name', 'text', true], ['email', 'Email address', 'email', true]]
};

function toLocalDateTimeInput(value) {
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return String(value).slice(0, 16).replace(' ', 'T');
  const pad = number => String(number).padStart(2, '0');
  return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`;
}

function App() {
  const [user, setUser] = useState(null);
  const [page, setPage] = useState('home');
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState('');
  const [notice, setNotice] = useState('');

  const nav = useNavigate();

  useEffect(() => {
    if (sessionStorage.getItem('vet_token')) {
      api.get('/user')
        .then(r => setUser(r.data.user))
        .catch(() => {
          sessionStorage.removeItem('vet_token');
          setUser(null);
        });
    }
  }, []);

  const logout = async () => {
    try { await api.post('/logout'); } catch {}
    sessionStorage.removeItem('vet_token');
    setUser(null);
    setPage('home');
    nav('/');
  };

  const logoEmoji = '🐾';

  return (
    <div className="app">
      <header className="top">
        <Link className="brand" to="/" onClick={() => setPage('home')}>
          <span className="logo-emoji" aria-hidden="true">{logoEmoji}</span>
          <span className="brand-text">kindred<span>vet</span></span>
        </Link>
        {user ? (
          <>
            <div className="welcome">Good care starts here, <strong>{user.name}</strong></div>
            <button className="btn outline" onClick={logout}>Log out ↗</button>
          </>
        ) : (
          <div className="top-buttons">
            <button className="plain" onClick={() => setPage('login')}>Log in</button>
            <button className="btn dark" onClick={() => setPage('register')}>Pet owner sign up ↗</button>
          </div>
        )}
      </header>
{user ? (
        <Workspace user={user} page={page} setPage={setPage} notice={notice} setNotice={setNotice} />
      ) : (page === 'login' || page === 'register') ? (
        <Auth mode={page} setUser={setUser} setPage={setPage} setError={setError} error={error} busy={busy} setBusy={setBusy} />
      ) : (
        <Landing setPage={setPage} user={user} />
      )}

      <footer>
        <div className="footer-brand">
          <span className="logo-emoji logo-small" aria-hidden="true">{logoEmoji}</span>
          <span className="brand-text brand-small">kindred<span>vet</span></span>
        </div>
        <span>Thoughtful care for every companion.</span>
        <span>Mini-capstone · Veterinary Clinic Management System</span>
      </footer>
    </div>
  );
}

function Landing({ setPage, user }) {
  const nav = useNavigate();
  
  // Animal photo URLs from Unsplash (free to use)
  const heroPhoto = 'https://images.unsplash.com/photo-1583337130417-3346a1be7dee?w=800&h=600&fit=crop&auto=format';
  const featurePhoto = 'https://images.unsplash.com/photo-1548199973-03cce0bbc87b?w=600&h=400&fit=crop&auto=format';
  const ctaPhoto = 'https://images.unsplash.com/photo-1552053831-71594a27632d?w=400&h=300&fit=crop&auto=format';
  const tilePhotos = [
    'https://images.unsplash.com/photo-1583337130417-3346a1be7dee?w=400&h=300&fit=crop&auto=format',
    'https://images.unsplash.com/photo-1592194996308-7b43878e84a6?w=400&h=300&fit=crop&auto=format',
    'https://images.unsplash.com/photo-1548199973-03cce0bbc87b?w=400&h=300&fit=crop&auto=format',
    'https://images.unsplash.com/photo-1601758228041-f3b2795255f1?w=400&h=300&fit=crop&auto=format',
  ];

  return (
    <>
      <section className="hero">
        <div className="hero-copy">
          <p className="eyebrow">✳ THOUGHTFUL CARE, EVERY DAY</p>
          <h1>Good care for<br /><em>good companions.</em></h1>
          <p>A calmer way to manage pet visits, services and health records. Keep the important details together, so you can focus on the companions who make life brighter.</p>
          <div className="actions">
            <button className="btn dark" onClick={() => setPage('register')}>Get started ↗</button>
            <a className="btn outline" href="#services">Explore services</a>
          </div>
          <div className="note">✳ &nbsp; Care that feels a little more like home.</div>
        </div>
        <div className="hero-photo">
          <img src={heroPhoto} alt="Happy golden retriever at vet clinic" className="hero-image" />
        </div>
      </section>

      <div className="ribbon">CARE WITH HEART　 ·　 RECORDS MADE SIMPLE　 ·　 HERE FOR EVERY PAW　 ·　 YOUR CLINIC, CONNECTED</div>

      <section className="section" id="services">
        <div className="section-head">
          <div>
            <p className="eyebrow">HERE FOR THE WHOLE JOURNEY</p>
            <h2>Care for every <em>little milestone.</em></h2>
          </div>
          <p>From everyday wellness to important health details, we make the practical parts of pet care feel easier.</p>
        </div>
        <div className="tiles">
          {[
            ['✦', 'Wellness exams', 'Routine checkups and preventive care.', 'appointments', tilePhotos[0]],
            ['♡', 'Vaccinations', 'Keep vaccination dates and due dates organized.', 'vaccinations', tilePhotos[1]],
            ['＋', 'Consultations', 'Record diagnoses, treatments and visit history.', 'appointments', tilePhotos[2]],
            ['⌁', 'Pet health records', 'A clearer view of your companion care.', 'medical-records', tilePhotos[3]],
          ].map((x, i) => (
            <article className="tile" key={x[1]}>
              <img src={x[4]} alt={x[1]} className="tile-image" loading="lazy" />
              <div className="tile-content">
                <div className="tile-icon">{x[0]}</div>
                <h3>{x[1]}</h3>
                <p>{x[2]}</p>
                {user ? (
                  <button className="inline" onClick={() => { nav('/'); setPage('workspace'); }}>Go to dashboard ↗</button>
                ) : (
                  <button className="inline" onClick={() => setPage('register')}>Get started ↗</button>
                )}
              </div>
            </article>
          ))}
        </div>
      </section>

      <section className="feature">
        <div className="feature-photo">
          <img src={featurePhoto} alt="Veterinarian caring for a dog" className="feature-image" />
        </div>
        <div className="feature-copy">
          <p className="eyebrow">A MORE THOUGHTFUL EXPERIENCE</p>
          <h2>Less paperwork.<br /><em>More nose boops.</em></h2>
          <p>Pet care comes with a lot to remember. Kindred Vet brings profiles, appointment requests and health records together in one place.</p>
          <button className="btn outline light" onClick={() => setPage('register')}>Create your account ↗</button>
        </div>
      </section>

      <section className="cta">
        <div>
          <p className="eyebrow">YOUR NEXT VISIT STARTS HERE</p>
          <h2>Care begins with<br /><em>a simple hello.</em></h2>
          <p>Create an account to manage your pets and request an appointment with the clinic.</p>
          <button className="btn dark" onClick={() => setPage('register')}>Create pet owner account ↗</button>
        </div>
        <div className="cta-card">
          <img src={ctaPhoto} alt="Cat and dog together" className="cta-image" />
          <div className="cta-card-content">
            <b>✳</b>
            <h3>One home for their care.</h3>
            <p>Appointments, services and health history — organized around the pets you love.</p>
          </div>
        </div>
      </section>
    </>
  );
}

function Auth({ mode, setUser, setPage, setError, error, busy, setBusy }) {
  const [form, setForm] = useState({ name: '', email: '', password: '', password_confirmation: '' });
  const nav = useNavigate();

  const submit = async e => {
    e.preventDefault();
    setBusy(true);
    setError('');
    try {
      const r = await api.post(mode === 'login' ? '/login' : '/register', form);
      sessionStorage.setItem('vet_token', r.data.token);
      setUser(r.data.user);
      setPage('workspace');
      nav('/');
    } catch (e) {
      setError(
        Object.values(e.response?.data?.errors || {}).flat().join(' ') ||
        e.response?.data?.message ||
        'Unable to connect. Check that Laravel is running.'
      );
    } finally {
      setBusy(false);
    }
  };

  return (
    <main className="auth">
      <form className="auth-box" onSubmit={submit}>
        <button type="button" className="inline" onClick={() => setPage('home')}>← Back to home</button>
        <p className="eyebrow">{mode === 'login' ? 'WELCOME BACK' : 'A GOOD PLACE TO START'}</p>
        <h1>{mode === 'login' ? 'Lovely to see you again.' : "Let's meet your companion."}</h1>
        <p className="muted">
          {mode === 'login' ? 'Sign in to your clinic account.' : 'Create a pet-owner account to manage pets and appointments.'}
        </p>
        {mode === 'register' && (
          <label>Full name<input required value={form.name} onChange={e => setForm({ ...form, name: e.target.value })} /></label>
        )}
        <label>Email address<input type="email" required value={form.email} onChange={e => setForm({ ...form, email: e.target.value })} /></label>
        <label>Password<input type="password" minLength="8" required value={form.password} onChange={e => setForm({ ...form, password: e.target.value })} /></label>
        {mode === 'register' && (
          <label>Confirm password<input type="password" required value={form.password_confirmation} onChange={e => setForm({ ...form, password_confirmation: e.target.value })} /></label>
        )}
        {error && <p className="error">{error}</p>}
        <button className="btn dark full" disabled={busy}>
          {busy ? 'Please wait…' : mode === 'login' ? 'Log in ↗' : 'Create account ↗'}
        </button>
        <p className="switch">
          {mode === 'login' ? 'New here?' : 'Already have an account?'}
          <button type="button" className="inline" onClick={() => { setError(''); setPage(mode === 'login' ? 'register' : 'login'); }}>
            {mode === 'login' ? 'Create an account' : 'Log in'}
          </button>
        </p>
      </form>
    </main>
  );
}

function Workspace({ user, page, setPage, notice, setNotice }) {
  const [resource, setResource] = useState(user.role === 'admin' ? 'dashboard' : 'pet-portal');
  const [stats, setStats] = useState(null);

  useEffect(() => {
    if (user.role === 'admin') {
      api.get('/dashboard').then(r => setStats(r.data)).catch(() => {});
    }
  }, [user, resource]);

  const navItems = user.role === 'admin'
    ? [['dashboard', 'Overview'], ['appointments', 'Appointments'], ['pets', 'Pets'], ['owners', 'Pet owners'], ['services', 'Services'], ['medical-records', 'Medical records'], ['vaccinations', 'Vaccinations']]
    : [['pet-portal', 'Pet Care Portal'], ['pets', 'My Pets'], ['appointments', 'Appointments'], ['services', 'Clinic Services'], ['medical-records', 'Medical Records'], ['vaccinations', 'Vaccinations']];

  return (
    <main className="workspace">
      <aside className="sidebar">
        <p className="eyebrow">{user.role === 'admin' ? 'CLINIC WORKSPACE' : 'YOUR PET CARE'}</p>
        <div className="user-chip">
          <div className="avatar">{user.name?.slice(0, 1).toUpperCase()}</div>
          <div><strong>{user.name}</strong><small>{user.role === 'admin' ? 'Clinic administrator' : 'Pet owner'}</small></div>
        </div>
        {navItems.map(([key, label]) => (
          <button key={key} className={`side-link ${resource === key ? 'active' : ''}`} onClick={() => setResource(key)}>
            {label}<span>↗</span>
          </button>
        ))}
      </aside>
      <section className="work-content">
        {notice && <div className="success">{notice}<button onClick={() => setNotice('')}>×</button></div>}
        {resource === 'dashboard' && user.role === 'admin' ? (
          <Dashboard stats={stats} setResource={setResource} />
        ) : resource === 'pet-portal' ? (
          <PetOwnerPortal user={user} setNotice={setNotice} setResource={setResource} />
        ) : (
          <ResourcePage resource={resource} user={user} setNotice={setNotice} />
        )}
      </section>
    </main>
  );
}

function Dashboard({ stats, setResource, user }) {
  const statCards = [
    { label: 'Registered pets', value: stats?.total_pets ?? '—', resource: 'pets', variant: 'pets', icon: '🐾' },
    { label: 'Appointments today', value: stats?.appointments_today ?? '—', resource: 'appointments', variant: 'appointments', icon: '📅' },
    { label: 'Pending requests', value: stats?.pending_appointments ?? '—', resource: 'appointments', variant: 'pending', icon: '⏳' },
    { label: 'Completed visits', value: stats?.completed_consultations ?? '—', resource: 'medical-records', variant: 'completed', icon: '✅' },
    { label: 'Vaccines due soon', value: stats?.upcoming_vaccinations ?? '—', resource: 'vaccinations', variant: 'vaccines', icon: '💉' },
  ];

  const quickLinks = [
    { title: 'Pet owners', description: 'Manage registered pet owners', resource: 'owners', icon: '👥' },
    { title: 'Pets', description: 'View and manage all pets', resource: 'pets', icon: '🐾' },
    { title: 'Appointments', description: 'Review and process appointments', resource: 'appointments', icon: '📅' },
    { title: 'Services', description: 'Manage clinic services and pricing', resource: 'services', icon: '🩺' },
    { title: 'Medical records', description: 'Clinical records and visit history', resource: 'medical-records', icon: '📋' },
    { title: 'Vaccinations', description: 'Track vaccination schedules and due dates', resource: 'vaccinations', icon: '💉' },
  ];

  return (
    <div className="admin-overview">
      <header className="admin-overview__header">
        <p className="admin-overview__eyebrow">YOUR CLINIC AT A GLANCE</p>
        <h1 className="admin-overview__greeting">Good morning, <em>{user?.name?.split(' ')[0] || 'team'}.</em></h1>
        <p className="admin-overview__subtitle">Here&apos;s what&apos;s happening at your clinic today.</p>
      </header>

      <section className="admin-stats" aria-label="Clinic statistics">
        {statCards.map(card => (
          <button
            key={card.label}
            className={`admin-stat-card admin-stat-card--${card.variant}`}
            onClick={() => setResource(card.resource)}
            aria-label={`View ${card.label}`}
          >
            <div className="admin-stat-card__top">
              <span className="admin-stat-card__icon" aria-hidden="true">{card.icon}</span>
              <span className="admin-stat-card__arrow" aria-hidden="true">→</span>
            </div>
            <p className="admin-stat-card__label">{card.label}</p>
            <p className="admin-stat-card__value">{card.value}</p>
            <span className="admin-stat-card__link">View details →</span>
          </button>
        ))}
      </section>

      <section className="admin-dashboard-panel" aria-label="Vaccination attention">
        <div className="admin-vaccination-panel">
          <span className="admin-vaccination-panel__icon" aria-hidden="true">💉</span>
          <div className="admin-vaccination-panel__content">
            <h2 className="admin-vaccination-panel__title">Vaccinations needing attention</h2>
            <p className="admin-vaccination-panel__description">
              {stats?.upcoming_vaccinations ?? 0} vaccination{stats?.upcoming_vaccinations === 1 ? '' : 's'} due within 30 days or overdue.
            </p>
          </div>
          <span className="admin-vaccination-panel__count" aria-label="Vaccination count">{stats?.upcoming_vaccinations ?? 0}</span>
        </div>
      </section>

      <section className="admin-workspace" aria-label="Quick workspace access">
        <header className="admin-workspace__header">
          <h2 className="admin-workspace__title">Clinic workspace</h2>
          <p className="admin-workspace__description">Everything you need to keep the clinic running smoothly.</p>
        </header>
        <div className="admin-workspace__grid">
          {quickLinks.map(link => (
            <button
              key={link.title}
              className="admin-quick-link"
              onClick={() => setResource(link.resource)}
              aria-label={`${link.title} management`}
            >
              <span className="admin-quick-link__icon" aria-hidden="true">{link.icon}</span>
              <div className="admin-quick-link__content">
                <p className="admin-quick-link__title">{link.title}</p>
                <p className="admin-quick-link__description">{link.description}</p>
              </div>
              <span className="admin-quick-link__arrow" aria-hidden="true">→</span>
            </button>
          ))}
        </div>
      </section>
    </div>
  );
}

function PetOwnerPortal({ user, setNotice, setResource }) {
  const [pets, setPets] = useState([]);
  const [appointments, setAppointments] = useState([]);
  const [vaccinations, setVaccinations] = useState([]);
  const [medicalRecords, setMedicalRecords] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  useEffect(() => {
    const loadAll = async () => {
      setLoading(true);
      setError('');
      try {
        const [petsRes, apptsRes, vaccRes, recordsRes] = await Promise.all([
          api.get('/pets', { params: { per_page: 100 } }),
          api.get('/appointments', { params: { per_page: 100 } }),
          api.get('/vaccinations', { params: { per_page: 100 } }),
          api.get('/medical-records', { params: { per_page: 100 } })
        ]);
        setPets(petsRes.data.data || []);
        setAppointments(apptsRes.data.data || []);
        setVaccinations(vaccRes.data.data || []);
        setMedicalRecords(recordsRes.data.data || []);
      } catch (e) {
        setError('Failed to load pet care data. Please try again.');
      } finally {
        setLoading(false);
      }
    };
    loadAll();
  }, []);

  const upcomingVaccinations = vaccinations
    .filter(v => v.next_due_date && new Date(v.next_due_date) >= new Date())
    .sort((a, b) => new Date(a.next_due_date) - new Date(b.next_due_date));

  const pastVaccinations = vaccinations
    .filter(v => !v.next_due_date || new Date(v.next_due_date) < new Date())
    .sort((a, b) => new Date(b.date_administered || 0) - new Date(a.date_administered || 0));

  const vaccinationCount = upcomingVaccinations.length + pastVaccinations.length;

  if (loading) {
    return (
      <div className="pet-portal__empty" style={{ padding: '60px', textAlign: 'center' }}>
        <div style={{ fontSize: '28px', marginBottom: '16px' }}>⏳</div>
        <p>Loading your pet care portal...</p>
      </div>
    );
  }

  if (error) {
    return (
      <div className="pet-portal__empty" style={{ padding: '40px', textAlign: 'center' }}>
        <div style={{ fontSize: '28px', marginBottom: '16px', color: '#a33a2b' }}>⚠️</div>
        <p>{error}</p>
        <button className="btn dark" onClick={() => window.location.reload()}>Retry</button>
      </div>
    );
  }

  const navCards = [
    { title: 'My Pets', description: 'Profiles and pet details', resource: 'pets', variant: '', icon: '🐾' },
    { title: 'Appointments', description: 'Bookings and visit status', resource: 'appointments', variant: 'appointments', icon: '📅' },
    { title: 'Clinic Services', description: 'Explore available care', resource: 'services', variant: 'services', icon: '🩺' },
    { title: 'Medical Records', description: 'Health history and visits', resource: 'medical-records', variant: 'records', icon: '📋' },
    { title: 'Vaccinations', description: 'Vaccines and due dates', resource: 'vaccinations', variant: 'vaccinations', icon: '💉' },
    { title: 'Pet Care Portal', description: 'Your personal care hub', resource: 'pet-portal', variant: 'overview', icon: '🏠' },
  ];

  return (
    <div className="pet-portal">
      <header className="pet-portal__header">
        <div className="pet-portal__brand-icon" aria-hidden="true">🐾</div>
        <div className="pet-portal__brand">
          <h2>Kindred Vet Clinic</h2>
          <p>PET OWNER PORTAL</p>
        </div>
      </header>
      <hr className="pet-portal__divider" />

      <div className="pet-portal__intro">
        <p className="pet-portal__eyebrow">Your pet's wellness, all in one place</p>
        <h1 className="pet-portal__greeting">Welcome back, <em>pet parent.</em></h1>
        <p className="pet-portal__subtitle">Everything you need to care for your furry family, in one place.</p>
      </div>

      <div className="pet-portal__welcome">
        <span className="pet-portal__welcome-icon" aria-hidden="true">🏠</span>
        <div>
          <h2>Your pet care hub</h2>
          <p>Manage appointments, health records, and everyday care.</p>
        </div>
      </div>

      <h2 className="pet-portal__section-heading">Explore your portal</h2>

      <div className="pet-portal__grid">
        {navCards.map(card => (
          <button
            key={card.title}
            className={`pet-portal__card ${card.variant ? `pet-portal__card--${card.variant}` : ''}`}
            onClick={() => setResource(card.resource)}
            aria-label={`${card.title} management`}
          >
            <div className="pet-portal__card-top">
              <span className="pet-portal__card-icon" aria-hidden="true">{card.icon}</span>
              <span className="pet-portal__card-arrow" aria-hidden="true">→</span>
            </div>
            <h3 className="pet-portal__card-title">{card.title}</h3>
            <p className="pet-portal__card-description">{card.description}</p>
            <span className="pet-portal__card-action">Open →</span>
          </button>
        ))}
      </div>
    </div>
  );
}

function ResourcePage({ resource, user, setNotice }) {
  const [rows, setRows] = useState([]);
  const [meta, setMeta] = useState(null);
  const [search, setSearch] = useState('');
  const [status, setStatus] = useState('');
  const [page, setPage] = useState(1);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [editing, setEditing] = useState(null);
  const [form, setForm] = useState({});

  const isAdmin = user.role === 'admin';
  const config = (fields[resource] || []).filter(([key]) => key !== 'user_id' || isAdmin);
  const [appointmentDate, setAppointmentDate] = useState('');
  const [dueFilter, setDueFilter] = useState('');

  const load = async () => {
    setLoading(true);
    setError('');
    try {
      const r = await api.get('/' + resource, {
        params: {
          search,
          page,
          per_page: 10,
          ...(resource === 'appointments' && status ? { status } : {}),
          ...(resource === 'appointments' && appointmentDate ? { date: appointmentDate } : {}),
          ...(resource === 'vaccinations' && dueFilter ? { due: dueFilter } : {})
        }
      });
      const d = r.data;
      setRows(d.data || []);
      setMeta(d);
    } catch (e) {
      setError(e.response?.data?.message || 'Could not load records.');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    load();
  }, [resource, page, search, status, appointmentDate, dueFilter]);

  const startNew = () => {
    setEditing('new');
    setForm({ is_available: true, ...(resource === 'pets' && isAdmin ? { user_id: '' } : {}) });
  };

  const save = async e => {
    e.preventDefault();
    setError('');
    const payload = { ...form };
    if (resource === 'appointments' && payload.appointment_date) {
      payload.appointment_date = payload.appointment_date.replace('T', ' ') + (payload.appointment_date.length === 16 ? ':00' : '');
    }
    for (const k of ['weight', 'price', 'duration_minutes', 'pet_id', 'service_id', 'appointment_id']) {
      if (payload[k] === '') delete payload[k];
    }
    try {
      if (editing === 'new') {
        await api.post('/' + resource, payload);
      } else {
        await api.put('/' + resource + '/' + editing, payload);
      }
      setEditing(null);
      setNotice('Record saved successfully.');
      load();
    } catch (e) {
      setError(
        Object.values(e.response?.data?.errors || {}).flat().join(' ') ||
        e.response?.data?.message ||
        'Unable to save record.'
      );
    }
  };

  const remove = async id => {
    if (!confirm('Delete this record? This action may be blocked if historical records depend on it.')) return;
    try {
      await api.delete('/' + resource + '/' + id);
      setNotice('Record deleted.');
      load();
    } catch (e) {
      setError(e.response?.data?.message || 'Could not delete record.');
    }
  };

  const updateStatus = async (row, next) => {
    try {
      await api.put('/appointments/' + row.id, { status: next });
      setNotice('Appointment status updated.');
      load();
    } catch (e) {
      setError(e.response?.data?.message || 'Could not update status.');
    }
  };

  const displayColumns = {
    pets: ['name', 'species', 'breed', 'sex', 'birth_date', 'weight', ...(isAdmin ? ['user_id'] : [])],
    services: ['name', 'description', 'price', 'duration_minutes', 'is_available'],
    appointments: [...(isAdmin ? ['user_id'] : []), 'pet_id', 'service_id', 'appointment_date', 'status', 'reason'],
    'medical-records': ['pet_id', 'appointment_id', 'diagnosis', 'record_date'],
    vaccinations: ['pet_id', 'vaccine_name', 'date_administered', 'next_due_date'],
    owners: ['name', 'email', 'created_at']
  };
  const keys = displayColumns[resource] || ['id'];

  return (
    <>
      <div className="resource-head">
        <div>
          <p className="eyebrow">MANAGE YOUR RECORDS</p>
          <h1>{labels[resource] || resource}</h1>
          <p className="muted">Search, review and maintain clinic records.</p>
        </div>
        {(isAdmin && resource !== 'owners' || ['pets', 'appointments'].includes(resource) && !isAdmin) && (
          <button className="btn dark" onClick={startNew}>＋ Add new</button>
        )}
      </div>

      {error && <p className="error">{error}</p>}

      <div className="filters">
        <input placeholder="Search records…" value={search} onChange={e => { setPage(1); setSearch(e.target.value); }} />
        {resource === 'appointments' && (
          <select value={status} onChange={e => { setPage(1); setStatus(e.target.value); }}>
            <option value="">All statuses</option>
            {['pending', 'approved', 'completed', 'cancelled'].map(s => <option key={s} value={s}>{s}</option>)}
          </select>
        )}
        {resource === 'appointments' && <input aria-label="Filter appointments by date" type="date" value={appointmentDate} onChange={e => { setPage(1); setAppointmentDate(e.target.value); }} />}
        {resource === 'vaccinations' && <select aria-label="Filter vaccinations by due status" value={dueFilter} onChange={e => { setPage(1); setDueFilter(e.target.value); }}><option value="">All due dates</option><option value="overdue">Overdue</option><option value="soon">Due in 30 days</option><option value="later">Due later</option></select>}
      </div>

      {editing !== null && (
        <form className="edit-form" onSubmit={save}>
          <div className="form-title">
            <h2>{editing === 'new' ? 'Create record' : 'Edit record'}</h2>
            <button type="button" className="plain" onClick={() => setEditing(null)}>Close ×</button>
          </div>
          <div className="form-grid">
            {(config.length ? config : []).map(([key, label, type, required]) => {
              if (key === 'user_id' && resource === 'pets' && isAdmin) {
                if (editing !== 'new') return null;
                return <OwnerSelector key={key} value={form[key] ?? ''} onChange={v => setForm({ ...form, user_id: v })} label={label} required={true} />;
              }
              if (key === 'pet_id' && (resource === 'appointments' || resource === 'medical-records' || resource === 'vaccinations')) {
                return (
                  <PetSelector
                    key={key}
                    label={label}
                    required={required}
                    value={form[key] ? Number(form[key]) : ''}
                    onChange={v => setForm({ ...form, [key]: v ? Number(v) : '', ...(resource === 'medical-records' && key === 'pet_id' ? { appointment_id: '' } : {}) })}
                  />
                );
              }
              if (key === 'appointment_id' && resource === 'medical-records') {
                return <AppointmentSelector key={key} value={form[key] ?? ''} petId={form.pet_id} onChange={v => setForm({ ...form, appointment_id: v })} label={label} required={false} />;
              }
              if (key === 'service_id' && resource === 'appointments') {
                return (
                  <ServiceSelector
                    key={key}
                    label={label}
                    required={required}
                    value={form[key] ? Number(form[key]) : ''}
                    onChange={v => setForm({ ...form, [key]: v ? Number(v) : '' })}
                  />
                );
              }
              return (
                <label key={key}>
                  {label}
                  {type === 'textarea' ? (
                    <textarea required={required} value={form[key] ?? ''} onChange={e => setForm({ ...form, [key]: e.target.value })} />
                  ) : type === 'select' ? (
                    <select value={form[key] ?? ''} onChange={e => setForm({ ...form, [key]: e.target.value })}>
                      <option value="">Choose</option>
                      <option>Male</option>
                      <option>Female</option>
                      <option>Unknown</option>
                    </select>
                  ) : type === 'boolean' ? (
                    <input type="checkbox" checked={!!form[key]} onChange={e => setForm({ ...form, [key]: e.target.checked })} />
                  ) : resource === 'appointments' && key === 'appointment_date' ? (
                    <input
                      type="datetime-local"
                      required={required}
                      value={form[key] ? toLocalDateTimeInput(form[key]) : ''}
                      onChange={e => setForm({ ...form, [key]: e.target.value })}
                    />
                  ) : (
                    <input
                      type={type}
                      required={required}
                      min={type === 'number' ? '0' : undefined}
                      value={form[key] ?? ''}
                      onChange={e => setForm({ ...form, [key]: e.target.value })}
                    />
                  )}
                </label>
              );
            })}
          </div>
          <button className="btn dark">{editing === 'new' ? 'Create record' : 'Save changes'}</button>
        </form>
      )}

      <div className="table-wrap">
        {loading ? (
          <p className="muted pad">Loading records…</p>
        ) : rows.length === 0 ? (
          <div className="empty">
            <span>✳</span>
            <h3>No records found</h3>
            <p>Try a different search or add a record if your role permits it.</p>
          </div>
        ) : (
          <table>
            <thead>
              <tr>
                {keys.map(k => <th key={k}>{k.replaceAll('_', ' ')}</th>)}
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              {rows.map(row => (
                <tr key={row.id}>
                  {keys.map(k => (
                    <td key={k}>
                      {k === 'status' && resource === 'appointments' ? (
                        <StatusBadge status={row[k]} />
                      ) : typeof row[k] === 'object' && row[k] !== null ? (
                        row[k].name || row[k].vaccine_name || JSON.stringify(row[k])
                      ) : k === 'pet_id' && row.pet ? (
                        <span>{row.pet.name} ({row.pet.species})</span>
                      ) : k === 'service_id' && row.service ? (
                        <span>{row.service.name} — ₱{row.service.price}</span>
                      ) : k === 'appointment_id' && row.appointment ? (
                        <span>{row.appointment.id}</span>
                      ) : k === 'user_id' && row.owner ? (
                        <span>{row.owner.name}</span>
                      ) : k === 'owner_id' && row.owner ? (
                        <span>{row.owner.name}</span>
                      ) : k === 'is_available' ? (
                        row[k] ? 'Yes' : 'No'
                      ) : k === 'next_due_date' && resource === 'vaccinations' && row[k] ? (
                        (() => {
                          const due = new Date(row[k]);
                          const diff = Math.ceil((due - new Date()) / (1000 * 60 * 60 * 24));
                          if (diff < 0) return <span className="badge badge-err">Overdue</span>;
                          if (diff <= 30) return <span className="badge badge-warn">Due soon ({diff}d)</span>;
                          return <span className="badge badge-ok">Up to date</span>;
                        })()
                      ) : ['birth_date', 'date_administered', 'next_due_date', 'record_date'].includes(k) && row[k] ? String(row[k]).slice(0, 10) : typeof row[k] === 'number' && !row[k] ? '—' : String(row[k] ?? '—')}
                    </td>
                  ))}
                  <td>
                    <div className="row-actions">
                      {(isAdmin || resource === 'pets') && (
                        <button onClick={() => {
                          setEditing(row.id);
                          const values = Object.fromEntries(config.map(([field, , type]) => {
                            let value = row[field] ?? '';
                            if (value && type === 'date') value = String(value).slice(0, 10);
                            if (value && type === 'datetime-local') value = toLocalDateTimeInput(value);
                            return [field, value];
                          }));
                          setForm(values);
                        }}>Edit</button>
                      )}
                      {isAdmin && (
                        <button onClick={() => remove(row.id)}>Delete</button>
                      )}
                      {resource === 'appointments' && isAdmin && row.status === 'pending' && (
                        <button onClick={() => updateStatus(row, 'approved')}>Approve</button>
                      )}
                      {resource === 'appointments' && isAdmin && row.status === 'approved' && (
                        <button onClick={() => updateStatus(row, 'completed')}>Complete</button>
                      )}
                      {resource === 'appointments' && !isAdmin && row.status === 'pending' && (
                        <button onClick={() => updateStatus(row, 'cancelled')}>Cancel</button>
                      )}
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        )}
      </div>

      {meta && (
        <div className="pagination">
          <span>Showing {meta.from || 0}–{meta.to || 0} of {meta.total || 0}</span>
          <div>
            <button disabled={!meta.prev_page_url} onClick={() => setPage(p => Math.max(1, p - 1))}>← Previous</button>
            <span>Page {meta.current_page || page} / {meta.last_page || 1}</span>
            <button disabled={!meta.next_page_url} onClick={() => setPage(p => p + 1)}>Next →</button>
          </div>
        </div>
      )}

      <p className="tip">Access control is enforced by the API. Pet and service fields use dropdown selectors. Status changes are validated by the backend.</p>
    </>
  );
}

export default App;
