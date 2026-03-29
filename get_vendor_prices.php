<?php
include 'db.php';
header('Content-Type: application/json');

$item_id = intval($_GET['item_id'] ?? 0);
if (!$item_id) { echo json_encode(['vendors' => []]); exit(); }

// Get item band info
$item_r = $conn->query("SELECT veg_name, price, min_price, max_price FROM items WHERE id=$item_id LIMIT 1");
$item   = $item_r ? $item_r->fetch_assoc() : null;

// vendor_prices.vendor_id references users.id (101-150)
// users table has full_name, phone, stall_number for vendors
$sql = "SELECT u.full_name AS name, u.stall_number, u.phone, vp.selling_price
        FROM vendor_prices vp
        JOIN users u ON vp.vendor_id = u.id
        WHERE vp.item_id = $item_id
          AND u.role = 'vendor'
          AND u.status = 'approved'
        ORDER BY vp.selling_price ASC";

$res = $conn->query($sql);

$vendors = [];
if ($res && $res->num_rows > 0) {
    while ($r = $res->fetch_assoc()) {
        $vendors[] = [
            'name'  => $r['name'],
            'stall' => $r['stall_number'] ?? 'N/A',
            'phone' => $r['phone'],
            'price' => number_format($r['selling_price'], 2),
        ];
    }
}

$band = null;
if ($item && !empty($item['min_price']) && !empty($item['max_price'])) {
    $band = [
        'min' => number_format($item['min_price'], 0),
        'max' => number_format($item['max_price'], 0),
        'ref' => number_format($item['price'], 2),
    ];
}

echo json_encode(['vendors' => $vendors, 'band' => $band]);
?>
