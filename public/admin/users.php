<?php
require_once __DIR__ . '/../../src/auth.php';
require_once __DIR__ . '/../../src/db.php';
require_once __DIR__ . '/../../src/functions.php';

require_role('admin');

$error = '';
$message = '';

// Handle Create
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'cutter';

    if (empty($username) || empty($password)) {
        $error = "Barcha maydonlar to'ldirilishi shart.";
    } else {
        $check = $db->querySingle("SELECT count(*) FROM users WHERE username = '$username'");
        if ($check > 0) {
            $error = "Bu foydalanuvchi nomi band.";
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("INSERT INTO users (username, password, role) VALUES (:u, :p, :r)");
            $stmt->bindValue(':u', $username, SQLITE3_TEXT);
            $stmt->bindValue(':p', $hashed, SQLITE3_TEXT);
            $stmt->bindValue(':r', $role, SQLITE3_TEXT);
            if ($stmt->execute()) {
                $message = "Foydalanuvchi yaratildi.";
            } else {
                $error = "Xatolik yuz berdi.";
            }
        }
    }
}

// Handle Delete
if (isset($_POST['delete_id'])) {
    if ($_POST['delete_id'] == $_SESSION['user_id']) {
        $error = "O'zingizni o'chira olmaysiz.";
    } else {
        $stmt = $db->prepare("DELETE FROM users WHERE id = :id");
        $stmt->bindValue(':id', $_POST['delete_id'], SQLITE3_INTEGER);
        $stmt->execute();
        $message = "Foydalanuvchi o'chirildi.";
    }
}

$users = $db->query("SELECT * FROM users ORDER BY created_at DESC");

include __DIR__ . '/../../src/templates/header.php';
?>

<div class="grid grid-cols-1 md:grid-cols-2 gap-8">
    <div>
        <h2 class="text-2xl font-bold mb-4">Foydalanuvchilar Ro'yxati</h2>
        <div class="bg-white shadow overflow-hidden sm:rounded-lg">
            <ul class="divide-y divide-gray-200">
                <?php while ($user = $users->fetchArray(SQLITE3_ASSOC)): ?>
                    <li class="px-4 py-4 flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-indigo-600"><?php echo htmlspecialchars($user['username']); ?></p>
                            <p class="text-sm text-gray-500">Rol: <?php echo htmlspecialchars($user['role']); ?></p>
                        </div>
                        <?php if ($user['id'] != $_SESSION['user_id']): ?>
                        <form method="POST" onsubmit="return confirm('O\'chirmoqchimisiz?');">
                            <input type="hidden" name="delete_id" value="<?php echo $user['id']; ?>">
                            <button type="submit" class="text-red-600 hover:text-red-900 text-sm">O'chirish</button>
                        </form>
                        <?php endif; ?>
                    </li>
                <?php endwhile; ?>
            </ul>
        </div>
    </div>

    <div>
        <h2 class="text-2xl font-bold mb-4">Yangi Foydalanuvchi</h2>
        <div class="bg-white shadow sm:rounded-lg p-6">
            <?php if ($error): ?>
                <p class="text-red-600 mb-4"><?php echo htmlspecialchars($error); ?></p>
            <?php endif; ?>
            <?php if ($message): ?>
                <p class="text-green-600 mb-4"><?php echo htmlspecialchars($message); ?></p>
            <?php endif; ?>
            <form method="POST">
                <input type="hidden" name="action" value="create">
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Foydalanuvchi nomi</label>
                    <input type="text" name="username" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Parol</label>
                    <input type="password" name="password" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Rol</label>
                    <select name="role" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                        <option value="cutter">Kesuvchi (Cutter)</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded w-full">Yaratish</button>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../src/templates/footer.php'; ?>
