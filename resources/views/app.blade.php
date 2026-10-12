<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SCM TaxVault - Arsip Dokumen Pajak</title>

    <!-- Ikon tab: ikon arsip polos (?v= memaksa browser mengganti ikon lama yang tersimpan di cache) -->
    <link rel="icon" type="image/svg+xml" href="/favicon.svg?v=2">

    <!-- Font: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- SheetJS for Excel (.xlsx, .xls) Import -->
    <script src="https://cdn.sheetjs.com/xlsx-0.20.3/package/dist/xlsx.full.min.js"></script>

    <!-- Theme initialization (Prevent FOUC) -->
    <script>
        (function() {
            try {
                var theme = localStorage.getItem('scm_taxvault_theme');
                var prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                if (theme === 'dark' || (!theme && prefersDark)) {
                    document.documentElement.classList.add('dark');
                } else {
                    document.documentElement.classList.remove('dark');
                }
            } catch (e) {}
        })();
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-[#F8FAFC] dark:bg-[#090D16] text-[#0F172A] dark:text-[#F8FAFC] antialiased font-sans selection:bg-[#E0F2FE] selection:text-[#0369A1] transition-colors">
    <div id="app"></div>
</body>
</html>
