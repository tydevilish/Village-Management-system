<?php
require_once '../../config/config.php';
require_once '../../includes/auth.php';

header('Content-Type: application/json');

try {
    if (!isset($_GET['id'])) {
        throw new Exception('ไม่พบ ID ที่ต้องการ');
    }

    $roleId = $_GET['id'];
    
    // ดึงข้อมูลสิทธิ์
    $stmt = $conn->prepare("
        SELECT r.role_id, r.role_name
        FROM roles r
        WHERE r.role_id = ?
    ");
    $stmt->execute([$roleId]);
    $role = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$role) {
        throw new Exception('ไม่พบข้อมูลสิทธิ์');
    }

    // ดึงข้อมูลเมนูที่เข้าถึงได้
    $stmt = $conn->prepare("
        SELECT menu_id 
        FROM role_permissions 
        WHERE role_id = ?
    ");
    $stmt->execute([$roleId]);
    $permissions = $stmt->fetchAll(PDO::FETCH_COLUMN);

    echo json_encode([
        'success' => true,
        'role_id' => $role['role_id'],
        'role_name' => $role['role_name'],
        'menu_access' => $permissions
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 