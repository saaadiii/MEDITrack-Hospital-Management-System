<?php
$logoutRole = 'admin';
$nav = [
    ['admin', 'index.php?page=admin', 'Dashboard'],
    ['admin_billing', 'index.php?page=admin_billing', 'Cost & Billing'],
    ['admin_staff', 'index.php?page=admin_staff', 'Staff'],
    ['admin_equipment', 'index.php?page=admin_equipment', 'Equipment'],
    ['admin_inventory', 'index.php?page=admin_inventory', 'Stock Monitor'],
    ['admin_doctors', 'index.php?page=admin_doctors', 'Doctors'],
    ['admin_records', 'index.php?page=admin_records', 'All Records']
];
require __DIR__ . '/../shared/top.php';
?>
