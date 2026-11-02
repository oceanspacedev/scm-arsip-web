// Pencarian program di pemilih program (panel unggah). Program cocok bila semua kata kunci ada di
// nama program, perusahaan, supplier, No. PO/SJ, No. Invoice, atau No. Faktur. Tanda baca diabaikan,
// jadi "04.002.600" tetap menemukan faktur 04002600372891514.

const compact = (text) => String(text || '').toLowerCase().replace(/[^a-z0-9]/g, '');

// Kolom yang belum diisi kadang berisi "-"
const filled = (value) => {
    const text = String(value ?? '').trim();
    return text === '-' ? '' : text;
};

export function programTitle(program) {
    return program.title || program.program_name || '';
}

export function programMeta(program) {
    const poSj = filled(program.po_sj_number);
    const invoice = filled(program.invoice_no);
    return [
        poSj && `PO/SJ ${poSj}`,
        filled(program.company_name),
        invoice && `No. Invoice ${invoice}`
    ].filter(Boolean).join(' · ');
}

export function matchesProgram(program, query) {
    const words = String(query || '').toLowerCase().split(/\s+/).filter(Boolean);
    if (words.length === 0) return true;

    const text = [
        programTitle(program),
        program.company_name,
        program.supplier,
        program.po_sj_number,
        program.invoice_no,
        program.faktur_number
    ].filter(Boolean).join(' ').toLowerCase();
    const compactText = compact(text);

    return words.every(word => text.includes(word) || (compact(word) !== '' && compactText.includes(compact(word))));
}
