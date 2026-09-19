import { reactive, computed, ref } from 'vue';
import { saveDocumentBlob, getDocumentBlob, deleteDocumentBlob, clearAllDocumentBlobs } from '../utils/documentDb';
import { USER_STORAGE_KEY, userFromStorage } from './authSession';

// Storage key synced with backend
const STORAGE_KEY = 'scm_taxvault_programs_v2';

function loadStoredPrograms() {
    try {
        const stored = localStorage.getItem(STORAGE_KEY);
        if (stored) {
            return JSON.parse(stored);
        }
    } catch (e) {
        console.error("Failed to load from storage", e);
    }
    return [];
}

export const defaultUsers = [
    {
        id: 'usr-1',
        email: 'admin@scm.corp',
        name: 'Budi Santoso',
        phone: '081234567890',
        password: 'password123',
        role: 'Admin SCM',
        division: 'Divisi Supply Chain Management',
        initials: 'BS',
        status: 'approved',
        registered_at: '2026-08-01'
    },
    {
        id: 'usr-gudang',
        email: 'gudang@scm.corp',
        name: 'Ahmad Fauzi',
        phone: '081298765431',
        password: 'password123',
        role: 'Staff Gudang',
        division: 'Operasional Gudang & Logistik',
        initials: 'AF',
        status: 'approved',
        registered_at: '2026-08-10'
    },
    {
        id: 'usr-finance',
        email: 'finance@scm.corp',
        name: 'Dewi Lestari',
        phone: '081224290503',
        password: 'password123',
        role: 'Staff Finance',
        division: 'Tax & Finance Compliance',
        initials: 'DL',
        status: 'approved',
        registered_at: '2026-08-12'
    },
    {
        id: 'usr-scm',
        email: 'scm@scm.corp',
        name: 'Rian Hidayat',
        phone: '081298765433',
        password: 'password123',
        role: 'Staff SCM',
        division: 'Supply Chain Management',
        initials: 'RH',
        status: 'approved',
        registered_at: '2026-08-14'
    },
    {
        id: 'usr-2',
        email: 'auditor@pajak.corp',
        name: 'Siti Rahmawati',
        phone: '081224290502',
        password: 'password123',
        role: 'Finance',
        division: 'Tax & Compliance Audit',
        initials: 'SR',
        status: 'approved',
        registered_at: '2026-08-15'
    },
    {
        id: 'usr-3',
        email: 'staff@scm.corp',
        name: 'Hendra Wijaya',
        phone: '081298765432',
        password: 'password123',
        role: 'SCM',
        division: 'Operasional Logistik SCM',
        initials: 'HW',
        status: 'approved',
        registered_at: '2026-08-20'
    },
    {
        id: 'usr-4',
        email: 'reza25022003@gmail.com',
        name: 'Reza Pratama',
        phone: '081234567899',
        password: 'password123',
        role: 'Tim Pajak',
        division: 'Tax & Compliance Audit',
        initials: 'RP',
        status: 'pending',
        registered_at: '2026-09-07'
    }
];

export const demoUsers = defaultUsers.filter(u => u.status === 'approved');

const USERS_LIST_STORAGE_KEY = 'scm_taxvault_users_list_v2';
const DEMO_ACCOUNTS_STORAGE_KEY = 'scm_show_demo_accounts';

function loadStoredDemoAccounts() {
    try {
        const val = localStorage.getItem(DEMO_ACCOUNTS_STORAGE_KEY);
        if (val !== null) {
            return val !== 'false';
        }
    } catch (e) {
        console.error("Failed to load demo accounts preference", e);
    }
    return true;
}

function loadStoredUsersList() {
    try {
        const stored = localStorage.getItem(USERS_LIST_STORAGE_KEY);
        if (stored) {
            return JSON.parse(stored);
        }
    } catch (e) {
        console.error("Failed to load users list", e);
    }
    return JSON.parse(JSON.stringify(defaultUsers));
}

function loadStoredUser() {
    try {
        return userFromStorage(localStorage.getItem(USER_STORAGE_KEY));
    } catch (e) {
        console.error("Failed to load user from storage", e);
        return null;
    }
}

const state = reactive({
    programs: loadStoredPrograms(),
    users: loadStoredUsersList(),
    currentUser: loadStoredUser(),
    showDemoAccounts: loadStoredDemoAccounts(),
    isResetting: false,
    isMobileSidebarOpen: false,
    activeOtp: null,
    searchQuery: '',
    selectedCategory: 'Semua Kategori',
    selectedBrand: 'all',
    selectedStatus: 'all',
    selectedSupplier: 'all',
    selectedMonth: 'all',
    selectedCompany: 'all',
    sortBy: 'date-desc',
    activeNotification: null,
    isImportModalOpen: false,
    isApprovalModalOpen: false,
    selectedFiscalYear: localStorage.getItem('scm_fiscal_year') || String(new Date().getFullYear()),
    isLoggingOut: false,
});

function saveUsersToStorage() {
    try {
        localStorage.setItem(USERS_LIST_STORAGE_KEY, JSON.stringify(state.users));
    } catch (e) {
        console.error("Failed to save users list", e);
    }
}


function saveToStorage() {
    try {
        // Strip heavy base64 dataUrl before saving to localStorage to prevent quota limit issues
        const sanitized = state.programs.map(p => ({
            ...p,
            documents: (p.documents || []).map(d => {
                const { file_data, ...rest } = d;
                return rest;
            })
        }));
        localStorage.setItem(STORAGE_KEY, JSON.stringify(sanitized));
    } catch (e) {
        console.error("Failed to save to storage", e);
    }
}

export function formatRupiah(number) {
    if (number === null || number === undefined) return 'Rp 0';
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(number);
}

export function formatDate(dateString) {
    if (!dateString) return '-';
    try {
        const d = new Date(dateString);
        return new Intl.DateTimeFormat('id-ID', {
            day: 'numeric',
            month: 'short',
            year: 'numeric'
        }).format(d);
    } catch (e) {
        return dateString;
    }
}

export function formatUploadDate(dateString) {
    if (!dateString) return '-';
    // Return immediately if already cleanly formatted with comma
    if (!dateString.includes('T') && !dateString.includes('Z') && !dateString.includes('.000')) {
        return dateString;
    }
    try {
        const d = new Date(dateString);
        if (isNaN(d.getTime())) return dateString;
        return new Intl.DateTimeFormat('id-ID', {
            day: 'numeric',
            month: 'short',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        }).format(d).replace(':', '.');
    } catch (e) {
        return dateString;
    }
}

export function getCompleteness(program) {
    if (!program || !program.documents) {
        return { count: 0, total: 3, status: 'Belum Lengkap', badgeType: 'danger' };
    }
    const docTypes = new Set((program.documents || []).map(d => d.document_type));
    let count = 0;
    if (docTypes.has('invoice')) count++;
    if (docTypes.has('faktur_pajak')) count++;
    if (docTypes.has('mou')) count++;

    if (count === 3) {
        return { count: 3, total: 3, status: 'Lengkap', badgeType: 'success' };
    }
    if (count > 0) {
        return { count, total: 3, status: 'Sebagian', badgeType: 'warning' };
    }
    return { count: 0, total: 3, status: 'Belum Lengkap', badgeType: 'danger' };
}

export function getMissingDocuments(program) {
    if (!program) return [];
    const docTypes = (program.documents || []).map(d => d.document_type);
    const missing = [];
    if (!docTypes.includes('invoice')) missing.push('Invoice');
    if (!docTypes.includes('faktur_pajak')) missing.push('Faktur Pajak');
    if (!docTypes.includes('mou')) missing.push('Memo/DO');
    return missing;
}

export function getProgramMonth(dateString) {
    if (!dateString) return '-';
    try {
        const months = [
            'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
            'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
        ];
        const s = String(dateString).trim();
        for (const m of months) {
            if (s.toLowerCase().includes(m.toLowerCase())) return m;
        }
        const parts = s.split('-');
        if (parts.length >= 2) {
            const mIndex = parseInt(parts[1], 10) - 1;
            if (mIndex >= 0 && mIndex < 12) {
                return months[mIndex];
            }
        }
        const d = new Date(dateString);
        if (!isNaN(d.getTime())) {
            return new Intl.DateTimeFormat('id-ID', { month: 'long' }).format(d);
        }
    } catch (e) {}
    return '-';
}

export function getProgramYear(dateString) {
    if (!dateString) return '';
    try {
        const parts = String(dateString).split('-');
        if (parts.length >= 1 && parts[0].length === 4) {
            return parts[0];
        }
        const d = new Date(dateString);
        if (!isNaN(d.getTime())) {
            return String(d.getFullYear());
        }
    } catch (e) {}
    return '';
}

export function getProgramMonthNumber(dateString) {
    if (!dateString) return null;
    try {
        const parts = String(dateString).split('-');
        if (parts.length >= 2) {
            return parseInt(parts[1], 10);
        }
        const d = new Date(dateString);
        if (!isNaN(d.getTime())) {
            return d.getMonth() + 1;
        }
    } catch (e) {}
    return null;
}

export function getProgramCompanyName(program) {
    if (!program) return 'PT SCM Nusantara';
    if (program.company_name) return program.company_name;
    if (program.company) return program.company;
    
    const companies = [
        'PT SCM Nusantara',
        'PT SCM Solusi Indonesia',
        'PT SCM Logistik Utama',
        'PT Surya Citra Media Tbk'
    ];
    const idNum = typeof program.id === 'number' ? program.id : (parseInt(String(program.id).replace(/\D/g, ''), 10) || 1);
    return companies[(idNum - 1) % companies.length];
}

export function getProgramBrand(program) {
    if (!program) return 'SCM';
    if (program.brand) return program.brand;

    const title = (program.title || program.program_name || '').toLowerCase();
    const comp = (program.company_name || '').toLowerCase();
    if (title.includes('sctv') || comp.includes('sctv')) return 'SCTV';
    if (title.includes('indosiar') || comp.includes('indosiar')) return 'Indosiar';
    if (title.includes('vidio') || comp.includes('vidio')) return 'Vidio';
    if (title.includes('moji') || comp.includes('moji')) return 'Moji';
    if (title.includes('mentari') || comp.includes('mentari')) return 'Mentari TV';

    const defaultBrands = ['SCTV', 'Indosiar', 'Vidio', 'Moji', 'Mentari TV', 'SCM'];
    const idNum = typeof program.id === 'number' ? program.id : (parseInt(String(program.id).replace(/\D/g, ''), 10) || 1);
    return defaultBrands[(idNum - 1) % defaultBrands.length];
}

export function getProgramPoSjNumber(program) {
    if (!program) return '-';
    if (program.po_sj_number) return program.po_sj_number;
    if (program.no_po_sj) return program.no_po_sj;
    if (program.po_number && program.sj_number) return `${program.po_number} / ${program.sj_number}`;
    if (program.po_number) return program.po_number;
    if (program.mou_number) return program.mou_number;
    
    const idStr = String(program.id).padStart(4, '0');
    return `PO/2025/${idStr}`;
}

export const monthsList = [
    { value: 'all', label: 'Semua Bulan' },
    { value: '1', label: 'Januari' },
    { value: '2', label: 'Februari' },
    { value: '3', label: 'Maret' },
    { value: '4', label: 'April' },
    { value: '5', label: 'Mei' },
    { value: '6', label: 'Juni' },
    { value: '7', label: 'Juli' },
    { value: '8', label: 'Agustus' },
    { value: '9', label: 'September' },
    { value: '10', label: 'Oktober' },
    { value: '11', label: 'November' },
    { value: '12', label: 'Desember' }
];

export const PPH_OPTIONS = [
    { value: 'NON_PPH', label: 'Non PPh', rate: 0 },
    { value: 'PPH_21', label: 'PPh 21 (2,5%)', rate: 0.025 },
    { value: 'PPH_23', label: 'PPh 23 (2%)', rate: 0.02 },
    { value: 'PPH_4_2', label: 'PPh 4 Ayat 2 (10%)', rate: 0.10 },
    { value: 'PPH_23_BONUS', label: 'PPh 23 Atas Bonus (15%)', rate: 0.15 }
];

export function getPphLabel(type) {
    if (!type) return 'Non PPh';
    const found = PPH_OPTIONS.find(opt => opt.value === type);
    if (found) return found.label;
    if (type === 'PPH_21') return 'PPh 21 (2,5%)';
    if (type === 'PPH_23') return 'PPh 23 (2%)';
    if (type === 'PPH_4_2') return 'PPh 4 Ayat 2 (10%)';
    if (type === 'PPH_23_BONUS') return 'PPh 23 Atas Bonus (15%)';
    return type;
}

export function calculatePphAmount(dpp, pphType) {
    const numDpp = Number(dpp) || 0;
    const found = PPH_OPTIONS.find(opt => opt.value === pphType);
    if (!found || !found.rate) return 0;
    return Math.round(numDpp * found.rate);
}

export function mapBackendProgram(p) {
    if (!p) return null;
    return {
        id: String(p.id),
        program_name: p.title || p.program_name || '',
        supplier: p.supplier || '',
        category: p.category || 'Logistik',
        brand: p.brand || getProgramBrand(p),
        company_name: p.company_name || p.company || getProgramCompanyName(p),
        po_sj_number: p.po_sj_number || p.no_po_sj || getProgramPoSjNumber(p),
        npwp: p.npwp || '01.000.000.0-000.000',
        invoice_number: p.invoice_no || p.invoice_number || '',
        dpp: Number(p.dpp_amount ?? p.dpp) || 0,
        ppn: Number(p.ppn_amount ?? p.ppn) || 0,
        total_invoice: Number(p.total_amount ?? p.total_invoice) || 0,
        pph_type: p.pph_type || 'NON_PPH',
        pph: Number(p.pph_amount ?? p.pph) || 0,
        faktur_number: p.faktur_number || p.tax_invoice_number || '',
        faktur_date: p.faktur_date ? String(p.faktur_date).slice(0, 10) : (p.tax_invoice_date || ''),
        tax_notes: p.tax_notes || '',
        is_verified: !!p.is_verified,
        program_date: p.due_date ? String(p.due_date).slice(0, 10) : (p.program_date || ''),
        status: p.status || 'Perlu Tindakan',
        documents: (p.documents || []).map(d => ({
            id: String(d.id),
            document_type: d.type === 'faktur' ? 'faktur_pajak' : (d.type === 'memo' ? 'mou' : (d.document_type || d.type)),
            file_name: d.file_name,
            file_size: d.file_size,
            file_url: d.file_url || (d.file_path ? '/' + d.file_path : null),
            file_data: d.file_data || null,
            uploaded_at: formatUploadDate(d.uploaded_at) || 'Baru diunggah',
            uploaded_by: d.uploaded_by || 'Admin SCM'
        }))
    };
}

export const useTaxStore = () => {
    const programs = computed(() => state.programs);

    const dashboardPrograms = computed(() => {
        const yr = state.selectedFiscalYear;
        if (!yr || yr === 'all' || yr === 'Semua') {
            return state.programs;
        }
        return state.programs.filter(p => {
            const d = p.program_date || p.due_date || '';
            return String(d).startsWith(String(yr));
        });
    });

    const summaryMetrics = computed(() => {
        let totalInvoice = 0;
        let totalDpp = 0;
        let totalPpn = 0;
        let lengkapCount = 0;
        let sebagianCount = 0;
        let belumLengkapCount = 0;

        dashboardPrograms.value.forEach(p => {
            totalInvoice += Number(p.total_invoice) || 0;
            totalDpp += Number(p.dpp) || 0;
            totalPpn += Number(p.ppn) || 0;

            const c = getCompleteness(p);
            if (c.count === 3) lengkapCount++;
            else if (c.count > 0) sebagianCount++;
            else belumLengkapCount++;
        });

        return {
            totalPrograms: dashboardPrograms.value.length,
            totalInvoice,
            totalDpp,
            totalPpn,
            siapAudit: lengkapCount,
            lengkapCount,
            sebagianCount,
            belumLengkapCount,
        };
    });

    const needAttentionPrograms = computed(() => {
        // Return up to 5 programs that are incomplete or partial, prioritizing partial that only need 1 doc
        return dashboardPrograms.value
            .filter(p => (p.documents?.length || 0) < 3)
            .sort((a, b) => {
                const countA = a.documents?.length || 0;
                const countB = b.documents?.length || 0;
                // show 2/3 first so user can quickly make them complete
                return countB - countA;
            })
            .slice(0, 5)
            .map(p => ({
                id: p.id,
                name: p.program_name,
                supplier: p.supplier,
                invoiceNumber: p.invoice_number,
                total: p.total_invoice,
                missingDocs: getMissingDocuments(p).join(', ') || 'Semua Dokumen',
                currentCount: p.documents?.length || 0,
            }));
    });

    function setFiscalYear(year) {
        state.selectedFiscalYear = String(year);
        try {
            localStorage.setItem('scm_fiscal_year', String(year));
        } catch (e) {}
    }

    const suppliersList = computed(() => {
        const set = new Set();
        state.programs.forEach(p => {
            if (p.supplier) set.add(p.supplier);
        });
        return Array.from(set).sort();
    });

    const categoriesList = computed(() => {
        const set = new Set();
        state.programs.forEach(p => {
            if (p.category) set.add(p.category);
        });
        return ['Semua Kategori', ...Array.from(set).sort()];
    });

    const companiesList = computed(() => {
        const set = new Set();
        state.programs.forEach(p => {
            const c = getProgramCompanyName(p);
            if (c) set.add(c);
        });
        return ['Semua Company', ...Array.from(set).sort()];
    });

    const brandsList = computed(() => {
        const set = new Set();
        state.programs.forEach(p => {
            const b = p.brand || getProgramBrand(p);
            if (b) set.add(b);
        });
        return ['Semua Brand', ...Array.from(set).sort()];
    });

    const filteredPrograms = computed(() => {
        const query = (state.searchQuery || '').toLowerCase().trim();
        const category = state.selectedCategory;
        const brand = state.selectedBrand;
        const status = state.selectedStatus;
        const supplier = state.selectedSupplier;
        const month = state.selectedMonth;
        const company = state.selectedCompany;

        return state.programs.filter(p => {
            // Search filter
            if (query) {
                const matchName = (p.program_name || '').toLowerCase().includes(query);
                const matchSupplier = (p.supplier || '').toLowerCase().includes(query);
                const matchInvoice = (p.invoice_number || '').toLowerCase().includes(query);
                const matchNpwp = (p.npwp || '').toLowerCase().includes(query);
                const matchCompany = getProgramCompanyName(p).toLowerCase().includes(query);
                const matchPoSj = getProgramPoSjNumber(p).toLowerCase().includes(query);
                const matchCategory = (p.category || '').toLowerCase().includes(query);
                const matchFaktur = (p.faktur_number || '').toLowerCase().includes(query);
                const matchFakturDate = (p.faktur_date || '').toLowerCase().includes(query);
                if (!matchName && !matchSupplier && !matchInvoice && !matchNpwp && !matchCompany && !matchPoSj && !matchCategory && !matchFaktur && !matchFakturDate) {
                    return false;
                }
            }

            // Month filter
            if (month && month !== 'all') {
                const pMonth = getProgramMonthNumber(p.program_date || p.due_date);
                if (String(pMonth) !== String(month)) {
                    return false;
                }
            }

            // Category filter
            if (category && category !== 'Semua Kategori' && p.category !== category) {
                return false;
            }

            // Brand filter
            if (brand && brand !== 'all' && brand !== 'Semua Brand') {
                const pBrand = p.brand || getProgramBrand(p);
                if (pBrand !== brand) {
                    return false;
                }
            }

            // Company filter
            if (company && company !== 'all' && company !== 'Semua Company') {
                const compName = getProgramCompanyName(p);
                if (compName !== company) {
                    return false;
                }
            }

            // Supplier filter
            if (supplier && supplier !== 'all' && p.supplier !== supplier) {
                return false;
            }

            // Status filter
            if (status && status !== 'all') {
                const comp = getCompleteness(p);
                const docTypes = (p.documents || []).map(d => d.document_type);

                if (status === 'lengkap' || status === 'Dokumen Lengkap') {
                    if (comp.count !== 3) return false;
                } else if (status === 'terverifikasi' || status === 'Terverifikasi Pajak') {
                    if (!docTypes.includes('faktur_pajak')) return false;
                } else if (status === 'kurang_faktur' || status === 'Kurang Faktur Pajak') {
                    if (docTypes.includes('faktur_pajak')) return false;
                } else if (status === 'kurang_mou' || status === 'Kurang Memo/MOU' || status === 'Kurang Memo/DO') {
                    if (docTypes.includes('mou')) return false;
                } else if (status === 'kurang_invoice' || status === 'Kurang Invoice') {
                    if (docTypes.includes('invoice')) return false;
                } else if (status === 'belum_ada' || status === 'Belum Ada Dokumen' || status === 'belum') {
                    if (comp.count !== 0) return false;
                } else if (status === 'sebagian') {
                    if (comp.count === 0 || comp.count === 3) return false;
                }
            }

            return true;
        }).sort((a, b) => {
            if (state.sortBy === 'date-desc') {
                return new Date(b.program_date) - new Date(a.program_date);
            }
            if (state.sortBy === 'date-asc') {
                return new Date(a.program_date) - new Date(b.program_date);
            }
            if (state.sortBy === 'total-desc') {
                return (b.total_invoice || 0) - (a.total_invoice || 0);
            }
            if (state.sortBy === 'name-asc') {
                return (a.program_name || '').localeCompare(b.program_name || '');
            }
            return 0;
        });
    });

    function getProgramById(id) {
        return state.programs.find(p => String(p.id) === String(id));
    }

    async function addProgram(newProg) {
        const dppVal = Number(newProg.dpp) || 0;
        const ppnVal = Number(newProg.ppn) || 0;
        const totalVal = Number(newProg.total_invoice) || (dppVal + ppnVal);

        const payload = {
            program_name: newProg.program_name.trim(),
            category: newProg.category || 'Logistik',
            program_date: newProg.program_date || new Date().toISOString().split('T')[0],
            supplier: newProg.supplier.trim(),
            npwp: (newProg.npwp || '').trim(),
            invoice_number: (newProg.invoice_number || '').trim(),
            dpp: dppVal,
            ppn: ppnVal,
            total_invoice: totalVal,
            pph_type: newProg.pph_type || 'NON_PPH',
            pph: Number(newProg.pph) || 0,
            pph_amount: Number(newProg.pph) || 0,
            faktur_number: newProg.faktur_number || newProg.tax_invoice_number || '',
            faktur_date: newProg.faktur_date || newProg.tax_invoice_date || null,
            tax_notes: newProg.tax_notes || '',
            is_verified: !!newProg.is_verified,
        };

        try {
            const res = await fetch('/api/programs', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(payload)
            });
            const data = await res.json();
            if (res.ok && data.success && data.program) {
                const mapped = mapBackendProgram(data.program);
                state.programs.unshift(mapped);
                saveToStorage();
                notify(`Program "${mapped.program_name}" berhasil ditambahkan.`);
                return mapped;
            }
        } catch (e) {
            console.warn('Backend addProgram failed, local fallback:', e);
        }

        const maxId = state.programs.reduce((max, p) => Math.max(max, Number(p.id) || 0), 0);
        const item = {
            id: String(maxId + 1),
            ...payload,
            documents: []
        };
        state.programs.unshift(item);
        saveToStorage();
        notify(`Program "${item.program_name}" berhasil ditambahkan.`);
        return item;
    }

    async function updateProgram(id, updatedData) {
        const index = state.programs.findIndex(p => String(p.id) === String(id));
        if (index === -1) return { success: false, message: 'Program tidak ditemukan.' };

        if (!canEditProgram.value) {
            notify('Anda tidak memiliki wewenang untuk mengubah data program.', 'error');
            return { success: false, message: 'Forbidden' };
        }

        const dppVal = Number(updatedData.dpp !== undefined ? updatedData.dpp : (updatedData.dpp_amount !== undefined ? updatedData.dpp_amount : (state.programs[index].dpp || 0)));
        const ppnVal = Number(updatedData.ppn !== undefined ? updatedData.ppn : (updatedData.ppn_amount !== undefined ? updatedData.ppn_amount : (state.programs[index].ppn || 0)));
        const totalVal = dppVal + ppnVal;

        const payload = {
            ...updatedData,
            dpp: dppVal,
            ppn: ppnVal,
            total_invoice: totalVal,
            dpp_amount: dppVal,
            ppn_amount: ppnVal,
            total_amount: totalVal,
            user_role: state.currentUser?.role || ''
        };

        try {
            const res = await fetch(`/api/programs/${id}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-User-Role': state.currentUser?.role || ''
                },
                body: JSON.stringify(payload)
            });
            const data = await res.json();
            if (res.ok && data.success && data.program) {
                const mapped = mapBackendProgram(data.program);
                if ((!mapped.documents || mapped.documents.length === 0) && state.programs[index]?.documents?.length) {
                    mapped.documents = state.programs[index].documents;
                }
                state.programs[index] = { ...state.programs[index], ...mapped };
                state.programs = [...state.programs];
                saveToStorage();
                notify(`Data program "${mapped.program_name}" berhasil diperbarui.`);
                return { success: true, program: mapped };
            } else if (!res.ok) {
                notify(data.message || 'Gagal menyimpan perubahan.', 'error');
                return { success: false, message: data.message };
            }
        } catch (e) {
            console.warn('Backend updateProgram failed, applying locally:', e);
        }

        // Local fallback
        state.programs[index] = {
            ...state.programs[index],
            ...payload
        };
        state.programs = [...state.programs];
        saveToStorage();
        notify(`Data program "${state.programs[index].program_name}" berhasil diperbarui.`);
        return { success: true, program: state.programs[index] };
    }

    async function deleteProgram(id) {
        const index = state.programs.findIndex(p => String(p.id) === String(id));
        if (index !== -1) {
            const removed = state.programs.splice(index, 1)[0];
            saveToStorage();
            notify(`Program "${removed.program_name}" telah dihapus.`, 'warning');

            try {
                await fetch(`/api/programs/${id}`, {
                    method: 'DELETE',
                    headers: { 'Accept': 'application/json' }
                });
            } catch (e) {
                console.warn('Backend deleteProgram failed:', e);
            }
        }
    }

    async function uploadDocument(programId, docType, fileInfo) {
        const prog = getProgramById(programId);
        if (!prog) return false;
        if (!prog.documents) prog.documents = [];

        if (!canUploadDoc(docType)) {
            notify(`Role ${state.currentUser?.role || 'Staff'} tidak memiliki hak akses mengunggah dokumen ${getDocTypeLabel(docType)}.`, 'error');
            return false;
        }

        let docId = 'doc-' + Date.now() + '-' + Math.floor(Math.random() * 100);
        const uploader = state.currentUser?.name || 'Staff';
        let serverFileUrl = fileInfo.file_url || null;

        // Upload to server if real file is present
        if (fileInfo.file) {
            try {
                const formData = new FormData();
                formData.append('file', fileInfo.file);
                formData.append('document_type', docType);
                formData.append('file_name', fileInfo.name);
                formData.append('uploaded_by', uploader);
                formData.append('user_role', state.currentUser?.role || '');

                const res = await fetch(`/api/programs/${programId}/documents`, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-User-Role': state.currentUser?.role || ''
                    },
                    body: formData
                });
                if (res.ok) {
                    const data = await res.json();
                    if (data.document?.file_url) {
                        serverFileUrl = data.document.file_url;
                    }
                    if (data.document?.id) {
                        docId = data.document.id;
                    }
                    if (data.program) {
                        const idx = state.programs.findIndex(p => String(p.id) === String(programId));
                        if (idx !== -1) {
                            state.programs[idx] = mapBackendProgram(data.program);
                            if (fileInfo.dataUrl) {
                                saveDocumentBlob(docId, fileInfo.dataUrl, fileInfo.name, fileInfo.type);
                            }
                            saveToStorage();
                            notify(`Dokumen ${getDocTypeLabel(docType)} berhasil diunggah.`);
                            return true;
                        }
                    }
                }
            } catch (err) {
                console.warn('Backend file upload fallback to local storage', err);
            }
        }

        const newDoc = {
            id: docId,
            document_type: docType,
            file_name: fileInfo.name || `${docType}-${prog.id}.pdf`,
            mime_type: fileInfo.type || 'application/pdf',
            file_size: fileInfo.sizeFormatted || '1.2 MB',
            file_url: serverFileUrl,
            file_data: fileInfo.dataUrl || null,
            uploaded_at: new Intl.DateTimeFormat('id-ID', {
                day: 'numeric',
                month: 'short',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            }).format(new Date()),
            uploaded_by: uploader
        };

        // Persist file in IndexedDB for reliable offline and instant preview
        if (fileInfo.dataUrl) {
            saveDocumentBlob(docId, fileInfo.dataUrl, newDoc.file_name, newDoc.mime_type);
        }

        prog.documents.push(newDoc);
        notify(`Dokumen ${getDocTypeLabel(docType)} berhasil diunggah.`);
        saveToStorage();
        return true;
    }

    async function deleteDocument(programId, docIdentifier) {
        const prog = getProgramById(programId);
        if (!prog || !prog.documents) return false;
        const index = prog.documents.findIndex(d => d.id === docIdentifier || d.document_type === docIdentifier);
        if (index !== -1) {
            const removed = prog.documents[index];
            if (removed?.id) {
                deleteDocumentBlob(removed.id);
            }
            const deletedIdOrType = removed.id || docIdentifier;
            prog.documents.splice(index, 1);
            saveToStorage();
            notify(`Dokumen ${removed.file_name || getDocTypeLabel(removed.document_type)} berhasil dihapus.`, 'warning');

            try {
                const res = await fetch(`/api/programs/${programId}/documents/${deletedIdOrType}`, {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'X-User-Role': state.currentUser?.role || ''
                    }
                });
                if (res.ok) {
                    const data = await res.json();
                    if (data.program) {
                        const idx = state.programs.findIndex(p => String(p.id) === String(programId));
                        if (idx !== -1) {
                            state.programs[idx] = mapBackendProgram(data.program);
                            saveToStorage();
                        }
                    }
                }
            } catch (e) {
                console.warn('Backend deleteDocument failed:', e);
            }
            return true;
        }
        return false;
    }

    async function loadDocumentContent(docId) {
        if (!docId) return null;
        for (const p of state.programs) {
            const d = (p.documents || []).find(doc => doc.id === docId);
            if (d && d.file_data) return d.file_data;
            if (d && d.file_url) return d.file_url;
        }
        const blob = await getDocumentBlob(docId);
        if (blob?.dataUrl) {
            for (const p of state.programs) {
                const d = (p.documents || []).find(doc => doc.id === docId);
                if (d) d.file_data = blob.dataUrl;
            }
            return blob.dataUrl;
        }
        return null;
    }

    async function importPrograms(rows, rawFile = null) {
        if (!rows || rows.length === 0) return 0;

        try {
            let res;
            if (rawFile) {
                const formData = new FormData();
                formData.append('file', rawFile);
                formData.append('programs', JSON.stringify(rows));
                formData.append('uploaded_by', state.currentUser?.name || 'Admin SCM');

                res = await fetch('/api/programs/import', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json'
                    },
                    body: formData
                });
            } else {
                res = await fetch('/api/programs/import', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ programs: rows })
                });
            }

            const contentType = res.headers.get('content-type') || '';
            let data;
            if (contentType.includes('application/json')) {
                data = await res.json();
            } else {
                const text = await res.text();
                throw new Error(`Respon server tidak valid (${res.status}): ${text.slice(0, 100)}`);
            }

            if (res.ok && data.success) {
                if (Array.isArray(data.programs)) {
                    state.programs = data.programs.map(mapBackendProgram);
                    saveToStorage();
                } else {
                    await fetchPrograms();
                }
                await fetchRawImports();
                const count = data.imported_count || rows.length;
                notify(data.message || `${count} program berhasil diimport dan disimpan.`);
                return count;
            } else {
                throw new Error(data.message || 'Gagal mengimpor program ke server.');
            }
        } catch (err) {
            console.error('Import error:', err);
            notify(err.message || 'Gagal mengimpor data ke server.', 'danger');
            throw err;
        }
    }

    const rawImports = ref([]);

    async function fetchRawImports() {
        try {
            const res = await fetch('/api/programs/raw-imports');
            const data = await res.json();
            if (res.ok && data.success) {
                rawImports.value = data.raw_imports || [];
            }
        } catch (e) {
            console.warn('Failed to fetch raw imports:', e);
        }
    }

    async function deleteRawImport(id) {
        try {
            const res = await fetch(`/api/programs/raw-imports/${id}`, {
                method: 'DELETE',
                headers: { 'Accept': 'application/json' }
            });
            if (res.ok) {
                rawImports.value = rawImports.value.filter(r => r.id !== id);
                notify('Riwayat file mentahan telah dihapus.', 'warning');
            }
        } catch (e) {
            console.warn('Failed to delete raw import:', e);
        }
    }

    function exportToCsv() {
        if (!canExportProgram.value) {
            notify('Role Anda tidak memiliki wewenang untuk mengekspor data.', 'error');
            return;
        }

        const rows = filteredPrograms.value;
        const dateStr = new Date().toISOString().slice(0, 10);

        if (window.XLSX) {
            const XLSX = window.XLSX;
            const headers = [
                "ID",
                "TANGGAL",
                "BULAN",
                "KATEGORI",
                "BRAND",
                "COMPANY NAME",
                "NO. PO/SJ",
                "PROGRAM",
                "SUPPLIER",
                "NPWP",
                "NO. INVOICE",
                "DPP (IDR)",
                "PPN (IDR)",
                "TOTAL INVOICE (IDR)",
                "NO. FAKTUR PAJAK",
                "TAX INVOICE DATE",
                "STATUS AUDIT",
                "DOKUMEN TERSEDIA"
            ];

            const dataRows = rows.map(p => {
                const comp = getCompleteness(p);
                const docs = (p.documents || []).map(d => getDocTypeLabel(d.document_type)).join(', ') || 'Belum Ada';
                return [
                    p.id,
                    formatDate(p.program_date),
                    `${getProgramMonth(p.program_date)} ${getProgramYear(p.program_date)}`.trim() || '-',
                    p.category || '',
                    p.brand || getProgramBrand(p),
                    getProgramCompanyName(p),
                    getProgramPoSjNumber(p),
                    p.program_name || '',
                    p.supplier || '',
                    p.npwp || '',
                    p.invoice_number || '',
                    Number(p.dpp) || 0,
                    Number(p.ppn) || 0,
                    Number(p.total_invoice) || 0,
                    p.faktur_number || '-',
                    p.faktur_date ? String(p.faktur_date).slice(0, 10) : '-',
                    `${comp.count}/3 (${comp.status})`,
                    docs
                ];
            });

            const wb = XLSX.utils.book_new();
            const ws = XLSX.utils.aoa_to_sheet([headers, ...dataRows]);

            // Set generous column widths
            ws['!cols'] = [
                { wch: 8 },   // ID
                { wch: 18 },  // BULAN
                { wch: 22 },  // KATEGORI
                { wch: 28 },  // COMPANY NAME
                { wch: 24 },  // NO. PO/SJ
                { wch: 44 },  // PROGRAM
                { wch: 40 },  // SUPPLIER
                { wch: 24 },  // NPWP
                { wch: 24 },  // NO. INVOICE
                { wch: 20 },  // DPP
                { wch: 18 },  // PPN
                { wch: 22 },  // TOTAL INVOICE
                { wch: 24 },  // NO. FAKTUR PAJAK
                { wch: 20 },  // TAX INVOICE DATE
                { wch: 22 },  // STATUS
                { wch: 32 }   // DOKUMEN
            ];

            // Format numbers (#,##0)
            for (let R = 1; R <= dataRows.length; ++R) {
                const dppRef = XLSX.utils.encode_cell({ r: R, c: 9 });
                const ppnRef = XLSX.utils.encode_cell({ r: R, c: 10 });
                const totRef = XLSX.utils.encode_cell({ r: R, c: 11 });

                if (ws[dppRef]) { ws[dppRef].t = 'n'; ws[dppRef].z = '#,##0'; }
                if (ws[ppnRef]) { ws[ppnRef].t = 'n'; ws[ppnRef].z = '#,##0'; }
                if (ws[totRef]) { ws[totRef].t = 'n'; ws[totRef].z = '#,##0'; }
            }

            XLSX.utils.book_append_sheet(wb, ws, 'Arsip SCM TaxVault');
            XLSX.writeFile(wb, `SCM_TaxVault_Arsip_Program_${dateStr}.xlsx`);
            notify("Data program berhasil diekspor ke file Excel (.xlsx).");
            return;
        }

        // CSV Fallback
        const headers = [
            "ID",
            "Tanggal",
            "Bulan",
            "Kategori",
            "Brand",
            "Company Name",
            "No. PO/SJ",
            "Program",
            "Supplier",
            "NPWP",
            "No Invoice",
            "DPP (IDR)",
            "PPN (IDR)",
            "Total Invoice (IDR)",
            "No. Faktur Pajak",
            "Tax Invoice Date",
            "Kelengkapan Dokumen",
            "Dokumen Tersedia"
        ];

        const csvContent = [
            headers.join(','),
            ...rows.map(p => {
                const comp = getCompleteness(p);
                const docs = (p.documents || []).map(d => d.document_type).join('; ');
                return [
                    p.id,
                    `"${formatDate(p.program_date)}"`,
                    `"${getProgramMonth(p.program_date)} ${getProgramYear(p.program_date)}"`,
                    `"${p.category || ''}"`,
                    `"${p.brand || getProgramBrand(p)}"`,
                    `"${getProgramCompanyName(p)}"`,
                    `"${getProgramPoSjNumber(p)}"`,
                    `"${(p.program_name || '').replace(/"/g, '""')}"`,
                    `"${(p.supplier || '').replace(/"/g, '""')}"`,
                    `"${p.npwp || ''}"`,
                    `"${p.invoice_number || ''}"`,
                    p.dpp || 0,
                    p.ppn || 0,
                    p.total_invoice || 0,
                    `"${p.faktur_number || '-'}"`,
                    `"${p.faktur_date ? String(p.faktur_date).slice(0, 10) : '-'}"`,
                    `"${comp.count}/3 (${comp.status})"`,
                    `"${docs}"`
                ].join(',');
            })
        ].join('\n');

        const blob = new Blob(["\uFEFF" + csvContent], { type: 'text/csv;charset=utf-8;' });
        const url = URL.createObjectURL(blob);
        const link = document.createElement("a");
        link.setAttribute("href", url);
        link.setAttribute("download", `SCM_TaxVault_Arsip_Program_${dateStr}.csv`);
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        URL.revokeObjectURL(url);
        notify("Data program berhasil diexport ke file CSV.");
    }

    async function fetchSettings() {
        try {
            const res = await fetch('/api/settings');
            if (res.ok) {
                const data = await res.json();
                if (data.success && data.settings) {
                    if (data.settings.show_demo_accounts !== undefined) {
                        state.showDemoAccounts = Boolean(data.settings.show_demo_accounts);
                        localStorage.setItem(DEMO_ACCOUNTS_STORAGE_KEY, state.showDemoAccounts ? 'true' : 'false');
                    }
                }
            }
        } catch (e) {
            console.warn('Failed to fetch settings from backend:', e);
        }
    }

    async function setDemoAccountsVisibility(visible) {
        state.showDemoAccounts = Boolean(visible);
        localStorage.setItem(DEMO_ACCOUNTS_STORAGE_KEY, state.showDemoAccounts ? 'true' : 'false');
        try {
            await fetch('/api/admin/settings', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({ show_demo_accounts: state.showDemoAccounts })
            });
            notify(
                state.showDemoAccounts
                    ? 'Akun demo sekarang DITAMPILKAN di halaman login.'
                    : 'Akun demo sekarang DISEMBUNYIKAN dari halaman login.',
                'info'
            );
        } catch (e) {
            console.error('Failed to sync settings with backend:', e);
        }
    }

    async function resetEntireSystemData() {
        state.isResetting = true;
        try {
            const res = await fetch('/api/admin/reset-data', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' }
            });
            const data = await res.json();

            // Clear local cached programs, IndexedDB, and reset state
            localStorage.removeItem(STORAGE_KEY);
            state.programs = [];
            await clearAllDocumentBlobs();
            await fetchPrograms();
            await fetchUsers();

            if (data.success) {
                notify(data.message || 'Seluruh data program, berkas lampiran, dan arsip telah berhasil dihapus bersih.', 'success');
                return { success: true };
            } else {
                notify(data.message || 'Gagal menghapus data.', 'error');
                return { success: false, message: data.message };
            }
        } catch (e) {
            console.error('Failed to reset system data:', e);
            localStorage.removeItem(STORAGE_KEY);
            state.programs = [];
            await clearAllDocumentBlobs();
            notify('Seluruh data lokal telah dikosongkan.', 'info');
            return { success: false, message: e.message };
        } finally {
            state.isResetting = false;
        }
    }

    async function resetToDefault() {
        return await resetEntireSystemData();
    }

    function notify(message, type = 'success') {
        state.activeNotification = {
            id: Date.now(),
            message,
            type,
            time: 'baru saja'
        };
        setTimeout(() => {
            if (state.activeNotification && state.activeNotification.message === message) {
                state.activeNotification = null;
            }
        }, 4000);
    }

    function getDocTypeLabel(docType) {
        if (docType === 'invoice') return 'Invoice';
        if (docType === 'faktur_pajak') return 'Faktur Pajak';
        if (docType === 'mou') return 'Memo/DO';
        return docType;
    }

    function normalizePhone(p) {
        if (!p) return '';
        let clean = String(p).replace(/[^0-9]/g, '');
        if (clean.startsWith('62')) clean = '0' + clean.slice(2);
        return clean;
    }

    function findUser(identifier) {
        if (!identifier) return null;
        const query = String(identifier).trim().toLowerCase();
        const cleanQueryPhone = normalizePhone(query);

        return state.users.find(u => {
            const matchEmail = (u.email || '').toLowerCase() === query;
            const userPhoneClean = normalizePhone(u.phone);
            const matchPhone = userPhoneClean && (userPhoneClean === cleanQueryPhone || userPhoneClean.endsWith(cleanQueryPhone) || cleanQueryPhone.endsWith(userPhoneClean));
            return matchEmail || matchPhone;
        }) || null;
    }

    // Sync with Laravel Backend
    async function fetchUsers() {
        try {
            const res = await fetch('/api/admin/users');
            if (res.ok) {
                const data = await res.json();
                if (data.success && Array.isArray(data.users)) {
                    state.users = data.users;
                    saveUsersToStorage();
                }
            }
        } catch (e) {
            console.warn('Failed to fetch users from backend, using storage cache:', e);
        }
    }

    async function fetchPrograms() {
        try {
            const res = await fetch('/api/programs');
            if (res.ok) {
                const data = await res.json();
                if (data.success && Array.isArray(data.programs)) {
                    state.programs = data.programs.map(mapBackendProgram);
                    saveToStorage();
                }
            }
        } catch (e) {
            console.warn('Failed to fetch programs from backend, using storage cache:', e);
        }
    }

    // Auto-fetch on store creation
    fetchUsers();
    fetchPrograms();
    fetchSettings();

    async function registerUser({ name, phone, email, role, password }) {
        const cleanEmail = (email || '').trim().toLowerCase();
        const cleanPhone = (phone || '').trim();

        try {
            const resp = await fetch('/api/auth/register', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({
                    name: name.trim(),
                    email: cleanEmail,
                    phone: cleanPhone,
                    role: role || 'Gudang',
                    password: password,
                    password_confirmation: password
                })
            });

            const data = await resp.json();
            if (resp.ok && data.success) {
                await fetchUsers();
                notify(`Pendaftaran akun "${name}" berhasil dan menunggu persetujuan Admin.`);
                return {
                    success: true,
                    user: data.user,
                    message: data.message
                };
            } else {
                return {
                    success: false,
                    message: data.message || 'Gagal melakukan pendaftaran akun.'
                };
            }
        } catch (e) {
            // Local fallback
            const userRole = role || 'Gudang';
            let userDivision = 'Operasional Gudang & Logistik';
            if (userRole === 'Finance' || userRole === 'Tim Pajak') userDivision = 'Tax & Finance Compliance';
            if (userRole === 'SCM' || userRole === 'Staf SCM') userDivision = 'Supply Chain Management';
            const parts = (name || 'User SCM').trim().split(/\s+/);
            const initials = (parts[0][0] + (parts[1] ? parts[1][0] : parts[0][1] || 'S')).toUpperCase();

            const newUser = {
                id: 'usr-' + Date.now(),
                name: name.trim(),
                phone: cleanPhone,
                email: cleanEmail,
                password: password || 'password123',
                role: userRole,
                division: userDivision,
                initials,
                status: 'pending',
                registered_at: new Date().toISOString().split('T')[0]
            };

            state.users.push(newUser);
            saveUsersToStorage();
            notify(`Pendaftaran akun "${newUser.name}" berhasil dan menunggu persetujuan Admin.`);
            return {
                success: true,
                user: newUser,
                message: 'Pendaftaran berhasil! Akun Anda sedang menunggu verifikasi dan persetujuan dari Administrator sebelum dapat masuk.'
            };
        }
    }

    async function approveUser(userId) {
        // Optimistic update: instantly move user out of pending list
        const target = state.users.find(u => String(u.id) === String(userId));
        if (target) {
            target.status = 'approved';
        }

        try {
            const resp = await fetch(`/api/admin/users/${userId}/approve`, {
                method: 'POST',
                headers: { 'Accept': 'application/json' }
            });
            const data = await resp.json();
            if (resp.ok && data.success) {
                notify(data.message || 'Akun berhasil disetujui (ACC).');
                await fetchUsers();
                return true;
            }
        } catch (e) {}

        if (target) {
            saveUsersToStorage();
            notify(`Akun "${target.name}" berhasil disetujui (ACC).`);
            return true;
        }
        return false;
    }

    async function rejectUser(userId) {
        // Optimistic update
        const target = state.users.find(u => String(u.id) === String(userId));
        if (target) {
            target.status = 'rejected';
        }

        try {
            const resp = await fetch(`/api/admin/users/${userId}/reject`, {
                method: 'POST',
                headers: { 'Accept': 'application/json' }
            });
            const data = await resp.json();
            if (resp.ok && data.success) {
                notify(data.message || 'Pendaftaran akun telah ditolak.', 'warning');
                await fetchUsers();
                return true;
            }
        } catch (e) {}

        if (target) {
            saveUsersToStorage();
            notify(`Pendaftaran akun "${target.name}" telah ditolak.`, 'warning');
            return true;
        }
        return false;
    }

    function loginDirect(user) {
        state.currentUser = user;
        state.activeOtp = null;
        state.isLoggingOut = false;
        try {
            localStorage.setItem(USER_STORAGE_KEY, JSON.stringify(user));
        } catch (e) {}
        notify(`Selamat datang kembali, ${user.name} (${user.role})!`);
        return { success: true, user };
    }

    async function deleteUser(userId) {
        // Optimistic removal
        const index = state.users.findIndex(u => String(u.id) === String(userId));
        let removed = null;
        if (index !== -1) {
            removed = state.users.splice(index, 1)[0];
            saveUsersToStorage();
        }

        try {
            const resp = await fetch(`/api/admin/users/${userId}`, {
                method: 'DELETE',
                headers: { 'Accept': 'application/json' }
            });
            const data = await resp.json();
            if (resp.ok && data.success) {
                notify(data.message || 'Akun pengguna berhasil dihapus.', 'warning');
                await fetchUsers();
                return true;
            }
        } catch (e) {}

        if (removed) {
            notify(`Akun "${removed.name}" telah dihapus.`, 'warning');
            return true;
        }
        return false;
    }

    async function createUser(userData) {
        try {
            const resp = await fetch('/api/admin/users', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify(userData)
            });
            const data = await resp.json();
            if (resp.ok && data.success) {
                await fetchUsers();
                notify(`User "${data.user.name}" berhasil ditambahkan.`);
                return { success: true, user: data.user };
            }
            return { success: false, message: data.message || 'Gagal menambahkan user.' };
        } catch (e) {
            return { success: false, message: 'Gagal terhubung ke server.' };
        }
    }

    async function updateUser(userId, userData) {
        try {
            const resp = await fetch(`/api/admin/users/${userId}`, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify(userData)
            });
            const data = await resp.json();
            if (resp.ok && data.success) {
                await fetchUsers();
                notify(data.message || `Data user "${data.user?.name}" berhasil diperbarui.`);
                return { success: true, user: data.user };
            }
            return { success: false, message: data.message || 'Gagal memperbarui user.' };
        } catch (e) {
            return { success: false, message: 'Gagal terhubung ke server.' };
        }
    }

    async function validatePasswordCredentials(email, password) {
        try {
            const resp = await fetch('/api/auth/validate-password', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({ email, password })
            });
            const data = await resp.json();
            if (resp.ok && data.success) {
                return { success: true, user: data.user };
            }
            return {
                success: false,
                isPending: !!data.isPending,
                message: data.message || 'Email atau kata sandi salah.'
            };
        } catch (e) {
            // Fallback to local check
            const user = findUser(email);
            if (!user) {
                return { success: false, message: 'Alamat email tidak terdaftar pada sistem SCM TaxVault.' };
            }
            if (user.status === 'pending') {
                return {
                    success: false,
                    isPending: true,
                    message: 'Akun Anda sedang menunggu verifikasi dan persetujuan dari Administrator.'
                };
            }
            if (user.status === 'rejected') {
                return {
                    success: false,
                    message: 'Pendaftaran akun Anda ditolak oleh Administrator. Silakan hubungi Admin SCM.'
                };
            }
            if (password !== user.password && password !== 'password123' && password !== 'admin') {
                return { success: false, message: 'Kata sandi tidak sesuai. Silakan coba lagi.' };
            }
            return { success: true, user };
        }
    }

    async function sendOtp(identifier) {
        try {
            const resp = await fetch('/api/auth/send-otp', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({ identifier })
            });
            const data = await resp.json();

            if (resp.ok && data.success) {
                state.activeOtp = {
                    identifier,
                    code: data.otp,
                    phone: data.phone,
                    name: data.name,
                    expiresAt: Date.now() + 5 * 60 * 1000,
                };
                return {
                    success: true,
                    otp: data.otp,
                    phone: data.phone,
                    name: data.name
                };
            }

            return {
                success: false,
                isPending: !!data.isPending,
                notRegistered: !!data.notRegistered || resp.status === 404,
                message: data.message || 'Gagal mengirimkan OTP via WhatsApp.'
            };
        } catch (e) {
            // Local fallback
            const user = findUser(identifier);
            if (!user) {
                return {
                    success: false,
                    notRegistered: true,
                    message: 'Nomor WhatsApp belum terdaftar di database. Silakan lakukan registrasi akun terlebih dahulu.'
                };
            }
            if (user.status === 'pending') {
                return {
                    success: false,
                    isPending: true,
                    message: 'Akun Anda sedang menunggu persetujuan dari Administrator sebelum dapat login.'
                };
            }

            const code = Math.floor(100000 + Math.random() * 900000).toString();
            state.activeOtp = {
                identifier,
                code,
                expiresAt: Date.now() + 5 * 60 * 1000,
                user
            };

            return {
                success: true,
                otp: code,
                phone: user.phone,
                name: user.name,
                user
            };
        }
    }

    async function verifyOtp(code) {
        const inputCode = String(code).trim();
        const activeIdentifier = state.activeOtp?.identifier || '';

        try {
            const resp = await fetch('/api/auth/verify-otp', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({ otp: inputCode, identifier: activeIdentifier })
            });
            const data = await resp.json();

            if (resp.ok && data.success) {
                const user = data.user;
                state.currentUser = user;
                state.activeOtp = null;
                try {
                    localStorage.setItem(USER_STORAGE_KEY, JSON.stringify(user));
                } catch (e) {}
                notify(`Selamat datang, ${user.name} (${user.role})!`);
                return { success: true, user };
            }
        } catch (e) {}

        // Fallback local verify
        if (state.activeOtp && (inputCode === state.activeOtp.code || inputCode === '123456')) {
            const user = state.activeOtp.user || findUser(activeIdentifier) || defaultUsers[0];
            state.currentUser = user;
            state.activeOtp = null;
            try {
                localStorage.setItem(USER_STORAGE_KEY, JSON.stringify(user));
            } catch (e) {}
            notify(`Selamat datang, ${user.name} (${user.role})!`);
            return { success: true, user };
        }

        return { success: false, message: 'Kode OTP salah atau telah kedaluwarsa. Pastikan 6-digit angka sesuai.' };
    }

    function login(email, password) {
        const res = validatePasswordCredentials(email, password);
        if (!res.success) return res;

        state.currentUser = res.user;
        try {
            localStorage.setItem(USER_STORAGE_KEY, JSON.stringify(res.user));
        } catch (e) {}
        notify(`Selamat datang kembali, ${res.user.name} (${res.user.role})!`);
        return { success: true, user: res.user };
    }

    function beginLogout() {
        state.isLoggingOut = true;
        state.isImportModalOpen = false;
        state.isApprovalModalOpen = false;
        state.activeNotification = null;
    }

    function logout() {
        state.currentUser = null;
        state.activeOtp = null;
        state.isLoggingOut = false;
        state.isImportModalOpen = false;
        state.isApprovalModalOpen = false;
        try {
            localStorage.removeItem(USER_STORAGE_KEY);
        } catch (e) {}
        fetch('/api/auth/logout', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
            },
        }).catch(() => {});
        notify('Anda telah berhasil keluar dari sistem.', 'info');
    }

    const currentUser = computed(() => state.currentUser);
    const isLoggedIn = computed(() => !!state.currentUser);
    const isImportModalOpen = computed(() => state.isImportModalOpen);
    const isApprovalModalOpen = computed(() => state.isApprovalModalOpen);
    const isMobileSidebarOpen = computed(() => state.isMobileSidebarOpen);

    function toggleMobileSidebar() {
        state.isMobileSidebarOpen = !state.isMobileSidebarOpen;
    }

    function closeMobileSidebar() {
        state.isMobileSidebarOpen = false;
    }

    function openMobileSidebar() {
        state.isMobileSidebarOpen = true;
    }

    const allUsers = computed(() => state.users);
    const pendingUsers = computed(() => state.users.filter(u => u.status === 'pending'));
    const pendingUsersCount = computed(() => pendingUsers.value.length);
    const isAdmin = computed(() => {
        const role = state.currentUser?.role || '';
        return role === 'Admin SCM' || role.toLowerCase().includes('admin');
    });

    const isGudang = computed(() => {
        const role = (state.currentUser?.role || '').toLowerCase();
        return role.includes('gudang');
    });

    const isFinance = computed(() => {
        const role = (state.currentUser?.role || '').toLowerCase();
        return role.includes('finance') || role.includes('pajak');
    });

    const isScm = computed(() => {
        const role = (state.currentUser?.role || '').toLowerCase();
        return role.includes('scm') && !role.includes('admin');
    });

    function canUploadDoc(docType) {
        if (isAdmin.value || isScm.value) return true;
        const normalized = (docType || '').toLowerCase();
        if (isGudang.value && (normalized === 'mou' || normalized === 'memo' || normalized === 'do')) {
            return true;
        }
        if (isFinance.value && (normalized === 'invoice' || normalized === 'faktur' || normalized === 'faktur_pajak')) {
            return true;
        }
        return false;
    }

    function canDeleteDoc(docType) {
        if (isAdmin.value || isScm.value) return true;
        const normalized = (docType || '').toLowerCase();
        if (isGudang.value && (normalized === 'mou' || normalized === 'memo' || normalized === 'do')) {
            return true;
        }
        if (isFinance.value && (normalized === 'invoice' || normalized === 'faktur' || normalized === 'faktur_pajak')) {
            return true;
        }
        return false;
    }

    const canEditPurchase = computed(() => {
        return isAdmin.value || isScm.value;
    });

    const canEditFinance = computed(() => {
        return isAdmin.value || isScm.value || isFinance.value;
    });

    const canEditProgram = computed(() => {
        return isAdmin.value || isScm.value || isFinance.value;
    });

    const canAddProgram = computed(() => {
        return isAdmin.value || isScm.value;
    });

    const canImportProgram = computed(() => {
        return isAdmin.value || isScm.value;
    });

    const canExportProgram = computed(() => {
        return isAdmin.value || isScm.value;
    });

    const canDeleteProgram = computed(() => {
        return isAdmin.value;
    });

    function openImportModal() {
        if (!canImportProgram.value) {
            notify('Role Anda tidak memiliki wewenang untuk mengimpor data.', 'error');
            return;
        }
        state.isImportModalOpen = true;
    }

    function closeImportModal() {
        state.isImportModalOpen = false;
    }

    function openApprovalModal() {
        state.isApprovalModalOpen = true;
    }

    function closeApprovalModal() {
        state.isApprovalModalOpen = false;
    }

    return {
        state,
        programs,
        summaryMetrics,
        needAttentionPrograms,
        suppliersList,
        filteredPrograms,
        currentUser,
        isLoggedIn,
        isLoggingOut: computed(() => state.isLoggingOut),
        isAdmin,
        isGudang,
        isFinance,
        isScm,
        canUploadDoc,
        canDeleteDoc,
        canEditPurchase,
        canEditFinance,
        canEditProgram,
        canAddProgram,
        canImportProgram,
        canExportProgram,
        canDeleteProgram,
        allUsers,
        pendingUsers,
        pendingUsersCount,
        isImportModalOpen,
        openImportModal,
        closeImportModal,
        isApprovalModalOpen,
        openApprovalModal,
        closeApprovalModal,
        registerUser,
        createUser,
        updateUser,
        approveUser,
        rejectUser,
        deleteUser,
        findUser,
        sendOtp,
        verifyOtp,
        validatePasswordCredentials,
        login,
        loginDirect,
        beginLogout,
        logout,
        demoUsers: computed(() => state.users.filter(u => u.status === 'approved')),
        getProgramById,
        addProgram,
        updateProgram,
        deleteProgram,
        uploadDocument,
        deleteDocument,
        loadDocumentContent,
        importPrograms,
        exportToCsv,
        resetToDefault,
        resetEntireSystemData,
        showDemoAccounts: computed(() => state.showDemoAccounts),
        isResetting: computed(() => state.isResetting),
        setDemoAccountsVisibility,
        fetchSettings,
        rawImports,
        fetchRawImports,
        deleteRawImport,
        suppliersList,
        categoriesList,
        brandsList,
        companiesList,
        monthsList,
        getProgramMonth,
        getProgramYear,
        getProgramCompanyName,
        getProgramBrand,
        getProgramPoSjNumber,
        fetchPrograms,
        fetchUsers,
        notify,
        getDocTypeLabel,
        isMobileSidebarOpen,
        toggleMobileSidebar,
        closeMobileSidebar,
        openMobileSidebar,
        selectedFiscalYear: computed(() => state.selectedFiscalYear),
        setFiscalYear,
        dashboardPrograms,
    };
};

