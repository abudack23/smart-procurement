import { Moon, Sun, Search, Bell, Settings, User } from 'lucide-react';
import { useMemo } from 'react';
import { useAuthContext } from '../../context/AuthContext';

interface TopbarProps {
  onMenuToggle: () => void;
  onThemeToggle: () => void;
  theme: 'light' | 'dark';
}

export function Topbar({ onMenuToggle, onThemeToggle, theme }: TopbarProps) {
  const { user } = useAuthContext();
  const themeLabel = useMemo(() => (theme === 'dark' ? 'Switch to light mode' : 'Switch to dark mode'), [theme]);

  return (
    <header className="sticky top-0 z-20 border-b border-slate-200/70 bg-slate-50/85 backdrop-blur-xl dark:border-slate-800/80 dark:bg-slate-950/90">
      <div className="mx-auto flex max-w-[1480px] items-center justify-between gap-3 px-4 py-4 sm:px-6 lg:px-8">
        <div className="flex items-center gap-3">
          <button
            type="button"
            onClick={onMenuToggle}
            className="flex h-11 w-11 items-center justify-center rounded-2xl bg-white text-slate-700 shadow-soft transition hover:bg-slate-50 dark:bg-slate-900 dark:text-slate-100 md:hidden"
            aria-label="Open sidebar menu"
          >
            ☰
          </button>
          <div className="hidden sm:flex items-center gap-3 rounded-2xl border border-slate-200 bg-white px-4 py-2 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <Search className="h-4.5 w-4.5 text-slate-400" />
            <input
              type="text"
              placeholder="Search for procurement, bids, reports..."
              className="w-full bg-transparent text-sm text-slate-700 placeholder:text-slate-400 focus:outline-none dark:text-slate-100 dark:placeholder:text-slate-500"
            />
          </div>
        </div>

        <div className="flex items-center gap-3">
          <button
            type="button"
            title={themeLabel}
            onClick={onThemeToggle}
            className="inline-flex h-11 w-11 items-center justify-center rounded-2xl border border-slate-200 bg-white text-slate-700 shadow-soft transition hover:border-slate-300 hover:bg-slate-50 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-100"
          >
            {theme === 'dark' ? <Sun className="h-5 w-5" /> : <Moon className="h-5 w-5" />}
          </button>

          <button
            type="button"
            className="inline-flex h-11 w-11 items-center justify-center rounded-2xl border border-slate-200 bg-white text-slate-700 shadow-soft transition hover:border-slate-300 hover:bg-slate-50 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-100"
          >
            <Bell className="h-5 w-5" />
          </button>

          <button
            type="button"
            className="hidden h-11 rounded-2xl border border-slate-200 bg-white px-4 text-sm font-medium text-slate-700 transition hover:border-slate-300 hover:bg-slate-50 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-100 md:inline-flex"
          >
            <User className="mr-2 h-4.5 w-4.5" />
            {user?.name ?? 'Account'}
          </button>

          <button
            type="button"
            className="inline-flex h-11 w-11 items-center justify-center rounded-2xl border border-slate-200 bg-white text-slate-700 shadow-soft transition hover:border-slate-300 hover:bg-slate-50 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-100"
          >
            <Settings className="h-5 w-5" />
          </button>
        </div>
      </div>
    </header>
  );
}
