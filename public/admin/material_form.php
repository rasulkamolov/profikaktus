<?php
require_once __DIR__ . '/../../src/auth.php';
require_once __DIR__ . '/../../src/db.php';
require_once __DIR__ . '/../../src/functions.php';

require_role('admin');

$material = [
    'id' => '',
    'name' => '',
    'width' => '',
    'purchase_price' => '',
    'selling_price' => '',
    'min_stock_warning' => 50,
    'min_stock_critical' => 30,
    'max_stock_level' => 100,
    'image_path' => ''
];

$error = '';

if (isset($_GET['id'])) {
    $stmt = $db->prepare("SELECT * FROM materials WHERE id = :id");
    $stmt->bindValue(':id', $_GET['id'], SQLITE3_INTEGER);
    $result = $stmt->execute();
    $fetched = $result->fetchArray(SQLITE3_ASSOC);
    if ($fetched) {
        $material = $fetched;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $width = $_POST['width'] ?? 0;
    $purchase_price = $_POST['purchase_price'] ?? 0;
    $selling_price = $_POST['selling_price'] ?? 0;
    $min_stock_warning = $_POST['min_stock_warning'] ?? 50;
    $min_stock_critical = $_POST['min_stock_critical'] ?? 30;
    $max_stock_level = $_POST['max_stock_level'] ?? 100;

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
        if ($material['id']) {
            // Update
            $stmt = $db->prepare("UPDATE materials SET name=:n, width=:w, purchase_price=:pp, selling_price=:sp, min_stock_warning=:mw, min_stock_critical=:mc, max_stock_level=:ml, image_path=:i WHERE id=:id");
            $stmt->bindValue(':id', $material['id'], SQLITE3_INTEGER);
        } else {
            // Insert
            $stmt = $db->prepare("INSERT INTO materials (name, width, purchase_price, selling_price, min_stock_warning, min_stock_critical, max_stock_level, image_path) VALUES (:n, :w, :pp, :sp, :mw, :mc, :ml, :i)");
        }

        $stmt->bindValue(':n', $name, SQLITE3_TEXT);
        $stmt->bindValue(':w', $width, SQLITE3_FLOAT);
        $stmt->bindValue(':pp', $purchase_price, SQLITE3_FLOAT);
        $stmt->bindValue(':sp', $selling_price, SQLITE3_FLOAT);
        $stmt->bindValue(':mw', $min_stock_warning, SQLITE3_FLOAT);
        $stmt->bindValue(':mc', $min_stock_critical, SQLITE3_FLOAT);
        $stmt->bindValue(':ml', $max_stock_level, SQLITE3_FLOAT);
        $stmt->bindValue(':i', $image_path, SQLITE3_TEXT);

        if ($stmt->execute()) {
            header('Location: /admin/materials.php');
            exit;
        } else {
            $error = "Bazaga yozishda xatolik.";
        }
    }
}

include __DIR__ . '/../../src/templates/header.php';
?>

<div class="max-w-2xl mx-auto bg-white p-6 rounded-lg shadow">
    <h2 class="text-2xl font-bold mb-6"><?php echo $material['id'] ? 'Materialni Tahrirlash' : 'Yangi Material Qo\'shish'; ?></h2>

    <?php if ($error): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <div class="mb-4">
            <label class="block text-gray-700 font-bold mb-2">Nomi</label>
            <input type="text" name="name" value="<?php echo htmlspecialchars($material['name']); ?>" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
        </div>

        <div class="grid grid-cols-2 gap-4 mb-4">
            <div>
                <label class="block text-gray-700 font-bold mb-2">Kengligi (sm)</label>
                <input type="number" step="0.01" name="width" value="<?php echo htmlspecialchars($material['width']); ?>" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
            </div>
            <div>
                <label class="block text-gray-700 font-bold mb-2">Rasm</label>
                <input type="file" name="image" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                <?php if ($material['image_path']): ?>
                    <p class="text-xs mt-1">Joriy: <?php echo htmlspecialchars($material['image_path']); ?></p>
                <?php endif; ?>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4 mb-4">
            <div>
                <label class="block text-gray-700 font-bold mb-2">Sotib Olish Narxi (so'm)</label>
                <input type="number" step="0.01" name="purchase_price" value="<?php echo htmlspecialchars($material['purchase_price']); ?>" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
            </div>
            <div>
                <label class="block text-gray-700 font-bold mb-2">Sotish Narxi (so'm)</label>
                <input type="number" step="0.01" name="selling_price" value="<?php echo htmlspecialchars($material['selling_price']); ?>" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
            </div>
        </div>

        <h3 class="text-lg font-semibold mt-6 mb-3">Ombor Limitlari (metr)</h3>
        <div class="grid grid-cols-3 gap-4 mb-6">
            <div>
                <label class="block text-gray-700 text-sm font-bold mb-2">Ogohlantirish (<)</label>
                <input type="number" step="0.1" name="min_stock_warning" value="<?php echo htmlspecialchars($material['min_stock_warning']); ?>" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
            </div>
            <div>
                <label class="block text-gray-700 text-sm font-bold mb-2">Kritik (<)</label>
                <input type="number" step="0.1" name="min_stock_critical" value="<?php echo htmlspecialchars($material['min_stock_critical']); ?>" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
            </div>
            <div>
                <label class="block text-gray-700 text-sm font-bold mb-2">Max. Limit</label>
                <input type="number" step="0.1" name="max_stock_level" value="<?php echo htmlspecialchars($material['max_stock_level']); ?>" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
            </div>
        </div>

        <div class="flex items-center justify-end">
            <button class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline" type="submit">
                Saqlash
            </button>
        </div>
    </form>
</div>

<?php include __DIR__ . '/../../src/templates/footer.php'; ?>
