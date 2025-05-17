<?php
session_start();
require_once '../../config/config.php';
require_once '../../includes/auth.php';

checkPageAccess(PAGE_MANAGE_PAYMENT);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $amount = floatval($_POST['amount']);
        $user_id = intval($_POST['user_id']);
        $created_by = $_SESSION['user_id'];

        // ตรวจสอบข้อมูล
        if ($amount <= 0) {
            throw new Exception('จำนวนเงินต้องมากกว่า 0');
        }

        if ($user_id <= 0) {
            throw new Exception('กรุณาเลือกผู้ใช้');
        }

        // ตรวจสอบว่าผู้ใช้มีอยู่จริง
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE user_id = ? AND role_id = 2");
        $stmt->execute([$user_id]);
        if (!$stmt->fetch()) {
            throw new Exception('ไม่พบข้อมูลผู้ใช้');
        }

        $conn->beginTransaction();

        // ตรวจสอบว่ามีข้อมูลเงินล่วงหน้าของผู้ใช้นี้อยู่แล้วหรือไม่
        $stmt = $conn->prepare("
            SELECT advance_id 
            FROM advance_payments 
            WHERE user_id = ? 
            AND status = 'active'
        ");
        $stmt->execute([$user_id]);
        $existing_advance = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existing_advance) {
            // ถ้ามีข้อมูลอยู่แล้ว ให้บวกเพิ่มที่ remaining_amount
            $stmt = $conn->prepare("
                UPDATE advance_payments 
                SET remaining_amount = remaining_amount + :amount
                WHERE advance_id = :advance_id
            ");
            $stmt->execute([
                'amount' => $amount,
                'advance_id' => $existing_advance['advance_id']
            ]);
        } else {
            // ถ้ายังไม่มีข้อมูล ให้สร้างใหม่
            $stmt = $conn->prepare("
                INSERT INTO advance_payments (
                    user_id,
                    remaining_amount,
                    created_by,
                    status
                ) VALUES (
                    :user_id,
                    :remaining_amount,
                    :created_by,
                    'active'
                )
            ");

            $stmt->execute([
                'user_id' => $user_id,
                'remaining_amount' => $amount,
                'created_by' => $created_by
            ]);
        }

        $conn->commit();
        $_SESSION['success'] = "บันทึกเงินล่วงหน้าเรียบร้อยแล้ว";
        header('Location: ../../pages/payment/manage_payment.php');
        exit();

    } catch (Exception $e) {
        $conn->rollBack();
        $_SESSION['error'] = "เกิดข้อผิดพลาด: " . $e->getMessage();
        header('Location: ../../pages/payment/manage_payment.php');
        exit();
    }
}

header('Location: ../../pages/payment/manage_payment.php');
exit(); 