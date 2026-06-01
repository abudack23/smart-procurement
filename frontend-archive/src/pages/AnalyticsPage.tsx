import { useEffect, useState } from 'react';
import { Card } from '../components/ui/Card';
import { Button } from '../components/ui/Button';
import { analyticsApi } from '../lib/api';

export function AnalyticsPage() {
  const [analytics, setAnalytics] = useState<any>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  const loadAnalytics = async () => {
    setLoading(true);
    setError(null);
    try {
      const response = await analyticsApi.overview();
      setAnalytics(response);
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Unable to load analytics');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    loadAnalytics();
  }, []);

  const overview = analytics?.overview ?? {};
  const statusCounts = analytics?.status_counts ?? {};

  return (
    <div className="space-y-6">
      <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <p className="text-sm uppercase tracking-[0.24em] text-brand-600">Predictive analytics</p>
          <h1 className="text-3xl font-semibold text-slate-950 dark:text-slate-100">Demand and risk insights</h1>
          <p className="max-w-2xl text-sm text-slate-500 dark:text-slate-400">
            Review AI-driven forecasting, supplier risk, and procurement scorecards for strategic decisions.
          </p>
        </div>
        <Button onClick={loadAnalytics} variant="secondary">Refresh</Button>
      </div>

      {error && (
        <Card className="p-5 text-red-700 dark:text-red-300">
          <p>{error}</p>
        </Card>
      )}

      <div className="grid gap-6 xl:grid-cols-3">
        <Card className="p-5">
          <p className="text-sm uppercase tracking-[0.24em] text-slate-500 dark:text-slate-400">Open procurements</p>
          <p className="mt-4 text-3xl font-semibold text-slate-950 dark:text-slate-100">{overview.open_procurements ?? overview.total_bids ?? '—'}</p>
          <p className="mt-2 text-sm text-slate-500 dark:text-slate-400">Current open procurement activity or total supplier bids.</p>
        </Card>
        <Card className="p-5">
          <p className="text-sm uppercase tracking-[0.24em] text-slate-500 dark:text-slate-400">Active bids</p>
          <p className="mt-4 text-3xl font-semibold text-slate-950 dark:text-slate-100">{overview.active_bids ?? overview.wins ?? '—'}</p>
          <p className="mt-2 text-sm text-slate-500 dark:text-slate-400">Pending bids or supplier wins for your account.</p>
        </Card>
        <Card className="p-5">
          <p className="text-sm uppercase tracking-[0.24em] text-slate-500 dark:text-slate-400">Supplier reliability</p>
          <p className="mt-4 text-3xl font-semibold text-slate-950 dark:text-slate-100">{overview.reliability ? `${overview.reliability}%` : overview.win_rate ? `${overview.win_rate}%` : '—'}</p>
          <p className="mt-2 text-sm text-slate-500 dark:text-slate-400">AI-led reliability score or win rate for supplier activity.</p>
        </Card>
      </div>

      <Card className="p-5">
        <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
          <div>
            <h2 className="text-xl font-semibold text-slate-950 dark:text-slate-100">Procurement status</h2>
            <p className="text-sm text-slate-500 dark:text-slate-400">Live procurement counts by status.</p>
          </div>
          <Button onClick={loadAnalytics} variant="ghost">Update</Button>
        </div>

        <div className="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
          {['open', 'closed'].map((key) => (
            <div key={key} className="rounded-3xl border border-slate-200/70 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-900">
              <p className="text-sm uppercase tracking-[0.24em] text-slate-500 dark:text-slate-400">{key}</p>
              <p className="mt-3 text-2xl font-semibold text-slate-950 dark:text-slate-100">{statusCounts[key] ?? 0}</p>
            </div>
          ))}
        </div>
      </Card>
    </div>
  );
}
