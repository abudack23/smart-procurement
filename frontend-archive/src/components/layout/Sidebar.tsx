import { motion } from 'framer-motion';
import type { LucideIcon } from 'lucide-react';
import { Home, Briefcase, Layers, BarChart3, Users, Bell, Settings, UserCircle2, LogOut } from 'lucide-react';
import { NavLink, useNavigate } from 'react-router-dom';
import { useState } from 'react';
import { useAuthContext } from '../../context/AuthContext';

interface NavItem {
  label: string;
  path: string;
  icon: LucideIcon;
}

interface SidebarProps {
  collapsed: boolean;
  mobileOpen: boolean;
  onToggle: () => void;
  onClose: () => void;
}

export function Sidebar({ collapsed, mobileOpen, onToggle, onClose }: SidebarProps) {
  const [activeGroup, setActiveGroup] = useState<string | null>(null);
  const { logout, user } = useAuthContext();
  const navigate = useNavigate();

  const navItems: NavItem[] = [
    { label: 'Dashboard', path: '/', icon: Home },
    ...(user?.role === 'admin'
      ? [
          { label: 'Procurements', path: '/procurements', icon: Briefcase },
          { label: 'Suppliers', path: '/suppliers', icon: Users },
          { label: 'Reports', path: '/reports', icon: BarChart3 },
          { label: 'Users', path: '/users', icon: Users }
        ]
      : []),
    ...(user?.role === 'supplier' ? [{ label: 'Opportunities', path: '/opportunities', icon: Briefcase }] : []),
    { label: 'Bidding', path: '/bidding', icon: Layers },
    { label: 'Analytics', path: '/analytics', icon: BarChart3 },
    { label: 'Notifications', path: '/notifications', icon: Bell },
    { label: 'Profile', path: '/profile', icon: UserCircle2 },
    { label: 'Settings', path: '/settings', icon: Settings }
  ];

  const handleLogout = async () => {
    await logout();
    navigate('/auth');
    onClose();
  };

  return (
    <>
      <div
        className={`fixed inset-0 z-20 bg-slate-950/60 transition-opacity duration-300 md:hidden ${mobileOpen ? 'opacity-100 pointer-events-auto' : 'opacity-0 pointer-events-none'}`}
        aria-hidden={!mobileOpen}
        onClick={onClose}
      />
      <aside
        className={`fixed inset-y-0 left-0 z-30 flex w-full max-w-[280px] flex-col border-r border-slate-200/80 bg-slate-950/95 shadow-soft backdrop-blur-xl transition-transform duration-300 dark:border-slate-800 dark:bg-slate-950/95 ${collapsed ? 'md:w-[78px]' : 'md:w-[280px]'} ${mobileOpen ? 'translate-x-0' : '-translate-x-full'} md:translate-x-0`}
      >
        <div className="flex h-20 items-center justify-between gap-4 px-4 py-4">
          <div className="flex items-center gap-3">
            <div className="flex h-11 w-11 items-center justify-center rounded-2xl bg-brand-500 text-white shadow-brand">
              <span className="text-lg font-bold">SP</span>
            </div>
            <div className={`${collapsed ? 'hidden' : 'block'}`}>
              <p className="text-sm font-semibold text-slate-100">Smart Procurement</p>
              <p className="text-xs text-slate-400">Enterprise dashboard</p>
            </div>
          </div>
          <button
            type="button"
            onClick={onToggle}
            aria-label="Toggle sidebar"
            className="rounded-2xl bg-slate-900/70 p-2 text-slate-200 transition hover:bg-slate-800"
          >
            <span aria-hidden="true">{collapsed ? '›' : '‹'}</span>
          </button>
        </div>

        <nav className="flex-1 overflow-y-auto px-2 pb-6">
          <ul className="space-y-1">
            {navItems.map((item) => {
              const Icon = item.icon;
              return (
                <li key={item.path}>
                  <motion.div whileHover={{ x: 4 }}>
                    <NavLink
                      to={item.path}
                      className={({ isActive }) =>
                        `group flex items-center gap-3 rounded-3xl px-3 py-3 text-sm font-medium transition ${
                          isActive ? 'bg-brand-500/15 text-brand-200' : 'text-slate-300 hover:bg-slate-800/80 hover:text-white'
                        }`
                      }
                      onClick={onClose}
                    >
                      <Icon className="h-5 w-5" />
                      <span className={`${collapsed ? 'hidden' : 'block'}`}>{item.label}</span>
                    </NavLink>
                  </motion.div>
                </li>
              );
            })}
          </ul>
        </nav>

        <div className="border-t border-slate-800 px-4 py-4">
          <button
            type="button"
            onClick={handleLogout}
            className="flex w-full items-center gap-3 rounded-3xl bg-slate-900/80 px-3 py-3 text-sm font-medium text-slate-300 transition hover:bg-slate-800"
          >
            <LogOut className="h-5 w-5" />
            <span className={`${collapsed ? 'hidden' : 'block'}`}>Sign out</span>
          </button>
        </div>
      </aside>
    </>
  );
}
