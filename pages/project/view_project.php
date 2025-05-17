<?php
session_start();
require_once '../../config/config.php';
require_once '../../includes/auth.php';
include '../../components/menu/Menu.php';

checkPageAccess(PAGE_MANAGE_PROJECT);

// ตรวจสอบว่ามี project_id
if (!isset($_GET['id'])) {
    header('Location: manage_project.php');
    exit;
}

$project_id = $_GET['id'];

$stmt = $conn->prepare("
    SELECT role_id 
    FROM users
    WHERE user_id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$users = $stmt->fetch(PDO::FETCH_ASSOC);


// ดึงข้อมูลโครงการ
$stmt = $conn->prepare("
    SELECT p.*, 
           COUNT(DISTINCT pa.user_id) as approval_count,
           (SELECT COUNT(*) FROM users WHERE director = 1) as total_directors
    FROM projects p
    LEFT JOIN project_approvals pa ON p.project_id = pa.project_id AND pa.status = 'approved'
    WHERE p.project_id = ?
    GROUP BY p.project_id
");
$stmt->execute([$project_id]);
$project = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$project) {
    header('Location: manage_project.php');
    exit;
}

// ดึงข้อมูลผู้รับผิดชอบโครงการ
$stmt = $conn->prepare("
    SELECT u.fullname, u.user_id
    FROM project_managers pm
    JOIN users u ON pm.user_id = u.user_id
    WHERE pm.project_id = ?
");
$stmt->execute([$project_id]);
$managers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ดึงข้อมูลวิธีดำเนินการ
$stmt = $conn->prepare("
    SELECT * FROM project_methods 
    WHERE project_id = ? 
    ORDER BY order_number
");
$stmt->execute([$project_id]);
$methods = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ดึงข้อมูลแผนการปฏิบัติงาน
$stmt = $conn->prepare("
    SELECT pp.*, u.fullname as responsible_person
    FROM project_plans pp
    LEFT JOIN users u ON pp.user_id = u.user_id
    WHERE pp.project_id = ?
    ORDER BY pp.order_number
");
$stmt->execute([$project_id]);
$plans = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ดึงข้อมูลการอนุมัติ
$stmt = $conn->prepare("
    SELECT pa.*, u.fullname
    FROM project_approvals pa
    JOIN users u ON pa.user_id = u.user_id
    WHERE pa.project_id = ?
    ORDER BY pa.approved_at DESC
");
$stmt->execute([$project_id]);
$approvals = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ดึงข้อมูลรายรับ-รายจ่าย
$stmt = $conn->prepare("
    SELECT * FROM project_transactions
    WHERE project_id = ?
    ORDER BY transaction_date DESC
");
$stmt->execute([$project_id]);
$transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ตรวจสอบว่าผู้ใช้ปัจจุบันเป็นกรรมการหรือไม่
$is_director = false;
if (isset($_SESSION['user_id'])) {
    $stmt = $conn->prepare("SELECT director FROM users WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    $is_director = $user['director'] == 1;
}

// คำนวณเปอร์เซ็นต์การอนุมัติ
$approval_percentage = ($project['total_directors'] > 0)
    ? ($project['approval_count'] / $project['total_directors']) * 100
    : 0;
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($project['name']); ?> | ระบบจัดการหมู่บ้าน</title>
    <link rel="icon" href="https://devcm.info/img/favicon.png">
    <link rel="stylesheet" href="../../src/style.css">
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-modern">
    <div class="flex">
        <!-- Sidebar -->
        <div id="sidebar" class="fixed top-0 left-0 z-20 h-full w-20 transition-all duration-300 ease-in-out bg-gradient-to-b from-blue-600 to-blue-500 shadow-xl">
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
                <h1 class="text-2xl font-bold text-gray-800"><?php echo htmlspecialchars($project['name']); ?></h1>
                <div class="flex items-center space-x-4">
                    <?php if ($project['status'] === 'draft'): ?>
                        <a href="edit_project.php?id=<?php echo $project_id; ?>"
                            class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">
                            แก้ไขโครงการ
                        </a>
                    <?php endif; ?>
                    <a href="manage_project.php" class="text-gray-600 hover:text-gray-800">
                        กลับไปหน้าจัดการโครงการ
                    </a>
                </div>
            </div>
        </nav>

        <!-- Project Content -->
        <div class="p-6">
            <div class="max-w-7xl mx-auto">


                <?php if ($project['status'] === 'pending'): ?>
                    <div class="mt-4">
                        <div class="flex justify-between text-sm text-gray-600 mb-1">
                            <span>การอนุมัติจากคณะกรรมการ</span>
                            <span><?php echo $project['approval_count']; ?> / <?php echo $project['total_directors']; ?> ท่าน</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-blue-600 h-2 rounded-full" style="width: <?php echo $approval_percentage; ?>%"></div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- รายละเอียดโครงการ -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <!-- ข้อมูลพื้นฐาน -->
                <div class="bg-white rounded-xl shadow-lg p-6">
                    <h2 class="text-xl font-semibold text-gray-800 mb-4">ข้อมูลพื้นฐาน</h2>
                    <div class="space-y-4">
                        <div>
                            <label class="text-sm text-gray-600">ประเภทโครงการ</label>
                            <p class="font-medium"><?php echo htmlspecialchars($project['type']); ?></p>
                        </div>
                        <div>
                            <label class="text-sm text-gray-600">ผู้รับผิดชอบโครงการ</label>
                            <div class="space-y-1">
                                <?php foreach ($managers as $manager): ?>
                                    <p class="font-medium"><?php echo htmlspecialchars($manager['fullname']); ?></p>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div>
                            <label class="text-sm text-gray-600">ระยะเวลาดำเนินการ</label>
                            <p class="font-medium">
                                <?php
                                $start_date = new DateTime($project['start_date']);
                                $end_date = new DateTime($project['end_date']);
                                echo $start_date->format('d/m/Y') . ' - ' . $end_date->format('d/m/Y');
                                ?>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- งบประมาณ -->
                <div class="bg-white rounded-xl shadow-lg p-6">
                    <h2 class="text-xl font-semibold text-gray-800 mb-4">งบประมาณ</h2>
                    <div class="space-y-4">
                        <div>
                            <label class="text-sm text-gray-600">งบประมาณทั้งหมด</label>
                            <p class="font-medium"><?php echo number_format($project['budget'], 2); ?> บาท</p>
                        </div>
                        <div>
                            <label class="text-sm text-gray-600">งบประมาณคงเหลือ</label>
                            <p class="font-medium"><?php echo number_format($project['remaining_budget'], 2); ?> บาท</p>
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
                    </div>
                </div>

                <!-- รูปภาพโครงการ -->
                <div class="bg-white rounded-xl shadow-lg p-6">
                    <h2 class="text-xl font-semibold text-gray-800 mb-4">รูปภาพโครงการ</h2>
                    <img src="<?php echo !empty($project['image_path']) ? $project['image_path'] : '../../src/img/default_project.jpg'; ?>"
                        class="w-full h-48 object-cover rounded-lg"
                        alt="<?php echo htmlspecialchars($project['name']); ?>">
                </div>
            </div>

            <!-- รายละเอียดเพิ่มเติม -->
            <div class="grid grid-cols-1 gap-6 mb-6">
                <!-- หลักการและเหตุผล -->
                <div class="bg-white rounded-xl shadow-lg p-6">
                    <h2 class="text-xl font-semibold text-gray-800 mb-4">หลักการและเหตุผล</h2>
                    <div class="prose max-w-none">
                        <?php echo nl2br(htmlspecialchars($project['principle'])); ?>
                    </div>
                </div>

                <!-- วัตถุประสงค์ -->
                <div class="bg-white rounded-xl shadow-lg p-6">
                    <h2 class="text-xl font-semibold text-gray-800 mb-4">วัตถุประสงค์</h2>
                    <div class="prose max-w-none">
                        <?php echo nl2br(htmlspecialchars($project['objective'])); ?>
                    </div>
                </div>

                <!-- เป้าหมาย -->
                <div class="bg-white rounded-xl shadow-lg p-6">
                    <h2 class="text-xl font-semibold text-gray-800 mb-4">เป้าหมาย</h2>
                    <div class="prose max-w-none">
                        <?php echo nl2br(htmlspecialchars($project['target'])); ?>
                    </div>
                </div>
            </div>

            <!-- วิธีดำเนินการ -->
            <div class="bg-white rounded-xl shadow-lg p-6 mb-6">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">วิธีดำเนินการ</h2>
                <div class="space-y-4">
                    <?php foreach ($methods as $method): ?>
                        <div class="flex items-start space-x-4">
                            <span class="flex-shrink-0 w-8 h-8 bg-blue-100 text-blue-800 rounded-full flex items-center justify-center font-semibold">
                                <?php echo $method['order_number']; ?>
                            </span>
                            <div class="flex-1">
                                <p class="text-gray-800"><?php echo htmlspecialchars($method['description']); ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- แผนการปฏิบัติงาน -->
            <div class="bg-white rounded-xl shadow-lg p-6 mb-6">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">แผนการปฏิบัติงาน</h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr>
                                <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ลำดับ</th>
                                <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">รายละเอียด</th>
                                <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ผู้รับผิดชอบ</th>
                                <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ระยะเวลา</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($plans as $plan): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo $plan['order_number']; ?>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900">
                                        <?php echo htmlspecialchars($plan['description']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo htmlspecialchars($plan['responsible_person']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php
                                        $start = new DateTime($plan['start_date']);
                                        $end = new DateTime($plan['end_date']);
                                        echo $start->format('d/m/Y') . ' - ' . $end->format('d/m/Y');
                                        ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <?php if ($project['status'] === 'approved'): ?>
                <!-- รายรับ-รายจ่าย -->
                <div class="bg-white rounded-xl shadow-lg p-6 mb-6">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-xl font-semibold text-gray-800">รายรับ-รายจ่าย</h2>
                        <button onclick="openTransactionModal()"
                            class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                            </svg>
                            เพิ่มรายการ
                        </button>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead>
                                <tr>
                                    <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">วันที่</th>
                                    <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">รายละเอียด</th>
                                    <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ประเภท</th>
                                    <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">จำนวนเงิน</th>
                                    <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">หลักฐาน</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($transactions as $transaction): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php
                                            $date = new DateTime($transaction['transaction_date']);
                                            echo $date->format('d/m/Y');
                                            ?>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-900">
                                            <?php echo htmlspecialchars($transaction['description']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                <?php echo $transaction['type'] === 'income' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                                <?php echo $transaction['type'] === 'income' ? 'รายรับ' : 'รายจ่าย'; ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?php echo number_format($transaction['amount'], 2); ?> บาท
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php if ($transaction['slip_image']): ?>
                                                <a href="<?php echo $transaction['slip_image']; ?>"
                                                    target="_blank"
                                                    class="text-blue-600 hover:text-blue-800">
                                                    ดูหลักฐาน
                                                </a>
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>

            <!-- ประวัติการอนุมัติ -->
            <?php if (!empty($approvals)): ?>
                <div class="bg-white rounded-xl shadow-lg p-6 mb-6">
                    <h2 class="text-xl font-semibold text-gray-800 mb-4">ประวัติการอนุมัติ</h2>
                    <div class="space-y-4">
                        <?php foreach ($approvals as $approval): ?>
                            <div class="flex items-start space-x-4">
                                <div class="flex-shrink-0">
                                    <span class="w-8 h-8 rounded-full flex items-center justify-center
                                        <?php echo $approval['status'] === 'approved' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <?php if ($approval['status'] === 'approved'): ?>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                            <?php else: ?>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                            <?php endif; ?>
                                        </svg>
                                    </span>
                                </div>
                                <div class="flex-1">
                                    <div class="flex items-center justify-between">
                                        <p class="text-sm font-medium text-gray-900">
                                            <?php echo htmlspecialchars($approval['fullname']); ?>
                                        </p>
                                        <p class="text-sm text-gray-500">
                                            <?php
                                            $date = new DateTime($approval['approved_at']);
                                            echo $date->format('d/m/Y H:i');
                                            ?>
                                        </p>
                                    </div>
                                    <?php if ($approval['comment']): ?>
                                        <p class="mt-1 text-sm text-gray-600">
                                            <?php echo htmlspecialchars($approval['comment']); ?>
                                        </p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (isDirector()): // ฟังก์ชันตรวจสอบว่าเป็นกรรมการ 
            ?>
                <div class="mb-8">
                    <div class="bg-white rounded-xl shadow-lg p-6">
                        <h2 class="text-xl font-semibold text-gray-800 mb-4">การอนุมัติโครงการ</h2>

                        <?php if (!hasApproved($project_id)): // ตรวจสอบว่ายังไม่เคยอนุมัติ 
                        ?>
                            <div class="flex space-x-4">
                                <button type="button" onclick="handleApproval('approved')"
                                    class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                                    อนุมัติโครงการ
                                </button>
                                <button type="button" onclick="handleApproval('rejected')"
                                    class="px-6 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
                                    ไม่อนุมัติโครงการ
                                </button>
                            </div>
                        <?php else: ?>
                            <p class="text-gray-600">คุณได้ดำเนินการกับโครงการนี้แล้ว</p>
                        <?php endif; ?>

                        <!-- แสดงสถานะการอนุมัติทั้งหมด -->
                        <div class="mt-4">
                            <p class="text-sm text-gray-600">
                                การอนุมัติ: <?php echo $project['approval_count']; ?>/<?php echo $project['total_directors']; ?> ท่าน
                            </p>
                            <div class="w-full bg-gray-200 rounded-full h-2.5 mt-2">
                                <div class="bg-blue-600 h-2.5 rounded-full"
                                    style="width: <?php echo ($project['approval_count'] / $project['total_directors']) * 100; ?>%">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modal สำหรับการอนุมัติ -->
                <div id="approvalModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden">
                    <div class="flex items-center justify-center min-h-screen px-4">
                        <div class="bg-white rounded-xl shadow-xl max-w-md w-full p-6">
                            <h3 class="text-lg font-semibold text-gray-800 mb-4" id="modalTitle"></h3>
                            <div id="approvalContent">
                                <input type="hidden" id="projectId" value="<?php echo $project_id; ?>">
                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">ความคิดเห็น</label>
                                    <textarea id="approvalComment" rows="4"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"></textarea>
                                </div>

                                <div class="flex justify-end space-x-4">
                                    <button type="button" onclick="closeApprovalModal()"
                                        class="px-4 py-2 text-gray-700 hover:text-gray-900">
                                        ยกเลิก
                                    </button>
                                    <button type="button" onclick="submitApproval()"
                                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                                        ยืนยัน
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <script>
                    let currentApprovalStatus = '';

                    function handleApproval(status) {
                        currentApprovalStatus = status;
                        const modal = document.getElementById('approvalModal');
                        const modalTitle = document.getElementById('modalTitle');

                        modalTitle.textContent = status === 'approved' ? 'อนุมัติโครงการ' : 'ไม่อนุมัติโครงการ';
                        modal.classList.remove('hidden');
                    }

                    function submitApproval() {
                        const projectId = document.getElementById('projectId').value;
                        const comment = document.getElementById('approvalComment').value;

                        const formData = new FormData();
                        formData.append('project_id', projectId);
                        formData.append('status', currentApprovalStatus);
                        formData.append('comment', comment);

                        // Debug log
                        console.log('Sending data:', {
                            project_id: projectId,
                            status: currentApprovalStatus,
                            comment: comment
                        });

                        fetch('../../actions/project/process_approval.php', {
                                method: 'POST',
                                body: formData
                            })
                            .then(response => response.json())
                            .then(data => {
                                console.log('Response:', data); // Debug log
                                if (data.status === 'success') {
                                    alert(data.message);
                                    window.location.reload();
                                } else {
                                    alert(data.message);
                                }
                            })
                            .catch(error => {
                                console.error('Error:', error);
                                alert('เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง');
                            })
                            .finally(() => {
                                closeApprovalModal();
                            });
                    }

                    function closeApprovalModal() {
                        document.getElementById('approvalModal').classList.add('hidden');
                        document.getElementById('approvalComment').value = '';
                    }
                </script>
            <?php endif; ?>
        </div>
    </div>
    </div>

    <!-- Modal สำหรับเพิ่มรายรับ-รายจ่าย -->
    <div id="transactionModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="bg-white rounded-xl shadow-xl max-w-md w-full">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">เพิ่มรายรับ-รายจ่าย</h3>
                    <form id="transactionForm" action="../../actions/project/process_transaction.php" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="project_id" value="<?php echo $project_id; ?>">

                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">ประเภทรยการ</label>
                                <select name="type" required
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                    <?php if ($users['role_id'] === 7): ?>
                                        <option value="income">รายรับ</option>
                                    <?php endif ?>
                                    <option value="expense">รายจ่าย</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">จำนวนเงิน</label>
                                <input type="number" name="amount" step="0.01" required
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">รายละเอียด</label>
                                <textarea name="description" required
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"></textarea>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">แนบหลักฐาน (ถ้ามี)</label>
                                <input type="file" name="slip_image" accept="image/*"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                            </div>
                        </div>

                        <div class="mt-6 flex justify-end space-x-4">
                            <button type="button" onclick="closeTransactionModal()"
                                class="px-4 py-2 text-gray-700 hover:text-gray-900">
                                ยกเลิก
                            </button>
                            <button type="submit"
                                class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">
                                บันทึก
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Sidebar Toggle
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

        // Modal Functions
        function openTransactionModal() {
            document.getElementById('transactionModal').classList.remove('hidden');
        }

        function closeTransactionModal() {
            document.getElementById('transactionModal').classList.add('hidden');
        }

        // Approval Functions
        function openApprovalModal() {
            document.getElementById('approvalModal').classList.remove('hidden');
        }

        function closeApprovalModal() {
            document.getElementById('approvalModal').classList.add('hidden');
        }

        // Chart Functions (ถ้ามีการใช้กราฟ)
        function initBudgetChart() {
            // TODO: เพิ่มโค้ดสำหรับแสดงกราฟงบประมาณ
        }

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize charts if needed
            initBudgetChart();
        });
    </script>
</body>

</html>