/**
 * Format a JS Date or date string to YYYY-MM-DD
 */
export function toDateString(date) {
  const d = date instanceof Date ? date : new Date(date);
  return d.toISOString().slice(0, 10);
}

/**
 * Format YYYY-MM-DD to locale-aware display string
 */
export function formatDate(dateStr) {
  if (!dateStr) return '-';
  const d = new Date(dateStr + 'T00:00:00');
  return d.toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' });
}

/**
 * Return today as YYYY-MM-DD
 */
export function today() {
  return toDateString(new Date());
}

/**
 * Month name in Indonesian
 */
const MONTHS_ID = [
  'Januari','Februari','Maret','April','Mei','Juni',
  'Juli','Agustus','September','Oktober','November','Desember',
];

export function monthName(num) {
  return MONTHS_ID[(num - 1)] ?? '-';
}

/**
 * Zero-pad a number
 */
export function pad(n) {
  return String(n).padStart(2, '0');
}
