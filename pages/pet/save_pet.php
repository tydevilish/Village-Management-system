<?php
session_start();
require_once '../../config/config.php';
require_once '../../includes/auth.php';


try {
    $conn->beginTransaction();
 
    // ตรวจสอบข้อมูลที่ส่งมา
    if (empty($_POST['pet_name'])) {
        throw new Exception('กรุณากรอกชื่อสัตว์เลี้ยง');
    }

    if (empty($_POST['type_id']) || $_POST['type_id'] === '') {
        throw new Exception('กรุณาเลือกประเภทสัตว์เลี้ยง');
    }
    
    if (empty($_POST['species_id']) || $_POST['species_id'] === '') {
        throw new Exception('กรุณาเลือกสายพันธุ์');
    }
 
    $pet_id = !empty($_POST['pet_id']) ? $_POST['pet_id'] : null;
    $type_id = $_POST['type_id'];
    $species_id = $_POST['species_id'];
    $description = $_POST['description'];
    $gender = $_POST['gender'];
    $birthdate = $_POST['birthdate'];

 
    // จัดการประเภทอื่นๆ
    if ($type_id === 'other') {
        if (empty($_POST['other_type'])) {
            throw new Exception('กรุณาระบุประเภทอื่นๆ');
        }
 
        // เพิ่มประเภทใหม่
        $stmt = $conn->prepare("INSERT INTO pet_types (name) VALUES (:name)");
        $stmt->execute([':name' => $_POST['other_type']]);
        $type_id = $conn->lastInsertId();
    }
 
    // จัดกาสายพันธุ์อื่นๆ
    if ($species_id === 'other') {
        if (empty($_POST['other_species'])) {
            throw new Exception('กรุณาระบุสายพันธุ์อื่นๆ');
        }
 
        // เพิ่มสายพันธุ์ใหม่
        $stmt = $conn->prepare("INSERT INTO species (name, type_id) VALUES (:name, :type_id)");
        $stmt->execute([
            ':name' => $_POST['other_species'],
            ':type_id' => $type_id
        ]);
        $species_id = $conn->lastInsertId();
    }
 
    // จัดการรูปภาพสัตว์เลี้ยง
    $photo_path = null;
    if (!empty($_FILES['pet_photo']['name'])) {
        $upload_dir = '../../uploads/pets/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
 
        $file_ext = strtolower(pathinfo($_FILES['pet_photo']['name'], PATHINFO_EXTENSION));
        $file_name = uniqid() . '.' . $file_ext;
        $target_file = $upload_dir . $file_name;
 
        if (move_uploaded_file($_FILES['pet_photo']['tmp_name'], $target_file)) {
            $photo_path = '../../uploads/pets/' . $file_name;
        }
    }
 
    // บันทึกหรืออัพเดทข้อมูลสัตว์เลี้ยง
    if ($pet_id) {
        $sql = "UPDATE pets SET 
                pet_name = :pet_name, 
                type_id = :type_id, 
                species_id = :species_id,
                description = :description,
                gender = :gender,
                birthdate = :birthdate";
 
        if ($photo_path) {
            $sql .= ", photo = :photo";
        }
 
        $sql .= " WHERE id = :id AND user_id = :user_id";
 
        $stmt = $conn->prepare($sql);
        $params = [
            ':id' => $pet_id,
            ':pet_name' => $_POST['pet_name'],
            ':type_id' => $type_id,
            ':species_id' => $species_id,
            ':description' => $description,
            ':gender' => $gender,
            ':birthdate' => $birthdate,
            ':user_id' => $_SESSION['user_id']
        ];
 
        if ($photo_path) {
            $params[':photo'] = $photo_path;
        }
    } else {
        $stmt = $conn->prepare("
            INSERT INTO pets (
                pet_name, type_id, species_id, 
                description, gender, birthdate, 
                photo, status, user_id
            ) VALUES (
                :pet_name, :type_id, :species_id, 
                :description, :gender, :birthdate, 
                :photo, :status, :user_id
            )
        ");
 
        $params = [
            ':pet_name' => $_POST['pet_name'],
            ':type_id' => $type_id,
            ':species_id' => $species_id,
            ':description' => $description,
            ':gender' => $gender,
            ':birthdate' => $birthdate,
            ':photo' => $photo_path,
            ':status' => 'pending',
            ':user_id' => $_SESSION['user_id']
        ];
    }
 
    $stmt->execute($params);
 
    // ถ้าเป็นการเพิ่มใหม่ ให้เก็บ pet_id
    if (!$pet_id) {
        $pet_id = $conn->lastInsertId();
    }
 
    // บันทึกข้อมูลวัคซีน
    if ($pet_id) {
        // ดึงข้อมูลวัคซีนเก่า
        $stmt = $conn->prepare("SELECT vaccine_id, photo FROM pet_vaccines WHERE pet_id = :pet_id");
        $stmt->execute([':pet_id' => $pet_id]);
        $oldVaccines = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $selectedVaccines = !empty($_POST['vaccines']) ? $_POST['vaccines'] : [];
        foreach ($oldVaccines as $oldVaccine) {
            if (!in_array($oldVaccine['vaccine_id'], $selectedVaccines)) {
                // ลบข้อมูลวัคซีน
                $stmt = $conn->prepare("DELETE FROM pet_vaccines WHERE pet_id = :pet_id AND vaccine_id = :vaccine_id");
                $stmt->execute([
                    ':pet_id' => $pet_id,
                    ':vaccine_id' => $oldVaccine['vaccine_id']
                ]);
                
                // ลบไฟล์รูปภาพถ้ามี
                if ($oldVaccine['photo']) {
                    $photo_path = str_replace('../../', '', $oldVaccine['photo']);
                    if (file_exists($photo_path)) {
                        unlink($photo_path);
                    }
                }
            }
        }
    }

    // บันทึกหรืออัพเดทข้อมูลวัคซีน
    if (!empty($_POST['vaccines'])) {
        foreach ($_POST['vaccines'] as $vaccine_id) {
            // เช็คว่ามีข้อมูลวัคซีนนี้อยู่แล้วหรือไม่
            $stmt = $conn->prepare("SELECT id FROM pet_vaccines WHERE pet_id = :pet_id AND vaccine_id = :vaccine_id");
            $stmt->execute([
                ':pet_id' => $pet_id,
                ':vaccine_id' => $vaccine_id
            ]);
            
            if (!$stmt->fetch()) {
                // ถ้ายังไม่มี ให้เพิ่มใหม่
                $stmt = $conn->prepare("
                    INSERT INTO pet_vaccines (pet_id, vaccine_id)
                    VALUES (:pet_id, :vaccine_id)
                ");
                $stmt->execute([
                    ':pet_id' => $pet_id,
                    ':vaccine_id' => $vaccine_id
                ]);
            }

            // จัดการรูปภาพวัคซีน
            if (!empty($_FILES["vaccine_photo_{$vaccine_id}"]['name'])) {
                $upload_dir = '../../uploads/vaccines/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }

                $file_ext = strtolower(pathinfo($_FILES["vaccine_photo_{$vaccine_id}"]['name'], PATHINFO_EXTENSION));
                $file_name = uniqid() . '.' . $file_ext;
                $target_file = $upload_dir . $file_name;

                if (move_uploaded_file($_FILES["vaccine_photo_{$vaccine_id}"]['tmp_name'], $target_file)) {
                    // ลบรูปเก่าถ้ามี
                    $stmt = $conn->prepare("SELECT photo FROM pet_vaccines WHERE pet_id = :pet_id AND vaccine_id = :vaccine_id");
                    $stmt->execute([
                        ':pet_id' => $pet_id,
                        ':vaccine_id' => $vaccine_id
                    ]);
                    $old_photo = $stmt->fetchColumn();
                    
                    if ($old_photo) {
                        $old_photo_path = str_replace('../../', '', $old_photo);
                        if (file_exists($old_photo_path)) {
                            unlink($old_photo_path);
                        }
                    }

                    // อัพเดทรูปใหม่
                    $stmt = $conn->prepare("
                        UPDATE pet_vaccines 
                        SET photo = :photo
                        WHERE pet_id = :pet_id AND vaccine_id = :vaccine_id
                    ");
                    $stmt->execute([
                        ':photo' => '../../uploads/vaccines/' . $file_name,
                        ':pet_id' => $pet_id,
                        ':vaccine_id' => $vaccine_id
                    ]);
                }
            }
        }
    }

    function uploadVaccinePhoto($file) {
        // กำหนดโฟลเดอร์สำหรับเก็บรูปภาพวัคซีน
        $target_dir = "../../uploads/vaccines/";
        
        // สร้างโฟลเดอร์ถ้ายังไม่มี
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        // ตรวจสอบประเภทไฟล์
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($file_extension, $allowed_types)) {
            throw new Exception('อนุญาตเฉพาะไฟล์รูปภาพ (JPG, JPEG, PNG, GIF)');
        }

        // ตรวจสอบขนาดไฟล์ (ไม่เกิน 5MB)
        if ($file['size'] > 5 * 1024 * 1024) {
            throw new Exception('ขนาดไฟล์ต้องไม่เกิน 5MB');
        }

        // สร้างชื่อไฟล์ใหม่
        $new_filename = uniqid() . '.' . $file_extension;
        $target_file = $target_dir . $new_filename;

        // อัพโหลดไฟล์
        if (move_uploaded_file($file['tmp_name'], $target_file)) {
            // ส่งคืนเฉพาะชื่อไฟล์และโฟลเดอร์ (ไม่รวม ../../)
            return '../../uploads/vaccines/' . $new_filename;
        } else {
            throw new Exception('เกิดข้อผิดพลาดในการอัพโหลดไฟล์');
        }
    }

    // บันทึกวัคซีนที่ผู้ใช้สร้างใหม่
    if (isset($_POST['custom_vaccine_names']) && is_array($_POST['custom_vaccine_names'])) {
        $stmtCustomVaccine = $conn->prepare("
            INSERT INTO vaccines (name, description, for_type_id, created_by)
            VALUES (:name, :description, :for_type_id, :created_by)
        ");

        $stmtPetVaccine = $conn->prepare("
            INSERT INTO pet_vaccines (pet_id, vaccine_id, photo, vaccination_date)
            VALUES (:pet_id, :vaccine_id, :photo, CURRENT_DATE)
        ");

        foreach ($_POST['custom_vaccine_names'] as $index => $name) {
            try {
                // บันทึกวัคซีนใหม่
                $stmtCustomVaccine->execute([
                    'name' => $name,
                    'description' => $_POST['custom_vaccine_descriptions'][$index] ?? null,
                    'for_type_id' => $_POST['type_id'],
                    'created_by' => $_SESSION['user_id']
                ]);
                $vaccine_id = $conn->lastInsertId();

                // จัดการรูปภาพ
                $photo = null;
                $file_key = "vaccine_photo_custom_{$index}";
                
                error_log("Processing file key: " . $file_key);
                error_log("FILES data: " . print_r($_FILES, true));

                if (isset($_FILES[$file_key]) && $_FILES[$file_key]['error'] === 0) {
                    try {
                        $photo = uploadVaccinePhoto($_FILES[$file_key]);
                        error_log("Successfully uploaded photo: " . $photo);
                    } catch (Exception $e) {
                        error_log("Upload error: " . $e->getMessage());
                    }
                }

                // บันทึกประวัติการฉีด
                $stmtPetVaccine->execute([
                    'pet_id' => $pet_id,
                    'vaccine_id' => $vaccine_id,
                    'photo' => $photo
                ]);

            } catch (Exception $e) {
                error_log("Error in vaccine save process: " . $e->getMessage());
                continue;
            }
        }
    }

    

 
    $conn->commit();
    echo json_encode(['status' => 'success']);
 
} catch (Exception $e) {
    $conn->rollBack();
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?> 