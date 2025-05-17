<?php
session_start();
require_once '../../config/config.php';
require_once '../../includes/auth.php';

// ตรวจสอบสิทธิ์
checkPageAccess(PAGE_MANAGE_PAYMENT);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $month = $_POST['month'];
        $year = $_POST['year'] + 543;
        $description = $_POST['description'];
        $amount = $_POST['amount'];
        $selected_users = isset($_POST['selected_users']) ? $_POST['selected_users'] : [];
        $approve_by = $_SESSION['user_id'];
        // เริ่ม transaction
        $conn->beginTransaction();
        
        // เพิ่มข้อมูลในตาราง payments
        $stmt = $conn->prepare("
            INSERT INTO payments (month, year, description, amount, created_by) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$month, $year, $description, $amount, $_SESSION['user_id']]);
        $payment_id = $conn->lastInsertId();
        
        // เพิ่มข้อมูลการกำหนดผู้ใช้และสร้าง transactions
        if (!empty($selected_users)) {
            // หาเลขที่ใบวางบิลล่าสุดของเดือนและปีนั้นๆ
            $stmt = $conn->prepare("
                SELECT MAX(SUBSTRING_INDEX(invoice_number, '-', -1)) as last_number
                FROM transactions 
                WHERE invoice_number LIKE ?
            ");
            $invoice_prefix = sprintf("%02d-%02d-", $year % 100, $month);
            $stmt->execute([$invoice_prefix . '%']);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // สร้างเลขที่ใบวางบิลใหม่
            $next_number = ($result['last_number'] ? intval($result['last_number']) + 1 : 1);

            // เตรียม statement สำหรับสร้าง transaction
            $create_transaction = $conn->prepare("
                INSERT INTO transactions (
                    payment_id, 
                    user_id, 
                    amount,
                    status,
                    payment_type,
                    approved_at,
                    approved_by,
                    created_at,
                    month,
                    year,
                    username,
                    invoice_number
                ) VALUES (?, ?, ?, ?, ?, CURRENT_TIMESTAMP, ?, NOW(), ?, ?, (SELECT username FROM users WHERE user_id = ?), ?)
            ");

            // เตรียม statement สำหรับตรวจสอบเงินล่วงหน้า
            $check_advance = $conn->prepare("
                SELECT 
                    ap.advance_id,
                    ap.remaining_amount
                FROM advance_payments ap
                WHERE ap.user_id = ?
                AND ap.status = 'active'
                AND ap.remaining_amount > 0
                ORDER BY ap.payment_date ASC
            ");

            foreach ($selected_users as $user_id) {
                $invoice_number = sprintf(
                    "%02d-%02d-%03d",
                    $year % 100,
                    $month,
                    $next_number
                );
                
                // ตรวจสอบเงินล่วงหน้า
                $check_advance->execute([$user_id]);
                $advance = $check_advance->fetch(PDO::FETCH_ASSOC);

                if ($advance && $advance['remaining_amount'] >= $amount) {
                    // สร้าง transaction ด้วยสถานะ approved
                    $create_transaction->execute([
                        $payment_id,
                        $user_id,
                        $amount,
                        'approved',
                        'advance',
                        $approve_by,
                        $month,
                        $year,
                        $user_id,
                        $invoice_number
                    ]);
                    
                    // หาเลขที่ใบเสร็จล่าสุดของเดือนและปีนั้นๆ
                    $stmt = $conn->prepare("
                        SELECT MAX(SUBSTRING_INDEX(receipt_number, '-', -1)) as last_number
                        FROM transactions 
                        WHERE receipt_number LIKE ?
                    ");
                    $receipt_prefix = sprintf("%02d-%02d-", $year % 100, $month);
                    $stmt->execute([$receipt_prefix . '%']);
                    $result = $stmt->fetch(PDO::FETCH_ASSOC);

                    // สร้างเลขที่ใบเสร็จใหม่
                    $next_receipt_number = ($result['last_number'] ? intval($result['last_number']) + 1 : 1);
                    $receipt_number = sprintf(
                        "%02d-%02d-%03d",
                        $year % 100,
                        $month,
                        $next_receipt_number
                    );

                    // บันทึกเลขที่ใบเสร็จลงฐานข้อมูล
                    $update_stmt = $conn->prepare("
                        UPDATE transactions 
                        SET receipt_number = ? 
                        WHERE payment_id = ? AND user_id = ?
                    ");
                    $update_stmt->execute([$receipt_number, $payment_id, $user_id]);

                    // บันทึกการใช้เงินล่วงหน้า
                    $conn->prepare("
                        INSERT INTO advance_payment_transactions (
                            advance_id,
                            payment_id,
                            amount_used,
                            month,
                            year
                        ) VALUES (?, ?, ?, ?, ?)
                    ")->execute([
                        $advance['advance_id'],
                        $payment_id,
                        $amount,
                        $month,
                        $year
                    ]);

                    // อัพเดทยอดเงินล่วงหน้าคงเหลือ
                    $conn->prepare("
                        UPDATE advance_payments 
                        SET status = CASE 
                                WHEN remaining_amount = ? THEN 'used_up'
                                ELSE status 
                            END,
                        remaining_amount = remaining_amount - ?
                        WHERE advance_id = ?
                    ")->execute([
                        $amount, // สำหรับเช็คว่าจะเหลือ 0 ไหม
                        $amount, // สำหรับการลบ
                        $advance['advance_id']
                    ]);

                } else {
                    // สร้าง transaction ด้วยสถานะ not_paid
                    $create_transaction->execute([
                        $payment_id,
                        $user_id,
                        $amount,
                        'not_paid',
                        'normal',
                        $approve_by,
                        $month,
                        $year,
                        $user_id,
                        $invoice_number
                    ]);
                }
                $next_number++;
            }
        }
        
        $conn->commit();
        header('Location: ../../pages/payment/manage_payment.php?success=1');
        exit();

    } catch (Exception $e) {
        $conn->rollBack();
        header('Location: ../../pages/payment/manage_payment.php?error=' . urlencode($e->getMessage()));
        exit();
    }
} 