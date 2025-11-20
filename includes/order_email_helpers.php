<?php
function buildOrderDetailsHtml(mysqli $conn, int $orderId): array {
    $orderId = max(1, intval($orderId));
    
    $stmt = $conn->prepare("
        SELECT *
        FROM view_order_transaction_details
        WHERE order_id = ?
    ");
    
    if (!$stmt) {
        return [
            'meta' => ['order_id' => $orderId, 'status' => null],
            'html' => "<p>Database error: Unable to prepare statement.</p>",
            'grandTotal' => 0.0
        ];
    }
    
    $stmt->bind_param("i", $orderId);
    $stmt->execute();
    $result = $stmt->get_result();

    $rows = [];
    $grandTotal = 0.0;
    $meta = null;

    while ($r = $result->fetch_assoc()) {
        if ($meta === null) {
            $meta = [
                'order_id'        => (int)$r['order_id'],
                'created_at'      => $r['created_at'],
                'status'          => $r['status'],
                'tracking_number' => $r['tracking_number'],
                'customer_email'  => filter_var($r['customer_email'], FILTER_SANITIZE_EMAIL),
                'recipient'       => htmlspecialchars($r['recipient'], ENT_QUOTES, 'UTF-8'),
                'street'          => htmlspecialchars($r['street'], ENT_QUOTES, 'UTF-8'),
                'barangay'        => htmlspecialchars($r['barangay'], ENT_QUOTES, 'UTF-8'),
                'city'            => htmlspecialchars($r['city'], ENT_QUOTES, 'UTF-8'),
                'province'        => htmlspecialchars($r['province'], ENT_QUOTES, 'UTF-8'),
                'zipcode'         => htmlspecialchars($r['zipcode'], ENT_QUOTES, 'UTF-8'),
            ];
        }

        $rows[] = [
            'product_name' => htmlspecialchars($r['product_name'], ENT_QUOTES, 'UTF-8'),
            'variant_name' => htmlspecialchars($r['color'] . ' ' . $r['material'], ENT_QUOTES, 'UTF-8'), // ✅ include variant
            'quantity'     => max(0, (int)$r['quantity']),
            'unit_price'   => max(0.0, (float)$r['unit_price']),
            'subtotal'     => max(0.0, (float)$r['subtotal']),
        ];
        $grandTotal += max(0.0, (float)$r['subtotal']);
    }
    
    $stmt->close();

    if ($meta === null) {
        return [
            'meta' => ['order_id' => $orderId, 'status' => null],
            'html' => "<p>No items found for this order.</p>",
            'grandTotal' => 0.0
        ];
    }

    $html = "<table border='1' cellpadding='8' cellspacing='0' style='border-collapse:collapse;width:100%;'>
        <thead style='background:#f6f6f6;'>
            <tr>
                <th align='left'>Product</th>
                <th align='left'>Variant</th>
                <th align='center'>Qty</th>
                <th align='right'>Unit Price</th>
                <th align='right'>Subtotal</th>
            </tr>
        </thead>
        <tbody>";

    foreach ($rows as $it) {
        $html .= "<tr>
            <td>" . $it['product_name'] . "</td>
            <td>" . $it['variant_name'] . "</td>
            <td align='center'>" . $it['quantity'] . "</td>
            <td align='right'>" . number_format($it['unit_price'], 2) . "</td>
            <td align='right'>" . number_format($it['subtotal'], 2) . "</td>
        </tr>";
    }

    $html .= "</tbody>
        <tfoot>
            <tr>
                <td colspan='4' align='right'><strong>Grand Total</strong></td>
                <td align='right'><strong>" . number_format($grandTotal, 2) . "</strong></td>
            </tr>
        </tfoot>
    </table>";

    return ['meta' => $meta, 'html' => $html, 'grandTotal' => $grandTotal];
}

function buildAddressBlock(array $m): string {
    $parts = array_filter([
        $m['recipient'] ?? null,
        $m['street'] ?? null,
        $m['barangay'] ?? null,
        $m['city'] ?? null,
        $m['province'] ?? null,
        $m['zipcode'] ?? null,
    ]);
    return htmlspecialchars(implode(', ', $parts), ENT_QUOTES, 'UTF-8');
}