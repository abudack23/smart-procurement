import { useEffect, useState } from 'react';
import { Card } from '../components/ui/Card';
import { Button } from '../components/ui/Button';
import { Input } from '../components/ui/Input';
import { procurementsApi } from '../lib/api';
import { useAuthContext } from '../context/AuthContext';

type Procurement = {
  id: number;
  title: string;
  description: string;
  budget: string;
  status: string;
  submission_deadline: string;
  created_at: string;
};

export function ProcurementsPage() {
  const { user } = useAuthContext();
  const [procurements, setProcurements] = useState<Procurement[]>([]);
  const [form, setForm] = useState({
    title: '',
    description: '',
    budget: '',
    delivery_days: '7',
    submission_deadline: '',
    evaluation_criteria: 'Price,Delivery time,Reliability'
  });
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [success, setSuccess] = useState<string | null>(null);

  const loadProcurements = async () => {
    setLoading(true);
    setError(null);
    try {
      const response = await procurementsApi.list();
      setProcurements(response.procurements ?? []);
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Unable to load procurements');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    if (user?.role === 'admin') {
      loadProcurements();
    }
  }, [user]);

  const handleCreate = async (event: React.FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    setSaving(true);
    setError(null);
    setSuccess(null);

    try {
      await procurementsApi.create({
        title: form.title,
        description: form.description,
        budget: form.budget,
        delivery_days: parseInt(form.delivery_days, 10),
        submission_deadline: form.submission_deadline,
        evaluation_criteria: form.evaluation_criteria
      });
      setSuccess('Procurement request created successfully.');
      setForm({
        title: '',
        description: '',
        budget: '',
        delivery_days: '7',
        submission_deadline: '',
        evaluation_criteria: 'Price,Delivery time,Reliability'
      });
      await loadProcurements();
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Unable to create procurement');
    } finally {
      setSaving(false);
    }
  };

  if (user?.role !== 'admin') {
    return (
      <div className="space-y-6">
        <Card className="p-5">
          <h1 className="text-2xl font-semibold text-slate-950 dark:text-slate-100">Admin access required</h1>
          <p className="mt-2 text-sm text-slate-500 dark:text-slate-400">
            Procurement management is only available to admin users.
          </p>
        </Card>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <p className="text-sm uppercase tracking-[0.24em] text-brand-600">Procurement requests</p>
          <h1 className="text-3xl font-semibold text-slate-950 dark:text-slate-100">Manage purchase orders</h1>
          <p className="max-w-2xl text-sm text-slate-500 dark:text-slate-400">
            Create, update, and track procurement requests across departments and suppliers.
          </p>
        </div>
        <Button onClick={loadProcurements} variant="secondary">Refresh list</Button>
      </div>

      {error && (
        <Card className="p-5 text-red-700 dark:text-red-300">
          <p>{error}</p>
        </Card>
      )}
      {success && (
        <Card className="p-5 text-slate-900 dark:text-slate-100">
          <p>{success}</p>
        </Card>
      )}

      <div className="grid gap-6 lg:grid-cols-[0.65fr_0.35fr]">
        <Card className="p-5">
          <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
              <h2 className="text-xl font-semibold text-slate-950 dark:text-slate-100">Active procurements</h2>
              <p className="text-sm text-slate-500 dark:text-slate-400">All current purchase orders and status tracking.</p>
            </div>
            <div className="text-sm text-slate-500 dark:text-slate-400">{procurements.length} records</div>
          </div>

          <div className="mt-5 overflow-x-auto">
            <table className="min-w-full text-left text-sm text-slate-600 dark:text-slate-300">
              <thead className="border-b border-slate-200 dark:border-slate-800">
                <tr>
                  <th className="px-4 py-4 font-semibold">ID</th>
                  <th className="px-4 py-4 font-semibold">Title</th>
                  <th className="px-4 py-4 font-semibold">Status</th>
                  <th className="px-4 py-4 font-semibold">Budget</th>
                  <th className="px-4 py-4 font-semibold">Deadline</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-200 dark:divide-slate-800">
                {loading ? (
                  <tr>
                    <td colSpan={5} className="px-4 py-6 text-center text-sm text-slate-500 dark:text-slate-400">
                      Loading procurements...
                    </td>
                  </tr>
                ) : procurements.length === 0 ? (
                  <tr>
                    <td colSpan={5} className="px-4 py-6 text-center text-sm text-slate-500 dark:text-slate-400">
                      No procurement requests found.
                    </td>
                  </tr>
                ) : (
                  procurements.map((item) => (
                    <tr key={item.id} className="transition hover:bg-slate-50 dark:hover:bg-slate-900/70">
                      <td className="px-4 py-4 font-medium text-slate-900 dark:text-slate-100">{item.id}</td>
                      <td className="px-4 py-4">{item.title}</td>
                      <td className="px-4 py-4">{item.status}</td>
                      <td className="px-4 py-4">{item.budget || 'N/A'}</td>
                      <td className="px-4 py-4">{item.submission_deadline}</td>
                    </tr>
                  ))
                )}
              </tbody>
            </table>
          </div>
        </Card>

        <Card className="p-5">
          <div>
            <h2 className="text-xl font-semibold text-slate-950 dark:text-slate-100">New request</h2>
            <p className="mt-2 text-sm text-slate-500 dark:text-slate-400">Submit a procurement request with clear details and priority level.</p>
          </div>
          <form className="mt-6 space-y-4" onSubmit={handleCreate}>
            <div>
              <label className="block text-sm font-medium text-slate-700 dark:text-slate-200">Request title</label>
              <Input
                value={form.title}
                onChange={(event) => setForm((prev) => ({ ...prev, title: event.target.value }))}
                placeholder="Office supplies for Q4"
                required
              />
            </div>
            <div>
              <label className="block text-sm font-medium text-slate-700 dark:text-slate-200">Description</label>
              <Input
                value={form.description}
                onChange={(event) => setForm((prev) => ({ ...prev, description: event.target.value }))}
                placeholder="Description of goods or services"
                required
              />
            </div>
            <div>
              <label className="block text-sm font-medium text-slate-700 dark:text-slate-200">Budget estimate</label>
              <Input
                value={form.budget}
                onChange={(event) => setForm((prev) => ({ ...prev, budget: event.target.value }))}
                placeholder="$12,000"
              />
            </div>
            <div>
              <label className="block text-sm font-medium text-slate-700 dark:text-slate-200">Submission deadline</label>
              <Input
                type="date"
                value={form.submission_deadline}
                onChange={(event) => setForm((prev) => ({ ...prev, submission_deadline: event.target.value }))}
                required
              />
            </div>
            <div>
              <label className="block text-sm font-medium text-slate-700 dark:text-slate-200">Delivery timeline (days)</label>
              <Input
                type="number"
                value={form.delivery_days}
                onChange={(event) => setForm((prev) => ({ ...prev, delivery_days: event.target.value }))}
                min={1}
                required
              />
            </div>
            <Button type="submit" disabled={saving} className="w-full">
              {saving ? 'Submitting…' : 'Submit request'}
            </Button>
          </form>
        </Card>
      </div>
    </div>
  );
}
