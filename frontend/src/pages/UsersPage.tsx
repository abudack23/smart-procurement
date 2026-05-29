import { useEffect, useState } from 'react';
import { Card } from '../components/ui/Card';
import { Button } from '../components/ui/Button';
import { usersApi } from '../lib/api';
import { useAuthContext } from '../context/AuthContext';

export function UsersPage() {
  const { user } = useAuthContext();
  const [users, setUsers] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  const loadUsers = async () => {
    setLoading(true);
    setError(null);
    try {
      const response = await usersApi.list();
      setUsers(response.users ?? []);
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Unable to load users');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    if (user?.role === 'admin') {
      loadUsers();
    }
  }, [user]);

  if (user?.role !== 'admin') {
    return (
      <div className="space-y-6">
        <Card className="p-5">
          <h1 className="text-2xl font-semibold text-slate-950 dark:text-slate-100">Admin access required</h1>
          <p className="mt-2 text-sm text-slate-500 dark:text-slate-400">
            User management is only available to admin users.
          </p>
        </Card>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <p className="text-sm uppercase tracking-[0.24em] text-brand-600">User management</p>
          <h1 className="text-3xl font-semibold text-slate-950 dark:text-slate-100">Team and roles</h1>
          <p className="max-w-2xl text-sm text-slate-500 dark:text-slate-400">Manage access and user accounts for procurement operations.</p>
        </div>
        <Button onClick={loadUsers} variant="secondary">Refresh list</Button>
      </div>

      {error && (
        <Card className="p-5 text-red-700 dark:text-red-300">
          <p>{error}</p>
        </Card>
      )}

      <Card className="p-5 overflow-x-auto">
        <table className="min-w-full text-left text-sm text-slate-600 dark:text-slate-300">
          <thead className="border-b border-slate-200 dark:border-slate-800">
            <tr>
              <th className="px-4 py-4 font-semibold">Name</th>
              <th className="px-4 py-4 font-semibold">Role</th>
              <th className="px-4 py-4 font-semibold">Email</th>
              <th className="px-4 py-4 font-semibold">Joined</th>
              <th className="px-4 py-4 font-semibold">Actions</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-slate-200 dark:divide-slate-800">
            {loading ? (
              <tr>
                <td colSpan={5} className="px-4 py-6 text-center text-sm text-slate-500 dark:text-slate-400">
                  Loading users...
                </td>
              </tr>
            ) : users.length === 0 ? (
              <tr>
                <td colSpan={5} className="px-4 py-6 text-center text-sm text-slate-500 dark:text-slate-400">
                  No team members found.
                </td>
              </tr>
            ) : (
              users.map((user) => (
                <tr key={user.id} className="transition hover:bg-slate-50 dark:hover:bg-slate-900/70">
                  <td className="px-4 py-4 font-medium text-slate-900 dark:text-slate-100">{user.name}</td>
                  <td className="px-4 py-4">{user.role}</td>
                  <td className="px-4 py-4">{user.email}</td>
                  <td className="px-4 py-4">{user.created_at ? new Date(user.created_at).toLocaleDateString() : 'N/A'}</td>
                  <td className="px-4 py-4">
                    <Button variant="ghost">Edit</Button>
                  </td>
                </tr>
              ))
            )}
          </tbody>
        </table>
      </Card>
    </div>
  );
}
