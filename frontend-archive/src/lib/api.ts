const runtimeBase = typeof window !== 'undefined' ? `${window.location.origin}${(window as any).__APP_BASE ?? '/Smart-Procurement'}/api` : '/Smart-Procurement/api';
const API_BASE = import.meta.env.VITE_API_BASE ?? runtimeBase;

async function request(path: string, options: RequestInit = {}) {
  const url = `${API_BASE}/${path}`;
  const init: RequestInit = {
    credentials: 'include',
    headers: {},
    ...options
  };

  if (init.body && !(init.body instanceof FormData)) {
    (init.headers as Record<string, string>)['Content-Type'] = 'application/json';
  }

  const response = await fetch(url, init);
  const text = await response.text();
  const data = text ? JSON.parse(text) : null;
  if (!response.ok) {
    throw new Error(data?.error || response.statusText || 'Request failed');
  }
  return data;
}

export const authApi = {
  me: () => request('auth.php'),
  login: (email: string, password: string) => request('auth.php', {
    method: 'POST',
    body: JSON.stringify({ action: 'login', email, password })
  }),
  register: (name: string, email: string, password: string) => request('auth.php', {
    method: 'POST',
    body: JSON.stringify({ action: 'register', name, email, password })
  }),
  forgotPassword: (email: string) => request('auth.php', {
    method: 'POST',
    body: JSON.stringify({ action: 'forgot_password', email })
  }),
  resetPassword: (token: string, password: string) => request('auth.php', {
    method: 'POST',
    body: JSON.stringify({ action: 'reset_password', token, password })
  }),
  logout: () => request('auth.php', { method: 'DELETE' })
};

export const dashboardApi = {
  getOverview: () => request('dashboard.php')
};

export const procurementsApi = {
  list: () => request('procurements.php'),
  create: (payload: Record<string, unknown>) => request('procurements.php', { method: 'POST', body: JSON.stringify(payload) })
};

export const bidsApi = {
  list: (procurementId?: number) => request(`bids.php${procurementId ? `?procurement_id=${procurementId}` : ''}`),
  award: (bidId: number, procurementId: number) => request('bids.php', {
    method: 'POST',
    body: JSON.stringify({ action: 'award', bid_id: bidId, procurement_id: procurementId })
  }),
  submit: (formData: FormData) => fetch(`${API_BASE}/bids.php`, {
    method: 'POST',
    credentials: 'include',
    body: formData
  }).then(async (response) => {
    const text = await response.text();
    const data = text ? JSON.parse(text) : null;
    if (!response.ok) {
      throw new Error(data?.error || response.statusText || 'Request failed');
    }
    return data;
  })
};

export const opportunitiesApi = {
  list: (params: Record<string, string | number | undefined> = {}) => {
    const searchParams = new URLSearchParams();
    Object.entries(params).forEach(([key, value]) => {
      if (value || value === 0) {
        searchParams.set(key, String(value));
      }
    });
    return request(`opportunities.php?${searchParams.toString()}`);
  }
};

export const notificationsApi = {
  list: () => request('notifications.php'),
  markRead: (notificationId: number) => request('notifications.php', {
    method: 'POST',
    body: JSON.stringify({ action: 'mark_read', notification_id: notificationId })
  }),
  markAllRead: () => request('notifications.php', { method: 'POST', body: JSON.stringify({ action: 'mark_all_read' }) })
};

export const usersApi = {
  list: () => request('users.php'),
  me: () => request('profile.php'),
  update: (payload: Record<string, unknown>) => request('profile.php', { method: 'PUT', body: JSON.stringify(payload) })
};

export const suppliersApi = {
  list: () => request('suppliers.php')
};

export const reportsApi = {
  list: () => request('reports.php')
};

export const analyticsApi = {
  overview: () => request('analytics.php')
};
