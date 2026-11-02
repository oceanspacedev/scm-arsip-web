import { describe, it } from 'node:test';
import assert from 'node:assert/strict';

// taxStore membaca localStorage saat modul dimuat
const storage = new Map();
globalThis.localStorage = {
    getItem: (key) => (storage.has(key) ? storage.get(key) : null),
    setItem: (key, value) => storage.set(key, String(value)),
    removeItem: (key) => storage.delete(key),
};

const PROGRAM = { id: 'P1', title: 'PROGRAM DEALER AUG 2026 C100I', company_name: 'CV. TOP SELULAR', documents: [] };

const requests = [];
const skipped = [];
let nextLogId = 10;
globalThis.fetch = async (url, options = {}) => {
    const skipMatch = String(url).match(/^\/api\/document-logs\/(\d+)\/skip$/);
    if (skipMatch) {
        skipped.push(skipMatch[1]);
        return { ok: true, status: 200, json: async () => ({ success: true }) };
    }
    if (url !== '/api/documents/auto-upload') {
        return { ok: false, status: 404, json: async () => ({}) };
    }
    const form = options.body;
    const name = form.get('file').name;
    requests.push({ name, programId: form.get('program_id'), documentType: form.get('document_type'), number: form.get('number'), logId: form.get('log_id') });

    let body;
    if (name.startsWith('sibuk')) {
        // Pertama kali AI kena batas permintaan; saat diulang (membawa log_id) berhasil
        body = form.get('log_id')
            ? { success: true, status: 'matched', document_type: 'invoice', reason: 'No. SJ 2609000812', extracted: { document_type: 'invoice' }, program: PROGRAM, log_id: Number(form.get('log_id')) }
            : { success: false, status: 'review', message: 'AI belum bisa membaca dokumen ini: Batas permintaan AI tercapai (429).', extracted: null, candidates: [], log_id: 77 };
    } else if (form.get('program_id')) {
        body = { success: true, status: 'matched', document_type: form.get('document_type'), reason: 'Dipilih manual', program: PROGRAM, log_id: Number(form.get('log_id')) };
    } else if (name.startsWith('2004')) {
        // AI di server gagal total: jenis dokumennya pun tidak diketahui
        body = { success: false, status: 'review', message: 'AI belum bisa membaca dokumen ini: Model AI tidak tersedia (404). Pilih programnya.', extracted: null, candidates: [], log_id: 88 };
    } else if (name.startsWith('invoice')) {
        body = { success: true, status: 'matched', document_type: 'invoice', reason: 'No. SJ 2609000804', extracted: { document_type: 'invoice', invoice_no: '791/TOP/IX/26' }, program: PROGRAM, log_id: nextLogId++ };
    } else {
        body = { success: false, status: 'review', message: 'Pilih programnya.', extracted: { document_type: 'faktur_pajak', faktur_number: '04002600372891514', dpp_amount: 25443694 }, candidates: [], log_id: nextLogId++ };
    }
    return { ok: true, status: 200, json: async () => body };
};

const { useDocumentUploadQueue, guessDocumentType } = await import('../../resources/js/store/documentUploadQueue.js');

async function untilIdle(queue) {
    while (queue.isProcessing) {
        await new Promise((resolve) => setTimeout(resolve, 5));
    }
}

describe('antrean unggah invoice & faktur di latar belakang', () => {
    const { queue, counts, enqueue, canSave, saveChoice, skip, canRetry, retry } = useDocumentUploadQueue();

    it('memproses berkas satu per satu tanpa menunggu modal', async () => {
        enqueue([new File(['%PDF'], 'invoice-791.pdf'), new File(['%PDF'], 'faktur-1514.pdf'), new File(['x'], 'catatan.txt')]);
        assert.equal(queue.isProcessing, true, 'pemrosesan langsung berjalan setelah dikirim');
        await untilIdle(queue);

        assert.deepEqual(requests.map((r) => r.name), ['invoice-791.pdf', 'faktur-1514.pdf'], 'format tidak didukung tidak dikirim');
        assert.deepEqual(queue.items.map((item) => item.status), ['matched', 'review', 'failed']);
        assert.equal(queue.items[0].program.title, 'PROGRAM DEALER AUG 2026 C100I');
        assert.equal(queue.items[1].documentType, 'faktur_pajak');
        assert.equal(counts.value.finished, 2, 'yang perlu dipilih belum dihitung selesai');
    });

    it('pilihan manual faktur mengirim jenis, nomor, dan baris riwayatnya, tanpa dibaca ulang', async () => {
        const faktur = queue.items[1];
        faktur.choice = 'P1';
        await saveChoice(faktur);

        assert.deepEqual(requests.at(-1), { name: 'faktur-1514.pdf', programId: 'P1', documentType: 'faktur_pajak', number: '04002600372891514', logId: '11' });
        assert.equal(faktur.status, 'matched');
    });

    it('halaman yang gagal dibaca AI bisa diulang tanpa membuat baris riwayat baru', async () => {
        enqueue([new File(['%PDF'], 'sibuk (hal 07).pdf')]);
        await untilIdle(queue);
        const item = queue.items.at(-1);
        assert.equal(item.status, 'review');
        assert.equal(canRetry(item), true);

        retry(item);
        await untilIdle(queue);

        assert.equal(requests.at(-1).logId, '77', 'baris riwayat yang sama yang diperbarui');
        assert.equal(item.status, 'matched');
        assert.equal(canRetry(queue.items.find((i) => i.file.name === 'catatan.txt')), false, 'format salah tidak bisa diulang');
    });

    it('dokumen yang tidak terbaca AI baru dikirim setelah program dan jenisnya dipilih', async () => {
        enqueue([new File(['%PDF'], '2004000091.pdf')]);
        await untilIdle(queue);
        const item = queue.items.at(-1);
        assert.equal(item.status, 'review');
        assert.equal(item.typeChoice, '', 'nama berkas tidak menyebut jenisnya');

        item.choice = 'P1';
        const sent = requests.length;
        assert.equal(canSave(item), false);
        await saveChoice(item);
        assert.equal(requests.length, sent, 'tidak dikirim sebagai Invoice diam-diam');

        item.typeChoice = 'faktur_pajak';
        await saveChoice(item);
        assert.deepEqual(requests.at(-1), { name: '2004000091.pdf', programId: 'P1', documentType: 'faktur_pajak', number: null, logId: '88' });
        assert.equal(item.status, 'matched');
        assert.equal(item.documentType, 'faktur_pajak');
    });

    it('jenis dokumen ditebak dari nama berkas bila ada petunjuknya', () => {
        assert.equal(guessDocumentType('RX00282-CV TOP SELULAR-PROGRAM DEALER AUG 2026 C100I_FAKTUR_PAJAK.pdf'), 'faktur_pajak');
        assert.equal(guessDocumentType('RX00282-CV TOP SELULAR-PROGRAM DEALER AUG 2026 C100I_INVOICE.pdf'), 'invoice');
        assert.equal(guessDocumentType('FP 04002600372891514.pdf'), 'faktur_pajak');
        assert.equal(guessDocumentType('2004000091.pdf'), '');
        assert.equal(guessDocumentType('Gabungan (hal 01).pdf'), '');
    });

    it('berkas yang dilewati dicatat di riwayat', async () => {
        enqueue([new File(['%PDF'], 'faktur-lain.pdf')]);
        await untilIdle(queue);
        const item = queue.items.at(-1);

        skip(item);

        assert.equal(item.status, 'skipped');
        assert.deepEqual(skipped, [String(item.logId)]);
    });
});
