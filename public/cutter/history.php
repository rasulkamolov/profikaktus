<?php
require_once __DIR__ . '/../../src/auth.php';
require_once __DIR__ . '/../../src/db.php';
require_once __DIR__ . '/../../src/functions.php';

require_role('cutter');

$user_id = $_SESSION['user_id'];
$query = "
    SELECT c.*, m.name as material_name
    FROM cuts c
    JOIN materials m ON c.material_id = m.id
    WHERE c.user_id = :uid
    ORDER BY c.created_at DESC
    LIMIT 50
";
$stmt = $db->prepare($query);
$stmt->bindValue(':uid', $user_id, SQLITE3_INTEGER);
$results = $stmt->execute();

include __DIR__ . '/../../src/templates/header.php';
?>

<div class="max-w-4xl mx-auto">
    <div class="mb-8">
        <h2 class="text-2xl font-bold text-slate-900">Mening Kesish Tarixim</h2>
        <p class="mt-1 text-sm text-slate-500">Oxirgi 50 ta amal</p>
    </div>

    <div class="bg-white shadow-sm rounded-xl overflow-hidden border border-slate-200">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Sana</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Material</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Mijoz</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Kesilgan</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Summa</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-slate-200">
                    <?php
                    $has_rows = false;
                    while ($row = $results->fetchArray(SQLITE3_ASSOC)):
                        $has_rows = true;
                    ?>
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">
                            <?php echo date('d.m.Y H:i', strtotime($row['created_at'])); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-sm font-medium text-slate-900"><?php echo htmlspecialchars($row['material_name']); ?></span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">
                            <?php echo htmlspecialchars($row['customer_name']); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-slate-800">
                            <?php echo $row['length_cut']; ?> m
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-primary-600 font-bold">
                            <?php echo format_currency($row['price_sold']); ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>

                    <?php if (!$has_rows): ?>
                    <tr>
                        <td colspan="5" class="px-6 py-10 text-center text-sm text-slate-400 italic">
                            Hozircha hech qanday ma'lumot yo'q.
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../src/templates/footer.php'; ?>
