<?php
require_once __DIR__ . '/../../src/auth.php';
require_once __DIR__ . '/../../src/db.php';
require_once __DIR__ . '/../../src/functions.php';

require_role('admin');

// Date Filter
$start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
$end_date = $_GET['end_date'] ?? date('Y-m-d');

// Financial Stats (Filtered)
$stats_query = "
    SELECT
        COUNT(*) as total_sales,
        SUM(length_cut) as total_length,
        SUM(price_sold) as total_revenue,
        SUM(profit) as total_profit
    FROM cuts
    WHERE date(created_at) BETWEEN :start AND :end
";
$stmt = $db->prepare($stats_query);
$stmt->bindValue(':start', $start_date, SQLITE3_TEXT);
$stmt->bindValue(':end', $end_date, SQLITE3_TEXT);
$stats = $stmt->execute()->fetchArray(SQLITE3_ASSOC);

// Chart Data: Daily Sales
$chart_query = "
    SELECT date(created_at) as sale_date, SUM(profit) as daily_profit, SUM(price_sold) as daily_revenue
    FROM cuts
    WHERE date(created_at) BETWEEN :start AND :end
    GROUP BY date(created_at)
    ORDER BY sale_date ASC
";
$stmt_chart = $db->prepare($chart_query);
$stmt_chart->bindValue(':start', $start_date, SQLITE3_TEXT);
$stmt_chart->bindValue(':end', $end_date, SQLITE3_TEXT);
$chart_res = $stmt_chart->execute();

$dates = [];
$profits = [];
$revenues = [];

while ($row = $chart_res->fetchArray(SQLITE3_ASSOC)) {
    $dates[] = date('d-M', strtotime($row['sale_date']));
    $profits[] = $row['daily_profit'];
    $revenues[] = $row['daily_revenue'];
}

// Convert to JSON for JS
$json_dates = json_encode($dates);
$json_profits = json_encode($profits);
$json_revenues = json_encode($revenues);

include __DIR__ . '/../../src/templates/header.php';
?>

<div class="mb-8 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div>
        <h1 class="text-3xl font-bold text-slate-900">Statistika</h1>
        <p class="mt-1 text-sm text-slate-500">Moliyaviy ko'rsatkichlar va tahlillar</p>
    </div>

    <form class="flex space-x-2 bg-white p-2 rounded-lg shadow-sm border border-slate-200">
        <div class="relative">
            <input type="date" name="start_date" value="<?php echo $start_date; ?>" class="border-slate-300 rounded-md text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 py-2 pl-3 pr-2">
        </div>
        <span class="text-slate-400 self-center font-medium">-</span>
        <div class="relative">
            <input type="date" name="end_date" value="<?php echo $end_date; ?>" class="border-slate-300 rounded-md text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 py-2 pl-3 pr-2">
        </div>
        <button type="submit" class="bg-primary-600 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-primary-700 shadow-sm transition-colors flex items-center">
            <svg class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" /></svg>
            Yangilash
        </button>
    </form>
</div>

<!-- Key Metrics Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <!-- Total Revenue -->
    <div class="bg-white overflow-hidden shadow-sm rounded-xl border border-slate-200">
        <div class="p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0 bg-primary-100 rounded-md p-3">
                    <svg class="h-6 w-6 text-primary-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-slate-500 truncate">Jami Savdo (Tushum)</dt>
                        <dd>
                            <div class="text-2xl font-bold text-slate-900"><?php echo format_currency($stats['total_revenue'] ?? 0); ?></div>
                        </dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    <!-- Total Profit -->
    <div class="bg-white overflow-hidden shadow-sm rounded-xl border border-slate-200">
        <div class="p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0 bg-emerald-100 rounded-md p-3">
                    <svg class="h-6 w-6 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                    </svg>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-slate-500 truncate">Sof Foyda</dt>
                        <dd>
                            <div class="text-2xl font-bold text-emerald-600"><?php echo format_currency($stats['total_profit'] ?? 0); ?></div>
                        </dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    <!-- Total Cuts -->
    <div class="bg-white overflow-hidden shadow-sm rounded-xl border border-slate-200">
        <div class="p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0 bg-indigo-100 rounded-md p-3">
                    <svg class="h-6 w-6 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.121 14.121L19 19m-7-7l7-7m-7 7l-2.879 2.879M12 12L9.121 9.121m0 5.758a3 3 0 10-4.243 4.243 3 3 0 004.243-4.243zm0-5.758a3 3 0 10-4.243-4.243 3 3 0 004.243 4.243z" />
                    </svg>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-slate-500 truncate">Bitimlar Soni</dt>
                        <dd>
                            <div class="text-2xl font-bold text-slate-900"><?php echo $stats['total_sales'] ?? 0; ?></div>
                        </dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    <!-- Length Cut -->
    <div class="bg-white overflow-hidden shadow-sm rounded-xl border border-slate-200">
        <div class="p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0 bg-orange-100 rounded-md p-3">
                    <svg class="h-6 w-6 text-orange-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                    </svg>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-slate-500 truncate">Kesilgan Material</dt>
                        <dd>
                            <div class="text-2xl font-bold text-slate-900"><?php echo number_format($stats['total_length'] ?? 0, 1, '.', ' '); ?> m</div>
                        </dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Chart Section -->
<div class="bg-white shadow-sm rounded-xl border border-slate-200 p-6">
    <h3 class="text-lg leading-6 font-medium text-slate-900 mb-6">Kunlik Tushum va Foyda Dinamikasi</h3>
    <div class="relative h-80 w-full">
        <canvas id="profitChart"></canvas>
    </div>
</div>

<script>
    const ctx = document.getElementById('profitChart').getContext('2d');
    const profitChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?php echo $json_dates; ?>,
            datasets: [
                {
                    label: 'Tushum (Revenue)',
                    data: <?php echo $json_revenues; ?>,
                    borderColor: '#0ea5e9', // primary-500
                    backgroundColor: 'rgba(14, 165, 233, 0.1)',
                    borderWidth: 2,
                    tension: 0.3,
                    fill: true
                },
                {
                    label: 'Sof Foyda (Profit)',
                    data: <?php echo $json_profits; ?>,
                    borderColor: '#10b981', // emerald-500
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    borderWidth: 2,
                    tension: 0.3,
                    fill: true
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                },
                tooltip: {
                    mode: 'index',
                    intersect: false,
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: '#f1f5f9'
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            },
            interaction: {
                mode: 'nearest',
                axis: 'x',
                intersect: false
            }
        }
    });
</script>

<?php include __DIR__ . '/../../src/templates/footer.php'; ?>
