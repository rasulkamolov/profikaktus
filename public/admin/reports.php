<?php
require_once __DIR__ . '/../../src/auth.php';
require_once __DIR__ . '/../../src/db.php';
require_once __DIR__ . '/../../src/functions.php';

require_role('admin');

// 1. Stock Alerts
$alerts_query = "
    SELECT m.*,
    (SELECT COALESCE(SUM(current_length), 0) FROM rolls WHERE material_id = m.id) as total_stock
    FROM materials m
    WHERE total_stock < min_stock_warning
    ORDER BY total_stock ASC
";
$alerts = $db->query($alerts_query);

// 2. Reorder Report (Max - Current)
$reorder_query = "
    SELECT m.name, m.max_stock_level,
    (SELECT COALESCE(SUM(current_length), 0) FROM rolls WHERE material_id = m.id) as total_stock
    FROM materials m
    WHERE total_stock < max_stock_level
    ORDER BY (m.max_stock_level - total_stock) DESC
";
$reorder = $db->query($reorder_query);

// 3. Profit/Loss Report (Overall)
// Aggregate profit
$profit_data = $db->querySingle("SELECT SUM(profit) as total_profit, SUM(price_sold) as total_revenue, COUNT(*) as total_sales FROM cuts", true);

// Recent Transactions
$transactions = $db->query("
    SELECT c.*, m.name as material_name, u.username
    FROM cuts c
    JOIN materials m ON c.material_id = m.id
    JOIN users u ON c.user_id = u.id
    ORDER BY c.created_at DESC
    LIMIT 20
");

include __DIR__ . '/../../src/templates/header.php';
?>

<div class="mb-8">
    <h1 class="text-3xl font-bold text-gray-900">Hisobotlar</h1>
</div>

<!-- Financial Summary -->
<div class="mb-10">
    <h2 class="text-xl font-bold text-gray-800 mb-4">Moliyaviy Ko'rsatkichlar (Jami)</h2>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white overflow-hidden shadow rounded-lg p-5">
            <dt class="text-sm font-medium text-gray-500 truncate">Jami Savdo</dt>
            <dd class="mt-1 text-3xl font-semibold text-gray-900"><?php echo format_currency($profit_data['total_revenue'] ?? 0); ?></dd>
        </div>
        <div class="bg-white overflow-hidden shadow rounded-lg p-5">
            <dt class="text-sm font-medium text-gray-500 truncate">Sof Foyda</dt>
            <dd class="mt-1 text-3xl font-semibold text-green-600"><?php echo format_currency($profit_data['total_profit'] ?? 0); ?></dd>
        </div>
        <div class="bg-white overflow-hidden shadow rounded-lg p-5">
            <dt class="text-sm font-medium text-gray-500 truncate">Bitimlar Soni</dt>
            <dd class="mt-1 text-3xl font-semibold text-indigo-600"><?php echo $profit_data['total_sales'] ?? 0; ?></dd>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-10">
    <!-- Stock Alerts -->
    <div>
        <h2 class="text-xl font-bold text-gray-800 mb-4">Zahira Ogohlantirishlari</h2>
        <div class="bg-white shadow overflow-hidden sm:rounded-lg">
            <ul class="divide-y divide-gray-200">
                <?php
                $has_alerts = false;
                while ($row = $alerts->fetchArray(SQLITE3_ASSOC)):
                    $has_alerts = true;
                    $is_critical = $row['total_stock'] < $row['min_stock_critical'];
                    $badge_color = $is_critical ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800';
                ?>
                <li class="px-4 py-4 flex items-center justify-between">
                    <span class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($row['name']); ?></span>
                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $badge_color; ?>">
                        <?php echo $row['total_stock']; ?> m qoldi
                    </span>
                </li>
                <?php endwhile; ?>
                <?php if (!$has_alerts): ?>
                <li class="px-4 py-4 text-sm text-gray-500">Ogohlantirishlar yo'q. Hammasi joyida.</li>
                <?php endif; ?>
            </ul>
        </div>
    </div>

    <!-- Reorder Report -->
    <div>
        <h2 class="text-xl font-bold text-gray-800 mb-4">Buyurtma Qilinishi Kerak (Tavsiya)</h2>
        <div class="bg-white shadow overflow-hidden sm:rounded-lg">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Material</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Mavjud</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Buyurtma</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php while ($row = $reorder->fetchArray(SQLITE3_ASSOC)):
                        $needed = $row['max_stock_level'] - $row['total_stock'];
                        if ($needed <= 0) continue;
                    ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo htmlspecialchars($row['name']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo $row['total_stock']; ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-indigo-600 font-bold">+<?php echo $needed; ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Recent Transactions -->
<div>
    <h2 class="text-xl font-bold text-gray-800 mb-4">So'nggi Kesilganlar (Tarix)</h2>
    <div class="bg-white shadow overflow-hidden sm:rounded-lg overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Sana</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Material</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Mijoz</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Miqdor</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Summa</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kesuvchi</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php while ($row = $transactions->fetchArray(SQLITE3_ASSOC)): ?>
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo $row['created_at']; ?></td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo htmlspecialchars($row['material_name']); ?></td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($row['customer_name']); ?></td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo $row['length_cut']; ?> m</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo format_currency($row['price_sold']); ?></td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($row['username']); ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../../src/templates/footer.php'; ?>
