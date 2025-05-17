<?php
session_start();
require_once '../../config/config.php';
require_once '../../includes/auth.php';
include '../../components/menu/Menu.php';

checkPageAccess(PAGE_MANAGE_PROJECT);

// ดึงข้อมูลโครงการทั้งหมด
$stmt = $conn->query("
    SELECT p.*, 
           COUNT(DISTINCT pa.user_id) as approval_count,
           (SELECT COUNT(*) FROM users WHERE director = 1) as total_directors,
           (SELECT SUM(amount) FROM project_transactions WHERE project_id = p.project_id AND type = 'income') as total_income,
           (SELECT SUM(amount) FROM project_transactions WHERE project_id = p.project_id AND type = 'expense') as total_expense,
           (SELECT GROUP_CONCAT(CONCAT(type, ':', amount, ':', description, ':', transaction_date) SEPARATOR '||') 
            FROM project_transactions 
            WHERE project_id = p.project_id) as transactions
    FROM projects p
    LEFT JOIN project_approvals pa ON p.project_id = pa.project_id AND pa.status = 'approved'
    GROUP BY p.project_id
    ORDER BY p.created_at DESC
");


$projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
</head>

<body class="bg-modern">
    <div class="flex">
        <div id="sidebar" class="fixed top-0 left-0 z-20 h-full w-20 transition-all duration-300 ease-in-out bg-gradient-to-b from-blue-600 to-blue-500 shadow-xl">
            <!-- ปุ่ม toggle -->
            <button id="toggleSidebar" class="absolute -right-3 bottom-24 bg-blue-800 text-white rounded-full p-1 shadow-lg hover:bg-blue-900">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </button>
            <?php renderMenu(); ?>
        </div>
    </div>

    <div class="flex-1 ml-20">
        <!-- Top Navigation -->
        <nav class="bg-white shadow-sm px-6 py-3">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold text-gray-800">จัดการโครงการ</h1>
                <div class="flex items-center space-x-4">
                    <a href="add_project.php" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        เพิ่มโครงการใหม่
                    </a>
                </div>
            </div>
        </nav>

        <!-- Projects Grid -->
        <div class="p-6">
        <?php if (empty($projects)): ?>
                    <!-- ส่วนแสดงเมื่อไม่มีข้อมูล -->
                    <div class="text-center py-16 bg-white rounded-2xl shadow-sm">
                        <div class="mb-6">
                            <svg class="mx-auto h-16 w-16 text-gray-400 animate-bounce" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4m8-8v16m-4-8h.01M15 12h.01M19 12h.01M8 12h.01" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-semibold text-gray-900 mb-2">ยังไม่มีข้อมูลโครงการ</h3>
                        <p class="text-gray-500 mb-6">เริ่มเพิ่มข้อมูลโครงการ</p>
                        <a href="add_project.php" class="inline-flex items-center px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-full transition duration-200 transform hover:scale-105">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                            </svg>
                            เพิ่มโครงการ
                        </a>
                    </div>
                <?php endif ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($projects as $project): ?>
                    <div class="bg-white rounded-xl shadow-lg overflow-hidden hover:shadow-xl transition-shadow duration-300">
                        <!-- รูปภาพโครงการ -->
                        <div class="relative h-48">
                            <img src="<?php echo !empty($project['image_path']) ? $project['image_path'] : '../../src/img/default_project.jpg'; ?>"
                                class="w-full h-full object-cover" alt="<?php echo htmlspecialchars($project['name']); ?>">
                            <div class="absolute top-4 right-4">
                                <?php
                                $approvalPercentage = ($project['total_directors'] > 0)
                                    ? ($project['approval_count'] / $project['total_directors']) * 100
                                    : 0;
                                $statusClass = '';
                                $statusText = '';

                                switch ($project['status']) {
                                    case 'draft':
                                        $statusClass = 'bg-gray-500';
                                        $statusText = 'แบบร่าง';
                                        break;
                                    case 'pending':
                                        $statusClass = 'bg-yellow-500';
                                        $statusText = 'รออนุมัติ ' . round($approvalPercentage) . '%';
                                        break;
                                    case 'approved':
                                        $statusClass = 'bg-green-500';
                                        $statusText = 'อนุมัติแล้ว';
                                        break;
                                    case 'rejected':
                                        $statusClass = 'bg-red-500';
                                        $statusText = 'ไม่อนุมัติ';
                                        break;
                                }
                                ?>
                                <span class="<?php echo $statusClass; ?> text-white px-3 py-1 rounded-full text-sm font-medium">
                                    <?php echo $statusText; ?>
                                </span>
                            </div>
                        </div>

                        <!-- ข้อมูลโครงการ -->
                        <div class="p-6">
                            <div class="mb-4">
                                <h3 class="text-xl font-semibold text-gray-800 mb-2">
                                    <?php echo htmlspecialchars($project['name']); ?>
                                </h3>
                                <span class="inline-block bg-blue-100 text-blue-800 text-sm px-2 py-1 rounded">
                                    <?php echo htmlspecialchars($project['type']); ?>
                                </span>
                            </div>
                            <!-- งบประมาณ -->
                            <div class="mb-4">
                                <div class="flex justify-between text-sm text-gray-600 mb-1">
                                    <span>งบประมาณที่ใช้ไป</span>
                                    <span><?php echo number_format($project['budget'] - $project['remaining_budget'], 2); ?> / <?php echo number_format($project['budget'], 2); ?> บาท</span>
                                </div>
                                <?php
                                // คำนวณเปอร์เซ็นต์จากยอดที่ใช้ไป
                                $usedBudget = $project['budget'] - $project['remaining_budget'];
                                $percentage = ($project['budget'] > 0) ? ($usedBudget / $project['budget']) * 100 : 0;
                                $percentage = min(max($percentage, 0), 100);

                                // กำหนดสีตามเปอร์เซ็นต์การใช้งบประมาณ
                                $barColor = $percentage >= 80 ? 'bg-red-500' : ($percentage >= 50 ? 'bg-yellow-500' : 'bg-green-500');
                                ?>
                                <div class="w-full bg-gray-200 rounded-full h-2 overflow-hidden">
                                    <div class="<?php echo $barColor; ?> h-2 rounded-full transition-all duration-300"
                                        style="width: <?php echo $percentage; ?>%">
                                    </div>
                                </div>
                            </div>

                            <!-- ระยะเวลา -->
                            <div class="text-sm text-gray-600 mb-4">
                                <div class="flex items-center">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    <?php
                                    $start_date = new DateTime($project['start_date']);
                                    $end_date = new DateTime($project['end_date']);
                                    echo $start_date->format('d/m/Y') . ' - ' . $end_date->format('d/m/Y');
                                    ?>
                                </div>
                            </div>

                            <!-- ปายรับ-รายจ่าย -->
                            <div class="mt-2 space-y-2">
                                <div class="flex justify-between items-center">
                                    <span class="text-sm font-medium text-gray-600">รายรับทั้งหมด:</span>
                                    <span class="text-green-600 font-semibold">
                                        <?php echo number_format($project['total_income'] ?? 0, 2); ?> บาท
                                    </span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-sm font-medium text-gray-600">รายจ่ายทั้งหมด:</span>
                                    <span class="text-red-600 font-semibold">
                                        <?php echo number_format($project['total_expense'] ?? 0, 2); ?> บาท
                                    </span>
                                </div>
                                
                                <!-- รายละเอียดธุรกรรม -->
                                <?php if (!empty($project['transactions'])): ?>
                                    <div class="mt-4">
                                        <h4 class="text-sm font-medium text-gray-700 mb-2">รายละเอียดธุรกรรม:</h4>
                                        <div class="space-y-2 max-h-40 overflow-y-auto">
                                            <?php
                                            $transactions = explode('||', $project['transactions']);
                                            foreach ($transactions as $transaction) {
                                                if (empty($transaction)) continue;
                                                
                                                list($type, $amount, $description, $date) = explode(':', $transaction);
                                                try {
                                                    $dateParts = explode(' ', $date);
                                                    $dateObj = new DateTime($dateParts[0]);
                                                    $formattedDate = $dateObj->format('d/m/Y');
                                                } catch (Exception $e) {
                                                    $formattedDate = 'ไม่ระบุวันที่';
                                                }
                                                
                                                $typeClass = $type === 'income' ? 'text-green-600' : 'text-red-600';
                                                $typeText = $type === 'income' ? 'รายรับ' : 'รายจ่าย';
                                            ?>
                                            <div class="flex justify-between items-center text-sm">
                                                <div>
                                                    <span class="<?php echo $typeClass; ?>">[<?php echo $typeText; ?>]</span>
                                                    <span class="text-gray-600"><?php echo htmlspecialchars($description); ?></span>
                                                </div>
                                                <div>
                                                    <span class="<?php echo $typeClass; ?>"><?php echo number_format($amount, 2); ?> บาท</span>
                                                    <span class="text-gray-500 text-xs">(<?php echo $formattedDate; ?>)</span>
                                                </div>
                                            </div>
                                            <?php } ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- ปุ่มดำเนินการ -->
                            <div class="flex justify-end space-x-2 mt-2">
                                <a href="view_project.php?id=<?php echo $project['project_id']; ?>"
                                    class="text-blue-600 hover:text-blue-800 bg-blue-100 px-4 py-2 rounded-lg">
                                    ดูรายละเอียด
                                </a>
                                <?php if ($project['status'] === 'draft'): ?>
                                    <a href="edit_project.php?id=<?php echo $project['project_id']; ?>"
                                        class="text-gray-600 hover:text-gray-800">
                                        แก้ไข
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
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
    </script>
</body>

</html>