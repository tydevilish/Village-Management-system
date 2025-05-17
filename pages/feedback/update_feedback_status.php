<?php
session_start();
require_once '../../config/config.php';
require_once '../../includes/auth.php';

try {
    $data = json_decode(file_get_contents('php://input'), true);

    if (!isset($data['id']) || !isset($data['status'])) {
        throw new Exception('ข้อมูลไม่ครบถ้วน');
    }

    $id = $data['id'];
    $status = $data['status'];
    $reject_reason = $data['reject_reason'] ?? null;
    $approval_note = $data['approval_note'] ?? null;
    $current_user_id = $_SESSION['user_id'];
    $current_timestamp = date('Y-m-d H:i:s');

    // ตรวจสอบส่ามีการเลือกประเภทหรือไม่
    $stmt = $conn->prepare("SELECT category_id , status FROM feedback WHERE id = ?");
    $stmt->execute([$id]);
    $feedback = $stmt->fetch();

    if (!$feedback['category_id']) {
        throw new Exception('กรุณาเลือกประเภทข้อเสนอแนะก่อนดำเนินการ');
    }

    $statusLabels = [
        'pending' => 'รอการตรวจสอบ',
        'in_progress' => 'กำลังดำเนินการ',
        'completed' => 'เสร็จสิ้น',
        'rejected' => 'ไม่อนุมัติ'
    ];

    if ($status === $feedback['status']) {
        $currentStatus = $statusLabels[$feedback['status']] ?? $feedback['status'];
        throw new Exception("ข้อเสนอแนะนี้อยู่ในสถานะ '{$currentStatus}' อยู่แล้ว");
    }

    // ตรวจสอบสถานะที่ส่งมา
    $allowed_statuses = ['pending', 'in_progress', 'completed', 'rejected'];
    if (!in_array($status, $allowed_statuses)) {
        throw new Exception('สถานะไม่ถูกต้อง');
    }

    // อัพเดทสถานะ
    $sql = "UPDATE feedback SET 
        status = ?, 
        reject_reason = ?,
        approval_note = ?,
        updated_at = CURRENT_TIMESTAMP";

    $params = [$status, $reject_reason, $approval_note];

    // เพิ่มข้อมูลตามสถานะ
    if ($status === 'completed') {
        $sql .= ", processed_by = null, processed_at = null, rejected_at = null, 
                  rejected_by = null, reject_reason = null, completed_at = ?, completed_by = ?";
        $params[] = $current_timestamp;
        $params[] = $current_user_id;
    } elseif ($status === 'rejected') {
        $sql .= ", processed_by = null, processed_at = null, completed_at = null, 
                  completed_by = null, approval_note = null, rejected_at = ?, rejected_by = ?";
        $params[] = $current_timestamp;
        $params[] = $current_user_id;
    } elseif ($status === 'in_progress') {
        $sql .= ", processed_by = null, processed_at = null, completed_at = null, 
        completed_by = null, approval_note = null, rejected_at = null, rejected_by = null , processed_at = ? , processed_by = ?";
        $params[] = $current_timestamp;
        $params[] = $current_user_id;
    }

    $sql .= " WHERE id = ?";
    $params[] = $id;

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);

    echo json_encode([
        'status' => 'success',
        'message' => 'อัพเดทสถานะเรียบร้อยแล้ว'
    ]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
