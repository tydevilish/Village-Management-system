<?php
require_once '../../config/config.php';
require_once '../../includes/auth.php';

header('Content-Type: application/json');

try {
    if (empty($_POST['permissionName'])) {
        throw new Exception('กรุณาระบุชื่อสิทธิ์');
    }

    if (empty($_POST['menus'])) {
        throw new Exception('กรุณาเลือกเมนูอย่างน้อย 1 รายการ');
    }

    // ตรวจสอบว่าชื่อสิทธิ์ซ้ำหรือไม่
    $stmt = $conn->prepare("
        SELECT role_id FROM roles 
        WHERE role_name = ? AND role_id != ?
    ");
    $stmt->execute([$_POST['permissionName'], $_POST['role_id'] ?? 0]);
    if ($stmt->fetch()) {
        throw new Exception('มีชื่อสิทธิ์นี้อยู่ในระบบแล้ว');
    }

    $conn->beginTransaction();

    $roleId = $_POST['role_id'] ?? null;
    $roleName = $_POST['permissionName'];
    $menus = $_POST['menus'] ?? [];

    // เพิ่มหรือแก้ไขข้อมูลสิทธิ์
    if ($roleId) {
        // แก้ไขสิทธิ์
        $stmt = $conn->prepare("UPDATE roles SET role_name = ? WHERE role_id = ?");
        $stmt->execute([$roleName, $roleId]);
    } else {
        // เพิ่มสิทธิ์ใหม่
        $stmt = $conn->prepare("INSERT INTO roles (role_name) VALUES (?)");
        $stmt->execute([$roleName]);
        $roleId = $conn->lastInsertId();
    }

    // ลบข้อมูลการอนุญาตเดิม
    $stmt = $conn->prepare("DELETE FROM role_permissions WHERE role_id = ?");
    $stmt->execute([$roleId]);

    // เพิ่มข้อมูลการอนุญาตใหม่
    if (!empty($menus)) {
        $stmt = $conn->prepare("
            INSERT INTO role_permissions (role_id, menu_id, can_access) 
            VALUES (?, ?, 1)
        ");
        foreach ($menus as $menuId) {
            $stmt->execute([$roleId, $menuId]);
        }
    }

    $conn->commit();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    $conn->rollBack();
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()
    ]);
} 