<?php
session_start();
require_once '../../config/config.php';
require_once '../../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['payment_id'])) {
    try {
        // ตรวจสอบไฟล์ที่อัปโหลด
        if (!isset($_FILES['slip_image']) || $_FILES['slip_image']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('กรุณาอัปโหลดหลักฐานการโอนเงิน');
        }

        $payment_id = $_POST['payment_id'];
        $user_id = $_SESSION['user_id'];
        
        // เพิ่มการตรวจสอบสิทธิ์ก่อนการชำระเงิน
        $stmt = $conn->prepare("
            SELECT 1 FROM transactions 
            WHERE payment_id = ? AND user_id = ?
        ");
        $stmt->execute([$payment_id, $user_id]);
        if ($stmt->rowCount() === 0) {
            throw new Exception('คุณไม่มีสิทธิ์ชำระเงินรายการนี้');
        }

        // เริ่ม transaction
        $conn->beginTransaction();
        
        // ลบรายการเก่าที่ถูกปฏิเสธ (ถ้ามี)
        $stmt = $conn->prepare("DELETE FROM transactions 
                              WHERE payment_id = ? 
                              AND user_id = ? 
                              AND status = 'rejected'");
        $stmt->execute([$payment_id, $user_id]);

        // ตรวจสอบว่ามีการชำระเงินที่รอตรวจสอบหรืออนุมัติแล้วหรือไม่
        $stmt = $conn->prepare("SELECT * FROM transactions 
                              WHERE payment_id = ? 
                              AND user_id = ? 
                              AND status IN ('pending', 'approved')");
        $stmt->execute([$payment_id, $user_id]);
        if ($stmt->rowCount() > 0) {
            throw new Exception('คุณได้ทำการชำระเงินรายการนี้ไปแล้ว');
        }

        // อัปโหลดไฟล์
        $upload_dir = '../../uploads/slips/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $file_extension = pathinfo($_FILES['slip_image']['name'], PATHINFO_EXTENSION);
        $file_name = uniqid() . '.' . $file_extension;
        $file_path = $upload_dir . $file_name;

        if (!move_uploaded_file($_FILES['slip_image']['tmp_name'], $file_path)) {
            throw new Exception('ไม่สามารถอัปโหลดไฟล์ได้');
        }

        // บันทึกข้อมูลการชำระเงิน
        $stmt = $conn->prepare("
            UPDATE transactions 
            SET status = 'pending',
                slip_image = ?,
                created_at = CURRENT_TIMESTAMP
            WHERE payment_id = ? 
            AND user_id = ?
            AND status = 'not_paid'
        ");
        $stmt->execute([$file_name, $payment_id, $user_id]);

        $conn->commit();
        header('Location: ../../pages/payment/payment.php?success=1');
        exit();

    } catch (Exception $e) {
        $conn->rollBack();
        header('Location: ../../pages/payment/payment.php?error=' . urlencode($e->getMessage()));
        exit();
    }
} else {
    header('Location: ../../pages/payment/payment.php');
    exit();
} 