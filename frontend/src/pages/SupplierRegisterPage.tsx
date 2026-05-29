import { useState, FormEvent } from 'react';
import { useNavigate } from 'react-router-dom';
import { Card } from '../components/ui/Card';
import { Button } from '../components/ui/Button';
import { Input } from '../components/ui/Input';
import { useAuthContext } from '../context/AuthContext';

export function SupplierRegisterPage() {
  const [form, setForm] = useState({ name: '', email: '', password: '' });
  const [error, setError] = useState<string | null>(null);
  const [message, setMessage] = useState<string | null>(null);
  const { register, login } = useAuthContext();
  const navigate = useNavigate();

  const handleSubmit = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    setError(null);
    setMessage(null);

    try {
      await register(form.name, form.email, form.password);
      await login(form.email, form.password);
      navigate('/opportunities');
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Unable to register.');
    }
  };

  return (
    <div className="flex min-h-screen items-center justify-center bg-slate-100 px-4 py-10 dark:bg-slate-950">
      <Card className="w-full max-w-2xl p-8 shadow-panel">
        <div className="grid gap-6 lg:grid-cols-[0.55fr_0.45fr]">
          <div className="space-y-4">
            <div className="rounded-3xl bg-brand-500/10 p-5 text-brand-700 dark:bg-brand-500/15 dark:text-brand-200">
              <p className="text-xs uppercase tracking-[0.28em]">Smart Procurement</p>
              <h1 className="mt-3 text-3xl font-semibold">Supplier registration</h1>
              <p className="mt-2 text-sm text-slate-700 dark:text-slate-300">Create your supplier account to browse procurement opportunities and submit bids.</p>
            </div>
            <div className="rounded-3xl bg-slate-50 p-5 dark:bg-slate-900">
              <p className="text-sm text-slate-600 dark:text-slate-300">Admin accounts are not created here.</p>
              <p className="mt-2 text-sm text-slate-500 dark:text-slate-400">Supplier registration automatically assigns the supplier role.</p>
            </div>
          </div>

          <div className="rounded-3xl bg-slate-100 p-6 dark:bg-slate-900">
            <h2 className="text-xl font-semibold text-slate-950 dark:text-slate-100">Join as a supplier</h2>
            <p className="mt-2 text-sm text-slate-500 dark:text-slate-400">Complete your profile to start bidding on open opportunities.</p>

            <form className="mt-6 space-y-4" onSubmit={handleSubmit}>
              <div>
                <label className="block text-sm font-medium text-slate-700 dark:text-slate-200">Full name</label>
                <Input
                  value={form.name}
                  onChange={(e) => setForm((prev) => ({ ...prev, name: e.target.value }))}
                  placeholder="Jane Supplier"
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-slate-700 dark:text-slate-200">Email address</label>
                <Input
                  type="email"
                  value={form.email}
                  onChange={(e) => setForm((prev) => ({ ...prev, email: e.target.value }))}
                  placeholder="name@example.com"
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-slate-700 dark:text-slate-200">Password</label>
                <Input
                  type="password"
                  value={form.password}
                  onChange={(e) => setForm((prev) => ({ ...prev, password: e.target.value }))}
                  placeholder="••••••••"
                />
              </div>
              {message && <p className="text-sm text-emerald-600 dark:text-emerald-300">{message}</p>}
              {error && <p className="text-sm text-rose-500">{error}</p>}
              <Button type="submit" className="w-full">Register</Button>
              <div className="mt-4 text-center text-sm text-slate-500 dark:text-slate-400">
                <button type="button" onClick={() => navigate('/auth')} className="font-medium text-brand-600 hover:text-brand-700 dark:text-brand-300">
                  Back to login
                </button>
              </div>
            </form>
          </div>
        </div>
      </Card>
    </div>
  );
}
