<?php
session_start();
require_once '../../config/config.php';
require_once '../../includes/auth.php';

// ตรวจสอบสิทธิ์
checkPageAccess(PAGE_MANAGE_USERS);

$response = ['status' => 'error', 'message' => 'Invalid request'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'add':
            try {
                $sql = "INSERT INTO users (username, password, fullname, street, phone, role_id, 
                        non_contact_address, contact_address) 
                        VALUES (:username, :password, :fullname, :street, :phone, :role_id, 
                        :non_contact_address, :contact_address)";
                $stmt = $conn->prepare($sql);
                
                $stmt->execute([
                    ':username' => $_POST['username'],
                    ':password' => $_POST['password'],
                    ':fullname' => $_POST['fullname'] ?? null,
                    ':street' => $_POST['street'] ?? null,
                    ':phone' => $_POST['phone'] ?? null,
                    ':role_id' => $_POST['role_id'] ?? null,
                    ':non_contact_address' => $_POST['non_contact_address'] ?? null,
                    ':contact_address' => $_POST['contact_address'] ?? null
                ]);
                
                $response = ['status' => 'success', 'message' => 'เพิ่มผู้ใช้สำเร็จ'];
            } catch (PDOException $e) {
                $response = ['status' => 'error', 'message' => $e->getMessage()];
            }
            break;

        case 'edit':
            try {
                $sql = "UPDATE users SET 
                        username = :username,
                        fullname = :fullname,
                        street = :street,
                        phone = :phone,
                        role_id = :role_id,
                        non_contact_address = :non_contact_address,
                        contact_address = :contact_address";
                
                if (!empty($_POST['password'])) {
                    $sql .= ", password = :password";
                }
                
                $sql .= " WHERE user_id = :user_id";
                
                $stmt = $conn->prepare($sql);
                
                $params = [
                    ':username' => $_POST['username'],
                    ':fullname' => $_POST['fullname'] ?? null,
                    ':street' => $_POST['street'] ?? null,
                    ':phone' => $_POST['phone'] ?? null,
                    ':role_id' => $_POST['role_id'] ?? null,
                    ':non_contact_address' => $_POST['non_contact_address'] ?? null,
                    ':contact_address' => $_POST['contact_address'] ?? null,
                    ':user_id' => $_POST['user_id']
                ];
                
                if (!empty($_POST['password'])) {
                    $params[':password'] = $_POST['password'];
                }
                
                $stmt->execute($params);
                
                $response = ['status' => 'success', 'message' => 'อัพเดทข้อมูลสำเร็จ'];
            } catch (PDOException $e) {
                $response = ['status' => 'error', 'message' => $e->getMessage()];
            }
            break;

        case 'delete':
            try {
                $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
                $stmt->execute([$_POST['user_id']]);
                $response = ['status' => 'success', 'message' => 'ลบผู้ใช้สำเร็จ'];
            } catch (PDOException $e) {
                $response = ['status' => 'error', 'message' => $e->getMessage()];
            }
            break;
    }
}

header('Content-Type: application/json');
echo json_encode($response); 