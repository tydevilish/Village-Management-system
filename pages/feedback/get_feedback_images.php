<?php
session_start();
require_once '../../config/config.php';
require_once '../../includes/auth.php';

try {
    $feedbackId = $_GET['feedback_id'] ?? null;
    
    if (!$feedbackId) {
        throw new Exception('ไม่พบ ID ข้อเสนอแนะ');
    }

    // ตรวจสอบสิทธิ์การเข้าถึง
    $stmt = $conn->prepare("
        SELECT user_id 
        FROM feedback 
        WHERE id = ?
    ");
    $stmt->execute([$feedbackId]);
    $feedback = $stmt->fetch();

    if (!$feedback || $feedback['user_id'] !== $_SESSION['user_id']) {
        throw new Exception('คุณไม่มีสิทธิ์ดูรูปภาพนี้');
    }

    // ดึงข้อมูลรูปภาพ
    $stmt = $conn->prepare("
        SELECT image_path 
        FROM feedback_images 
        WHERE feedback_id = ?
    ");
    $stmt->execute([$feedbackId]);
    $images = $stmt->fetchAll();

    echo json_encode($images);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'error' => $e->getMessage()
    ]);
}
