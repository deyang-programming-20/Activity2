
<?php

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

if (logged_in()) {

    header('Location: dashboard.php');
    exit;

}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    verify_csrf();

    $email = strtolower(
        trim($_POST['email'] ?? '')
    );

    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("
        SELECT
            id,
            full_name,
            email,
            password_hash,
            role,
            status
        FROM users
        WHERE email = ?
        LIMIT 1
    ");

    $stmt->execute([$email]);

    $user = $stmt->fetch();

    if (
        !$user ||
        !password_verify($password, $user['password_hash'])
    ) {

        $error = 'Incorrect email or password.';

    } elseif ($user['status'] === 'pending') {

        $error = 'Your account is waiting for owner or manager approval.';

    } elseif ($user['status'] !== 'active') {

        $error = 'This account is disabled. Please contact the owner.';

    } else {

        session_regenerate_id(true);

        $_SESSION['user_id'] = (int) $user['id'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = $user['role'];

        header('Location: dashboard.php');
        exit;

    }
}

page_start('Sign In');

?>

<div class="auth-shell">

    <section class="auth-card">

        <div class="auth-brand">
            ☕ MAGEL Coffee Shop
        </div>

        <p class="sub">
            Sign in to manage your coffee shop inventory.
        </p>

        <?php if (isset($_GET['account'])): ?>

            <div class="notice warning" style="margin-top: 18px;">

                Your account is no longer active.
                Please contact the owner or manager.

            </div>

        <?php endif; ?>

        <?php if ($error): ?>

            <div class="notice error" style="margin-top: 18px;">

                <?= h($error) ?>

            </div>

        <?php endif; ?>

        <form method="post" action="index.php">


            <input
                type="hidden"
                name="csrf_token"
                value="<?= h(csrf_token()) ?>"
            >

            <label for="email">
                Email address
            </label>

            <input
                id="email"
                type="email"
                name="email"
                required
                autocomplete="username"
                autofocus
            >

            <label for="password">
                Password
            </label>

            <input
                id="password"
                type="password"
                name="password"
                required
                autocomplete="current-password"
            >

            <button type="submit">
                Sign in
            </button>

        </form>

        <div class="auth-foot">

            New staff member?

            <a href="register.php">
                Create an account
            </a>

            <p class="small muted">
                New staff accounts require approval
                before access is granted.
            </p>

        </div>

    </section>

</div>


<?php

page_end();

?>
