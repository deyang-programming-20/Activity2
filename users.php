
<?php

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

require_manager();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    verify_csrf();

    $id = (int) ($_POST['user_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if ($id === (int) $_SESSION['user_id']) {

        flash(
            'You cannot change your own account here.',
            'error'
        );

    } elseif ($action === 'update') {

        $role = $_POST['role'] ?? 'staff';
        $status = $_POST['status'] ?? 'pending';

        if (
            !in_array($role, ['staff', 'manager'], true) ||
            !in_array($status, ['pending', 'active', 'disabled'], true)
        ) {

            flash(
                'Invalid role or account status.',
                'error'
            );

        } else {

            $stmt = $pdo->prepare("
                SELECT role
                FROM users
                WHERE id = ?
            ");

            $stmt->execute([$id]);

            $target = $stmt->fetch();

            if (!$target || $target['role'] === 'owner') {

                flash(
                    'Owner accounts are protected.',
                    'error'
                );

            } elseif (
                ($_SESSION['role'] ?? '') === 'manager' &&
                $role === 'manager'
            ) {

                flash(
                    'Only the owner can assign manager access.',
                    'error'
                );

            } else {
                $stmt = $pdo->prepare("
                    UPDATE users
                    SET role = ?, status = ?
                    WHERE id = ?
                    AND role <> 'owner'
                ");

                $stmt->execute([
                    $role,
                    $status,
                    $id
                ]);

                flash('User account updated.');
            }
        }
    }

    header('Location: users.php');
    exit;
}

$users = $pdo->query("
    SELECT
        id,
        full_name,
        email,
        role,
        status,
        created_at
    FROM users
    ORDER BY
        CASE status
            WHEN 'pending' THEN 0
            ELSE 1
        END,
        created_at DESC
")->fetchAll();

page_start('User Management');

?>

<div class="page-head">

    <div>
        <h1>User management</h1>

        <p class="sub">
            Approve staff accounts and manage access permissions.
        </p>
    </div>

</div>

<section class="panel">

    <div class="table-wrap">

        <table>

            <thead>

                <tr>
                    <th>Name / email</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Joined</th>
                    <th>Manage account</th>
                </tr>

            </thead>

            <tbody>

                <?php foreach ($users as $u): ?>

                    <tr>

                        <td>

                            <strong>
                                <?= h($u['full_name']) ?>
                            </strong>

                            <br>

                            <span class="small muted">
                                <?= h($u['email']) ?>
                            </span>

                        </td>

                        <td>

                            <span class="badge">
                                <?= h(ucfirst($u['role'])) ?>
                            </span>

                        </td>

                        <td>

                            <span class="badge <?= h($u['status']) ?>">
                                <?= h(ucfirst($u['status'])) ?>
                            </span>

                        </td>

                        <td>
                            <?= h(date(
                                'M j, Y',
                                strtotime($u['created_at'])
                            )) ?>
                        </td>

                        <td>

                            <?php if (
                                $u['role'] === 'owner' ||
                                (int) $u['id'] === (int) $_SESSION['user_id']
                            ): ?>

                                <span class="small muted">
                                    Protected account
                                </span>

                            <?php else: ?>

                                <form method="post">

                                    <input
                                        type="hidden"
                                        name="csrf_token"
                                        value="<?= h(csrf_token()) ?>"
                                    >

                                    <input
                                        type="hidden"
                                        name="user_id"
                                        value="<?= (int) $u['id'] ?>"
                                    >

                                    <input
                                        type="hidden"
                                        name="action"
                                        value="update"
                                    >

                                    <label>Role</label>

                                    <select name="role">

                                        <?php if (
                                            ($_SESSION['role'] ?? '') === 'owner'
                                        ): ?>

                                            <option
                                                value="staff"
                                                <?= $u['role'] === 'staff' ? 'selected' : '' ?>
                                            >
                                                Staff
                                            </option>

                                            <option
                                                value="manager"
                                                <?= $u['role'] === 'manager' ? 'selected' : '' ?>
                                            >
                                                Manager
                                            </option>

                                        <?php else: ?>

                                            <option value="staff" selected>
                                                Staff
                                            </option>

                                        <?php endif; ?>

                                    </select>

                                    <label>Status</label>

                                    <select name="status">

                                        <option
                                            value="pending"
                                            <?= $u['status'] === 'pending' ? 'selected' : '' ?>
                                        >
                                            Pending
                                        </option>

                                        <option
                                            value="active"
                                            <?= $u['status'] === 'active' ? 'selected' : '' ?>
                                        >
                                            Active / Approve
                                        </option>

                                        <option
                                            value="disabled"
                                            <?= $u['status'] === 'disabled' ? 'selected' : '' ?>
                                        >
                                            Disabled
                                        </option>

                                    </select>

                                    <button
                                        type="submit"
                                        style="margin-top: 10px;"
                                    >
                                        Save changes
                                    </button>

                                </form>

                            <?php endif; ?>

                        </td>

                    </tr>

                <?php endforeach; ?>

                <?php if (!$users): ?>

                    <tr>

                        <td colspan="5" class="empty">
                            No user accounts found.
                        </td>

                    </tr>

                <?php endif; ?>

            </tbody>

        </table>

    </div>

</section>


<?php

page_end();

?>
