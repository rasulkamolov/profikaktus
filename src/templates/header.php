<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mato Ombori</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Heroicons (via unpkg for simplicity in raw usage) -->
    <!-- We will use SVG strings or a simple library if needed, but for now simple SVGs are best -->
</head>
<body class="bg-gray-100 min-h-screen text-gray-800">
    <nav class="bg-white shadow-md">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex">
                    <div class="flex-shrink-0 flex items-center">
                        <span class="text-xl font-bold text-indigo-600">Mato Ombori</span>
                    </div>
                    <?php if (isset($_SESSION['role'])): ?>
                    <div class="hidden sm:ml-6 sm:flex sm:space-x-8">
                        <?php if ($_SESSION['role'] === 'admin'): ?>
                            <a href="/admin/index.php" class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">Boshqaruv Paneli</a>
                            <a href="/admin/materials.php" class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">Materiallar</a>
                            <a href="/admin/stock.php" class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">Kirim (Ombor)</a>
                            <a href="/admin/reports.php" class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">Hisobotlar</a>
                        <?php else: ?>
                            <a href="/cutter/index.php" class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">Kesuvchi Paneli</a>
                            <a href="/cutter/cut.php" class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">Kesish</a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="flex items-center">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <span class="text-gray-700 mr-4">Foydalanuvchi: <?php echo htmlspecialchars($_SESSION['username'] ?? ''); ?></span>
                        <a href="/logout.php" class="text-indigo-600 hover:text-indigo-900 font-medium">Chiqish</a>
                    <?php else: ?>
                        <a href="/login.php" class="text-indigo-600 hover:text-indigo-900 font-medium">Kirish</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>
    <main class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
