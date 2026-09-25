import { describe, it } from 'node:test';
import assert from 'node:assert/strict';

if (typeof globalThis.localStorage === 'undefined') {
    globalThis.localStorage = {
        getItem: () => null,
        setItem: () => {},
        removeItem: () => {},
        clear: () => {}
    };
}

const { formatDate, getProgramMonth, getProgramYear, getProgramMonthNumber } = await import('../../resources/js/store/taxStore.js');

describe('formatDate', () => {
    it('formats YYYY-MM-DD to DD-MM-YYYY', () => {
        assert.equal(formatDate('2003-02-25'), '25-02-2003');
        assert.equal(formatDate('2026-09-24'), '24-09-2026');
        assert.equal(formatDate('2026-03-05'), '05-03-2026');
    });

    it('preserves and standardizes DD-MM-YYYY, DD/MM/YYYY, and DD.MM.YYYY', () => {
        assert.equal(formatDate('25-02-2003'), '25-02-2003');
        assert.equal(formatDate('25/02/2003'), '25-02-2003');
        assert.equal(formatDate('25.02.2003'), '25-02-2003');
        assert.equal(formatDate('5-2-2003'), '05-02-2003');
    });

    it('formats ISO timestamps to DD-MM-YYYY without timezone shift', () => {
        assert.equal(formatDate('2026-09-24T00:00:00.000000Z'), '24-09-2026');
        assert.equal(formatDate('2003-02-25T00:00:00.000Z'), '25-02-2003');
    });

    it('handles empty or dash inputs gracefully', () => {
        assert.equal(formatDate(null), '-');
        assert.equal(formatDate(undefined), '-');
        assert.equal(formatDate(''), '-');
        assert.equal(formatDate('-'), '-');
    });
});

describe('getProgramYear and getProgramMonth', () => {
    it('extracts year and month from DD-MM-YYYY correctly', () => {
        assert.equal(getProgramYear('25-02-2003'), '2003');
        assert.equal(getProgramMonth('25-02-2003'), 'Februari');
        assert.equal(getProgramMonthNumber('25-02-2003'), 2);
    });

    it('extracts year and month from YYYY-MM-DD correctly', () => {
        assert.equal(getProgramYear('2026-09-24'), '2026');
        assert.equal(getProgramMonth('2026-09-24'), 'September');
        assert.equal(getProgramMonthNumber('2026-09-24'), 9);
    });

    it('extracts year and month from DD/MM/YYYY correctly', () => {
        assert.equal(getProgramYear('05/03/2026'), '2026');
        assert.equal(getProgramMonth('05/03/2026'), 'Maret');
        assert.equal(getProgramMonthNumber('05/03/2026'), 3);
    });

    it('extracts year and month from Indonesian month name strings', () => {
        assert.equal(getProgramYear('Maret 2026'), '2026');
        assert.equal(getProgramMonth('Maret 2026'), 'Maret');
    });
});

describe('Excel date conversion', () => {
    it('accurately converts Excel serial numbers without timezone shifting 25 to 24', () => {
        // 37677 is 25 Feb 2003
        const val = 37677;
        const utcDays = Math.floor(val - 25569);
        const dateObj = new Date(utcDays * 86400 * 1000);
        const y = dateObj.getUTCFullYear();
        const m = String(dateObj.getUTCMonth() + 1).padStart(2, '0');
        const d = String(dateObj.getUTCDate()).padStart(2, '0');
        const converted = `${y}-${m}-${d}`;
        assert.equal(converted, '2003-02-25');
        assert.equal(formatDate(converted), '25-02-2003');
    });

    it('accurately converts 2026 serial number without shifting', () => {
        // 46086 is 5 Mar 2026
        const val = 46086;
        const utcDays = Math.floor(val - 25569);
        const dateObj = new Date(utcDays * 86400 * 1000);
        const y = dateObj.getUTCFullYear();
        const m = String(dateObj.getUTCMonth() + 1).padStart(2, '0');
        const d = String(dateObj.getUTCDate()).padStart(2, '0');
        const converted = `${y}-${m}-${d}`;
        assert.equal(converted, '2026-03-05');
        assert.equal(formatDate(converted), '05-03-2026');
    });
});
