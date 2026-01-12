<?php
require_once __DIR__ . '/../../src/auth.php';
require_once __DIR__ . '/../../src/db.php';
require_once __DIR__ . '/../../src/functions.php';

require_any_role(['admin', 'cutter']);

$message = '';
$error = '';
$receipt = null;

// Handle Cut Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $roll_id = $_POST['roll_id'] ?? null;
    $customer_name = $_POST['customer_name'] ?? '';

    // Split M/CM
    $length_m = (float)($_POST['length_m'] ?? 0);
    $length_cm = (float)($_POST['length_cm'] ?? 0);
    $length = $length_m + ($length_cm / 100);

    if (!$roll_id || empty($customer_name) || $length <= 0) {
        $error = "Iltimos, barcha ma'lumotlarni to'g'ri kiriting.";
    } else {
        // Fetch roll details with material info to verify stock and calculate price
        $stmt = $db->prepare("
            SELECT r.*, m.selling_price, m.purchase_price, m.name as material_name
            FROM rolls r
            JOIN materials m ON r.material_id = m.id
            WHERE r.id = :rid
        ");
        $stmt->bindValue(':rid', $roll_id, SQLITE3_INTEGER);
        $result = $stmt->execute();
        $roll = $result->fetchArray(SQLITE3_ASSOC);

        if (!$roll) {
            $error = "Rulon topilmadi.";
        } elseif ($roll['current_length'] < $length) {
            $error = "Tanlangan rulonda yetarli material yo'q. Mavjud: " . $roll['current_length'] . " m.";
        } else {
            // Process Transaction
            $price_sold = $length * $roll['selling_price'];
            $cost_price = $length * $roll['purchase_price'];
            $profit = $price_sold - $cost_price;

            // Update Roll
            $new_length = $roll['current_length'] - $length;
            $updateStmt = $db->prepare("UPDATE rolls SET current_length = :nl WHERE id = :rid");
            $updateStmt->bindValue(':nl', $new_length, SQLITE3_FLOAT);
            $updateStmt->bindValue(':rid', $roll_id, SQLITE3_INTEGER);

            // Record Cut - Explicitly set created_at to PHP time (Tashkent)
            $insertStmt = $db->prepare("
                INSERT INTO cuts (roll_id, material_id, user_id, customer_name, length_cut, price_sold, profit, created_at)
                VALUES (:rid, :mid, :uid, :cn, :lc, :ps, :prof, :created_at)
            ");
            $insertStmt->bindValue(':rid', $roll_id, SQLITE3_INTEGER);
            $insertStmt->bindValue(':mid', $roll['material_id'], SQLITE3_INTEGER);
            $insertStmt->bindValue(':uid', $_SESSION['user_id'], SQLITE3_INTEGER);
            $insertStmt->bindValue(':cn', $customer_name, SQLITE3_TEXT);
            $insertStmt->bindValue(':lc', $length, SQLITE3_FLOAT);
            $insertStmt->bindValue(':ps', $price_sold, SQLITE3_FLOAT);
            $insertStmt->bindValue(':prof', $profit, SQLITE3_FLOAT);
            $insertStmt->bindValue(':created_at', date('Y-m-d H:i:s'), SQLITE3_TEXT);

            // Execute Transaction (simulate transaction since sqlite3 in php handles auto-commit usually, but ideally wrap in transaction)
            $db->exec('BEGIN TRANSACTION');
            $u1 = $updateStmt->execute();
            $u2 = $insertStmt->execute();

            if ($u1 && $u2) {
                $db->exec('COMMIT');
                $message = "Kesish muvaffaqiyatli bajarildi!";
                $receipt = [
                    'customer' => $customer_name,
                    'material' => $roll['material_name'],
                    'length' => $length,
                    'total' => $price_sold
                ];
            } else {
                $db->exec('ROLLBACK');
                $error = "Xatolik yuz berdi.";
            }
        }
    }
}

// Fetch Materials and their Rolls
$rolls_query = "
    SELECT r.id, r.current_length, r.created_at, m.name, m.width
    FROM rolls r
    JOIN materials m ON r.material_id = m.id
    WHERE r.current_length > 0
    ORDER BY m.name ASC, r.created_at ASC
";
$rolls = $db->query($rolls_query);

include __DIR__ . '/../../src/templates/header.php';
?>

<div class="max-w-3xl mx-auto">
    <div class="bg-white shadow-sm rounded-xl overflow-hidden border border-slate-200">
        <div class="px-6 py-8">
            <h2 class="text-2xl font-bold text-slate-900 mb-6">Material Kesish</h2>

            <?php if ($message): ?>
                <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-md mb-6">
                    <div class="flex items-center mb-2">
                        <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                        <strong class="font-semibold">Bajarildi!</strong>
                    </div>
                    <span class="block"><?php echo htmlspecialchars($message); ?></span>

                    <?php if ($receipt): ?>
                    <div class="mt-4 bg-white bg-opacity-60 rounded p-3 text-sm border border-green-200">
                        <div class="grid grid-cols-2 gap-2">
                            <span class="text-green-800 font-medium">Mijoz:</span>
                            <span class="text-green-900"><?php echo htmlspecialchars($receipt['customer']); ?></span>

                            <span class="text-green-800 font-medium">Material:</span>
                            <span class="text-green-900"><?php echo htmlspecialchars($receipt['material']); ?></span>

                            <span class="text-green-800 font-medium">Kesildi:</span>
                            <span class="text-green-900"><?php echo $receipt['length']; ?> m</span>

                            <span class="text-green-800 font-medium">Jami Summa:</span>
                            <span class="text-green-900 font-bold"><?php echo format_currency($receipt['total']); ?></span>
                        </div>
                    </div>
                    <?php endif; ?>
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
                    <label class="block text-sm font-medium text-slate-700 mb-1">Rulonni Tanlang (Mavjud)</label>
                    <div class="relative">
                        <select name="roll_id" class="block w-full pl-3 pr-10 py-3 text-base border-slate-300 focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm rounded-md shadow-sm">
                            <option value="">-- Tanlang --</option>
                            <?php while ($row = $rolls->fetchArray(SQLITE3_ASSOC)):
                                $date_badge = date('d-M', strtotime($row['created_at']));
                            ?>
                                <option value="<?php echo $row['id']; ?>">
                                    <?php echo htmlspecialchars($row['name']); ?>
                                    (Qoldiq: <?php echo $row['current_length']; ?>m | Sana: <?php echo $date_badge; ?>)
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Mijoz Ismi / Buyurtma ID</label>
                    <input type="text" name="customer_name" class="focus:ring-primary-500 focus:border-primary-500 block w-full sm:text-sm border-slate-300 rounded-md py-3 px-4" placeholder="Mijoz ismi">
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Kesish Uzunligi</label>
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
                </div>

                <div class="pt-4 flex items-center justify-end">
                    <button type="submit" class="w-full sm:w-auto inline-flex justify-center py-3 px-8 border border-transparent shadow-sm text-lg font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition-colors">
                        <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.121 14.121L19 19m-7-7l7-7m-7 7l-2.879 2.879M12 12L9.121 9.121m0 5.758a3 3 0 10-4.243 4.243 3 3 0 004.243-4.243zm0-5.758a3 3 0 10-4.243-4.243 3 3 0 004.243 4.243z" /></svg>
                        Kesishni Tasdiqlash
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../src/templates/footer.php'; ?>
