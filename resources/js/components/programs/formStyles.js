// Gaya formulir program bersama (dialog ubah program di tabel dan di halaman detail)

export const dialogOverlay = 'fixed inset-0 z-50 flex items-start sm:items-center justify-center p-4 overflow-y-auto bg-slate-900/50 dark:bg-black/70';
export const dialogPanel = 'w-full max-w-2xl my-auto max-h-[calc(100vh-2rem)] flex flex-col rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-graphite-card shadow-[0_16px_48px_rgba(0,0,0,0.18)]';
export const dialogHeader = 'flex items-start justify-between gap-4 px-6 py-4 border-b border-slate-200 dark:border-slate-800 shrink-0';
export const dialogBody = 'flex-1 overflow-y-auto px-6 py-5 space-y-8';
export const dialogFooter = 'flex items-center justify-end gap-2 px-6 py-4 border-t border-slate-200 dark:border-slate-800 shrink-0';
export const closeButton = 'h-8 w-8 -mr-2 inline-flex items-center justify-center rounded-lg text-slate-400 hover:text-slate-900 hover:bg-slate-100 dark:hover:text-slate-100 dark:hover:bg-slate-800 transition-colors cursor-pointer';

export const sectionHead = 'flex items-center justify-between gap-3 mb-4';
export const sectionTitle = 'text-sm font-medium text-slate-900 dark:text-slate-100';
export const readOnlyBadge = 'inline-flex items-center gap-1.5 text-xs text-slate-500 dark:text-slate-400';

export const fieldGrid = 'grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-4';
export const fieldLabel = 'block mb-2 text-sm text-slate-700 dark:text-slate-300';
export const fieldHint = 'mt-1.5 text-xs text-slate-500 dark:text-slate-400';
export const fieldInput = 'w-full h-9 px-3 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-sm text-slate-900 dark:text-slate-100 placeholder:text-slate-400 dark:placeholder:text-slate-500 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-colors disabled:bg-slate-50 dark:disabled:bg-slate-800/60 disabled:text-slate-500 dark:disabled:text-slate-400 disabled:cursor-not-allowed';
export const fieldNumber = `${fieldInput} text-right tabular-nums`;

export const summaryBox = 'mt-4 rounded-lg border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-900/60 divide-y divide-slate-200 dark:divide-slate-800 text-sm';
export const summaryRow = 'flex items-center justify-between gap-4 px-4 h-10';

export const buttonSecondary = 'h-9 px-4 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm font-medium text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed';
export const buttonPrimary = 'h-9 px-4 inline-flex items-center gap-2 rounded-lg text-sm font-medium bg-slate-900 text-white hover:bg-slate-800 dark:bg-slate-100 dark:text-slate-900 dark:hover:bg-white transition-colors cursor-pointer disabled:opacity-60 disabled:cursor-not-allowed';

export const PPH_TYPE_OPTIONS = [
  { value: 'NON_PPH', label: 'Non PPh' },
  { value: 'PPH_21', label: 'PPh 21 (2,5%)' },
  { value: 'PPH_23', label: 'PPh 23 (2%)' },
  { value: 'PPH_4_2', label: 'PPh 4 ayat 2 (10%)' },
  { value: 'PPH_23_BONUS', label: 'PPh 23 atas bonus (15%)' },
];
