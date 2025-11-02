// Helper to build API URLs from an env variable (Vite injects VITE_API_BASE)
// Default to local Apache path (port 80) so Vite dev server requests go to XAMPP-served PHP.
export const API_BASE = import.meta.env.VITE_API_BASE || 'http://localhost/shenieva-teacher/src/lib/api';

export function apiUrl(path) {
  const base = API_BASE.replace(/\/$/, '');
  const p = path.replace(/^\//, '');
  return `${base}/${p}`;
}

export default apiUrl;
