export default function LoadingState({ message = 'Loading...' }) {
  return (
    <div className="portal-loading">
      <div className="spinner"></div>
      <p>{message}</p>
    </div>
  );
}
