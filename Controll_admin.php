<?php
session_start();

$ADMIN_EMAIL = 'gasore@1000hillsrugby,rw';
$ADMIN_PASSWORD = 'Back123!!';

if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header('Location: Controll_admin.php');
    exit;
}

$loginError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    if ($email === $ADMIN_EMAIL && $password === $ADMIN_PASSWORD) {
        $_SESSION['is_admin'] = true;
        $_SESSION['admin_email'] = $email;
        $redirect = isset($_GET['redirect']) ? $_GET['redirect'] : '';
        if ($redirect) {
            header('Location: ' . $redirect);
        } else {
            header('Location: Controll_admin.php');
        }
        exit;
    } else {
        $loginError = 'Invalid email or password';
    }
}

$isAdmin = !empty($_SESSION['is_admin']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Control Panel</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">
    <div class="w-full max-w-md bg-white shadow-lg rounded-lg p-6">
        <h1 class="text-2xl font-bold mb-4 text-center">Admin Control Panel</h1>
        <?php if (!$isAdmin): ?>
            <?php if ($loginError): ?>
                <div class="mb-4 p-3 rounded bg-red-100 text-red-700 text-sm"><?php echo htmlspecialchars($loginError, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>
            <form method="POST" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium mb-1">Email</label>
                    <input type="email" name="email" required class="w-full border rounded px-3 py-2" placeholder="admin@example.com">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Password</label>
                    <input type="password" name="password" required class="w-full border rounded px-3 py-2">
                </div>
                <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white font-semibold py-2 rounded">Login</button>
            </form>
            <p class="mt-4 text-xs text-gray-500 text-center">Update the admin email and password in Controll_admin.php to your own secure values.</p>
        <?php else: ?>
            <div class="mb-4 flex justify-between items-center">
                <p class="text-sm text-gray-700">Logged in as: <?php echo htmlspecialchars($_SESSION['admin_email'], ENT_QUOTES, 'UTF-8'); ?></p>
                <a href="Controll_admin.php?logout=1" class="text-xs text-red-600 hover:underline">Logout</a>
            </div>
            <h2 class="text-lg font-semibold mb-3">Manage Upload Pages</h2>
            <ul class="space-y-2 text-sm">
                <li><a href="upload.php" class="block px-3 py-2 rounded bg-gray-100 hover:bg-gray-200">News / Articles Upload (upload.php)</a></li>
                <li><a href="uploadevent.php" class="block px-3 py-2 rounded bg-gray-100 hover:bg-gray-200">Events & Schedule (uploadevent.php)</a></li>
                <li><a href="uploadprofile.php" class="block px-3 py-2 rounded bg-gray-100 hover:bg-gray-200">Player Profiles (uploadprofile.php)</a></li>
                <li><a href="uploadsponsors.php" class="block px-3 py-2 rounded bg-gray-100 hover:bg-gray-200">Sponsor Logos (uploadsponsors.php)</a></li>
                <li><a href="uploadtables.php" class="block px-3 py-2 rounded bg-gray-100 hover:bg-gray-200">League Tables / Standings (uploadtables.php)</a></li>
            </ul>
        <?php endif; ?>
    </div>
</body>
</html>
