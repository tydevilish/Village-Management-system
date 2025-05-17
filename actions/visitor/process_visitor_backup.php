<?php
session_start();
require_once '../../config/config.php';
require_once '../../includes/auth.php';

// ตรวจสอบว่ามีการ login และมีสิทธิ์
checkPageAccess(PAGE_MANAGE_VISITORS);

try {
    $response = ['status' => 'error', 'message' => ''];
    
    $action = $_POST['formAction'];
    
    if ($action === 'edit') {
        if (empty($_POST['visitor_id'])) {
            throw new Exception('ไม่พบข้อมูลผู้มาเยือน');
        }
        
        $visitor_id = $_POST['visitor_id'];
        $visitor_name = $_POST['visitor_name'];
        $id_card = $_POST['id_card'];
        $user_id = $_POST['user_id'];
        $reason_id = $_POST['reason_id'];
        $other_reason = $reason_id === 'other' ? $_POST['other_reason'] : null;
        
        // Validate only reason_id
        if (empty($_POST['reason_id'])) {
            throw new Exception('กรุณาระบุเหตุผลการเข้าพบ');
        }

        // Handle custom reason if selected
        if ($_POST['reason_id'] === 'other') {
            if (empty($_POST['other_reason'])) {
                throw new Exception('กรุณาระบุเหตุผลอื่นๆ');
            }
            // เพิ่มเหตุผลใหม่ในตาราง visit_reasons
            $stmt = $conn->prepare("INSERT INTO visit_reasons (reason_name) VALUES (?)");
            $stmt->execute([$_POST['other_reason']]);
            $reason_id = $conn->lastInsertId();
        } else {
            $reason_id = $_POST['reason_id'];
        }

        // ตรวจสอบ user_id ถ้าไม่ได้เลือกให้เป็น null
        $user_id = !empty($_POST['user_id']) ? $_POST['user_id'] : null;

        // Handle file upload if provided
        $filepath = null;
        if (!empty($_FILES['id_card_image']['name'])) {
            $upload_dir = '../../uploads/id_cards/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $file = $_FILES['id_card_image'];
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = time() . '_' . uniqid() . '.' . $ext;
            $filepath = $upload_dir . $filename;

            if (!move_uploaded_file($file['tmp_name'], $filepath)) {
                throw new Exception('ไม่สามารถอัพโหลดไฟล์ได้');
            }

            // ลบรูปเก่า
            $stmt = $conn->prepare("SELECT id_card_image FROM visitors WHERE visitor_id = ?");
            $stmt->execute([$_POST['visitor_id']]);
            $old_image = $stmt->fetchColumn();
            if ($old_image && file_exists($old_image)) {
                unlink($old_image);
            }
        }

        // ตรวจสอบว่ามีการอัพโหลดรูปใหม่หรือไม่
        if (empty($_FILES['id_card_image']['name'])) {
            // ตรวจสอบว่ามีรูปเดิมหรือไม่
            $stmt = $conn->prepare("SELECT id_card_image FROM visitors WHERE visitor_id = ?");
            $stmt->execute([$_POST['visitor_id']]);
            $existing_image = $stmt->fetchColumn();
            
            if (!$existing_image) {
                throw new Exception('กรุณาอัพโหลดรูปบัตรประชาชน');
            }
        }

        // สร้าง SQL query ตามเงื่อนไขว่ามีการอัพโหลดรูปใหม่หรือไม่
        $sql = "UPDATE visitors SET 
                visitor_name = ?, 
                id_card = ?, 
                user_id = ?, 
                reason_id = ?, 
                other_reason = ?";
        
        $params = [
            $_POST['visitor_name'] ?? null,
            $_POST['id_card'] ?? null,
            $user_id,
            $reason_id,
            $_POST['reason_id'] === 'other' ? $_POST['other_reason'] : null
        ];

        if ($filepath) {
            $sql .= ", id_card_image = ?";
            $params[] = $filepath;
        }

        $sql .= " WHERE visitor_id = ?";
        $params[] = $_POST['visitor_id'];

        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        
        $response['status'] = 'success';
        $response['message'] = 'อัพเดทข้อมูลสำเร็จ';
    } elseif ($action === 'add') {
        // Validate required fields
        if (empty($_POST['reason_id'])) {
            throw new Exception('กรุณาระบุเหตุผลการเข้าพบ');
        }

        // ตรวจสอบการอัพโหลดรูปภาพ
        if (empty($_FILES['id_card_image']['name'])) {
            throw new Exception('กรุณาอัพโหลดรูปบัตรประชาชน');
        }

        // Handle custom reason if selected
        if ($_POST['reason_id'] === 'other') {
            if (empty($_POST['other_reason'])) {
                throw new Exception('กรุณาระบุเหตุผลอื่นๆ');
            }
            // เพิ่มเหตุผลใหม่ในตาราง visit_reasons
            $stmt = $conn->prepare("INSERT INTO visit_reasons (reason_name) VALUES (?)");
            $stmt->execute([$_POST['other_reason']]);
            $reason_id = $conn->lastInsertId();
        } else {
            $reason_id = $_POST['reason_id'];
        }

        // ตรวจสอบ user_id ถ้าไม่ได้เลือกให้เป็น null
        $user_id = !empty($_POST['user_id']) ? $_POST['user_id'] : null;

        // Handle file upload
        $upload_dir = '../../uploads/id_cards/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $file = $_FILES['id_card_image'];
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = time() . '_' . uniqid() . '.' . $ext;
        $filepath = $upload_dir . $filename;

        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
            throw new Exception('ไม่สามารถอัพโหลดรูปภาพได้ กรุณาลองใหม่อีกครั้ง');
        }

        // Save to database
        $stmt = $conn->prepare("
            INSERT INTO visitors (
                visitor_name, id_card, id_card_image, reason_id, 
                other_reason, user_id, created_by
            ) VALUES (?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $_POST['visitor_name'] ?? null,
            $_POST['id_card'] ?? null,
            $filepath ?? null,
            $reason_id,
            $_POST['reason_id'] === 'other' ? $_POST['other_reason'] : null,
            $user_id,
            $_SESSION['user_id']
        ]);
        
        $response['status'] = 'success';
        $response['message'] = 'บันทึกข้อมูลสำเร็จ';
    }
    
} catch (Exception $e) {
    $response['message'] = '' . $e->getMessage();
}

header('Content-Type: application/json');
echo json_encode($response); 