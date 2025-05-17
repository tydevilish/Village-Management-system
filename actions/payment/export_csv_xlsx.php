<?php
session_start();
require_once '../../config/config.php';
require_once '../../includes/auth.php';
require_once '../../vendor/autoload.php';

// ตรวจสอบสิทธิ์
checkPageAccess(PAGE_MANAGE_PAYMENT);

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

try {
    // ดึงข้อมูลจากฐานข้อมูล
    $stmt = $conn->prepare("
        SELECT 
            t.payment_id,
            t.user_id,
            CASE 
                WHEN t.status = 'pending' THEN 'รอตรวจสอบ'
                WHEN t.status = 'approved' THEN 'อนุมัติ'
                WHEN t.status = 'rejected' THEN 'ไม่อนุมัติ'
            END as status,
            t.reject_reason
        FROM transactions t
        ORDER BY t.payment_id ASC
    ");
    $stmt->execute();
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // สร้าง spreadsheet ใหม่
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // กำหนดหัวข้อคอลัมน��
    $sheet->setCellValue('A1', 'ลำดับค่าส่วนกลาง');
    $sheet->setCellValue('B1', 'รหัสผู้ใช้');
    $sheet->setCellValue('C1', 'สถานะ');
    $sheet->setCellValue('D1', 'เหตุผลที่ไม่อนุมัติ');

    // ใส่ข้อมูล
    $row = 2;
    foreach ($transactions as $transaction) {
        $sheet->setCellValue('A' . $row, $transaction['payment_id']);
        $sheet->setCellValue('B' . $row, $transaction['user_id']);
        $sheet->setCellValue('C' . $row, $transaction['status']);
        $sheet->setCellValue('D' . $row, $transaction['reject_reason']);
        $row++;
    }

    // ตั้งค่าความกว้างคอลัมน์อัตโนมัติ
    foreach (range('A', 'D') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }

    // สร้างไฟล์ XLSX
    $writer = new Xlsx($spreadsheet);
    
    // ตั้งค่า header สำหรับดาวน์โหลด
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="payment_transactions.xlsx"');
    header('Cache-Control: max-age=0');

    // ส่งไฟล์ไปยังเบ���าว์เซอร์
    $writer->save('php://output');
    
    // ล้างหน่วยความจำ
    $spreadsheet->disconnectWorksheets();
    unset($spreadsheet);
    exit;

} catch (Exception $e) {
    $_SESSION['error'] = "เกิดข้อผิดพลาดในการส่งออกไฟล์: " . $e->getMessage();
    header('Location: ../../pages/payment/manage_payment.php');
    exit();
} 