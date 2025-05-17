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

        if (empty($_POST['reason_id'])) {
            throw new Exception('กรุณาระบุเหตุผลการเข้าพบ');
        }
        
        $visitor_id = $_POST['visitor_id'];
        $visitor_name = $_POST['visitor_name'];
        $car_registration = $_POST['car_registration'];
        $user_id = !empty($_POST['user_id']) ? $_POST['user_id'] : null;
        $reason_id = $_POST['reason_id'];
        
        // Handle custom reason if selected
        if ($_POST['reason_id'] === 'other') {
            if (empty($_POST['other_reason'])) {
                throw new Exception('กรุณาระบุเหตุผลอื่นๆ');
            }
            $stmt = $conn->prepare("INSERT INTO visit_reasons (reason_name) VALUES (?)");
            $stmt->execute([$_POST['other_reason']]);
            $reason_id = $conn->lastInsertId();
        }

        // สร้าง SQL query พื้นฐาน
        $sql = "UPDATE visitors SET 
                visitor_name = ?, 
                car_registration = ?, 
                user_id = ?, 
                reason_id = ?, 
                other_reason = ?";
        
        $params = [
            $visitor_name,
            $car_registration,
            $user_id,
            $reason_id,
            $_POST['reason_id'] === 'other' ? $_POST['other_reason'] : null
        ];

        // จัดการอัพโหลดรูปบัตรประชาชน
        if (!empty($_FILES['id_card_image']['name'])) {
            $upload_dir = '../../uploads/id_cards/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $file = $_FILES['id_card_image'];
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = time() . '_' . uniqid() . '.' . $ext;
            $id_card_filepath = $upload_dir . $filename;

            if (!move_uploaded_file($file['tmp_name'], $id_card_filepath)) {
                throw new Exception('ไม่สามารถอัพโหลดรูปบัตรประชาชนได้');
            }

            // ลบรูปเก่า
            $stmt = $conn->prepare("SELECT id_card_image FROM visitors WHERE visitor_id = ?");
            $stmt->execute([$visitor_id]);
            $old_image = $stmt->fetchColumn();
            if ($old_image && file_exists($old_image)) {
                unlink($old_image);
            }

            $sql .= ", id_card_image = ?";
            $params[] = $id_card_filepath;
        }

        // จัดการอัพโหลดรูปทะเบียนรถ
        if (!empty($_FILES['car_registration_image']['name'])) {
            $upload_dir = '../../uploads/car_registrations/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $file = $_FILES['car_registration_image'];
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = time() . '_' . uniqid() . '.' . $ext;
            $car_registration_filepath = $upload_dir . $filename;

            if (!move_uploaded_file($file['tmp_name'], $car_registration_filepath)) {
                throw new Exception('ไม่สามารถอัพโหลดรูปทะเบียนรถได้');
            }

            // ลบรูปเก่า
            $stmt = $conn->prepare("SELECT car_registration_image FROM visitors WHERE visitor_id = ?");
            $stmt->execute([$visitor_id]);
            $old_image = $stmt->fetchColumn();
            if ($old_image && file_exists($old_image)) {
                unlink($old_image);
            }

            $sql .= ", car_registration_image = ?";
            $params[] = $car_registration_filepath;
        }

        $sql .= " WHERE visitor_id = ?";
        $params[] = $visitor_id;

        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        
        $response['status'] = 'success';
        $response['message'] = 'อัพเดทข้อมูลสำเร็จ';

        // เพิ่มเงื่อนไขตรวจสอบว่าผู้ใช้มีสิทธิ์แก้ไขข้อมูลนี้หรือไม่
        if (in_array($_SESSION['user_id'], [518, 519])) {
            $check_stmt = $conn->prepare("SELECT created_by FROM visitors WHERE visitor_id = ?");
            $check_stmt->execute([$visitor_id]);
            $created_by = $check_stmt->fetchColumn();

            if ($created_by != $_SESSION['user_id']) {
                throw new Exception('คุณไม่มีสิทธิ์แก้ไขข้อมูลนี้');
            }
        }
    } elseif ($action === 'add') {
        // ตรวจสอบเฉพาะฟิลด์ที่จำเป็น
        if (empty($_FILES['id_card_image']['name'])) {
            throw new Exception('กรุณาอัพโหลดรูปบัตรประชาชน');
        }

        if (empty($_FILES['car_registration_image']['name'])) {
            throw new Exception('กรุณาอัพโหลดรูปทะเบียนรถ');
        }

        if (empty($_POST['reason_id'])) {
            throw new Exception('กรุณาระบุเหตุผลการเข้าพบ');
        }

        if ($_POST['reason_id'] === 'other' && empty($_POST['other_reason'])) {
            throw new Exception('กรุณาระบุเหตุผลอื่นๆ');
        }

        // Handle custom reason if selected
        if ($_POST['reason_id'] === 'other') {
            if (empty($_POST['other_reason'])) {
                throw new Exception('กรุณาระบุเหตุผลอื่นๆ');
            }
            // เพิ่มเหตุผลใหม่��นตาราง visit_reasons
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

        // จัดการอัพโหลดรูปทะเบียนรถ
        $upload_dir = '../../uploads/car_registrations/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $file = $_FILES['car_registration_image'];
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = time() . '_' . uniqid() . '.' . $ext;
        $car_registration_filepath = $upload_dir . $filename;

        if (!move_uploaded_file($file['tmp_name'], $car_registration_filepath)) {
            throw new Exception('ไม่สามารถอัพโหลดรูปทะเบียนรถได้');
        }

        // Save to database
        $sql = "INSERT INTO visitors (
            visitor_name,
            car_registration,
            id_card_image,
            car_registration_image,
            user_id,
            reason_id,
            other_reason,
            created_by
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $conn->prepare($sql);
        $stmt->execute([
            $_POST['visitor_name'] ?? null,
            $_POST['car_registration'] ?? null,
            $filepath ?? null,
            $car_registration_filepath ?? null,
            $user_id,
            $reason_id,
            $_POST['reason_id'] === 'other' ? $_POST['other_reason'] : null,
            $_SESSION['user_id']
        ]);
        
        $response['status'] = 'success';
        $response['message'] = 'บันทึกข้อมูลสำเร็จ';
    } elseif ($action === 'exit') {
        // ตรวจสอบ visitor_id
        if (empty($_POST['visitor_id'])) {
            throw new Exception('ไม่พบข้อมูลผู้มาเยือน');
        }

        $visitor_id = $_POST['visitor_id'];

        // เพิ่มเงื่อนไขตรวจสอบว่าผู้ใช้มีสิทธิ์แก้ไขข้อมูลนี้หรือไม่
        if (in_array($_SESSION['user_id'], [518, 519])) {
            $check_stmt = $conn->prepare("SELECT created_by FROM visitors WHERE visitor_id = ?");
            $check_stmt->execute([$visitor_id]);
            $created_by = $check_stmt->fetchColumn();

            if ($created_by != $_SESSION['user_id']) {
                throw new Exception('คุณไม่มีสิทธิ์แก้ไขข้อมูลนี้');
            }
        }

        // บันทึกเวลาออก
        $stmt = $conn->prepare("UPDATE visitors SET exit_at = CURRENT_TIMESTAMP WHERE visitor_id = ?");
        $stmt->execute([$visitor_id]);
        
        $response['status'] = 'success';
        $response['message'] = 'บันทึกเวลาออกสำเร็จ';
    }
    
} catch (Exception $e) {
    $response['message'] = '' . $e->getMessage();
}

header('Content-Type: application/json');
echo json_encode($response); 