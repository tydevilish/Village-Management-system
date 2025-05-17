<?php
require_once '../../config/config.php';
require_once '../../includes/auth.php';
require_once '../../vendor/autoload.php';

// รับค่าพารามิเตอร์
$user_id = $_GET['user_id'] ?? null;
$payment_id = $_GET['payment_id'] ?? null;
$transaction_id = $_GET['transaction_id'] ?? null;

if (!$user_id || !$payment_id || !$transaction_id) {
    die('ข้อมูลไม่ครบถ้วน');
}

// ดึงข้อมูลการชำระเงิน
$stmt = $conn->prepare("
    SELECT 
        t.transaction_id,
        t.created_at,
        p.amount,
        p.description,
        p.month,
        p.year,
        u.fullname,
        u.non_contact_address,
        t.receipt_number,
        t.penalty
    FROM transactions t
    JOIN payments p ON t.payment_id = p.payment_id
    JOIN users u ON t.user_id = u.user_id
    WHERE t.transaction_id = ? AND t.user_id = ? AND t.payment_id = ?
");
$stmt->execute([$transaction_id, $user_id, $payment_id]);
$receipt_data = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$receipt_data['receipt_number']) {
    // หาเลขที่ใบเสร็จล่าสุดของเดือนและปีนั้นๆ
    $stmt = $conn->prepare("
        SELECT MAX(SUBSTRING_INDEX(receipt_number, '-', -1)) as last_number
        FROM transactions 
        WHERE receipt_number LIKE ?
    ");
    $receipt_prefix = sprintf("%02d-%02d-", $receipt_data['year'] % 100, $receipt_data['month']);
    $stmt->execute([$receipt_prefix . '%']);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    // สร้างเลขที่ใบเสร็จใหม่
    $next_number = ($result['last_number'] ? intval($result['last_number']) + 1 : 1);
    $receipt_number = sprintf(
        "%02d-%02d-%03d",
        $receipt_data['year'] % 100,
        $receipt_data['month'],
        $next_number
    );

    // บันทึกเลขที่ใบเสร็จลงฐานข้อมูล
    $update_stmt = $conn->prepare("
        UPDATE transactions 
        SET receipt_number = ? 
        WHERE transaction_id = ?
    ");
    $update_stmt->execute([$receipt_number, $transaction_id]);

    // อัพเดทข้อมูลสำหรับแสดงผล
    $receipt_data['receipt_number'] = $receipt_number;
}

if (!$receipt_data) {
    die('ไม่พบข้อมูลการชำระเงิน');
}

// ใพิ่ม query ดึงข้อมูลเงินล่วงหน้าคงเหลือ
$stmt = $conn->prepare("
    SELECT remaining_amount 
    FROM advance_payments
    WHERE user_id = ? 
    AND status = 'active'
    ORDER BY payment_date DESC 
    LIMIT 1
");
$stmt->execute([$user_id]);
$advance_balance = $stmt->fetch(PDO::FETCH_ASSOC);

// ใช้ FPDI สำหรับเติมข้อมูลลงในเทมเพลต PDF
use setasign\Fpdi\Fpdi;

class MYPDF extends Fpdi
{
    function Header()
    {
        // ไม่ต้องมี header
    }

    function Footer()
    {
        // ไม่ต้องมี footer
    }
}

// เพิ่มบรรทัดนี้หลังจาก require
header('Content-Type: text/html; charset=utf-8');

// แก้ไขการเรียกใช้ฟอนต์
$pdf = new MYPDF();
$pdf->AddFont('THSarabunNew', '', 'THSarabunNew.php');
$pdf->SetFont('THSarabunNew', '', 12);

// กำหนดขนาดกระดาษเป็น A5 แนวนอน (14.85 x 21 ซม.)
$pdf->AddPage('L', array(148.5, 210));
$pdf->SetAutoPageBreak(true, 0);
$pdf->SetMargins(0, 0, 0);
$pdf->SetDisplayMode('real', 'default');

// โหลดเทมเพลต PDF
$template = '../../src/template_receipt.pdf';
$pdf->setSourceFile($template);
$tplIdx = $pdf->importPage(1);
$pdf->useTemplate($tplIdx);

// เลขที่ใบเสร็จ
$pdf->SetXY(155, 43.5);
$pdf->Write(0, $receipt_data['receipt_number']);

// วันที่
$pdf->setXY(148, 49.5);
$pdf->Write(0, date('d/m/Y', strtotime($receipt_data['created_at'])));

// ชื่อผู้จ่าย
$pdf->setXY(36, 44);
$pdf->Write(0, iconv('UTF-8', 'cp874', $receipt_data['fullname']));

// ที่อยู่
$pdf->setXY(34, 49.8);
$pdf->Write(0, iconv('UTF-8', 'cp874', ' บ้านเลขที่ ' . $receipt_data['non_contact_address']));

// ลำดับ
$pdf->setXY(18, 73);
$pdf->Write(0, '1');

if ($receipt_data['penalty'] > 0) {
    $pdf->setXY(18, 79);
    $pdf->Write(0, '2');

    // สร้าง array เก็บชื่อเดือนภาษาไทย
    $thai_months = array(
        1 => 'มกราคม',
        2 => 'กุมภาพันธ์',
        3 => 'มีนาคม',
        4 => 'เมษายน',
        5 => 'พฤษภาคม',
        6 => 'มิถุนายน',
        7 => 'กรกฎาคม',
        8 => 'สิงหาคม',
        9 => 'กันยายน',
        10 => 'ตุลาคม',
        11 => 'พฤศจิกายน',
        12 => 'ธันวาคม'
    );

    $month_name = $thai_months[(int)$receipt_data['month']];
    $pdf->setXY(27, 79);
    $pdf->Write(0, iconv('UTF-8', 'cp874', 'เบี้ยปรับ 10% ( เดือน' . $month_name . ' )'));

    $penalty_units = ceil($receipt_data['penalty'] / 30);

    // แสดงจำนวนเบี้ยปรับ
    $pdf->setXY(112, 79);
    $pdf->Write(0, $penalty_units);

    $pdf->setXY(186, 79);
    $pdf->Write(0, number_format($receipt_data['penalty'], 2));

    $pdf->setXY(148.5, 79);
    $pdf->Write(0, number_format(30, 2));
}
// รายการชำระ
$pdf->setXY(27, 73);
$pdf->Write(0, iconv('UTF-8', 'cp874', $receipt_data['description']));

// จำนวน / หน่วยนับ
$pdf->setXY(112, 73);
$pdf->Write(0, '1');



// ราคา / หน่วย Price
$pdf->setXY(147, 73);
$pdf->Write(0, number_format(300, 2));

// จำนวนเงิน
$pdf->setXY(186, 73);
$pdf->Write(0, number_format($receipt_data['amount'], 2));


// เพิ่มฟอนต์ตัวหนา
$pdf->AddFont('THSarabunNew', 'B', 'THSarabunNew_b.php');

// จำนวนเงิน (ตัวเลข) - ตัวหนา
$total_amount = $receipt_data['amount'] + ($receipt_data['penalty'] ?? 0);
$pdf->SetFont('THSarabunNew', 'B', 12); // เปลี่ยนเป็นตัวหนา
$pdf->setXY(186, 105);
$pdf->Write(0, number_format($total_amount, 2));

// กลับไปใช้ฟอนต์ปกติ
$pdf->SetFont('THSarabunNew', '', 12);

if ($advance_balance && isset($advance_balance['remaining_amount']) && $advance_balance['remaining_amount'] > 0) {
    $pdf->SetTextColor(255, 0, 0); // RGB: Red
    $pdf->setXY(27, 95);
    $pdf->Write(0, iconv('UTF-8', 'cp874', '*** คงเหลือเงินที่ชำระล่วงหน้า ' . number_format($advance_balance['remaining_amount'], 2)  . ' บาท'));
    // กลับไปใช้สีดำตามเดิม
    $pdf->SetTextColor(0, 0, 0); // RGB: Black
}


// เพิ่มฟังก์ชันแปลงตัวเลขเป็นตัวหนังสือ
function convertNumberToThaiBaht($number)
{
    $number = number_format($number, 2, '.', '');
    $numberStr = explode('.', $number);
    $digit = ['ศูนย์', 'หนึ่ง', 'สอง', 'สาม', 'สี่', 'ห้า', 'หก', 'เจ็ด', 'แปด', 'เก้า'];
    $unit = ['', 'สิบ', 'ร้อย', 'พัน', 'หมื่น', 'แสน', 'ล้าน'];

    $bahtText = '';
    $length = strlen($numberStr[0]);

    for ($i = 0; $i < $length; $i++) {
        $position = $length - $i - 1;
        $value = intval($numberStr[0][$i]);

        if ($value != 0) {
            if ($position % 6 == 1 && $value == 2) {
                $bahtText .= 'ยี่';
            } elseif ($position % 6 == 1 && $value == 1) {
                $bahtText .= '';
            } elseif ($position % 6 == 0 && $value == 1 && $length > 1) {
                $bahtText .= 'เอ็ด';
            } else {
                $bahtText .= $digit[$value];
            }

            $bahtText .= $unit[$position % 6];
            if ($position == 6) $bahtText .= 'ล้าน';
        }
    }

    $bahtText .= 'บาท';

    // จัดการทศนิยม
    if ($numberStr[1] == '00') {
        $bahtText .= 'ถ้วน';
    } else {
        $value = intval($numberStr[1]);
        if ($value > 0) {
            $bahtText .= $digit[intval($numberStr[1][0])] . 'สิบ';
            if (isset($numberStr[1][1]) && $numberStr[1][1] > 0) {
                $bahtText .= $digit[intval($numberStr[1][1])];
            }
            $bahtText .= 'สตางค์';
        }
    }

    return $bahtText;
}

// แก้ไขส่วนแสดงจำนวนเงินตัวหนังสือ
$total_amount = $receipt_data['amount'] + ($receipt_data['penalty'] ?? 0);
$pdf->SetFont('THSarabunNew', 'B', 12); // เปลี่ยนเป็นตัวหนา
$pdf->setXY(95, 105);
$pdf->Write(0, iconv('UTF-8', 'cp874', convertNumberToThaiBaht($total_amount)));

// เพิ่มการแสดงผลเงินล่วงหน้าคงเหลือในใบเสร็

// แสดง PDF
$pdf->Output('receipt.pdf', 'I');
