<?php
session_start();
require_once '../../config/config.php';
require_once '../../includes/auth.php';

// ตรวจสอบสิทธิ์
checkPageAccess(PAGE_MANAGE_PAYMENT);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $payment_id = $_POST['payment_id'];
        
        // เริ่ม transaction
        $conn->beginTransaction();
        
        // 1. ลบข้อมูลที่เกี่ยวข้องในตาราง advance_payment_transactions ก่อน
        $stmt = $conn->prepare("DELETE FROM advance_payment_transactions WHERE payment_id = ?");
        $stmt->execute([$payment_id]);
        
        // 2. ลบข้อมูลที่เกี่ยวข้องในตาราง transactions
        $stmt = $conn->prepare("DELETE FROM transactions WHERE payment_id = ?");
        $stmt->execute([$payment_id]);
        
        // 3. ลบข้อมูลจากตาราง payments
        $stmt = $conn->prepare("DELETE FROM payments WHERE payment_id = ?");
        $stmt->execute([$payment_id]);
        
        $conn->commit();
        
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        $conn->rollBack();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}