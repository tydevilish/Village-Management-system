<?php
session_start();
require_once '../../config/config.php';
require_once '../../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payment_id = $_POST['payment_id'];
    $reject_reason = $_POST['reject_reason'];
    $user_id = $_SESSION['user_id'];

    try {
        // บันทึกข้อมูลการปฏิเสธลงในตาราง transactions
        $stmt = $conn->prepare("INSERT INTO transactions (payment_id, user_id, status, reject_reason, created_at) VALUES (:payment_id, :user_id, 'rejected', :reject_reason, NOW())");
        
        $stmt->execute([
            'payment_id' => $payment_id,
            'user_id' => $user_id,
            'reject_reason' => $reject_reason
        ]);

        $_SESSION['success'] = "บันทึกการปฏิเสธการชำระเงินเรียบร้อยแล้ว";
    } catch(PDOException $e) {
        $_SESSION['error'] = "เกิดข้อผิดพลาด: " . $e->getMessage();
    }

    header("Location: ../../pages/payment/payment.php");
    exit();
} 