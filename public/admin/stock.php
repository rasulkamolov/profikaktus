<?php
require_once __DIR__ . '/../../src/auth.php';
require_once __DIR__ . '/../../src/db.php';
require_once __DIR__ . '/../../src/functions.php';

require_role('admin');

$materials = $db->query("SELECT id, name FROM materials ORDER BY name ASC");
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $material_id = $_POST['material_id'] ?? null;

    // Split M/CM inputs
    $length_m = (float)($_POST['length_m'] ?? 0);
    $length_cm = (float)($_POST['length_cm'] ?? 0);
    $length = $length_m + ($length_cm / 100);

    if (!$material_id || $length <= 0) {
        $error = "Material tanlang va uzunlikni to'g'ri kiriting.";
    } else {
        $stmt = $db->prepare("INSERT INTO rolls (material_id, original_length, current_length) VALUES (:mid, :len, :len)");
        $stmt->bindValue(':mid', $material_id, SQLITE3_INTEGER);
        $stmt->bindValue(':len', $length, SQLITE3_FLOAT);

        if ($stmt->execute()) {
            $message = "Yangi rulon muvaffaqiyatli qo'shildi.";
        } else {
            $error = "Xatolik yuz berdi.";
        }
    }
}

include __DIR__ . '/../../src/templates/header.php';
?>

<div class="max-w-xl mx-auto">
    <div class="bg-white shadow-sm rounded-xl overflow-hidden border border-slate-200">
        <div class="px-6 py-8">
            <h2 class="text-2xl font-bold text-slate-900 mb-6">Omborga Kirim (Yangi Rulon)</h2>

            <?php if ($message): ?>
                <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-md mb-6 flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-md mb-6 flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-6">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Materialni Tanlang</label>
                    <div class="relative">
                        <select name="material_id" class="block w-full pl-3 pr-10 py-3 text-base border-slate-300 focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm rounded-md shadow-sm">
                            <option value="">-- Tanlang --</option>
                            <?php while ($row = $materials->fetchArray(SQLITE3_ASSOC)): ?>
                                <option value="<?php echo $row['id']; ?>"><?php echo htmlspecialchars($row['name']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Rulon Uzunligi (Height)</label>
                    <div class="flex space-x-3">
                        <div class="relative rounded-md shadow-sm flex-1">
                            <input type="number" name="length_m" class="focus:ring-primary-500 focus:border-primary-500 block w-full sm:text-sm border-slate-300 rounded-md pl-4 pr-8 py-3" placeholder="0">
                            <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                <span class="text-slate-500 sm:text-sm font-medium">m</span>
                            </div>
                        </div>
                        <div class="relative rounded-md shadow-sm flex-1">
                            <input type="number" name="length_cm" class="focus:ring-primary-500 focus:border-primary-500 block w-full sm:text-sm border-slate-300 rounded-md pl-4 pr-8 py-3" placeholder="0">
                            <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                <span class="text-slate-500 sm:text-sm font-medium">sm</span>
                            </div>
                        </div>
                    </div>
                    <p class="mt-2 text-sm text-slate-500">Yangi rulon alohida saqlanadi va sanasi belgilanadi.</p>
                </div>

                <div class="pt-4 flex items-center justify-end">
                     <button type="submit" class="w-full sm:w-auto inline-flex justify-center py-3 px-6 border border-transparent shadow-sm text-base font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition-colors">
                        <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" /></svg>
                        Rulonni Qo'shish
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../src/templates/footer.php'; ?>
