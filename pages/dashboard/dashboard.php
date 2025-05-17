<?php
session_start();
date_default_timezone_set('Asia/Bangkok');
require_once '../../config/config.php';
require_once '../../includes/auth.php';
include '../../components/menu/Menu.php';


// ตรวจสอบสิทธิ์การเข้าถึงหน้า dashboard
checkPageAccess(PAGE_DASHBOARD);

// ดึงข้อมูลบทบาทของผู้ใช้
$user_role_id = $_SESSION['role_id'];

// เพิ่ม queries สำหรับดึงข้อมูลสถิติต่างๆ สำหรับทั้งแอดมินและเจ้าหน้าที่
if ($user_role_id == 1 || $user_role_id == 7) {
    // ดึงยอดเงินทั้งหมดที่ผู้ใช้จ่าย
    $stmt = $conn->query("SELECT SUM(amount) as total_amount FROM transactions WHERE status = 'approved'");
    $total_amount = $stmt->fetch(PDO::FETCH_ASSOC)['total_amount'] ?? 0;

    // จำนวนสมาชิกทั้งหมด
    $stmt = $conn->query("SELECT COUNT(*) as total_users FROM users");
    $total_users = $stmt->fetch(PDO::FETCH_ASSOC)['total_users'];

    // จำนวนผู้มาเยือนทั้งหมด
    $stmt = $conn->query("SELECT COUNT(*) as total_visitors FROM visitors");
    $total_visitors = $stmt->fetch(PDO::FETCH_ASSOC)['total_visitors'];

    // จำนวนสิทธิ์ในระบบ
    $stmt = $conn->query("SELECT COUNT(*) as total_roles FROM roles");
    $total_roles = $stmt->fetch(PDO::FETCH_ASSOC)['total_roles'];

    // จำนวนคนที่รออนุมัติ
    $stmt = $conn->query("SELECT COUNT(*) as pending_users FROM transactions WHERE status = 'pending'");
    $pending_users = $stmt->fetch(PDO::FETCH_ASSOC)['pending_users'] ?? 0;

    // จำนวนเงินที่ยังไม่ได้ชำระ
    $stmt = $conn->query("
    SELECT SUM(amount) as unpaid_amount 
    FROM transactions 
    WHERE status = 'not_paid'
");
    $unpaid_amount = $stmt->fetch(PDO::FETCH_ASSOC)['unpaid_amount'] ?? 0;
}

// เพิ่ม queries สำหรับผู้ใช้งาน
if ($user_role_id == 2) {
    $user_id = $_SESSION['user_id'];

    // จำนวนรายการและเงินที่รอชำระ
    $stmt = $conn->query("
        SELECT 
            COUNT(*) as pending_count,
            SUM(amount) as pending_amount
        FROM transactions
        WHERE user_id = $user_id 
        AND status = 'not_paid'
    ");
    $pending = $stmt->fetch(PDO::FETCH_ASSOC);
    $pending_count = $pending['pending_count'] ?? 0;
    $pending_amount = $pending['pending_amount'] ?? 0;

    // จำนวนรายการและยอดเงินที่ชำระแล้ว
    $stmt = $conn->query("
        SELECT 
            COUNT(*) as paid_count,
            SUM(amount) as paid_amount
        FROM transactions 
        WHERE user_id = $user_id 
        AND status = 'approved'
    ");
    $paid = $stmt->fetch(PDO::FETCH_ASSOC);
    $paid_count = $paid['paid_count'] ?? 0;
    $paid_amount = $paid['paid_amount'] ?? 0;

    // จำนวนรายการที่ถูกปฏิเสธ
    $stmt = $conn->query("
        SELECT COUNT(*) as rejected_count
        FROM transactions 
        WHERE user_id = $user_id 
        AND status = 'rejected'
    ");
    $rejected_count = $stmt->fetch(PDO::FETCH_ASSOC)['rejected_count'] ?? 0;

    // ตรวจสอบการชำระในเดือนนี้
    $current_month = date('m');
    $current_year = date('Y');
    $stmt = $conn->query("
        SELECT COUNT(*) as paid_this_month
        FROM transactions 
        WHERE user_id = $user_id 
        AND status = 'approved'
        AND MONTH(created_at) = $current_month
        AND YEAR(created_at) = $current_year
    ");
    $paid_this_month = $stmt->fetch(PDO::FETCH_ASSOC)['paid_this_month'] > 0;
}

// เพิ่ม queries สำหรับเจ้าหน้าที่รักษาความปลอดภัย
if ($user_role_id == 9) {
    // ผู้มาเยือนเมื่อวาน
    $yesterday = date('Y-m-d', strtotime('-1 day'));
    $stmt = $conn->query("
        SELECT COUNT(*) as yesterday_visitors 
        FROM visitors 
        WHERE DATE(created_at) = '$yesterday'
    ");
    $yesterday_visitors = $stmt->fetch(PDO::FETCH_ASSOC)['yesterday_visitors'];

    // ผู้มาเยือนวันนี้
    $today = date('Y-m-d');
    $stmt = $conn->query("
        SELECT COUNT(*) as today_visitors 
        FROM visitors 
        WHERE DATE(created_at) = '$today'
    ");
    $today_visitors = $stmt->fetch(PDO::FETCH_ASSOC)['today_visitors'];

    // ดึงข้อมูลเหตุผลการเข้าเยี่ยมและจำนวน
    $stmt = $conn->query("
        SELECT r.reason_name, COUNT(v.visitor_id) as count
        FROM visit_reasons r
        LEFT JOIN visitors v ON r.reason_id = v.reason_id
        GROUP BY r.reason_id, r.reason_name
        ORDER BY count DESC
    ");
    $visit_reasons = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // ผู้มาเยือนทั้งหมด
    $stmt = $conn->query("
        SELECT COUNT(*) as total_all_visitors 
        FROM visitors
    ");
    $total_all_visitors = $stmt->fetch(PDO::FETCH_ASSOC)['total_all_visitors'];
}

// ดึงข้อมูลผู้มาเยือนรายเดือน
$monthly_stats = $conn->query("
    SELECT 
        DATE_FORMAT(created_at, '%Y-%m') as month,
        COUNT(*) as visitor_count
    FROM visitors
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY month ASC
")->fetchAll(PDO::FETCH_ASSOC);

// ดึงข้อมูลสัดส่นเหตุผลการเข้าพบ
$reason_stats = $conn->query("
    SELECT 
        vr.reason_name,
        COUNT(v.visitor_id) as count
    FROM visit_reasons vr
    LEFT JOIN visitors v ON vr.reason_id = v.reason_id
    GROUP BY vr.reason_id, vr.reason_name
    ORDER BY count DESC
")->fetchAll(PDO::FETCH_ASSOC);


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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body class="bg-modern">
    <div class="flex">
        <div id="sidebar" class="fixed top-0 left-0 z-50 h-full w-20 transition-all duration-300 ease-in-out bg-gradient-to-b from-blue-600 to-blue-500 shadow-xl">
            <button id="toggleSidebar" class="absolute -right-3 bottom-24 bg-blue-800 text-white rounded-full p-1 shadow-lg hover:bg-blue-900">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </button>
            <?php renderMenu(); ?>
        </div>
    </div>

    <div class="flex-1 ml-20">
        <nav class="bg-white shadow-sm px-6 py-3">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold text-eva">หน้าแรก</h1>
                <div class="flex items-center space-x-4">
                    <a href="https://devcm.info" target="_blank" class="p-2 rounded-full hover:bg-gray-100">
                        <img src="https://devcm.info/img/favicon.png" class="h-6 w-6" alt="User icon">
                    </a>
                </div>
            </div>
        </nav>

        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6 mb-6">
                <?php if ($user_role_id == 1): ?>
                    <!-- แสดงเนื้อหาสำหรับผู้ดูแลระบบ -->
                    <!-- ยอดเงินทั้งหมด -->
                    <div class="bg-white shadow-sm p-6 hover:shadow-md transition-shadow duration-300">
                        <div class="flex items-center justify-between">
                            <div class="rounded-lg p-3 bg-blue-500">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-medium text-gray-500">ยอดเงินทั้งหมด</p>
                                <h3 class="text-xl font-bold text-gray-800"><?= number_format($total_amount, 2) ?> บาท</h3>
                            </div>
                        </div>
                    </div>

                    <!-- เงินที่รออนุมัติ -->
                    <div class="bg-white shadow-sm p-6 hover:shadow-md transition-shadow duration-300">
                        <div class="flex items-center justify-between">
                            <div class="rounded-lg p-3 bg-yellow-500">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                </svg>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-medium text-gray-500">จำนวนคนที่รออนุมัติ</p>
                                <h3 class="text-xl font-bold text-gray-800"><?= number_format($pending_users) ?> คน</h3>
                                <p class="text-sm text-yellow-500">รอการอนุมัติ</p>
                            </div>
                        </div>
                    </div>

                    <!-- เงินที่ยังไม่ได้ชำระ -->
                    <div class="bg-white shadow-sm p-6 hover:shadow-md transition-shadow duration-300">
                        <div class="flex items-center justify-between">
                            <div class="rounded-lg p-3 bg-red-500">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-medium text-gray-500">จำนวนเงินที่ยังไม่ได้ชำระ</p>
                                <h3 class="text-xl font-bold text-gray-800"><?= number_format($unpaid_amount, 2) ?> บาท</h3>
                                <p class="text-sm text-red-500">ยังไม่ได้ชำระ</p>
                            </div>
                        </div>
                    </div>

                    <!-- จำนวนสมาชิก -->
                    <div class="bg-white shadow-sm p-6 hover:shadow-md transition-shadow duration-300">
                        <div class="flex items-center justify-between">
                            <div class="rounded-lg p-3 bg-green-500">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                </svg>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-medium text-gray-500">จำนวนสมาชิกในระบบ</p>
                                <h3 class="text-xl font-bold text-gray-800"><?= number_format($total_users) ?> คน</h3>
                            </div>
                        </div>
                    </div>

                    <!-- จำนวนผู้มาเยือน -->
                    <div class="bg-white shadow-sm p-6 hover:shadow-md transition-shadow duration-300">
                        <div class="flex items-center justify-between">
                            <div class="rounded-lg p-3 bg-yellow-500">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-medium text-gray-500">จำนวนผู้มาเยือนทั้งหมด</p>
                                <h3 class="text-xl font-bold text-gray-800"><?= number_format($total_visitors) ?> คน</h3>
                            </div>
                        </div>
                    </div>

                    <!-- จำนวนสิทธิ์ -->
                    <div class="bg-white shadow-sm p-6 hover:shadow-md transition-shadow duration-300">
                        <div class="flex items-center justify-between">
                            <div class="rounded-lg p-3 bg-purple-500">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                </svg>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-medium text-gray-500">จำนวนสิทธิ์ในระบบ</p>
                                <h3 class="text-xl font-bold text-gray-800"><?= number_format($total_roles) ?> สิทธิ์</h3>
                            </div>
                        </div>
                    </div>
                <?php elseif ($user_role_id == 7): ?>
                    <!-- แสดงเนื้อหาสำหรับเจ้าหน้าที่ -->
                    <!-- ยอดเงินทั้งหมด -->
                    <div class="bg-white shadow-sm p-6 hover:shadow-md transition-shadow duration-300">
                        <div class="flex items-center justify-between">
                            <div class="rounded-lg p-3 bg-blue-500">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-medium text-gray-500">ยอดเงินทั้งหมด</p>
                                <h3 class="text-xl font-bold text-gray-800"><?= number_format($total_amount, 2) ?> บาท</h3>
                            </div>
                        </div>
                    </div>

                    <!-- เงินที่รออนุมัติ -->
                    <div class="bg-white shadow-sm p-6 hover:shadow-md transition-shadow duration-300">
                        <div class="flex items-center justify-between">
                            <div class="rounded-lg p-3 bg-yellow-500">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                </svg>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-medium text-gray-500">จำนวนคนที่รออนุมัติ</p>
                                <h3 class="text-xl font-bold text-gray-800"><?= number_format($pending_users) ?> คน</h3>
                                <p class="text-sm text-yellow-500">รอการอนุมัติ</p>
                            </div>
                        </div>
                    </div>

                    <!-- เงินที่ยังไม่ได้ชำระ -->
                    <div class="bg-white shadow-sm p-6 hover:shadow-md transition-shadow duration-300">
                        <div class="flex items-center justify-between">
                            <div class="rounded-lg p-3 bg-red-500">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-medium text-gray-500">เงินที่ยังไม่ได้ชำระ</p>
                                <h3 class="text-xl font-bold text-gray-800"><?= number_format($unpaid_amount, 2) ?> บาท</h3>
                                <p class="text-sm text-red-500">ยังไม่ได้ชำระ</p>
                            </div>
                        </div>
                    </div>

                    <!-- จำนวนสมาชิก -->
                    <div class="bg-white shadow-sm p-6 hover:shadow-md transition-shadow duration-300">
                        <div class="flex items-center justify-between">
                            <div class="rounded-lg p-3 bg-green-500">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                </svg>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-medium text-gray-500">จำนวนสมาชิกในะบบ</p>
                                <h3 class="text-xl font-bold text-gray-800"><?= number_format($total_users) ?> คน</h3>
                            </div>
                        </div>
                    </div>

                    <!-- จำนวนสิทธิ์ -->
                    <div class="bg-white shadow-sm p-6 hover:shadow-md transition-shadow duration-300">
                        <div class="flex items-center justify-between">
                            <div class="rounded-lg p-3 bg-purple-500">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                </svg>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-medium text-gray-500">จำนวนสิทธิ์ในระบบ</p>
                                <h3 class="text-xl font-bold text-gray-800"><?= number_format($total_roles) ?> สิทธิ์</h3>
                            </div>
                        </div>
                    </div>
                <?php elseif ($user_role_id == 9): ?>

                    <!-- กราฟแท่งแสดงสถิติรายเดือน -->
                    <div class="bg-white rounded-xl p-6 hover:shadow-md transition-all duration-300 mb-6"> <!-- เพิ่ม mb-6 -->
                        <div class="flex items-center justify-between mb-6">
                            <div class="flex items-center space-x-3">
                                <div class="p-3 bg-gradient-to-r from-blue-500 to-blue-600 rounded-lg">
                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 8v8m-4-5v5m-4-2v2m-2 4h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-800">สถิติผู้มาเยือนรายเดือน</h3>
                                    <p class="text-sm text-gray-500">ข้อมูลย้อนหลัง 12 เดือน</p>
                                </div>
                            </div>
                        </div>
                        <div class="h-[100px] lg:h-[200px]">
                            <canvas id="monthlyVisitorsChart"></canvas>
                        </div>
                    </div>

                    <!-- กราฟวงกลมแสดงสัดส่วนเหตุผลการเข้าพบ -->
                    <div class="bg-white rounded-xl p-6 hover:shadow-md transition-all duration-300 mb-6"> <!-- เพิ่ม mb-6 -->
                        <div class="flex items-center justify-between mb-6">
                            <div class="flex items-center space-x-3">
                                <div class="p-3 bg-gradient-to-r from-purple-500 to-purple-600 rounded-lg">
                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-800">สัดส่วนเหตุผลการเข้าพบ</h3>
                                    <p class="text-sm text-gray-500">แยกตามประเภท</p>
                                </div>
                            </div>
                        </div>
                        <div class="h-[200px]">
                            <canvas id="visitReasonsChart"></canvas>
                        </div>
                    </div>

                    <!-- สถิติผู้เข้าเยี่ยมที่มาส่งอาหาร -->
                    <div class="bg-white rounded-xl p-6 hover:shadow-md transition-all duration-300 mb-6"> <!-- เพิ่ม mb-6 -->
                        <div class="flex items-center justify-between mb-6">
                            <div class="flex items-center space-x-3">
                                <div class="p-3 bg-gradient-to-r from-orange-500 to-orange-600 rounded-lg">
                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-800">สถิติผู้เข้าเยี่ยมที่มาส่งอาหาร</h3>
                                    <p class="text-sm text-gray-500">เรียงลำดับตามจำนวนครั้ง</p>
                                </div>
                            </div>
                        </div>
                        <div class="h-[200px]">
                            <canvas id="foodDeliveryChart"></canvas>
                        </div>
                    </div>

                    <script>
                        // เพิ่ม script สำหรับกราฟแท่งแนวนอน
                        <?php
                        $food_delivery_stats = $conn->query("
    SELECT 
        u.username,
        COUNT(*) as visit_count,
        MAX(v.created_at) as last_visit
    FROM visitors v
    JOIN users u ON v.user_id = u.user_id
    WHERE v.reason_id = 16
    GROUP BY v.user_id, u.username
    ORDER BY visit_count DESC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

                        $labels = array_map(function ($item) {
                            return $item['username'];
                        }, $food_delivery_stats);
                        $data = array_map(function ($item) {
                            return $item['visit_count'];
                        }, $food_delivery_stats);
                        ?>

                        new Chart(document.getElementById('foodDeliveryChart'), {
                            type: 'bar',
                            data: {
                                labels: <?= json_encode($labels) ?>,
                                datasets: [{
                                    label: 'จำนวนครั้งที่มาส่งอาหาร',
                                    data: <?= json_encode($data) ?>,
                                    backgroundColor: 'rgba(249, 115, 22, 0.2)',
                                    borderColor: 'rgb(249, 115, 22)',
                                    borderWidth: 1,
                                    borderRadius: 4,
                                    barThickness: 20,
                                }]
                            },
                            options: {
                                indexAxis: 'y',
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: {
                                        display: false
                                    },
                                    tooltip: {
                                        callbacks: {
                                            label: function(context) {
                                                return `${context.parsed.x} ครั้ง`;
                                            }
                                        }
                                    }
                                },
                                scales: {
                                    x: {
                                        beginAtZero: true,
                                        grid: {
                                            display: false
                                        },
                                        ticks: {
                                            stepSize: 1
                                        }
                                    },
                                    y: {
                                        grid: {
                                            display: false
                                        }
                                    }
                                }
                            }
                        });
                    </script>

                    <!-- วันที่มีผู้มาเยือนมากที่สุด -->
                    <div class="bg-white rounded-xl p-6 hover:shadow-md transition-all duration-300">
                        <div class="flex items-center justify-between mb-6">
                            <div class="flex items-center space-x-3">
                                <div class="p-3 bg-gradient-to-r from-green-500 to-green-600 rounded-lg">
                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-800">วันที่มีผู้เข้ามามากที่สุด</h3>
                                    <p class="text-sm text-gray-500">เรียงตามจำนวนผู้เข้าเยี่ยม</p>
                                </div>
                            </div>
                        </div>
                        <div class="h-[200px]">
                            <canvas id="peakDaysChart"></canvas>
                        </div>
                    </div>

                    <script>
                        <?php
                        $peak_days = $conn->query("
    SELECT 
        DATE_FORMAT(created_at, '%d/%m/%Y') as formatted_date,
        COUNT(*) as visitor_count,
        DAYNAME(created_at) as day_name
    FROM visitors 
    GROUP BY DATE(created_at), DAYNAME(created_at)
    ORDER BY visitor_count DESC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

                        $thai_days = [
                            'Sunday' => 'วันอาทิตย์',
                            'Monday' => 'วันจันทร์',
                            'Tuesday' => 'วันอังคา',
                            'Wednesday' => 'วันพุธ',
                            'Thursday' => 'วันพฤหัสบดี',
                            'Friday' => 'วันศุกร์',
                            'Saturday' => 'วันเสาร์'
                        ];

                        $labels = array_map(function ($item) use ($thai_days) {
                            return $item['formatted_date'] . ' (' . $thai_days[$item['day_name']] . ')';
                        }, $peak_days);
                        $data = array_map(function ($item) {
                            return $item['visitor_count'];
                        }, $peak_days);
                        ?>

                        new Chart(document.getElementById('peakDaysChart'), {
                            type: 'bar',
                            data: {
                                labels: <?= json_encode($labels) ?>,
                                datasets: [{
                                    label: 'จำนวนผู้เข้าเยี่ยม',
                                    data: <?= json_encode($data) ?>,
                                    backgroundColor: 'rgba(34, 197, 94, 0.2)',
                                    borderColor: 'rgb(34, 197, 94)',
                                    borderWidth: 1,
                                    borderRadius: 4,
                                    barThickness: 30,
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: {
                                        display: false
                                    },
                                    tooltip: {
                                        callbacks: {
                                            label: function(context) {
                                                return `${context.parsed.y} คน`;
                                            }
                                        }
                                    }
                                },
                                scales: {
                                    y: {
                                        beginAtZero: true,
                                        grid: {
                                            display: false
                                        },
                                        ticks: {
                                            maxRotation: 0,
                                            minRotation: 0
                                        }
                                    },
                                    x: {
                                        grid: {
                                            display: false
                                        },
                                        ticks: {
                                            maxRotation: 0,
                                            minRotation: 0
                                        }
                                    }
                                }
                            }
                        });
                    </script>

                    <!-- ผู้มาเยือนเมื่อวาน -->
                    <div class="bg-white shadow-sm p-6 hover:shadow-md transition-shadow duration-300">
                        <div class="flex items-center justify-between">
                            <div class="rounded-lg p-3 bg-blue-500">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-medium text-gray-500">ผู้มาเยือนเมื่อวาน</p>
                                <h3 class="text-xl font-bold text-gray-800"><?= number_format($yesterday_visitors) ?> คน</h3>
                                <p class="text-sm text-blue-500"><?= date('d/m/Y', strtotime('-1 day')) ?></p>
                            </div>
                        </div>
                    </div>

                    <!-- ผู้มาเยือนวันนี้ -->
                    <div class="bg-white shadow-sm p-6 hover:shadow-md transition-shadow duration-300">
                        <div class="flex items-center justify-between">
                            <div class="rounded-lg p-3 bg-green-500">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-medium text-gray-500">ผู้มาเยือนวันนี้</p>
                                <h3 class="text-xl font-bold text-gray-800"><?= number_format($today_visitors) ?> คน</h3>
                                <p class="text-sm text-green-500"><?= date('d/m/Y') ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white shadow-sm p-6 hover:shadow-md transition-shadow duration-300">
                        <div class="flex items-center justify-between">
                            <div class="rounded-lg p-3 bg-indigo-500">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-medium text-gray-500">จำนวนผู้มาเยือนทั้งหมด</p>
                                <h3 class="text-xl font-bold text-gray-800"><?= number_format($total_all_visitors) ?> คน</h3>
                                <p class="text-sm text-indigo-500">ตั้งแต่เริ่มใช้ระบบ</p>
                            </div>
                        </div>
                    </div>

                    <!-- แสดงจำนวนผู้เข้าเยี่ยมตามเหตุผล -->
                    <?php foreach ($visit_reasons as $reason): ?>
                        <div class="bg-white shadow-sm p-6 hover:shadow-md transition-shadow duration-300">
                            <div class="flex items-center justify-between">
                                <div class="rounded-lg p-3 bg-purple-500">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                    </svg>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm font-medium text-gray-500"><?= htmlspecialchars($reason['reason_name']) ?></p>
                                    <h3 class="text-xl font-bold text-gray-800"><?= number_format($reason['count']) ?> คน</h3>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>



                <?php elseif ($user_role_id == 2): ?>
                    <!-- จำนวนรายการที่รอชำระ -->
                    <div class="bg-white shadow-sm p-6 hover:shadow-md transition-shadow duration-300">
                        <div class="flex items-center justify-between">
                            <div class="rounded-lg p-3 bg-yellow-500">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                </svg>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-medium text-gray-500">รายการที่รอชำระ</p>
                                <h3 class="text-xl font-bold text-gray-800"><?= number_format($pending_count) ?> รายการ</h3>
                            </div>
                        </div>
                    </div>

                    <!-- จำนวนเงินที่รอชำระ -->
                    <div class="bg-white shadow-sm p-6 hover:shadow-md transition-shadow duration-300">
                        <div class="flex items-center justify-between">
                            <div class="rounded-lg p-3 bg-red-500">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-medium text-gray-500">จำนวนเงินที่รอชำระ</p>
                                <h3 class="text-xl font-bold text-gray-800"><?= number_format($pending_amount, 2) ?> บาท</h3>
                            </div>
                        </div>
                    </div>

                    <!-- จำนวนรายการที่ชำระแล้ว -->
                    <div class="bg-white shadow-sm p-6 hover:shadow-md transition-shadow duration-300">
                        <div class="flex items-center justify-between">
                            <div class="rounded-lg p-3 bg-green-500">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-medium text-gray-500">รายการที่ชำระแล้ว</p>
                                <h3 class="text-xl font-bold text-gray-800"><?= number_format($paid_count) ?> รายการ</h3>
                            </div>
                        </div>
                    </div>

                    <!-- จำนวนเงินที่ชำระแล้ว -->
                    <div class="bg-white shadow-sm p-6 hover:shadow-md transition-shadow duration-300">
                        <div class="flex items-center justify-between">
                            <div class="rounded-lg p-3 bg-blue-500">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-medium text-gray-500">ยอดเงินที่ชำระแล้ว</p>
                                <h3 class="text-xl font-bold text-gray-800"><?= number_format($paid_amount, 2) ?> บาท</h3>
                            </div>
                        </div>
                    </div>

                    <!-- จำนวนรายการที่ถูกปฏิเสธ -->
                    <div class="bg-white shadow-sm p-6 hover:shadow-md transition-shadow duration-300">
                        <div class="flex items-center justify-between">
                            <div class="rounded-lg p-3 bg-red-500">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-medium text-gray-500">รายการที่ถูกปฏิเสธ</p>
                                <h3 class="text-xl font-bold text-gray-800"><?= number_format($rejected_count) ?> รายการ</h3>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white shadow-sm p-6 hover:shadow-md transition-shadow duration-300">
                        <div class="flex items-center justify-between">
                            <div class="rounded-lg p-3 bg-<?= $paid_this_month ? 'green' : 'yellow' ?>-500">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-medium text-gray-500">สถานะการชำระเดือนนี้</p>
                                <h3 class="text-xl font-bold text-<?= $paid_this_month ? 'green' : 'yellow' ?>-500">
                                    <?= $paid_this_month ? 'ชำระแล้ว' : 'ยังไม่ได้ชำระ' ?>
                                </h3>
                            </div>
                        </div>
                    </div>
                <?php elseif ($user_role_id == 14): ?>

<?php
                    // ดึงข้อมูลยอดรวมค่าส่วนกลางทั้งหมดแยกตามเดือน
                    $total_fees = $conn->query("
                        SELECT 
                            p.month,
                            p.year,
                            SUM(t.amount) as total_amount,
                            COUNT(DISTINCT t.user_id) as total_users,
                            SUM(t.penalty) as total_penalty,
                            CONCAT(p.year, '-', LPAD(p.month, 2, '0')) as period
                        FROM payments p
                        JOIN transactions t ON p.payment_id = t.payment_id
                        WHERE p.status = 'active'
                        GROUP BY p.year, p.month
                        ORDER BY p.year DESC, p.month DESC
                        LIMIT 12
                    ")->fetchAll(PDO::FETCH_ASSOC);

                    // ดึงข้อมูลผอดรวมค่าส่วนกลางที่ชำระแล้วแยกตามเดือน
                    $paid_fees = $conn->query("
                        SELECT 
                            p.month,
                            p.year,
                            SUM(t.amount) as paid_amount,
                            COUNT(DISTINCT t.user_id) as paid_users,
                            CONCAT(p.year, '-', LPAD(p.month, 2, '0')) as period
                        FROM payments p
                        JOIN transactions t ON p.payment_id = t.payment_id
                        WHERE t.status = 'approved'
                        GROUP BY p.year, p.month
                        ORDER BY p.year DESC, p.month
                        LIMIT 12
                    ")->fetchAll(PDO::FETCH_ASSOC);

                    // ดึงข้อมูลผู้ค้างชำระแยกตามระยะเวลา
                    $late_payments_sql = "
                        WITH UserLatestStatus AS (
                            SELECT 
                                t.user_id,
                                u.username,
                                u.fullname,
                                COUNT(*) as months_late,
                                MAX(CONCAT(p.year, LPAD(p.month, 2, '0'))) as latest_month
                            FROM transactions t
                            JOIN payments p ON t.payment_id = p.payment_id
                            JOIN users u ON t.user_id = u.user_id
                            WHERE t.status = 'not_paid'
                            AND p.status = 'active'
                            GROUP BY t.user_id, u.username
                        )
                        SELECT 
                            CASE 
                                WHEN months_late >= 12 THEN '12'
                                WHEN months_late >= 6 THEN '6'
                                WHEN months_late >= 3 THEN '3'
                                ELSE 'current'
                            END as late_category,
                            COUNT(*) as user_count,
                            GROUP_CONCAT(username) as usernames
                        FROM UserLatestStatus
                        WHERE months_late >= 3
                        GROUP BY 
                            CASE 
                                WHEN months_late >= 12 THEN '12'
                                WHEN months_late >= 6 THEN '6'
                                WHEN months_late >= 3 THEN '3'
                                ELSE 'current'
                            END
                        ORDER BY late_category DESC";

                    $late_payments = $conn->query($late_payments_sql)->fetchAll(PDO::FETCH_ASSOC);

                    // จัดการข้อมูลให้ง่ายต่อการใช้งาน
                    $late_stats = array_reduce($late_payments, function ($carry, $item) {
                        $carry[$item['late_category']] = [
                            'count' => $item['user_count'],
                            'users' => explode(',', $item['usernames'])
                        ];
                        return $carry;
                    }, []);
                    ?>

                    <!-- กราฟแสดงยอดรวมค่าส่วนกลาง -->
                    <div class="col-span-4 bg-white rounded-xl p-6 hover:shadow-md transition-all duration-300">
                        <div class="flex items-center justify-between mb-6">
                            <div class="flex items-center space-x-3">
                                <div class="p-3 bg-gradient-to-r from-blue-500 to-blue-600 rounded-lg">
                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 8v8m-4-5v5m-4-2v2m-2 4h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-800">สถิติค่าส่วนกลางรายเดือน</h3>
                                    <p class="text-sm text-gray-500">เปรียบเทียบยอดรวม ยอดชำระ และเบี้ยปรับ</p>
                                </div>
                            </div>
                        </div>
                        <div class="h-[400px]">
                            <canvas id="monthlyFeesChart"></canvas>
                        </div>
                    </div>

                    <script>
                        // สร้างกราฟแท่งเปรียบเทียบ
                        const feesCtx = document.getElementById('monthlyFeesChart').getContext('2d');
                        new Chart(feesCtx, {
                            type: 'bar',
                            data: {
                                labels: <?= json_encode(array_map(function ($item) {
                                            return 'เดือน ' . $item['month'] . '/' . $item['year'];
                                        }, $total_fees)) ?>,
                                datasets: [{
                                    label: 'ยอดรวมค่าส่วนกลางทั้งหมด',
                                    data: <?= json_encode(array_column($total_fees, 'total_amount')) ?>,
                                    backgroundColor: 'rgba(59, 130, 246, 0.5)',
                                    borderColor: 'rgb(59, 130, 246)',
                                    borderWidth: 1
                                }, {
                                    label: 'ยอดรวมที่ชำระแล้ว',
                                    data: <?= json_encode(array_column($paid_fees, 'paid_amount')) ?>,
                                    backgroundColor: 'rgba(16, 185, 129, 0.5)',
                                    borderColor: 'rgb(16, 185, 129)',
                                    borderWidth: 1
                                }, {
                                    label: 'เบี้ยปรับ',
                                    data: <?= json_encode(array_column($total_fees, 'total_penalty')) ?>,
                                    backgroundColor: 'rgba(239, 68, 68, 0.5)',
                                    borderColor: 'rgb(239, 68, 68)',
                                    borderWidth: 1
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                scales: {
                                    y: {
                                        beginAtZero: true,
                                        ticks: {
                                            callback: function(value) {
                                                return value.toLocaleString() + ' บาท';
                                            }
                                        }
                                    }
                                },
                                plugins: {
                                    legend: {
                                        position: 'top',
                                    },
                                    tooltip: {
                                        callbacks: {
                                            label: function(context) {
                                                let label = context.dataset.label || '';
                                                if (label) {
                                                    label += ': ';
                                                }
                                                label += context.parsed.y.toLocaleString() + ' บาท';
                                                return label;
                                            }
                                        }
                                    }
                                }
                            }
                        });
                    </script>

                    <!-- Cards แสดงจำนวนผู้ค้างชำระ -->
                    <div class="col-span-4 bg-white rounded-xl p-6 hover:shadow-md transition-all duration-300">
                        <div class="flex items-center justify-between mb-6">
                            <div class="flex items-center space-x-3">
                                <div class="p-3 bg-gradient-to-r from-green-500 to-green-600 rounded-lg">
                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-800">สถิติผู้ค้างชำระค่าส่วนกลาง</h3>
                                    <p class="text-sm text-gray-500">แยกตามระยะเวลาการค้างชำระ</p>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <!-- ค้างชำระ 3-5 เดือน -->
                            <div class="border rounded-xl p-4 hover:shadow-md transition-all duration-300">
                                <div class="flex items-center justify-between mb-4">
                                    <div class="flex items-center space-x-3">
                                        <div class="p-3 bg-gradient-to-r from-yellow-400 to-yellow-500 rounded-lg">
                                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        </div>
                                        <div>
                                            <h3 class="text-lg font-semibold text-gray-800">3 เดือนขึ้นไป</h3>
                                            <p class="text-2xl font-bold text-yellow-500"><?= $late_stats['3']['count'] ?? 0 ?> คน</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-4 space-y-2 max-h-40 overflow-auto">
                                    <?php if (isset($late_stats['3'])): ?>
                                        <?php foreach ($late_stats['3']['users'] as $username): ?>
                                            <div class="px-3 py-2 bg-yellow-50 rounded-lg">
                                                <span class="text-sm text-yellow-700"><?= htmlspecialchars($username,) ?></span>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- ค้างชำระ 6-11 เดือน -->
                            <div class="border rounded-xl p-4 hover:shadow-md transition-all duration-300">
                                <div class="flex items-center justify-between mb-4">
                                    <div class="flex items-center space-x-3">
                                        <div class="p-3 bg-gradient-to-r from-orange-400 to-orange-500 rounded-lg">
                                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        </div>
                                        <div>
                                            <h3 class="text-lg font-semibold text-gray-800">6 เดือนขึ้นไป</h3>
                                            <p class="text-2xl font-bold text-orange-500"><?= $late_stats['6']['count'] ?? 0 ?> คน</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-4 space-y-2 max-h-40 overflow-auto">
                                    <?php if (isset($late_stats['6'])): ?>
                                        <?php foreach ($late_stats['6']['users'] as $username): ?>
                                            <div class="px-3 py-2 bg-orange-50 rounded-lg">
                                                <span class="text-sm text-orange-700"><?= htmlspecialchars($username) ?></span>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- ค้างชำระ 1 ปีขึ้นไป -->
                            <div class="border rounded-xl p-4 hover:shadow-md transition-all duration-300">
                                <div class="flex items-center justify-between mb-4">
                                    <div class="flex items-center space-x-3">
                                        <div class="p-3 bg-gradient-to-r from-red-400 to-red-500 rounded-lg">
                                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        </div>
                                        <div>
                                            <h3 class="text-lg font-semibold text-gray-800">1 ปีขึ้นไป</h3>
                                            <p class="text-2xl font-bold text-red-500"><?= $late_stats['12']['count'] ?? 0 ?> คน</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-4 space-y-2 max-h-40 overflow-auto">
                                    <?php if (isset($late_stats['12'])): ?>
                                        <?php foreach ($late_stats['12']['users'] as $username): ?>
                                            <div class="px-3 py-2 bg-red-50 rounded-lg">
                                                <span class="text-sm text-red-700"><?= htmlspecialchars($username) ?></span>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6 col-span-4">
                        <!-- กราฟแท่งแสดงสถิติรายเดือน -->
                        <div class="bg-white rounded-xl p-6 hover:shadow-md transition-all duration-300 mb-6"> <!-- เพิ่ม mb-6 -->
                            <div class="flex items-center justify-between mb-6">
                                <div class="flex items-center space-x-3">
                                    <div class="p-3 bg-gradient-to-r from-blue-500 to-blue-600 rounded-lg">
                                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 8v8m-4-5v5m-4-2v2m-2 4h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                    </div>
                                    <div>
                                        <h3 class="text-lg font-semibold text-gray-800">สถิติผู้มาเยือนรายเดือน</h3>
                                        <p class="text-sm text-gray-500">ข้อมูลย้อนหลัง 12 เดือน</p>
                                    </div>
                                </div>
                            </div>
                            <div class="h-[200px]">
                                <canvas id="monthlyVisitorsChart"></canvas>
                            </div>
                        </div>


                        <!-- กราฟวงกลมแสดงสัดส่วนเหตุผลการเข้าพบ -->
                        <div class="bg-white rounded-xl p-6 hover:shadow-md transition-all duration-300 mb-6"> <!-- เพิ่ม mb-6 -->
                            <div class="flex items-center justify-between mb-6">
                                <div class="flex items-center space-x-3">
                                    <div class="p-3 bg-gradient-to-r from-purple-500 to-purple-600 rounded-lg">
                                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z" />
                                        </svg>
                                    </div>
                                    <div>
                                        <h3 class="text-lg font-semibold text-gray-800">สัดส่วนเหตุผลการเข้าพบ</h3>
                                        <p class="text-sm text-gray-500">แยกตามประเภท</p>
                                    </div>
                                </div>
                            </div>
                            <div class="h-[200px]"> <!-- เปลี่ยนจาก h-[200px] เป็น -->
                                <canvas id="visitReasonsChart"></canvas>
                            </div>
                        </div>

                        <!-- สถิติผู้เข้าเยี่ยมที่มาส่งอาหาร -->
                        <div class="bg-white rounded-xl p-6 hover:shadow-md transition-all duration-300 mb-6"> <!-- เพิ่ม mb-6 -->
                            <div class="flex items-center justify-between mb-6">
                                <div class="flex items-center space-x-3">
                                    <div class="p-3 bg-gradient-to-r from-orange-500 to-orange-600 rounded-lg">
                                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                                        </svg>
                                    </div>
                                    <div>
                                        <h3 class="text-lg font-semibold text-gray-800">สถิติผู้เข้าเยี่ยมที่มาส่งอาหาร</h3>
                                        <p class="text-sm text-gray-500">เรียงลำดับตามจำนวนครั้ง</p>
                                    </div>
                                </div>
                            </div>
                            <div class="h-[200px]">
                                <canvas id="foodDeliveryChart"></canvas>
                            </div>
                        </div>

                        <script>
                            // เพิ่ม script สำหรับกราฟแท่งแนวนอน
                            <?php
                            $food_delivery_stats = $conn->query("
    SELECT 
        u.username,
        COUNT(*) as visit_count,
        MAX(v.created_at) as last_visit
    FROM visitors v
    JOIN users u ON v.user_id = u.user_id
    WHERE v.reason_id = 16
    GROUP BY v.user_id, u.username
    ORDER BY visit_count DESC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

                            $labels = array_map(function ($item) {
                                return $item['username'];
                            }, $food_delivery_stats);
                            $data = array_map(function ($item) {
                                return $item['visit_count'];
                            }, $food_delivery_stats);
                            ?>

                            new Chart(document.getElementById('foodDeliveryChart'), {
                                type: 'bar',
                                data: {
                                    labels: <?= json_encode($labels) ?>,
                                    datasets: [{
                                        label: 'จำนวนครั้งที่มาส่งอาหาร',
                                        data: <?= json_encode($data) ?>,
                                        backgroundColor: 'rgba(249, 115, 22, 0.2)',
                                        borderColor: 'rgb(249, 115, 22)',
                                        borderWidth: 1,
                                        borderRadius: 4,
                                        barThickness: 20,
                                    }]
                                },
                                options: {
                                    indexAxis: 'y',
                                    responsive: true,
                                    maintainAspectRatio: false,
                                    plugins: {
                                        legend: {
                                            display: false
                                        },
                                        tooltip: {
                                            callbacks: {
                                                label: function(context) {
                                                    return `${context.parsed.x} ครั้ง`;
                                                }
                                            }
                                        }
                                    },
                                    scales: {
                                        x: {
                                            beginAtZero: true,
                                            grid: {
                                                display: false
                                            },
                                            ticks: {
                                                stepSize: 1
                                            }
                                        },
                                        y: {
                                            grid: {
                                                display: false
                                            }
                                        }
                                    }
                                }
                            });
                        </script>

                        <!-- วันที่มีผู้มาเยือนมากที่สุด -->
                        <div class="bg-white rounded-xl p-6 hover:shadow-md transition-all duration-300">
                            <div class="flex items-center justify-between mb-6">
                                <div class="flex items-center space-x-3">
                                    <div class="p-3 bg-gradient-to-r from-green-500 to-green-600 rounded-lg">
                                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                    </div>
                                    <div>
                                        <h3 class="text-lg font-semibold text-gray-800">วันที่มีผู้เข้ามามากที่สุด</h3>
                                        <p class="text-sm text-gray-500">เรียงตามจำนวนผู้เข้าเยี่ยม</p>
                                    </div>
                                </div>
                            </div>
                            <div class="h-[200px]">
                                <canvas id="peakDaysChart"></canvas>
                            </div>
                        </div>
                    </div>
            </div>



            <script>
                <?php
                    $peak_days = $conn->query("
    SELECT 
        DATE_FORMAT(created_at, '%d/%m/%Y') as formatted_date,
        COUNT(*) as visitor_count,
        DAYNAME(created_at) as day_name
    FROM visitors 
    GROUP BY DATE(created_at), DAYNAME(created_at)
    ORDER BY visitor_count DESC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

                    $thai_days = [
                        'Sunday' => 'วันอาทิตย์',
                        'Monday' => 'วันจันทร์',
                        'Tuesday' => 'วันอังคา',
                        'Wednesday' => 'วันพุธ',
                        'Thursday' => 'วันพฤหัสบดี',
                        'Friday' => 'วันศุกร์',
                        'Saturday' => 'วันเสาร์'
                    ];

                    $labels = array_map(function ($item) use ($thai_days) {
                        return $item['formatted_date'] . ' (' . $thai_days[$item['day_name']] . ')';
                    }, $peak_days);
                    $data = array_map(function ($item) {
                        return $item['visitor_count'];
                    }, $peak_days);
                ?>

                new Chart(document.getElementById('peakDaysChart'), {
                    type: 'bar',
                    data: {
                        labels: <?= json_encode($labels) ?>,
                        datasets: [{
                            label: 'จำนวนผู้เข้าเยี่ยม',
                            data: <?= json_encode($data) ?>,
                            backgroundColor: 'rgba(34, 197, 94, 0.2)',
                            borderColor: 'rgb(34, 197, 94)',
                            borderWidth: 1,
                            borderRadius: 4,
                            barThickness: 30,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        return `${context.parsed.y} คน`;
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                grid: {
                                    display: false
                                },
                                ticks: {
                                    maxRotation: 0,
                                    minRotation: 0
                                }
                            },
                            x: {
                                grid: {
                                    display: false
                                },
                                ticks: {
                                    maxRotation: 0,
                                    minRotation: 0
                                }
                            }
                        }
                    }
                });
            </script>

        <?php else: ?>
            <div class="bg-white shadow-sm p-6 hover:shadow-md transition-shadow duration-300">
                <div class="text-center">
                    <p class="text-xl font-bold text-gray-800">ยังไม่มีข้อมูลให้แสดงในขณะนี้</p>
                </div>
            </div>
        <?php endif; ?>
        </div>
    </div>
    </div>

 <?php include '../../components/footer/footer.php'; ?>

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
      
        // สร้างกราฟแท่งแสดงสถิติรายเดือน
        const monthlyCtx = document.getElementById('monthlyVisitorsChart').getContext('2d');
        new Chart(monthlyCtx, {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_column($monthly_stats, 'month')) ?>,
                datasets: [{
                    label: 'จำนวนผู้มาเยือน',
                    data: <?= json_encode(array_column($monthly_stats, 'visitor_count')) ?>,
                    backgroundColor: 'rgba(59, 130, 246, 0.5)',
                    borderColor: 'rgb(59, 130, 246)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return value.toLocaleString() + ' คน';
                            }
                        }
                    }
                },
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                label += context.parsed.y.toLocaleString() + ' คน';
                                return label;
                            }
                        }
                    }
                }
            }
        });


        const reasonsCtx = document.getElementById('visitReasonsChart').getContext('2d');
        new Chart(reasonsCtx, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode(array_column($reason_stats, 'reason_name')) ?>,
                datasets: [{
                    data: <?= json_encode(array_column($reason_stats, 'count')) ?>,
                    backgroundColor: [
                        'rgba(59, 130, 246, 0.5)',
                        'rgba(16, 185, 129, 0.5)',
                        'rgba(245, 158, 11, 0.5)',
                        'rgba(239, 68, 68, 0.5)',
                        'rgba(139, 92, 246, 0.5)'
                    ],
                    borderColor: [
                        'rgb(59, 130, 246)',
                        'rgb(16, 185, 129)',
                        'rgb(245, 158, 11)',
                        'rgb(239, 68, 68)',
                        'rgb(139, 92, 246)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                        onClick: function(e, legendItem, legend) {
                            const index = legendItem.index;
                            const ci = legend.chart;
                            const meta = ci.getDatasetMeta(0);

                            // ซ่อน/แสดงข้อมูล
                            meta.data[index].hidden = !meta.data[index].hidden;

                            // อัพเดท labels
                            ci.update();
                        },
                        labels: {
                            boxWidth: 15,
                            padding: 15,
                            font: {
                                size: 11
                            },
                            generateLabels: function(chart) {
                                const data = chart.data;
                                if (data.labels.length && data.datasets.length) {
                                    return data.labels.map((label, i) => {
                                        const dataset = data.datasets[0];
                                        const meta = chart.getDatasetMeta(0);
                                        const value = dataset.data[i];
                                        const hidden = meta.data[i] ? meta.data[i].hidden : false;

                                        return {
                                            text: `${label} (${hidden ? '-' : value})`,
                                            fillStyle: dataset.backgroundColor[i],
                                            strokeStyle: dataset.borderColor[i],
                                            lineWidth: 1,
                                            hidden: hidden,
                                            index: i
                                        };
                                    });
                                }
                                return [];
                            }
                        }
                    }
                },
                layout: {
                    padding: {
                        right: 100
                    }
                }
            }
        });

    </script>
</body>

</html>