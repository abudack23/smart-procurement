import { useEffect, useState } from 'react';
import { Card } from '../components/ui/Card';
import { Button } from '../components/ui/Button';
import { Input } from '../components/ui/Input';
import { usersApi } from '../lib/api';

export function ProfilePage() {
  const [profile, setProfile] = useState<any>(null);
  const [form, setForm] = useState({ company_name: '', services_offered: '', past_experience: '' });
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [success, setSuccess] = useState<string | null>(null);

  const loadProfile = async () => {
    setLoading(true);
    setError(null);
    try {
      const response = await usersApi.me();
      setProfile(response.user);
      setForm({
        company_name: response.user.company_name ?? '',
        services_offered: response.user.services_offered ?? '',
        past_experience: response.user.past_experience ?? ''
      });
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Unable to load profile');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    loadProfile();
  }, []);

  const handleSubmit = async (event: React.FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    setSaving(true);
    setError(null);
    setSuccess(null);

    try {
      await usersApi.update({
        company_name: form.company_name,
        services_offered: form.services_offered,
        past_experience: form.past_experience
      });
      setSuccess('Profile updated successfully.');
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Unable to save profile');
    } finally {
      setSaving(false);
    }
  };

  return (
    <div className="space-y-6">
      <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <p className="text-sm uppercase tracking-[0.24em] text-brand-600">Profile</p>
          <h1 className="text-3xl font-semibold text-slate-950 dark:text-slate-100">Company profile</h1>
          <p className="max-w-2xl text-sm text-slate-500 dark:text-slate-400">Update your profile and contact information for procurement notifications.</p>
        </div>
        <Button form="profileForm" type="submit" disabled={saving}>Save changes</Button>
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

      <Card className="p-5">
        {loading ? (
          <div className="text-sm text-slate-500 dark:text-slate-400">Loading profile...</div>
        ) : (
          <form id="profileForm" className="space-y-6" onSubmit={handleSubmit}>
            <div className="grid gap-4 lg:grid-cols-2">
              <div>
                <label className="block text-sm font-medium text-slate-700 dark:text-slate-200">Name</label>
                <Input value={profile?.name ?? ''} readOnly />
              </div>
              <div>
                <label className="block text-sm font-medium text-slate-700 dark:text-slate-200">Email</label>
                <Input value={profile?.email ?? ''} readOnly />
              </div>
              <div>
                <label className="block text-sm font-medium text-slate-700 dark:text-slate-200">Role</label>
                <Input value={profile?.role ?? ''} readOnly />
              </div>
              <div>
                <label className="block text-sm font-medium text-slate-700 dark:text-slate-200">Company name</label>
                <Input
                  value={form.company_name}
                  onChange={(event) => setForm((prev) => ({ ...prev, company_name: event.target.value }))}
                />
              </div>
              <div className="lg:col-span-2">
                <label className="block text-sm font-medium text-slate-700 dark:text-slate-200">Services offered</label>
                <Input
                  value={form.services_offered}
                  onChange={(event) => setForm((prev) => ({ ...prev, services_offered: event.target.value }))}
                />
              </div>
              <div className="lg:col-span-2">
                <label className="block text-sm font-medium text-slate-700 dark:text-slate-200">Past experience</label>
                <Input
                  value={form.past_experience}
                  onChange={(event) => setForm((prev) => ({ ...prev, past_experience: event.target.value }))}
                  placeholder="Brief background of procurement or supplier experience"
                />
              </div>
            </div>
          </form>
        )}
      </Card>
    </div>
  );
}
