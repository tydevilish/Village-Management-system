<?php
session_start();
require_once '../../config/config.php';
require_once '../../includes/auth.php';
include '../../components/menu/Menu.php';

// ตรวจสอบสิทธิ์การเข้าถึงหน้า dashboard
checkPageAccess(PAGE_MANAGE_PAYMENT);

// เพิ่มโค้ดนี้ก่อนส่วนแสดงผล (ประมาณบรรทัด 80)
$stmt = $conn->prepare("
    SELECT 
        COUNT(*) as pending_users
    FROM (
        SELECT DISTINCT t.payment_id, t.user_id
        FROM transactions t
        WHERE t.status = 'pending'
    ) as unique_pending
");
$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_ASSOC);
$pending_users = $result['pending_users'];
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

        /* จัดแต่ง scrollbar */
        #paymentDetailsContent::-webkit-scrollbar {
            width: 8px;
        }

        #paymentDetailsContent::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }

        #paymentDetailsContent::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 4px;
        }

        #paymentDetailsContent::-webkit-scrollbar-thumb:hover {
            background: #666;
        }

        /* สำหรับ Firefox */
        #paymentDetailsContent {
            scrollbar-width: thin;
            scrollbar-color: #888 #f1f1f1;
        }

        @media (max-width: 640px) {
            #addPaymentModal .relative {
                top: 10px;
                margin-bottom: 20px;
            }

            #addPaymentModal form {
                padding: 0;
            }

            #addPaymentModal .max-h-40 {
                max-height: 30vh;
            }
        }

        /* ปรับ animation ให้ทำงานได้ดีบนมือถือ */
        @keyframes modalFadeIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        #addPaymentModal .relative {
            animation: modalFadeIn 0.3s ease-out;
        }
    </style>
</head>

<body class="bg-modern">
    <div class="flex">
        <div id="sidebar"
            class="fixed top-0 left-0 h-full w-20 transition-all duration-300 ease-in-out bg-gradient-to-b from-blue-600 to-blue-500 shadow-xl">
            <!-- ย้ายปุ่ม toggle ไปด้านล่าง -->
            <button id="toggleSidebar"
                class="absolute -right-3 bottom-24 bg-blue-800 text-white rounded-full p-1 shadow-lg hover:bg-blue-900">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </button>
            <!-- Menu Section -->
            <?php renderMenu(); ?>
        </div>
    </div>

    <div class="flex-1 ml-20">
        <!-- ปรับแต่ง Top Navigation ให้เหมือนกับ payment.php -->
        <nav class="bg-white shadow-sm px-6 py-4">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">จัดการค่าส่วนกลาง</h1>
                    <p class="text-sm text-gray-500 mt-1">จัดการการชำระค่าส่วนกลางของสมาชิก</p>
                </div>
                <div class="flex items-center space-x-4">
                    <div class="text-right">
                        <p class="text-sm font-medium text-gray-600">จำนวนผู้ที่รออนุมัติ</p>
                        <p class="text-2xl font-bold text-blue-600"><?php echo $pending_users; ?> คน</p>
                    </div>
                </div>
            </div>
        </nav>

        <!-- รับแ่งส่วนของตาราง -->
        <div class="p-6">
            <div class="flex items-center justify-between mb-6">
                <div class="w-full lg:w-auto">
                    <div class="flex flex-col lg:flex-row gap-4">
                        <div class="flex flex-wrap gap-2 items-center">
                            <!-- เดือน -->
                            <select id="monthFilter"
                                class="px-3 py-2 bg-white border border-gray-300 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">ทุกเดือน</option>
                                <?php for ($i = 1; $i <= 12; $i++): ?>
                                    <option value="<?= $i ?>"><?= str_pad($i, 2, '0', STR_PAD_LEFT) ?></option>
                                <?php endfor; ?>
                            </select>

                            <!-- ปี พ.ศ. -->
                            <select id="yearFilter"
                                class="px-3 py-2 bg-white border border-gray-300 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">ทุกปี</option>
                                <?php
                                $currentYear = (int) date('Y') + 543; // แปลงเป็น พ.ศ.
                                for ($i = $currentYear - 5; $i <= $currentYear + 1; $i++):
                                ?>
                                    <option value="<?= $i ?>"><?= $i ?></option>
                                <?php endfor; ?>
                            </select>

                            <!-- เพิ่มตัวเลือกการเรียงลำดับ -->
                            <select id="sortFilter"
                                class="px-3 py-2 bg-white border border-gray-300 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">การเรียงลำดับ</option>
                                <option value="unpaid_desc">ยังไม่ชำระมากที่สุด</option>
                                <option value="paid_desc">ชำระแล้วมากที่สุด</option>
                            </select>

                            <!-- ค้นหา -->
                            <div class="relative">
                                <input type="text" id="searchFilter" placeholder="ค้นหารายละเอียด..."
                                    class="pl-10 pr-4 py-2 bg-white border border-gray-300 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 w-full sm:w-auto">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="flex space-x-4">
                    <button onclick="showAddPaymentModal()"
                        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center space-x-2 transition-all duration-200">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        <span>เพิ่มค่าส่วนกลาง</span>
                    </button>

                    <button onclick="showAdvancePaymentModal()"
                        class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg flex items-center space-x-2 transition-all duration-200">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>เพิ่มเงินล่วงหน้า</span>
                    </button>

                    <button type="button"
                        onclick="showUpdateStatusModal()"
                        class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded">
                        <i class="fas fa-sync mr-1"></i>อัพเดทสถานะการชำระเงิน
                    </button>

                    <!-- เพิ่มปุ่มหลังปุ่มอัพเดทสถานะการชำระเงิน -->
                    <button type="button"
                        onclick="showUpdateReceiptModal()"
                        class="bg-purple-500 hover:bg-purple-600 text-white px-4 py-2 rounded">
                        <i class="fas fa-receipt mr-1"></i>แก้ไขเลขที่ใบเสร็จ
                    </button>

                    <button type="button"
                        onclick="showUpdateInvoiceModal()"
                        class="bg-pink-500 hover:bg-pink-600 text-white px-4 py-2 rounded">
                        <i class="fas fa-file-invoice mr-1"></i>แก้ไขเลขที่ใบวางบิล
                    </button>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                <!-- ตารางยังคงเหมือนเดิม แต่ปรับ style ให้เข้ากับ theme -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col"
                                    class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    ลำดับ</th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    เดือน ปี</th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    รายละเอียด</th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    จำนวนเงิน (บาท)</th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    สถานะ</th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    การกระทำ</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php
                            // ดึงข้อมูลค่าส่วนกลางทั้งหมด
                            $stmt = $conn->prepare("
                                SELECT p.*, 
                                    COUNT(DISTINCT CASE WHEN t.status = 'approved' THEN t.user_id END) as total_paid,
                                    COUNT(DISTINCT CASE WHEN t.status = 'pending' THEN t.user_id END) as total_pending,
                                    COUNT(DISTINCT CASE WHEN t.status = 'rejected' THEN t.user_id END) as total_rejected,
                                    COUNT(DISTINCT t.user_id) as total_assigned_users,
                                    (SELECT COUNT(DISTINCT u.user_id) 
                                     FROM users u 
                                     LEFT JOIN transactions t2 ON u.user_id = t2.user_id AND t2.payment_id = p.payment_id 
                                     WHERE t2.status = 'not_paid' OR t2.status IS NULL) as total_unpaid
                                FROM payments p
                                LEFT JOIN transactions t ON p.payment_id = t.payment_id
                                WHERE p.status = 'active'
                                GROUP BY p.payment_id
                                ORDER BY p.year DESC, p.month DESC
                            ");
                            $stmt->execute();
                            $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

                            foreach ($payments as $payment) {
                                echo "<tr>";
                                echo "<td class='px-6 py-4 whitespace-nowrap text-sm text-gray-500'>" . $payment['payment_id'] . "</td>";
                                echo "<td class='px-6 py-4 whitespace-nowrap text-sm text-gray-500'>" .
                                    sprintf("%02d/%04d", $payment['month'], $payment['year']) .
                                    "</td>";
                                echo "<td class='px-6 py-4 text-sm text-gray-500'>" . $payment['description'] . "</td>";
                                echo "<td class='px-6 py-4 whitespace-nowrap text-sm text-gray-500'>" . number_format($payment['amount'], 2) . "</td>";
                                echo "<td class='px-6 py-4 whitespace-nowrap'>";
                                echo "<div class='flex flex-col space-y-1'>";
                                // แสดงจำนวนคนที่ชำระแล้ว
                                echo "<div class='flex items-center space-x-2'>";
                                echo "<span class='inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800'>";
                                echo "<svg class='w-3 h-3 mr-1' fill='currentColor' viewBox='0 0 20 20'>";
                                echo "<path fill-rule='evenodd' d='M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z' clip-rule='evenodd'/>";
                                echo "</svg>";
                                echo "ชำระแล้ว {$payment['total_paid']}/{$payment['total_assigned_users']}";
                                echo "</span>";
                                echo "</div>";

                                // แสดงจำนวนคนที่รออนุมัติ (ถ้ามี)
                                if ($payment['total_pending'] > 0) {
                                    echo "<div class='flex items-center space-x-2'>";
                                    echo "<span class='inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800'>";
                                    echo "<svg class='w-3 h-3 mr-1' fill='currentColor' viewBox='0 0 20 20'>";
                                    echo "<path fill-rule='evenodd' d='M10 18a8 8 0 100-16 8 8 0 000 16zm1-8.414l2.293-2.293a1 1 0 011.414 1.414L11.414 12l3.293 3.293a1 1 0 01-1.414 1.414L10 13.414l-3.293 3.293a1 1 0 01-1.414-1.414L8.586 12 5.293 8.707a1 1 0 011.414-1.414L10 10.586z' clip-rule='evenodd'/>";
                                    echo "</svg>";
                                    echo "รออนุมัติ {$payment['total_pending']}";
                                    echo "</span>";
                                    echo "</div>";
                                }

                                // แสดงจำนวนคนที่ยังไม่ชำระ (ไม่รวมคนที่รออนุมัติ)
                                $real_unpaid = $payment['total_assigned_users'] - $payment['total_paid'] - $payment['total_pending'];
                                if ($real_unpaid > 0) {
                                    echo "<div class='flex items-center space-x-2'>";
                                    echo "<span class='inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800'>";
                                    echo "<svg class='w-3 h-3 mr-1' fill='currentColor' viewBox='0 0 20 20'>";
                                    echo "<path fill-rule='evenodd' d='M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z' clip-rule='evenodd'/>";
                                    echo "</svg>";
                                    echo "ยังไม่ชำระ {$real_unpaid}";
                                    echo "</span>";
                                    echo "</div>";
                                }

                                if ($payment['total_rejected'] > 0) {
                                    echo "<div class='flex items-center space-x-2'>";
                                    echo "<span class='inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800'>";
                                    echo "<svg class='w-3 h-3 mr-1' fill='currentColor' viewBox='0 0 20 20'>";
                                    echo "<path fill-rule='evenodd' d='M10 18a8 8 0 100-16 8 8 0 000 16zm1-8.414l2.293-2.293a1 1 0 011.414 1.414L11.414 12l3.293 3.293a1 1 0 01-1.414 1.414L10 13.414l-3.293 3.293a1 1 0 01-1.414-1.414L8.586 12 5.293 8.707a1 1 0 011.414-1.414L10 10.586z' clip-rule='evenodd'/>";
                                    echo "</svg>";
                                    echo "ไม่อนุมัติ {$payment['total_rejected']}";
                                    echo "</span>";
                                    echo "</div>";
                                }
                                echo "</div>";
                                echo "</td>";
                                echo "<td class='px-6 py-4 whitespace-nowrap text-sm font-medium'>";
                                echo "<button onclick='viewPaymentDetails({$payment['payment_id']})' class='inline-flex items-center px-3 py-1.5 bg-blue-100 text-blue-700 rounded-md hover:bg-blue-200 transition-colors mr-3'>
                                <svg class='w-4 h-4 mr-1' fill='none' stroke='currentColor' viewBox='0 0 24 24'>
                                    <path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M15 12a3 3 0 11-6 0 3 3 0 016 0z'/>
                                    <path stroke-linecap='ound' stroke-linejoin='round' stroke-width='2' d='M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z'/>
                                </svg>
                                       ดูรายละเอียด
                                </button>";
                                if ($_SESSION['role_id'] == 1) {
                                    echo "<button onclick='deletePayment({$payment['payment_id']})' class='text-red-600 hover:text-red-900 bg-red-100 rounded-md px-3 py-1'>ลบ</button>";
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

    <!-- ปรบแ่ง Modal เพิ่มค่าส่วนกลาง -->
    <div id="addPaymentModal" class="hidden fixed inset-0 bg-black bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative min-h-screen flex items-center justify-center p-4">
            <div class="relative w-full max-w-2xl bg-white rounded-lg shadow-2xl mx-auto">
                <!-- ส่วนหัว Modal -->
                <div class="bg-gradient-to-r from-blue-600 to-blue-700 rounded-t-lg">
                    <div class="flex justify-between items-center p-6">
                        <h3 class="text-2xl font-semibold text-white">เพิ่มค่าสวนกลาง</h3>
                        <button onclick="closeAddPaymentModal()"
                            class="text-white hover:text-gray-200 transition-colors">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- ส่วนเนื้อหา Modal -->
                <div class="p-6">
                    <form id="addPaymentForm" action="../../actions/payment/add_payment.php" method="POST">
                        <div class="space-y-4">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div class="relative">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">เดือน</label>
                                    <select name="month" required
                                        class="w-full p-2 rounded-lg border-2 border-gray-200 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 transition-all duration-200 bg-gray-50 hover:bg-white">
                                        <?php for ($i = 1; $i <= 12; $i++): ?>
                                            <option value="<?php echo $i; ?>" <?php echo $i == date('n') ? 'selected' : ''; ?>>
                                                <?php echo str_pad($i, 2, '0', STR_PAD_LEFT); ?>
                                            </option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                                <div class="relative">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">ปี</label>
                                    <select name="year" required
                                        class="w-full p-2 rounded-lg border-2 border-gray-200 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 transition-all duration-200 bg-gray-50 hover:bg-white">
                                        <?php
                                        $currentYear = (int) date('Y');
                                        for ($i = $currentYear - 1; $i <= $currentYear + 1; $i++):
                                        ?>
                                            <option value="<?php echo $i; ?>" <?php echo $i == $currentYear ? 'selected' : ''; ?>>
                                                <?php echo $i + 543; ?>
                                            </option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="relative">
                                <label class="block text-sm font-medium text-gray-700 mb-1">รายละเอียด</label>
                                <textarea name="description" required
                                    class="w-full p-2 rounded-lg border-2 border-gray-200 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 transition-all duration-200 bg-gray-50 hover:bg-white"></textarea>
                            </div>

                            <div class="relative">
                                <label class="block text-sm font-medium text-gray-700 mb-1">จำนวนเงิน (บาท)</label>
                                <input type="number" name="amount" step="0.01" required
                                    class="w-full p-2 rounded-lg border-2 border-gray-200 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 transition-all duration-200 bg-gray-50 hover:bg-white">
                            </div>

                            <div class="relative">
                                <label class="block text-sm font-medium text-gray-700 mb-1">เลือกผู้ใช้</label>
                                <div class="mb-2">
                                    <input type="text" id="userSearch" placeholder="ค้นหาผู้���ช้..."
                                        class="w-full p-2 rounded-lg border-2 border-gray-200 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 transition-all duration-200 bg-gray-50 hover:bg-white">
                                </div>
                                <div class="flex items-center mb-2">
                                    <input type="checkbox" id="selectAll"
                                        class="mr-2 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                    <label for="selectAll" class="text-sm text-gray-700">เลือกทั้งหมด</label>
                                </div>
                                <div id="userList"
                                    class="max-h-40 overflow-y-auto border-2 border-gray-200 rounded-lg p-2 bg-gray-50">
                                    <?php
                                    // ดึงข้อมูลผู้ใช้ที่เป็นลูกบ้าน
                                    $stmt = $conn->prepare("SELECT user_id, username, fullname FROM users WHERE role_id = 2 ORDER BY username");
                                    $stmt->execute();
                                    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

                                    foreach ($users as $user):
                                    ?>
                                        <div class="user-item flex items-center space-x-2 p-1">
                                            <input type="checkbox" name="selected_users[]" value="<?= $user['user_id'] ?>"
                                                class="user-checkbox rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                            <label class="text-sm text-gray-700">
                                                <?= htmlspecialchars($user['username']) ?> -
                                                <?= htmlspecialchars($user['fullname'] ?? '') ?>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>

                        <div
                            class="flex flex-col sm:flex-row justify-end space-y-3 sm:space-y-0 sm:space-x-4 mt-6 pt-6 border-t">
                            <button type="button" onclick="closeAddPaymentModal()"
                                class="px-6 py-2.5 text-sm font-medium text-gray-700 bg-white border-2 border-gray-300 rounded-lg hover:bg-gray-50 hover:border-gray-400 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200">
                                ยกเลิก
                            </button>
                            <button type="submit"
                                class="px-6 py-2.5 text-sm font-medium text-white bg-gradient-to-r from-blue-600 to-blue-700 rounded-lg hover:from-blue-700 hover:to-blue-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200 shadow-lg">
                                บันทึก
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div id="paymentDetailsModal"
        class="hidden fixed inset-0 bg-black bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative min-h-screen flex items-center justify-center p-4">
            <div class="relative w-full max-w-[90%] bg-white rounded-lg shadow-2xl mx-auto">
                <!-- ส่วนหัว Modal -->
                <div class="bg-gradient-to-r from-blue-600 to-blue-700 rounded-t-lg">
                    <div class="flex justify-between items-center p-6">
                        <h3 class="text-2xl font-semibold text-white">รายละเอียดการชำระเงิน</h3>
                        <div class="flex items-center space-x-5">
                            <!-- เพิ่มปุ่ม Import หลังปุ่มเพิ่มค่าส่วนกลาง -->
                            <button onclick="showImportModal()"
                                class="ml-2 bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg flex items-center space-x-2 transition-all duration-200">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3 3m0 0l-3-3m3 3V8" />
                                </svg>
                                <span>นำเข้าไฟล์ XLSX , CSV</span>
                            </button>

                            <a class=" bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg flex items-center space-x-2 transition-all duration-200" href="../../actions/payment/export_csv_xlsx.php" class="btn btn-success">
                                <i class="fas fa-file-excel mr-2"></i> Export XLSX
                            </a>

                            <button onclick="closePaymentDetailsModal()"
                                class="text-white hover:text-gray-200 transition-colors">
                                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- ส่วนเนื้อหา Modal -->
                <div id="paymentDetailsContent" class="p-8 max-h-[70vh] overflow-y-auto">
                    <!-- เนื้อหาจะถูกโหลดที่นี่ -->
                </div>
            </div>

        </div>
    </div>

    <!-- เพิ่ม Modal Import CSV -->
    <div id="importModal" class="hidden fixed inset-0 bg-black bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative min-h-screen flex items-center justify-center p-4">
            <div class="relative w-full max-w-md bg-white rounded-lg shadow-2xl mx-auto">
                <div class="bg-gradient-to-r from-blue-600 to-blue-700 rounded-t-lg">
                    <div class="flex justify-between items-center p-6">
                        <h3 class="text-2xl font-semibold text-white">นำเข้าไฟล์ XLSX , CSV</h3>
                        <button onclick="closeImportModal()" class="text-white hover:text-gray-200 transition-colors">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>
                <div class="p-6">
                    <form id="importForm" action="../../actions/payment/import_csv_xlsx.php" method="POST"
                        enctype="multipart/form-data">
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">เลือกไฟล์ XLSX , CSV</label>
                            <input type="file" name="xlsx_file" accept=".xlsx,.csv" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div class="text-sm text-gray-600 mb-4">
                            <p class="mb-2">ดาวน์โหลดไฟล์ตัวอย่างได้ที่นี่:</p>
                            <a href="../../src/example.xlsx"
                                download
                                class="inline-flex items-center px-4 py-2 bg-green-100 text-green-700 rounded-md hover:bg-green-200 transition-all duration-200">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                </svg>
                                ดาวน์โหลดไฟล์ตัวอย่าง
                            </a>
                        </div>
                        <div class="flex justify-end space-x-3">
                            <button type="button" onclick="closeImportModal()"
                                class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                                ยกเลิก
                            </button>
                            <button type="submit"
                                class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700">
                                Import
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>


    <!-- เพิ่ม Modal สำหรับอัพโหลดหลักฐาน -->
    <div id="uploadSlipModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
        <div class="bg-white rounded-lg p-6 w-96">
            <h3 class="text-lg font-semibold mb-4">อัพโหลดหลักฐานกาชำระเงิน</h3>
            <form id="uploadSlipForm" enctype="multipart/form-data">
                <input type="hidden" id="transactionId" name="transaction_id">
                <input type="file" name="slip_image" accept="image/*" required class="w-full p-2 border rounded mb-4">
                <div class="flex justify-end space-x-2">
                    <button type="button" onclick="closeUploadModal()"
                        class="px-4 py-2 text-gray-600 bg-gray-100 rounded hover:bg-gray-200">
                        ยกเลิก
                    </button>
                    <button type="submit" class="px-4 py-2 text-white bg-blue-600 rounded hover:bg-blue-700">
                        อัพโหลด
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal ตั้งเบี้ยปรับ -->
    <div id="penaltyModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-xl w-full max-w-md">
                <div class="p-6">
                    <h3 class="text-lg font-semibold mb-4">ตั้งเบี้ยปรับ</h3>
                    <form id="penaltyForm" onsubmit="submitPenalty(event)">
                        <input type="hidden" id="penaltyUserId">
                        <input type="hidden" id="penaltyPaymentId">

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                จำนวนเงินเบี้ยปรับ (บาท)
                            </label>
                            <input type="number"
                                id="penaltyAmount"
                                step="0.01"
                                min="0"
                                class="w-full p-2 border rounded-lg focus:ring-blue-500 focus:border-blue-500"
                                required>
                        </div>

                        <div class="flex justify-end space-x-3">
                            <button type="button"
                                onclick="closePenaltyModal()"
                                class="px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200">
                                ยกเลิก
                            </button>
                            <button type="submit"
                                class="px-4 py-2 text-white bg-blue-600 rounded-lg hover:bg-blue-700">
                                บันทึก
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- เพิ่ม Modal เงินล่วงหน้าต่อจาก Modal อื่นๆ -->
    <div id="advancePaymentModal" class="hidden fixed inset-0 bg-black bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative min-h-screen flex items-center justify-center p-4">
            <div class="relative w-full max-w-2xl bg-white rounded-lg shadow-2xl mx-auto">
                <!-- ส่วนหัว Modal -->
                <div class="bg-gradient-to-r from-green-600 to-green-700 rounded-t-lg">
                    <div class="flex justify-between items-center p-6">
                        <h3 class="text-2xl font-semibold text-white">เพิ่มเงินล่วงหน้า</h3>
                        <button onclick="closeAdvancePaymentModal()"
                            class="text-white hover:text-gray-200 transition-colors">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- ส่วนเนื้อหา Modal -->
                <div class="p-6">
                    <form id="advancePaymentForm" action="../../actions/payment/add_advance_payment.php" method="POST">
                        <div class="space-y-4">
                            <div class="relative">
                                <label class="block text-sm font-medium text-gray-700 mb-1">จำนวนเงิน (บาท)</label>
                                <input type="number" name="amount" step="0.01" required
                                    class="w-full p-2 rounded-lg border-2 border-gray-200 shadow-sm focus:border-green-500 focus:ring focus:ring-green-200 transition-all duration-200 bg-gray-50 hover:bg-white">
                            </div>

                            <div class="relative">
                                <label class="block text-sm font-medium text-gray-700 mb-1">เลือกผู้ใช้</label>
                                <div class="mb-2">
                                    <input type="text" id="advanceUserSearch" placeholder="ค้นหาผู้ใช้..."
                                        class="w-full p-2 rounded-lg border-2 border-gray-200 shadow-sm focus:border-green-500 focus:ring focus:ring-green-200 transition-all duration-200 bg-gray-50 hover:bg-white">
                                </div>
                                <div id="advanceUserList"
                                    class="max-h-40 overflow-y-auto border-2 border-gray-200 rounded-lg p-2 bg-gray-50">
                                    <?php
                                    // ดึงข้อมูลผู้ใช้ที่เป็นลูกบ้าน
                                    $stmt = $conn->prepare("SELECT user_id, username, fullname FROM users WHERE role_id = 2 ORDER BY username");
                                    $stmt->execute();
                                    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

                                    foreach ($users as $user):
                                    ?>
                                        <div class="user-item flex items-center space-x-2 p-1">
                                            <input type="radio" name="user_id" value="<?= $user['user_id'] ?>" required
                                                class="rounded-full border-gray-300 text-green-600 focus:ring-green-500">
                                            <label class="text-sm text-gray-700">
                                                <?= htmlspecialchars($user['username']) ?> -
                                                <?= htmlspecialchars($user['fullname'] ?? '') ?>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>

                        <!-- ปุ่มกดด้านล่าง -->
                        <div class="flex justify-end space-x-4 mt-6 pt-6 border-t">
                            <button type="button" onclick="closeAdvancePaymentModal()"
                                class="px-6 py-2.5 text-sm font-medium text-gray-700 bg-white border-2 border-gray-300 rounded-lg hover:bg-gray-50 hover:border-gray-400 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-all duration-200">
                                ยกเลิก
                            </button>
                            <button type="submit"
                                class="px-6 py-2.5 text-sm font-medium text-white bg-gradient-to-r from-green-600 to-green-700 rounded-lg hover:from-green-700 hover:to-green-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-all duration-200 shadow-lg">
                                บันทึก
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal อัพเดทสถานะการชำระเงิน -->
    <div id="updateStatusModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 overflow-y-auto">
        <div class="relative min-h-screen flex items-center justify-center p-4">
            <div class="relative w-full max-w-2xl bg-white rounded-lg shadow-2xl mx-auto">
                <!-- ส่วนหัว Modal -->
                <div class="bg-gradient-to-r from-yellow-600 to-yellow-700 rounded-t-lg">
                    <div class="flex justify-between items-center p-6">
                        <h3 class="text-2xl font-semibold text-white">อัพเดทสถานะการชำระเงิน</h3>
                        <button onclick="hideUpdateStatusModal()"
                            class="text-white hover:text-gray-200 transition-colors">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- ส่วนเนื้อหา Modal -->
                <div class="p-6">
                    <form id="updateStatusForm" action="../../actions/payment/update_individual_status.php" method="POST">
                        <div class="space-y-6">
                            <!-- ส่วนค้นหาผู้ใช้ -->
                            <div class="relative">
                                <label class="block text-sm font-medium text-gray-700 mb-1">เลือกผู้ใช้</label>
                                <input type="text" id="statusUserSearch" placeholder="ค้นหาผู้ใช้..."
                                    class="w-full p-2 mb-2 rounded-lg border-2 border-gray-200 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 transition-all duration-200 bg-gray-50 hover:bg-white">
                                <div id="statusUserList"
                                    class="max-h-40 overflow-y-auto border-2 border-gray-200 rounded-lg p-2 bg-gray-50">
                                    <?php
                                    $stmt = $conn->prepare("SELECT user_id, username, fullname FROM users WHERE role_id = 2 ORDER BY username");
                                    $stmt->execute();
                                    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                    foreach ($users as $user):
                                    ?>
                                        <div class="user-item flex items-center space-x-2 p-2 hover:bg-gray-100 rounded transition-colors">
                                            <input type="radio" name="user_id" value="<?= $user['user_id'] ?>" required
                                                class="rounded-full border-gray-300 text-blue-600 focus:ring-blue-500">
                                            <label class="text-sm text-gray-700 cursor-pointer flex-grow">
                                                <?= htmlspecialchars($user['username']) ?> -
                                                <?= htmlspecialchars($user['fullname'] ?? '') ?>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <!-- ส่วนเลือกรายการชำระเงิน -->
                            <div class="relative">
                                <label class="block text-sm font-medium text-gray-700 mb-1">เลือกรายการชำระเงิน</label>
                                <select name="payment_id" class="w-full p-2 rounded-lg border-2 border-gray-200 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 transition-all duration-200 bg-gray-50 hover:bg-white" required>
                                    <?php
                                    $stmt = $conn->query("SELECT payment_id, description, month, year FROM payments ORDER BY year DESC, month DESC");
                                    while ($payment = $stmt->fetch()) {
                                        echo "<option value='{$payment['payment_id']}'>{$payment['description']} ({$payment['month']}/{$payment['year']})</option>";
                                    }
                                    ?>
                                </select>
                            </div>

                            <!-- ส่วนเลือกสถานะ -->
                            <div class="relative">
                                <label class="block text-sm font-medium text-gray-700 mb-1">สถานะ</label>
                                <select name="status" id="statusSelect"
                                    class="w-full p-2 rounded-lg border-2 border-gray-200 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 transition-all duration-200 bg-gray-50 hover:bg-white"
                                    required onchange="toggleRejectReason(this.value)">
                                    <option value="approved">ชำระแล้ว</option>
                                    <option value="pending">รอตรวจสอบ</option>
                                    <option value="rejected">ไม่อนุมัติ</option>
                                </select>
                            </div>

                            <!-- ส่วนเหตุผลการไม่อนุมัติ -->
                            <div id="rejectReasonField" class="hidden">
                                <label class="block text-sm font-medium text-gray-700 mb-1">เหตุผลที่ไม่อนุมัติ</label>
                                <textarea name="reject_reason" rows="3" placeholder="ระบุเหตุผลที่ไม่อนุมัติ"
                                    class="w-full p-2 rounded-lg border-2 border-gray-200 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 transition-all duration-200 bg-gray-50 hover:bg-white"></textarea>
                            </div>
                        </div>

                        <!-- ส่วนปุ่มด้านล่าง -->
                        <div class="flex justify-end space-x-4 mt-6 pt-6 border-t">
                            <button type="button" onclick="hideUpdateStatusModal()"
                                class="px-6 py-2.5 text-sm font-medium text-gray-700 bg-white border-2 border-gray-300 rounded-lg hover:bg-gray-50 hover:border-gray-400 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200">
                                ยกเลิก
                            </button>
                            <button type="submit"
                                class="px-6 py-2.5 text-sm font-medium text-white bg-gradient-to-r from-yellow-600 to-yellow-700 rounded-lg hover:from-yellow-700 hover:to-yellow-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500 transition-all duration-200 shadow-lg">
                                บันทึก
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- เพิ่ม Modal แก้ไขเลขที่ใบเสร็จ -->
    <div id="updateReceiptModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
        <div class="relative min-h-screen flex items-center justify-center p-4">
            <div class="relative w-full max-w-2xl bg-white rounded-lg shadow-2xl mx-auto">
                <div class="bg-gradient-to-r from-purple-600 to-purple-700 rounded-t-lg">
                    <div class="flex justify-between items-center p-6">
                        <h3 class="text-2xl font-semibold text-white">แก้ไขเลขที่ใบเสร็จ</h3>
                        <button onclick="hideUpdateReceiptModal()" class="text-white hover:text-gray-200">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="p-6">
                    <form id="updateReceiptForm" onsubmit="submitUpdateReceipt(event)">
                        <div class="space-y-4">
                            <!-- เลือกเดือน -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">เดือน</label>
                                <select name="month" required class="w-full p-2 border rounded">
                                    <?php for ($i = 1; $i <= 12; $i++): ?>
                                        <option value="<?= $i ?>"><?= str_pad($i, 2, '0', STR_PAD_LEFT) ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>

                            <!-- เลือกปี -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">ปี</label>
                                <select name="year" required class="w-full p-2 border rounded">
                                    <?php 
                                    $currentYear = (int)date('Y');
                                    for ($i = $currentYear - 5; $i <= $currentYear + 1; $i++):
                                    ?>
                                        <option value="<?= $i ?>"><?= $i + 543 ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>

                            <!-- ค้นหาผู้ใช้ -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">เลือกผู้ใช้</label>
                                <input type="text" name="user_id" id="receiptUserSearch" placeholder="ค้นหาผู้ใช้..." 
                                       class="w-full p-2 border rounded mb-2">
                                <div class="max-h-40 overflow-y-auto border rounded p-2">
                                    <?php foreach ($users as $user): ?>
                                        <div class="user-item flex items-center p-2 hover:bg-gray-100">
                                            <input type="radio" name="user_id" value="<?= $user['user_id'] ?>" required
                                                   class="mr-2">
                                            <label><?= htmlspecialchars($user['username']) ?> - 
                                                   <?= htmlspecialchars($user['fullname']) ?></label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <!-- เลขที่ใบเสร็จ -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">เลขที่ใบเสร็จ</label>
                                <input type="text" name="receipt_number" required 
                                       class="w-full p-2 border rounded">
                            </div>
                        </div>

                        <div class="flex justify-end space-x-4 mt-6">
                            <button type="button" onclick="hideUpdateReceiptModal()"
                                    class="px-4 py-2 text-gray-700 bg-gray-100 rounded">
                                ยกเลิก
                            </button>
                            <button type="submit"
                                    class="px-4 py-2 text-white bg-purple-600 rounded hover:bg-purple-700">
                                บันทึก
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- เพิ่ม Modal แก้ไขเลขที่ใบวางบิล -->
    <div id="updateInvoiceModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
        <div class="relative min-h-screen flex items-center justify-center p-4">
            <div class="relative w-full max-w-2xl bg-white rounded-lg shadow-2xl mx-auto">
                <div class="bg-gradient-to-r from-pink-600 to-pink-700 rounded-t-lg">
                    <div class="flex justify-between items-center p-6">
                        <h3 class="text-2xl font-semibold text-white">แก้ไขเลขที่ใบวางบิล</h3>
                        <button onclick="hideUpdateInvoiceModal()" class="text-white hover:text-gray-200">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="p-6">
                    <form id="updateInvoiceForm" onsubmit="submitUpdateInvoice(event)">
                        <!-- เนื้อหาเหมือนกับ updateReceiptForm แต่เปลี่ยนชื่อฟิลด์ -->
                        <div class="space-y-4">
                            <!-- เลือกเดือน -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">เดือน</label>
                                <select name="month" required class="w-full p-2 border rounded">
                                    <?php for ($i = 1; $i <= 12; $i++): ?>
                                        <option value="<?= $i ?>"><?= str_pad($i, 2, '0', STR_PAD_LEFT) ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>

                            <!-- เลือกปี -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">ปี</label>
                                <select name="year" required class="w-full p-2 border rounded">
                                    <?php 
                                    $currentYear = (int)date('Y');
                                    for ($i = $currentYear - 5; $i <= $currentYear + 1; $i++):
                                    ?>
                                        <option value="<?= $i ?>"><?= $i + 543 ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>

                            <!-- ค้นหาผู้ใช้ -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">เลือกผู้ใช้</label>
                                <input type="text" id="invoiceUserSearch" placeholder="ค้นหาผู้ใช้..." 
                                       class="w-full p-2 border rounded mb-2">
                                <div class="max-h-40 overflow-y-auto border rounded p-2">
                                    <?php foreach ($users as $user): ?>
                                        <div class="user-item flex items-center p-2 hover:bg-gray-100">
                                            <input type="radio" name="user_id" value="<?= $user['user_id'] ?>" required
                                                   class="mr-2">
                                            <label><?= htmlspecialchars($user['username']) ?> - 
                                                   <?= htmlspecialchars($user['fullname']) ?></label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <!-- เลขที่ใบวางบิล -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">เลขที่ใบวางบิล</label>
                                <input type="text" name="invoice_number" required 
                                       class="w-full p-2 border rounded">
                            </div>
                        </div>

                        <div class="flex justify-end space-x-4 mt-6">
                            <button type="button" onclick="hideUpdateInvoiceModal()"
                                    class="px-4 py-2 text-gray-700 bg-gray-100 rounded">
                                ยกเลิก
                            </button>
                            <button type="submit"
                                    class="px-4 py-2 text-white bg-pink-600 rounded hover:bg-pink-700">
                                บันทึก
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function setPenalty(userId, paymentId, currentPenalty) {
            document.getElementById('penaltyUserId').value = userId;
            document.getElementById('penaltyPaymentId').value = paymentId;
            document.getElementById('penaltyAmount').value = currentPenalty;
            document.getElementById('penaltyModal').classList.remove('hidden');
        }

        function closePenaltyModal() {
            document.getElementById('penaltyModal').classList.add('hidden');
        }

        function submitPenalty(event) {
            event.preventDefault();

            const userId = document.getElementById('penaltyUserId').value;
            const paymentId = document.getElementById('penaltyPaymentId').value;
            const penalty = document.getElementById('penaltyAmount').value;

            fetch('../../actions/payment/update_penalty.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `payment_id=${paymentId}&user_id=${userId}&penalty=${penalty}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        closePenaltyModal();
                        viewPaymentDetails(paymentId); // รีโหลดข้อมูล
                    } else {
                        alert('เกิดข้อผิดพลาด: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('เกิดข้อผิดพลาดในการบันทึกข้อมูล');
                });
        }

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
                toggleIcon.setAttribute('d', 'M15 19l-7-7 7-7');
                textElements.forEach(el => el.classList.remove('opacity-0'));
            } else {
                sidebar.classList.remove('w-64');
                sidebar.classList.add('w-20');
                toggleIcon.setAttribute('d', 'M9 5l7 7-7 7');
                textElements.forEach(el => el.classList.add('opacity-0'));
            }
        });

        function showAddPaymentModal() {
            document.getElementById('addPaymentModal').classList.remove('hidden');
        }

        function closeAddPaymentModal() {
            document.getElementById('addPaymentModal').classList.add('hidden');
        }

        function viewPaymentDetails(paymentId) {
            const modal = document.getElementById('paymentDetailsModal');
            document.body.style.overflow = 'hidden'; // ป้องกัน scroll ที่ body

            fetch(`../../actions/payment/get_payment_details.php?payment_id=${paymentId}`)
                .then(response => response.text())
                .then(html => {
                    document.getElementById('paymentDetailsContent').innerHTML = html;
                    modal.classList.remove('hidden');
                    showPaymentTab('not_paid'); // แสดง tab แรก
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('เกิดข้อผิดพลาด');
                    document.body.style.overflow = 'auto';
                });
        }

        function closePaymentDetailsModal() {
            const modal = document.getElementById('paymentDetailsModal');
            modal.classList.add('hidden');
            document.body.style.overflow = 'auto'; // คืนค่า scroll ให้ body
        }

        function togglePaymentStatus(paymentId, newStatus) {
            if (confirm('คุณต้องการเปลี่ยนสถานะค่าส่วนกลางนี้ใช่หรือไม่?')) {
                fetch('../../actions/payment/toggle_payment_status.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `payment_id=${paymentId}&status=${newStatus}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            location.reload();
                        } else {
                            alert('เกิดข้อผิดพลาด: ' + data.message);
                        }
                    });
            }
        }

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

        // แก้ไขฟังก์ชัน updateTransactionStatus เพื่อรองรับการลดขนาดรูป
        async function updateTransactionStatus(transactionId, status) {
            let reason = '';
            if (status === 'rejected') {
                reason = prompt('กรุณาระบุเหตุผลที่ไม่อนุมัติ:');
                if (!reason) return;
            }

            // ถ้ามีการอัพโหลดรูปใหม่
            const fileInput = document.querySelector(`input[data-transaction="${transactionId}"]`);
            let formData = new FormData();

            if (fileInput && fileInput.files.length > 0) {
                try {
                    const resizedFile = await resizeAndConvertToJpg(fileInput.files[0]);
                    formData.append('slip_image', resizedFile);
                } catch (error) {
                    console.error('Error resizing image:', error);
                    alert('เกิดข้อผิดพลาดในการประมวลผลรูปภาพ');
                    return;
                }
            }

            formData.append('transaction_id', transactionId);
            formData.append('status', status);
            formData.append('reason', reason);

            fetch('../../actions/payment/update_transaction_status.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        viewPaymentDetails(data.payment_id);
                    } else {
                        alert('เกิดข้อผิดพลาด: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('เกิดข้อผิดพลาดในการอัพเดทสถานะ');
                });
        }

        // เพิ่มฟังก์ชันสำหรับ preview รูปภาพ
        async function previewImage(event, transactionId) {
            const file = event.target.files[0];
            if (file) {
                try {
                    const resizedFile = await resizeAndConvertToJpg(file);
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const preview = document.querySelector(`#preview-${transactionId}`);
                        if (preview) {
                            preview.src = e.target.result;
                            preview.classList.remove('hidden');
                        }
                    }
                    reader.readAsDataURL(resizedFile);
                } catch (error) {
                    console.error('Error resizing image:', error);
                    alert('เกิดข้อผิดพลาดในการประมวลผลรูปภาพ');
                }
            }
        }

        function refreshPaymentDetails(paymentId) {
            fetch(`../../actions/payment/get_payment_details.php?payment_id=${paymentId}`)
                .then(response => response.text())
                .then(html => {
                    document.getElementById('paymentDetailsContent').innerHTML = html;
                })
                .catch(error => {
                    console.error('Error:', error);
                });
        }

        function updateCounters() {
            const pendingCount = document.querySelector('.bg-yellow-50 .space-y-2').children.length;
            const approvedCount = document.querySelector('.bg-green-50 .space-y-2').children.length;
            const notPaidCount = document.querySelector('.bg-gray-50 .space-y-2').children.length;

            document.querySelector('.bg-yellow-50 h3').textContent = `รอตรวจสอบ (${pendingCount})`;
            document.querySelector('.bg-green-50 h3').textContent = `ชำระแล้ว (${approvedCount})`;
            document.querySelector('.bg-gray-50 h3').textContent = `ยังไม่ชำระ (${notPaidCount})`;
        }

        function deletePayment(paymentId) {
            // ยืนยันครั้งแรก
            if (confirm('คุณต้องกาลบค่าส่วนกลางนี้ใช่หรือไม่?')) {
                // ยืนยันครั้งที่สอง
                if (confirm('️⚠️⚠️ โปรดยืนยันอีกครั้ง: การดำเนินการนี้ไม่สามารถย้อนกลับได้ และข้อมูลทั้งหมดที่เกี่ยวข้องจะถูกลบถาวร กรุณาตรวจสอบอีกทีให้แน่ใจว่าท่านลบรายการที่ถูกต้องแล้ว หรื ต้องการที่จะลบรายการนี้จริง ๆ ⚠️⚠️️')) {
                    fetch('../../actions/payment/delete_payment.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: `payment_id=${paymentId}`
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                location.reload();
                            } else {
                                alert('เกิดข้อผิดพลาด: ' + data.message);
                            }
                        });
                }
            }
        }

        function toggleNotifications() {
            const dropdown = document.getElementById('notificationDropdown');
            dropdown.classList.toggle('hidden');

            // ปิดเมื่อคลิกที่อื่น
            document.addEventListener('click', function closeDropdown(e) {
                if (!e.target.closest('#notificationDropdown') && !e.target.closest('button')) {
                    dropdown.classList.add('hidden');
                    document.removeEventListener('click', closeDropdown);
                }
            });
        }

        document.getElementById('selectAll').addEventListener('change', function() {
            const checkboxes = document.getElementsByClassName('user-checkbox');
            for (let checkbox of checkboxes) {
                checkbox.checked = this.checked;
            }
        });

        // ตรวจสอบการ submit form
        document.getElementById('addPaymentForm').addEventListener('submit', function(e) {
            const checkboxes = document.getElementsByClassName('user-checkbox');
            let checked = false;
            for (let checkbox of checkboxes) {
                if (checkbox.checked) {
                    checked = true;
                    break;
                }
            }
            if (!checked) {
                e.preventDefault();
                alert('กรุณาลือกผู้ใช้อย่างน้อย 1 คน');
            }
        });

        document.addEventListener('DOMContentLoaded', function() {
            const modal = document.getElementById('paymentDetailsModal');
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    modal.classList.add('hidden');
                }
            });
        });

        function showPaymentTab(tabName) {
            // ซ่อนทุก tab content
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.add('opacity-0');
                setTimeout(() => {
                    tab.classList.add('hidden');
                }, 150);
            });

            // แดง tab ที่เลือก
            setTimeout(() => {
                const selectedTab = document.getElementById(`tab-${tabName}`);
                if (selectedTab) {
                    selectedTab.classList.remove('hidden');
                    requestAnimationFrame(() => {
                        selectedTab.classList.remove('opacity-0');
                    });
                }
            }, 160);

            // อัพเดทสถานะปุ่ม
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('text-blue-600', 'border-blue-600');
                btn.classList.add('text-gray-500', 'border-transparent');
            });

            // ไฮไลท์ปุ่มที่เลือก
            const activeBtn = document.querySelector(`[data-tab="${tabName}"]`);
            if (activeBtn) {
                activeBtn.classList.remove('text-gray-500', 'border-transparent');
                activeBtn.classList.add('text-blue-600', 'border-blue-600');
            }
        }

        document.getElementById('userSearch').addEventListener('input', function() {
            const searchValue = this.value.toLowerCase();
            const userItems = document.querySelectorAll('.user-item');

            userItems.forEach(item => {
                const username = item.querySelector('label').textContent.toLowerCase();
                if (username.includes(searchValue)) {
                    item.style.display = 'flex';
                } else {
                    item.style.display = 'none';
                }
            });
        });

        document.getElementById('selectAll').addEventListener('change', function() {
            const checkboxes = document.getElementsByClassName('user-checkbox');
            for (let checkbox of checkboxes) {
                checkbox.checked = this.checked;
            }
        });

        // เพิ่มโค้นี้ต่อจาก script ที่มีอยู่เดิม
        document.addEventListener('DOMContentLoaded', function() {
            const userSearch = document.getElementById('userSearch');
            const userList = document.getElementById('userList');
            const selectAll = document.getElementById('selectAll');
            const userItems = document.querySelectorAll('.user-item');
            const userCheckboxes = document.querySelectorAll('.user-checkbox');

            // ฟังก์ชันค้นหาผู้ใช้
            userSearch.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase();
                userItems.forEach(item => {
                    const text = item.textContent.toLowerCase();
                    item.style.display = text.includes(searchTerm) ? '' : 'none';
                });
                updateSelectAllState();
            });

            // เลือกทั้งหมด
            selectAll.addEventListener('change', function() {
                const visibleCheckboxes = Array.from(userCheckboxes)
                    .filter(cb => cb.closest('.user-item').style.display !== 'none');
                visibleCheckboxes.forEach(checkbox => {
                    checkbox.checked = this.checked;
                });
            });

            // อัพเดทสถานะ "เลือกทั้งหมด" เมื่อมีการเลือก/ยกเลิกรายการ
            userList.addEventListener('change', function(e) {
                if (e.target.classList.contains('user-checkbox')) {
                    updateSelectAllState();
                }
            });

            function updateSelectAllState() {
                const visibleItems = Array.from(userItems).filter(item => item.style.display !== 'none');
                const visibleCheckboxes = visibleItems.map(item => item.querySelector('.user-checkbox'));

                // ถ้ามีรายการที่แสดงอยู่มากกว่า 1 รายการ จึงจะแสดงสถานะ "เลือกทั้งหมด"
                if (visibleItems.length > 1) {
                    const allChecked = visibleCheckboxes.every(cb => cb.checked);
                    const someChecked = visibleCheckboxes.some(cb => cb.checked);

                    selectAll.checked = allChecked;
                    selectAll.indeterminate = !allChecked && someChecked;
                } else {
                    // ถ้ามีรายการเดียว ให้ล้างสถานะ "เลือกทั้งหมด"
                    selectAll.checked = false;
                    selectAll.indeterminate = false;
                }
            }

            // เคลียร์การเลือกทั้งหมดเมื่อปิด Modal
            function clearSelections() {
                userCheckboxes.forEach(cb => cb.checked = false);
                selectAll.checked = false;
                selectAll.indeterminate = false;
                userSearch.value = '';
                userItems.forEach(item => item.style.display = '');
            }

            // เพิ่ม event listener สำหรับการปิด Modal
            document.getElementById('addPaymentModal').addEventListener('hidden.bs.modal', clearSelections);
        });

        // เพิ่มต่อจาก script ที่มอยู่
        document.addEventListener('DOMContentLoaded', function() {
            const monthFilter = document.getElementById('monthFilter');
            const yearFilter = document.getElementById('yearFilter');
            const searchFilter = document.getElementById('searchFilter');
            const sortFilter = document.getElementById('sortFilter');

            let payments = <?php echo json_encode($payments); ?>;

            function filterAndSortTable() {
                const month = monthFilter.value;
                const year = yearFilter.value;
                const search = searchFilter.value.toLowerCase();
                const sort = sortFilter.value;

                const rows = document.querySelectorAll('tbody tr');
                let visibleRows = [];

                rows.forEach(row => {
                    const [monthCell, yearCell] = row.cells[1].textContent.split('/');
                    const description = row.cells[2].textContent.toLowerCase();
                    const paidCount = parseInt(row.cells[4].textContent.split('/')[0]);
                    const totalCount = parseInt(row.cells[4].textContent.split('/')[1]);
                    const unpaidCount = totalCount - paidCount;

                    const monthMatch = !month || monthCell.trim() === month.padStart(2, '0');
                    const yearMatch = !year || yearCell === year;
                    const searchMatch = !search || description.includes(search);

                    if (monthMatch && yearMatch && searchMatch) {
                        row.style.display = '';
                        visibleRows.push({
                            element: row,
                            paidCount: paidCount,
                            unpaidCount: unpaidCount
                        });
                    } else {
                        row.style.display = 'none';
                    }
                });

                // เรียงลำดับตามที่เลือก
                if (sort) {
                    visibleRows.sort((a, b) => {
                        if (sort === 'unpaid_desc') {
                            return b.unpaidCount - a.unpaidCount;
                        } else if (sort === 'paid_desc') {
                            return b.paidCount - a.paidCount;
                        }
                        return 0;
                    });

                    // จัดเรียงใหม่ใน DOM
                    const tbody = document.querySelector('tbody');
                    visibleRows.forEach(row => {
                        tbody.appendChild(row.element);
                    });
                }
            }

            monthFilter.addEventListener('change', filterAndSortTable);
            yearFilter.addEventListener('change', filterAndSortTable);
            searchFilter.addEventListener('input', filterAndSortTable);
            sortFilter.addEventListener('change', filterAndSortTable);
        });

        function showImportModal() {
            document.getElementById('importModal').classList.remove('hidden');
        }

        function closeImportModal() {
            document.getElementById('importModal').classList.add('hidden');
        }

        function uploadSlip(transactionId, inputElement) {
            const file = inputElement.files[0];
            if (!file) {
                alert('กรุณาเลือกไฟล์');
                return;
            }

            const formData = new FormData();
            formData.append('transaction_id', transactionId);
            formData.append('slip_image', file);

            // แสดง loading หรือ disable ปุ่ม
            inputElement.disabled = true;

            fetch('../../actions/payment/upload_slip.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // รีโหลดข้อมูลทันที
                        refreshPaymentDetails(data.payment_id);
                        // หรือถ้าต้องการรีโหลดทั้งหน้า
                        // location.reload();
                    } else {
                        alert('เกิดข้อผิดพลาด: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('เกิดข้อผิดพลาดในการอัพโหลดไฟล์');
                })
                .finally(() => {
                    inputElement.disabled = false;
                });
        }

        function closeUploadModal() {
            document.getElementById('uploadSlipModal').classList.add('hidden');
        }

        // จัดการการ submit form อัพโหลดหลักฐาน
        document.getElementById('uploadSlipForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            formData.append('payment_id', currentPaymentId);

            fetch('../../actions/payment/upload_slip.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        closeUploadModal();
                        // รีโหลดรายละเอียดการชำระเงิน
                        refreshPaymentDetails(data.payment_id);
                    } else {
                        alert('เกิดข้อผิดพลาด: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('เกิดข้อผิดพลาดในการอัพโหลดไฟล์');
                });
        });

        document.addEventListener('DOMContentLoaded', function() {
            <?php if (isset($_SESSION['error'])): ?>
                alert('<?php echo addslashes($_SESSION['error']); ?>');
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>
        });

        // เพิ่มฟังก์ชันสำหรับ Modal เงินล่วงหน้า
        function showAdvancePaymentModal() {
            document.getElementById('advancePaymentModal').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        function closeAdvancePaymentModal() {
            document.getElementById('advancePaymentModal').classList.add('hidden');
            document.body.style.overflow = '';
        }

        // ค้นหาผู้ใช้ในส่วนของเงินล่วงหน้า
        document.getElementById('advanceUserSearch').addEventListener('input', function(e) {
            const searchText = e.target.value.toLowerCase();
            const userItems = document.querySelectorAll('#advanceUserList .user-item');

            userItems.forEach(item => {
                const text = item.textContent.toLowerCase();
                item.style.display = text.includes(searchText) ? '' : 'none';
            });
        });

        function showUpdateStatusModal() {
            document.getElementById('updateStatusModal').classList.remove('hidden');
        }

        function hideUpdateStatusModal() {
            document.getElementById('updateStatusModal').classList.add('hidden');
        }

        function toggleRejectReason(status) {
            const rejectReasonField = document.getElementById('rejectReasonField');
            const rejectReasonTextarea = rejectReasonField.querySelector('textarea');

            if (status === 'rejected') {
                rejectReasonField.classList.remove('hidden');
                rejectReasonTextarea.required = true;
            } else {
                rejectReasonField.classList.add('hidden');
                rejectReasonTextarea.required = false;
                rejectReasonTextarea.value = ''; // Clear value when not rejected
            }
        }

        document.getElementById('statusUserSearch').addEventListener('input', function(e) {
    const searchText = e.target.value.toLowerCase();
    const userItems = document.querySelectorAll('#statusUserList .user-item');

    userItems.forEach(item => {
        const text = item.textContent.toLowerCase();
        item.style.display = text.includes(searchText) ? '' : 'none';
    });
});
      
              function printReceipt(userId, paymentId, transactionId) {
            // สร้าง URL สำหรับเปิดหน้าใบเสร็จในหน้าต่างใหม่
            const url = `../../actions/payment/print_receipt.php?user_id=${userId}&payment_id=${paymentId}&transaction_id=${transactionId}`;
            window.open(url, '_blank');
        }

        function printInvoice(userId, paymentId) {
            const url = `../../actions/payment/print_invoice.php?user_id=${userId}&payment_id=${paymentId}`;
            window.open(url, '_blank');
        }
      
      
      function searchModalUsers() {
    const searchText = document.getElementById('modalUserSearch').value.toLowerCase();
    const tables = document.querySelectorAll('.tab-content table tbody tr');
    
    tables.forEach(row => {
        const username = row.querySelector('td:nth-child(1)')?.textContent.toLowerCase() || '';
        const fullname = row.querySelector('td:nth-child(2)')?.textContent.toLowerCase() || '';
        const phone = row.querySelector('td:nth-child(3)')?.textContent.toLowerCase() || '';
        
        if (username.includes(searchText) || 
            fullname.includes(searchText) || 
            phone.includes(searchText)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

// เพิ่ม Event Listener
document.addEventListener('DOMContentLoaded', function() {
    document.body.addEventListener('input', function(e) {
        if (e.target && e.target.id === 'modalUserSearch') {
            searchModalUsers();
        }
    });
});
        
        
        function viewSlip(slipUrl) {
    const modal = document.getElementById('viewSlipModal');
    const slipImage = document.getElementById('slipImage');
    
    if (modal && slipImage) {
        slipImage.src = slipUrl;
        modal.classList.remove('hidden');
    }
}

function closeSlipModal() {
    const modal = document.getElementById('viewSlipModal');
    if (modal) {
        modal.classList.add('hidden');
    }
}

// ฟังก์ชันสำหรับ Modal ใบเสร็จ
function showUpdateReceiptModal() {
    document.getElementById('updateReceiptModal').classList.remove('hidden');
}

function hideUpdateReceiptModal() {
    document.getElementById('updateReceiptModal').classList.add('hidden');
}

// ฟังก์ชันสำหรับ Modal ใบวางบิล
function showUpdateInvoiceModal() {
    document.getElementById('updateInvoiceModal').classList.remove('hidden');
}

function hideUpdateInvoiceModal() {
    document.getElementById('updateInvoiceModal').classList.add('hidden');
}

// ฟังก์ชันค้นหาผู้ใช้สำหรับใบเสร็จ
document.getElementById('receiptUserSearch').addEventListener('input', function(e) {
    const searchText = e.target.value.toLowerCase();
    document.querySelectorAll('#updateReceiptModal .user-item').forEach(item => {
        const text = item.textContent.toLowerCase();
        item.style.display = text.includes(searchText) ? '' : 'none';
    });
});

// ฟังก์ชันค้นหาผู้ใช้สำหรับใบวางบิล
document.getElementById('invoiceUserSearch').addEventListener('input', function(e) {
    const searchText = e.target.value.toLowerCase();
    document.querySelectorAll('#updateInvoiceModal .user-item').forEach(item => {
        const text = item.textContent.toLowerCase();
        item.style.display = text.includes(searchText) ? '' : 'none';
    });
});

// ฟังก์ชัน submit สำหรับใบเสร็จ
function submitUpdateReceipt(event) {
    event.preventDefault();
    const formData = new FormData(event.target);
    
    fetch('../../actions/payment/update_receipt_number.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('อัพเดทเลขที่ใบเสร็จเรียบร้อยแล้ว');
            hideUpdateReceiptModal();
            // รีเฟรชหน้าหรือข้อมูลตามต้องการ
        } else {
            alert('เกิดข้อผิดพลาด: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('เกิดข้อผิดพลาดในการอัพเดทข้อมูล');
    });
}

// ฟังก์ชัน submit สำหรับใบวางบิล
function submitUpdateInvoice(event) {
    event.preventDefault();
    const formData = new FormData(event.target);
    
    fetch('../../actions/payment/update_invoice_number.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('อัพเดทเลขที่ใบวางบิลเรียบร้อยแล้ว');
            hideUpdateInvoiceModal();
            // รีเฟรชหน้าหรือข้อมูลตามต้องการ
        } else {
            alert('เกิดข้อผิดพลาด: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('เกิดข้อผิดพลาดในการอัพเดทข้อมูล');
    });
}

    </script>
</body>

</html>