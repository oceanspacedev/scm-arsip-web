import { describe, it } from 'node:test';
import assert from 'node:assert/strict';
import pdfLib from 'pdf-lib';
import { countPdfPages, splitPdfPages } from '../../resources/js/utils/pdfSplit.js';

async function makePdf(name, pages) {
    const doc = await pdfLib.PDFDocument.create();
    for (let i = 0; i < pages; i++) {
        doc.addPage([200, 200]).drawText(`Halaman ${i + 1}`, { x: 20, y: 100 });
    }
    return new File([await doc.save()], name, { type: 'application/pdf' });
}

describe('pecah PDF gabungan per halaman', () => {
    it('membuat satu berkas per halaman dengan nama berurutan', async () => {
        const merged = await makePdf('Gabungan Invoice.pdf', 12);

        const parts = await splitPdfPages(merged);

        assert.equal(parts.length, 12);
        assert.equal(parts[0].name, 'Gabungan Invoice (hal 01).pdf');
        assert.equal(parts[11].name, 'Gabungan Invoice (hal 12).pdf');
        for (const part of parts) {
            assert.equal(await countPdfPages(part), 1);
        }
    });

    it('PDF satu halaman dan berkas gambar dikirim apa adanya', async () => {
        const single = await makePdf('INV 791.pdf', 1);
        const image = new File(['jpg'], 'scan.jpg', { type: 'image/jpeg' });

        assert.deepEqual(await splitPdfPages(single), [single]);
        assert.deepEqual(await splitPdfPages(image), [image]);
        assert.equal(await countPdfPages(image), null);
    });
});
