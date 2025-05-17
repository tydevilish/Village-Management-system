<?php
session_start();
require_once '../../config/config.php';
require_once '../../includes/auth.php';

checkPageAccess(PAGE_MANAGE_PAYMENT);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $user_id = intval($_POST['user_id']);
        $payment_id = intval($_POST['payment_id']);
        $status = $_POST['status'];

        $conn->beginTransaction();

        // ดึงข้อมูล penalty และ amount จาก transactions
        $stmt = $conn->prepare("
            SELECT COALESCE(penalty, 0) as penalty,
                   COALESCE(amount, 0) as amount,
                   status as current_status
            FROM transactions 
            WHERE payment_id = ? AND user_id = ?
        ");
        $stmt->execute([$payment_id, $user_id]);
        $transaction_data = $stmt->fetch(PDO::FETCH_ASSOC);

        // ถ้าไม่มีข้อมูลใน transactions ให้ดึงจาก payments
        if (!$transaction_data) {
            $stmt = $conn->prepare("SELECT amount FROM payments WHERE payment_id = ?");
            $stmt->execute([$payment_id]);
            $base_amount = $stmt->fetchColumn();
            $total_amount = $base_amount;
            $current_status = null;
        } else {
            $total_amount = $transaction_data['amount'] + $transaction_data['penalty'];
            $current_status = $transaction_data['current_status'];
        }

        $status_thai = [
            'approved' => 'ชำระแล้ว',
            'pending' => 'รอตรวจสอบ',
            'rejected' => 'ไม่อนุมัติ',
            'not_paid' => 'ยังไม่ชำระ'
        ];

        // ถ้าสถานะเหมือนเดิม
        if ($current_status && $status === $current_status) {
            $_SESSION['error'] = "ผู้ใช้อยู่ในสถานะ " . $status_thai[$status] ." อยู่แล้ว";
            header('Location: ../../pages/payment/manage_payment.php');
            exit();
        }

        if ($current_status) {
            // อัพเดทข้อมูลที่มีอยู่
            if ($status === 'approved') {
                $stmt = $conn->prepare("
                    UPDATE transactions 
                    SET status = 'approved',
                        amount = ?,
                        approved_at = CURRENT_TIMESTAMP,
                        approved_by = ?,
                        rejected_at = NULL,
                        rejected_by = NULL,
                        reject_reason = NULL
                    WHERE payment_id = ? AND user_id = ?
                ");
                $stmt->execute([$total_amount, $_SESSION['user_id'], $payment_id, $user_id]);
            } else if ($status === 'rejected') {
                $reject_reason = isset($_POST['reject_reason']) ? $_POST['reject_reason'] : '';
                $stmt = $conn->prepare("
                    UPDATE transactions 
                    SET status = 'rejected',
                        amount = ?,
                        approved_at = NULL,
                        approved_by = NULL,
                        reject_reason = ?,
                        rejected_at = CURRENT_TIMESTAMP,
                        rejected_by = ?
                    WHERE payment_id = ? AND user_id = ?
                ");
                $stmt->execute([$total_amount, $reject_reason, $_SESSION['user_id'], $payment_id, $user_id]);
            } else {
                $stmt = $conn->prepare("
                    UPDATE transactions 
                    SET status = ?,
                        amount = ?,
                        approved_at = NULL,
                        approved_by = NULL,
                        reject_reason = NULL,
                        rejected_at = NULL,
                        rejected_by = NULL
                    WHERE payment_id = ? AND user_id = ?
                ");
                $stmt->execute([$status, $total_amount, $payment_id, $user_id]);
            }
        } else {
            // สร้างข้อมูลใหม่
            $stmt = $conn->prepare("
                INSERT INTO transactions (
                    payment_id, 
                    user_id, 
                    status, 
                    amount, 
                    approved_at, 
                    approved_by,
                    rejected_at,
                    rejected_by,
                    reject_reason
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            if ($status === 'approved') {
                $stmt->execute([
                    $payment_id, 
                    $user_id, 
                    'approved', 
                    $total_amount, 
                    date('Y-m-d H:i:s'), 
                    $_SESSION['user_id'],
                    NULL,
                    NULL,
                    NULL
                ]);
            } else if ($status === 'rejected') {
                $reject_reason = isset($_POST['reject_reason']) ? $_POST['reject_reason'] : '';
                $stmt->execute([
                    $payment_id, 
                    $user_id, 
                    'rejected', 
                    $total_amount, 
                    NULL,
                    NULL,
                    date('Y-m-d H:i:s'),
                    $_SESSION['user_id'],
                    $reject_reason
                ]);
            } else {
                $stmt->execute([
                    $payment_id, 
                    $user_id, 
                    $status, 
                    $total_amount, 
                    NULL,
                    NULL,
                    NULL,
                    NULL,
                    NULL
                ]);
            }
        }

        $conn->commit();
        $_SESSION['success'] = "อัพเดทสถานะการชำระเงินเรียบร้อยแล้ว";
    } catch (Exception $e) {
        $conn->rollBack();
        $_SESSION['error'] = "เกิดข้อผิดพลาด: " . $e->getMessage();
    }
}

header('Location: ../../pages/payment/manage_payment.php');
exit();
