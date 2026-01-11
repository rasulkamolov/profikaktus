<?php
require_once __DIR__ . '/../../src/auth.php';
require_once __DIR__ . '/../../src/db.php';
require_once __DIR__ . '/../../src/functions.php';

require_role('cutter');

include __DIR__ . '/../../src/templates/header.php';
?>

<div class="text-center py-10">
    <h1 class="text-4xl font-bold text-gray-900 mb-4">Xush kelibsiz, Kesuvchi!</h1>
    <p class="text-xl text-gray-600 mb-8">Ishni boshlash uchun quyidagi tugmani bosing.</p>

    <a href="/cutter/cut.php" class="inline-flex items-center justify-center px-8 py-4 border border-transparent text-lg font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 md:py-4 md:text-lg md:px-10">
        Material Kesish
    </a>
</div>

<?php include __DIR__ . '/../../src/templates/footer.php'; ?>
