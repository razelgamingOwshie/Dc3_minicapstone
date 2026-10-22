export default function EmptyState({ title = 'No records found', message = 'There are no records to display at this time.', icon = '✳' }) {
  return (
    <div className="empty">
      <span>{icon}</span>
      <h3>{title}</h3>
      <p>{message}</p>
    </div>
  );
}
