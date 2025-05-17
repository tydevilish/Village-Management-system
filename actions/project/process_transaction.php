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

    // ตรวจสอบข้อมูลที่จำเป็น
    if (!isset($_POST['project_id']) || !isset($_POST['type']) || !isset($_POST['amount'])) {
        throw new Exception('ข้อมูลไม่ครบถ้วน');
    }

    // เริ่ม transaction
    $conn->beginTransaction();

    // ตรวจสอบสถานะโครงการ
    $stmt = $conn->prepare("
        SELECT status, budget, remaining_budget 
        FROM projects 
        WHERE project_id = ?
    ");
    $stmt->execute([$_POST['project_id']]);
    $project = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$project) {
        throw new Exception('ไม่พบข้อมูลโครงการ');
    }

    if ($project['status'] !== 'approved') {
        throw new Exception('โครงการยังไม่ได้รับการอนุมัติ');
    }

    // อัพโหลดรูปภาพสลิป (ถ้ามี)
    $slip_path = null;
    if (isset($_FILES['slip_image']) && $_FILES['slip_image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../../uploads/slips/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $file_extension = pathinfo($_FILES['slip_image']['name'], PATHINFO_EXTENSION);
        $new_filename = uniqid() . '_' . time() . '.' . $file_extension;
        $upload_path = $upload_dir . $new_filename;

        if (!move_uploaded_file($_FILES['slip_image']['tmp_name'], $upload_path)) {
            throw new Exception('ไม่สามารถอัพโหลดรูปภาพได้');
        }
        $slip_path = $upload_path;
    }

    // คำนวณงบประมาณคงเหลือ
    $amount = floatval($_POST['amount']);
    $new_remaining_budget = $project['remaining_budget'];
    $budget = $project['budget'];
    
    if ($_POST['type'] === 'income') {
        $new_remaining_budget += $amount;
        $new_budget = $budget + $amount;
    } else {
        $new_remaining_budget -= $amount;
        $new_budget = $budget;
        // ตรวจสอบว่างบประมาณเพียงพอหรือไม่
        if ($new_remaining_budget < 0) {
            throw new Exception('งบประมาณคงเหลือไม่เพียงพอ');
        }
    }

    // บันทึกรายการธุรกรรม
    $stmt = $conn->prepare( "
        INSERT INTO project_transactions (
            project_id, type, amount, description, slip_image, 
            transaction_date
        ) VALUES (
            :project_id, :type, :amount, :description, :slip_image,
             CURRENT_TIMESTAMP
        )
    ");

    $stmt->execute([
        ':project_id' => $_POST['project_id'],
        ':type' => $_POST['type'],
        ':amount' => $amount,
        ':description' => $_POST['description'] ?? null,
        ':slip_image' => $slip_path,
    ]);

    // อัพเดทงบประมาณคงเหลือในโครงการ
    $stmt = $conn->prepare("
        UPDATE projects 
        SET remaining_budget = :remaining_budget,
        budget = :budget
        WHERE project_id = :project_id
    ");

    $stmt->execute([
        ':remaining_budget' => $new_remaining_budget,
        ':budget' => $new_budget,
        ':project_id' => $_POST['project_id']
    ]);

    // ตรวจสอบงบประมาณคงเหลือ และส่งการแจ้งเตือนถ้าใกล้หมด
    $budget_percentage = ($new_remaining_budget / $project['budget']) * 100;
    if ($budget_percentage <= 20) {
        echo json_encode([
            'status' => 'success',
            'message' => 'บันทึกรายการเรียบร้อยแล้ว',
            'redirect' => '../../pages/project/view_project.php?id=' . $project_id,
        ]);
    }

    // Commit transaction
    $conn->commit();

    echo json_encode([
        'status' => 'success',
        'message' => 'บันทึกรายการเรียบร้อยแล้ว',
        'redirect' => '../../pages/project/view_project.php?id=' . $_POST['project_id'],
        'remaining_budget' => $new_remaining_budget,
        'budget_percentage' => $budget_percentage
    ]);
    header('Location: ../../pages/project/view_project.php?id=' . $_POST['project_id']);

} catch (Exception $e) {
    // Rollback transaction ในกรณีที่เกิดข้อผิดพลาด
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }

    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
} 