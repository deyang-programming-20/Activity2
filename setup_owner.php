
<?php

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

$count = (int) $pdo->query("
    SELECT COUNT(*)
    FROM users
")->fetchColumn();

$error = '';

if (
    $count === 0 &&
    $_SERVER['REQUEST_METHOD'] === 'POST'
) {

    verify_csrf();

    $name = trim($_POST['full_name'] ?? '');

    $email = strtolower(
        trim($_POST['email'] ?? '')
    );

    $password = $_POST['password'] ?? '';

    if (
        mb_strlen($name) < 2 ||
        !filter_var($email, FILTER_VALIDATE_EMAIL) ||
        strlen($password) < 8
    ) {

        $error = 'Enter a valid name, email, and password with at least 8 characters.';

    } else {

        try {

            $stmt = $pdo->prepare("
                INSERT INTO users (
                    full_name,
                    email,
                    password_hash,
                    role,
                    status
                )
                VALUES (?, ?, ?, 'owner', 'active')
            ");

            $stmt->execute([
                $name,
                $email,
                password_hash($password, PASSWORD_DEFAULT)
            ]);

            header('Location: index.php');
            exit;

        } catch (PDOException $e) {

            $error = 'Unable to create owner account. Check whether the email is already used.';

        }
    }
}

page_start('Initial Owner Setup');

?>

<div class="auth-shell">

    <section class="auth-card">

        <div class="auth-brand">
            ☕ MAGEL Coffee Shop
        </div>


        <?php if ($count > 0): ?>

            <h1>Setup is locked</h1>

            <p class="sub">
                An account already exists.
                For security, the initial owner setup is disabled.
            </p>

            <p class="small muted">
                Delete or rename setup_owner.php after creating
                the owner account.
            </p>

            <a class="button" href="index.php">
                Go to sign in
            </a>


        <?php else: ?>

            <h1>Initial owner setup</h1>

            <p class="sub">
                Create the first owner account.
                This page works only while the users table is empty.
            </p>

            <?php if ($error): ?>

                <div class="notice error">
                    <?= h($error) ?>
                </div>

            <?php endif; ?>

            <form method="post">

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= h(csrf_token()) ?>"
                >

                <label>Owner full name</label>

                <input
                    type="text"
                    name="full_name"
                    required
                    minlength="2"
                    maxlength="120"
                >

                <label>Email address</label>

                <input
                    type="email"
                    name="email"
                    required
                    maxlength="190"
                >

                <label>Password (8+ characters)</label>

                <input
                    type="password"
                    name="password"
                    minlength="8"
                    required
                >

                <button type="submit">
                    Create owner account
                </button>

            </form>

        <?php endif; ?>

    </section>

</div>


<?php

page_end();

?>
