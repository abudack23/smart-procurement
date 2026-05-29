import { useState, FormEvent } from 'react';
import { useNavigate, useSearchParams } from 'react-router-dom';
import { Card } from '../components/ui/Card';
import { Button } from '../components/ui/Button';
import { Input } from '../components/ui/Input';
import { authApi } from '../lib/api';

export function ResetPasswordPage() {
  const [searchParams] = useSearchParams();
  const token = searchParams.get('token') ?? '';
  const [password, setPassword] = useState('');
  const [confirmPassword, setConfirmPassword] = useState('');
  const [error, setError] = useState<string | null>(null);
  const [message, setMessage] = useState<string | null>(null);
  const [submitting, setSubmitting] = useState(false);
  const navigate = useNavigate();

  const handleSubmit = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    setError(null);
    setMessage(null);

    if (!token) {
      setError('Invalid reset token.');
      return;
    }
    if (!password) {
      setError('Password is required.');
      return;
    }
    if (password !== confirmPassword) {
      setError('Passwords do not match.');
      return;
    }

    setSubmitting(true);
    try {
      await authApi.resetPassword(token, password);
      setMessage('Your password has been reset successfully. You can now login.');
      setTimeout(() => navigate('/auth'), 1500);
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Unable to reset password.');
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
            <h1 className="mt-3 text-3xl font-semibold">Reset Password</h1>
            <p className="mt-2 text-sm text-slate-700 dark:text-slate-300">Set a new password for your account using the link from your email.</p>
          </div>

          <form className="space-y-4" onSubmit={handleSubmit}>
            <div>
              <label className="block text-sm font-medium text-slate-700 dark:text-slate-200">New password</label>
              <Input
                type="password"
                value={password}
                onChange={(e) => setPassword(e.target.value)}
                placeholder="••••••••"
              />
            </div>
            <div>
              <label className="block text-sm font-medium text-slate-700 dark:text-slate-200">Confirm password</label>
              <Input
                type="password"
                value={confirmPassword}
                onChange={(e) => setConfirmPassword(e.target.value)}
                placeholder="••••••••"
              />
            </div>
            {message && <p className="text-sm text-emerald-600 dark:text-emerald-300">{message}</p>}
            {error && <p className="text-sm text-rose-500">{error}</p>}
            <Button type="submit" className="w-full" disabled={submitting || !password || !confirmPassword}>
              Reset password
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
