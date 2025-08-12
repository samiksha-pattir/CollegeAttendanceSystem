<?php
require_once("../backend-php/db.php");

$query = "SELECT u.trade, ar.date, COUNT(*) AS present_count
          FROM attendance_requests ar
          JOIN users u ON ar.student_id = u.id
          WHERE ar.status = 'approved'
            AND ar.date >= CURDATE() - INTERVAL 6 DAY
          GROUP BY u.trade, ar.date
          ORDER BY ar.date ASC, u.trade ASC";
$result = mysqli_query($conn, $query);

$data = [];
$trades = [];
$dates = [];

// Prepare raw data
while($row = mysqli_fetch_assoc($result)) {
    $trade = $row['trade'];
    $date = $row['date'];
    $present_count = (int)$row['present_count'];
    $data[$trade][$date] = $present_count;
    if(!in_array($trade, $trades)) $trades[] = $trade;
    if(!in_array($date, $dates)) $dates[] = $date;
}

// Sort dates ASC
sort($dates);

// Prepare chart.js datasets structure
$datasets = [];
$colors = ['#0d6efd','#dc3545','#ffc107','#198754','#6f42c1','#fd7e14','#20c997','#6610f2','#0dcaf0','#e83e8c'];
$colorIndex = 0;
foreach($trades as $trade) {
    $counts = [];
    foreach($dates as $date) {
        $counts[] = isset($data[$trade][$date]) ? $data[$trade][$date] : 0;
    }
    $datasets[] = [
        "label" => $trade,
        "data" => $counts,
        "borderColor" => $colors[$colorIndex % count($colors)],
        "backgroundColor" => $colors[$colorIndex % count($colors)],
        "tension" => 0.4,
        "fill" => false
    ];
    $colorIndex++;
}

echo json_encode([
    "labels" => $dates,
    "datasets" => $datasets
]);
?>