<?php
require_once '../../config/config.php';
require_once '../../includes/auth.php';
require_once '../../vendor/autoload.php';

// รับค่าพารามิเตอร์
$user_id = $_GET['user_id'] ?? null;
$payment_id = $_GET['payment_id'] ?? null;

if (!$user_id || !$payment_id) {
    die('ข้อมูลไม่ครบถ้วน');
}

// ดึงข้อมูลการชำระเงิน
$stmt = $conn->prepare("
    SELECT 
        p.amount,
        p.description,
        p.month,
        p.year,
        u.fullname,
        u.non_contact_address,
        t.penalty,
        t.transaction_id,
        t.invoice_number
    FROM payments p
    JOIN transactions t ON p.payment_id = t.payment_id 
    JOIN users u ON t.user_id = u.user_id
    WHERE p.payment_id = ? AND u.user_id = ?
");
$stmt->execute([$payment_id, $user_id]);
$invoice_data = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$invoice_data) {
    die('ไม่พบข้อมูลใบวางบิล');
}

// สร้างเลขที่ใบวางบิล
$invoice_number = sprintf(
    "INV%02d%02d%04d%04d",
    $invoice_data['year'] % 100,
    $invoice_data['month'],
    $payment_id,
    $invoice_data['transaction_id']
);

use setasign\Fpdi\Fpdi;

class MYPDF extends Fpdi {
    function Header() {}
    function Footer() {}
}

header('Content-Type: text/html; charset=utf-8');

$pdf = new MYPDF();
$pdf->AddFont('THSarabunNew', '', 'THSarabunNew.php');
$pdf->AddFont('THSarabunNew', 'B', 'THSarabunNew_b.php');
$pdf->SetFont('THSarabunNew', '', 12);

// กำหนดขนาดกระดาษ A5 แนวนอน
$pdf->AddPage('L', array(148.5, 210));
$pdf->SetAutoPageBreak(true, 0);
$pdf->SetMargins(0, 0, 0);

// โหลดเทมเพลต PDF
$pdf->setSourceFile('../../src/template_invoice.pdf');
$tplIdx = $pdf->importPage(1);
$pdf->useTemplate($tplIdx);

// เลขที่ใบวางบิล
$pdf->SetFont('THSarabunNew', 'B', 12);
$pdf->SetXY(155, 43.5);
$pdf->Write(0, $invoice_data['invoice_number']);

// วันที่
$pdf->SetFont('THSarabunNew', '', 12);
$pdf->setXY(148, 49.5);
$pdf->Write(0, date('d/m/Y'));

$pdf->setXY(36, 44);
$pdf->Write(0, iconv('UTF-8', 'cp874',$invoice_data['fullname']));

$pdf->setXY(34, 49.8);
$pdf->Write(0, iconv('UTF-8', 'cp874', 'บ้านเลขที่ ' . $invoice_data['non_contact_address']));

// รายการ
$pdf->setXY(18, 73);
$pdf->Write(0, '1');

$pdf->setXY(27, 73);
$pdf->Write(0, iconv('UTF-8', 'cp874', $invoice_data['description']));

// จำนวน
$pdf->setXY(112, 73);
$pdf->Write(0, '1');

// ราคา
$total = $invoice_data['amount'] + ($invoice_data['penalty'] ?? 0);
$pdf->SetFont('THSarabunNew', 'B', 12);
$pdf->setXY(147, 73);
$pdf->Write(0, number_format($total, 2));

// จำนวนเงินรวม
$pdf->setXY(186, 73);
$pdf->Write(0, number_format($total, 2));

// ตัวอักษร
$pdf->setXY(95, 105);
$pdf->Write(0, iconv('UTF-8', 'cp874', convertNumberToThaiBaht($total)));

// แสดง PDF
$pdf->Output('invoice.pdf', 'I');

// เพิ่มฟังก์ชันนี้ก่อนการสร้าง PDF
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
    
    if ($numberStr[1] == '00') {
        $bahtText .= 'ถ้วน';
    } else {
        $value = intval($numberStr[1]);
        if ($value > 0) {
            $bahtText .= $digit[intval($numberStr[1][0])] . 'สิบ';
            if (isset($numberStr[1][1]) && $numberStr[1][1] > 0) {
                if ($numberStr[1][1] == '1') {
                    $bahtText .= 'เอ็ด';
                } else {
                    $bahtText .= $digit[intval($numberStr[1][1])];
                }
            }
            $bahtText .= 'สตางค์';
        }
    }
    
    return $bahtText;
}
