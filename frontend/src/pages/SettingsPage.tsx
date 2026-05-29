import { Card } from '../components/ui/Card';
import { Button } from '../components/ui/Button';

export function SettingsPage() {
  return (
    <div className="space-y-6">
      <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <p className="text-sm uppercase tracking-[0.24em] text-brand-600">Settings</p>
          <h1 className="text-3xl font-semibold text-slate-950 dark:text-slate-100">Platform configuration</h1>
          <p className="max-w-2xl text-sm text-slate-500 dark:text-slate-400">Manage system defaults, notification delivery, and user experience settings.</p>
        </div>
        <Button>Update preferences</Button>
      </div>

      <div className="grid gap-6 xl:grid-cols-2">
        <Card className="p-5">
          <h2 className="text-xl font-semibold text-slate-950 dark:text-slate-100">General settings</h2>
          <p className="mt-2 text-sm text-slate-500 dark:text-slate-400">Controls for procurement workflow and approval rules.</p>
          <div className="mt-6 space-y-4">
            {['Auto-assign approvals', 'Enable supplier scoring', 'Email notifications'].map((item) => (
              <div key={item} className="flex items-center justify-between rounded-3xl border border-slate-200 bg-slate-50 px-4 py-4 dark:border-slate-800 dark:bg-slate-900">
                <p className="text-sm text-slate-700 dark:text-slate-100">{item}</p>
                <span className="rounded-full bg-brand-500 px-3 py-1 text-xs font-semibold text-white">On</span>
              </div>
            ))}
          </div>
        </Card>

        <Card className="p-5">
          <h2 className="text-xl font-semibold text-slate-950 dark:text-slate-100">Security</h2>
          <p className="mt-2 text-sm text-slate-500 dark:text-slate-400">Controls for account protection and session limits.</p>
          <div className="mt-6 space-y-4">
            {['MFA required', 'Session timeout', 'Password policy'].map((item) => (
              <div key={item} className="rounded-3xl border border-slate-200 bg-slate-50 px-4 py-4 dark:border-slate-800 dark:bg-slate-900">
                <p className="text-sm text-slate-700 dark:text-slate-100">{item}</p>
              </div>
            ))}
          </div>
        </Card>
      </div>
    </div>
  );
}
