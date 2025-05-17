<?php
session_start();
require_once '../../config/config.php';
require_once '../../includes/auth.php';
include '../../components/menu/Menu.php';

// ตรวจสอบสิทธิ์การเข้าถึงหน้า dashboard
checkPageAccess(PAGE_PAYMENT);

// คำนวณยอดค้างชำระทั้งหมด
$stmt = $conn->prepare("
    SELECT COALESCE(SUM(p.amount + COALESCE(t.penalty, 0)), 0) as total_amount
    FROM payments p
    INNER JOIN transactions t ON p.payment_id = t.payment_id
    WHERE p.status = 'active'
    AND t.user_id = :user_id
    AND t.status = 'not_paid'
");

$stmt->execute(['user_id' => $_SESSION['user_id']]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);
$total_pending_amount = $result['total_amount'];

// เพิ่มการดึงข้อมูลเงินล่วงหน้าก่อนแสดงผล
$stmt = $conn->prepare("
    SELECT COALESCE(SUM(remaining_amount), 0) as total_advance_amount
    FROM advance_payments
    WHERE user_id = ? AND status = 'active'
");
$stmt->execute([$_SESSION['user_id']]);
$total_advance_amount = $stmt->fetchColumn();
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>| ระบบจัดการหมู่บ้าน </title>
    <link rel="icon" href="https://devcm.info/img/favicon.png">
    <link rel="stylesheet" href="../../src/style.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <style>
        @keyframes slideIn {
            from {
                transform: translateY(-100px);
                opacity: 0;
            }

            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .modal-content {
            animation: slideIn 0.3s ease-out;
        }

        .status-badge {
            transition: all 0.2s ease-in-out;
        }

        .status-badge:hover {
            transform: scale(1.05);
        }

        #paymentModal .modal-content {
            max-height: 90vh;
            overflow-y: auto;
        }

        @media (max-height: 800px) {
            #paymentModal>div {
                margin-top: 2rem;
                margin-bottom: 2rem;
            }
        }
    </style>
</head>

<body class="bg-modern">
    <div class="flex">
        <div id="sidebar" class="fixed top-0 left-0 h-full w-20 transition-all duration-300 ease-in-out bg-gradient-to-b from-blue-600 to-blue-500 shadow-xl">
            <!-- ย้ายปุ่ม toggle ไปด้านล่าง -->
            <button id="toggleSidebar" class="absolute -right-3 bottom-24 bg-blue-800 text-white rounded-full p-1 shadow-lg hover:bg-blue-900">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </button>
            <!-- Menu Section -->
            <?php renderMenu(); ?>
        </div>
    </div>

    <div class="flex-1 ml-20">
        <!-- Top Navigation -->
        <nav class="bg-white shadow-sm px-6 py-4">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">ชำระค่าส่วนกลาง</h1>
                    <p class="text-sm text-gray-500 mt-1">จัดการการชำระค่าส่วนกลางของคุณ</p>
                </div>
                <div class="flex items-center justify-end space-x-8">
                    <div class="text-right">
                        <p class="text-sm font-medium text-gray-600">ยอดค้างชำระทั้งหมด</p>
                        <p class="text-2xl font-bold text-red-600"><?php echo number_format($total_pending_amount, 2); ?></p>
                    </div>
                    <div class="text-right">
                        <p class="text-sm font-medium text-gray-600">จำนวนเงินที่ชำระล่วงหน้า</p>
                        <p class="text-2xl font-bold text-green-600"><?php echo number_format($total_advance_amount, 2); ?></p>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Payment Table Section -->
        <div class="p-6">
            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                <div class="p-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-800 mb-4">รายการค่าส่วนกลางทั้งหมด</h2>
                    <div class="mt-4 bg-blue-50 rounded-lg p-4 border border-blue-100">
                        <div class="flex items-center mb-3">
                            <div class="bg-blue-100 p-2 rounded-lg mr-3">
                                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                                </svg>
                            </div>
                            <h3 class="text-lg font-semibold text-blue-800">ข้อมูลบัญชีธนาคาร</h3>
                        </div>
                        <div class="space-y-2 pl-2">
                            <div class="flex items-center text-gray-700">
                                <span class="w-32 text-gray-500">ชื่อบัญชี</span>
                                <span class="font-medium">นิติบุคคลหมู่บ้านจัดสรร ทิพภิรมย์ 5</span>
                            </div>
                            <div class="flex items-center text-gray-700">
                                <span class="w-32 text-gray-500">ธนาคาร</span>
                                <span class="font-medium">กสิกรไทย</span>
                            </div>
                            <div class="flex items-center text-gray-700">
                                <span class="w-32 text-gray-500">เลขบัญชี</span>
                                <span class="font-medium tracking-wide">176-2-91114-7</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <!-- เพิ่ม class md:hidden สำหรับมือถือ -->
                    <div class="block lg:hidden">
                        <?php
                        // ดึงข้อมูลค่าส่วนกลางที่ active
                        // ดึงข้อมูลค่าส่วนกลางที่ active
                        $stmt = $conn->prepare("
    SELECT 
        p.*,
        t.status as payment_status,
        t.transaction_id,
        t.reject_reason,
        t.slip_image,
        t.created_at as payment_date,
        (p.amount + COALESCE(t.penalty, 0)) as total_amount,
        t.penalty,
        t.payment_type
    FROM payments p 
    INNER JOIN transactions t ON p.payment_id = t.payment_id
    WHERE p.status = 'active' 
    AND t.user_id = :user_id
    ORDER BY p.year DESC, p.month DESC
");

                        $stmt->execute([
                            'user_id' => $_SESSION['user_id']
                        ]);
                        $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        foreach ($payments as $index => $payment): ?>
                            <div class="bg-white rounded-lg shadow-sm mb-4 p-4">
                                <div class="space-y-3">
                                    <div class="flex justify-between">
                                        <span class="text-gray-500">เดือน ปี</span>
                                        <span class="font-medium"><?php echo str_pad($payment['month'], 2, '0', STR_PAD_LEFT) . '/' . ($payment['year']); ?></span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-500">รายละเอียด</span>
                                        <span class="font-medium truncate"><?php echo $payment['description']; ?></span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-500">จำนวนเงิน</span>
                                        <div class="text-right">
                                            <?php if ($payment['penalty'] > 0): ?>
                                                <div class="space-y-1">
                                                    <div class="text-sm">ค่าส่วนกลาง: <?php echo number_format($payment['amount'], 2); ?> บาท</div>
                                                    <div class="text-sm text-red-600">เบี้ยปรับ: <?php echo number_format($payment['penalty'], 2); ?> บาท</div>
                                                    <div class="text-sm font-medium pt-1 border-t">รวม: <?php echo number_format($payment['total_amount'], 2); ?> บาท</div>
                                                </div>
                                            <?php else: ?>
                                                <span class="font-medium"><?php echo number_format($payment['total_amount'], 2); ?> บาท</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="flex justify-between items-center">
                                        <span class="text-gray-500">สถานะ</span>
                                        <?php
                                        // ใช้โค้ดสถานะเดิม
                                        $status_class = '';
                                        $status_text = '';
                                        if (empty($payment['payment_status'])) {
                                            $status_class = 'bg-gray-100 text-gray-800';
                                            $status_text = 'ยังไม่ชำระ';
                                        } else if ($payment['payment_status'] == 'pending') {
                                            $status_class = 'bg-yellow-100 text-yellow-800';
                                            $status_text = 'รอตรวจสอบ';
                                        } else if ($payment['payment_status'] == 'approved') {
                                            $status_class = 'bg-green-100 text-green-800';
                                            $status_text = 'ชำระแล้ว';
                                            if ($payment['payment_type'] == 'advance') {
                                                $status_text = 'ชำระแล้ว (ล่วงหน้า)';
                                            }
                                        } else if ($payment['payment_status'] == 'rejected') {
                                            $status_class = 'bg-red-100 text-red-800';
                                            $status_text = 'ไม่อนุมัติ';
                                        }
                                        ?>
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                                    </div>
                                    <?php if ($payment['payment_status'] == 'rejected'): ?>
                                        <div class="flex justify-between">
                                            <span class="text-gray-500">เหตุผล</span>
                                            <span class="text-red-600"><?php echo htmlspecialchars($payment['reject_reason'] ?? '-'); ?></span>
                                        </div>
                                    <?php endif; ?>
                                    <div class="flex justify-end space-x-2 pt-2">
                                        <?php if ($payment['payment_status'] == 'not_paid' || $payment['payment_status'] == 'rejected'): ?>
                                            <button onclick="showPaymentForm(<?php echo $payment['payment_id']; ?>)" class="text-blue-600 hover:text-blue-900 bg-blue-100 rounded-md px-3 py-1">ชำระเงิน</button>
                                            <button onclick="printInvoice(<?php echo $_SESSION['user_id']; ?>, <?php echo $payment['payment_id']; ?>)" class="text-yellow-600 hover:text-yellow-900 bg-yellow-100 rounded-md px-3 py-1">พิมพ์ใบวางบิล</button>
                                        <?php elseif ($payment['payment_status'] == 'approved'): ?>
                                            <button onclick="showPaymentDetails(<?php echo $payment['payment_id']; ?>, '<?php echo $payment['slip_image']; ?>', '<?php echo $payment['payment_date']; ?>', <?php echo $payment['total_amount']; ?>)" class="text-blue-600 hover:text-blue-900 bg-blue-100 rounded-md px-3 py-1">ดูรายละเอียด</button>
                                            <button onclick="printReceipt(<?php echo $_SESSION['user_id']; ?>, <?php echo $payment['payment_id']; ?>, <?php echo $payment['transaction_id']; ?>)" class="text-green-600 hover:text-green-900 bg-green-100 rounded-md px-3 py-1">พิมพ์ใบเสร็จ</button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- ตารางสำหรับหน้าจอใหญ่ (เพิ่ม class hidden md:block) -->
                    <table class="min-w-full divide-y divide-gray-200 hidden lg:table">
                        <!-- ส่วนหัวตาราง -->
                        <thead class="bg-gray-50">
                            <tr>
                                <?php foreach (['ลำดับ', 'เดือน ปี', 'รายละเอียด', 'จำนวนเงิน (บาท)', 'สถานะ', 'เหตุผล', 'การกระทำ'] as $header): ?>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        <?php echo $header; ?>
                                    </th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <!-- ส่วนเนื้อหา -->
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php
                            // ดึงข้อมูลค่าส่วนกลางที่ active
                            // ดึงข้อมูลค่าส่วนกลางที่ active
                            $stmt = $conn->prepare("
    SELECT 
        p.*,
        t.status as payment_status,
        t.transaction_id,
        t.reject_reason,
        t.slip_image,
        t.created_at as payment_date,
        (p.amount + COALESCE(t.penalty, 0)) as total_amount,
        t.penalty,
        t.payment_type
    FROM payments p 
    INNER JOIN transactions t ON p.payment_id = t.payment_id
    WHERE p.status = 'active' 
    AND t.user_id = :user_id
    ORDER BY p.year DESC, p.month DESC
");

                            $stmt->execute([
                                'user_id' => $_SESSION['user_id']
                            ]);
                            $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
                            foreach ($payments as $index => $payment) {
                                echo "<tr class='hover:bg-gray-50 transition-colors duration-150'>";
                                echo "<td class='px-6 py-4 whitespace-nowrap text-sm text-gray-500'>" . ($index + 1) . "</td>";
                                echo "<td class='px-6 py-4 whitespace-nowrap text-sm text-gray-500'>";
                                echo str_pad($payment['month'], 2, '0', STR_PAD_LEFT) . '/' . ($payment['year']);
                                echo "</td>";
                                echo "<td class='px-6 py-4 text-sm text-gray-500'>" . $payment['description'] . "</td>";
                                echo "<td class='px-6 py-4 whitespace-nowrap'>";
                                if ($payment['penalty'] > 0) {
                                    echo "<div class='space-y-1'>";
                                    echo "<div class='text-sm text-gray-600'>ค่าส่วนกลาง: <span class='font-medium text-gray-900'>" . number_format($payment['amount'], 2) . " บาท</span></div>";
                                    echo "<div class='text-sm text-red-600'>เบี้ยปรับ: <span class='font-medium'>" . number_format($payment['penalty'], 2) . " บาท</span></div>";
                                    echo "<div class='text-sm font-medium text-gray-900 pt-1 border-t border-gray-200'>รวมทั้งสิ้น: <span class='font-semibold'>" . number_format($payment['total_amount'], 2) . " บาท</span></div>";
                                    echo "</div>";
                                } else {
                                    echo "<div class='text-sm font-medium text-gray-900'>" . number_format($payment['total_amount'], 2) . " บาท</div>";
                                }
                                echo "</td>";
                                echo "<td class='px-6 py-4 whitespace-nowrap'>";

                                // แสดงสถานะการชำระเงิน
                                $status_class = '';
                                $status_text = '';

                                if (empty($payment['payment_status'])) {
                                    $status_class = 'bg-gray-100 text-gray-800';
                                    $status_text = 'ยังไม่ชำระ';
                                } else if ($payment['payment_status'] == 'pending') {
                                    $status_class = 'bg-yellow-100 text-yellow-800';
                                    $status_text = 'รอตรวจสอบ';
                                } else if ($payment['payment_status'] == 'approved') {
                                    $status_class = 'bg-green-100 text-green-800';
                                    $status_text = 'ชำระแล้ว';
                                    if ($payment['payment_type'] == 'advance') {
                                        $status_text = ' ชำระแล้ว ( ล่วงหน้า )';
                                    }
                                } else if ($payment['payment_status'] == 'rejected') {
                                    $status_class = 'bg-red-100 text-red-800';
                                    $status_text = 'ไม่อนุมัติ';
                                }

                                echo "<span class='px-2 inline-flex text-xs leading-5 font-semibold rounded-full {$status_class}'>{$status_text}</span>";
                                echo "</td>";

                                echo "<td class='px-6 py-4 whitespace-nowrap text-sm text-gray-500'>";
                                if ($payment['payment_status'] == 'rejected') {
                                    echo htmlspecialchars($payment['reject_reason'] ?? '-');
                                } else {
                                    echo "-";
                                }
                                echo "</td>";

                                // ปุ่มดำเนินการ
                                echo "<td class='px-6 py-4 whitespace-nowrap text-sm font-medium'>";
                                if ($payment['payment_status'] == 'not_paid' || $payment['payment_status'] == 'rejected') {
                                    echo "<div class='space-x-2'>";
                                    echo "<button onclick='showPaymentForm({$payment['payment_id']})' class='text-blue-600 hover:text-blue-900 bg-blue-100 rounded-md px-3 py-1'>ชำระเงิน</button>";
                                    echo "<button onclick='printInvoice({$_SESSION['user_id']}, {$payment['payment_id']})' class='text-yellow-600 hover:text-yellow-900 bg-yellow-100 rounded-md px-3 py-1'>
                                        <svg class='w-4 h-4 inline-block mr-1' fill='none' stroke='currentColor' viewBox='0 0 24 24'>
                                            <path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z' />
                                        </svg>
                                        พิมพ์ใบวางบิล
                                    </button>";
                                    echo "</div>";
                                } else if ($payment['payment_status'] == 'approved') {
                                    echo "<div class='space-x-2'>";
                                    echo "<button onclick='showPaymentDetails({$payment['payment_id']}, \"{$payment['slip_image']}\", \"{$payment['payment_date']}\", {$payment['total_amount']})' class='text-blue-600 hover:text-blue-900 bg-blue-100 rounded-md px-3 py-1'>ดูรายละเอียด</button>";
                                    echo "<button onclick='printReceipt({$_SESSION['user_id']}, {$payment['payment_id']}, {$payment['transaction_id']})' class='text-green-600 hover:text-green-900 bg-green-100 rounded-md px-3 py-1'>
                                        <svg class='w-4 h-4 inline-block mr-1' fill='none' stroke='currentColor' viewBox='0 0 24 24'>
                                            <path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z' />
                                        </svg>
                                        พิมพ์ใบเสร็จ
                                    </button>";
                                    echo "</div>";
                                }

                                echo "</td>";
                                echo "</tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal สำหรับฟอร์มชำระเงิน -->
    <div id="paymentModal" class="hidden fixed inset-0 bg-black bg-opacity-50 overflow-y-auto h-full w-full z-50 flex items-center justify-center">
        <div class="relative mx-auto p-8 border w-[600px] shadow-2xl rounded-xl bg-white transform transition-all">
            <div class="absolute top-4 right-4">
                <button onclick="closePaymentModal()" class="text-gray-400 hover:text-gray-600 transition-colors duration-200">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div class="mt-3">
                <div class="flex items-center space-x-3 mb-6">
                    <div class="bg-blue-100 p-2 rounded-lg">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>
                    <h3 class="text-2xl font-semibold text-gray-800">แจ้งชำระเงิน</h3>
                </div>
                <form id="paymentForm" action="../../actions/payment/submit_payment.php" method="POST" enctype="multipart/form-data" class="space-y-6">
                    <input type="hidden" name="payment_id" id="payment_id">
                    <input type="hidden" name="transaction_id" id="transaction_id">

                    <div class="upload-area border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-blue-500 transition-colors duration-200">
                        <div class="space-y-2">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            <div class="flex text-sm text-gray-600">
                                <label for="slip_image" class="relative cursor-pointer bg-white rounded-md font-medium text-blue-600 hover:text-blue-500 focus-within:outline-none">
                                    <span>อัพโหลดสลิป</span>
                                    <input id="slip_image" name="slip_image" type="file" class="sr-only" required accept="image/*" onchange="previewImage(event)">
                                </label>
                                <p class="pl-1">หรือลากไฟล์มาวาง</p>
                            </div>
                            <p class="text-xs text-gray-500">PNG, JPG, GIF ไม่เกิน 10MB</p>
                        </div>
                        <div id="image_preview" class="mt-4 hidden">
                            <img src="" alt="Preview" class="mx-auto max-h-48 rounded-lg">
                        </div>
                    </div>

                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="closePaymentModal()" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-300 transition-colors duration-200">
                            ยกเลิก
                        </button>
                        <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors duration-200">
                            ยืนยันการชำระเงิน
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- เพิ่ม Modal แสดงรายละเอียดการชำระเงิน -->
    <div id="paymentDetailsModal" class="hidden fixed inset-0 bg-black bg-opacity-50 overflow-y-auto h-full w-full z-50 flex items-center justify-center">
        <div class="relative mx-auto p-8 border w-[600px] shadow-2xl rounded-xl bg-white">
            <div class="absolute top-4 right-4">
                <button onclick="closePaymentDetailsModal()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div class="mt-3">
                <div class="flex items-center space-x-3 mb-6">
                    <div class="bg-green-100 p-2 rounded-lg">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <h3 class="text-2xl font-semibold text-gray-800">รายละเอียดการชำระเงิน</h3>
                </div>

                <div class="space-y-4">
                    <div class="border rounded-lg p-4">
                        <p class="text-sm text-gray-600">วันที่ชำระ: <span id="paymentDate" class="text-gray-800"></span></p>
                        <p class="text-sm text-gray-600">จำนวนเงิน: <span id="paymentAmount" class="text-gray-800"></span> บาท</p>
                    </div>

                    <div class="mt-4">
                        <p class="text-sm text-gray-600 mb-2">สลิปการชำระเงิน:</p>
                        <img id="slipPreview" src="" alt="สลิปการชำระเงิน" class="max-w-full h-auto rounded-lg shadow-lg">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const sidebar = document.getElementById('sidebar');
        const toggleBtn = document.getElementById('toggleSidebar');
        const toggleIcon = toggleBtn.querySelector('svg path');
        const textElements = document.querySelectorAll('.opacity-0');
        let isExpanded = false;

        toggleBtn.addEventListener('click', () => {
            isExpanded = !isExpanded;
            if (isExpanded) {
                sidebar.classList.remove('w-20');
                sidebar.classList.add('w-64');
                toggleIcon.setAttribute('d', 'M15 19l-7-7 7-7'); // ลูกศรชี้ซ้าย
                textElements.forEach(el => el.classList.remove('opacity-0'));
            } else {
                sidebar.classList.remove('w-64');
                sidebar.classList.add('w-20');
                toggleIcon.setAttribute('d', 'M9 5l7 7-7 7'); // ลูกศรชี้ขวา
                textElements.forEach(el => el.classList.add('opacity-0'));
            }
        });

        // เิิ่ม JavaScript สำหรั Modal
        function showPaymentForm(paymentId, transactionId) {
            document.getElementById('payment_id').value = paymentId;
            if (transactionId) {
                document.getElementById('transaction_id').value = transactionId;
            }
            document.getElementById('paymentModal').classList.remove('hidden');
            document.body.style.overflow = 'hidden'; // ป้องกันการ scroll ของ background
        }

        function closePaymentModal() {
            document.getElementById('paymentModal').classList.add('hidden');
            document.body.style.overflow = ''; // คืนค่า scroll ให้ background
        }

        // เพิ่มฟังก์ชันสำหรับลด��นาดรู��และแปลงเป็น JPG ในส่วน JavaScript
        function resizeAndConvertToJpg(file) {
            return new Promise((resolve, reject) => {
                const reader = new FileReader();
                reader.readAsDataURL(file);
                reader.onload = function(e) {
                    const img = new Image();
                    img.src = e.target.result;
                    img.onload = function() {
                        const canvas = document.createElement('canvas');
                        const ctx = canvas.getContext('2d');

                        // กำหนดขนาดสูงสุดที่ต้องการ
                        const MAX_WIDTH = 1024;
                        const MAX_HEIGHT = 1024;

                        let width = img.width;
                        let height = img.height;

                        // คำนวณขนาดใหม่โดยรักษาอัตราส่วน
                        if (width > height) {
                            if (width > MAX_WIDTH) {
                                height *= MAX_WIDTH / width;
                                width = MAX_WIDTH;
                            }
                        } else {
                            if (height > MAX_HEIGHT) {
                                width *= MAX_HEIGHT / height;
                                height = MAX_HEIGHT;
                            }
                        }

                        canvas.width = width;
                        canvas.height = height;

                        // วาดรูปลงบน canvas ด้วยขนาดใหม่
                        ctx.drawImage(img, 0, 0, width, height);

                        // แปลงเป็น JPG
                        canvas.toBlob((blob) => {
                            resolve(new File([blob], 'image.jpg', {
                                type: 'image/jpeg'
                            }));
                        }, 'image/jpeg', 0.8); // quality 0.8 = 80%
                    };
                    img.onerror = reject;
                };
                reader.onerror = reject;
            });
        }

        // แก้ไขฟังก์ชัน handleDrop
        async function handleDrop(e) {
            const dt = e.dataTransfer;
            const file = dt.files[0];

            try {
                const resizedFile = await resizeAndConvertToJpg(file);
                const dataTransfer = new DataTransfer();
                dataTransfer.items.add(resizedFile);
                document.getElementById('slip_image').files = dataTransfer.files;
                previewImage({
                    target: {
                        files: dataTransfer.files
                    }
                });
            } catch (error) {
                console.error('Error resizing image:', error);
            }
        }

        // แก้ไขฟังก์ชัน previewImage
        async function previewImage(event) {
            const file = event.target.files[0];
            if (file) {
                try {
                    const resizedFile = await resizeAndConvertToJpg(file);
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const preview = document.getElementById('image_preview');
                        const img = preview.querySelector('img');
                        img.src = e.target.result;
                        preview.classList.remove('hidden');

                        // อัพเดทไฟล์ input ด้วยไฟล์ที่ถูกลดขนาดแล้ว
                        const dataTransfer = new DataTransfer();
                        dataTransfer.items.add(resizedFile);
                        document.getElementById('slip_image').files = dataTransfer.files;
                    }
                    reader.readAsDataURL(resizedFile);
                } catch (error) {
                    console.error('Error resizing image:', error);
                }
            }
        }

        // แก้ไข event listener สำหรับ file input
        document.getElementById('slip_image').addEventListener('change', function(e) {
            previewImage(e);
        });

        // เพิ่ม Drag and Drop support
        const uploadArea = document.querySelector('.upload-area');
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            uploadArea.addEventListener(eventName, preventDefaults, false);
        });

        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }

        ['dragenter', 'dragover'].forEach(eventName => {
            uploadArea.addEventListener(eventName, highlight, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            uploadArea.addEventListener(eventName, unhighlight, false);
        });

        function highlight(e) {
            uploadArea.classList.add('border-blue-500', 'bg-blue-50');
        }

        function unhighlight(e) {
            uploadArea.classList.remove('border-blue-500', 'bg-blue-50');
        }

        uploadArea.addEventListener('drop', handleDrop, false);

        function showPaymentDetails(paymentId, slipImage, paymentDate, amount) {
            const modal = document.getElementById('paymentDetailsModal');
            const slipPreview = document.getElementById('slipPreview');
            const paymentDateElement = document.getElementById('paymentDate');
            const paymentAmountElement = document.getElementById('paymentAmount');

            // แสดงรูปสลิป
            slipPreview.src = '../../uploads/slips/' + slipImage;

            // แสดงวันที่และจำนวนเงิน
            const formattedDate = new Date(paymentDate).toLocaleDateString('th-TH', {
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
            paymentDateElement.textContent = formattedDate;
            paymentAmountElement.textContent = amount.toLocaleString();

            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        function closePaymentDetailsModal() {
            const modal = document.getElementById('paymentDetailsModal');
            modal.classList.add('hidden');
            document.body.style.overflow = '';
        }

        function printInvoice(userId, paymentId) {
            window.open(`../../actions/payment/print_invoice.php?user_id=${userId}&payment_id=${paymentId}`, '_blank');
        }

        function printReceipt(userId, paymentId, transactionId) {
            window.open(`../../actions/payment/print_receipt.php?user_id=${userId}&payment_id=${paymentId}&transaction_id=${transactionId}`, '_blank');
        }
    </script>

    <?php include '../../components/footer/footer.php'; ?>
</body>

</html>