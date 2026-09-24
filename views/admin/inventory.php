<?php
$title = 'Stock Monitor';
$current = 'admin_inventory';
$heading = 'Stock Monitor';
$subheading = 'Monitor consumable stock against minimum levels';
require __DIR__ . '/_page_top.php';
?>
<section
    class="panel"
    id="admin-inventory-form-panel"
>
    <div class="panel-header">
        <div>
            <h2 id="admin-inventory-form-title">Smart Hospital Stock Monitor</h2>
        </div>
    </div>
    <form
        method="POST"
        class="form-grid"
        id="admin-inventory-form"
    >
        <input
            type="hidden"
            name="csrf_token"
            value="<?= esc(
                 csrf_token()
             ) ?>"
        >
        <input
            type="hidden"
            name="action"
            value="inventory_save"
        >
        <input
            type="hidden"
            name="section"
            value="inventory"
        >
        <input
            type="hidden"
            name="id"
            value=""
        >
        <div class="form-group">
            <label>Item</label>
            <select
                name="item_name"
                id="admin-inventory-item"
                required
            >
                <option value="">Select stock item</option><?php foreach (
                      $stockItemTypes
                      as $type
                  ): ?>
                <option
                    value="<?= esc($type['name']) ?>"
                    data-category="<?= esc(
                        $type['category']
                    ) ?>"
                ><?= esc($type['name']) ?></option><?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label>Category</label>
            <input
                id="admin-inventory-category-display"
                type="text"
                value=""
                readonly
            >
            <input
                type="hidden"
                name="category"
                id="admin-inventory-category"
            >
        </div>
        <div class="form-group">
            <label>Current quantity</label>
            <input
                type="number"
                min="0"
                name="quantity"
                value="0"
                required
            >
        </div>
        <div class="form-group">
            <label>Minimum level</label>
            <input
                type="number"
                min="0"
                name="minimum_level"
                value="10"
                required
            >
        </div>
        <div class="form-group full">
            <label>Supplier</label>
            <input name="supplier">
        </div>
        <div class="full form-actions">
            <button
                class="small-btn"
                id="admin-inventory-submit"
            >Add Stock Item</button>
            <button
                type="button"
                class="action-btn secondary"
                id="admin-inventory-cancel-edit"
                hidden
            >Cancel Edit</button>
        </div>
    </form>
    <div
        class="catalog-subform stock-catalog-subform"
        id="admin-stock-type-add"
    >
        <div class="catalog-subform-heading">
            <h3>Add Stock Item</h3>
        </div>
        <div class="catalog-subform-fields">
            <input
                type="text"
                id="admin-new-stock-type"
                maxlength="120"
                placeholder="Item name"
            >
            <select id="admin-new-stock-category">
                <option value="">Select category</option><?php foreach (
                      stock_categories()
                      as $category
                  ): ?>
                <option value="<?= esc($category) ?>"><?= esc(
                    $category
                ) ?></option><?php endforeach; ?>
            </select>
            <button
                type="button"
                class="small-btn"
                id="admin-add-stock-type"
            >Add</button><span
                class="catalog-message"
                id="admin-stock-type-message"
            ></span>
        </div>
    </div>
</section>
<section class="panel">
    <div class="panel-header">
        <div>
            <h2>Stock Records</h2>
            <p>Click a Last Updated time to view that item's complete recorded edit history.</p>
        </div>
    </div>
    <div class="toolbar admin-search-toolbar">
        <input
            id="admin-inventory-search"
            type="search"
            placeholder="Search by stock ID, item name, category, current quantity, minimum level, supplier or last updated"
            autocomplete="off"
        >
        <button
            id="admin-inventory-search-button"
            type="button"
            class="small-btn secondary"
        >Search</button>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Item</th>
                    <th>Category</th>
                    <th>Current</th>
                    <th>Minimum</th>
                    <th>Supplier</th>
                    <th>Last Updated</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody
                id="admin-inventory-table-body"
                data-empty-message="No matching stock records found."
            ><?php foreach (
                 $inventory
                 as $i
             ): ?>
                <tr>
                    <td>I<?= str_pad((string) (int) $i['id'], 4, '0', STR_PAD_LEFT) ?></td>
                    <td><?= esc(
                        $i['item_name']
                    ) ?></td>
                    <td><?= esc($i['category']) ?></td>
                    <td><?= esc($i['quantity']) ?></td>
                    <td><?= esc(
                        $i['minimum_level']
                    ) ?></td>
                    <td><?= esc(
                        $i['supplier'] ?? '—'
                    ) ?></td>
                    <td>
                        <button
                            type="button"
                            class="history-link admin-stock-history"
                            data-id="<?= $i[
                                'id'
                            ] ?>"
                            data-item="<?= esc($i['item_name']) ?>"
                        ><?= esc(
                            $i['updated_at_display'] ?? $i['updated_at']
                        ) ?></button>
                    </td>
                    <td><span class="badge <?= $i['stock_status'] === 'Low Stock'
                        ? 'low'
                        : 'in-stock' ?>"><?= esc(
                        $i['stock_status']
                    ) ?></span></td>
                    <td class="actions">
                        <button
                            type="button"
                            class="action-btn secondary admin-inventory-edit"
                            data-id="<?= $i[
                                'id'
                            ] ?>"
                            data-item="<?= esc($i['item_name']) ?>"
                            data-category="<?= esc(
                                $i['category']
                            ) ?>"
                            data-quantity="<?= esc($i['quantity']) ?>"
                            data-minimum="<?= esc(
                                $i['minimum_level']
                            ) ?>"
                            data-supplier="<?= esc(
                                $i['supplier'] ?? ''
                            ) ?>"
                        >Edit</button>
                        <form
                            method="POST"
                            class="inline-form"
                        >
                            <input
                                type="hidden"
                                name="csrf_token"
                                value="<?= esc(
                                    csrf_token()
                                ) ?>"
                            >
                            <input
                                type="hidden"
                                name="action"
                                value="inventory_delete"
                            >
                            <input
                                type="hidden"
                                name="section"
                                value="inventory"
                            >
                            <input
                                type="hidden"
                                name="id"
                                value="<?= $i[
                                    'id'
                                ] ?>"
                            >
                            <button class="action-btn danger">Delete</button>
                        </form>
                    </td>
                </tr><?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<div
    class="history-modal"
    id="admin-stock-history-modal"
    hidden
>
    <div
        class="history-modal-card"
        role="dialog"
        aria-modal="true"
        aria-labelledby="admin-stock-history-title"
    >
        <div class="history-modal-header">
            <div>
                <h2 id="admin-stock-history-title">Stock Edit History</h2>
                <p id="admin-stock-history-subtitle"></p>
            </div>
            <button
                type="button"
                class="history-close"
                id="admin-stock-history-close"
                aria-label="Close"
            >×</button>
        </div>
        <div
            id="admin-stock-history-content"
            class="history-list"
        >
            <p class="empty-cell">Loading history...</p>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../shared/bottom.php'; ?>
