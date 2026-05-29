import { useEffect, useState } from 'react';
import { Card } from '../components/ui/Card';
import { Button } from '../components/ui/Button';
import { suppliersApi } from '../lib/api';
import { useAuthContext } from '../context/AuthContext';

export function SuppliersPage() {
  const { user } = useAuthContext();
  const [suppliers, setSuppliers] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  const loadSuppliers = async () => {
    setLoading(true);
    setError(null);
    try {
      const response = await suppliersApi.list();
      setSuppliers(response.suppliers ?? []);
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Unable to load suppliers');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    if (user?.role === 'admin') {
      loadSuppliers();
    }
  }, [user]);

  if (user?.role !== 'admin') {
    return (
      <div className="space-y-6">
        <Card className="p-5">
          <h1 className="text-2xl font-semibold text-slate-950 dark:text-slate-100">Admin access required</h1>
          <p className="mt-2 text-sm text-slate-500 dark:text-slate-400">
            Supplier directory access is only available to admin users.
          </p>
        </Card>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <p className="text-sm uppercase tracking-[0.24em] text-brand-600">Supplier management</p>
          <h1 className="text-3xl font-semibold text-slate-950 dark:text-slate-100">Supplier directory</h1>
          <p className="max-w-2xl text-sm text-slate-500 dark:text-slate-400">
            Manage supplier profiles, onboarding status, and performance data from a single workspace.
          </p>
        </div>
        <Button onClick={loadSuppliers} variant="secondary">Refresh list</Button>
      </div>

      {error && (
        <Card className="p-5 text-red-700 dark:text-red-300">
          <p>{error}</p>
        </Card>
      )}

      <Card className="p-5">
        <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
          <div>
            <h2 className="text-xl font-semibold text-slate-950 dark:text-slate-100">Supplier performance</h2>
            <p className="mt-1 text-sm text-slate-500 dark:text-slate-400">Top partners and active supplier engagements.</p>
          </div>
          <div className="grid gap-3 sm:grid-cols-3">
            <Button variant="secondary">Filters</Button>
            <Button variant="secondary">Export list</Button>
          </div>
        </div>
        <div className="mt-6 overflow-x-auto">
          <table className="min-w-full text-left text-sm text-slate-600 dark:text-slate-300">
            <thead className="border-b border-slate-200 dark:border-slate-800">
              <tr>
                <th className="px-4 py-4 font-semibold">Supplier</th>
                <th className="px-4 py-4 font-semibold">Company</th>
                <th className="px-4 py-4 font-semibold">Services</th>
                <th className="px-4 py-4 font-semibold">Joined</th>
                <th className="px-4 py-4 font-semibold">Actions</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-200 dark:divide-slate-800">
              {loading ? (
                <tr>
                  <td colSpan={5} className="px-4 py-6 text-center text-sm text-slate-500 dark:text-slate-400">
                    Loading suppliers...
                  </td>
                </tr>
              ) : suppliers.length === 0 ? (
                <tr>
                  <td colSpan={5} className="px-4 py-6 text-center text-sm text-slate-500 dark:text-slate-400">
                    No suppliers found.
                  </td>
                </tr>
              ) : (
                suppliers.map((supplier) => (
                  <tr key={supplier.id} className="transition hover:bg-slate-50 dark:hover:bg-slate-900/70">
                    <td className="px-4 py-4 font-medium text-slate-900 dark:text-slate-100">{supplier.name}</td>
                    <td className="px-4 py-4">{supplier.company_name ?? supplier.email}</td>
                    <td className="px-4 py-4">{supplier.services_offered ?? 'N/A'}</td>
                    <td className="px-4 py-4">{supplier.created_at ? new Date(supplier.created_at).toLocaleDateString() : 'N/A'}</td>
                    <td className="px-4 py-4">
                      <Button variant="ghost">View</Button>
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
