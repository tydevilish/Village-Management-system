<?php
session_start();
require_once '../../config/config.php';
require_once '../../includes/auth.php';

// ตรวจสอบสิทธิ์
checkPageAccess(PAGE_MANAGE_PAYMENT);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $payment_id = $_POST['payment_id'];
        $user_id = $_POST['user_id'];
        $penalty = floatval($_POST['penalty']);

        // ตรวจสอบว่ามีข้อมูลใน transactions หรือไม่
        $stmt = $conn->prepare("
            SELECT COUNT(*) FROM transactions 
            WHERE payment_id = :payment_id AND user_id = :user_id
        ");
        $stmt->execute([
            'payment_id' => $payment_id,
            'user_id' => $user_id
        ]);
        
        if ($stmt->fetchColumn() == 0) {
            // ถ้าไม่มีข้อมูล ให้ดึง amount จากตาราง payments
            $stmt = $conn->prepare("SELECT amount FROM payments WHERE payment_id = ?");
            $stmt->execute([$payment_id]);
            $base_amount = $stmt->fetchColumn();

            // เพิ่มข้อมูลใหม่ในตาราง transactions
            $stmt = $conn->prepare("
                INSERT INTO transactions (
                    payment_id, 
                    user_id, 
                    amount,
                    penalty,
                    status
                ) VALUES (
                    :payment_id, 
                    :user_id, 
                    :amount,
                    :penalty,
                    'not_paid'
                )
            ");
            $stmt->execute([
                'payment_id' => $payment_id,
                'user_id' => $user_id,
                'amount' => $base_amount,
                'penalty' => $penalty
            ]);
        } else {
            // ถ้ามีข้อมูลแล้ว ให้อัพเดท penalty
            $stmt = $conn->prepare("
                UPDATE transactions 
                SET penalty = :penalty 
                WHERE payment_id = :payment_id AND user_id = :user_id
            ");
            $stmt->execute([
                'payment_id' => $payment_id,
                'user_id' => $user_id,
                'penalty' => $penalty
            ]);
        }

        echo json_encode([
            'success' => true,
            'message' => 'อัพเดทเบี้ยปรับเรียบร้อยแล้ว'
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()
        ]);
    }
}