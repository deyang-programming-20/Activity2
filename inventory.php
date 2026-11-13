
<?php

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    verify_csrf();

    if (!can_manage_inventory()) {

        http_response_code(403);
        exit('Access denied.');

    }

    $action = $_POST['action'] ?? '';

    if ($action === 'delete') {

        $id = (int) ($_POST['id'] ?? 0);

        $stmt = $pdo->prepare("
            DELETE FROM inventory
            WHERE id = ?
        ");

        $stmt->execute([$id]);

        $deleted = $stmt->rowCount() > 0;

        flash(
            $deleted ? 'Product deleted.' : 'Product not found.',
            $deleted ? 'success' : 'error'
        );

    }

    elseif ($action === 'save') {

        $id = (int) ($_POST['id'] ?? 0);

        $name = trim($_POST['product_name'] ?? '');

        $category = trim($_POST['category'] ?? '');

        $quantity = filter_var(
            $_POST['quantity'] ?? null,
            FILTER_VALIDATE_FLOAT
        );

        $unit = trim($_POST['unit'] ?? '');

        $price = filter_var(
            $_POST['unit_price'] ?? null,
            FILTER_VALIDATE_FLOAT
        );

        if (
            $name === '' ||
            mb_strlen($name) > 150 ||

            $category === '' ||
            mb_strlen($category) > 80 ||

            $quantity === false ||
            $quantity < 0 ||

            $unit === '' ||
            mb_strlen($unit) > 30 ||

            $price === false ||
            $price < 0
        ) {

            flash(
                'Please enter valid product details. Quantity and price cannot be negative.',
                'error'
            );

        } else {

            if ($id > 0) {

                $stmt = $pdo->prepare("
                    UPDATE inventory
                    SET
                        product_name = ?,
                        category = ?,
                        quantity = ?,
                        unit = ?,
                        unit_price = ?
                    WHERE id = ?
                ");

                $stmt->execute([
                    $name,
                    $category,
                    $quantity,
                    $unit,
                    $price,
                    $id
                ]);

                flash('Product updated successfully.');

            }

            else {

                $stmt = $pdo->prepare("
                    INSERT INTO inventory (
                        product_name,
                        category,
                        quantity,
                        unit,
                        unit_price
                    )
                    VALUES (?, ?, ?, ?, ?)
                ");

                $stmt->execute([
                    $name,
                    $category,
                    $quantity,
                    $unit,
                    $price
                ]);

                flash('Product added successfully.');

            }
        }
    }

    header('Location: inventory.php');
    exit;

}

$edit = null;

if (
    can_manage_inventory() &&
    isset($_GET['edit'])
) {

    $stmt = $pdo->prepare("
        SELECT *
        FROM inventory
        WHERE id = ?
    ");

    $stmt->execute([
        (int) $_GET['edit']
    ]);

    $edit = $stmt->fetch() ?: null;

}

$q = trim($_GET['q'] ?? '');

if ($q !== '') {

    $stmt = $pdo->prepare("
        SELECT *
        FROM inventory
        WHERE
            product_name LIKE ?
            OR category LIKE ?
        ORDER BY product_name
    ");

    $like = '%' . $q . '%';

    $stmt->execute([
        $like,
        $like
    ]);

    $items = $stmt->fetchAll();

} else {

    $items = $pdo->query("
        SELECT *
        FROM inventory
        ORDER BY product_name
    ")->fetchAll();

}

page_start('Inventory');

?>

<div class="page-head">

    <div>
        <h1>Inventory</h1>

        <p class="sub">
            Track products, quantities, categories, and prices.
        </p>
    </div>

</div>

<?php if (can_manage_inventory()): ?>

    <section class="panel">

        <h2>
            <?= $edit ? 'Edit product' : 'Add a product' ?>
        </h2>

        <form method="post">

            <input
                type="hidden"
                name="csrf_token"
                value="<?= h(csrf_token()) ?>"
            >

            <input
                type="hidden"
                name="action"
                value="save"
            >

            <input
                type="hidden"
                name="id"
                value="<?= (int) ($edit['id'] ?? 0) ?>"
            >

            <div class="form-grid">

                <div>

                    <label>Product name</label>

                    <input
                        type="text"
                        name="product_name"
                        maxlength="150"
                        required
                        value="<?= h($edit['product_name'] ?? '') ?>"
                        placeholder="e.g. Arabica Coffee Beans"
                    >

                </div>
                <div>

                    <label>Category</label>

                    <input
                        type="text"
                        name="category"
                        maxlength="80"
                        required
                        value="<?= h($edit['category'] ?? '') ?>"
                        placeholder="Coffee, milk, pastry..."
                    >

                </div>

                <div>

                    <label>Quantity</label>

                    <input
                        type="number"
                        name="quantity"
                        min="0"
                        step="0.01"
                        required
                        value="<?= h($edit['quantity'] ?? '0') ?>"
                    >

                </div>

                <div>

                    <label>Unit</label>

                    <input
                        type="text"
                        name="unit"
                        maxlength="30"
                        required
                        value="<?= h($edit['unit'] ?? 'pcs') ?>"
                        placeholder="pcs, kg, L"
                    >

                </div>

                <div>

                    <label>Unit price (PHP)</label>

                    <input
                        type="number"
                        name="unit_price"
                        min="0"
                        step="0.01"
                        required
                        value="<?= h($edit['unit_price'] ?? '0') ?>"
                    >

                </div>

            </div>

            <div class="form-actions">

                <button type="submit">

                    <?= $edit ? 'Save changes' : 'Add product' ?>

                </button>

                <?php if ($edit): ?>

                    <a
                        class="button secondary"
                        href="inventory.php"
                    >
                        Cancel
                    </a>

                <?php endif; ?>

            </div>

        </form>

    </section>


<?php else: ?>

    <div class="notice warning">

        You have staff access.
        You can view inventory, but only an owner or manager
        can add, edit, or delete products.

    </div>

<?php endif; ?>

<section class="panel">

    <div class="page-head" style="margin-bottom: 16px;">

        <div>

            <h2 style="margin: 0;">
                Product list
            </h2>

            <p class="sub small">
                <?= count($items) ?> result(s)
            </p>

        </div>

        <form class="searchbar" method="get">

            <input
                type="search"
                name="q"
                value="<?= h($q) ?>"
                placeholder="Search product or category"
            >

            <button type="submit">
                Search
            </button>

            <?php if ($q !== ''): ?>

                <a
                    class="button secondary"
                    href="inventory.php"
                >
                    Clear
                </a>

            <?php endif; ?>

        </form>
    </div>

    <div class="table-wrap">

        <table>

            <thead>

                <tr>

                    <th>Product</th>

                    <th>Category</th>

                    <th>Quantity</th>

                    <th>Unit price</th>


                    <?php if (can_manage_inventory()): ?>

                        <th>Actions</th>

                    <?php endif; ?>

                </tr>

            </thead>

            <tbody>

                <?php foreach ($items as $item): ?>

                    <tr>

                        <td>

                            <strong>
                                <?= h($item['product_name']) ?>
                            </strong>

                        </td>

                        <td>
                            <?= h($item['category']) ?>
                        </td>

                        <td>

                            <?= h($item['quantity']) ?>

                            <?= h($item['unit']) ?>


                            <?php if ((float) $item['quantity'] <= 5): ?>

                                <span class="badge low">
                                    Low
                                </span>

                            <?php endif; ?>

                        </td>

                        <td>

                            ₱<?= number_format(
                                (float) $item['unit_price'],
                                2
                            ) ?>

                        </td>

                        <?php if (can_manage_inventory()): ?>

                            <td>

                                <div class="actions">

                                    <a
                                        class="button secondary"
                                        href="inventory.php?edit=<?= (int) $item['id'] ?>"
                                    >
                                        Edit
                                    </a>

                                    <form
                                        class="inline"
                                        method="post"
                                        onsubmit="return confirm('Delete this product?')"
                                    >

                                        <input
                                            type="hidden"
                                            name="csrf_token"
                                            value="<?= h(csrf_token()) ?>"
                                        >

                                        <input
                                            type="hidden"
                                            name="action"
                                            value="delete"
                                        >

                                        <input
                                            type="hidden"
                                            name="id"
                                            value="<?= (int) $item['id'] ?>"
                                        >

                                        <button
                                            class="danger"
                                            type="submit"
                                        >
                                            Delete
                                        </button>

                                    </form>

                                </div>

                            </td>

                        <?php endif; ?>


                    </tr>

                <?php endforeach; ?>



                <?php if (!$items): ?>

                    <tr>

                        <td
                            colspan="<?= can_manage_inventory() ? 5 : 4 ?>"
                            class="empty"
                        >

                            No products found.

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
