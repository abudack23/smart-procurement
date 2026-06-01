import { useEffect, useState } from 'react';
import { Card } from '../components/ui/Card';
import { Button } from '../components/ui/Button';
import { notificationsApi } from '../lib/api';

export function NotificationsPage() {
  const [notifications, setNotifications] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [marking, setMarking] = useState(false);

  const loadNotifications = async () => {
    setLoading(true);
    setError(null);
    try {
      const response = await notificationsApi.list();
      setNotifications(response.notifications ?? []);
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Unable to load notifications');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    loadNotifications();
  }, []);

  const handleMarkAllRead = async () => {
    setMarking(true);
    setError(null);
    try {
      await notificationsApi.markAllRead();
      await loadNotifications();
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Unable to mark notifications read');
    } finally {
      setMarking(false);
    }
  };

  return (
    <div className="space-y-6">
      <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <p className="text-sm uppercase tracking-[0.24em] text-brand-600">Notifications</p>
          <h1 className="text-3xl font-semibold text-slate-950 dark:text-slate-100">Real-time alerts</h1>
          <p className="max-w-2xl text-sm text-slate-500 dark:text-slate-400">Stay ahead of new bids, approvals, and analytic warnings.</p>
        </div>
        <Button onClick={handleMarkAllRead} variant="secondary" disabled={marking}>
          {marking ? 'Updating…' : 'Mark all read'}
        </Button>
      </div>

      {error && (
        <Card className="p-5 text-red-700 dark:text-red-300">
          <p>{error}</p>
        </Card>
      )}

      <div className="grid gap-4">
        {loading ? (
          <Card className="p-5 text-slate-500 dark:text-slate-400">Loading notifications...</Card>
        ) : notifications.length === 0 ? (
          <Card className="p-5 text-slate-500 dark:text-slate-400">No notifications yet.</Card>
        ) : (
          notifications.map((note) => (
            <Card key={note.id ?? note.title} className="p-5">
              <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                  <p className="text-lg font-semibold text-slate-950 dark:text-slate-100">{note.title}</p>
                  {note.type && <p className="text-sm text-slate-500 dark:text-slate-400">{note.type}</p>}
                  {note.message && <p className="mt-2 text-sm text-slate-600 dark:text-slate-300">{note.message}</p>}
                </div>
                <span className="text-sm text-slate-400 dark:text-slate-500">{note.created_at ? new Date(note.created_at).toLocaleString() : 'Unknown'}</span>
              </div>
            </Card>
          ))
        )}
      </div>
    </div>
  );
}
