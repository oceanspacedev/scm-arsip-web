// Memecah PDF gabungan (mis. 30 invoice dalam satu berkas) menjadi satu berkas per halaman,
// langsung di browser, supaya tiap halaman bisa dicocokkan ke programnya sendiri.

async function loadPdfLib() {
    const lib = await import('pdf-lib');
    // Build ESM (Vite) punya ekspor bernama; build CommonJS (Node) menaruhnya di default
    return lib.PDFDocument ? lib : lib.default;
}

export function isPdf(file) {
    return file?.type === 'application/pdf' || /\.pdf$/i.test(file?.name || '');
}

async function loadPdf(file) {
    const { PDFDocument } = await loadPdfLib();
    return PDFDocument.load(await file.arrayBuffer(), { ignoreEncryption: true });
}

/** Jumlah halaman PDF, atau null bila bukan PDF / tidak bisa dibuka */
export async function countPdfPages(file) {
    if (!isPdf(file)) return null;
    try {
        return (await loadPdf(file)).getPageCount();
    } catch {
        return null;
    }
}

/**
 * Satu berkas per halaman: "Gabungan.pdf" → "Gabungan (hal 01).pdf", "Gabungan (hal 02).pdf", ...
 * PDF satu halaman (atau bukan PDF) dikembalikan apa adanya.
 */
export async function splitPdfPages(file) {
    if (!isPdf(file)) return [file];

    const { PDFDocument } = await loadPdfLib();
    const source = await loadPdf(file);
    const total = source.getPageCount();
    if (total <= 1) return [file];

    const baseName = file.name.replace(/\.pdf$/i, '');
    const digits = String(total).length;
    const parts = [];
    for (let index = 0; index < total; index++) {
        const part = await PDFDocument.create();
        const [page] = await part.copyPages(source, [index]);
        part.addPage(page);
        const pageNumber = String(index + 1).padStart(digits, '0');
        parts.push(new File([await part.save()], `${baseName} (hal ${pageNumber}).pdf`, { type: 'application/pdf' }));
    }
    return parts;
}
