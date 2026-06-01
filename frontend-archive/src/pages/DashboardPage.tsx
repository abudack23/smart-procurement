import { useEffect, useState } from 'react';
import { ArrowUpRight, Sparkles } from 'lucide-react';
import { Card } from '../components/ui/Card';
import { Button } from '../components/ui/Button';
import { StatusPill } from '../components/ui/StatusPill';
import { dashboardApi } from '../lib/api';
import { useAuthContext } from '../context/AuthContext';

export function DashboardPage() {
  const { user } = useAuthContext();
  const [dashboard, setDashboard] = useState<any>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    dashboardApi
      .getOverview()
      .then((response) => setDashboard(response))
      .catch((err) => setError(err instanceof Error ? err.message : 'Unable to load dashboard'))
      .finally(() => setLoading(false));
  }, []);

  const overview = dashboard?.overview ?? {};
  const recentItems = dashboard?.recent_procurements ?? dashboard?.recent_bids ?? [];
  const isAdmin = user?.role === 'admin';

  return (
    <div className="space-y-6">
      {loading && !error && (
        <Card className="p-5 text-center">
          <p className="text-sm text-slate-500 dark:text-slate-400">Loading dashboard...</p>
        </Card>
      )}

      {error && (
        <Card className="p-5 text-center">
          <p className="text-sm text-rose-500">{error}</p>
        </Card>
      )}

      {!loading && !error && (
        <>
          <div className="grid gap-6 xl:grid-cols-[1fr_320px]">
            <section className="space-y-5">
              <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                  <p className="text-sm uppercase tracking-[0.24em] text-brand-600">Welcome back</p>
                  <h1 className="text-3xl font-semibold text-slate-950 dark:text-slate-100">
                    {isAdmin ? 'Admin dashboard' : 'Supplier dashboard'}
                  </h1>
                  <p className="max-w-2xl text-sm text-slate-500 dark:text-slate-400">
                    Monitor procurement workflows, bidding activity, and predictive insights in one unified workspace.
                  </p>
                </div>
                <div className="flex flex-wrap items-center gap-3">
                  {isAdmin ? (
                    <>
                      <Button variant="secondary">Create procurement</Button>
                      <Button>New supplier invite</Button>
                    </>
                  ) : (
                    <Button variant="secondary">View opportunities</Button>
                  )}
                </div>
              </div>

              <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <Card className="p-5">
                  <div>
                    <p className="text-sm font-medium text-slate-500 dark:text-slate-400">
                      {isAdmin ? 'Open procurements' : 'Total bids'}
                    </p>
                    <p className="mt-3 text-3xl font-semibold text-slate-950 dark:text-slate-100">
                      {isAdmin ? overview.open_procurements ?? 0 : overview.total_bids ?? 0}
                    </p>
                  </div>
                </Card>
                <Card className="p-5">
                  <div>
                    <p className="text-sm font-medium text-slate-500 dark:text-slate-400">
                      {isAdmin ? 'Active bids' : 'Wins'}
                    </p>
                    <p className="mt-3 text-3xl font-semibold text-slate-950 dark:text-slate-100">
                      {isAdmin ? overview.active_bids ?? 0 : overview.wins ?? 0}
                    </p>
                  </div>
                </Card>
                <Card className="p-5">
                  <div>
                    <p className="text-sm font-medium text-slate-500 dark:text-slate-400">
                      {isAdmin ? 'Completed bids' : 'Win rate'}
                    </p>
                    <p className="mt-3 text-3xl font-semibold text-slate-950 dark:text-slate-100">
                      {isAdmin ? overview.completed_bids ?? 0 : `${overview.win_rate ?? 0}%`}
                    </p>
                  </div>
                </Card>
                <Card className="p-5">
                  <div>
                    <p className="text-sm font-medium text-slate-500 dark:text-slate-400">
                      {isAdmin ? 'Suppliers' : 'Reliability'}
                    </p>
                    <p className="mt-3 text-3xl font-semibold text-slate-950 dark:text-slate-100">
                      {isAdmin ? overview.suppliers ?? 0 : `${overview.reliability ?? 0}%`}
                    </p>
                  </div>
                </Card>
              </div>

              <div className="grid gap-4 xl:grid-cols-[1.4fr_0.9fr]">
                <Card className="p-5">
                  <div className="flex flex-col gap-6">
                    <div className="flex items-center justify-between gap-3">
                      <div>
                        <p className="text-sm uppercase tracking-[0.24em] text-slate-500 dark:text-slate-400">
                          Predictive insight
                        </p>
                        <h2 className="mt-2 text-2xl font-semibold text-slate-950 dark:text-slate-100">
                          Bid pricing alert
                        </h2>
                      </div>
                      <div className="inline-flex items-center gap-2 rounded-3xl bg-brand-50 px-3 py-2 text-sm font-semibold text-brand-700 dark:bg-brand-500/15 dark:text-brand-200">
                        <Sparkles className="h-4 w-4" /> AI
                      </div>
                    </div>
                    <p className="text-sm leading-7 text-slate-600 dark:text-slate-300">
                      The model identifies five procurements with above-average supplier risk and recommends adjusting evaluation weight for delivery, reliability, and cost.
                    </p>
                    <div className="grid gap-4 sm:grid-cols-3">
                      {[
                        { label: 'Confidence', value: '94%' },
                        { label: 'Risk score', value: 'Low' },
                        { label: 'Recommendation', value: 'Increase Supplier Quality' }
                      ].map((metric) => (
                        <div key={metric.label} className="rounded-3xl border border-slate-200/70 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-900">
                          <p className="text-xs uppercase tracking-[0.24em] text-slate-500 dark:text-slate-400">
                            {metric.label}
                          </p>
                          <p className="mt-3 text-xl font-semibold text-slate-950 dark:text-slate-100">
                            {metric.value}
                          </p>
                        </div>
                      ))}
                    </div>
                  </div>
                </Card>

                <Card className="p-5">
                  <div className="flex items-center justify-between gap-3">
                    <div>
                      <p className="text-sm uppercase tracking-[0.24em] text-slate-500 dark:text-slate-400">
                        Quick actions
                      </p>
                      <h2 className="mt-2 text-2xl font-semibold text-slate-950 dark:text-slate-100">
                        Workflow shortcuts
                      </h2>
                    </div>
                    <ArrowUpRight className="h-5 w-5 text-brand-500" />
                  </div>
                  <div className="mt-6 space-y-3">
                    {[
                      'Review pending bids',
                      'Publish new procurement request',
                      'Run supplier performance audit'
                    ].map((action) => (
                      <button
                        key={action}
                        type="button"
                        className="flex w-full items-center justify-between rounded-3xl border border-slate-200/80 bg-slate-50 px-4 py-4 text-left text-sm font-medium text-slate-700 transition hover:border-brand-300 hover:bg-brand-50 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-100 dark:hover:bg-slate-800"
                      >
                        <span>{action}</span>
                        <ArrowUpRight className="h-4.5 w-4.5 text-slate-400" />
                      </button>
                    ))}
                  </div>
                </Card>
              </div>
            </section>

            <aside className="space-y-6">
              <Card className="p-5">
                <div className="flex items-center justify-between gap-3">
                  <div>
                    <p className="text-sm uppercase tracking-[0.24em] text-slate-500 dark:text-slate-400">
                      Live updates
                    </p>
                    <h2 className="mt-2 text-2xl font-semibold text-slate-950 dark:text-slate-100">
                      Notifications
                    </h2>
                  </div>
                  <Button variant="ghost">View all</Button>
                </div>
                <div className="mt-5 space-y-3">
                  <div className="rounded-3xl border border-slate-200/80 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-900">
                    <div className="flex items-center justify-between gap-3">
                      <p className="text-sm text-slate-700 dark:text-slate-100">
                        You have new supplier proposals waiting review.
                      </p>
                      <span className="text-xs text-slate-400 dark:text-slate-500">2m ago</span>
                    </div>
                    <p className="mt-2 text-xs uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">
                      Procurement
                    </p>
                  </div>
                  <div className="rounded-3xl border border-slate-200/80 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-900">
                    <div className="flex items-center justify-between gap-3">
                      <p className="text-sm text-slate-700 dark:text-slate-100">
                        Your latest bid has been awarded.
                      </p>
                      <span className="text-xs text-slate-400 dark:text-slate-500">10m ago</span>
                    </div>
                    <p className="mt-2 text-xs uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">
                      Bidding
                    </p>
                  </div>
                </div>
              </Card>
            </aside>
          </div>

          <Card className="overflow-hidden p-5">
            <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
              <div>
                <p className="text-sm uppercase tracking-[0.24em] text-slate-500 dark:text-slate-400">
                  Activity stream
                </p>
                <h2 className="mt-2 text-2xl font-semibold text-slate-950 dark:text-slate-100">
                  Recent procurement requests
                </h2>
              </div>
              <Button variant="secondary">Export report</Button>
            </div>
            <div className="mt-6 overflow-x-auto">
              <table className="min-w-full text-left text-sm text-slate-600 dark:text-slate-300">
                <thead className="border-b border-slate-200 dark:border-slate-800">
                  <tr>
                    <th className="px-4 py-4 font-semibold">Ref</th>
                    <th className="px-4 py-4 font-semibold">Request</th>
                    <th className="px-4 py-4 font-semibold">Owner</th>
                    <th className="px-4 py-4 font-semibold">Status</th>
                    <th className="px-4 py-4 font-semibold">Due Date</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-slate-200 dark:divide-slate-800">
                  {recentItems.map((item: any) => (
                    <tr key={item.id ?? item.ref ?? item.title ?? Math.random()} className="transition hover:bg-slate-50 dark:hover:bg-slate-900/70">
                      <td className="px-4 py-4 font-medium text-slate-900 dark:text-slate-100">{item.id ?? item.ref ?? 'N/A'}</td>
                      <td className="px-4 py-4">{item.title ?? item.description ?? 'N/A'}</td>
                      <td className="px-4 py-4">{item.name ?? item.owner ?? 'N/A'}</td>
                      <td className="px-4 py-4">
                        <StatusPill value={item.status ?? 'Pending'} />
                      </td>
                      <td className="px-4 py-4">{item.submission_deadline ?? item.created_at ?? 'N/A'}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </Card>
        </>
      )}
    </div>
  );
}
