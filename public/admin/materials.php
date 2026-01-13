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

// Fetch Materials with Total Stock and Rolls
$query = "
    SELECT m.*,
    (SELECT COALESCE(SUM(current_length), 0) FROM rolls WHERE material_id = m.id) as total_stock
    FROM materials m
    ORDER BY m.name ASC
";
$results = $db->query($query);

include __DIR__ . '/../../src/templates/header.php';
?>

<div class="md:flex md:items-center md:justify-between mb-8">
    <div class="flex-1 min-w-0">
        <h2 class="text-2xl font-bold leading-7 text-slate-900 sm:text-3xl sm:truncate">
            Materiallar Ro'yxati
        </h2>
    </div>
    <div class="mt-4 flex md:mt-0 md:ml-4">
        <a href="material_form.php" class="ml-3 inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition-colors">
            <!-- Icon: Plus -->
            <svg class="-ml-1 mr-2 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" /></svg>
            Yangi Qo'shish
        </a>
    </div>
</div>

<div class="bg-white shadow-sm rounded-xl overflow-hidden border border-slate-200">
    <ul class="divide-y divide-slate-200">
        <?php while ($row = $results->fetchArray(SQLITE3_ASSOC)): ?>
            <?php
                $stockClass = 'bg-green-100 text-green-800 border-green-200';
                if ($row['total_stock'] < $row['min_stock_critical']) {
                    $stockClass = 'bg-red-100 text-red-800 border-red-200';
                } elseif ($row['total_stock'] < $row['min_stock_warning']) {
                    $stockClass = 'bg-yellow-100 text-yellow-800 border-yellow-200';
                }

                // Fetch Rolls for this material
                $rolls_stmt = $db->prepare("SELECT * FROM rolls WHERE material_id = :mid AND current_length > 0 ORDER BY created_at DESC");
                $rolls_stmt->bindValue(':mid', $row['id'], SQLITE3_INTEGER);
                $rolls_res = $rolls_stmt->execute();
            ?>
            <li class="hover:bg-slate-50 transition-colors">
                <div class="px-6 py-6">
                    <div class="flex items-start justify-between">
                        <div class="flex items-center">
                            <?php if ($row['image_path']): ?>
                                <img class="h-16 w-16 rounded-lg object-cover border border-slate-200 mr-5" src="../<?php echo htmlspecialchars($row['image_path']); ?>" alt="">
                            <?php else: ?>
                                <div class="h-16 w-16 rounded-lg bg-slate-100 border border-slate-200 flex items-center justify-center mr-5">
                                    <svg class="h-8 w-8 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                </div>
                            <?php endif; ?>
                            <div>
                                <p class="text-lg font-semibold text-slate-900"><?php echo htmlspecialchars($row['name']); ?></p>
                                <div class="mt-1 flex items-center text-sm text-slate-500 space-x-4">
                                    <span class="flex items-center">
                                        <!-- Icon: Scale -->
                                        <svg class="flex-shrink-0 mr-1.5 h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3" /></svg>
                                        Eni: <?php echo $row['width']; ?> sm
                                    </span>
                                    <span class="flex items-center">
                                        <!-- Icon: Tag -->
                                        <svg class="flex-shrink-0 mr-1.5 h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" /></svg>
                                        Sotish: <?php echo format_currency($row['selling_price']); ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="flex flex-col items-end">
                            <span class="px-3 py-1 inline-flex text-sm leading-5 font-semibold rounded-full border <?php echo $stockClass; ?>">
                                Jami: <?php echo $row['total_stock'] . ' ' . $row['unit']; ?>
                            </span>

                            <div class="mt-4 flex items-center space-x-3">
                                <a href="material_form.php?id=<?php echo $row['id']; ?>" class="text-primary-600 hover:text-primary-900 text-sm font-medium">Tahrirlash</a>
                                <span class="text-slate-300">|</span>
                                <form method="POST" onsubmit="return confirm('Haqiqatan ham o\'chirmoqchimisiz?');" class="inline">
                                    <input type="hidden" name="delete_id" value="<?php echo $row['id']; ?>">
                                    <button type="submit" class="text-red-600 hover:text-red-900 text-sm font-medium">O'chirish</button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Individual Rolls List -->
                    <div class="mt-6 border-t border-slate-100 pt-4">
                        <h4 class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-3">Mavjud Rulonlar</h4>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                            <?php
                            $has_rolls = false;
                            while ($roll = $rolls_res->fetchArray(SQLITE3_ASSOC)):
                                $has_rolls = true;
                                $date = date('d-M, Y', strtotime($roll['created_at']));
                            ?>
                                <div class="flex items-center justify-between bg-slate-50 border border-slate-200 rounded-md px-3 py-2 text-sm">
                                    <div class="flex items-center">
                                        <!-- Icon: Calendar Badge -->
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-white border border-slate-200 text-slate-600 mr-2 shadow-sm">
                                            <svg class="mr-1 h-3 w-3 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                            <?php echo $date; ?>
                                        </span>
                                    </div>
                                    <span class="font-bold text-slate-800"><?php echo $roll['current_length']; ?> m</span>
                                </div>
                            <?php endwhile; ?>
                            <?php if (!$has_rolls): ?>
                                <p class="text-sm text-slate-400 italic col-span-full">Hozirda rulonlar yo'q.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </li>
        <?php endwhile; ?>
    </ul>
</div>

<?php include __DIR__ . '/../../src/templates/footer.php'; ?>
