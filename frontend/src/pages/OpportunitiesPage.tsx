import { useEffect, useState } from 'react';
import { Card } from '../components/ui/Card';
import { Button } from '../components/ui/Button';
import { Input } from '../components/ui/Input';
import { opportunitiesApi, bidsApi } from '../lib/api';
import { useAuthContext } from '../context/AuthContext';

type Opportunity = {
  id: number;
  title: string;
  description: string;
  budget: string;
  submission_deadline: string;
  bids: number;
};

export function OpportunitiesPage() {
  const { user } = useAuthContext();
  const [opportunities, setOpportunities] = useState<Opportunity[]>([]);
  const [search, setSearch] = useState('');
  const [minBudget, setMinBudget] = useState('');
  const [maxBudget, setMaxBudget] = useState('');
  const [selected, setSelected] = useState<Opportunity | null>(null);
  const [price, setPrice] = useState('');
  const [deliveryDays, setDeliveryDays] = useState('7');
  const [remarks, setRemarks] = useState('');
  const [loading, setLoading] = useState(true);
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [success, setSuccess] = useState<string | null>(null);

  const loadOpportunities = async () => {
    setLoading(true);
    setError(null);
    try {
      const query = {
        q: search,
        min_budget: minBudget,
        max_budget: maxBudget
      };
      const response = await opportunitiesApi.list(query);
      setOpportunities(response.opportunities ?? []);
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Unable to load opportunities');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    loadOpportunities();
  }, []);

  const handleSearch = async (event: React.FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    await loadOpportunities();
  };

  const handleBidSubmit = async (event: React.FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    if (!selected) {
      return;
    }

    setSubmitting(true);
    setError(null);
    setSuccess(null);

    try {
      const formData = new FormData();
      formData.append('action', 'submit');
      formData.append('procurement_id', String(selected.id));
      formData.append('price', price);
      formData.append('delivery_days', deliveryDays);
      formData.append('remarks', remarks);

      await bidsApi.submit(formData);
      setSuccess('Bid submitted successfully.');
      setPrice('');
      setDeliveryDays('7');
      setRemarks('');
      setSelected(null);
      await loadOpportunities();
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Unable to submit bid');
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <div className="space-y-6">
      <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <p className="text-sm uppercase tracking-[0.24em] text-brand-600">Opportunity marketplace</p>
          <h1 className="text-3xl font-semibold text-slate-950 dark:text-slate-100">Available procurement requests</h1>
          <p className="max-w-2xl text-sm text-slate-500 dark:text-slate-400">
            Browse open procurement opportunities and submit supplier bids directly from the platform.
          </p>
        </div>
        <Button onClick={loadOpportunities} variant="secondary">Refresh opportunities</Button>
      </div>

      <Card className="p-5">
        <form className="grid gap-4 lg:grid-cols-[1fr_180px]" onSubmit={handleSearch}>
          <Input
            value={search}
            onChange={(event) => setSearch(event.target.value)}
            placeholder="Search by title or description"
          />
          <div className="grid gap-4 sm:grid-cols-2">
            <Input
              type="number"
              value={minBudget}
              onChange={(event) => setMinBudget(event.target.value)}
              placeholder="Min budget"
            />
            <Input
              type="number"
              value={maxBudget}
              onChange={(event) => setMaxBudget(event.target.value)}
              placeholder="Max budget"
            />
          </div>
          <Button type="submit" className="sm:col-span-2">Search opportunities</Button>
        </form>
      </Card>

      {error && (
        <Card className="p-5 text-red-700 dark:text-red-300">
          <p>{error}</p>
        </Card>
      )}

      {success && (
        <Card className="p-5 text-slate-950 dark:text-slate-100">
          <p>{success}</p>
        </Card>
      )}

      {user?.role !== 'supplier' ? (
        <Card className="p-5">
          <p className="text-sm text-slate-600 dark:text-slate-300">
            Procurement opportunities are available for supplier accounts. Please switch to a supplier role to submit bids.
          </p>
        </Card>
      ) : null}

      <Card className="p-5 overflow-x-auto">
        <div className="flex items-center justify-between gap-3">
          <h2 className="text-xl font-semibold text-slate-950 dark:text-slate-100">Open opportunities</h2>
          <span className="rounded-3xl bg-slate-100 px-3 py-1 text-sm font-semibold text-slate-600 dark:bg-slate-900 dark:text-slate-300">
            {opportunities.length} available
          </span>
        </div>

        <table className="min-w-full text-left text-sm text-slate-600 dark:text-slate-300 mt-5">
          <thead className="border-b border-slate-200 dark:border-slate-800">
            <tr>
              <th className="px-4 py-4 font-semibold">Ref</th>
              <th className="px-4 py-4 font-semibold">Title</th>
              <th className="px-4 py-4 font-semibold">Budget</th>
              <th className="px-4 py-4 font-semibold">Deadline</th>
              <th className="px-4 py-4 font-semibold">Bids</th>
              <th className="px-4 py-4 font-semibold">Action</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-slate-200 dark:divide-slate-800">
            {loading ? (
              <tr>
                <td colSpan={6} className="px-4 py-6 text-center text-slate-500 dark:text-slate-400">
                  Loading opportunities...
                </td>
              </tr>
            ) : opportunities.length === 0 ? (
              <tr>
                <td colSpan={6} className="px-4 py-6 text-center text-slate-500 dark:text-slate-400">
                  No opportunities found.
                </td>
              </tr>
            ) : (
              opportunities.map((opportunity) => (
                <tr key={opportunity.id} className="transition hover:bg-slate-50 dark:hover:bg-slate-900/70">
                  <td className="px-4 py-4 font-medium text-slate-900 dark:text-slate-100">{opportunity.id}</td>
                  <td className="px-4 py-4">{opportunity.title}</td>
                  <td className="px-4 py-4">{opportunity.budget || 'N/A'}</td>
                  <td className="px-4 py-4">{opportunity.submission_deadline}</td>
                  <td className="px-4 py-4">{opportunity.bids}</td>
                  <td className="px-4 py-4">
                    <Button
                      variant="secondary"
                      onClick={() => setSelected(opportunity)}
                    >
                      Submit bid
                    </Button>
                  </td>
                </tr>
              ))
            )}
          </tbody>
        </table>
      </Card>

      {selected && (
        <Card className="p-5">
          <div className="flex flex-col gap-4">
            <div className="flex items-center justify-between gap-3">
              <div>
                <p className="text-sm uppercase tracking-[0.24em] text-slate-500 dark:text-slate-400">Bid submission</p>
                <h2 className="text-2xl font-semibold text-slate-950 dark:text-slate-100">{selected.title}</h2>
              </div>
              <Button variant="ghost" onClick={() => setSelected(null)}>
                Cancel
              </Button>
            </div>

            <form className="grid gap-4" onSubmit={handleBidSubmit}>
              <div>
                <label className="block text-sm font-medium text-slate-700 dark:text-slate-200">Proposed amount</label>
                <Input
                  type="number"
                  value={price}
                  onChange={(event) => setPrice(event.target.value)}
                  placeholder="Enter your bid amount"
                  required
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-slate-700 dark:text-slate-200">Delivery timeline (days)</label>
                <Input
                  type="number"
                  value={deliveryDays}
                  onChange={(event) => setDeliveryDays(event.target.value)}
                  min={1}
                  required
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-slate-700 dark:text-slate-200">Remarks</label>
                <Input
                  value={remarks}
                  onChange={(event) => setRemarks(event.target.value)}
                  placeholder="Optional proposal notes"
                />
              </div>
              <Button type="submit" disabled={submitting}>
                {submitting ? 'Submitting…' : 'Submit bid'}
              </Button>
            </form>
          </div>
        </Card>
      )}
    </div>
  );
}
