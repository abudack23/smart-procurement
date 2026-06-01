import { useState, FormEvent } from 'react';
import { useNavigate } from 'react-router-dom';
import { Card } from '../components/ui/Card';
import { Button } from '../components/ui/Button';
import { Input } from '../components/ui/Input';
import { useAuthContext } from '../context/AuthContext';

export function AuthPage() {
  const [form, setForm] = useState({ email: '', password: '' });
  const [error, setError] = useState<string | null>(null);
  const { login } = useAuthContext();
  const navigate = useNavigate();

  const handleSubmit = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    setError(null);
    try {
      const user = await login(form.email, form.password);
      navigate(user?.role === 'supplier' ? '/opportunities' : '/');
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Unable to authenticate.');
    }
  };

  return (
    <div className="flex min-h-screen items-center justify-center bg-slate-100 px-4 py-10 dark:bg-slate-950">
      <Card className="w-full max-w-2xl p-8 shadow-panel">
        <div className="grid gap-6 lg:grid-cols-[0.55fr_0.45fr]">
          <div className="space-y-4">
            <div className="rounded-3xl bg-brand-500/10 p-5 text-brand-700 dark:bg-brand-500/15 dark:text-brand-200">
              <p className="text-xs uppercase tracking-[0.28em]">Smart Procurement</p>
              <h1 className="mt-3 text-3xl font-semibold">Secure enterprise access</h1>
              <p className="mt-2 text-sm text-slate-700 dark:text-slate-300">Login with your supplier or admin account to continue.</p>
            </div>
            <div className="rounded-3xl bg-slate-50 p-5 dark:bg-slate-900">
              <p className="text-sm text-slate-600 dark:text-slate-300">New to the platform?</p>
              <Button type="button" onClick={() => navigate('/register')} className="mt-3 w-full">
                Register as Supplier
              </Button>
            </div>
          </div>

          <div className="rounded-3xl bg-slate-100 p-6 dark:bg-slate-900">
            <h2 className="text-xl font-semibold text-slate-950 dark:text-slate-100">Welcome back</h2>
            <p className="mt-2 text-sm text-slate-500 dark:text-slate-400">Sign in to access your dashboard.</p>

            <form className="mt-6 space-y-4" onSubmit={handleSubmit}>
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
              {error && <p className="text-sm text-rose-500">{error}</p>}
              <Button type="submit" className="w-full">Login</Button>
              <div className="mt-4 text-center text-sm text-slate-500 dark:text-slate-400">
                <button type="button" onClick={() => navigate('/forgot-password')} className="font-medium text-brand-600 hover:text-brand-700 dark:text-brand-300">
                  Forgot password?
                </button>
              </div>
            </form>
          </div>
        </div>
      </Card>
    </div>
  );
}
