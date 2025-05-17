<?php
session_start();
require_once '../../config/config.php';
require_once '../../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $response = ['status' => 'error', 'message' => ''];
    
    $action = $_POST['action'] ?? '';
    
    try {
        if ($action === 'add' || $action === 'edit') {
            $title = trim($_POST['title'] ?? '');
            $content = trim($_POST['content'] ?? '');
            $category_id = $_POST['category_id'] ?? 0;
            $status = $_POST['status'] ?? 'active';
            
            // ตรวจสอบข้อมูลเฉพาะกรณีเพิ่มและแก้ไข
            if (empty($title) || empty($content) || empty($category_id)) {
                $response['message'] = 'กรุณากรอกข้อมูลให้ครบถ้วน';
                echo json_encode($response);
                exit;
            }

            // จัดการอัพโหลดรูปภาพ
            $image_path = null;
            if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
                $upload_dir = '../../uploads/news/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                $new_filename = uniqid() . '.' . $file_extension;
                $upload_path = $upload_dir . $new_filename;
                
                if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                    $image_path = '../../uploads/news/' . $new_filename;
                }
            }
            
            if ($action === 'add') {
                $stmt = $conn->prepare("
                    INSERT INTO news (title, content, category_id, status, image_path, created_at) 
                    VALUES (?, ?, ?, ?, ?, NOW())
                ");
                $stmt->execute([$title, $content, $category_id, $status, $image_path]);
                $response = ['status' => 'success', 'message' => 'เพิ่มข่าวสารเรียบร้อยแล้ว'];
            } else {
                $news_id = $_POST['news_id'] ?? 0;
                
                if ($image_path) {
                    // ลบรูปเก่าถ้ามีการอัพโหลดรูปใหม่
                    $stmt = $conn->prepare("SELECT image_path FROM news WHERE news_id = ?");
                    $stmt->execute([$news_id]);
                    $old_image = $stmt->fetchColumn();
                    if ($old_image && file_exists('../../' . $old_image)) {
                        unlink('../../' . $old_image);
                    }
                    
                    $stmt = $conn->prepare("
                        UPDATE news 
                        SET title = ?, content = ?, category_id = ?, status = ?, image_path = ?
                        WHERE news_id = ?
                    ");
                    $stmt->execute([$title, $content, $category_id, $status, $image_path, $news_id]);
                } else {
                    $stmt = $conn->prepare("
                        UPDATE news 
                        SET title = ?, content = ?, category_id = ?, status = ?
                        WHERE news_id = ?
                    ");
                    $stmt->execute([$title, $content, $category_id, $status, $news_id]);
                }
                $response = ['status' => 'success', 'message' => 'แก้ไขข่าวสารเรียบร้อยแล้ว'];
            }
        } elseif ($action === 'delete') {
            $news_id = $_POST['news_id'] ?? 0;
            
            // ลบรูปภาพ (ถ้ามี)
            $stmt = $conn->prepare("SELECT image_path FROM news WHERE news_id = ?");
            $stmt->execute([$news_id]);
            $image_path = $stmt->fetchColumn();
            if ($image_path && file_exists($image_path)) {
                unlink($image_path);
            }
            
            // ลบข้อมูลข่าว
            $stmt = $conn->prepare("DELETE FROM news WHERE news_id = ?");
            $stmt->execute([$news_id]);
            $response = ['status' => 'success', 'message' => 'ลบข่าวสารเรียบร้อยแล้ว'];
        }
    } catch (PDOException $e) {
        $response['message'] = 'เกิดข้อผิดพลาด: ' . $e->getMessage();
    }
    
    echo json_encode($response);
    exit;
}
