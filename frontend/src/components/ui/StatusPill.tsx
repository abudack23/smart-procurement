interface StatusPillProps {
  value: string;
}

const toneMap: Record<string, string> = {
  Awarded: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300',
  'Under Review': 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-200',
  'Awaiting Bids': 'bg-sky-100 text-sky-700 dark:bg-sky-900/30 dark:text-sky-200',
  'Pending Approval': 'bg-violet-100 text-violet-700 dark:bg-violet-900/30 dark:text-violet-200'
};

export function StatusPill({ value }: StatusPillProps) {
  return (
    <span className={`inline-flex rounded-full px-3 py-1 text-xs font-semibold ${toneMap[value] ?? 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-200'}`}>
      {value}
    </span>
  );
}
