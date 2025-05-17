<?php
session_start();
require_once '../../config/config.php';
require_once '../../includes/auth.php';

// ตรวจสอบว่ามีการส่งข้อมูลแบบ POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $response = ['status' => 'error', 'message' => ''];
    
    // ดึงข้อมูลจากฟอร์ม
    $action = $_POST['action'] ?? '';
    
    try {
        if ($action === 'add' || $action === 'edit') {
            $category_name = trim($_POST['category_name'] ?? '');
            
            // ตรวจสอบข้อมูลเฉพาะกรณีเพิ่มและแก้ไข
            if (empty($category_name)) {
                $response['message'] = 'กรุณากรอกชื่อประเภท';
                echo json_encode($response);
                exit;
            }
            
            if ($action === 'add') {
                $stmt = $conn->prepare("INSERT INTO news_categories (category_name) VALUES (?)");
                $stmt->execute([$category_name]);
                $response = ['status' => 'success', 'message' => 'เพิ่มประเภทข่าวสารเรียบร้อยแล้ว'];
            } else {
                $category_id = $_POST['category_id'] ?? 0;
                $stmt = $conn->prepare("UPDATE news_categories SET category_name = ? WHERE category_id = ?");
                $stmt->execute([$category_name, $category_id]);
                $response = ['status' => 'success', 'message' => 'แก้ไขประเภทข่าวสารเรียบร้อยแล้ว'];
            }
        } elseif ($action === 'delete') {
            $category_id = $_POST['category_id'] ?? 0;
            $stmt = $conn->prepare("DELETE FROM news_categories WHERE category_id = ?");
            $stmt->execute([$category_id]);
            $response = ['status' => 'success', 'message' => 'ลบประเภทข่าวสารเรียบร้อยแล้ว'];
        }
    } catch (PDOException $e) {
        $response['message'] = 'เกิดข้อผิดพลาด: ' . $e->getMessage();
    }
    
    echo json_encode($response);
    exit;
}
