<?php
session_start();
require_once '../../config/config.php';
require_once '../../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
    $news_id = $_GET['id'];
    
    try {
        $stmt = $conn->prepare("
            SELECT n.*, nc.category_name 
            FROM news n
            LEFT JOIN news_categories nc ON n.category_id = nc.category_id
            WHERE n.news_id = ?
        ");
        $stmt->execute([$news_id]);
        $news = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($news) {
            echo json_encode($news);
        } else {
            echo json_encode(['error' => 'ไม่พบข้อมูลข่าวสาร']);
        }
    } catch (PDOException $e) {
        echo json_encode(['error' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['error' => 'คำขอไม่ถูกต้อง']);
}
