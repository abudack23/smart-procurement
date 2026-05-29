import { PropsWithChildren } from 'react';

interface CardProps {
  className?: string;
}

export function Card({ className = '', children }: PropsWithChildren<CardProps>) {
  return (
    <div className={`rounded-3xl border border-slate-200/80 bg-white shadow-soft dark:border-slate-800/80 dark:bg-slate-950 ${className}`}>
      {children}
    </div>
  );
}
