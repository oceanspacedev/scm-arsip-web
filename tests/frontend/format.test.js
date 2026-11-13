import { describe, it } from 'node:test';
import assert from 'node:assert/strict';
import { formatCompact, formatNpwp, formatNumber, formatRupiah, formatRupiahShort, formatTanggal } from '../../resources/js/lib/format.js';

describe('format tampilan', () => {
    it('Rupiah memakai titik ribuan tanpa desimal', () => {
        assert.equal(formatRupiah(311972485), 'Rp 311.972.485');
        assert.equal(formatRupiah('25443694.4'), 'Rp 25.443.694');
        assert.equal(formatRupiah(null), 'Rp 0');
        assert.equal(formatNumber(52283033), '52.283.033');
    });

    it('angka ringkas untuk grafik', () => {
        assert.equal(formatCompact(2400000000), '2,4 M');
        assert.equal(formatCompact(1250000000), '1,25 M');
        assert.equal(formatCompact(999999999), '1 M', 'tidak menjadi "1.000 Jt"');
        assert.equal(formatCompact(412300000), '412 Jt');
        assert.equal(formatCompact(250000), '250.000');
        assert.equal(formatCompact(0), '0');
        assert.equal(formatRupiahShort(2400000000), 'Rp 2,4 M');
    });

    it('tanggal tidak bergeser karena zona waktu', () => {
        assert.equal(formatTanggal('2026-09-13'), '13 Sep 2026');
        assert.equal(formatTanggal('2026-08-01T00:00:00.000000Z'), '01 Agu 2026');
        assert.equal(formatTanggal('13/09/2026'), '13 Sep 2026');
        assert.equal(formatTanggal(''), '—');
        assert.equal(formatTanggal('bukan tanggal'), '—');
    });

    it('NPWP disamakan jadi 16 digit', () => {
        assert.equal(formatNpwp('62.525.392.7-607.000'), '0625253927607000', '15 digit diberi 0 di depan');
        assert.equal(formatNpwp('0313523391426000'), '0313523391426000');
        assert.equal(formatNpwp('0626 3844 6504 7000'), '0626384465047000');
        assert.equal(formatNpwp('01.000.000.0-000.000'), '', 'placeholder dari store berarti kosong');
        assert.equal(formatNpwp(''), '');
        assert.equal(formatNpwp(null), '');
    });
});
