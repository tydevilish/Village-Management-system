<?php
session_start();
require_once '../../config/config.php';
require_once '../../includes/auth.php';

checkPageAccess(PAGE_MANAGE_PROJECT);

header('Content-Type: application/json');

try {
    // Debug: ตรวจสอบข้อมูลที่ส่งมา
    error_log("POST Data: " . print_r($_POST, true));

    // ตรวจสอบว่ามีการ login หรือไม่
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('กรุณาเข้าสู่ระบบ');
    }

    // ตรวจสอบว้อมูลที่จำเป็น
    if (!isset($_POST['project_id']) || empty($_POST['status'])) {
        throw new Exception('ข้อมูลไม่ครบถ้วน (Project ID: ' . $_POST['project_id'] . ', Status: ' . $_POST['status'] . ')');
    }

    $project_id = $_POST['project_id'];
    $status = $_POST['status'];
    $comment = isset($_POST['comment']) ? $_POST['comment'] : '';
    $user_id = $_SESSION['user_id'];

    // เริ่ม transaction
    $conn->beginTransaction();

    // บันทึกการอนุมัติ
    $stmt = $conn->prepare("
        INSERT INTO project_approvals (
            project_id, 
            user_id, 
            status, 
            comment, 
            approved_at
        ) VALUES (
            :project_id,
            :user_id,
            :status,
            :comment,
            CURRENT_TIMESTAMP
        )
    ");

    $params = [
        ':project_id' => $project_id,
        ':user_id' => $user_id,
        ':status' => $status,
        ':comment' => $comment
    ];

    // Debug: ตรวจสอบ SQL และ parameters
    error_log("SQL: INSERT INTO project_approvals");
    error_log("Parameters: " . print_r($params, true));

    $stmt->execute($params);

    // ตรวจสอบจำนวนการอนุมัติ
    $stmt = $conn->prepare("
        SELECT 
            COUNT(CASE WHEN status = 'approved' THEN 1 END) as approval_count,
            COUNT(*) as total_votes,
            (SELECT COUNT(*) FROM users WHERE director = 1) as total_directors
        FROM project_approvals
        WHERE project_id = ?
    ");
    $stmt->execute([$project_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    $approval_percentage = ($result['approval_count'] / $result['total_directors']) * 100;
    $rejection_count = $result['total_votes'] - $result['approval_count'];


    if ($approval_percentage) {
        // อนุมัติเมื่อมีการโหวต approved มากกว่าหรือเท่ากับ 50%
        $stmt = $conn->prepare("
            UPDATE projects 
            SET status = 'approved'
            WHERE project_id = ?
        ");
        $stmt->execute([$project_id]);
    } 
    

    if ($approval_percentage >= 50) {
        // อนุมัติเมื่อมีการโหวต approved มากกว่าหรือเท่ากับ 50%
        $stmt = $conn->prepare("
            UPDATE projects 
            SET status = 'approved'
            WHERE project_id = ?
        ");
        $stmt->execute([$project_id]);
    } 
    else if ($rejection_count >= ceil($result['total_directors'] / 2)) {
        $stmt = $conn->prepare("
            UPDATE projects 
            SET status = 'rejected'
            WHERE project_id = ?
        ");
        $stmt->execute([$project_id]);
    }

    // Debug log
    error_log("Approval stats: " . print_r([
        'approval_count' => $result['approval_count'],
        'total_votes' => $result['total_votes'],
        'total_directors' => $result['total_directors'],
        'approval_percentage' => $approval_percentage,
        'rejection_count' => $rejection_count
    ], true));

    // Commit transaction
    $conn->commit();

    echo json_encode([
        'status' => 'success',
        'message' => 'บันทึกการอนุมัติเรียบร้อยแล้ว',
        'debug_info' => [
            'approval_count' => $result['approval_count'],
            'total_directors' => $result['total_directors'],
            'approval_percentage' => $approval_percentage
        ]
    ]);

} catch (Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }

    error_log("Error in process_approval.php: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
        'debug_info' => [
            'post_data' => $_POST,
            'error' => $e->getMessage()
        ]
    ]);
} 