<?php
session_start();
require_once '../../config/config.php';
require_once '../../includes/auth.php';

try {
    // ตรวจสอบการเข้าสู่ระบบ
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('กรุณาเข้าสู่ระบบ');
    }

    // ตรวจสอบข้อมูลที่จำเป็น
    if (empty($_POST['title']) || empty($_POST['description'])) {
        throw new Exception('กรุณากรอกข้อมูลให้ครบถ้วน');
    }

    $conn->beginTransaction();

    // บันทึกข้อมูลข้อเสนอแนะ
    $stmt = $conn->prepare("
        INSERT INTO feedback (user_id, title, description, status) 
        VALUES (?, ?, ?, 'pending')
    ");
    $stmt->execute([
        $_SESSION['user_id'],
        $_POST['title'],
        $_POST['description']
    ]);
    
    $feedbackId = $conn->lastInsertId();

    // จัดการรูปภาพ
    if (!empty($_POST['images'])) {
        $images = json_decode($_POST['images'], true);
        
        if (!is_dir('../../uploads/feedback')) {
            mkdir('../../uploads/feedback', 0777, true);
        }

        $stmt = $conn->prepare("
            INSERT INTO feedback_images (feedback_id, image_path) 
            VALUES (?, ?)
        ");

        foreach ($images as $imageData) {
            // แปลง base64 เป็นไฟล์
            $imageData = str_replace('data:image/png;base64,', '', $imageData);
            $imageData = str_replace('data:image/jpeg;base64,', '', $imageData);
            $imageData = str_replace(' ', '+', $imageData);
            $imageBinary = base64_decode($imageData);

            // สร้างชื่อไฟล์
            $filename = uniqid() . '.jpg';
            $filepath = '../../uploads/feedback/' . $filename;

            // บันทึกไฟล์
            file_put_contents($filepath, $imageBinary);

            // บันทึกข้อมูลในฐานข้อมูล
            $stmt->execute([
                $feedbackId,
                '../../uploads/feedback/' . $filename
            ]);
        }
    }

    $conn->commit();

    echo json_encode([
        'status' => 'success',
        'message' => 'ส่งข้อเสนอแนะเรียบร้อยแล้ว'
    ]);

} catch (Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}