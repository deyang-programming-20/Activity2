
<?php

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

require_login();

$total = (int) $pdo->query("
    SELECT COUNT(*)
    FROM inventory
")->fetchColumn();

$low = (int) $pdo->query("
    SELECT COUNT(*)
    FROM inventory
    WHERE quantity <= 5
")->fetchColumn();

$value = (float) $pdo->query("
    SELECT COALESCE(SUM(quantity * unit_price), 0)
    FROM inventory
")->fetchColumn();

$pending = can_manage_inventory()
    ? (int) $pdo->query("
        SELECT COUNT(*)
        FROM users
        WHERE status = 'pending'
    ")->fetchColumn()
    : 0;

$recent = $pdo->query("
    SELECT
        product_name,
        category,
        quantity,
        unit,
        unit_price
    FROM inventory
    ORDER BY updated_at DESC, id DESC
    LIMIT 6
")->fetchAll();

page_start('Dashboard');

?>

<div class="page-head">
    <div>
        <h1>Dashboard</h1>

        <p class="sub">
            Good day, <?= h($_SESSION['full_name']) ?>.
            Here's your shop overview.
        </p>
    </div>

    <a class="button" href="inventory.php">
        View inventory →
    </a>
</div>

<div class="grid">

    <div class="stat">
        <div class="stat-label">Total products</div>

        <div class="stat-value">
            <?= $total ?>
        </div>

        <div class="stat-note">
            Items in your inventory
        </div>
    </div>

    <div class="stat">
        <div class="stat-label">Low-stock items</div>

        <div class="stat-value">
            <?= $low ?>
        </div>

        <div class="stat-note">
            Quantity of 5 or below
        </div>
    </div>

    <div class="stat">
        <div class="stat-label">Estimated stock value</div>

        <div class="stat-value">
            ₱<?= number_format($value, 2) ?>
        </div>

        <div class="stat-note">
            Based on quantity × unit price
        </div>
    </div>

    <?php if (can_manage_inventory()): ?>

        <div class="stat">
            <div class="stat-label">Pending accounts</div>

            <div class="stat-value">
                <?= $pending ?>
            </div>

            <div class="stat-note">
                <a href="users.php">
                    Review staff requests
                </a>
            </div>
        </div>

    <?php else: ?>

        <div class="stat">
            <div class="stat-label">Your access</div>

            <div class="stat-value" style="font-size: 24px;">
                Staff
            </div>

            <div class="stat-note">
                View-only inventory access
            </div>
        </div>

    <?php endif; ?>

</div>

<section class="panel" style="margin-top: 22px;">

    <div class="page-head" style="margin-bottom: 12px;">
        <div>
            <h2>Recently updated products</h2>

            <p class="sub small">
                A quick look at the latest inventory records.
            </p>
        </div>
    </div>

    <div class="table-wrap">

        <table>

            <thead>
                <tr>
                    <th>Product</th>
                    <th>Category</th>
                    <th>Quantity</th>
                    <th>Unit price</th>
                </tr>
            </thead>

            <tbody>

                <?php foreach ($recent as $r): ?>

                    <tr>

                        <td>
                            <strong>
                                <?= h($r['product_name']) ?>
                            </strong>
                        </td>

                        <td>
                            <?= h($r['category']) ?>
                        </td>

                        <td>
                            <?= h($r['quantity']) ?>
                            <?= h($r['unit']) ?>

                            <?php if ((float) $r['quantity'] <= 5): ?>

                                <span class="badge low">
                                    Low stock
                                </span>

                            <?php endif; ?>
                        </td>

                        <td>
                            ₱<?= number_format(
                                (float) $r['unit_price'],
                                2
                            ) ?>
                        </td>

                    </tr>

                <?php endforeach; ?>

                <?php if (!$recent): ?>

                    <tr>
                        <td colspan="4" class="empty">
                            No products yet. Add your first item in Inventory.
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
