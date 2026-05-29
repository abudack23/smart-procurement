import { useState, FormEvent } from 'react';
import { useNavigate } from 'react-router-dom';
import { Card } from '../components/ui/Card';
import { Button } from '../components/ui/Button';
import { Input } from '../components/ui/Input';
import { authApi } from '../lib/api';

export function ForgotPasswordPage() {
  const [email, setEmail] = useState('');
  const [message, setMessage] = useState<string | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [submitting, setSubmitting] = useState(false);
  const navigate = useNavigate();

  const handleSubmit = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    setError(null);
    setMessage(null);
    setSubmitting(true);

    try {
      await authApi.forgotPassword(email);
      setMessage('If that email is registered, a password reset link has been sent.');
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Unable to send reset instructions.');
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <div className="flex min-h-screen items-center justify-center bg-slate-100 px-4 py-10 dark:bg-slate-950">
      <Card className="w-full max-w-xl p-8 shadow-panel">
        <div className="space-y-6">
          <div className="rounded-3xl bg-brand-500/10 p-5 text-brand-700 dark:bg-brand-500/15 dark:text-brand-200">
            <p className="text-xs uppercase tracking-[0.28em]">Smart Procurement</p>
            <h1 className="mt-3 text-3xl font-semibold">Forgot Password</h1>
            <p className="mt-2 text-sm text-slate-700 dark:text-slate-300">Enter your email and we’ll send a reset link so you can set a new password.</p>
          </div>

          <form className="space-y-4" onSubmit={handleSubmit}>
            <div>
              <label className="block text-sm font-medium text-slate-700 dark:text-slate-200">Email address</label>
              <Input
                type="email"
                value={email}
                onChange={(e) => setEmail(e.target.value)}
                placeholder="name@example.com"
              />
            </div>

            {message && <p className="text-sm text-emerald-600 dark:text-emerald-300">{message}</p>}
            {error && <p className="text-sm text-rose-500">{error}</p>}

            <Button type="submit" className="w-full" disabled={submitting || !email.trim()}>
              Send reset link
            </Button>

            <div className="text-center text-sm text-slate-500 dark:text-slate-400">
              <button type="button" onClick={() => navigate('/auth')} className="font-medium text-brand-600 hover:text-brand-700 dark:text-brand-300">
                Back to login
              </button>
            </div>
          </form>
        </div>
      </Card>
    </div>
  );
}
