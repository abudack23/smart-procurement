import { useEffect, useState } from 'react';
import { Card } from '../components/ui/Card';
import { Button } from '../components/ui/Button';
import { StatusPill } from '../components/ui/StatusPill';
import { bidsApi } from '../lib/api';
import { useAuthContext } from '../context/AuthContext';

export function BiddingPage() {
  const { user } = useAuthContext();
  const [bids, setBids] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [refreshing, setRefreshing] = useState(false);
  const [awardLoading, setAwardLoading] = useState<number | null>(null);

  const loadBids = async () => {
    setLoading(true);
    setError(null);
    try {
      const response = await bidsApi.list();
      setBids(response.bids ?? []);
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Unable to load bids');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    loadBids();
  }, []);

  const handleAward = async (bidId: number, procurementId: number) => {
    setAwardLoading(bidId);
    setError(null);
    try {
      await bidsApi.award(bidId, procurementId);
      await loadBids();
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Unable to award bid');
    } finally {
      setAwardLoading(null);
    }
  };

  return (
    <div className="space-y-6">
      <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <p className="text-sm uppercase tracking-[0.24em] text-brand-600">Bidding management</p>
          <h1 className="text-3xl font-semibold text-slate-950 dark:text-slate-100">Review supplier proposals</h1>
          <p className="max-w-2xl text-sm text-slate-500 dark:text-slate-400">
            Track bid submissions, evaluate scores, and award contracts with predictive confidence.
          </p>
        </div>
        <Button onClick={loadBids} variant="secondary">Refresh data</Button>
      </div>

      {error && (
        <Card className="p-5 text-red-700 dark:text-red-300">
          <p>{error}</p>
        </Card>
      )}

      <Card className="p-5">
        <div className="flex flex-col gap-4 sm:flex-row sm:justify-between sm:items-center">
          <div>
            <h2 className="text-xl font-semibold text-slate-950 dark:text-slate-100">Current bid pipeline</h2>
            <p className="mt-1 text-sm text-slate-500 dark:text-slate-400">Overview of live proposals and award status.</p>
          </div>
          <Button variant="secondary" onClick={loadBids} disabled={refreshing}>
            {refreshing ? 'Refreshing…' : 'Refresh data'}
          </Button>
        </div>
        <div className="mt-6 overflow-x-auto">
          <table className="min-w-full text-left text-sm text-slate-600 dark:text-slate-300">
            <thead className="border-b border-slate-200 dark:border-slate-800">
              <tr>
                <th className="px-4 py-4 font-semibold">Bid ID</th>
                <th className="px-4 py-4 font-semibold">Procurement</th>
                <th className="px-4 py-4 font-semibold">Supplier</th>
                <th className="px-4 py-4 font-semibold">Amount</th>
                <th className="px-4 py-4 font-semibold">Delivery</th>
                <th className="px-4 py-4 font-semibold">Status</th>
                <th className="px-4 py-4 font-semibold">Action</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-200 dark:divide-slate-800">
              {loading ? (
                <tr>
                  <td colSpan={7} className="px-4 py-6 text-center text-sm text-slate-500 dark:text-slate-400">
                    Loading bids...
                  </td>
                </tr>
              ) : bids.length === 0 ? (
                <tr>
                  <td colSpan={7} className="px-4 py-6 text-center text-sm text-slate-500 dark:text-slate-400">
                    No bids found.
                  </td>
                </tr>
              ) : (
                bids.map((bid) => (
                  <tr key={bid.id} className="transition hover:bg-slate-50 dark:hover:bg-slate-900/70">
                    <td className="px-4 py-4 font-medium text-slate-900 dark:text-slate-100">{bid.id}</td>
                    <td className="px-4 py-4">{bid.title ?? bid.procurement_title ?? 'N/A'}</td>
                    <td className="px-4 py-4">{bid.supplier_name ?? bid.name ?? 'N/A'}</td>
                    <td className="px-4 py-4">{bid.price ? `$${bid.price}` : 'N/A'}</td>
                    <td className="px-4 py-4">{bid.delivery_days ?? 'N/A'}</td>
                    <td className="px-4 py-4">
                      <StatusPill value={bid.status ?? 'Pending'} />
                    </td>
                    <td className="px-4 py-4">
                      {user?.role === 'admin' && bid.status !== 'awarded' ? (
                        <Button
                          disabled={awardLoading !== null}
                          onClick={() => handleAward(bid.id, bid.procurement_id ?? bid.procurementId)}
                        >
                          {awardLoading === bid.id ? 'Awarding…' : 'Award'}
                        </Button>
                      ) : (
                        <span className="text-sm text-slate-500 dark:text-slate-400">{user?.role === 'supplier' ? 'View only' : 'N/A'}</span>
                      )}
                    </td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </div>
      </Card>
    </div>
  );
}
