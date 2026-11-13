
<?php

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

if (logged_in()) {

    header('Location: dashboard.php');
    exit;

}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    verify_csrf();

    $name = trim($_POST['full_name'] ?? '');

    $email = strtolower(
        trim($_POST['email'] ?? '')
    );

    $password = $_POST['password'] ?? '';

    $confirm = $_POST['confirm_password'] ?? '';

    if (
        mb_strlen($name) < 2 ||
        mb_strlen($name) > 120 ||
        !filter_var($email, FILTER_VALIDATE_EMAIL)
    ) {

        $error = 'Please enter a valid name and email address.';

    }

    elseif (strlen($password) < 8) {

        $error = 'Password must be at least 8 characters.';

    }

    elseif ($password !== $confirm) {

        $error = 'Passwords do not match.';

    }

    else {

        try {

            $stmt = $pdo->prepare("
                INSERT INTO users (
                    full_name,
                    email,
                    password_hash,
                    role,
                    status
                )
                VALUES (?, ?, ?, 'staff', 'pending')
            ");


            $stmt->execute([

                $name,
                $email,

                password_hash(
                    $password,
                    PASSWORD_DEFAULT
                )

            ]);

            $success = 'Your account was created. Wait for the owner or manager to approve it before signing in.';

        }

        catch (PDOException $e) {

            if ($e->getCode() === '23000') {

                $error = 'That email address is already registered.';

            }

            else {

                $error = 'Unable to create the account. Please try again.';

            }

        }

    }

}

page_start('Create Account');

?>

<div class="auth-shell">

    <section class="auth-card">

        <div class="auth-brand">
            ☕ MAGEL Coffee Shop
        </div>
        <h1>Create staff account</h1>
        <p class="sub">
            Register your details to request staff access.
        </p>

        <?php if ($error): ?>

            <div
                class="notice error"
                style="margin-top: 18px;"
            >

                <?= h($error) ?>

            </div>

        <?php endif; ?>


        <?php if ($success): ?>

            <div
                class="notice"
                style="margin-top: 18px;"
            >

                <?= h($success) ?>

            </div>

        <?php endif; ?>


        <form method="post">

            <input
                type="hidden"
                name="csrf_token"
                value="<?= h(csrf_token()) ?>"
            >

            <label>Full name</label>

            <input
                type="text"
                name="full_name"
                maxlength="120"
                required
                value="<?= h($_POST['full_name'] ?? '') ?>"
                placeholder="Enter your full name"
            >

            <label>Email address</label>

            <input
                type="email"
                name="email"
                maxlength="190"
                required
                value="<?= h($_POST['email'] ?? '') ?>"
                placeholder="Enter your email address"
            >

            <label>Password</label>

            <input
                type="password"
                name="password"
                minlength="8"
                required
                placeholder="Enter your password"
            >

            <label>Confirm password</label>

            <input
                type="password"
                name="confirm_password"
                minlength="8"
                required
                placeholder="Confirm your password"
            >

            <button type="submit">
                Create account
            </button>

        </form>

        <div class="auth-foot">

            <a href="index.php">
                ← Back to sign in
            </a>

        </div>
    </section>
</div>


<?php

page_end();

?>
