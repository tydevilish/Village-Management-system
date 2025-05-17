<?php
session_start();
require_once '../../config/config.php';
require_once '../../includes/auth.php';

checkPageAccess(PAGE_MANAGE_PAYMENT);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $month = $_POST['month'];
        $year = (int)$_POST['year'] + 543;
        $user_id = $_POST['user_id'];
        $receipt_number = $_POST['receipt_number'];

        // ค้นหา payment_id ก่อน
        $stmt = $conn->prepare("
            SELECT payment_id 
            FROM payments 
            WHERE month = ? 
            AND year = ? 
            AND status = 'active'
            LIMIT 1
        ");
        $stmt->execute([$month, $year]);
        $payment = $stmt->fetch();

        if (!$payment) {
            throw new Exception('ไม่พบข้อมูลค่าส่วนกลางสำหรับเดือน ' . $month . ' ปี ' . $year);
        }

        // จากนั้นค้นหา transaction
        $stmt = $conn->prepare("
            SELECT transaction_id 
            FROM transactions 
            WHERE payment_id = ? 
            AND user_id = ? 
            AND status = 'approved'
            LIMIT 1
        ");
        $stmt->execute([$payment['payment_id'], $user_id]);
        $transaction = $stmt->fetch();

        if (!$transaction) {
            throw new Exception('ไม่พบข้อมูลการชำระเงินที่อนุมัติแล้วสำหรับผู้ใช้นี้');
        }

        // อัพเดทเลขที่ใบเสร็จ
        $stmt = $conn->prepare("
            UPDATE transactions 
            SET receipt_number = ? 
            WHERE transaction_id = ?
        ");
        
        $stmt->execute([$receipt_number,  $transaction['transaction_id']]);
        
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} 