<?php
session_start();
require_once '../../config/config.php';
require_once '../../includes/auth.php';
require_once '../../vendor/autoload.php';

// ตรวจสอบสิทธิ์
checkPageAccess(PAGE_MANAGE_PAYMENT);

use PhpOffice\PhpSpreadsheet\IOFactory;

// เพิ่มการตรวจสอบ users ที่ได้รับสิทธิ์พิเศษ
function shouldSkipUser($user_id, $payment_id) {
    // ข้าม users 125 และ 316 ทุกเดือน
    if (in_array($user_id, [125, 316])) {
        return true;
    }
    
    // ข้าม user 153 เฉพาะเดือนแรก (payment_id = 1)
    if ($user_id == 153 && $payment_id == 1) {
        return true;
    }
    
    return false;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_FILES['csv_file']) || isset($_FILES['xlsx_file']))) {
    try {
        $file = isset($_FILES['csv_file']) ? $_FILES['csv_file'] : $_FILES['xlsx_file'];
        $file_path = $file['tmp_name'];
        
        if (!file_exists($file_path)) {
            throw new Exception('ไม่พบไฟล์ที่อัปโหลด');
        }

        // ตรวจสอบนามสกุลไฟล์
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if ($file_extension === 'xlsx') {
            // โหลดไฟล์ Excel
            $spreadsheet = IOFactory::load($file_path);
            $worksheet = $spreadsheet->getActiveSheet();

            // เริ่มทำ Transaction
            $conn->beginTransaction();

            // ก็บข้อมูลการชำระเงินทั้งหมดเพื่อคำนวณเบี้ยปรับ
            $paymentHistory = [];

            // ดึงรายการ user_id ที่มีอยู่ในระบบ
            $stmt = $conn->prepare("SELECT user_id FROM users");
            $stmt->execute();
            $validUserIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

            // อ่านข้อมูลจากแต่ละแถว
            foreach ($worksheet->getRowIterator(2) as $row) { // เริ่มจากแถวที่ 2
                $cellIterator = $row->getCellIterator();
                $cellIterator->setIterateOnlyExistingCells(false);
                $rowData = [];
                foreach ($cellIterator as $cell) {
                    $rowData[] = $cell->getValue();
                }

                // ข้ามแถวที่ว่างเปล่า
                if (empty($rowData[0])) continue;

                $user_id = $rowData[0]; // บ้านเลขที่อยู่ในคอลัมน์แรก

                // ตรวจสอบว่า user_id มีอยู่ในระบบหรือไม่
                if (!in_array($user_id, $validUserIds)) {
                    continue; // ข้ามไปถ้าไม่มี user_id นี้ในระบบ
                }

                // วนลูปผ่านคอลัมน์ payment (D ถึง L หรือ index 3 ถึง 11)
                for ($i = 3; $i <= 11; $i++) {  // เปลี่ยนจาก 10 เป็น 11 เพื่อรวมเดือนปัจจุบัน
                    $payment_id = match($i) {
                        3 => 1,  // คอลัมน์ D
                        4 => 2,  // คอลัมน์ E
                        5 => 3,  // คอลัมน์ F
                        6 => 4,  // คอลัมน์ G
                        7 => 5,  // คอลัมน์ H
                        8 => 6,  // คอลัมน์ I
                        9 => 7,  // คอลัมน์ J
                        10 => 8, // คอลัมน์ K
                        11 => 9, // คอลัมน์ L (เดือนปัจจุบัน)
                        default => null
                    };

                    if (!$payment_id) continue;

                    // ข้าม users ที่ได้รับสิทธิ์พิเศษ
                    if (shouldSkipUser($user_id, $payment_id)) {
                        continue;
                    }

                    // ตรวจสอบว่า payment_id มีอยู่ในตาราง payments หรือไม่
                    $stmt = $conn->prepare("SELECT payment_id FROM payments WHERE payment_id = ?");
                    $stmt->execute([$payment_id]);
                    if (!$stmt->fetch()) {
                        continue;
                    }

                    $amount = $rowData[$i];
                    if (empty($amount)) $amount = 0;

                    // เก็บประวัติการชำระเงิน
                    if (!isset($paymentHistory[$user_id])) {
                        $paymentHistory[$user_id] = [];
                    }
                    $paymentHistory[$user_id][$payment_id] = $amount == 300;

                    if ($amount == 300) {
                        // อัพเดทสถานะการชำระเงิน
                        $stmt = $conn->prepare("
                            INSERT INTO transactions (payment_id, user_id, amount, status, created_at, approved_at, approved_by)
                            VALUES (?, ?, 300, 'approved', NOW(), NOW(), 1)
                            ON DUPLICATE KEY UPDATE 
                            status = 'approved',
                            amount = 300,
                            approved_at = NOW(),
                            approved_by = 1
                        ");
                        $stmt->execute([$payment_id, $user_id]);

                        // รีเซ็ตเบี้ยปรับเมื่อชำระเงิน
                        $stmt = $conn->prepare("
                            INSERT INTO payment_users (payment_id, user_id, penalty)
                            VALUES (?, ?, 0)
                            ON DUPLICATE KEY UPDATE penalty = 0
                        ");
                        $stmt->execute([$payment_id, $user_id]);
                    } else {
                        // ถ้าเป็นเดือนปัจจุบัน (payment_id = 9) ไม่คิดเบี้ยปรับ
                        if ($payment_id == 9) {
                            // เพิ่มข้อมูลแต่ไม่มีเบี้ยปรับ
                            $stmt = $conn->prepare("
                                INSERT INTO payment_users (payment_id, user_id, penalty)
                                VALUES (?, ?, 0)
                                ON DUPLICATE KEY UPDATE penalty = 0
                            ");
                            $stmt->execute([$payment_id, $user_id]);
                            continue;
                        }

                        // คำนวณเบี้ยปรับสำหรับเดือนอื่นๆ
                        $penalty = 30; // เบี้ยปรับเริ่มต้นสำหรับเดือนที่ไม่ชำระ
                        
                        // อัพเดทเบี้ยปรับสำหรับเดือนปัจจุบัน
                        $stmt = $conn->prepare("
                            INSERT INTO payment_users (payment_id, user_id, penalty)
                            VALUES (?, ?, ?)
                            ON DUPLICATE KEY UPDATE penalty = VALUES(penalty)
                        ");
                        $stmt->execute([$payment_id, $user_id, $penalty]);

                        // อัพเดทเบี้ยปรับสะสมสำหรับเดือนก่อนหน้าที่ยังไม่ได้ชำระ
                        for ($prev_id = 1; $prev_id < $payment_id; $prev_id++) {
                            if (isset($paymentHistory[$user_id][$prev_id]) && !$paymentHistory[$user_id][$prev_id]) {
                                // คำนวณเบี้ยปรับสะสม: จำนวนเดือนที่ผ่านมา * 30
                                $accumulated_penalty = ($payment_id - $prev_id + 1) * 30;
                                
                                $stmt = $conn->prepare("
                                    UPDATE payment_users 
                                    SET penalty = ?
                                    WHERE payment_id = ? AND user_id = ?
                                ");
                                $stmt->execute([$accumulated_penalty, $prev_id, $user_id]);
                            }
                        }
                    }
                }
            }

            $conn->commit();
            $_SESSION['success'] = "นำเข้าข้อมูลสำเร็จ";
            header('Location: ../../pages/payment/manage_payment.php');
            exit();

        } else {
            throw new Exception('รองรับเฉพาะไฟล์ .xlsx เท่านั้น');
        }

        if (isset($spreadsheet)) {
            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);
        }

        header('Location: ../../pages/payment/manage_payment.php');
        exit();

    } catch (Exception $e) {
        if (isset($conn)) {
            $conn->rollBack();
        }
        
        $_SESSION['error'] = "เกิดข้อผิดพลาด: " . $e->getMessage();
        header('Location: ../../pages/payment/manage_payment.php');
        exit();
    }
}

header('Location: ../../pages/payment/manage_payment.php');
exit();