<?php
require_once __DIR__ . '/../../src/auth.php';
require_once __DIR__ . '/../../src/db.php';
require_once __DIR__ . '/../../src/functions.php';

require_role('admin');

$material = [
    'id' => '',
    'name' => '',
    'width' => 0,
    'purchase_price' => '',
    'selling_price' => '',
    'min_stock_warning' => 50,
    'min_stock_critical' => 30,
    'max_stock_level' => 100,
    'image_path' => ''
];

$error = '';
$is_edit = false;

if (isset($_GET['id'])) {
    $stmt = $db->prepare("SELECT * FROM materials WHERE id = :id");
    $stmt->bindValue(':id', $_GET['id'], SQLITE3_INTEGER);
    $result = $stmt->execute();
    $fetched = $result->fetchArray(SQLITE3_ASSOC);
    if ($fetched) {
        $material = $fetched;
        $is_edit = true;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';

    // Width (M + CM)
    $width_m = (float)($_POST['width_m'] ?? 0);
    $width_cm = (float)($_POST['width_cm'] ?? 0);
    $width = ($width_m * 100) + $width_cm; // Store in cm? Or keep using whatever "width" column was.
    // Wait, previous code used "width" as generic. Let's assume database stores width in CM for precision, or keep using meters if that was the convention.
    // The schema comment said "Width in cm or meters (display only mostly)".
    // Let's standardise: Store Width in CM.
    // Wait, let's stick to user inputs. If user enters 1m 50cm, that is 1.5 meters.
    // Let's store Width in CM to be safe and precise, or Meters?
    // User asked "width (with meter and cantimeter)".
    // Let's store as CM in DB for width.
    // But wait, "Height" (Length) is usually meters.
    // Let's convert everything to Meters for DB storage to be consistent with existing logic (Length is definitely meters).
    $width_total_meters = $width_m + ($width_cm / 100);

    $purchase_price = $_POST['purchase_price'] ?? 0;
    $selling_price = $_POST['selling_price'] ?? 0;
    $min_stock_warning = $_POST['min_stock_warning'] ?? 50;
    $min_stock_critical = $_POST['min_stock_critical'] ?? 30;
    $max_stock_level = $_POST['max_stock_level'] ?? 100;

    // Initial Roll (Height) - Only for New Materials
    $height_m = (float)($_POST['height_m'] ?? 0);
    $height_cm = (float)($_POST['height_cm'] ?? 0);
    $initial_length = $height_m + ($height_cm / 100);

    // Image Upload
    $image_path = $material['image_path'];
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploaded = upload_image($_FILES['image']);
        if ($uploaded) {
            $image_path = $uploaded;
        }
    }

    if (empty($name)) {
        $error = "Nom kiritilishi shart.";
    } else {
        if ($is_edit) {
            // Update
            $stmt = $db->prepare("UPDATE materials SET name=:n, width=:w, purchase_price=:pp, selling_price=:sp, min_stock_warning=:mw, min_stock_critical=:mc, max_stock_level=:ml, image_path=:i, created_at=:created_at WHERE id=:id");
            $stmt->bindValue(':id', $material['id'], SQLITE3_INTEGER);
        } else {
            // Insert
            $stmt = $db->prepare("INSERT INTO materials (name, width, purchase_price, selling_price, min_stock_warning, min_stock_critical, max_stock_level, image_path, created_at) VALUES (:n, :w, :pp, :sp, :mw, :mc, :ml, :i, :created_at)");
        }

        $stmt->bindValue(':n', $name, SQLITE3_TEXT);
        $stmt->bindValue(':w', $width_total_meters * 100); // Storing Width in CM for display (standard for fabric width like 150cm)
        $stmt->bindValue(':pp', $purchase_price, SQLITE3_FLOAT);
        $stmt->bindValue(':sp', $selling_price, SQLITE3_FLOAT);
        $stmt->bindValue(':mw', $min_stock_warning, SQLITE3_FLOAT);
        $stmt->bindValue(':mc', $min_stock_critical, SQLITE3_FLOAT);
        $stmt->bindValue(':ml', $max_stock_level, SQLITE3_FLOAT);
        $stmt->bindValue(':i', $image_path, SQLITE3_TEXT);
        $stmt->bindValue(':created_at', date('Y-m-d H:i:s'), SQLITE3_TEXT);

        if ($stmt->execute()) {
            // If NEW material and has initial length, create first roll
            if (!$is_edit && $initial_length > 0) {
                $new_material_id = $db->lastInsertRowID();
                $stmt_roll = $db->prepare("INSERT INTO rolls (material_id, original_length, current_length, created_at) VALUES (:mid, :len, :len, :created_at)");
                $stmt_roll->bindValue(':mid', $new_material_id, SQLITE3_INTEGER);
                $stmt_roll->bindValue(':len', $initial_length, SQLITE3_FLOAT);
                $stmt_roll->bindValue(':created_at', date('Y-m-d H:i:s'), SQLITE3_TEXT);
                $stmt_roll->execute();
            }

            header('Location: materials.php');
            exit;
        } else {
            $error = "Bazaga yozishda xatolik.";
        }
    }
}

include __DIR__ . '/../../src/templates/header.php';

// Prepare display values
$display_width_m = 0;
$display_width_cm = 0;
if ($material['width']) {
    // Stored in CM? Previous schema comment said "Width in cm or meters".
    // Let's assume previous data was random.
    // Going forward we store in CM.
    // If value is small (e.g. 1.5), it might be meters. If large (150), it is CM.
    // Heuristic: If < 10, treat as meters. Else CM.
    $val = $material['width'];
    if ($val < 10) { $val = $val * 100; } // Convert to CM
    $display_width_m = floor($val / 100);
    $display_width_cm = $val % 100;
}
?>

<div class="max-w-3xl mx-auto">
    <div class="md:flex md:items-center md:justify-between mb-8">
        <div class="flex-1 min-w-0">
            <h2 class="text-2xl font-bold leading-7 text-slate-900 sm:text-3xl sm:truncate">
                <?php echo $is_edit ? 'Materialni Tahrirlash' : 'Yangi Material Qo\'shish'; ?>
            </h2>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="rounded-md bg-red-50 p-4 mb-6 border border-red-200">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm leading-5 font-medium text-red-800">
                        Xatolik yuz berdi
                    </h3>
                    <div class="mt-2 text-sm leading-5 text-red-700">
                        <p><?php echo htmlspecialchars($error); ?></p>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" class="bg-white shadow-sm rounded-xl overflow-hidden border border-slate-200">
        <div class="px-6 py-6 sm:p-8 space-y-8">

            <!-- Basic Info Section -->
            <div>
                <h3 class="text-lg leading-6 font-medium text-slate-900 border-b border-slate-200 pb-2 mb-6">Asosiy Ma'lumotlar</h3>
                <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                    <div class="sm:col-span-4">
                        <label class="block text-sm font-medium text-slate-700">Material Nomi</label>
                        <div class="mt-1">
                            <input type="text" name="name" value="<?php echo htmlspecialchars($material['name']); ?>" class="shadow-sm focus:ring-primary-500 focus:border-primary-500 block w-full sm:text-sm border-slate-300 rounded-md py-2.5 px-3">
                        </div>
                    </div>

                    <div class="sm:col-span-6">
                        <label class="block text-sm font-medium text-slate-700 mb-1">Rasm (Opsional)</label>
                        <div class="flex items-center space-x-4">
                            <?php if ($material['image_path']): ?>
                                <img class="h-16 w-16 rounded-lg object-cover border border-slate-200" src="../<?php echo htmlspecialchars($material['image_path']); ?>" alt="Current">
                            <?php endif; ?>
                            <input type="file" name="image" class="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100 transition-colors">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Dimensions & Price Section -->
            <div>
                <h3 class="text-lg leading-6 font-medium text-slate-900 border-b border-slate-200 pb-2 mb-6">O'lchamlar va Narxlar</h3>
                <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-2">

                    <!-- Width Input (Split) -->
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Eni (Width)</label>
                        <div class="flex space-x-2">
                            <div class="relative rounded-md shadow-sm flex-1">
                                <input type="number" name="width_m" value="<?php echo $display_width_m; ?>" class="focus:ring-primary-500 focus:border-primary-500 block w-full sm:text-sm border-slate-300 rounded-md pl-3 pr-8 py-2.5" placeholder="0">
                                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                    <span class="text-slate-500 sm:text-sm">m</span>
                                </div>
                            </div>
                            <div class="relative rounded-md shadow-sm flex-1">
                                <input type="number" name="width_cm" value="<?php echo $display_width_cm; ?>" class="focus:ring-primary-500 focus:border-primary-500 block w-full sm:text-sm border-slate-300 rounded-md pl-3 pr-8 py-2.5" placeholder="0">
                                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                    <span class="text-slate-500 sm:text-sm">sm</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Initial Height (Only New) -->
                    <?php if (!$is_edit): ?>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Uzunligi (Height/Length) - <span class="text-primary-600">Birinchi Rulon</span></label>
                        <div class="flex space-x-2">
                            <div class="relative rounded-md shadow-sm flex-1">
                                <input type="number" name="height_m" class="focus:ring-primary-500 focus:border-primary-500 block w-full sm:text-sm border-slate-300 rounded-md pl-3 pr-8 py-2.5" placeholder="0">
                                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                    <span class="text-slate-500 sm:text-sm">m</span>
                                </div>
                            </div>
                            <div class="relative rounded-md shadow-sm flex-1">
                                <input type="number" name="height_cm" class="focus:ring-primary-500 focus:border-primary-500 block w-full sm:text-sm border-slate-300 rounded-md pl-3 pr-8 py-2.5" placeholder="0">
                                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                    <span class="text-slate-500 sm:text-sm">sm</span>
                                </div>
                            </div>
                        </div>
                        <p class="mt-1 text-xs text-slate-500">Agar hozir omborda bo'lmasa, bo'sh qoldiring.</p>
                    </div>
                    <?php else: ?>
                    <div class="flex items-center justify-center bg-slate-50 rounded-md border border-dashed border-slate-300">
                        <p class="text-sm text-slate-500">Qo'shimcha rulon qo'shish uchun "Kirim" bo'limiga o'ting.</p>
                    </div>
                    <?php endif; ?>

                    <!-- Prices -->
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Sotib Olish Narxi</label>
                        <div class="relative rounded-md shadow-sm">
                            <input type="number" step="0.01" name="purchase_price" value="<?php echo htmlspecialchars($material['purchase_price']); ?>" class="focus:ring-primary-500 focus:border-primary-500 block w-full sm:text-sm border-slate-300 rounded-md pl-3 pr-12 py-2.5">
                            <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                <span class="text-slate-500 sm:text-sm">so'm</span>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Sotish Narxi</label>
                        <div class="relative rounded-md shadow-sm">
                            <input type="number" step="0.01" name="selling_price" value="<?php echo htmlspecialchars($material['selling_price']); ?>" class="focus:ring-primary-500 focus:border-primary-500 block w-full sm:text-sm border-slate-300 rounded-md pl-3 pr-12 py-2.5">
                            <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                <span class="text-slate-500 sm:text-sm">so'm</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Limits Section -->
            <div>
                <h3 class="text-lg leading-6 font-medium text-slate-900 border-b border-slate-200 pb-2 mb-6">Ombor Limitlari (Metr)</h3>
                <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-3">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Ogohlantirish (<)</label>
                        <input type="number" step="0.1" name="min_stock_warning" value="<?php echo htmlspecialchars($material['min_stock_warning']); ?>" class="shadow-sm focus:ring-yellow-500 focus:border-yellow-500 block w-full sm:text-sm border-slate-300 rounded-md py-2.5 px-3">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Kritik (<)</label>
                        <input type="number" step="0.1" name="min_stock_critical" value="<?php echo htmlspecialchars($material['min_stock_critical']); ?>" class="shadow-sm focus:ring-red-500 focus:border-red-500 block w-full sm:text-sm border-slate-300 rounded-md py-2.5 px-3">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Max. Limit</label>
                        <input type="number" step="0.1" name="max_stock_level" value="<?php echo htmlspecialchars($material['max_stock_level']); ?>" class="shadow-sm focus:ring-primary-500 focus:border-primary-500 block w-full sm:text-sm border-slate-300 rounded-md py-2.5 px-3">
                    </div>
                </div>
            </div>

        </div>
        <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex items-center justify-end">
            <a href="materials.php" class="text-sm font-medium text-slate-600 hover:text-slate-500 mr-6">Bekor qilish</a>
            <button type="submit" class="inline-flex justify-center py-2.5 px-6 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition-colors">
                Saqlash
            </button>
        </div>
    </form>
</div>

<?php include __DIR__ . '/../../src/templates/footer.php'; ?>
