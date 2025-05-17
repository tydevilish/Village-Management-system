<?php
session_start();
require_once '../../config/config.php';
require_once '../../includes/auth.php';
include '../../components/menu/Menu.php';

// ตรวจสอบสิทธิ์
checkPageAccess(PAGE_ADVANCE_PAYMENT);
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>| ระบบจัดการหมู่บ้าน</title>
    <link rel="icon" href="https://devcm.info/img/favicon.png">
    <link rel="stylesheet" href="../../src/style.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <style>
        /* เพิ่ม animation และ styles เหมือน manage_payment.php */
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

        /* จัดแต่ง scrollbar เหมือน manage_payment.php */
        #usageHistoryContent::-webkit-scrollbar {
            width: 8px;
        }

        #usageHistoryContent::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }

        #usageHistoryContent::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 4px;
        }

        #usageHistoryContent::-webkit-scrollbar-thumb:hover {
            background: #666;
        }

        @media (max-width: 640px) {
            #usageHistoryModal .relative {
                top: 10px;
                margin-bottom: 20px;
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
        <!-- ปรับแต่ง Top Navigation -->
        <nav class="bg-white shadow-sm px-6 py-4">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">รายชื่อและประวัติเงินล่วงหน้า</h1>
                    <p class="text-sm text-gray-500 mt-1">รายการเงินล่วงหน้าและการใช้งาน</p>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="manage_payment.php"
                        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center space-x-2 transition-all duration-200">
                        <i class="fas fa-arrow-left mr-2"></i>
                        กลับไปหน้าจัดการค่าส่วนกลาง
                    </a>
                </div>
            </div>
        </nav>

        <!-- Content Section -->
        <div class="p-6">
            <!-- Filter Section -->
            <div class="flex items-center justify-between mb-6">
                <div class="w-full lg:w-auto">
                    <div class="flex flex-col lg:flex-row gap-4">
                        <div class="flex flex-wrap gap-2 items-center">
                            <!-- ค้นหา -->
                            <div class="relative">
                                <input type="text" id="searchFilter" placeholder="ค้นหาผู้ใช้..."
                                    class="pl-10 pr-4 py-2 bg-white border border-gray-300 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 w-full sm:w-auto">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                </div>
                            </div>

                            <!-- เพิ่มตัวเลือกการเรียงลำดับ -->
                            <select id="sortFilter" class="px-3 py-2 bg-white border border-gray-300 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">การเรียงลำดับ</option>
                                <option value="date_desc">วันที่ล่าสุด</option>
                                <option value="amount_desc">ยอดเงินมากที่สุด</option>
                                <option value="remaining_desc">คงเหลือมากที่สุด</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                <?php
                // ดึงข้อมูลสรุป
                $stmt = $conn->prepare("
                        SELECT 
                            SUM(remaining_amount) as total_remaining,
                            COUNT(DISTINCT user_id) as total_users
                        FROM advance_payments
                        WHERE status = 'active'
                    ");
                $stmt->execute();
                $summary = $stmt->fetch(PDO::FETCH_ASSOC);
                ?>

                <div class="bg-white rounded-lg shadow-sm p-6 transform hover:scale-105 transition-transform duration-200">
                    <h3 class="text-lg font-semibold text-gray-700 mb-2">ยอดเงินที่ชำระล่วงหน้า</h3>
                    <p class="text-3xl font-bold text-green-600"><?= number_format($summary['total_remaining'] ?? 0, 2) ?> บาท</p>
                </div>

                <div class="bg-white rounded-lg shadow-sm p-6 transform hover:scale-105 transition-transform duration-200">
                    <h3 class="text-lg font-semibold text-gray-700 mb-2">จำนวนผู้ใช้ที่มีเงินล่วงหน้า</h3>
                    <p class="text-3xl font-bold text-purple-600"><?= number_format($summary['total_users'] ?? 0) ?> คน</p>
                </div>
            </div>

            <!-- Table Section -->
            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                <div class="p-6 border-b">
                    <h2 class="text-xl font-semibold text-gray-800">รายการเงินล่วงหน้า</h2>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ชื่อผู้ใช้</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ยอดคงเหลือ</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">วันที่ชำระ</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">สถานะ</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ดูประวัติ</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php
                            // ดึงข้อมูลเงินล่วงหน้าทั้งหมด
                            $stmt = $conn->prepare("
                                    SELECT 
                                        ap.*,
                                        u.username,
                                        u.fullname
                                    FROM advance_payments ap
                                    JOIN users u ON ap.user_id = u.user_id
                                    ORDER BY ap.payment_date DESC
                                ");
                            $stmt->execute();
                            $advances = $stmt->fetchAll(PDO::FETCH_ASSOC);

                            foreach ($advances as $advance):
                            ?>
                                <tr class="hover:bg-gray-50 transition-colors duration-200">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">
                                            <?= htmlspecialchars($advance['username']) ?>
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            <?= htmlspecialchars($advance['fullname']) ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">
                                            <?= number_format($advance['remaining_amount'], 2) ?> บาท
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">
                                            <?= date('d/m/Y H:i', strtotime($advance['payment_date'])) ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= $advance['status'] === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' ?>">
                                            <?= $advance['status'] === 'active' ? 'ใช้งานอยู่' : 'ใช้หมดแล้ว' ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <button onclick="showUsageHistory(<?= $advance['advance_id'] ?>)"
                                            class="text-blue-600 hover:text-blue-900 bg-blue-100 rounded-md px-3 py-1.5 transition-colors duration-200">
                                            <i class="fas fa-history mr-1"></i> ดูประวัติ
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Section -->
    <div id="usageHistoryModal" class="hidden fixed inset-0 bg-black bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative min-h-screen flex items-center justify-center p-4">
            <div class="relative w-full max-w-2xl bg-white rounded-lg shadow-2xl mx-auto">
                <div class="bg-gradient-to-r from-blue-600 to-blue-700 rounded-t-lg">
                    <div class="flex justify-between items-center p-6">
                        <h3 class="text-2xl font-semibold text-white">ประวัติการใช้เงินล่วงหน้า</h3>
                        <button onclick="closeUsageHistoryModal()" class="text-white hover:text-gray-200 transition-colors">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>
                <div id="usageHistoryContent" class="p-6">
                    <!-- Content will be loaded here -->
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
                toggleIcon.setAttribute('d', 'M15 19l-7-7 7-7');
                textElements.forEach(el => el.classList.remove('opacity-0'));
            } else {
                sidebar.classList.remove('w-64');
                sidebar.classList.add('w-20');
                toggleIcon.setAttribute('d', 'M9 5l7 7-7 7');
                textElements.forEach(el => el.classList.add('opacity-0'));
            }
        });


        // เพิ่ม JavaScript สำหรับการค้นหาและเรียงลำดับ
        document.addEventListener('DOMContentLoaded', function() {
            const searchFilter = document.getElementById('searchFilter');
            const sortFilter = document.getElementById('sortFilter');

            function filterAndSortTable() {
                // Implementation of filter and sort logic
            }

            searchFilter.addEventListener('input', filterAndSortTable);
            sortFilter.addEventListener('change', filterAndSortTable);
        });

        function showUsageHistory(advanceId) {
            const modal = document.getElementById('usageHistoryModal');
            const content = document.getElementById('usageHistoryContent');
            modal.classList.remove('hidden');

            // ดึงข้อมูลประวัติการใช้งาน
            fetch(`../../actions/payment/get_advance_usage_history.php?advance_id=${advanceId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        content.innerHTML = data.html;
                    } else {
                        content.innerHTML = '<p class="text-red-500">เกิดข้อผิดพลาดในการดึงข้อมูล</p>';
                    }
                })
                .catch(error => {
                    content.innerHTML = '<p class="text-red-500">เกิดข้อผิดพลาดในการดึงข้อมูล</p>';
                });
        }

        function closeUsageHistoryModal() {
            document.getElementById('usageHistoryModal').classList.add('hidden');
        }
    </script>
</body>

</html>