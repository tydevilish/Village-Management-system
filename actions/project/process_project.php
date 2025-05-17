<?php
session_start();
require_once '../../config/config.php';
require_once '../../includes/auth.php';

checkPageAccess(PAGE_MANAGE_PROJECT);

header('Content-Type: application/json');

try {
    // ตรวจสอบว่ามีการ login หรือไม่
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('กรุณาเข้าสู่ระบบ');
    }

    $action = $_POST['action'] ?? '';
    
    // เริ่ม transaction
    $conn->beginTransaction();

    switch ($action) {
        case 'add':
            // อัพโหลดรูปภาพ (ถ้ามี)
            $image_path = null;
            if (isset($_FILES['project_image']) && $_FILES['project_image']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = '../../uploads/projects/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }

                $file_extension = pathinfo($_FILES['project_image']['name'], PATHINFO_EXTENSION);
                $new_filename = uniqid() . '_' . time() . '.' . $file_extension;
                $upload_path = $upload_dir . $new_filename;

                if (!move_uploaded_file($_FILES['project_image']['tmp_name'], $upload_path)) {
                    throw new Exception('ไม่สามารถอัพโหลดรูปภาพได้');
                }
                $image_path = $upload_path;
            }

            // เพิ่มข้อมูลโครงการ
            $sql = "INSERT INTO projects (
                        name, type, image_path, budget, remaining_budget,
                        principle, objective, target, location,
                        start_date, end_date, status
                    ) VALUES (
                        :name, :type, :image_path, :budget, :budget,
                        :principle, :objective, :target, :location,
                        :start_date, :end_date, 'draft'
                    )";

            $stmt = $conn->prepare($sql);
            $stmt->execute([
                ':name' => $_POST['name'],
                ':type' => $_POST['type'],
                ':image_path' => $image_path,
                ':budget' => $_POST['budget'],
                ':principle' => $_POST['principle'],
                ':objective' => $_POST['objective'],
                ':target' => $_POST['target'],
                ':location' => $_POST['location'],
                ':start_date' => $_POST['start_date'],
                ':end_date' => $_POST['end_date']
            ]);

            $project_id = $conn->lastInsertId();

            // บันทึกวิธีดำเนินการ
            if (!empty($_POST['method_descriptions'])) {
                $stmt = $conn->prepare("INSERT INTO project_methods (project_id, description, order_number) VALUES (?, ?, ?)");
                foreach ($_POST['method_descriptions'] as $index => $description) {
                    if (!empty($description)) {
                        $stmt->execute([$project_id, $description, $index + 1]);
                    }
                }
            }

            // บันทึกแผนการปฏิบัติงาน
            if (!empty($_POST['plan_descriptions'])) {
                $stmt = $conn->prepare("INSERT INTO project_plans (project_id, description, user_id, start_date, end_date, order_number) 
                                      VALUES (?, ?, ?, ?, ?, ?)");
                foreach ($_POST['plan_descriptions'] as $index => $description) {
                    if (!empty($description) && !empty($_POST['plan_users'][$index])) {
                        $stmt->execute([
                            $project_id,
                            $description,
                            $_POST['plan_users'][$index],
                            $_POST['plan_start_dates'][$index] ?? null,
                            $_POST['plan_end_dates'][$index] ?? null,
                            $index + 1
                        ]);
                    }
                }
            }

            // บันทึกผู้รับผิดชอบโครงการ
            if (!empty($_POST['project_managers'])) {
                $stmt = $conn->prepare("INSERT INTO project_managers (project_id, user_id, role) VALUES (?, ?, ?)");
                foreach ($_POST['project_managers'] as $manager) {
                    $stmt->execute([$project_id, $manager, 'manager']);
                }
            }

            // Commit transaction
            $conn->commit();

            // ส่ง notification ถึงกรรมการทุกคน
            $director_stmt = $conn->query("SELECT user_id FROM users WHERE director = 1");
            $directors = $director_stmt->fetchAll(PDO::FETCH_COLUMN);
            
            foreach ($directors as $director_id) {
                // TODO: ส่ง notification (จะพัฒนาในส่วนถัดไป)
            }

            echo json_encode([
                'status' => 'success',
                'message' => 'บันทึกโครงการเรียบร้อยแล้ว',
                'redirect' => '../../pages/project/view_project.php?id=' . $project_id
            ]);
            break;

        case 'edit':
            // TODO: พัฒนาส่วนแก้ไขโครงการ
            break;

        case 'delete':
            // TODO: พัฒนาส่วนลบโครงการ
            break;

        default:
            throw new Exception('Invalid action');
    }

} catch (Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }

    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
        
    ]);
} 