import { useEffect, useState } from 'react';
import { Card } from '../components/ui/Card';
import { Button } from '../components/ui/Button';
import { reportsApi } from '../lib/api';
import { useAuthContext } from '../context/AuthContext';

export function ReportsPage() {
  const { user } = useAuthContext();
  const [reportData, setReportData] = useState<any>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  const loadReports = async () => {
    setLoading(true);
    setError(null);
    try {
      const response = await reportsApi.list();
      setReportData(response);
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Unable to load reports');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    if (user?.role === 'admin') {
      loadReports();
    }
  }, [user]);

  if (user?.role !== 'admin') {
    return (
      <div className="space-y-6">
        <Card className="p-5">
          <h1 className="text-2xl font-semibold text-slate-950 dark:text-slate-100">Admin access required</h1>
          <p className="mt-2 text-sm text-slate-500 dark:text-slate-400">
            Reporting is only available to admin users.
          </p>
        </Card>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <p className="text-sm uppercase tracking-[0.24em] text-brand-600">Reports</p>
          <h1 className="text-3xl font-semibold text-slate-950 dark:text-slate-100">Business insights</h1>
          <p className="max-w-2xl text-sm text-slate-500 dark:text-slate-400">Generate reports for procurement activity, supplier engagement, and audit requirements.</p>
        </div>
        <Button onClick={loadReports} variant="secondary">Refresh</Button>
      </div>

      {error && (
        <Card className="p-5 text-red-700 dark:text-red-300">
          <p>{error}</p>
        </Card>
      )}

      <div className="grid gap-6 lg:grid-cols-3">
        <Card className="p-5">
          <p className="text-sm uppercase tracking-[0.24em] text-slate-500 dark:text-slate-400">Procurements</p>
          <p className="mt-3 text-3xl font-semibold text-slate-950 dark:text-slate-100">{reportData?.procurements?.length ?? '—'}</p>
          <p className="mt-2 text-sm text-slate-500 dark:text-slate-400">Requests with bid history and performance counts.</p>
        </Card>
        <Card className="p-5">
          <p className="text-sm uppercase tracking-[0.24em] text-slate-500 dark:text-slate-400">Suppliers</p>
          <p className="mt-3 text-3xl font-semibold text-slate-950 dark:text-slate-100">{reportData?.suppliers?.length ?? '—'}</p>
          <p className="mt-2 text-sm text-slate-500 dark:text-slate-400">Supplier performance and awarded bid summaries.</p>
        </Card>
        <Card className="p-5">
          <p className="text-sm uppercase tracking-[0.24em] text-slate-500 dark:text-slate-400">Audit entries</p>
          <p className="mt-3 text-3xl font-semibold text-slate-950 dark:text-slate-100">{reportData?.audit?.length ?? '—'}</p>
          <p className="mt-2 text-sm text-slate-500 dark:text-slate-400">Recent actions and compliance events.</p>
        </Card>
      </div>

      <Card className="p-5">
        <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
          <div>
            <h2 className="text-xl font-semibold text-slate-950 dark:text-slate-100">Latest audit activity</h2>
            <p className="mt-1 text-sm text-slate-500 dark:text-slate-400">Activity captured from admin and supplier workflows.</p>
          </div>
          <Button onClick={loadReports} variant="secondary">Reload</Button>
        </div>
        <div className="mt-6 overflow-x-auto">
          <table className="min-w-full text-left text-sm text-slate-600 dark:text-slate-300">
            <thead className="border-b border-slate-200 dark:border-slate-800">
              <tr>
                <th className="px-4 py-4 font-semibold">Timestamp</th>
                <th className="px-4 py-4 font-semibold">User</th>
                <th className="px-4 py-4 font-semibold">Action</th>
                <th className="px-4 py-4 font-semibold">Details</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-200 dark:divide-slate-800">
              {loading ? (
                <tr>
                  <td colSpan={4} className="px-4 py-6 text-center text-sm text-slate-500 dark:text-slate-400">
                    Loading audit log...
                  </td>
                </tr>
              ) : reportData?.audit?.length ? (
                reportData.audit.map((entry: any) => (
                  <tr key={entry.id} className="transition hover:bg-slate-50 dark:hover:bg-slate-900/70">
                    <td className="px-4 py-4">{entry.created_at ? new Date(entry.created_at).toLocaleString() : 'N/A'}</td>
                    <td className="px-4 py-4">{entry.user_name ?? 'System'}</td>
                    <td className="px-4 py-4">{entry.action}</td>
                    <td className="px-4 py-4">{entry.details}</td>
                  </tr>
                ))
              ) : (
                <tr>
                  <td colSpan={4} className="px-4 py-6 text-center text-sm text-slate-500 dark:text-slate-400">
                    No audit activity available.
                  </td>
                </tr>
              )}
            </tbody>
          </table>
        </div>
      </Card>
    </div>
  );
}
