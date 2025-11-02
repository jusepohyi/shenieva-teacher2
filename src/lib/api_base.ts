// Helper to build API URLs from an env variable (Vite injects VITE_API_BASE)
// Default to local Apache path so the dev server (Vite) can call PHP served by XAMPP on port 80.
// Override with VITE_API_BASE for Hostinger/Netlify deployments (e.g. "https://example.com/shenieva-teacher/src/lib/api").
export const API_BASE: string = (import.meta.env.VITE_API_BASE as string) || 'http://localhost/shenieva-teacher/src/lib/api';

export function apiUrl(path: string): string {
  const base = API_BASE.replace(/\/$/, '');
  const p = path.replace(/^\//, '');
  return `${base}/${p}`;
}

export default apiUrl;
