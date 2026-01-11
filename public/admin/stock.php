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
    $length = $_POST['length'] ?? 0;

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

<div class="max-w-xl mx-auto bg-white p-6 rounded-lg shadow">
    <h2 class="text-2xl font-bold mb-6">Omborga Kirim (Yangi Rulon)</h2>

    <?php if ($message): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <form method="POST">
        <div class="mb-4">
            <label class="block text-gray-700 font-bold mb-2">Materialni Tanlang</label>
            <select name="material_id" class="block appearance-none w-full bg-white border border-gray-400 hover:border-gray-500 px-4 py-2 pr-8 rounded shadow leading-tight focus:outline-none focus:shadow-outline">
                <option value="">-- Tanlang --</option>
                <?php while ($row = $materials->fetchArray(SQLITE3_ASSOC)): ?>
                    <option value="<?php echo $row['id']; ?>"><?php echo htmlspecialchars($row['name']); ?></option>
                <?php endwhile; ?>
            </select>
        </div>

        <div class="mb-6">
            <label class="block text-gray-700 font-bold mb-2">Uzunligi (metr)</label>
            <input type="number" step="0.01" name="length" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" placeholder="Masalan: 50">
        </div>

        <div class="flex items-center justify-end">
            <button class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline" type="submit">
                Qo'shish
            </button>
        </div>
    </form>
</div>

<?php include __DIR__ . '/../../src/templates/footer.php'; ?>
