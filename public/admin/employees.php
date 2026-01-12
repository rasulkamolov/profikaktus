<?php
require_once __DIR__ . '/../../src/auth.php';
require_once __DIR__ . '/../../src/db.php';
require_once __DIR__ . '/../../src/functions.php';

require_role('admin');

$error = '';
$message = '';
$edit_user = null;

// Handle Delete
if (isset($_POST['delete_id'])) {
    if ($_POST['delete_id'] == $_SESSION['user_id']) {
        $error = "O'zingizni o'chira olmaysiz.";
    } else {
        $stmt = $db->prepare("DELETE FROM users WHERE id = :id");
        $stmt->bindValue(':id', $_POST['delete_id'], SQLITE3_INTEGER);
        $stmt->execute();
        $message = "Xodim o'chirildi.";
    }
}

// Handle Create / Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'cutter';

    if ($_POST['action'] === 'create') {
        if (empty($username) || empty($password)) {
            $error = "Barcha maydonlar to'ldirilishi shart.";
        } else {
            $stmt_check = $db->prepare("SELECT count(*) FROM users WHERE username = :u");
            $stmt_check->bindValue(':u', $username, SQLITE3_TEXT);
            $check = $stmt_check->execute()->fetchArray(SQLITE3_NUM)[0];

            if ($check > 0) {
                $error = "Bu foydalanuvchi nomi band.";
            } else {
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $db->prepare("INSERT INTO users (username, password, role, created_at) VALUES (:u, :p, :r, :created_at)");
                $stmt->bindValue(':u', $username, SQLITE3_TEXT);
                $stmt->bindValue(':p', $hashed, SQLITE3_TEXT);
                $stmt->bindValue(':r', $role, SQLITE3_TEXT);
                $stmt->bindValue(':created_at', date('Y-m-d H:i:s'), SQLITE3_TEXT);
                if ($stmt->execute()) {
                    $message = "Xodim yaratildi.";
                } else {
                    $error = "Xatolik yuz berdi.";
                }
            }
        }
    } elseif ($_POST['action'] === 'update') {
        $id = $_POST['user_id'] ?? 0;
        if (empty($username)) {
            $error = "Foydalanuvchi nomi bo'sh bo'lishi mumkin emas.";
        } else {
            // If password is provided, update it. If not, keep old one.
            if (!empty($password)) {
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $db->prepare("UPDATE users SET username = :u, password = :p, role = :r WHERE id = :id");
                $stmt->bindValue(':p', $hashed, SQLITE3_TEXT);
            } else {
                $stmt = $db->prepare("UPDATE users SET username = :u, role = :r WHERE id = :id");
            }
            $stmt->bindValue(':u', $username, SQLITE3_TEXT);
            $stmt->bindValue(':r', $role, SQLITE3_TEXT);
            $stmt->bindValue(':id', $id, SQLITE3_INTEGER);

            if ($stmt->execute()) {
                $message = "Xodim ma'lumotlari yangilandi.";
            } else {
                $error = "Yangilashda xatolik (Nom band bo'lishi mumkin).";
            }
        }
    }
}

// Fetch for Edit
if (isset($_GET['edit'])) {
    $stmt = $db->prepare("SELECT * FROM users WHERE id = :id");
    $stmt->bindValue(':id', $_GET['edit'], SQLITE3_INTEGER);
    $res = $stmt->execute();
    $edit_user = $res->fetchArray(SQLITE3_ASSOC);
}

$users = $db->query("SELECT * FROM users ORDER BY created_at DESC");

include __DIR__ . '/../../src/templates/header.php';
?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    <!-- List Column -->
    <div class="lg:col-span-2">
        <h2 class="text-2xl font-bold text-slate-900 mb-6">Xodimlar Ro'yxati</h2>
        <div class="bg-white shadow-sm rounded-xl overflow-hidden border border-slate-200">
            <ul class="divide-y divide-slate-200">
                <?php while ($user = $users->fetchArray(SQLITE3_ASSOC)): ?>
                    <li class="px-6 py-5 flex items-center justify-between hover:bg-slate-50 transition-colors">
                        <div class="flex items-center">
                            <div class="h-10 w-10 rounded-full bg-slate-100 flex items-center justify-center mr-4 text-slate-500">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-slate-900"><?php echo htmlspecialchars($user['username']); ?></p>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo ($user['role'] === 'admin') ? 'bg-indigo-100 text-indigo-800' : 'bg-emerald-100 text-emerald-800'; ?>">
                                    <?php echo ucfirst($user['role']); ?>
                                </span>
                            </div>
                        </div>
                        <div class="flex items-center space-x-3">
                            <a href="employees.php?edit=<?php echo $user['id']; ?>" class="text-slate-400 hover:text-primary-600 transition-colors">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                            </a>
                            <?php if ($user['id'] != $_SESSION['user_id']): ?>
                            <form method="POST" onsubmit="return confirm('O\'chirmoqchimisiz?');">
                                <input type="hidden" name="delete_id" value="<?php echo $user['id']; ?>">
                                <button type="submit" class="text-slate-400 hover:text-red-600 transition-colors">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                </button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </li>
                <?php endwhile; ?>
            </ul>
        </div>
    </div>

    <!-- Form Column -->
    <div>
        <h2 class="text-2xl font-bold text-slate-900 mb-6"><?php echo $edit_user ? "Tahrirlash: " . htmlspecialchars($edit_user['username']) : "Yangi Xodim"; ?></h2>

        <?php if ($message): ?>
            <div class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-md flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-md flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <div class="bg-white shadow-sm rounded-xl p-6 border border-slate-200">
            <form method="POST">
                <input type="hidden" name="action" value="<?php echo $edit_user ? 'update' : 'create'; ?>">
                <?php if ($edit_user): ?>
                    <input type="hidden" name="user_id" value="<?php echo $edit_user['id']; ?>">
                <?php endif; ?>

                <div class="space-y-5">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Login (Username)</label>
                        <input type="text" name="username" value="<?php echo $edit_user ? htmlspecialchars($edit_user['username']) : ''; ?>" class="focus:ring-primary-500 focus:border-primary-500 block w-full sm:text-sm border-slate-300 rounded-md py-2.5 px-3">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Parol <?php echo $edit_user ? '<span class="text-xs font-normal text-slate-400">(O\'zgartirish uchun kiriting)</span>' : ''; ?></label>
                        <input type="password" name="password" class="focus:ring-primary-500 focus:border-primary-500 block w-full sm:text-sm border-slate-300 rounded-md py-2.5 px-3">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Rol</label>
                        <select name="role" class="focus:ring-primary-500 focus:border-primary-500 block w-full sm:text-sm border-slate-300 rounded-md py-2.5 px-3">
                            <option value="cutter" <?php echo ($edit_user && $edit_user['role'] === 'cutter') ? 'selected' : ''; ?>>Kesuvchi (Cutter)</option>
                            <option value="admin" <?php echo ($edit_user && $edit_user['role'] === 'admin') ? 'selected' : ''; ?>>Admin</option>
                        </select>
                    </div>

                    <div class="pt-4">
                        <?php if ($edit_user): ?>
                            <div class="flex space-x-3">
                                <a href="employees.php" class="flex-1 bg-white py-2 px-4 border border-slate-300 rounded-md shadow-sm text-sm font-medium text-slate-700 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 text-center">Bekor qilish</a>
                                <button type="submit" class="flex-1 inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">Saqlash</button>
                            </div>
                        <?php else: ?>
                            <button type="submit" class="w-full inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">Yaratish</button>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../src/templates/footer.php'; ?>
