<?php
// tests/integration_test.php
require_once __DIR__ . '/../src/db.php';

echo "Starting Integration Test...\n";

// 1. Reset DB for test (optional, but cleaner)
// For this test, we'll just insert new data.

// 2. Create Material
echo "Creating Material 'Test Fabric'...\n";
$stmt = $db->prepare("INSERT INTO materials (name, width, purchase_price, selling_price, min_stock_warning, min_stock_critical, max_stock_level) VALUES ('Test Fabric', 150, 50000, 80000, 50, 30, 100)");
if (!$stmt->execute()) die("Failed to create material.\n");
$material_id = $db->lastInsertRowID();
echo "Material Created. ID: $material_id\n";

// 3. Add Roll
echo "Adding 60m Roll...\n";
$stmt = $db->prepare("INSERT INTO rolls (material_id, original_length, current_length) VALUES (:mid, 60, 60)");
$stmt->bindValue(':mid', $material_id, SQLITE3_INTEGER);
if (!$stmt->execute()) die("Failed to add roll.\n");
$roll_id = $db->lastInsertRowID();
echo "Roll Added. ID: $roll_id\n";

// 4. Perform Cut (10m)
echo "Cutting 10m...\n";
$cut_length = 10;
$selling_price = 80000;
$purchase_price = 50000;
$price_sold = $cut_length * $selling_price;
$profit = $price_sold - ($cut_length * $purchase_price);

$db->exec('BEGIN TRANSACTION');

// Update Roll
$stmt = $db->prepare("UPDATE rolls SET current_length = current_length - :len WHERE id = :rid");
$stmt->bindValue(':len', $cut_length, SQLITE3_FLOAT);
$stmt->bindValue(':rid', $roll_id, SQLITE3_INTEGER);
$stmt->execute();

// Insert Transaction (Simulating Cutter User ID 2)
$stmt = $db->prepare("INSERT INTO cuts (roll_id, material_id, user_id, customer_name, length_cut, price_sold, profit) VALUES (:rid, :mid, 2, 'Test Customer', :len, :sold, :prof)");
$stmt->bindValue(':rid', $roll_id, SQLITE3_INTEGER);
$stmt->bindValue(':mid', $material_id, SQLITE3_INTEGER);
$stmt->bindValue(':len', $cut_length, SQLITE3_FLOAT);
$stmt->bindValue(':sold', $price_sold, SQLITE3_FLOAT);
$stmt->bindValue(':prof', $profit, SQLITE3_FLOAT);
$stmt->execute();

$db->exec('COMMIT');
echo "Cut Performed.\n";

// 5. Verify Results
echo "Verifying Stock...\n";
$current_len = $db->querySingle("SELECT current_length FROM rolls WHERE id = $roll_id");
echo "Current Length: $current_len (Expected: 50)\n";

if ($current_len == 50) {
    echo "STOCK CHECK PASS\n";
} else {
    echo "STOCK CHECK FAIL\n";
}

echo "Verifying Profit...\n";
$recorded_profit = $db->querySingle("SELECT profit FROM cuts WHERE roll_id = $roll_id ORDER BY id DESC LIMIT 1");
$expected_profit = (80000 - 50000) * 10; // 300,000
echo "Recorded Profit: $recorded_profit (Expected: $expected_profit)\n";

if ($recorded_profit == $expected_profit) {
    echo "PROFIT CHECK PASS\n";
} else {
    echo "PROFIT CHECK FAIL\n";
}

echo "Integration Test Complete.\n";
?>
