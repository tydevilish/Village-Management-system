<?php
session_start();
require_once '../../config/config.php';
require_once '../../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // ตรวจสอบไฟล์
        if (!isset($_FILES['slip_image']) || $_FILES['slip_image']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('กรุณาอัปโหลดหลักฐานการโอนเงิน');
        }

        $transaction_id = $_POST['transaction_id'];

        // ตรวจสอบว่ามี transaction อยู่จริง
        $stmt = $conn->prepare("SELECT payment_id, status FROM transactions WHERE transaction_id = ?");
        $stmt->execute([$transaction_id]);
        $transaction = $stmt->fetch();

        if (!$transaction) {
            throw new Exception('ไม่พบข้อมูลการทำรายการ');
        }

        // อัปโหลดไฟล์
        $upload_dir = '../../uploads/slips/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $file_extension = strtolower(pathinfo($_FILES['slip_image']['name'], PATHINFO_EXTENSION));
        if (!in_array($file_extension, ['jpg', 'jpeg', 'png'])) {
            throw new Exception('รองรับเฉพาะไฟล์ภาพ (jpg, jpeg, png)');
        }

        $file_name = uniqid() . '.' . $file_extension;
        $file_path = $upload_dir . $file_name;

        if (!move_uploaded_file($_FILES['slip_image']['tmp_name'], $file_path)) {
            throw new Exception('ไม่สามารถอัปโหลดไฟล์ได้');
        }

        // อัพเดทเฉพาะ slip_image และ payment_date
        $stmt = $conn->prepare("
            UPDATE transactions 
            SET slip_image = ?,
                payment_date = CURRENT_TIMESTAMP
            WHERE transaction_id = ?
        ");

        if (!$stmt->execute([$file_name, $transaction_id])) {
            // ถ้าอัพเดทไม่สำเร็จ ให้ลบไฟล์ที่อัพโหลด
            unlink($file_path);
            throw new Exception('ไม่สามารถบันทึกข้อมูลได้');
        }

        echo json_encode([
            'success' => true,
            'payment_id' => $transaction['payment_id'],
            'message' => 'อัพโหลดสลิปสำเร็จ'
        ]);

    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
}