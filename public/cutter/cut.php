<?php
require_once __DIR__ . '/../../src/auth.php';
require_once __DIR__ . '/../../src/db.php';
require_once __DIR__ . '/../../src/functions.php';

require_role('cutter');

$message = '';
$error = '';
$receipt = null;

// Handle Cut Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $roll_id = $_POST['roll_id'] ?? null;
    $customer_name = $_POST['customer_name'] ?? '';
    $length = $_POST['length'] ?? 0;

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

            // Record Cut
            $insertStmt = $db->prepare("
                INSERT INTO cuts (roll_id, material_id, user_id, customer_name, length_cut, price_sold, profit)
                VALUES (:rid, :mid, :uid, :cn, :lc, :ps, :prof)
            ");
            $insertStmt->bindValue(':rid', $roll_id, SQLITE3_INTEGER);
            $insertStmt->bindValue(':mid', $roll['material_id'], SQLITE3_INTEGER);
            $insertStmt->bindValue(':uid', $_SESSION['user_id'], SQLITE3_INTEGER);
            $insertStmt->bindValue(':cn', $customer_name, SQLITE3_TEXT);
            $insertStmt->bindValue(':lc', $length, SQLITE3_FLOAT);
            $insertStmt->bindValue(':ps', $price_sold, SQLITE3_FLOAT);
            $insertStmt->bindValue(':prof', $profit, SQLITE3_FLOAT);

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
// We want to group rolls by material in the UI or use dynamic fetching.
// For simplicity, we'll fetch all available rolls joined with material name.
$rolls_query = "
    SELECT r.id, r.current_length, m.name, m.width
    FROM rolls r
    JOIN materials m ON r.material_id = m.id
    WHERE r.current_length > 0
    ORDER BY m.name ASC, r.current_length DESC
";
$rolls = $db->query($rolls_query);

include __DIR__ . '/../../src/templates/header.php';
?>

<div class="max-w-3xl mx-auto bg-white p-6 rounded-lg shadow">
    <h2 class="text-2xl font-bold mb-6">Material Kesish</h2>

    <?php if ($message): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-6">
            <strong class="font-bold">Bajarildi!</strong>
            <span class="block sm:inline"><?php echo htmlspecialchars($message); ?></span>

            <?php if ($receipt): ?>
            <div class="mt-4 border-t border-green-200 pt-2">
                <p><strong>Mijoz:</strong> <?php echo htmlspecialchars($receipt['customer']); ?></p>
                <p><strong>Material:</strong> <?php echo htmlspecialchars($receipt['material']); ?></p>
                <p><strong>Kesildi:</strong> <?php echo $receipt['length']; ?> m</p>
                <p><strong>Jami Summa:</strong> <?php echo format_currency($receipt['total']); ?></p>
            </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-6">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <form method="POST">
        <div class="mb-6">
            <label class="block text-gray-700 font-bold mb-2">Rulonni Tanlang (Mavjud)</label>
            <select name="roll_id" class="block w-full bg-white border border-gray-400 hover:border-gray-500 px-4 py-2 pr-8 rounded shadow leading-tight focus:outline-none focus:shadow-outline" required>
                <option value="">-- Tanlang --</option>
                <?php while ($row = $rolls->fetchArray(SQLITE3_ASSOC)): ?>
                    <option value="<?php echo $row['id']; ?>">
                        <?php echo htmlspecialchars($row['name']); ?>
                        (Qoldiq: <?php echo $row['current_length']; ?> m, Eni: <?php echo $row['width']; ?> sm)
                    </option>
                <?php endwhile; ?>
            </select>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div>
                <label class="block text-gray-700 font-bold mb-2">Mijoz Ismi / Buyurtma ID</label>
                <input type="text" name="customer_name" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" placeholder="Mijoz ismi" required>
            </div>
            <div>
                <label class="block text-gray-700 font-bold mb-2">Kesish Uzunligi (metr)</label>
                <input type="number" step="0.01" name="length" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" placeholder="Masalan: 2.5" required>
            </div>
        </div>

        <div class="flex items-center justify-end">
            <button class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 px-6 rounded focus:outline-none focus:shadow-outline text-lg" type="submit">
                Kesishni Tasdiqlash
            </button>
        </div>
    </form>
</div>

<?php include __DIR__ . '/../../src/templates/footer.php'; ?>
