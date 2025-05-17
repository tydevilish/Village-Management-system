<?php
session_start();
require_once '../../config/config.php';
require_once '../../includes/auth.php';

// ตรวจสอบสิทธิ์
checkPageAccess(PAGE_MANAGE_PAYMENT);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $transaction_id = $_POST['transaction_id'];
        $status = $_POST['status'];
        $reason = isset($_POST['reason']) ? $_POST['reason'] : null;

        $conn->beginTransaction();

        // ดึงข้อมูลที่จำเป็นสำหรับสร้างเลขที่ใบเสร็จ
        $stmt = $conn->prepare("
            SELECT 
                t.transaction_id,
                p.month,
                p.year
            FROM transactions t
            JOIN payments p ON t.payment_id = p.payment_id
            WHERE t.transaction_id = ?
        ");
        $stmt->execute([$transaction_id]);
        $payment_data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($status === 'approved') {
            // หาเลขที่ใบเสร็จล่าสุดของเดือนและปีนั้นๆ
            $stmt = $conn->prepare("
                SELECT MAX(SUBSTRING_INDEX(receipt_number, '-', -1)) as last_number
                FROM transactions 
                WHERE receipt_number LIKE ?
            ");
            $receipt_prefix = sprintf("%02d-%02d-", $payment_data['year'] % 100, $payment_data['month']);
            $stmt->execute([$receipt_prefix . '%']);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // สร้างเลขที่ใบเสร็จใหม่
            $next_number = ($result['last_number'] ? intval($result['last_number']) + 1 : 1);
            $receipt_number = sprintf(
                "%02d-%02d-%03d",
                $payment_data['year'] % 100,
                $payment_data['month'],
                $next_number
            );

            // อัพเดทสถานะและเลขที่ใบเสร็จ
            $stmt = $conn->prepare("
                UPDATE transactions 
                SET status = 'approved',
                    approved_at = CURRENT_TIMESTAMP,
                    approved_by = ?,
                    receipt_number = ?
                WHERE transaction_id = ?
            ");
            $stmt->execute([$_SESSION['user_id'], $receipt_number, $transaction_id]);
            
        } else if ($status === 'rejected') {
            $stmt = $conn->prepare("
                UPDATE transactions 
                SET status = 'rejected',
                    rejected_at = CURRENT_TIMESTAMP,
                    rejected_by = ?,
                    reject_reason = ?
                WHERE transaction_id = ?
            ");
            $stmt->execute([$_SESSION['user_id'], $reason, $transaction_id]);
        }
        
        // ดึง payment_id สำหรับส่งกลับ
        $stmt = $conn->prepare("SELECT payment_id FROM transactions WHERE transaction_id = ?");
        $stmt->execute([$transaction_id]);
        $payment_id = $stmt->fetchColumn();
        
        $conn->commit();
        
        echo json_encode(['success' => true, 'payment_id' => $payment_id]);
    } catch (Exception $e) {
        $conn->rollBack();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} 