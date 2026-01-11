<?php
require_once __DIR__ . '/../../src/auth.php';
require_once __DIR__ . '/../../src/db.php';
require_once __DIR__ . '/../../src/functions.php';

require_role('admin');

// Handle Deletion
if (isset($_POST['delete_id'])) {
    $stmt = $db->prepare("DELETE FROM materials WHERE id = :id");
    $stmt->bindValue(':id', $_POST['delete_id'], SQLITE3_INTEGER);
    $stmt->execute();
    header('Location: materials.php');
    exit;
}

// Fetch Materials with Total Stock
$query = "
    SELECT m.*,
    (SELECT COALESCE(SUM(current_length), 0) FROM rolls WHERE material_id = m.id) as total_stock
    FROM materials m
    ORDER BY m.name ASC
";
$results = $db->query($query);

include __DIR__ . '/../../src/templates/header.php';
?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-900">Materiallar Ro'yxati</h1>
    <a href="material_form.php" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded">
        Yangi Qo'shish
    </a>
</div>

<div class="bg-white shadow overflow-hidden sm:rounded-lg">
    <ul class="divide-y divide-gray-200">
        <?php while ($row = $results->fetchArray(SQLITE3_ASSOC)): ?>
            <?php
                $stockClass = 'bg-green-100 text-green-800';
                if ($row['total_stock'] < $row['min_stock_critical']) {
                    $stockClass = 'bg-red-100 text-red-800';
                } elseif ($row['total_stock'] < $row['min_stock_warning']) {
                    $stockClass = 'bg-yellow-100 text-yellow-800';
                }
            ?>
            <li>
                <div class="px-4 py-4 sm:px-6">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <?php if ($row['image_path']): ?>
                                <img class="h-12 w-12 rounded-full object-cover mr-4" src="../<?php echo htmlspecialchars($row['image_path']); ?>" alt="">
                            <?php else: ?>
                                <div class="h-12 w-12 rounded-full bg-gray-200 flex items-center justify-center mr-4">
                                    <span class="text-gray-500 text-xs">No img</span>
                                </div>
                            <?php endif; ?>
                            <p class="text-sm font-medium text-indigo-600 truncate"><?php echo htmlspecialchars($row['name']); ?></p>
                        </div>
                        <div class="ml-2 flex-shrink-0 flex">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $stockClass; ?>">
                                Ombor: <?php echo $row['total_stock'] . ' ' . $row['unit']; ?>
                            </span>
                        </div>
                    </div>
                    <div class="mt-2 sm:flex sm:justify-between">
                        <div class="sm:flex">
                            <p class="flex items-center text-sm text-gray-500 mr-6">
                                Kengligi: <?php echo $row['width']; ?> sm
                            </p>
                            <p class="flex items-center text-sm text-gray-500 mr-6">
                                Sotish: <?php echo format_currency($row['selling_price']); ?>
                            </p>
                        </div>
                        <div class="mt-2 flex items-center text-sm sm:mt-0">
                            <a href="material_form.php?id=<?php echo $row['id']; ?>" class="text-indigo-600 hover:text-indigo-900 mr-4">Tahrirlash</a>
                            <form method="POST" onsubmit="return confirm('Haqiqatan ham o\'chirmoqchimisiz?');" class="inline">
                                <input type="hidden" name="delete_id" value="<?php echo $row['id']; ?>">
                                <button type="submit" class="text-red-600 hover:text-red-900">O'chirish</button>
                            </form>
                        </div>
                    </div>
                </div>
            </li>
        <?php endwhile; ?>
    </ul>
</div>

<?php include __DIR__ . '/../../src/templates/footer.php'; ?>
