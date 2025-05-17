<?php
session_start();
require_once '../../config/config.php';
require_once '../../includes/auth.php';

// ตรวจสอบสิทธิ์
checkPageAccess(PAGE_MANAGE_USERS);

// รับข้อมูล JSON
$data = json_decode(file_get_contents('php://input'), true);
$response = ['status' => 'error', 'message' => 'Invalid request'];

try {
    if ($data['action'] === 'add') {
        $stmt = $conn->prepare("INSERT INTO user_tags (name, color) VALUES (?, ?)");
        $stmt->execute([$data['name'], $data['color']]);
        $response = ['status' => 'success', 'message' => 'เพิ่มแท็กเรียบร้อย'];
    }
    elseif ($data['action'] === 'delete') {
        // ลบความสัมพันธ์ในตาราง user_tag_relations ก่อน
        $stmt = $conn->prepare("DELETE FROM user_tag_relations WHERE tag_id = ?");
        $stmt->execute([$data['tag_id']]);
        
        // จากนั้นลบแท็ก
        $stmt = $conn->prepare("DELETE FROM user_tags WHERE tag_id = ?");
        $stmt->execute([$data['tag_id']]);
        $response = ['status' => 'success', 'message' => 'ลบแท็กเรียบร้อย'];
    }
} catch (PDOException $e) {
    $response = ['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()];
}

header('Content-Type: application/json');
echo json_encode($response); 