<?php
session_start();
require_once '../../config/config.php';
require_once '../../includes/auth.php';

header('Content-Type: application/json');

try {
    // ตรวจสอบว่ามีการ login หรือไม่
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('กรุณาเข้าสู่ระบบ');
    }

    $user_id = $_SESSION['user_id'];
    
    // รับค่าจากฟอร์ม
    $fullname = $_POST['fullname'];
    $phone = $_POST['phone'];
    $contact_address = $_POST['contact_address'];
    $non_contact_address = $_POST['non_contact_address'];
    $street = $_POST['street'];

    // ถ้ามีการส่งรหัสผ่านใหม่มา
    $password_sql = '';
    $params = [];
    if (!empty($_POST['password'])) {
        $password_sql = ', password = ?';
        $params[] = $_POST['password'];
    }

    // จัดการอัพโหลดรูปภาพ (ถ้ามี)
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../../uploads/profiles/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $file_extension = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
        $new_filename = $user_id . '_' . time() . '.' . $file_extension;
        $upload_path = $upload_dir . $new_filename;

        if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $upload_path)) {
            $password_sql .= ', profile_image = ?';
            $params[] = $upload_path;
        }
    }

    // อัพเดทข้อมูลในฐานข้อมูล
    $sql = "UPDATE users SET 
            fullname = ?,
            phone = ?,
            contact_address = ?,
            non_contact_address = ?,
            street = ?
            $password_sql
            WHERE user_id = ?";
    
    // เพิ่มพารามิเตอร์พื้นฐาน
    array_unshift($params, $fullname, $phone, $contact_address, $non_contact_address, $street);
    // เพิ่ม user_id เป็นพารามิเตอร์สุดท้าย
    $params[] = $user_id;

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);

    echo json_encode([
        'status' => 'success',
        'message' => 'บันทึกข้อมูลเรียบร้อยแล้ว',
        'reload' => true
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
} 