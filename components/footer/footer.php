<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../actions/recordPageView.php';

// บันทึกการเข้าชมหน้าปัจจุบัน
$current_page = $_SERVER['REQUEST_URI'];
$page_name = basename($_SERVER['PHP_SELF']);
recordPageView($current_page, $page_name);

// ฟังก์ชันสำหรับดึงจำนวนผู้เข้าชม
function getVisitorCount($period, $page_path = null)
{
    global $conn;

    $conditions = [];
    $params = [];

    // กำหนดเงื่อนไขตามช่วงเวลา
    switch ($period) {
        case 'today':
            $conditions[] = "DATE(viewed_at) = CURDATE()";
            break;
        case 'week':
            $conditions[] = "YEARWEEK(viewed_at) = YEARWEEK(CURDATE())";
            break;
        case 'month':
            $conditions[] = "YEAR(viewed_at) = YEAR(CURDATE()) AND MONTH(viewed_at) = MONTH(CURDATE())";
            break;
        case 'year':
            $conditions[] = "YEAR(viewed_at) = YEAR(CURDATE())";
            break;
    }

    // เพิ่มเงื่อนไขหน้าปัจจุบัน (ถ้ามี)
    if ($page_path) {
        $conditions[] = "page_path = ?";
        $params[] = $page_path;
    }

    $where_clause = $conditions ? "WHERE " . implode(" AND ", $conditions) : "";

    $sql = "SELECT COUNT(DISTINCT visitor_ip) as visitor_count FROM page_views " . $where_clause;
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetch(PDO::FETCH_ASSOC)['visitor_count'] ?? 0;
}

// ดึงขำนวนผู้เข้าชมตามช่วงเวลาต่างๆ
$today_visitors = getVisitorCount('today');
$week_visitors = getVisitorCount('week');
$month_visitors = getVisitorCount('month');
$year_visitors = getVisitorCount('year');
$current_page_visitors = getVisitorCount('today', $current_page);
?>

<style>
    /* ใส่ที่ body */
    body {
        min-height: 100vh;
        display: flex;
        flex-direction: column;
        margin: 0;
    }

    /* ใส่ที่ wrapper หลักของ content */
    main,
    .main-content {
        flex: 1 0 auto;
    }

    /* ส่วนของ footer */
    footer {
        flex-shrink: 0;
    }
</style>

<footer class="<?php echo isset($_SESSION['user_id']) ? 'ml-20' : ''; ?> bg-gradient-to-r from-blue-900 via-blue-900 to-blue-900 text-white border-t border-gray-800">
    <div class="container mx-auto px-4 py-3">
        <!-- ส่วนหลัก -->
        <div class="flex flex-col md:flex-row items-center justify-between gap-4">
            <!-- โลโก้และข้อมูลติดต่อ -->
            <div class="flex items-center gap-6 w-full md:w-auto">
                <img src="https://www.thippirom.com/th/images/logo-thippirom-dark.svg"
                    alt="Logo"
                    class="h-14 w-auto filter brightness-0 invert hover:scale-105 transition-transform duration-300 z-0">
                <div class="text-gray-300 hover:text-white transition-colors duration-300 text-xs">
                    <div class="flex flex-wrap items-center gap-4">
                        <span class="flex items-center gap-1">
                            <i class="fas fa-building text-blue-400"></i>
                            หมู่บ้านทิพย์พิรมย์ 5
                        </span>
                        <span class="flex items-center gap-1">
                            <i class="fas fa-map-marker-alt text-red-400"></i>
                            ตำบลหนองผึ้ง อำเภอสารภี จังหวัดเชียงใหม่ 50140
                        </span>
                        <span class="flex items-center gap-1">
                            <i class="fas fa-bank text-green-400"></i>
                            176-2-91114-7
                        </span>
                        <span class="flex items-center gap-1">
                            <i class="fas fa-envelope text-yellow-400"></i>
                            info@thippirom.com
                        </span>
                    </div>
                </div>
            </div>

            <!-- สถิติผู้เข้าชม -->
            <div class="grid grid-cols-3 lg:flex items-center lg:gap-4 gap-2 text-xs bg-white/5 rounded-full px-4 py-2 backdrop-blur-sm">
                <div class="flex items-center gap-2 text-gray-300 hover:text-white transition-colors duration-300">
                    <i class="fas fa-calendar-day text-blue-400"></i>
                    <span>วันนี้: <b><?php echo number_format($today_visitors); ?></b></span>
                </div>
                <div class="flex items-center gap-2 text-gray-300 hover:text-white transition-colors duration-300">
                    <i class="fas fa-calendar-week text-green-400"></i>
                    <span>สัปดาห์: <b><?php echo number_format($week_visitors); ?></b></span>
                </div>
                <div class="flex items-center gap-2 text-gray-300 hover:text-white transition-colors duration-300">
                    <i class="fas fa-calendar-alt text-yellow-400"></i>
                    <span>เดือน: <b><?php echo number_format($month_visitors); ?></b></span>
                </div>
                <div class="flex items-center gap-2 text-gray-300 hover:text-white transition-colors duration-300">
                    <i class="fas fa-calendar text-red-400"></i>
                    <span>ปี: <b><?php echo number_format($year_visitors); ?></b></span>
                </div>
                <div class="flex items-center gap-2 text-blue-300 hover:text-blue-200">
                    <i class="fas fa-eye"></i>
                    <span>หน้านี้: <b><?php echo number_format($current_page_visitors); ?></b></span>
                </div>
            </div>
        </div>

        <!-- ลิขสิทธิ์ -->
        <div class="text-center text-[10px] text-gray-400 mt-2">
            <p class="hover:text-white transition-colors duration-300">
                &copy; <?php echo date('Y'); ?> หมู่บ้านทิพย์พิรมย์. สงวนลิขสิทธิ์.
            </p>
        </div>
    </div>
</footer>

<!-- เพิ่ม Font Awesome -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">