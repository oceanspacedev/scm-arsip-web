import { reactive, computed } from 'vue';
import { useTaxStore } from './taxStore.js';

const MAX_BYTES = 15 * 1024 * 1024;
const ALLOWED_EXTENSIONS = ['pdf', 'jpg', 'jpeg', 'png'];
const FINISHED_STATUSES = ['matched', 'duplicate', 'failed', 'skipped'];

// Antrean unggah invoice & faktur pajak otomatis. Disimpan di level modul supaya tetap berjalan saat
// modal ditutup atau pengguna pindah halaman. Isinya hilang bila tab dimuat ulang, jadi browser
// diminta mengingatkan selama masih ada berkas yang belum selesai.
const queue = reactive({ items: [], isProcessing: false });
let nextKey = 0;

if (typeof window !== 'undefined') {
    window.addEventListener('beforeunload', (event) => {
        if (queue.items.some(item => !FINISHED_STATUSES.includes(item.status))) {
            event.preventDefault();
            event.returnValue = '';
        }
    });
}

/**
 * Tebakan jenis dari nama berkas, dipakai bila AI tidak bisa membaca dokumen. Pengguna tetap bisa
 * menggantinya; tanpa petunjuk di nama, jenisnya harus dipilih sendiri.
 */
export function guessDocumentType(fileName) {
    const name = String(fileName || '').toLowerCase();
    if (/faktur|pajak|(^|[^a-z])fp([^a-z]|$)/.test(name)) return 'faktur_pajak';
    if (/invoice|(^|[^a-z])inv([^a-z]|$)/.test(name)) return 'invoice';
    return '';
}

function applyResult(item, result) {
    item.status = ['matched', 'duplicate', 'review'].includes(result.status) ? result.status : 'failed';
    item.message = result.message || '';
    item.reason = result.reason || '';
    item.extracted = result.extracted || item.extracted;
    item.documentType = result.document_type || item.extracted?.document_type || item.documentType;
    item.logId = result.log_id || item.logId;
    item.candidates = result.candidates || [];
    item.program = result.program
        ? { title: result.program.title, company_name: result.program.company_name }
        : null;
}

export function useDocumentUploadQueue() {
    const store = useTaxStore();

    // Satu per satu: tiap berkas dibaca AI beberapa detik, dan server lokal melayani satu request sekaligus
    async function processPending() {
        if (queue.isProcessing) return;
        queue.isProcessing = true;
        try {
            let item;
            while ((item = queue.items.find(i => i.status === 'pending'))) {
                item.status = 'processing';
                // logId terisi bila berkas sedang diulang, supaya baris History yang sama yang diperbarui
                applyResult(item, await store.autoUploadDocument(item.file, { logId: item.logId }));
            }
        } finally {
            queue.isProcessing = false;
        }
    }

    function enqueue(files) {
        for (const file of Array.from(files || [])) {
            const extension = file.name.split('.').pop().toLowerCase();
            const problem = !ALLOWED_EXTENSIONS.includes(extension)
                ? 'Format tidak didukung, gunakan PDF/JPG/PNG.'
                : (file.size > MAX_BYTES ? 'Melebihi batas 15 MB.' : '');

            queue.items.push({
                key: nextKey++,
                file,
                status: problem ? 'failed' : 'pending',
                message: problem,
                // Format/ukuran salah tidak akan berhasil walau diulang
                invalid: Boolean(problem),
                documentType: null,
                // Jenis pilihan pengguna, hanya dipakai bila AI tidak mengenali jenisnya
                typeChoice: guessDocumentType(file.name),
                logId: null,
                program: null,
                reason: '',
                extracted: null,
                candidates: [],
                choice: '',
                saving: false
            });
        }
        processPending();
    }

    /** Program sudah dipilih dan jenisnya jelas (dibaca AI atau dipilih pengguna) */
    function canSave(item) {
        return Boolean(item.choice && (item.documentType || item.typeChoice) && !item.saving);
    }

    async function saveChoice(item) {
        if (!canSave(item)) return;
        const documentType = item.documentType || item.typeChoice;
        item.saving = true;
        try {
            applyResult(item, await store.autoUploadDocument(item.file, {
                programId: item.choice,
                documentType,
                number: documentType === 'faktur_pajak' ? item.extracted?.faktur_number : item.extracted?.invoice_no,
                logId: item.logId
            }));
        } finally {
            item.saving = false;
        }
    }

    function skip(item) {
        item.status = 'skipped';
        store.skipDocumentLog(item.logId);
    }

    /** Bisa diulang: gagal di server/AI, atau AI tidak membaca apa pun (mis. batas permintaan, koneksi) */
    function canRetry(item) {
        return !item.invalid && (item.status === 'failed' || (item.status === 'review' && !item.extracted));
    }

    function retry(item) {
        if (!canRetry(item)) return;
        item.status = 'pending';
        item.message = '';
        processPending();
    }

    function clearFinished() {
        queue.items = queue.items.filter(item => !FINISHED_STATUSES.includes(item.status));
    }

    const counts = computed(() => {
        const result = { total: queue.items.length, finished: 0 };
        for (const item of queue.items) {
            result[item.status] = (result[item.status] || 0) + 1;
            if (FINISHED_STATUSES.includes(item.status)) result.finished++;
        }
        return result;
    });

    return { queue, counts, enqueue, canSave, saveChoice, skip, canRetry, retry, clearFinished };
}
