<?php
session_start();
require_once '../../config/config.php';
require_once '../../includes/auth.php';
include '../../components/menu/Menu.php';
 
// ตรวจสอบสิทธิ์การเข้าถึงหน้า
checkPageAccess(PAGE_MANAGE_VISITORS);
 
// ดึงข้อมูลผู้ใช้ทั้งหมด
$stmt = $conn->query("SELECT user_id, fullname, username FROM users WHERE role_id != 9 ORDER BY username");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
 
// ดึงข้อมูลเหตุผลการเข้าพบ
$stmt = $conn->query("SELECT * FROM visit_reasons ORDER BY reason_id");
$reasons = $stmt->fetchAll(PDO::FETCH_ASSOC);
 
// ฟังก์ชันสำหรับสร้าง SQL condition ตามช่วงเวลา
function getDateCondition($period)
{
    switch ($period) {
        case 'today':
            return "DATE(v.created_at) = CURDATE()";
        case 'this_week':
            return "YEARWEEK(v.created_at, 1) = YEARWEEK(CURDATE(), 1)";
        case 'this_month':
            return "YEAR(v.created_at) = YEAR(CURDATE()) AND MONTH(v.created_at) = MONTH(CURDATE())";
        case 'this_year':
            return "YEAR(v.created_at) = YEAR(CURDATE())";
        default:
            return "";
    }
}
 
// รับค่าการค้นหาจาก URL parameter
$search_period = isset($_GET['period']) ? $_GET['period'] : '';
 
// สร้าง date condition
$date_condition = getDateCondition($search_period);
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
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
 
    <style>
        .ts-control {
            border: 2px solid #e5e7eb;
            border-radius: 0.5rem;
            background-color: #f9fafb;
            transition: border-color 0.2s, background-color 0.2s;
            padding: 0.75rem 1rem 0.75rem 0.75rem !important;
            font-size: 1rem !important;
        }
 
        .ts-control:hover {
            background-color: #ffffff;
            /* สีพื้นหลังเมื่อ hover */
        }
 
        .ts-control:focus-within {
            border-color: #3b82f6;
            /* สี border เมื่อ focus */
        }
 
        .ts-dropdown {
            border-radius: 0.5rem;
            font-size: 1rem !important;
        }
 
        .ts-dropdown .option {
            padding: 0.75rem 1rem !important;
        }
 
        .ts-control .item {
            font-size: 1rem !important;
            line-height: 1rem !important;
        }
 
        .ts-wrapper.single .ts-control::after {
            border-width: 6px !important;
            top: 50% !important;
            right: 1.25rem !important;
        }
 
        /* ปรับแต่ง Tom Select */
        .ts-control.ts-control-user-select {
            border-color: #3b82f6;
            /* สี border เมื่อ focus */
        }
 
        .ts-control.ts-control-user-select:hover {
            background-color: #ffffff;
            /* สีพื้นหลังเมื่อ hover */
        }
 
        .ts-control.ts-control-user-select:focus-within {
            border-color: #3b82f6;
            /* สี border เมื่ focus */
        }
 
        .ts-dropdown.ts-dropdown-user-select {
            border-radius: 0.5rem;
            /* มุมโค้งของ dropdown */
        }
    </style>
 
</head>
 
<body class="bg-modern">
    <div class="flex">
        <div id="sidebar"
            class="fixed top-0 left-0 h-full w-20 transition-all duration-300 ease-in-out bg-gradient-to-b from-blue-600 to-blue-500 shadow-xl">
            <button id="toggleSidebar"
                class="absolute -right-3 bottom-24 bg-blue-800 text-white rounded-full p-1 shadow-lg hover:bg-blue-900">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </button>
            <?php renderMenu(); ?>
        </div>
 
        <div class="flex-1 ml-20">
            <!-- Top Navigation -->
            <nav class="bg-white shadow-sm px-4 sm:px-6 py-3">
                <div class="flex flex-col sm:flex-row space-y-3 sm:space-y-0 sm:items-center sm:justify-between">
                    <h1 class="text-xl sm:text-2xl font-bold text-gray-800">จัดการข้อมูลผู้มาเยือน</h1>
 
                    <!-- แยก Controls เป็น 2 ส่วน -->
                    <div class="flex flex-col sm:flex-row space-y-3 sm:space-y-0 sm:space-x-4">
                        <!-- Date Filter Controls -->
                        <div class="flex flex-wrap gap-2">
                            <a href="?period=today" class="<?= $search_period == 'today' ? 'bg-blue-600 text-white' : 'bg-blue-100 text-blue-800 hover:bg-blue-200' ?> 
                                      px-3 py-1 rounded-full text-sm whitespace-nowrap transition duration-200 ease-in-out 
                                      hover:shadow-md flex items-center space-x-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                <span>วันนี้</span>
                            </a>
                            <a href="?period=this_week" class="<?= $search_period == 'this_week' ? 'bg-blue-600 text-white' : 'bg-blue-100 text-blue-800 hover:bg-blue-200' ?> 
                                      px-3 py-1 rounded-full text-sm whitespace-nowrap transition duration-200 ease-in-out 
                                      hover:shadow-md flex items-center space-x-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                </svg>
                                <span>สัปดาห์นี้</span>
                            </a>
                            <a href="?period=this_month" class="<?= $search_period == 'this_month' ? 'bg-blue-600 text-white' : 'bg-blue-100 text-blue-800 hover:bg-blue-200' ?> 
                                      px-3 py-1 rounded-full text-sm whitespace-nowrap transition duration-200 ease-in-out 
                                      hover:shadow-md flex items-center space-x-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                <span>เดือนนี้</span>
                            </a>
                            <a href="?period=this_year" class="<?= $search_period == 'this_year' ? 'bg-blue-600 text-white' : 'bg-blue-100 text-blue-800 hover:bg-blue-200' ?> 
                                      px-3 py-1 rounded-full text-sm whitespace-nowrap transition duration-200 ease-in-out 
                                      hover:shadow-md flex items-center space-x-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span>ปีนี้</span>
                            </a>
                            <button onclick="toggleExitedVisitors()" 
                                class="<?= isset($_GET['show_exited']) ? 'bg-green-600 text-white' : 'bg-green-100 text-green-800 hover:bg-green-200' ?> 
                                px-3 py-1 rounded-full text-sm whitespace-nowrap transition duration-200 ease-in-out 
                                hover:shadow-md flex items-center space-x-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                        d="M5 13l4 4L19 7"/>
                                </svg>
                                <span>ดูรายชื่อที่ออกแล้ว</span>
                            </button>
                            <a href="?" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-3 py-1 rounded-full text-sm 
                                          whitespace-nowrap transition duration-200 ease-in-out hover:shadow-md 
                                          flex items-center space-x-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12" />
                                </svg>
                                <span>ล้างการค้นหา</span>
                            </a>
                        </div>
 
                        <!-- Add Button -->
                        <button onclick="openAddVisitorModal()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg 
                                       flex items-center justify-center sm:justify-start whitespace-nowrap
                                       transition duration-200 ease-in-out transform hover:scale-105 hover:shadow-lg">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 4v16m8-8H4" />
                            </svg>
                            <span class="font-medium">เพิ่มรายการ</span>
                        </button>
                    </div>
                </div>
            </nav>
 
            <!-- ตารางแสดงข้อมูล -->
            <div class="p-6">
 
 
                <!-- ปรับปรุงตารางให้ responsive -->
                <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                    <div class="overflow-x-auto">
                        <!-- เพิ่ม responsive wrapper -->
                        <div class="min-w-full lg:w-auto">
                            <!-- สำหรับหน้าจอขนาดใหญ่ -->
                            <table class="min-w-full divide-y divide-gray-200 hidden lg:table">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col"
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            วันที่</th>
                                        <th scope="col"
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            ชื่อผู้มาเยือน</th>
                                        <th scope="col"
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            เลขทะเบียนรถ</th>
                                        <th scope="col"
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            เหตุผล</th>
                                        <th scope="col"
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            ไปพบ</th>
                                        <th scope="col"
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            เลขที่บ้าน</th>
                                        <th scope="col"
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            การกระทำ</th>
                                        <?php if ($_SESSION['role_id'] == 1 || $_SESSION['role_id'] == 7): ?>
                                            <th scope="col"
                                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                รายงานโดย</th>
                                        <?php endif; ?>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php
                                    $sql = "SELECT v.*, u.fullname, u.username, vr.reason_name, 
                                            (SELECT username FROM users WHERE user_id = v.created_by) as reporter_username
                                            FROM visitors v 
                                            LEFT JOIN users u ON v.user_id = u.user_id 
                                            LEFT JOIN visit_reasons vr ON v.reason_id = vr.reason_id 
                                            WHERE 1=1 ";
 
                                    // เพิ่มเงื่อนไขสำหรับเจ้าหน้าที่ user_id 518 และ 519
                                    if (in_array($_SESSION['user_id'], [518, 519])) {
                                        $sql .= "AND v.created_by = " . $_SESSION['user_id'] . " ";
                                    }
 
                                    // เพิ่มเงื่อนไขการแสดงผล
                                    if (isset($_GET['show_exited'])) {
                                        $sql .= "AND v.exit_at IS NOT NULL ";
                                    } else {
                                        $sql .= "AND v.exit_at IS NULL ";
                                    }
 
                                    // เพิ่ม date condition ถ้ามีการค้นหา
                                    if ($date_condition) {
                                        $sql .= " AND $date_condition ";
                                    }
 
                                    $sql .= "ORDER BY v.created_at DESC";
 
                                    $stmt = $conn->query($sql);
                                    while ($visitor = $stmt->fetch(PDO::FETCH_ASSOC)):
                                        ?>
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                เข้า: <?= date('d/m/Y H:i', strtotime($visitor['created_at'])) ?>
                                                <?php if (!empty($visitor['exit_at'])): ?>
                                                    <br>
                                                    <span class="text-red-600">
                                                        ออก: <?= date('d/m/Y H:i', strtotime($visitor['exit_at'])) ?>
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm font-medium text-gray-900">
                                                    <?= htmlspecialchars($visitor['visitor_name']) ?>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?= htmlspecialchars($visitor['car_registration']) ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?= $visitor['reason_id'] == 'other' ?
                                                    htmlspecialchars($visitor['other_reason']) :
                                                    htmlspecialchars($visitor['reason_name']) ?>
                                            </td>
                                            <?php if ($_SESSION['role_id'] == 1 || $_SESSION['role_id'] == 7) { ?>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                    <?= htmlspecialchars($visitor['fullname']) ?>
                                                </td>
                                            <?php } else { ?>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
 
                                                </td>
                                            <?php } ?>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?= htmlspecialchars($visitor['username']) ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                                <button
                                                    onclick="viewIdCard('<?= htmlspecialchars($visitor['id_card_image']) ?>')"
                                                    class="bg-blue-200 text-blue-700 rounded-md hover:bg-blue-300 transition-colors px-2 py-1">
                                                    ดูบัตร
                                                </button>
                                                <button
                                                    onclick="viewCarRegistration('<?= htmlspecialchars($visitor['car_registration_image']) ?>')"
                                                    class="bg-blue-200 text-blue-700 rounded-md hover:bg-blue-300 transition-colors px-2 py-1">
                                                    ดูทะเบียนรถ
                                                </button>
                                                <button onclick='openEditVisitorModal(<?= json_encode([
                                                    "visitor_id" => $visitor["visitor_id"],
                                                    "visitor_name" => $visitor["visitor_name"],
                                                    "car_registration" => $visitor["car_registration"],
                                                    "user_id" => $visitor["user_id"],
                                                    "reason_id" => $visitor["reason_id"],
                                                    "other_reason" => $visitor["other_reason"]
                                                ]) ?>)'
                                                    class="text-green-600 hover:text-green-900 bg-green-200 rounded-md hover:bg-green-300 transition-colors px-2 py-1">แก้ไข</button>
                                                <?php if (empty($visitor['exit_time'])): ?>
                                                    <button onclick="confirmExit(<?= $visitor['visitor_id'] ?>)" 
                                                        class="text-red-600 hover:text-red-900 bg-red-200 rounded-md hover:bg-red-300 transition-colors px-2 py-1">ออก</button>
                                                <?php endif; ?>
                                            </td>
                                            <?php if ($_SESSION['role_id'] == 1 || $_SESSION['role_id'] == 7): ?>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                    <span
                                                        class="font-medium"><?= htmlspecialchars($visitor['reporter_username']) ?></span>
                                                </td>
                                            <?php endif; ?>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
 
                            <!-- สำหรับหน้าจอขนาดเล็ก -->
                            <div class="lg:hidden">
                                <?php
                                // เพิ่มเงื่อนไขสำหรับเจ้าหน้าที่ user_id 518 และ 519
                                $user_condition = "";
                                if (in_array($_SESSION['user_id'], [518, 519])) {
                                    $user_condition = "AND v.created_by = " . $_SESSION['user_id'] . " ";
                                }
 
                                $sql = "SELECT v.*, u.fullname, u.username, vr.reason_name, 
                                (SELECT username FROM users WHERE user_id = v.created_by) as reporter_username
                                FROM visitors v 
                                LEFT JOIN users u ON v.user_id = u.user_id 
                                LEFT JOIN visit_reasons vr ON v.reason_id = vr.reason_id 
                                WHERE 1=1 ";
 
                                // เพิ่มเงื่อนไขการแสดงผล
                                if (isset($_GET['show_exited'])) {
                                    $sql .= "AND v.exit_at IS NOT NULL ";
                                } else {
                                    $sql .= "AND v.exit_at IS NULL ";
                                }
 
                                // เพิ่ม date condition ถ้ามีการค้นหา
                                if ($date_condition) {
                                    $sql .= " AND $date_condition ";
                                }
 
                                $sql .= "ORDER BY v.created_at DESC";
 
                                $stmt = $conn->query($sql);
                                while ($visitor = $stmt->fetch(PDO::FETCH_ASSOC)):
                                    ?>
                                    <div class="border-b p-4 space-y-2">
                                        <div class="flex justify-between items-start">
                                            <div class="text-sm font-medium text-gray-900">
                                                <?= htmlspecialchars($visitor['visitor_name']) ?>
                                            </div>
                                            <div class="text-sm text-gray-500">
                                                เข้า: <?= date('d/m/Y H:i', strtotime($visitor['created_at'])) ?>
                                                <?php if (!empty($visitor['exit_at'])): ?>
                                                    <br>
                                                    <span class="text-red-600">
                                                        ออก: <?= date('d/m/Y H:i', strtotime($visitor['exit_at'])) ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="grid grid-cols-2 gap-2 text-sm">
                                            <div>
                                                <span class="text-gray-500">เลขทะเบียนรถ:</span>
                                                <span
                                                    class="font-medium"><?= htmlspecialchars($visitor['car_registration']) ?></span>
                                            </div>
                                            <div>
                                                <span class="text-gray-500">เหตุผล:</span>
                                                <span class="font-medium">
                                                    <?= $visitor['reason_id'] == 'other' ?
                                                        htmlspecialchars($visitor['other_reason']) :
                                                        htmlspecialchars($visitor['reason_name']) ?>
                                                </span>
                                            </div>
                                            <div>
                                                <span class="text-gray-500">ไปพบ:</span>
                                                <?php if ($_SESSION['role_id'] == 1 || $_SESSION['role_id'] == 7) { ?>
                                                    <span
                                                        class="font-medium"><?= htmlspecialchars($visitor['fullname']) ?></span>
                                                <?php } else { ?>
                                                    <span> </span>
                                                <?php } ?>
                                            </div>
                                            <div>
                                                <span class="text-gray-500">บ้านเลขที่:</span>
                                                <span
                                                    class="font-medium"><?= htmlspecialchars($visitor['username']) ?></span>
                                            </div>
                                        </div>
                                        <div class="flex space-x-3 pt-2">
                                            <button
                                                onclick="viewIdCard('<?= htmlspecialchars($visitor['id_card_image']) ?>')"
                                                class="bg-blue-200 text-blue-700 rounded-md hover:bg-blue-300 transition-colors px-2 py-1 text-sm">
                                                ดูบัตร
                                            </button>
                                            <button
                                                onclick="viewCarRegistration('<?= htmlspecialchars($visitor['car_registration_image']) ?>')"
                                                class="bg-blue-200 text-blue-700 rounded-md hover:bg-blue-300 transition-colors px-2 py-1 text-sm">
                                                ดูทะเบียน
                                            </button>
                                            <button onclick='openEditVisitorModal(<?= json_encode([
                                                "visitor_id" => $visitor["visitor_id"],
                                                "visitor_name" => $visitor["visitor_name"],
                                                "car_registration" => $visitor["car_registration"],
                                                "user_id" => $visitor["user_id"],
                                                "reason_id" => $visitor["reason_id"],
                                                "other_reason" => $visitor["other_reason"]
                                            ]) ?>)'
                                                class="text-green-600 hover:text-green-900 bg-green-200 rounded-md hover:bg-green-300 transition-colors px-2 py-1 text-sm">แก้ไข</button>
                                            <?php if (empty($visitor['exit_time'])): ?>
                                                <button onclick="confirmExit(<?= $visitor['visitor_id'] ?>)"
                                                    class="text-red-600 hover:text-red-900 bg-red-200 rounded-md hover:bg-red-300 transition-colors px-2 py-1 text-sm">ออก</button>
                                            <?php endif; ?>
                                        </div>
                                        <?php if ($_SESSION['role_id'] == 1 || $_SESSION['role_id'] == 7): ?>
                                            <div class="grid grid-cols-2 gap-2 text-sm">
                                                <div>
                                                    <span class="text-gray-500">รายงานโดย:</span>
                                                    <span
                                                        class="font-medium"><?= htmlspecialchars($visitor['reporter_username']) ?></span>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
 
    <!-- ปรัปรุง Modal ให้ responsive -->
    <div id="addVisitorModal" class="hidden fixed inset-0 bg-black bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative min-h-screen flex items-center justify-center p-4">
            <div class="relative w-full max-w-6xl bg-white rounded-lg shadow-2xl mx-auto">
                <!-- ส่วนหัว Modal -->
                <div class="bg-gradient-to-r from-blue-600 to-blue-700 rounded-t-lg">
                    <div class="flex justify-between items-center p-6">
                        <h3 class="text-2xl font-semibold text-white" id="modalTitle">เพิ่มผู้มาเยือน</h3>
                        <button onclick="closeAddVisitorModal()"
                            class="text-white hover:text-gray-200 transition-colors">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>
 
                <!-- ส่วนเนื้อหา Modal -->
                <div class="p-4 sm:p-8">
                    <form id="visitorForm" onsubmit="return validateForm()" method="POST" enctype="multipart/form-data">
                        <input type="hidden" id="formAction" name="formAction" value="add">
                        <input type="hidden" id="visitor_id" name="visitor_id" value="">
 
                        <!-- รวมข้อมูลเป็นแผ่นเดียว -->
                        <div class="bg-white p-4 sm:p-6 rounded-xl shadow-lg border border-gray-100 space-y-4">
                            <!-- Modified grid for image inputs -->
                            <div class="grid grid-cols-2 sm:grid-cols-1 gap-4">
                                <div class="relative">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">รูปบัตรประชาชน *</label>
                                    <div class="relative h-16">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none z-10">
                                            <svg class="h-6 w-6 text-gray-400 flex-shrink-0 min-w-[1.5rem]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                        </div>
                                        <input type="file" id="id_card_image" name="id_card_image" accept="image/*" onchange="updateFileName(this, 'id_card_filename')" class="hidden">
                                        <div onclick="document.getElementById('id_card_image').click()" class="h-full pl-12 w-full rounded-lg border-2 border-gray-200 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 transition-all duration-200 bg-gray-50 hover:bg-white flex items-center cursor-pointer text-base">
                                            <span id="id_card_filename" class="truncate text-gray-500">เลือกไฟล์</span>
                                        </div>
                                    </div>
                                </div>
 
                                <div class="relative">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">รูปทะเบียนรถ *</label>
                                    <div class="relative h-16">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none z-10">
                                            <svg class="h-6 w-6 text-gray-400 flex-shrink-0 min-w-[1.5rem]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                        </div>
                                        <input type="file" id="car_registration_image" name="car_registration_image" accept="image/*" onchange="updateFileName(this, 'car_registration_filename')" class="hidden">
                                        <div onclick="document.getElementById('car_registration_image').click()" class="h-full pl-12 w-full rounded-lg border-2 border-gray-200 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 transition-all duration-200 bg-gray-50 hover:bg-white flex items-center cursor-pointer text-base">
                                            <span id="car_registration_filename" class="truncate text-gray-500">เลือกไฟล์</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
 
                            <div class="relative hidden">
                                <label class="block text-sm font-medium text-gray-700 mb-1">ชื่อผู้มาเยือน</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                        </svg>
                                    </div>
                                    <input type="text" id="visitor_name" name="visitor_name" class="pl-12 w-full h-12 rounded-lg border-2 border-gray-200 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 transition-all duration-200 bg-gray-50 hover:bg-white">
                                </div>
                            </div>
 
                            <div class="relative">
                                <label class="block text-sm font-medium text-gray-700 mb-1">เลขทะเบียนรถ</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 4H5a2 2 0 00-2 2v12a2 2 0 002 2h14a2 2 0 002-2V6a2 2 0 00-2-2z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9h8M8 13h4" />
                                        </svg>
                                    </div>
                                    <input type="text" id="car_registration" name="car_registration" class="pl-12 w-full h-12 rounded-lg border-2 border-gray-200 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 transition-all duration-200 bg-gray-50 hover:bg-white">
                                </div>
                            </div>
 
                            <div class="relative mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">ผู้มาพบ</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none z-10">
                                        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                        </svg>
                                    </div>
                                    <select id="user_id" name="user_id" class="block w-full pl-10 pr-10 py-3 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-lg shadow-sm bg-white hover:bg-gray-50 transition-colors duration-200">
                                        <option value="">เลือกผู้มาพบ</option>
                                        <?php foreach ($users as $user): ?>
                                            <option value="<?= $user['user_id'] ?>">
                                                <?php if ($_SESSION['role_id'] == 1 || $_SESSION['role_id'] == 7) { ?>
                                                    <?= htmlspecialchars($user['fullname']) ?>     <?php } ?>
                                                (<?= htmlspecialchars($user['username']) ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </div>
                                </div>
                            </div>
 
                            <div class="relative">
                                <label class="block text-sm font-medium text-gray-700 mb-1">เหตุผลการเข้าพบ *</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                        </svg>
                                    </div>
                                    <select id="reason_id" name="reason_id" class="pl-12 w-full h-12 rounded-lg border-2 border-gray-200 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 transition-all duration-200 bg-gray-50 hover:bg-white">
                                        <option value="">เลือกเหตุผล</option>
                                        <?php foreach ($reasons as $reason): ?>
                                            <option value="<?= $reason['reason_id'] ?>">
                                                <?= htmlspecialchars($reason['reason_name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                        <option value="other">อื่นๆ</option>
                                    </select>
                                </div>
                            </div>
 
                            <div id="other_reason_container" class="relative hidden">
                                <label class="block text-sm font-medium text-gray-700 mb-1">ระบุเหตุผลอื่นๆ *</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </div>
                                    <input type="text" id="other_reason" name="other_reason" class="pl-12 w-full h-12 rounded-lg border-2 border-gray-200 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 transition-all duration-200 bg-gray-50 hover:bg-white">
                                </div>
                            </div>
                        </div>
 
                        <!-- ปรัปรุง button layout -->
                        <div class="grid grid-cols-2 sm:flex sm:flex-row sm:justify-end gap-3 sm:gap-4 pt-6 border-t">
                            <button type="button" onclick="confirmCancel()" class="w-full sm:w-auto px-6 py-3 text-base font-medium text-gray-700 bg-white border-2 border-gray-300 rounded-lg hover:bg-gray-50 hover:border-gray-400 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200">
                                ยกเลิก
                            </button>
                            <button type="submit" class="w-full sm:w-auto px-6 py-3 text-base font-medium text-white bg-gradient-to-r from-blue-600 to-blue-700 rounded-lg hover:from-blue-700 hover:to-blue-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200 shadow-lg">
                                บันทึก
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
 
    <!-- เพิ่ม JavaScript สำหรับ sidebar toggle และ notifications เหมือน manage_users.php -->
    <script>
        function openAddVisitorModal() {
            document.getElementById('modalTitle').textContent = 'เพิ่มผู้มาเยือน';
            document.getElementById('formAction').value = 'add';
            document.getElementById('visitorForm').reset();
 
            // ถ้ามี instance เก่าให้ทำลายก่อน
            if (window.userSelect) {
                window.userSelect.destroy();
            }
 
            // สร้าง instance ใหม่
            window.userSelect = initializeUserSelect('#user_id');
 
            document.getElementById('addVisitorModal').classList.remove('hidden');
        }
 
        function closeAddVisitorModal() {
            if (window.userSelect) {
                window.userSelect.destroy();
            }
            document.getElementById('addVisitorModal').classList.add('hidden');
        }
 
        function openEditVisitorModal(visitor) {
            document.getElementById('modalTitle').textContent = 'แก้ไขข้อมูลผู้มาเยือน';
            document.getElementById('formAction').value = 'edit';
            document.getElementById('visitor_id').value = visitor.visitor_id;
 
            // กำหนดค่าให้กับฟิลด์ต่างๆ
            document.getElementById('visitor_name').value = visitor.visitor_name || '';
            document.getElementById('car_registration').value = visitor.car_registration || '';
 
            // ถ้ามี instance เก่าให้ทำลายก่อน
            if (window.userSelect) {
                window.userSelect.destroy();
            }
 
            // สร้าง instance ใหม่และตั้งค่า
            window.userSelect = initializeUserSelect('#user_id');
            if (visitor.user_id) {
                window.userSelect.setValue(visitor.user_id);
            }
 
            // จัดการกับ reason_id และ other_reason
            const reasonSelect = document.getElementById('reason_id');
            reasonSelect.value = visitor.reason_id;
 
            const otherReasonContainer = document.getElementById('other_reason_container');
            const otherReasonInput = document.getElementById('other_reason');
 
            if (visitor.reason_id === 'other') {
                otherReasonContainer.classList.remove('hidden');
                otherReasonInput.value = visitor.other_reason || '';
                otherReasonInput.required = true;
            } else {
                otherReasonContainer.classList.add('hidden');
                otherReasonInput.value = '';
                otherReasonInput.required = false;
            }
 
            // แสดง Modal
            document.getElementById('addVisitorModal').classList.remove('hidden');
        }
 
        document.getElementById('visitorForm').addEventListener('submit', function (e) {
            e.preventDefault();
            const formData = new FormData(this);
 
            fetch('../../actions/visitor/process_visitor.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        alert(data.message);
                        location.reload();
                    } else {
                        alert(data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('เกิดข้อผิดพลาดในการส่งข้อมูล');
                });
        });
 
        document.getElementById('reason_id').addEventListener('change', function () {
            const otherReasonContainer = document.getElementById('other_reason_container');
            const otherReasonInput = document.getElementById('other_reason');
 
            if (this.value === 'other') {
                otherReasonContainer.classList.remove('hidden');
                otherReasonInput.required = true;
            } else {
                otherReasonContainer.classList.add('hidden');
                otherReasonInput.required = false;
                otherReasonInput.value = '';
            }
        });
 
        function validateForm() {
            // ตรวจสอบเฉพาะว่าฟอร์มถูก submit หรือไม่
            return true;
        }
 
        function viewIdCard(imagePath) {
            const modal = document.createElement('div');
            modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
            modal.innerHTML = `
                <div class="relative bg-white rounded-lg max-w-2xl w-full mx-4 max-h-[90vh] flex flex-col">
                    <!-- ่วนหั Modal -->
                    <div class="bg-gradient-to-r from-blue-600 to-blue-700 rounded-t-lg p-4 flex justify-between items-center">
                        <h3 class="text-lg font-medium text-white">รูปบัตรประชาชน</h3>
                        <button onclick="this.closest('.fixed').remove()" 
                            class="text-white hover:text-gray-200 transition-colors">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
 
                    <!-- ส่วนเนื้อหา Modal -->
                    <div class="overflow-y-auto p-4 flex-1">
                        <img src="${imagePath}" alt="บัตรประชาชน" class="w-full h-auto">
                    </div>
 
                    <!-- ส่วนท้าย Modal -->
                    <div class="border-t p-4">
                        <button onclick="this.closest('.fixed').remove()" 
                            class="w-full px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-800 rounded-lg transition-colors">
                            ปิด
                        </button>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);
 
            // ปิด Modal เมื่อคลิกพื้นหลัง
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    modal.remove();
                }
            });
        }
 
        function viewCarRegistration(imagePath) {
            const modal = document.createElement('div');
            modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
            modal.innerHTML = `
                <div class="relative bg-white rounded-lg max-w-2xl w-full mx-4 max-h-[90vh] flex flex-col">
                    <!-- ส่วนหัว Modal -->
                    <div class="bg-gradient-to-r from-blue-600 to-blue-700 rounded-t-lg p-4 flex justify-between items-center">
                        <h3 class="text-lg font-medium text-white">รูปทะเบียนรถ</h3>
                        <button onclick="this.closest('.fixed').remove()" 
                            class="text-white hover:text-gray-200 transition-colors">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
 
                    <!-- ส่วนเนื้อหา Modal -->
                    <div class="overflow-y-auto p-4 flex-1">
                        <img src="${imagePath}" alt="ทะเบียนรถ" class="w-full h-auto">
                    </div>
 
                    <!-- ส่วนท้าย Modal -->
                    <div class="border-t p-4">
                        <button onclick="this.closest('.fixed').remove()" 
                            class="w-full px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-800 rounded-lg transition-colors">
                            ปิด
                        </button>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);
 
            // ปิด Modal เมื่อคลิกพื้นหลัง
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    modal.remove();
                }
            });
        }
 
        // Toggle Sidebar
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
 
        // เพิ่มโค้ดสำหรับ notifications
        function toggleNotifications() {
            const dropdown = document.getElementById('notificationDropdown');
            dropdown.classList.toggle('hidden');
 
            document.addEventListener('click', function closeDropdown(e) {
                if (!e.target.closest('#notificationDropdown') && !e.target.closest('button')) {
                    dropdown.classList.add('hidden');
                    document.removeEventListener('click', closeDropdown);
                }
            });
        }
 
        // เพิ่มฟังก์ชันสำหรับ initialize Tom Select
        function initializeUserSelect(selectElement) {
            return new TomSelect(selectElement, {
                create: false,
                sortField: {
                    field: "text",
                    direction: "asc"
                },
                render: {
                    option: function (data, escape) {
                        return `<div class="py-2 px-3">
                            <div class="font-medium">${escape(data.text)}</div>
                        </div>`;
                    },
                    item: function (data, escape) {
                        return `<div>${escape(data.text)}</div>`;
                    }
                }
            });
        }
 
        // เพิ่มฟังก์ชันสำหรับแปลงขนาดไฟล์เป็น MB
        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }
 
        // เพิ่มฟังก์ชันสำหรับลดขนาดรูปภาพ
        function compressImage(file, maxWidth, maxHeight, quality = 0.7) {
            return new Promise((resolve, reject) => {
                const reader = new FileReader();
                reader.readAsDataURL(file);
                reader.onload = function (event) {
                    const img = new Image();
                    img.src = event.target.result;
                    img.onload = function () {
                        const canvas = document.createElement('canvas');
                        let width = img.width;
                        let height = img.height;
 
                        // คำนวณขนาดใหม่โดยรักษาอัตราส่วน
                        if (width > height) {
                            if (width > maxWidth) {
                                height = Math.round((height * maxWidth) / width);
                                width = maxWidth;
                            }
                        } else {
                            if (height > maxHeight) {
                                width = Math.round((width * maxHeight) / height);
                                height = maxHeight;
                            }
                        }
 
                        canvas.width = width;
                        canvas.height = height;
 
                        const ctx = canvas.getContext('2d');
                        ctx.drawImage(img, 0, 0, width, height);
 
                        // แปลง canvas เป็น Blob
                        canvas.toBlob((blob) => {
                            // สร้าง File ใหม่จาก Blob
                            const compressedFile = new File([blob], file.name, {
                                type: 'image/jpeg',
                                lastModified: Date.now()
                            });
                            resolve(compressedFile);
                        }, 'image/jpeg', quality);
                    };
                    img.onerror = reject;
                };
                reader.onerror = reject;
            });
        }
 
        // ปรับปรุง event listener สำหรับการอัพโหลดรูปภาพ
        document.getElementById('id_card_image').addEventListener('change', async function (e) {
            const file = e.target.files[0];
            if (file) {
                try {
                    // ลดขนาดรูปภาพ (maxWidth: 1024px, maxHeight: 1024px, quality: 0.7)
                    const compressedFile = await compressImage(file, 1024, 1024, 0.7);
 
                    // สร้าง FileList ใหม่ที่มีไฟล์ที่ถูกบีบอัดแล้ว
                    const dataTransfer = new DataTransfer();
                    dataTransfer.items.add(compressedFile);
                    this.files = dataTransfer.files;
 
                    // แสดงขนาดไฟล์ใหม่
                    // document.getElementById('id_card_size').textContent = `ขนาดไฟล์: ${formatFileSize(compressedFile.size)}`;
                } catch (error) {
                    console.error('Error compressing image:', error);
                    alert('เกิดข้อผิดพลาดในการบีบอัดรูปภาพ');
                }
            } else {
                document.getElementById('id_card_size').textContent = '';
            }
        });
 
        document.getElementById('car_registration_image').addEventListener('change', async function (e) {
            const file = e.target.files[0];
            if (file) {
                try {
                    // ลดขนาดรูปภาพ (maxWidth: 1024px, maxHeight: 1024px, quality: 0.7)
                    const compressedFile = await compressImage(file, 1024, 1024, 0.7);
 
                    // สร้าง FileList ใหม่ที่มีไฟล์ที่ถูกบีบอัดแล้ว
                    const dataTransfer = new DataTransfer();
                    dataTransfer.items.add(compressedFile);
                    this.files = dataTransfer.files;
 
                    // แสดงขนาดไฟล์ใหม่
                    // document.getElementById('car_registration_size').textContent = `ขนาดไฟล์: ${formatFileSize(compressedFile.size)}`;
                } catch (error) {
                    console.error('Error compressing image:', error);
                    alert('เกิดข้อผิดพลาดในการบีบอัดรูปภาพ');
                }
            } else {
                document.getElementById('car_registration_size').textContent = '';
            }
        });
 
        function updateFileName(input, targetId) {
            const filename = input.files[0].name;
            document.getElementById(targetId).textContent = filename;
        }
 
        function confirmCancel() {
            if (confirm('คุณต้องการยกเลิกการทำรายการนี้ใช่หรือไม่?')) {
                closeAddVisitorModal();
            }
        }
 
        function confirmExit(visitorId) {
            if (confirm('ยืนยันว่าผู้ที่เข้ามาออกไปแล้วหรือไม่?')) {
                fetch('../../actions/visitor/process_visitor.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `formAction=exit&visitor_id=${visitorId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        alert(data.message);
                        location.reload();
                    } else {
                        alert(data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('เกิดข้อผิดพลาดในการบันทึกข้อมูล');
                });
            }
        }
 
        function toggleExitedVisitors() {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('show_exited')) {
                urlParams.delete('show_exited');
            } else {
                urlParams.set('show_exited', '1');
            }
            window.location.href = `${window.location.pathname}?${urlParams.toString()}`;
        }
    </script>
</body>
 
</html>