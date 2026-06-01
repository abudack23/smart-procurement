import { useEffect } from 'react';

interface ToastProps {
  message: string;
  variant?: 'success' | 'error' | 'info';
  onClose: () => void;
}

const toneMap = {
  success: 'bg-emerald-500 text-white',
  error: 'bg-rose-500 text-white',
  info: 'bg-slate-800 text-white'
};

export function Toast({ message, variant = 'info', onClose }: ToastProps) {
  useEffect(() => {
    const timer = window.setTimeout(onClose, 4500);
    return () => window.clearTimeout(timer);
  }, [onClose]);

  return (
    <div className={`fixed bottom-6 right-6 z-50 max-w-sm rounded-3xl px-5 py-4 shadow-soft ${toneMap[variant]}`}>
      <p className="text-sm font-medium">{message}</p>
    </div>
  );
}
