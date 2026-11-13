// Format tampilan yang dipakai di seluruh aplikasi. Hanya untuk tampilan: data asli tidak diubah.

const numberFormatter = new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 });
const dateFormatter = new Intl.DateTimeFormat('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });

// Store mengisi NPWP kosong dengan "01.000.000.0-000.000"
const NPWP_PLACEHOLDER_DIGITS = '010000000000000';

/** 311972485 → "Rp 311.972.485" */
export function formatRupiah(value) {
    return `Rp ${numberFormatter.format(Number(value) || 0)}`;
}

/** 311972485 → "311.972.485" */
export function formatNumber(value) {
    return numberFormatter.format(Number(value) || 0);
}

const compactFormatter = new Intl.NumberFormat('id-ID', { maximumFractionDigits: 2 });

/** Angka ringkas untuk grafik: 2400000000 → "2,4 M", 412000000 → "412 Jt", 1250000000 → "1,25 M" */
export function formatCompact(value) {
    const number = Number(value) || 0;
    const abs = Math.abs(number);
    // Mulai 999,5 Jt ditulis dalam miliar, supaya tidak muncul "1.000 Jt"
    if (abs >= 999.5e9) return `${compactFormatter.format(number / 1e12)} T`;
    if (abs >= 999.5e6) return `${compactFormatter.format(number / 1e9)} M`;
    if (abs >= 1e6) return `${numberFormatter.format(number / 1e6)} Jt`;
    return numberFormatter.format(number);
}

/** 2400000000 → "Rp 2,4 M" */
export function formatRupiahShort(value) {
    return `Rp ${formatCompact(value)}`;
}

/** Date atau string tanggal ("2026-09-13", ISO, "13/09/2026") → Date lokal, atau null */
export function parseDate(value) {
    if (!value) return null;
    if (value instanceof Date) return Number.isNaN(value.getTime()) ? null : value;

    const text = String(value).trim();
    // Tanggal saja (tanpa jam) dibaca sebagai tanggal lokal, supaya tidak bergeser karena zona waktu
    const ymd = text.match(/^(\d{4})-(\d{1,2})-(\d{1,2})(?:$|T00:00:00(?:\.0+)?Z?$)/);
    if (ymd) return new Date(Number(ymd[1]), Number(ymd[2]) - 1, Number(ymd[3]));
    const dmy = text.match(/^(\d{1,2})[/\-.](\d{1,2})[/\-.](\d{4})$/);
    if (dmy) return new Date(Number(dmy[3]), Number(dmy[2]) - 1, Number(dmy[1]));

    const parsed = new Date(text);
    return Number.isNaN(parsed.getTime()) ? null : parsed;
}

/** "2026-09-13" → "13 Sep 2026"; tanggal kosong/tidak valid → "—" */
export function formatTanggal(value) {
    const date = parseDate(value);
    return date ? dateFormatter.format(date) : '—';
}

/**
 * NPWP disamakan jadi 16 digit tanpa tanda baca (NPWP 15 digit diberi "0" di depan).
 * Kosong / placeholder → "" supaya tampilan bisa menampilkan "—".
 */
export function formatNpwp(value) {
    const digits = String(value ?? '').replace(/\D/g, '');
    if (!digits || digits === NPWP_PLACEHOLDER_DIGITS || /^0+$/.test(digits)) return '';
    return digits.length === 15 ? `0${digits}` : digits;
}
