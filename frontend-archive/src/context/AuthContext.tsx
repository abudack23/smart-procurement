import { createContext, useContext, useEffect, useMemo, useState } from 'react';
import { authApi } from '../lib/api';

type User = { id: number; name: string; email: string; role: 'admin' | 'supplier' } | null;

interface AuthContextValue {
  user: User;
  loading: boolean;
  error: string | null;
  login: (email: string, password: string) => Promise<User>;
  register: (name: string, email: string, password: string) => Promise<void>;
  logout: () => Promise<void>;
}

declare global {
  interface Window {
    __INITIAL_USER?: User | null;
  }
}

const AuthContext = createContext<AuthContextValue | undefined>(undefined);

export function AuthProvider({ children }: { children: React.ReactNode }) {
  const [user, setUser] = useState<User>(typeof window !== 'undefined' ? (window.__INITIAL_USER ?? null) : null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    if (typeof window !== 'undefined' && (window.__INITIAL_USER ?? undefined) !== undefined) {
      setUser(window.__INITIAL_USER ?? null);
    }

    authApi
      .me()
      .then((result) => {
        setUser(result.user ?? null);
      })
      .catch(() => {
        setUser(null);
      })
      .finally(() => setLoading(false));
  }, []);

  const login = async (email: string, password: string) => {
    setError(null);
    try {
      const result = await authApi.login(email, password);
      setUser(result.user);
      return result.user;
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Login failed');
      throw err;
    }
  };

  const register = async (name: string, email: string, password: string) => {
    setError(null);
    try {
      await authApi.register(name, email, password);
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Register failed');
      throw err;
    }
  };

  const logout = async () => {
    await authApi.logout();
    setUser(null);
  };

  const value = useMemo(
    () => ({ user, loading, error, login, register, logout }),
    [user, loading, error]
  );

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}

export function useAuthContext() {
  const context = useContext(AuthContext);
  if (!context) {
    throw new Error('useAuthContext must be used within AuthProvider');
  }
  return context;
}
