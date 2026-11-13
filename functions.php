
<?php

if (session_status() !== PHP_SESSION_ACTIVE) {

    ini_set('session.use_strict_mode', '1');

    session_set_cookie_params([
        'httponly' => true,
        'secure'   => !empty($_SERVER['HTTPS']),
        'samesite' => 'Lax'
    ]);

    session_start();
}

function h($value): string
{
    return htmlspecialchars(
        (string) ($value ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}


function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}


function verify_csrf(): void
{
    $token = $_POST['csrf_token'] ?? '';

    if (
        !is_string($token) ||
        !hash_equals($_SESSION['csrf_token'] ?? '', $token)
    ) {
        http_response_code(403);

        exit('Invalid form token. Please refresh and try again.');
    }
}

function flash(string $message, string $type = 'success'): void
{
    $_SESSION['flash'] = [
        'message' => $message,
        'type'    => $type
    ];
}


function show_flash(): void
{
    if (!empty($_SESSION['flash'])) {

        $f = $_SESSION['flash'];

        echo '<div class="notice ' . h($f['type']) . '">'
            . h($f['message'])
            . '</div>';

        unset($_SESSION['flash']);
    }
}

function logged_in(): bool
{
    return !empty($_SESSION['user_id']);
}


function can_manage_inventory(): bool
{
    return in_array(
        $_SESSION['role'] ?? '',
        ['owner', 'manager'],
        true
    );
}


function require_login(): void
{
    if (!logged_in()) {
        header('Location: index.php');
        exit;
    }

    global $pdo;

    $stmt = $pdo->prepare("
        SELECT id, full_name, email, role, status
        FROM users
        WHERE id = ?
        LIMIT 1
    ");

    $stmt->execute([
        (int) $_SESSION['user_id']
    ]);

    $user = $stmt->fetch();

    if (!$user || $user['status'] !== 'active') {

        $_SESSION = [];

        session_destroy();

        header('Location: index.php?account=inactive');
        exit;
    }

    $_SESSION['full_name'] = $user['full_name'];
    $_SESSION['role']      = $user['role'];
    $_SESSION['email']     = $user['email'];
}

function require_manager(): void
{
    require_login();

    if (!can_manage_inventory()) {

        http_response_code(403);

        page_start('Access denied');

        echo '
            <section class="panel">
                <h1>Access denied</h1>

                <p>
                    Your account does not have permission
                    to manage users.
                </p>

                <a class="button" href="dashboard.php">
                    Back to dashboard
                </a>
            </section>
        ';

        page_end();

        exit;
    }
}

function page_start(string $title): void
{
    $name = $_SESSION['full_name'] ?? 'Guest';

    $active = basename($_SERVER['PHP_SELF'] ?? '');

    echo '
    <!doctype html>
    <html lang="en">

    <head>
        <meta charset="utf-8">

        <meta name="viewport"
              content="width=device-width, initial-scale=1">

        <title>' . h($title) . ' | MAGEL Coffee Shop</title>

        <style>

        :root {
            --coffee: #4b3025;
            --coffee2: #704b36;
            --cream: #f7f1e8;
            --paper: #fffdfa;
            --ink: #30251f;
            --muted: #81746b;
            --line: #eadfd4;
            --gold: #c99b62;
            --green: #286344;
            --red: #a33d35;
            --shadow: 0 12px 35px rgba(65,43,29,.08);
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: var(--cream);
            color: var(--ink);
            font: 15px/1.55 Arial, Helvetica, sans-serif;
        }

        a {
            color: var(--coffee2);
        }

        .topbar {
            background: var(--coffee);
            color: white;
            padding: 0 5%;
            min-height: 68px;

            display: flex;
            align-items: center;
            justify-content: space-between;

            gap: 18px;
            flex-wrap: wrap;
        }

        .brand {
            font-weight: 800;
            font-size: 18px;
            letter-spacing: .2px;
            color: white;
            text-decoration: none;
        }

        .brand span {
            color: #e8c99f;
        }

        .nav {
            display: flex;
            align-items: center;
            gap: 7px;
            flex-wrap: wrap;
        }

        .nav a {
            color: #f9eee4;
            text-decoration: none;
            padding: 9px 12px;
            border-radius: 8px;
            font-size: 14px;
        }

        .nav a:hover,
        .nav a.active {
            background: rgba(255,255,255,.13);
        }

        .user-tag {
            font-size: 13px;
            color: #e8d7c8;
            padding: 0 8px;
        }

        main {
            width: min(1120px, 92%);
            margin: 34px auto 60px;
        }

        .page-head {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            gap: 16px;
            flex-wrap: wrap;
            margin-bottom: 24px;
        }

        h1 {
            font-size: 30px;
            line-height: 1.2;
            margin: 0 0 7px;
            color: var(--coffee);
        }

        h2 {
            font-size: 19px;
            color: var(--coffee);
            margin: 0 0 16px;
        }

        .sub {
            color: var(--muted);
            margin: 0;
        }

        .panel {
            background: var(--paper);
            border: 1px solid var(--line);
            border-radius: 15px;
            padding: 24px;
            margin-bottom: 22px;
            box-shadow: var(--shadow);
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 16px;
        }

        .stat {
            background: var(--paper);
            border: 1px solid var(--line);
            border-radius: 14px;
            padding: 20px;
            box-shadow: var(--shadow);
        }

        .stat-label {
            color: var(--muted);
            font-size: 13px;
        }

        .stat-value {
            font-size: 30px;
            font-weight: 800;
            color: var(--coffee);
            margin-top: 5px;
        }

        .stat-note {
            font-size: 12px;
            color: var(--muted);
        }

        label {
            display: block;
            font-weight: 700;
            font-size: 13px;
            margin: 13px 0 6px;
        }

        input,
        select {
            width: 100%;
            padding: 12px 13px;
            border: 1px solid #d9c9ba;
            border-radius: 8px;
            background: white;
            color: var(--ink);
            font: inherit;
            outline: none;
        }

        input:focus,
        select:focus {
            border-color: var(--gold);
            box-shadow: 0 0 0 3px rgba(201,155,98,.17);
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 0 16px;
        }

        .form-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 18px;
        }

        button,
        .button {
            display: inline-flex;
            align-items: center;
            justify-content: center;

            border: 0;
            border-radius: 8px;

            background: var(--coffee2);
            color: white;

            padding: 11px 16px;
            text-decoration: none;
            font-weight: 700;
            font: inherit;
            cursor: pointer;
            transition: .15s;
        }

        button:hover,
        .button:hover {
            background: var(--coffee);
            color: white;
        }

        .secondary {
            background: #eee4da;
            color: var(--coffee);
        }

        .secondary:hover {
            background: #e3d3c4;
            color: var(--coffee);
        }

        .danger {
            background: var(--red);
        }

        .danger:hover {
            background: #812c27;
        }

        .notice {
            padding: 13px 15px;
            border-radius: 9px;
            background: #eaf4eb;
            border: 1px solid #d3e8d5;
            margin: 0 0 18px;
        }

        .notice.error {
            background: #fff0ee;
            border-color: #f1d0cc;
            color: #8d302a;
        }

        .notice.warning {
            background: #fff7e8;
            border-color: #f0dfb9;
            color: #76501c;
        }

        .table-wrap {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 650px;
        }

        th,
        td {
            text-align: left;
            padding: 13px 12px;
            border-bottom: 1px solid var(--line);
            vertical-align: middle;
        }

        th {
            background: #f5eee6;
            color: var(--coffee);
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: .5px;
        }

        tbody tr:hover {
            background: #fffaf4;
        }

        .badge {
            display: inline-block;
            border-radius: 99px;
            padding: 4px 9px;
            font-size: 12px;
            font-weight: 700;
            background: #eee4da;
            color: var(--coffee);
        }

        .badge.active {
            background: #e5f3e8;
            color: #286344;
        }

        .badge.pending {
            background: #fff2d9;
            color: #8b5a0a;
        }

        .badge.disabled,
        .badge.low {
            background: #fde9e6;
            color: #9a332b;
        }


        .muted {
            color: var(--muted);
        }

        .small {
            font-size: 13px;
        }

        .actions {
            display: flex;
            gap: 7px;
            align-items: center;
            flex-wrap: wrap;
        }

        .inline {
            display: inline;
        }

        .searchbar {
            display: flex;
            gap: 10px;
            max-width: 460px;
        }

        .searchbar input {
            flex: 1;
        }

        .auth-shell {
            min-height: calc(100vh - 68px);
            display: grid;
            place-items: start center;
            padding: 45px 16px;
        }

        .auth-card {
            width: min(460px, 100%);
            background: white;
            border: 1px solid var(--line);
            border-radius: 17px;
            padding: 32px;
            box-shadow: 0 18px 55px rgba(65,43,29,.11);
        }

        .auth-brand {
            color: var(--coffee);
            font-size: 13px;
            font-weight: 800;
            letter-spacing: 1.2px;
            text-transform: uppercase;
            margin-bottom: 22px;
        }

        .auth-card h1 {
            font-size: 29px;
        }

        .auth-card .button,
        .auth-card button {
            width: 100%;
            margin-top: 8px;
        }

        .auth-foot {
            margin-top: 20px;
            font-size: 14px;
        }

        .footer {
            text-align: center;
            color: var(--muted);
            font-size: 12px;
            padding: 18px;
        }

        .empty {
            text-align: center;
            padding: 30px;
            color: var(--muted);
        }

        @media (max-width: 800px) {

            .grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .topbar {
                padding: 13px 4%;
            }

            .nav {
                width: 100%;
            }

            main {
                margin-top: 24px;
            }
        }

        @media (max-width: 560px) {

            .grid,
            .form-grid {
                grid-template-columns: 1fr;
            }

            .panel {
                padding: 17px;
            }

            .auth-card {
                padding: 24px;
            }

            .page-head {
                align-items: flex-start;
            }

            h1 {
                font-size: 26px;
            }

            .searchbar {
                max-width: none;
            }
        }

        </style>

    </head>

    <body>
    ';

    if (logged_in()) {

        echo '
        <header class="topbar">

            <a class="brand" href="dashboard.php">
                ☕ <span>MAGEL</span> Coffee Shop
            </a>

            <nav class="nav">
        ';

        $navigation = [
            'dashboard.php' => 'Dashboard',
            'inventory.php' => 'Inventory'
        ];

        foreach ($navigation as $url => $label) {

            echo '
                <a class="' . ($active === $url ? 'active' : '') . '"
                   href="' . h($url) . '">
                    ' . h($label) . '
                </a>
            ';
        }

        if (can_manage_inventory()) {

            echo '
                <a class="' . ($active === 'users.php' ? 'active' : '') . '"
                   href="users.php">
                    User Management
                </a>
            ';
        }


        echo '
                <span class="user-tag">
                    ' . h($name) . ' · '
                    . h(ucfirst($_SESSION['role'] ?? '')) . '
                </span>

                <a href="logout.php">
                    Log out
                </a>

            </nav>

        </header>
        ';

    } else {


        echo '
        <header class="topbar">

            <a class="brand" href="index.php">
                ☕ <span>MAGEL</span> Coffee Shop
            </a>

        </header>
        ';
    }


    echo '<main>';

    show_flash();
}


function page_end(): void
{
    echo '
        </main>

        <footer class="footer">
            © ' . date('Y') . '
            MAGEL Coffee Shop · Inventory Management System
        </footer>

    </body>
    </html>
    ';
}

