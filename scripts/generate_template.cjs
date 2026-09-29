const fs = require('fs');
const path = require('path');
const ExcelJS = require('exceljs');

const sampleHeaders = [
  'TANGGAL',
  'BULAN',
  'KATEGORI',
  'BRAND',
  'COMPANY NAME',
  'KODE GUDANG',
  'NO. PO/SJ',
  'PROGRAM',
  'SUPPLIER',
  'NPWP',
  'NO. INVOICE',
  'DPP',
  'PPN',
  'TOTAL INVOICE',
  'STATUS PAYMENT',
  'NO. FAKTUR PAJAK',
  'TAX INVOICE DATE'
];

const sampleRows = [
  [
    '05-03-2026',
    'Maret 2026',
    'Pipa & Tubing',
    'SCTV',
    'PT SCM Nusantara',
    'GDG-JKT-01',
    'PO/2026/0101 / SJ-0101',
    'Pengadaan Komponen Pipa Gas Tuban',
    'PT Steel Pipe Industry of Indonesia Tbk',
    '01.345.678.9-012.000',
    'INV/2026/SCM/0101',
    45000000,
    4950000,
    49950000,
    'PAID',
    '010.002-26.11029381',
    '05-03-2026'
  ],
  [
    '08-03-2026',
    'Maret 2026',
    'Sewa Alat Berat',
    'Indosiar',
    'PT SCM Solusi Indonesia',
    'GDG-SBY-02',
    'PO/2026/0102 / SJ-0102',
    'Penyewaan Heavy Crane Lepas Pantai 50 Ton',
    'PT Radiant Utama Interinsco Tbk',
    '02.887.123.4-041.000',
    'INV/2026/SCM/0102',
    120000000,
    13200000,
    133200000,
    'tempo',
    '010.002-26.22910382',
    '08-03-2026'
  ],
  [
    '11-03-2026',
    'Maret 2026',
    'Inspeksi & Sertifikasi',
    'Vidio',
    'PT SCM Nusantara',
    'GDG-BDG-01',
    'PO/2026/0103 / SJ-0103',
    'Jasa Inspeksi Tangki Kilang Balikpapan',
    'PT Sucofindo (Persero)',
    '01.000.456.7-051.000',
    'INV/2026/SCM/0103',
    75000000,
    8250000,
    83250000,
    'WAITING PAYMENT',
    '010.002-26.33849102',
    '11-03-2026'
  ],
  [
    '14-03-2026',
    'Maret 2026',
    'Mekanikal & Valve',
    'Moji',
    'PT SCM Logistik Utama',
    'GDG-CKR-03',
    'PO/2026/0104 / SJ-0104',
    'Pengadaan High Pressure Valve & Flange Class 600',
    'PT Kitz Valve Indonesia',
    '03.221.456.7-052.000',
    'INV/2026/SCM/0104',
    38500000,
    4235000,
    42735000,
    'cbd',
    '010.002-26.44910293',
    '14-03-2026'
  ],
  [
    '18-03-2026',
    'Maret 2026',
    'Bahan Kimia',
    'Mentari TV',
    'PT Surya Citra Media Tbk',
    'GDG-TBN-01',
    'PO/2026/0105 / SJ-0105',
    'Pengadaan Chemical Demulsifier Lapangan Minyak',
    'PT Clariant Indonesia',
    '01.992.834.1-015.000',
    'INV/2026/SCM/0105',
    92000000,
    10120000,
    102120000,
    'PAID',
    '010.002-26.55910294',
    '18-03-2026'
  ],
  [
    '04-02-2026',
    'Februari 2026',
    'Logistik',
    'SCM',
    'PT SCM Solusi Indonesia',
    'GDG-JKT-02',
    'PO/2026/0201 / SJ-0201',
    'Pengadaan Armada Wingbox Pendingin Logistik',
    'PT Samudera Perkasa Abadi',
    '02.887.123.4-041.000',
    'INV/SPA/2026/0842',
    828828829,
    91171171,
    920000000,
    'tempo',
    '010.002-26.66910295',
    '04-02-2026'
  ],
  [
    '09-02-2026',
    'Februari 2026',
    'IT & Software',
    'Vidio',
    'PT SCM Nusantara',
    'GDG-JKT-01',
    'PO/2026/0202 / SJ-0202',
    'Integrasi WMS Automated Sorting Center Phase 2',
    'PT Global Solusi Informatika',
    '03.221.456.7-052.000',
    'INV/GSI/2026/0312',
    1126126126,
    123873874,
    1250000000,
    'WAITING PAYMENT',
    '010.002-26.77910296',
    '09-02-2026'
  ],
  [
    '15-02-2026',
    'Februari 2026',
    'Distribusi',
    'Indosiar',
    'PT SCM Logistik Utama',
    'GDG-SMG-01',
    'PO/2026/0203 / SJ-0203',
    'Distribusi Ritel Multi-Hub Jawa Bali',
    'PT Mitra Distribusi Utama',
    '01.992.834.1-015.000',
    'INV/MDU/2026/1109',
    648648649,
    71351351,
    720000000,
    'PAID',
    '010.002-26.88910297',
    '15-02-2026'
  ],
  [
    '20-02-2026',
    'Februari 2026',
    'Material Handling',
    'SCTV',
    'PT Surya Citra Media Tbk',
    'GDG-CKR-01',
    'PO/2026/0204 / SJ-0204',
    'Pengadaan Forklift Elektrik 3 Ton High-Mast',
    'PT Toyota Material Handling Indonesia',
    '01.442.981.2-064.000',
    'INV/TMH/2026/0488',
    495495495,
    54504505,
    550000000,
    'cbd',
    '010.002-26.99910298',
    '20-02-2026'
  ],
  [
    '08-01-2026',
    'Januari 2026',
    'Warehouse',
    'SCM',
    'PT SCM Nusantara',
    'GDG-CKR-02',
    'PO/2026/0111 / SJ-0111',
    'Instalasi Selective Pallet Racking Gudang Cikarang',
    'PT Dexion Warehouse Systems',
    '02.551.789.0-033.000',
    'INV/DXN/2026/0142',
    315315315,
    34684685,
    350000000,
    'WAITING PAYMENT',
    '010.002-26.10910299',
    '08-01-2026'
  ]
];

async function generateTemplates() {
  const publicDir = path.join(__dirname, '..', 'public', 'templates');
  fs.mkdirSync(publicDir, { recursive: true });

  // 1. Generate Executive Styled Excel (.xlsx) using ExcelJS
  const workbook = new ExcelJS.Workbook();
  workbook.creator = 'SCM TaxVault';
  workbook.lastModifiedBy = 'SCM TaxVault';
  workbook.created = new Date();
  workbook.modified = new Date();

  const worksheet = workbook.addWorksheet('Template Program', {
    views: [{ state: 'frozen', ySplit: 1, activeCell: 'A2' }],
    properties: { defaultRowHeight: 22 }
  });

  // Column definitions with optimal widths
  worksheet.columns = [
    { header: 'TANGGAL', key: 'tanggal', width: 16 },
    { header: 'BULAN', key: 'bulan', width: 16 },
    { header: 'KATEGORI', key: 'kategori', width: 22 },
    { header: 'BRAND', key: 'brand', width: 16 },
    { header: 'COMPANY NAME', key: 'company', width: 28 },
    { header: 'KODE GUDANG', key: 'kode_gudang', width: 18 },
    { header: 'NO. PO/SJ', key: 'po_sj', width: 26 },
    { header: 'PROGRAM', key: 'program', width: 46 },
    { header: 'SUPPLIER', key: 'supplier', width: 40 },
    { header: 'NPWP', key: 'npwp', width: 24 },
    { header: 'NO. INVOICE', key: 'invoice', width: 24 },
    { header: 'DPP', key: 'dpp', width: 20 },
    { header: 'PPN', key: 'ppn', width: 18 },
    { header: 'TOTAL INVOICE', key: 'total', width: 22 },
    { header: 'STATUS PAYMENT', key: 'payment_status', width: 20 },
    { header: 'NO. FAKTUR PAJAK', key: 'faktur_no', width: 24 },
    { header: 'TAX INVOICE DATE', key: 'faktur_date', width: 20 }
  ];

  // Style Header Row (Row 1)
  const headerRow = worksheet.getRow(1);
  headerRow.height = 30;
  headerRow.eachCell((cell) => {
    cell.font = { name: 'Calibri', size: 11, bold: true, color: { argb: 'FFFFFFFF' } };
    cell.fill = {
      type: 'pattern',
      pattern: 'solid',
      fgColor: { argb: 'FF1E293B' } // Dark Slate #1E293B
    };
    cell.alignment = {
      vertical: 'middle',
      horizontal: 'center'
    };
    cell.border = {
      top: { style: 'thin', color: { argb: 'FF475569' } },
      left: { style: 'thin', color: { argb: 'FF475569' } },
      bottom: { style: 'medium', color: { argb: 'FF0F172A' } },
      right: { style: 'thin', color: { argb: 'FF475569' } }
    };
  });

  // Populate & Style Data Rows
  sampleRows.forEach((rowData, index) => {
    const row = worksheet.addRow(rowData);
    row.height = 22;
    const isEven = index % 2 === 1;
    const bgColor = isEven ? 'FFF8FAFC' : 'FFFFFFFF';

    row.eachCell({ includeEmpty: true }, (cell, colNumber) => {
      cell.font = { name: 'Calibri', size: 10, color: { argb: 'FF1E293B' } };
      cell.fill = {
        type: 'pattern',
        pattern: 'solid',
        fgColor: { argb: bgColor }
      };
      cell.border = {
        top: { style: 'thin', color: { argb: 'FFE2E8F0' } },
        left: { style: 'thin', color: { argb: 'FFE2E8F0' } },
        bottom: { style: 'thin', color: { argb: 'FFE2E8F0' } },
        right: { style: 'thin', color: { argb: 'FFE2E8F0' } }
      };

      // Alignment and Formatting
      if ([1, 2, 3, 4, 6, 7, 10, 11, 15, 16, 17].includes(colNumber)) {
        cell.alignment = { vertical: 'middle', horizontal: 'center' };
        if ([1, 17].includes(colNumber)) {
          cell.numFmt = '@';
        }
      } else if ([12, 13, 14].includes(colNumber)) {
        cell.alignment = { vertical: 'middle', horizontal: 'right' };
        cell.numFmt = '#,##0';
        if (colNumber === 14) {
          cell.font = { name: 'Calibri', size: 10, bold: true, color: { argb: 'FF0F172A' } };
        }
      } else {
        cell.alignment = { vertical: 'middle', horizontal: 'left' };
      }
    });
  });

  // Enable AutoFilter on header row (A1 to Q1)
  worksheet.autoFilter = 'A1:Q1';

  const xlsxPath = path.join(publicDir, 'Template_Import_Arsip_Program_SCM.xlsx');
  await workbook.xlsx.writeFile(xlsxPath);
  console.log('Generated Styled Excel (.xlsx):', xlsxPath);

  // 2. Generate CSV with 'sep=,' directive so Excel opens with separate columns on any Windows locale
  const csvLines = [
    'sep=,',
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
  const csvPath = path.join(publicDir, 'Template_Import_Arsip_Program_SCM.csv');
  fs.writeFileSync(csvPath, '\uFEFF' + csvLines.join('\r\n'), 'utf8');
  console.log('Generated Separated CSV (.csv):', csvPath);
}

generateTemplates().catch(console.error);
