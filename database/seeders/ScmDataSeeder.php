<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Program;
use App\Models\ProgramDocument;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class ScmDataSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Seed Users
        $admin = User::updateOrCreate(
            ['email' => 'admin@scm.corp'],
            [
                'name' => 'Budi Santoso',
                'phone' => '081234567890',
                'role' => 'Admin SCM',
                'status' => 'approved',
                'division' => 'Divisi Supply Chain Management',
                'initials' => 'BS',
                'password' => Hash::make('password123'),
                'approved_at' => Carbon::now()->subDays(30)
            ]
        );

        $auditor = User::updateOrCreate(
            ['email' => 'auditor@pajak.corp'],
            [
                'name' => 'Siti Rahmawati',
                'phone' => '081224290502',
                'role' => 'Finance',
                'status' => 'approved',
                'division' => 'Tax & Finance Compliance',
                'initials' => 'SR',
                'password' => Hash::make('password123'),
                'approved_by' => $admin->id,
                'approved_at' => Carbon::now()->subDays(20)
            ]
        );

        $gudang = User::updateOrCreate(
            ['email' => 'gudang@scm.corp'],
            [
                'name' => 'Ahmad Fauzi',
                'phone' => '081298765431',
                'role' => 'Staff Gudang',
                'status' => 'approved',
                'division' => 'Operasional Gudang & Logistik',
                'initials' => 'AF',
                'password' => Hash::make('password123'),
                'approved_by' => $admin->id,
                'approved_at' => Carbon::now()->subDays(25)
            ]
        );

        $financeUser = User::updateOrCreate(
            ['email' => 'finance@scm.corp'],
            [
                'name' => 'Dewi Lestari',
                'phone' => '081224290503',
                'role' => 'Staff Finance',
                'status' => 'approved',
                'division' => 'Tax & Finance Compliance',
                'initials' => 'DL',
                'password' => Hash::make('password123'),
                'approved_by' => $admin->id,
                'approved_at' => Carbon::now()->subDays(18)
            ]
        );

        $scmUser = User::updateOrCreate(
            ['email' => 'scm@scm.corp'],
            [
                'name' => 'Rian Hidayat',
                'phone' => '081298765433',
                'role' => 'Staff SCM',
                'status' => 'approved',
                'division' => 'Supply Chain Management',
                'initials' => 'RH',
                'password' => Hash::make('password123'),
                'approved_by' => $admin->id,
                'approved_at' => Carbon::now()->subDays(15)
            ]
        );

        $staff = User::updateOrCreate(
            ['email' => 'staff@scm.corp'],
            [
                'name' => 'Hendra Wijaya',
                'phone' => '081298765432',
                'role' => 'SCM',
                'status' => 'approved',
                'division' => 'Supply Chain Management',
                'initials' => 'HW',
                'password' => Hash::make('password123'),
                'approved_by' => $admin->id,
                'approved_at' => Carbon::now()->subDays(15)
            ]
        );

        $pendingUser = User::updateOrCreate(
            ['email' => 'reza25022003@gmail.com'],
            [
                'name' => 'Reza Pratama',
                'phone' => '081234567899',
                'role' => 'Tim Pajak',
                'status' => 'pending', // Menunggu ACC Admin
                'division' => 'Tax & Compliance Audit',
                'initials' => 'RP',
                'password' => Hash::make('password123'),
            ]
        );

        // 2. Seed Initial Programs (18 real archive programs)
        $samplePrograms = [
            [
                'id' => '1',
                'title' => 'Program Supply Chain Optimization',
                'category' => 'Logistik',
                'due_date' => '2025-06-16',
                'supplier' => 'PT Cipta Logistik Nusantara',
                'npwp' => '01.345.678.9-012.000',
                'invoice_no' => 'INV/CLP/2025/1016',
                'dpp_amount' => 337837838,
                'ppn_amount' => 37162162,
                'total_amount' => 375000000,
                'status' => 'Lengkap',
                'docs' => [
                    ['id' => 'doc-101', 'type' => 'invoice', 'file_name' => 'invoice-clp-1016.pdf', 'file_size' => '1.4 MB'],
                    ['id' => 'doc-102', 'type' => 'faktur', 'file_name' => 'faktur-pajak-clp-1016.pdf', 'file_size' => '820 KB'],
                    ['id' => 'doc-103', 'type' => 'memo', 'file_name' => 'mou-optimasi-clp-2025.pdf', 'file_size' => '3.1 MB'],
                ]
            ],
            [
                'id' => '2',
                'title' => 'Pengadaan Armada Pendingin Logistik',
                'category' => 'Logistik',
                'due_date' => '2025-06-12',
                'supplier' => 'PT Samudera Perkasa Abadi',
                'npwp' => '02.887.123.4-041.000',
                'invoice_no' => 'INV/SPA/2025/0842',
                'dpp_amount' => 828828829,
                'ppn_amount' => 91171171,
                'total_amount' => 920000000,
                'status' => 'Lengkap',
                'docs' => [
                    ['id' => 'doc-201', 'type' => 'invoice', 'file_name' => 'inv-spa-0842-armada.pdf', 'file_size' => '1.8 MB'],
                    ['id' => 'doc-202', 'type' => 'faktur', 'file_name' => 'faktur-spa-0842.pdf', 'file_size' => '940 KB'],
                    ['id' => 'doc-203', 'type' => 'memo', 'file_name' => 'spk-spa-fleet-2025.pdf', 'file_size' => '2.4 MB'],
                ]
            ],
            [
                'id' => '3',
                'title' => 'Integrasi WMS Automated Sorting',
                'category' => 'IT & Software',
                'due_date' => '2025-06-08',
                'supplier' => 'PT Global Solusi Informatika',
                'npwp' => '03.221.456.7-052.000',
                'invoice_no' => 'INV/GSI/2025/0312',
                'dpp_amount' => 1126126126,
                'ppn_amount' => 123873874,
                'total_amount' => 1250000000,
                'status' => 'Lengkap',
                'docs' => [
                    ['id' => 'doc-301', 'type' => 'invoice', 'file_name' => 'inv-gsi-wms-sort.pdf', 'file_size' => '2.1 MB'],
                    ['id' => 'doc-302', 'type' => 'faktur', 'file_name' => 'fp-010-000-25-003129.pdf', 'file_size' => '760 KB'],
                    ['id' => 'doc-303', 'type' => 'memo', 'file_name' => 'sla-contract-gsi-2025.pdf', 'file_size' => '4.2 MB'],
                ]
            ],
            [
                'id' => '4',
                'title' => 'Distribusi Ritel Multi-Hub Jawa Bali',
                'category' => 'Distribusi',
                'due_date' => '2025-05-28',
                'supplier' => 'PT Mitra Distribusi Utama',
                'npwp' => '01.992.834.1-015.000',
                'invoice_no' => 'INV/MDU/2025/1109',
                'dpp_amount' => 648648649,
                'ppn_amount' => 71351351,
                'total_amount' => 720000000,
                'status' => 'Lengkap',
                'docs' => [
                    ['id' => 'doc-401', 'type' => 'invoice', 'file_name' => 'inv-mdu-distribusi-mei.pdf', 'file_size' => '1.6 MB'],
                    ['id' => 'doc-402', 'type' => 'faktur', 'file_name' => 'faktur-mdu-1109.pdf', 'file_size' => '810 KB'],
                    ['id' => 'doc-403', 'type' => 'memo', 'file_name' => 'mou-mdu-hub-jb.pdf', 'file_size' => '2.8 MB'],
                ]
            ],
            [
                'id' => '5',
                'title' => 'Pengadaan Pallet & Racking Gudang Cikarang',
                'category' => 'Pengadaan Material',
                'due_date' => '2025-05-20',
                'supplier' => 'PT Bahtera Niaga Sentosa',
                'npwp' => '02.441.789.0-033.000',
                'invoice_no' => 'INV/BNS/2025/0547',
                'dpp_amount' => 495495495,
                'ppn_amount' => 54504505,
                'total_amount' => 550000000,
                'status' => 'Lengkap',
                'docs' => [
                    ['id' => 'doc-501', 'type' => 'invoice', 'file_name' => 'invoice-bns-racking.pdf', 'file_size' => '1.3 MB'],
                    ['id' => 'doc-502', 'type' => 'faktur', 'file_name' => 'fp-bns-0547.pdf', 'file_size' => '720 KB'],
                    ['id' => 'doc-503', 'type' => 'memo', 'file_name' => 'perjanjian-bns-cikarang.pdf', 'file_size' => '1.9 MB'],
                ]
            ],
            [
                'id' => '6',
                'title' => 'Modernisasi Automated Conveyor Hub Marunda',
                'category' => 'Operasional',
                'due_date' => '2025-05-14',
                'supplier' => 'PT Tridaya Rekayasa Industri',
                'npwp' => '03.778.901.2-064.000',
                'invoice_no' => 'INV/TRI/2025/0421',
                'dpp_amount' => 792792793,
                'ppn_amount' => 87207207,
                'total_amount' => 880000000,
                'status' => 'Lengkap',
                'docs' => [
                    ['id' => 'doc-601', 'type' => 'invoice', 'file_name' => 'inv-tri-conveyor.pdf', 'file_size' => '2.0 MB'],
                    ['id' => 'doc-602', 'type' => 'faktur', 'file_name' => 'faktur-tri-0421.pdf', 'file_size' => '890 KB'],
                    ['id' => 'doc-603', 'type' => 'memo', 'file_name' => 'kontrak-kerja-marunda-tri.pdf', 'file_size' => '3.5 MB'],
                ]
            ],
            [
                'id' => '7',
                'title' => 'Program Restock Otomatis Cross-Docking',
                'category' => 'Logistik',
                'due_date' => '2025-06-18',
                'supplier' => 'PT Andalan Sarana Mandiri',
                'npwp' => '01.654.321.8-028.000',
                'invoice_no' => 'INV/ASM/2025/1204',
                'dpp_amount' => 414414414,
                'ppn_amount' => 45585586,
                'total_amount' => 460000000,
                'status' => 'Perlu Tindakan',
                'docs' => [
                    ['id' => 'doc-701', 'type' => 'invoice', 'file_name' => 'invoice-asm-crossdock.pdf', 'file_size' => '1.1 MB'],
                    ['id' => 'doc-703', 'type' => 'memo', 'file_name' => 'mou-crossdock-2025.pdf', 'file_size' => '1.8 MB'],
                ]
            ],
            [
                'id' => '8',
                'title' => 'Konsultasi SCM Tax Compliance & Audit',
                'category' => 'Jasa Konsultasi',
                'due_date' => '2025-06-04',
                'supplier' => 'PT Nusantara Integra Corpora',
                'npwp' => '02.334.567.8-019.000',
                'invoice_no' => 'INV/NIC/2025/0198',
                'dpp_amount' => 270270270,
                'ppn_amount' => 29729730,
                'total_amount' => 300000000,
                'status' => 'Perlu Tindakan',
                'docs' => [
                    ['id' => 'doc-801', 'type' => 'invoice', 'file_name' => 'inv-nic-audit-tax.pdf', 'file_size' => '1.4 MB'],
                    ['id' => 'doc-802', 'type' => 'faktur', 'file_name' => 'fp-nic-0198.pdf', 'file_size' => '690 KB'],
                ]
            ],
            [
                'id' => '9',
                'title' => 'Charter Armada Kapal Roro Logistik Timur',
                'category' => 'Logistik',
                'due_date' => '2025-05-30',
                'supplier' => 'PT Graha Surya Logistik',
                'npwp' => '01.882.341.2-088.000',
                'invoice_no' => 'INV/GSL/2025/0762',
                'dpp_amount' => 756756757,
                'ppn_amount' => 83243243,
                'total_amount' => 840000000,
                'status' => 'Perlu Tindakan',
                'docs' => [
                    ['id' => 'doc-901', 'type' => 'invoice', 'file_name' => 'invoice-gsl-roro-east.pdf', 'file_size' => '2.3 MB'],
                ]
            ],
            [
                'id' => '10',
                'title' => 'Penyewaan Cold Storage Subang Hub',
                'category' => 'Operasional',
                'due_date' => '2025-05-24',
                'supplier' => 'PT Buana Sumber Makmur',
                'npwp' => '03.119.876.5-045.000',
                'invoice_no' => 'INV/BSM/2025/0631',
                'dpp_amount' => 531531532,
                'ppn_amount' => 58468468,
                'total_amount' => 590000000,
                'status' => 'Perlu Tindakan',
                'docs' => [
                    ['id' => 'doc-1001', 'type' => 'invoice', 'file_name' => 'inv-bsm-coldstorage.pdf', 'file_size' => '1.7 MB'],
                    ['id' => 'doc-1002', 'type' => 'faktur', 'file_name' => 'fp-bsm-0631.pdf', 'file_size' => '820 KB'],
                ]
            ],
            [
                'id' => '11',
                'title' => 'Jasa Angkut Truk Tronton Rute Pantura',
                'category' => 'Logistik',
                'due_date' => '2025-05-18',
                'supplier' => 'PT Berkah Armada Trans',
                'npwp' => '02.556.789.1-017.000',
                'invoice_no' => 'INV/BAT/2025/0915',
                'dpp_amount' => 369369369,
                'ppn_amount' => 40630631,
                'total_amount' => 410000000,
                'status' => 'Perlu Tindakan',
                'docs' => [
                    ['id' => 'doc-1102', 'type' => 'faktur', 'file_name' => 'faktur-bat-0915.pdf', 'file_size' => '780 KB'],
                    ['id' => 'doc-1103', 'type' => 'memo', 'file_name' => 'spk-armada-bat-pantura.pdf', 'file_size' => '2.1 MB'],
                ]
            ],
            [
                'id' => '12',
                'title' => 'Pengadaan IoT Sensor Suhu & Kelembaban',
                'category' => 'IT & Software',
                'due_date' => '2025-05-10',
                'supplier' => 'PT Inti Sarana Solusindo',
                'npwp' => '01.443.210.9-055.000',
                'invoice_no' => 'INV/ISS/2025/0248',
                'dpp_amount' => 315315315,
                'ppn_amount' => 34684685,
                'total_amount' => 350000000,
                'status' => 'Perlu Tindakan',
                'docs' => [
                    ['id' => 'doc-1201', 'type' => 'invoice', 'file_name' => 'inv-iss-iot-sensors.pdf', 'file_size' => '1.2 MB'],
                ]
            ],
            [
                'id' => '13',
                'title' => 'Sewa Hub Distribusi Sentul 5000m2',
                'category' => 'Operasional',
                'due_date' => '2025-05-02',
                'supplier' => 'PT Sinergi Sukses Logistika',
                'npwp' => '02.771.234.5-039.000',
                'invoice_no' => 'INV/SSL/2025/0501',
                'dpp_amount' => 585585586,
                'ppn_amount' => 64414414,
                'total_amount' => 650000000,
                'status' => 'Perlu Tindakan',
                'docs' => [
                    ['id' => 'doc-1301', 'type' => 'invoice', 'file_name' => 'inv-ssl-hub-sentul.pdf', 'file_size' => '1.9 MB'],
                    ['id' => 'doc-1303', 'type' => 'memo', 'file_name' => 'perjanjian-sewa-hub-sentul.pdf', 'file_size' => '4.5 MB'],
                ]
            ],
            [
                'id' => '14',
                'title' => 'Pengadaan Box Corrugated & Pallet Wrap',
                'category' => 'Pengadaan Material',
                'due_date' => '2025-06-20',
                'supplier' => 'PT Cahaya Megah Abadi',
                'npwp' => '01.554.890.1-011.000',
                'invoice_no' => 'INV/CMA/2025/1330',
                'dpp_amount' => 288288288,
                'ppn_amount' => 31711712,
                'total_amount' => 320000000,
                'status' => 'Perlu Tindakan',
                'docs' => []
            ],
            [
                'id' => '15',
                'title' => 'Ekspansi Jalur Distribusi Sumatera Bagian Selatan',
                'category' => 'Distribusi',
                'due_date' => '2025-06-22',
                'supplier' => 'PT Delta Kencana Logistik',
                'npwp' => '02.991.654.3-024.000',
                'invoice_no' => 'INV/DKL/2025/0677',
                'dpp_amount' => 675675676,
                'ppn_amount' => 74324324,
                'total_amount' => 750000000,
                'status' => 'Perlu Tindakan',
                'docs' => []
            ],
            [
                'id' => '16',
                'title' => 'Penyewaan Kapal Kontainer Surabaya - Makassar',
                'category' => 'Logistik',
                'due_date' => '2025-06-25',
                'supplier' => 'PT Kencana Lintas Laut',
                'npwp' => '03.442.110.8-067.000',
                'invoice_no' => 'INV/KLL/2025/0819',
                'dpp_amount' => 1081081081,
                'ppn_amount' => 118918919,
                'total_amount' => 1200000000,
                'status' => 'Perlu Tindakan',
                'docs' => []
            ],
            [
                'id' => '17',
                'title' => 'Optimasi Rute Last-Mile dengan AI Dispatcher',
                'category' => 'IT & Software',
                'due_date' => '2025-06-26',
                'supplier' => 'PT Global Solusi Informatika',
                'npwp' => '03.221.456.7-052.000',
                'invoice_no' => 'INV/GSI/2025/0445',
                'dpp_amount' => 450450450,
                'ppn_amount' => 49549550,
                'total_amount' => 500000000,
                'status' => 'Perlu Tindakan',
                'docs' => []
            ],
            [
                'id' => '18',
                'title' => 'Audit Jejak Karbon Armada Logistik (Green SCM)',
                'category' => 'Jasa Konsultasi',
                'due_date' => '2025-06-28',
                'supplier' => 'PT Sentosa Solusi Berkelanjutan',
                'npwp' => '01.773.456.2-031.000',
                'invoice_no' => 'INV/SSB/2025/0122',
                'dpp_amount' => 180180180,
                'ppn_amount' => 19819820,
                'total_amount' => 200000000,
                'status' => 'Perlu Tindakan',
                'docs' => []
            ],
            [
                'id' => '19',
                'title' => 'Pengadaan Armada Truk Tronton Ekspedisi',
                'category' => 'Logistik',
                'due_date' => '2024-03-15',
                'supplier' => 'PT Samudera Perkasa Abadi',
                'npwp' => '02.887.123.4-041.000',
                'invoice_no' => 'INV/SPA/2024/0211',
                'dpp_amount' => 720720721,
                'ppn_amount' => 79279279,
                'total_amount' => 800000000,
                'status' => 'Lengkap',
                'docs' => [
                    ['id' => 'doc-401', 'type' => 'invoice', 'file_name' => 'inv-spa-2024-0211.pdf', 'file_size' => '1.5 MB'],
                    ['id' => 'doc-402', 'type' => 'faktur', 'file_name' => 'fp-spa-2024.pdf', 'file_size' => '780 KB'],
                    ['id' => 'doc-403', 'type' => 'memo', 'file_name' => 'spk-truk-2024.pdf', 'file_size' => '2.1 MB'],
                ]
            ],
            [
                'id' => '20',
                'title' => 'Integrasi Cloud Enterprise WMS & ERP SCM',
                'category' => 'IT & Software',
                'due_date' => '2024-05-20',
                'supplier' => 'PT Global Solusi Informatika',
                'npwp' => '03.221.456.7-052.000',
                'invoice_no' => 'INV/GSI/2024/0589',
                'dpp_amount' => 990990991,
                'ppn_amount' => 109009009,
                'total_amount' => 1100000000,
                'status' => 'Lengkap',
                'docs' => [
                    ['id' => 'doc-404', 'type' => 'invoice', 'file_name' => 'inv-gsi-erp-2024.pdf', 'file_size' => '1.8 MB'],
                    ['id' => 'doc-405', 'type' => 'faktur', 'file_name' => 'faktur-gsi-2024.pdf', 'file_size' => '850 KB'],
                    ['id' => 'doc-406', 'type' => 'memo', 'file_name' => 'mou-gsi-2024.pdf', 'file_size' => '3.0 MB'],
                ]
            ],
            [
                'id' => '21',
                'title' => 'Pengadaan Rak Pallet Mezzanine Heavy Duty',
                'category' => 'Pengadaan Material',
                'due_date' => '2024-08-10',
                'supplier' => 'PT Bahtera Niaga Sentosa',
                'npwp' => '02.441.789.0-033.000',
                'invoice_no' => 'INV/BNS/2024/0820',
                'dpp_amount' => 450450450,
                'ppn_amount' => 49549550,
                'total_amount' => 500000000,
                'status' => 'Lengkap',
                'docs' => [
                    ['id' => 'doc-407', 'type' => 'invoice', 'file_name' => 'inv-bns-mezzanine-2024.pdf', 'file_size' => '1.3 MB'],
                    ['id' => 'doc-408', 'type' => 'faktur', 'file_name' => 'fp-bns-2024.pdf', 'file_size' => '620 KB'],
                    ['id' => 'doc-409', 'type' => 'memo', 'file_name' => 'po-bns-2024.pdf', 'file_size' => '1.9 MB'],
                ]
            ],
            [
                'id' => '22',
                'title' => 'Distribusi Inter-Island Jawa - Kalimantan Barat',
                'category' => 'Distribusi',
                'due_date' => '2024-10-12',
                'supplier' => 'PT Mitra Distribusi Utama',
                'npwp' => '01.992.834.1-015.000',
                'invoice_no' => 'INV/MDU/2024/1042',
                'dpp_amount' => 585585586,
                'ppn_amount' => 64414414,
                'total_amount' => 650000000,
                'status' => 'Perlu Tindakan',
                'docs' => [
                    ['id' => 'doc-410', 'type' => 'invoice', 'file_name' => 'inv-mdu-kalbar-2024.pdf', 'file_size' => '1.4 MB'],
                ]
            ],
            [
                'id' => '23',
                'title' => 'Sewa Gudang Transit Hub Semarang',
                'category' => 'Operasional',
                'due_date' => '2024-11-25',
                'supplier' => 'PT Cipta Logistik Nusantara',
                'npwp' => '01.345.678.9-012.000',
                'invoice_no' => 'INV/CLP/2024/1190',
                'dpp_amount' => 360360360,
                'ppn_amount' => 39639640,
                'total_amount' => 400000000,
                'status' => 'Perlu Tindakan',
                'docs' => [
                    ['id' => 'doc-411', 'type' => 'invoice', 'file_name' => 'inv-clp-smg-2024.pdf', 'file_size' => '1.2 MB'],
                    ['id' => 'doc-412', 'type' => 'memo', 'file_name' => 'perjanjian-sewa-2024.pdf', 'file_size' => '2.5 MB'],
                ]
            ],
            [
                'id' => '24',
                'title' => 'Konsultasi Perencanaan Kapasitas Pergudangan 2025',
                'category' => 'Jasa Konsultasi',
                'due_date' => '2024-12-05',
                'supplier' => 'PT Sentosa Solusi Berkelanjutan',
                'npwp' => '01.773.456.2-031.000',
                'invoice_no' => 'INV/SSB/2024/1202',
                'dpp_amount' => 153153153,
                'ppn_amount' => 16846847,
                'total_amount' => 170000000,
                'status' => 'Perlu Tindakan',
                'docs' => []
            ]
        ];

        $brandList = ['SCTV', 'Indosiar', 'Vidio', 'Moji', 'Mentari TV', 'SCM'];
        foreach ($samplePrograms as $idx => $pData) {
            $docs = $pData['docs'];
            unset($pData['docs']);

            if (empty($pData['brand'])) {
                $idInt = (int) $pData['id'];
                $pData['brand'] = $brandList[($idInt - 1) % count($brandList)];
            }

            $prog = Program::updateOrCreate(
                ['id' => $pData['id']],
                $pData
            );

            foreach ($docs as $d) {
                ProgramDocument::updateOrCreate(
                    ['id' => $d['id']],
                    [
                        'program_id' => $prog->id,
                        'type' => $d['type'],
                        'file_name' => $d['file_name'],
                        'file_size' => $d['file_size'],
                        'uploaded_at' => Carbon::now()->subDays(rand(1, 15))
                    ]
                );
            }
        }
    }
}
