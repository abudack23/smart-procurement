import { useEffect, useState } from 'react';
import { Outlet } from 'react-router-dom';
import { Topbar } from './Topbar';
import { Sidebar } from './Sidebar';

export function Layout() {
  const [collapsed, setCollapsed] = useState(false);
  const [mobileOpen, setMobileOpen] = useState(false);
  const [theme, setTheme] = useState<'light' | 'dark'>(() => {
    if (typeof window === 'undefined') return 'light';
    return (localStorage.getItem('smart-procurement-theme') as 'light' | 'dark') ?? 'light';
  });

  useEffect(() => {
    const root = document.documentElement;
    root.classList.toggle('dark', theme === 'dark');
    document.body.classList.toggle('dark', theme === 'dark');
    localStorage.setItem('smart-procurement-theme', theme);
  }, [theme]);

  return (
    <div className="min-h-screen bg-slate-100 text-slate-900 dark:bg-slate-950 dark:text-slate-100">
      <Sidebar
        collapsed={collapsed}
        mobileOpen={mobileOpen}
        onToggle={() => setCollapsed((value) => !value)}
        onClose={() => setMobileOpen(false)}
      />
      <div
        className={`min-h-screen transition-all duration-300 ${collapsed ? 'pl-[78px]' : 'pl-[280px]'} ${mobileOpen ? 'pointer-events-none' : ''}`}
      >
        <Topbar
          onMenuToggle={() => setMobileOpen((value) => !value)}
          theme={theme}
          onThemeToggle={() => setTheme((value) => (value === 'light' ? 'dark' : 'light'))}
        />
        <main className="mx-auto max-w-[1480px] px-4 py-6 sm:px-6 lg:px-8" onClick={() => mobileOpen && setMobileOpen(false)}>
          <Outlet />
        </main>
      </div>
    </div>
  );
}
