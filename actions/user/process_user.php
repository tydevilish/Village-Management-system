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
                        non_contact_address, contact_address, director) 
                        VALUES (:username, :password, :fullname, :street, :phone, :role_id, 
                        :non_contact_address, :contact_address, :director)";
                $stmt = $conn->prepare($sql);

                $stmt->execute([
                    ':username' => $_POST['username'],
                    ':password' => $_POST['password'],
                    ':fullname' => $_POST['fullname'] ?? null,
                    ':street' => $_POST['street'] ?? null,
                    ':phone' => $_POST['phone'] ?? null,
                    ':role_id' => $_POST['role_id'] ?? null,
                    ':non_contact_address' => $_POST['non_contact_address'] ?? null,
                    ':contact_address' => $_POST['contact_address'] ?? null,
                    ':director' => isset($_POST['director']) ? 1 : 0
                ]);

                // จัดการ tags
                if (!empty($_POST['tags'])) {
                    $tag_stmt = $conn->prepare("INSERT INTO user_tag_relations (user_id, tag_id) VALUES (?, ?)");
                    foreach ($_POST['tags'] as $tag_id) {
                        $tag_stmt->execute([$user_id, $tag_id]);
                    }
                }

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
                        contact_address = :contact_address,
                        director = :director";

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
                    ':director' => isset($_POST['director']) ? 1 : 0,
                    ':user_id' => $_POST['user_id']
                ];

                if (!empty($_POST['password'])) {
                    $params[':password'] = $_POST['password'];
                }

                $stmt->execute($params);

                // อัพเดท tags
                $delete_tags = $conn->prepare("DELETE FROM user_tag_relations WHERE user_id = ?");
                $delete_tags->execute([$_POST['user_id']]);

                if (!empty($_POST['tags'])) {
                    $tag_stmt = $conn->prepare("INSERT INTO user_tag_relations (user_id, tag_id) VALUES (?, ?)");
                    foreach ($_POST['tags'] as $tag_id) {
                        $tag_stmt->execute([$_POST['user_id'], $tag_id]);
                    }
                }

                $response = ['status' => 'success', 'message' => 'อัพเดทข้อมูลสำเร็จ'];
            } catch (PDOException $e) {
                $response = ['status' => 'error', 'message' => $e->getMessage()];
            }
            break;

        case 'delete':
            try {
                // เริ่ม transaction
                $conn->beginTransaction();

                // 1. ลบข้อมูลการชำระเงินที่เกี่ยวข้อง
                $stmt = $conn->prepare("DELETE FROM transactions WHERE user_id = ?");
                $stmt->execute([$_POST['user_id']]);

                // 2. ลบข้อมูลการผูกค่าส่วนกลางกับผู้ใช้
                $stmt = $conn->prepare("DELETE FROM payment_users WHERE user_id = ?");
                $stmt->execute([$_POST['user_id']]);

                // 3. ตรวจสอบและลบรายการค่าส่วนกลางที่ไม่มีผู้ใช้เหลือ
                $stmt = $conn->prepare("
                    DELETE FROM payments 
                    WHERE payment_id NOT IN (
                        SELECT DISTINCT payment_id 
                        FROM payment_users
                    )
                ");
                $stmt->execute();

                // 4. ลบข้อมูลผู้ใช้
                $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
                $stmt->execute([$_POST['user_id']]);

                // ยืนยันการทำรายการทั้งหมด
                $conn->commit();

                $response = ['status' => 'success', 'message' => 'ลบผู้ใช้และข้อมูลที่เกี่ยวข้องสำเร็จ'];
            } catch (PDOException $e) {
                // ถ้าเกิดข้อผิดพลาด ให้ย้อนกลับทุกรายการ
                $conn->rollBack();
                $response = ['status' => 'error', 'message' => 'ไม่สามารถลบข้อมูลได้: ' . $e->getMessage()];
            }
            break;
    }
}

header('Content-Type: application/json');
echo json_encode($response);
