<?php
// public/login.php
define('REQUIRE_AUTH', false);
require_once __DIR__ . '/../config/bootstrap.php';

if ($auth->isLoggedIn()) {
  header('Location: /index.php');
  exit;
}

$error = '';
// Capture the redirect parameter, default to /index.php if not set
$redirect = $_GET['redirect'] ?? '/index.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $username = $_POST['username'] ?? '';
  $password = $_POST['password'] ?? '';

  if ($auth->login($username, $password)) {
    // Redirect to the captured path or default to index.php
    header('Location: ' . urldecode($redirect));
    exit;
  } else {
    $error = "Invalid username or password.";
  }
}
?>

<?php include __DIR__ . '/../templates/auth_header.php'; ?>

<div class="w-full max-w-sm p-8 bg-white rounded-lg shadow-md">
  <h2 class="text-2xl font-bold text-center mb-6">Tools Login</h2>

  <?php if (!empty($error)): ?>
    <p class="text-red-500 text-sm mb-4"><?php echo htmlspecialchars($error); ?></p>
  <?php endif; ?>

  <form method="POST" action="login.php?redirect=<?php echo htmlspecialchars($redirect); ?>" class="space-y-4">
    <div>
      <label class="block text-sm font-medium text-gray-700">Username</label>
      <input type="text" name="username" required class="w-full mt-1 p-2 border rounded">
    </div>
    <div>
      <label class="block text-sm font-medium text-gray-700">Password</label>
      <input type="password" name="password" required class="w-full mt-1 p-2 border rounded">
    </div>
    <button type="submit" class="w-full py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
      Sign In
    </button>
  </form>
</div>

<?php include __DIR__ . '/../templates/dashboard/footer.php'; ?>