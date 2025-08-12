<?php
require_once("../backend-php/db.php");

$from_date = isset($_GET['from_date']) ? $_GET['from_date'] : '';
$to_date   = isset($_GET['to_date'])   ? $_GET['to_date']   : '';
$trade     = isset($_GET['trade'])     ? $_GET['trade']     : '';
$status    = isset($_GET['status'])    ? $_GET['status']    : '';
$preview   = isset($_GET['preview'])   ? true : false;

$where = [];
if($from_date) $where[] = "ar.date >= '$from_date'";
if($to_date)   $where[] = "ar.date <= '$to_date'";
if($trade)     $where[] = "u.trade = '".mysqli_real_escape_string($conn, $trade)."'";
if($status && $status != 'absent') $where[] = "ar.status = '".mysqli_real_escape_string($conn, $status)."'";
$where_sql = $where ? "WHERE ".implode(' AND ', $where) : "";

$query = mysqli_query($conn, "
  SELECT ar.date, u.name, u.role, u.trade, ar.status, ar.reason, ar.remarks
  FROM attendance_requests ar
  JOIN users u ON ar.student_id = u.id
  $where_sql
  ORDER BY ar.date DESC
");

function rowToArr($row) {
  $status_label = ($row['status'] == 'approved') ? 'Present' : (ucfirst($row['status']) == 'Rejected' ? 'Rejected' : 'Absent');
  return [
    $row['date'],
    $row['name'],
    ucfirst($row['role']),
    $row['trade'],
    $status_label,
    $row['reason'],
    $row['remarks']
  ];
}

if ($preview) {
  // Return as JSON (for preview)
  $result = [];
  while($row = mysqli_fetch_assoc($query)) {
    if($status == 'absent' && $row['status'] == 'approved') continue;
    $result[] = rowToArr($row);
  }
  header('Content-Type: application/json');
  echo json_encode($result);
  exit;
} else {
  // Return as CSV (download)
  header('Content-Type: text/csv');
  header('Content-Disposition: attachment;filename="attendance_report.csv"');
  $output = fopen('php://output', 'w');
  fputcsv($output, ['Date', 'Student Name', 'Role', 'Trade', 'Status', 'Reason', 'Remarks']);
  while($row = mysqli_fetch_assoc($query)) {
    if($status == 'absent' && $row['status'] == 'approved') continue;
    fputcsv($output, rowToArr($row));
  }
  fclose($output);
  exit;
}
?>