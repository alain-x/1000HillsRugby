<?php
require_once __DIR__ . '/smartsheet-system/includes/auth.php';

// Initialize Auth (starts session internally)
$auth = new Auth();

// Detect if the user came from a protected page
$redirectTarget = isset($_GET['redirect']) ? trim($_GET['redirect']) : '';

// Handle logout
if (isset($_GET['logout'])) {
    $auth->logout();
    unset($_SESSION['is_admin']);
    header('Location: Controll_admin.php');
    exit;
}

$loginError = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = isset($_POST['identifier']) ? trim($_POST['identifier']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    if ($identifier === '' || $password === '') {
        $loginError = 'Please enter your email/username and password.';
    } else {
        $result = $auth->login($identifier, $password);
        if ($result['success'] && $auth->isAdmin()) {
            // Mark this session as having admin access for the public upload scripts
            $_SESSION['is_admin'] = true;
            $_SESSION['admin_identifier'] = isset($_SESSION['user_email']) && $_SESSION['user_email']
                ? $_SESSION['user_email']
                : $_SESSION['username'];

            $redirect = isset($_GET['redirect']) ? $_GET['redirect'] : '';
            if ($redirect) {
                header('Location: ' . $redirect);
            } else {
                header('Location: Controll_admin.php');
            }
            exit;
        } else {
            // If logged in but not admin, or login failed, ensure session is clean
            $auth->logout();
            $loginError = 'Invalid credentials or you are not authorized as an admin.';
        }
    }
}

$isAdmin = !empty($_SESSION['is_admin']);
$currentAdmin = $isAdmin ? ($_SESSION['admin_identifier'] ?? ($_SESSION['username'] ?? 'Admin')) : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Control Panel | 1000 Hills Rugby</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="min-h-screen flex flex-col">
        <!-- Top bar -->
        <header class="bg-green-700 text-white shadow-md">
            <div class="max-w-6xl mx-auto px-4 py-3 flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 rounded-full bg-white flex items-center justify-center text-green-700 font-bold text-lg">
                        1HR
                    </div>
                    <div>
                        <div class="font-semibold">1000 Hills Rugby</div>
                        <div class="text-xs text-green-100">Admin Control Panel</div>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <?php if ($isAdmin): ?>
                        <div class="text-sm">
                            <div class="font-medium text-right">Logged in as</div>
                            <div class="text-xs text-green-100"><?php echo htmlspecialchars($currentAdmin, ENT_QUOTES, 'UTF-8'); ?></div>
                        </div>
                        <a href="Controll_admin.php?logout=1" class="text-xs px-3 py-1 rounded bg-green-900 hover:bg-red-600 transition">Logout</a>
                    <?php else: ?>
                        <a href="index.html" class="text-xs px-3 py-1 rounded bg-green-900 hover:bg-green-800 transition">Back to Site</a>
                    <?php endif; ?>
                </div>
            </div>
        </header>

        <!-- Main content -->
        <main class="flex-1 flex items-center justify-center px-4 py-8">
            <?php if (!$isAdmin): ?>
                <div class="w-full max-w-md bg-white shadow-xl rounded-xl p-8">
                    <h1 class="text-2xl font-bold mb-1 text-center text-gray-800">Sign in to Admin</h1>
                    <p class="text-xs text-gray-500 mb-6 text-center">Use your 1000 Hills admin account (stored in the database).</p>

                    <?php if ($redirectTarget): ?>
                        <div class="mb-4 p-3 rounded bg-blue-50 border border-blue-200 text-blue-800 text-xs">
                            Access to the requested page is restricted. Please log in with an administrator account to continue.
                        </div>
                    <?php endif; ?>

                    <?php if ($loginError): ?>
                        <div class="mb-4 p-3 rounded bg-red-50 border border-red-200 text-red-700 text-xs"><?php echo htmlspecialchars($loginError, ENT_QUOTES, 'UTF-8'); ?></div>
                    <?php endif; ?>

                    <form method="POST" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium mb-1">Email or Username</label>
                            <input type="text" name="identifier" required class="w-full border border-gray-300 focus:border-green-600 focus:ring-1 focus:ring-green-600 rounded px-3 py-2 text-sm" placeholder="admin@1000hillsrugby.rw">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Password</label>
                            <input type="password" name="password" required class="w-full border border-gray-300 focus:border-green-600 focus:ring-1 focus:ring-green-600 rounded px-3 py-2 text-sm">
                        </div>
                        <button type="submit" class="w-full bg-green-700 hover:bg-green-800 text-white font-semibold py-2 rounded text-sm transition">Sign In</button>
                    </form>
                    <p class="mt-4 text-[11px] text-gray-400 text-center">Admin accounts are managed in the <code>users</code> table (role = 'admin').</p>
                </div>
            <?php else: ?>
                <div class="w-full max-w-5xl mx-auto grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Welcome card -->
                    <div class="md:col-span-1 bg-white rounded-xl shadow p-6 flex flex-col justify-between">
                        <div>
                            <h2 class="text-xl font-semibold mb-1 text-gray-800">Welcome, Admin</h2>
                            <p class="text-xs text-gray-500 mb-4">Use the quick actions to manage content on the 1000 Hills website.</p>
                            <ul class="space-y-2 text-xs text-gray-600">
                                <li class="flex items-center space-x-2"><span class="w-2 h-2 rounded-full bg-green-500"></span><span>Secure access to all upload tools</span></li>
                                <li class="flex items-center space-x-2"><span class="w-2 h-2 rounded-full bg-green-500"></span><span>News, events, players, sponsors and tables</span></li>
                            </ul>
                        </div>
                        <div class="mt-6 text-xs text-gray-400">
                            Tip: Keep your admin credentials safe. Only users with role <span class="font-semibold">admin</span> can sign in here.
                        </div>
                    </div>

                    <!-- Upload tools -->
                    <div class="md:col-span-2 grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <a href="upload.php" class="block bg-white rounded-xl shadow p-4 hover:shadow-md hover:-translate-y-0.5 transition">
                            <div class="flex items-center justify-between mb-2">
                                <h3 class="font-semibold text-sm text-gray-800">News & Articles</h3>
                                <span class="text-green-600 text-xs font-semibold px-2 py-0.5 rounded-full bg-green-50">upload.php</span>
                            </div>
                            <p class="text-xs text-gray-500">Create and edit rugby news articles, images and media for the site.</p>
                        </a>

                        <a href="uploadevent.php" class="block bg-white rounded-xl shadow p-4 hover:shadow-md hover:-translate-y-0.5 transition">
                            <div class="flex items-center justify-between mb-2">
                                <h3 class="font-semibold text-sm text-gray-800">Events & Schedule</h3>
                                <span class="text-green-600 text-xs font-semibold px-2 py-0.5 rounded-full bg-green-50">uploadevent.php</span>
                            </div>
                            <p class="text-xs text-gray-500">Manage fixtures, training sessions and other events.</p>
                        </a>

                        <a href="uploadprofile.php" class="block bg-white rounded-xl shadow p-4 hover:shadow-md hover:-translate-y-0.5 transition">
                            <div class="flex items-center justify-between mb-2">
                                <h3 class="font-semibold text-sm text-gray-800">Player Profiles</h3>
                                <span class="text-green-600 text-xs font-semibold px-2 py-0.5 rounded-full bg-green-50">uploadprofile.php</span>
                            </div>
                            <p class="text-xs text-gray-500">Add and update players for men, women and academy teams.</p>
                        </a>

                        <a href="uploadsponsors.php" class="block bg-white rounded-xl shadow p-4 hover:shadow-md hover:-translate-y-0.5 transition">
                            <div class="flex items-center justify-between mb-2">
                                <h3 class="font-semibold text-sm text-gray-800">Sponsors</h3>
                                <span class="text-green-600 text-xs font-semibold px-2 py-0.5 rounded-full bg-green-50">uploadsponsors.php</span>
                            </div>
                            <p class="text-xs text-gray-500">Upload sponsor logos displayed on the website.</p>
                        </a>

                        <a href="uploadtables.php" class="block bg-white rounded-xl shadow p-4 hover:shadow-md hover:-translate-y-0.5 transition">
                            <div class="flex items-center justify-between mb-2">
                                <h3 class="font-semibold text-sm text-gray-800">League Tables</h3>
                                <span class="text-green-600 text-xs font-semibold px-2 py-0.5 rounded-full bg-green-50">uploadtables.php</span>
                            </div>
                            <p class="text-xs text-gray-500">Maintain standings and team information for competitions.</p>
                        </a>

                        <a href="upload_resources.php" class="block bg-white rounded-xl shadow p-4 hover:shadow-md hover:-translate-y-0.5 transition">
                            <div class="flex items-center justify-between mb-2">
                                <h3 class="font-semibold text-sm text-gray-800">Resources & Documents</h3>
                                <span class="text-green-600 text-xs font-semibold px-2 py-0.5 rounded-full bg-green-50">upload_resources.php</span>
                            </div>
                            <p class="text-xs text-gray-500">Upload PDFs and learning resources for the community.</p>
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>
