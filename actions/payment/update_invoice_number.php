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
        $invoice_number = $_POST['invoice_number'];

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
            LIMIT 1
        ");
        $stmt->execute([$payment['payment_id'], $user_id]);
        $transaction = $stmt->fetch();

        if (!$transaction) {
            throw new Exception('ไม่พบข้อมูลการชำระเงินสำหรับผู้ใช้นี้');
        }

        // อัพเดทเลขที่ใบวางบิล
        $stmt = $conn->prepare("
            UPDATE transactions 
            SET invoice_number = ?
            WHERE transaction_id = ?
        ");
        
        $stmt->execute([$invoice_number, $transaction['transaction_id']]);
        
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}