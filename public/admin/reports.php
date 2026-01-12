<?php
require_once __DIR__ . '/../../src/auth.php';
require_once __DIR__ . '/../../src/db.php';
require_once __DIR__ . '/../../src/functions.php';

require_role('admin');

// Date Filter
$start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
$end_date = $_GET['end_date'] ?? date('Y-m-d');

// 1. Stock Alerts (Real-time, not filtered by date)
$alerts_query = "
    SELECT m.*,
    (SELECT COALESCE(SUM(current_length), 0) FROM rolls WHERE material_id = m.id) as total_stock
    FROM materials m
    WHERE total_stock < min_stock_warning
    ORDER BY total_stock ASC
";
$alerts = $db->query($alerts_query);

// 2. Reorder Report (Real-time)
$reorder_query = "
    SELECT m.name, m.max_stock_level,
    (SELECT COALESCE(SUM(current_length), 0) FROM rolls WHERE material_id = m.id) as total_stock
    FROM materials m
    WHERE total_stock < max_stock_level
    ORDER BY (m.max_stock_level - total_stock) DESC
";
$reorder = $db->query($reorder_query);

// 3. Transactions Filtered by Date
$transactions_query = "
    SELECT c.*, m.name as material_name, u.username
    FROM cuts c
    JOIN materials m ON c.material_id = m.id
    JOIN users u ON c.user_id = u.id
    WHERE date(c.created_at) BETWEEN :start AND :end
    ORDER BY c.created_at DESC
";
$stmt = $db->prepare($transactions_query);
$stmt->bindValue(':start', $start_date, SQLITE3_TEXT);
$stmt->bindValue(':end', $end_date, SQLITE3_TEXT);
$transactions = $stmt->execute();

include __DIR__ . '/../../src/templates/header.php';
?>

<div class="md:flex md:items-center md:justify-between mb-8">
    <div class="flex-1 min-w-0">
        <h2 class="text-2xl font-bold leading-7 text-slate-900 sm:text-3xl sm:truncate">
            Hisobotlar
        </h2>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-10">
    <!-- Stock Alerts -->
    <div>
        <h2 class="text-lg font-semibold text-slate-900 mb-4 flex items-center">
            <svg class="w-5 h-5 mr-2 text-yellow-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
            Zahira Ogohlantirishlari
        </h2>
        <div class="bg-white shadow-sm rounded-xl overflow-hidden border border-slate-200">
            <ul class="divide-y divide-slate-200">
                <?php
                $has_alerts = false;
                while ($row = $alerts->fetchArray(SQLITE3_ASSOC)):
                    $has_alerts = true;
                    $is_critical = $row['total_stock'] < $row['min_stock_critical'];
                    $badge_color = $is_critical ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800';
                ?>
                <li class="px-6 py-4 flex items-center justify-between hover:bg-slate-50 transition-colors">
                    <span class="text-sm font-medium text-slate-900"><?php echo htmlspecialchars($row['name']); ?></span>
                    <span class="px-2.5 py-0.5 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $badge_color; ?>">
                        <?php echo $row['total_stock']; ?> m qoldi
                    </span>
                </li>
                <?php endwhile; ?>
                <?php if (!$has_alerts): ?>
                <li class="px-6 py-8 text-center text-sm text-slate-500 italic">Ogohlantirishlar yo'q. Hammasi joyida.</li>
                <?php endif; ?>
            </ul>
        </div>
    </div>

    <!-- Reorder Report -->
    <div>
        <h2 class="text-lg font-semibold text-slate-900 mb-4 flex items-center">
            <svg class="w-5 h-5 mr-2 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" /></svg>
            Buyurtma Tavsiyasi
        </h2>
        <div class="bg-white shadow-sm rounded-xl overflow-hidden border border-slate-200">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Material</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Mavjud</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Buyurtma</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-slate-200">
                    <?php while ($row = $reorder->fetchArray(SQLITE3_ASSOC)):
                        $needed = $row['max_stock_level'] - $row['total_stock'];
                        if ($needed <= 0) continue;
                    ?>
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-slate-900"><?php echo htmlspecialchars($row['name']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500"><?php echo $row['total_stock']; ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-primary-600 font-bold">+<?php echo $needed; ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Transactions Filter -->
<div class="mb-6 flex flex-col sm:flex-row sm:items-end justify-between gap-4">
    <h2 class="text-lg font-semibold text-slate-900 flex items-center">
        <svg class="w-5 h-5 mr-2 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
        Kesish Tarixi
    </h2>
    <form class="flex space-x-2 bg-white p-2 rounded-lg shadow-sm border border-slate-200">
        <div class="relative">
            <input type="date" name="start_date" value="<?php echo $start_date; ?>" class="border-slate-300 rounded-md text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 py-1.5 pl-3 pr-2">
        </div>
        <span class="text-slate-400 self-center">-</span>
        <div class="relative">
            <input type="date" name="end_date" value="<?php echo $end_date; ?>" class="border-slate-300 rounded-md text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 py-1.5 pl-3 pr-2">
        </div>
        <button type="submit" class="bg-primary-600 text-white px-3 py-1.5 rounded-md text-sm font-medium hover:bg-primary-700 shadow-sm transition-colors">Filtrlash</button>
    </form>
</div>

<!-- Transactions Table -->
<div class="bg-white shadow-sm rounded-xl overflow-hidden border border-slate-200 mb-8">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Sana</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Material</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Mijoz</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Miqdor</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Summa</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Kesuvchi</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-slate-200">
                <?php
                $has_trans = false;
                while ($row = $transactions->fetchArray(SQLITE3_ASSOC)):
                    $has_trans = true;
                ?>
                <tr class="hover:bg-slate-50 transition-colors">
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500"><?php echo date('d.m.Y H:i', strtotime($row['created_at'])); ?></td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-slate-900"><?php echo htmlspecialchars($row['material_name']); ?></td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500"><?php echo htmlspecialchars($row['customer_name']); ?></td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-700 font-medium"><?php echo $row['length_cut']; ?> m</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-primary-600 font-bold"><?php echo format_currency($row['price_sold']); ?></td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500"><?php echo htmlspecialchars($row['username']); ?></td>
                </tr>
                <?php endwhile; ?>
                <?php if (!$has_trans): ?>
                <tr>
                    <td colspan="6" class="px-6 py-10 text-center text-sm text-slate-400 italic">Ushbu sana oralig'ida ma'lumot topilmadi.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../../src/templates/footer.php'; ?>
