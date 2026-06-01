import { Route, Routes, Navigate } from 'react-router-dom';
import { Layout } from './components/layout/Layout';
import { DashboardPage } from './pages/DashboardPage';
import { ProcurementsPage } from './pages/ProcurementsPage';
import { OpportunitiesPage } from './pages/OpportunitiesPage';
import { BiddingPage } from './pages/BiddingPage';
import { SuppliersPage } from './pages/SuppliersPage';
import { AnalyticsPage } from './pages/AnalyticsPage';
import { ReportsPage } from './pages/ReportsPage';
import { NotificationsPage } from './pages/NotificationsPage';
import { UsersPage } from './pages/UsersPage';
import { ProfilePage } from './pages/ProfilePage';
import { SettingsPage } from './pages/SettingsPage';
import { AuthPage } from './pages/AuthPage';
import { SupplierRegisterPage } from './pages/SupplierRegisterPage';
import { ForgotPasswordPage } from './pages/ForgotPasswordPage';
import { ResetPasswordPage } from './pages/ResetPasswordPage';
import { AuthProvider, useAuthContext } from './context/AuthContext';
import { Spinner } from './components/ui/Spinner';

export default function App() {
  function RequireAuth({ children }: { children: JSX.Element }) {
    const { user, loading } = useAuthContext();
    if (loading) {
      return (
        <div className="min-h-screen bg-slate-100 text-slate-900 dark:bg-slate-950 dark:text-slate-100">
          <Spinner />
        </div>
      );
    }
    if (!user) {
      return <Navigate to="/auth" replace />;
    }
    return children;
  }

  function AdminRoute({ children }: { children: JSX.Element }) {
    const { user, loading } = useAuthContext();
    if (loading) {
      return (
        <div className="min-h-screen bg-slate-100 text-slate-900 dark:bg-slate-950 dark:text-slate-100">
          <Spinner />
        </div>
      );
    }
    if (!user || user.role !== 'admin') {
      return <Navigate to="/" replace />;
    }
    return children;
  }

  function SupplierRoute({ children }: { children: JSX.Element }) {
    const { user, loading } = useAuthContext();
    if (loading) {
      return (
        <div className="min-h-screen bg-slate-100 text-slate-900 dark:bg-slate-950 dark:text-slate-100">
          <Spinner />
        </div>
      );
    }
    if (!user || user.role !== 'supplier') {
      return <Navigate to="/" replace />;
    }
    return children;
  }

  return (
    <AuthProvider>
    <Routes>
      <Route path="/auth" element={<AuthPage />} />
      <Route path="/register" element={<SupplierRegisterPage />} />
      <Route path="/forgot-password" element={<ForgotPasswordPage />} />
      <Route path="/reset-password" element={<ResetPasswordPage />} />
      <Route element={<RequireAuth><Layout /></RequireAuth>}>
        <Route path="/" element={<DashboardPage />} />
        <Route path="/procurements" element={<AdminRoute><ProcurementsPage /></AdminRoute>} />
        <Route path="/opportunities" element={<SupplierRoute><OpportunitiesPage /></SupplierRoute>} />
        <Route path="/bidding" element={<BiddingPage />} />
        <Route path="/suppliers" element={<AdminRoute><SuppliersPage /></AdminRoute>} />
        <Route path="/analytics/*" element={<AnalyticsPage />} />
        <Route path="/reports" element={<AdminRoute><ReportsPage /></AdminRoute>} />
        <Route path="/notifications" element={<NotificationsPage />} />
        <Route path="/users" element={<AdminRoute><UsersPage /></AdminRoute>} />
        <Route path="/profile" element={<ProfilePage />} />
        <Route path="/settings" element={<SettingsPage />} />
      </Route>
      <Route path="*" element={<Navigate to="/" replace />} />
    </Routes>
    </AuthProvider>
  );
}
