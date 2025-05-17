<?php
session_start();
require_once '../../config/config.php';
require_once '../../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
    $category_id = $_GET['id'];
    
    try {
        $stmt = $conn->prepare("SELECT * FROM news_categories WHERE category_id = ?");
        $stmt->execute([$category_id]);
        $category = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($category) {
            echo json_encode($category);
        } else {
            echo json_encode(['error' => 'ไม่พบข้อมูลประเภทข่าวสาร']);
        }
    } catch (PDOException $e) {
        echo json_encode(['error' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['error' => 'คำขอไม่ถูกต้อง']);
}
