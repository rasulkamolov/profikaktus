<!DOCTYPE html>
<html lang="uz" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>profikaktusMaterial Inventory</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    },
                    colors: {
                        primary: {
                            50: '#f0f9ff',
                            100: '#e0f2fe',
                            200: '#bae6fd',
                            300: '#7dd3fc',
                            400: '#38bdf8',
                            500: '#0ea5e9',
                            600: '#0284c7',
                            700: '#0369a1',
                            800: '#075985',
                            900: '#0c4a6e',
                        }
                    }
                }
            }
        }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        /* Thicker inputs and borders */
        input, select, textarea {
            border-width: 1px !important;
            border-color: #94a3b8 !important; /* slate-400 */
        }
        input:focus, select:focus, textarea:focus {
            border-color: #0ea5e9 !important; /* primary-500 */
        }
    </style>
</head>
<body class="h-full text-slate-900 flex flex-col">
    <nav class="bg-white border-b border-slate-200 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex">
                    <div class="flex-shrink-0 flex items-center">
                        <!-- Icon: Cube/Box -->
                        <svg class="h-8 w-8 text-primary-600 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                        </svg>
                        <span class="text-xl font-bold bg-clip-text text-transparent bg-gradient-to-r from-primary-700 to-primary-500">profikaktusMaterial Inventory</span>
                    </div>
                    <?php if (isset($_SESSION['role'])): ?>
                    <div class="hidden sm:ml-8 sm:flex sm:space-x-8">
                        <?php
                        $current_page = basename($_SERVER['PHP_SELF']);
                        $nav_active_class = "border-primary-500 text-slate-900 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium";
                        $nav_inactive_class = "border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium transition-colors duration-200";
                        ?>

                        <?php if ($_SESSION['role'] === 'admin'): ?>
                            <a href="../admin/index.php" class="<?php echo ($current_page == 'index.php') ? $nav_active_class : $nav_inactive_class; ?>">
                                <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" /></svg>
                                Boshqaruv
                            </a>
                            <a href="../admin/materials.php" class="<?php echo ($current_page == 'materials.php' || $current_page == 'material_form.php') ? $nav_active_class : $nav_inactive_class; ?>">
                                <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" /></svg>
                                Materiallar
                            </a>
                            <a href="../admin/stock.php" class="<?php echo ($current_page == 'stock.php') ? $nav_active_class : $nav_inactive_class; ?>">
                                <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                Kirim
                            </a>
                            <a href="../admin/reports.php" class="<?php echo ($current_page == 'reports.php') ? $nav_active_class : $nav_inactive_class; ?>">
                                <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
                                Hisobotlar
                            </a>
                            <a href="../admin/statistics.php" class="<?php echo ($current_page == 'statistics.php') ? $nav_active_class : $nav_inactive_class; ?>">
                                <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z" /></svg>
                                Statistika
                            </a>
                             <a href="../admin/employees.php" class="<?php echo ($current_page == 'employees.php' || $current_page == 'user_form.php') ? $nav_active_class : $nav_inactive_class; ?>">
                                <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                                Hodimlar
                            </a>
                        <?php else: ?>
                            <a href="../cutter/index.php" class="<?php echo ($current_page == 'index.php') ? $nav_active_class : $nav_inactive_class; ?>">
                                <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                                Kesuvchi Paneli
                            </a>
                            <a href="../cutter/cut.php" class="<?php echo ($current_page == 'cut.php') ? $nav_active_class : $nav_inactive_class; ?>">
                                <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.121 14.121L19 19m-7-7l7-7m-7 7l-2.879 2.879M12 12L9.121 9.121m0 5.758a3 3 0 10-4.243 4.243 3 3 0 004.243-4.243zm0-5.758a3 3 0 10-4.243-4.243 3 3 0 004.243 4.243z" /></svg>
                                Kesish
                            </a>
                            <a href="../cutter/history.php" class="<?php echo ($current_page == 'history.php') ? $nav_active_class : $nav_inactive_class; ?>">
                                <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                Tarix
                            </a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="flex items-center">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <div class="flex items-center bg-slate-100 rounded-full px-4 py-2">
                            <svg class="w-5 h-5 mr-2 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            <span class="text-sm font-medium text-slate-700 mr-4"><?php echo htmlspecialchars($_SESSION['username'] ?? ''); ?></span>
                            <div class="h-4 w-px bg-slate-300 mr-4"></div>
                            <a href="../logout.php" class="text-sm font-medium text-red-600 hover:text-red-700 flex items-center transition-colors">
                                Chiqish
                            </a>
                        </div>
                    <?php else: ?>
                        <a href="../login.php" class="text-primary-600 hover:text-primary-700 font-medium">Kirish</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>
    <main class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8 flex-grow w-full">
