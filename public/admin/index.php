<?php
require_once __DIR__ . '/../../src/auth.php';
require_once __DIR__ . '/../../src/db.php';
require_once __DIR__ . '/../../src/functions.php';

require_role('admin');

// Get stats
$total_materials = $db->querySingle("SELECT count(*) FROM materials");
$low_stock = $db->querySingle("SELECT count(*) FROM materials WHERE (SELECT COALESCE(SUM(current_length), 0) FROM rolls WHERE material_id = materials.id) < min_stock_warning");
$critical_stock = $db->querySingle("SELECT count(*) FROM materials WHERE (SELECT COALESCE(SUM(current_length), 0) FROM rolls WHERE material_id = materials.id) < min_stock_critical");

include __DIR__ . '/../../src/templates/header.php';
?>

<div class="mb-8">
    <h1 class="text-3xl font-bold text-gray-900">Boshqaruv Paneli</h1>
    <p class="mt-2 text-gray-600">Xush kelibsiz, Admin.</p>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <!-- Total Materials Card -->
    <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <dt class="text-sm font-medium text-gray-500 truncate">Jami Materiallar</dt>
            <dd class="mt-1 text-3xl font-semibold text-gray-900"><?php echo $total_materials; ?></dd>
        </div>
        <div class="bg-gray-50 px-4 py-4 sm:px-6">
            <div class="text-sm">
                <a href="materials.php" class="font-medium text-indigo-600 hover:text-indigo-500">Barchasini ko'rish</a>
            </div>
        </div>
    </div>

    <!-- Low Stock Card -->
    <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <dt class="text-sm font-medium text-gray-500 truncate">Oz Qolgan (Ogohlantirish)</dt>
            <dd class="mt-1 text-3xl font-semibold text-yellow-600"><?php echo $low_stock; ?></dd>
        </div>
        <div class="bg-gray-50 px-4 py-4 sm:px-6">
            <div class="text-sm">
                <a href="reports.php" class="font-medium text-indigo-600 hover:text-indigo-500">Hisobotni ko'rish</a>
            </div>
        </div>
    </div>

    <!-- Critical Stock Card -->
    <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <dt class="text-sm font-medium text-gray-500 truncate">Juda Oz Qolgan (Kritik)</dt>
            <dd class="mt-1 text-3xl font-semibold text-red-600"><?php echo $critical_stock; ?></dd>
        </div>
        <div class="bg-gray-50 px-4 py-4 sm:px-6">
            <div class="text-sm">
                <a href="reports.php" class="font-medium text-indigo-600 hover:text-indigo-500">Hisobotni ko'rish</a>
            </div>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="bg-white shadow rounded-lg p-6">
        <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Tezkor Harakatlar</h3>
        <div class="grid grid-cols-2 gap-4">
            <a href="material_form.php" class="flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">
                Yangi Material Qo'shish
            </a>
            <a href="stock.php" class="flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-indigo-700 bg-indigo-100 hover:bg-indigo-200">
                Omborga Kirim Qilish
            </a>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../src/templates/footer.php'; ?>
