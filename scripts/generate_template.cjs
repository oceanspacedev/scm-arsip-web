const fs = require('fs');
const path = require('path');
const XLSX = require('xlsx');

const sampleHeaders = [
  'BULAN',
  'KATEGORI',
  'COMPANY NAME',
  'NO. PO/SJ',
  'PROGRAM',
  'SUPPLIER',
  'NPWP',
  'NO. INVOICE',
  'DPP',
  'PPN',
  'TOTAL INVOICE'
];

const sampleRows = [
  [
    'Maret 2026',
    'Pipa & Tubing',
    'PT SCM Nusantara',
    'PO/2026/0101 / SJ-0101',
    'Pengadaan Komponen Pipa Gas Tuban',
    'PT Steel Pipe Industry of Indonesia Tbk',
    '01.345.678.9-012.000',
    'INV/2026/SCM/0101',
    45000000,
    4950000,
    49950000
  ],
  [
    'Maret 2026',
    'Sewa Alat Berat',
    'PT SCM Solusi Indonesia',
    'PO/2026/0102 / SJ-0102',
    'Penyewaan Heavy Crane Lepas Pantai 50 Ton',
    'PT Radiant Utama Interinsco Tbk',
    '02.887.123.4-041.000',
    'INV/2026/SCM/0102',
    120000000,
    13200000,
    133200000
  ],
  [
    'Maret 2026',
    'Inspeksi & Sertifikasi',
    'PT SCM Nusantara',
    'PO/2026/0103 / SJ-0103',
    'Jasa Inspeksi Tangki Kilang Balikpapan',
    'PT Sucofindo (Persero)',
    '01.000.456.7-051.000',
    'INV/2026/SCM/0103',
    75000000,
    8250000,
    83250000
  ],
  [
    'Maret 2026',
    'Mekanikal & Valve',
    'PT SCM Logistik Utama',
    'PO/2026/0104 / SJ-0104',
    'Pengadaan High Pressure Valve & Flange Class 600',
    'PT Kitz Valve Indonesia',
    '03.221.456.7-052.000',
    'INV/2026/SCM/0104',
    38500000,
    4235000,
    42735000
  ],
  [
    'Maret 2026',
    'Bahan Kimia',
    'PT Surya Citra Media Tbk',
    'PO/2026/0105 / SJ-0105',
    'Pengadaan Chemical Demulsifier Lapangan Minyak',
    'PT Clariant Indonesia',
    '01.992.834.1-015.000',
    'INV/2026/SCM/0105',
    92000000,
    10120000,
    102120000
  ],
  [
    'Februari 2026',
    'Logistik',
    'PT SCM Solusi Indonesia',
    'PO/2026/0201 / SJ-0201',
    'Pengadaan Armada Wingbox Pendingin Logistik',
    'PT Samudera Perkasa Abadi',
    '02.887.123.4-041.000',
    'INV/SPA/2026/0842',
    828828829,
    91171171,
    920000000
  ],
  [
    'Februari 2026',
    'IT & Software',
    'PT SCM Nusantara',
    'PO/2026/0202 / SJ-0202',
    'Integrasi WMS Automated Sorting Center Phase 2',
    'PT Global Solusi Informatika',
    '03.221.456.7-052.000',
    'INV/GSI/2026/0312',
    1126126126,
    123873874,
    1250000000
  ],
  [
    'Februari 2026',
    'Distribusi',
    'PT SCM Logistik Utama',
    'PO/2026/0203 / SJ-0203',
    'Distribusi Ritel Multi-Hub Jawa Bali',
    'PT Mitra Distribusi Utama',
    '01.992.834.1-015.000',
    'INV/MDU/2026/1109',
    648648649,
    71351351,
    720000000
  ],
  [
    'Februari 2026',
    'Material Handling',
    'PT Surya Citra Media Tbk',
    'PO/2026/0204 / SJ-0204',
    'Pengadaan Forklift Elektrik 3 Ton High-Mast',
    'PT Toyota Material Handling Indonesia',
    '01.442.981.2-064.000',
    'INV/TMH/2026/0488',
    495495495,
    54504505,
    550000000
  ],
  [
    'Januari 2026',
    'Warehouse',
    'PT SCM Nusantara',
    'PO/2026/0111 / SJ-0111',
    'Instalasi Selective Pallet Racking Gudang Cikarang',
    'PT Dexion Warehouse Systems',
    '02.551.789.0-033.000',
    'INV/DXN/2026/0142',
    315315315,
    34684685,
    350000000
  ],
  [
    'Januari 2026',
    'Packaging',
    'PT SCM Solusi Indonesia',
    'PO/2026/0112 / SJ-0112',
    'Pengadaan Kemasan Corrugated Box & Stretch Film',
    'PT Riau Sakti Packaging Industries',
    '01.884.223.5-021.000',
    'INV/RSP/2026/0991',
    162162162,
    17837838,
    180000000
  ],
  [
    'Januari 2026',
    'Promosi',
    'PT Surya Citra Media Tbk',
    'PO/2026/0113 / SJ-0113',
    'Pengadaan Booth Branding & Material Event SCM Expo',
    'PT Mahaka Visual Integrasi',
    '03.119.445.6-072.000',
    'INV/MVI/2026/0219',
    225225225,
    24774775,
    250000000
  ]
];

// 1. Build Excel Workbook
const wb = XLSX.utils.book_new();
const ws = XLSX.utils.aoa_to_sheet([sampleHeaders, ...sampleRows]);

// Column widths for easy reading in Excel
ws['!cols'] = [
  { wch: 15 }, // BULAN
  { wch: 22 }, // KATEGORI
  { wch: 26 }, // COMPANY NAME
  { wch: 24 }, // NO. PO/SJ
  { wch: 42 }, // PROGRAM
  { wch: 38 }, // SUPPLIER
  { wch: 24 }, // NPWP
  { wch: 22 }, // NO. INVOICE
  { wch: 18 }, // DPP
  { wch: 16 }, // PPN
  { wch: 18 }  // TOTAL INVOICE
];

// Format financial cells as integers with comma
for (let R = 1; R <= sampleRows.length; ++R) {
  const dppRef = XLSX.utils.encode_cell({ r: R, c: 8 });
  const ppnRef = XLSX.utils.encode_cell({ r: R, c: 9 });
  const totRef = XLSX.utils.encode_cell({ r: R, c: 10 });

  if (ws[dppRef]) { ws[dppRef].t = 'n'; ws[dppRef].z = '#,##0'; }
  if (ws[ppnRef]) { ws[ppnRef].t = 'n'; ws[ppnRef].z = '#,##0'; }
  if (ws[totRef]) { ws[totRef].t = 'n'; ws[totRef].z = '#,##0'; }
}

XLSX.utils.book_append_sheet(wb, ws, 'Template Program');

const publicDir = path.join(__dirname, '..', 'public', 'templates');
const xlsxPath = path.join(publicDir, 'Template_Import_Arsip_Program_SCM.xlsx');
const csvPath = path.join(publicDir, 'Template_Import_Arsip_Program_SCM.csv');

fs.mkdirSync(publicDir, { recursive: true });
XLSX.writeFile(wb, xlsxPath);
console.log('Generated Excel:', xlsxPath);

const csvLines = [
  sampleHeaders.join(','),
  ...sampleRows.map(row => {
    return row.map(cell => {
      if (typeof cell === 'string') {
        return `"${cell.replace(/"/g, '""')}"`;
      }
      return cell;
    }).join(',');
  })
];
fs.writeFileSync(csvPath, '\uFEFF' + csvLines.join('\r\n'), 'utf8');
console.log('Generated CSV:', csvPath);
