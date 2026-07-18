<?php
// public/login.php
// 1. Inform the bootstrapper that authentication is NOT required to view this file
define('REQUIRE_AUTH', false);

// 2. Load dependencies via the centralized application bootstrapper
require_once __DIR__ . '/../config/bootstrap.php';

$error = '';

// 3. Process Authentication Form Post Payloads
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $username = trim($_POST['username'] ?? '');
  $password = $_POST['password'] ?? '';

  if (empty($username) || empty($password)) {
    $error = 'Please fill out all credentials.';
  } else {
    // THE FIX: Grab the connection and inject it into the new Auth instance
    $db = Database::getConnection();
    $auth = new Auth($db);

    // Call the dynamic instance method cleanly
    if ($auth->login($username, $password)) {
      // Success! Send them straight onto the primary dashboard home index
      header('Location: /index.php');
      exit;
    } else {
      $error = 'Invalid username or password. Please try again.';
    }
  }
}
?>
<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-50">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login — FPCA Tech Tools</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="h-full flex flex-col justify-center items-center px-6 py-12 bg-slate-100/60 font-sans antialiased">

  <div class="w-full max-w-md">
    <div class="text-center mb-4">
      <div class=" inline-flex items-center justify-center rounded-xl bg-gray-900 text-white text-2xl font-bold shadow-md mb-3 p-2">
        <img src="/assets/images/logo.png" alt="FPCA Tech Tools" class="h-20 w-auto">
      </div>
      <h1 class="text-2xl font-extrabold tracking-tight text-gray-900 sr-only">FPCA Tech Tools</h1>
    </div>

    <div class="bg-white p-8 rounded-2xl shadow-sm border border-slate-200/80">

      <?php if (!empty($error)): ?>
        <div class="bg-red-50 border-l-4 border-red-500 text-red-800 p-4 mb-5 text-xs font-semibold rounded-r shadow-inner flex items-center space-x-2">
          <span>⚠️</span>
          <span><?php echo htmlspecialchars($error); ?></span>
        </div>
      <?php endif; ?>

      <form method="POST" class="space-y-5">
        <div>
          <label for="username" class="block text-xs font-bold text-slate-500 uppercase tracking-wide">Username</label>
          <div class="mt-1.5 relative rounded-md shadow-sm">
            <input type="text" name="username" id="username" required autocomplete="username"
              placeholder="admin"
              class="block w-full border-slate-300 rounded-lg shadow-sm p-3 border focus:ring-2 focus:ring-blue-500 outline-none text-sm bg-white font-medium text-slate-800 transition">
          </div>
        </div>

        <div>
          <label for="password" class="block text-xs font-bold text-slate-500 uppercase tracking-wide">Password</label>
          <div class="mt-1.5 relative rounded-md shadow-sm">
            <input type="password" name="password" id="password" required autocomplete="current-password"
              placeholder="••••••••"
              class="block w-full border-slate-300 rounded-lg shadow-sm p-3 border focus:ring-2 focus:ring-blue-500 outline-none text-sm bg-white font-medium text-slate-800 transition">
          </div>
        </div>

        <div class="pt-2">
          <button type="submit"
            class="w-full bg-gray-900 text-white font-bold py-3 px-4 rounded-lg hover:bg-gray-800 transition-all shadow-md active:scale-[0.98] text-sm tracking-wide flex items-center justify-center space-x-2 cursor-pointer">
            <span>Sign In</span>
            <span class="opacity-60 text-xs">➔</span>
          </button>
        </div>
      </form>
    </div>

    <div class="text-center mt-6 text-xs text-slate-400 font-medium">
      <a href="https://fpcallentown.org" target="_blank" class="hover:text-slate-600 transition hover:underline">
        &copy; <?php echo date('Y'); ?> First Presbyterian Church Allentown
      </a>
    </div>
  </div>

</body>

</html>