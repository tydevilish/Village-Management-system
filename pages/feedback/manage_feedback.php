<?php
session_start();
require_once '../../config/config.php';
require_once '../../includes/auth.php';
include '../../components/menu/Menu.php';

checkPageAccess(PAGE_MANAGE_FEEDBACK);

// ดึงจำนวนข้อเสนอแนะที่รอการตรวจสอบ
$stmt = $conn->prepare("
    SELECT COUNT(*) as pending_count
    FROM feedback
    WHERE status = 'pending'
");
$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_ASSOC);
$pending_count = $result['pending_count'] ?? 0;

// ดึงข้อมูลขระเภทข้อเสนอแนะ
$stmt = $conn->prepare("SELECT * FROM feedback_categories ORDER BY name");
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ดึงข้อมูลข้อเสนอแนะทั้งหมด
$stmt = $conn->prepare("
    SELECT 
        f.*,
        u.fullname,
        u.username,
        fc.name as category_name,
        (SELECT COUNT(*) FROM feedback_images WHERE feedback_id = f.id) as image_count
    FROM feedback f
    LEFT JOIN users u ON f.user_id = u.user_id
    LEFT JOIN feedback_categories fc ON f.category_id = fc.id
    ORDER BY f.created_at DESC
");
$stmt->execute();
$feedbacks = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
    <div class="flex-1">
        <!-- Sidebar -->
        <div id="sidebar" class="fixed top-0 left-0 h-full w-20 transition-all duration-300 ease-in-out bg-gradient-to-b from-blue-600 to-blue-500 shadow-xl">
            <button id="toggleSidebar" class="absolute -right-3 bottom-24 bg-blue-800 text-white rounded-full p-1 shadow-lg hover:bg-blue-900">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </button>
            <?php renderMenu(); ?>
        </div>

        <!-- Main Content -->
        <div class="flex-1 ml-20">
            <!-- Navbar -->
            <nav class="bg-white shadow-sm px-8 py-6 ">
                <div class="flex justify-between items-center">
                    <div>
                        <h1 class="text-xl font-bold bg-gradient-to-r from-blue-600 to-blue-800 bg-clip-text text-transparent">
                            จัดการข้อเสนอแนะ
                        </h1>
                        <p class="text-sm text-gray-500 mt-2">ตรวจสอบและจัดการข้อเสนอแนะจากผู้ใช้งาน</p>
                    </div>
                    <div class="flex items-center space-x-4">
                        <div class="p-0.5 rounded-xl">
                            <div class="bg-white px-4 py-2 rounded-lg flex items-center space-x-3">
                                <div class="w-8 h-8 bg-amber-50 rounded-lg flex items-center justify-center">
                                    <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div>
                                    <div class="text-xs text-gray-500">รอการตรวจสอบ</div>
                                    <div class="text-sm font-semibold text-gray-800"><?php echo $pending_count; ?> รายการ</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </nav>

            <!-- Content -->
            <div class="p-4 sm:p-8 min-h-[calc(100vh-5rem)]">
                <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
                    <!-- Tabs -->
                    <div class="border-b bg-white/80 backdrop-blur-sm sticky top-0 z-10 overflow-x-auto">
                        <div class="flex px-2 min-w-max">
                            <button onclick="showTab('all')" class="tab-btn group relative px-4 sm:px-6 py-4">
                                <div class="relative flex items-center space-x-2">
                                    <svg class="w-5 h-5 text-gray-400 group-hover:text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                                    </svg>
                                    <span class="text-sm font-medium text-gray-600 group-hover:text-blue-600">ทั้งหมด</span>
                                </div>
                            </button>

                            <?php foreach ($categories as $category): ?>
                                <button onclick="showTab('category-<?php echo $category['id']; ?>')" 
                                    class="tab-btn group relative px-4 sm:px-6 py-4 whitespace-nowrap">
                                    <span class="text-sm font-medium text-gray-600 group-hover:text-blue-600">
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </span>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Feedback List -->
                    <div class="p-4 sm:p-6">
                        <!-- Search -->
                        <div class="mb-6 max-w-2xl mx-auto">
                            <div class="relative">
                                <input type="text" id="searchFeedback"
                                    placeholder="ค้นหาข้อเสนอแนะ..."
                                    class="w-full pl-12 pr-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-100 focus:border-blue-400 transition-all">
                                <svg class="w-5 h-5 text-gray-400 absolute left-4 top-1/2 transform -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>
                        </div>

                        <!-- Feedback Grid -->
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
                            <?php foreach ($feedbacks as $feedback): ?>
                                <div class="feedback-card bg-white rounded-2xl shadow-md hover:shadow-lg transition-all duration-300 transform hover:-translate-y-1 overflow-hidden border-2 border-gray-100"
                                    data-status="<?php echo $feedback['status']; ?>"
                                    data-category="category-<?php echo $feedback['category_id']; ?>">
                                    <div class="p-6">
                                        <!-- Status Badge -->
                                        <?php
                                        $statusConfig = [
                                            'pending' => [
                                                'bg' => 'bg-yellow-100 text-yellow-800',
                                                'text' => 'รอการตรวจสอบ'
                                            ],
                                            'in_progress' => [
                                                'bg' => 'bg-blue-100 text-blue-800',
                                                'text' => 'กำลังดำเนินการ'
                                            ],
                                            'completed' => [
                                                'bg' => 'bg-green-100 text-green-800',
                                                'text' => 'เสร็จสิ้น'
                                            ],
                                            'rejected' => [
                                                'bg' => 'bg-red-100 text-red-800',
                                                'text' => 'ไม่อนุมัติ'
                                            ]
                                        ];
                                        $config = $statusConfig[$feedback['status']];
                                        ?>
                                        <div class="flex justify-between items-start mb-4">
                                            <div class="<?php echo $config['bg']; ?> px-3 py-1 rounded-full text-sm">
                                                <?php echo $config['text']; ?>
                                            </div>
                                            <span class="text-xs text-gray-500">
                                                <?php echo date('d/m/Y H:i', strtotime($feedback['created_at'])); ?>
                                            </span>
                                        </div>

                                        <!-- Title & Description -->
                                        <h3 class="text-lg font-semibold text-gray-800 mb-2">
                                            <?php echo htmlspecialchars($feedback['title']); ?>
                                        </h3>
                                        <p class="text-gray-600 text-sm mb-4 line-clamp-3">
                                            <?php echo nl2br(htmlspecialchars($feedback['description'])); ?>
                                        </p>

                                        <!-- User Info -->
                                        <div class="flex items-center space-x-2 mb-4">
                                            <div class="w-8 h-8 bg-gray-100 rounded-full flex items-center justify-center">
                                                <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                                </svg>
                                            </div>
                                            <div>
                                                <div class="text-xs text-gray-500">ผู้แจ้ง</div>
                                                <div class="text-sm font-medium text-gray-800">
                                                    <?php echo htmlspecialchars($feedback['fullname']); ?>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Images Count -->
                                        <?php if ($feedback['image_count'] > 0): ?>
                                            <div class="flex items-center gap-2 mb-4 bg-gray-50 p-2 rounded-lg">
                                                <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                </svg>
                                                <span class="text-sm text-gray-600">
                                                    รูปภาพ (<?php echo $feedback['image_count']; ?>)
                                                </span>
                                            </div>
                                        <?php endif; ?>

                                        <!-- Category -->
                                        <div class="flex flex-wrap gap-2 mb-4">
                                            <?php foreach ($categories as $category): ?>
                                                <?php
                                                // กำหนด class พื้นฐาน
                                                $buttonClass = "px-3 py-1.5 text-sm rounded-full transition-colors ";

                                                // เพิ่ม class ตามเงื่อนไข
                                                if (isset($feedback['category_id']) && $feedback['category_id'] == $category['id']) {
                                                    $buttonClass .= "bg-blue-100 text-blue-700 hover:bg-blue-200";
                                                } else {
                                                    $buttonClass .= "bg-gray-100 text-gray-600 hover:bg-gray-200";
                                                }
                                                ?>
                                                <button onclick="updateFeedbackCategory(<?php echo $feedback['id']; ?>, <?php echo $category['id']; ?>)"
                                                    class="<?php echo $buttonClass; ?>">
                                                    <?php echo htmlspecialchars($category['name']); ?>
                                                </button>
                                            <?php endforeach; ?>
                                        </div>

                                        <!-- Action Button -->
                                        <button onclick="viewFeedbackDetails(<?php echo $feedback['id']; ?>)"
                                            class="mt-4 w-full bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white px-6 py-3 rounded-xl flex items-center justify-center space-x-2 transform transition-all duration-200 hover:scale-[1.02]">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                            <span>จัดการข้อเสนอแนะ</span>
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Feedback Details Modal -->
    <div id="feedbackDetailsModal" class="fixed inset-0 bg-black/50 z-50 hidden">
        <div class="flex items-center justify-center p-2 sm:p-4 h-full">
            <div class="bg-white rounded-2xl w-full max-w-4xl max-h-[95vh] overflow-hidden relative">
                <div id="feedbackDetailsContent">
                    <!-- Content will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <script>
        // ฟังก์ชันแสดง/ซ่อน tab
        function showTab(status) {
            const cards = document.querySelectorAll('.feedback-card');
            cards.forEach(card => {
                if (status === 'all' || card.dataset.status === status) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        }

        // ฟังก์ชันค้นหา
        document.getElementById('searchFeedback').addEventListener('input', function(e) {
            const searchText = e.target.value.toLowerCase();
            const cards = document.querySelectorAll('.feedback-card');

            cards.forEach(card => {
                const text = card.textContent.toLowerCase();
                if (text.includes(searchText)) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        });

        // ฟังก์ชันดูรายละเอียด
        async function viewFeedbackDetails(id) {
            const modal = document.getElementById('feedbackDetailsModal');
            const content = document.getElementById('feedbackDetailsContent');

            try {
                content.innerHTML = `
                    <div class="flex items-center justify-center p-8">
                        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500"></div>
                    </div>
                `;

                modal.classList.remove('hidden');

                const response = await fetch(`get_feedback_details.php?id=${id}`);
                const data = await response.json();

                if (data.status === 'success') {
                    content.innerHTML = data.html;
                } else {
                    throw new Error(data.message);
                }
            } catch (error) {
                content.innerHTML = `
                    <div class="p-8 text-center text-red-500">
                        เกิดข้อผิดพลาดในการโหลดข้อมูล: ${error.message}
                    </div>
                `;
            }
        }

        // ฟังก์ชันอัพเดทสถานะ
        async function updateFeedbackStatus(id, status, reject_reason = null, approval_note = null) {
            try {
                const response = await fetch('update_feedback_status.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        id: id,
                        status: status,
                        reject_reason: reject_reason,
                        approval_note: approval_note
                    })
                });

                const data = await response.json();

                if (data.status === 'success') {
                    alert('อัพเดทสถานะเรียบร้อยแล้ว');
                    location.reload();
                } else {
                    throw new Error(data.message);
                }
            } catch (error) {
                alert('เกิดข้อผิดพลาด: ' + error.message);
            }
        }


        function completedFeedback(id) {
            const approval_note = prompt('กรุณาระบุหมายเหตุ:');
            if (approval_note === null) return; // ยกเลิกถ้าผู้ใช้กด Cancel

            if (approval_note.trim() === '') {
                alert('กรุณาระบุหมายเหตุ');
                return;
            }

            updateFeedbackStatus(id, 'completed', null, approval_note);
        }

        function rejectFeedback(id) {
            const reason = prompt('กรุณาระบุเหตุผลที่ไม่อนุมัติ:');
            if (reason === null) return; // ยกเลิกถ้าผู้ใช้กด Cancel

            if (reason.trim() === '') {
                alert('กรุณาระบุเหตุผล');
                return;
            }

            updateFeedbackStatus(id, 'rejected', reason);
        }


        function showTab(filter) {
            const cards = document.querySelectorAll('.feedback-card');
            cards.forEach(card => {
                if (filter === 'all') {
                    card.style.display = 'block';
                } else if (filter.startsWith('category-')) {
                    // กรองตามประเภท
                    card.style.display = card.dataset.category === filter ? 'block' : 'none';
                } else {
                    // กรองตามสถานะ
                    card.style.display = card.dataset.status === filter ? 'block' : 'none';
                }
            });
        }

        async function updateFeedbackCategory(id, categoryId) {
            try {
                const response = await fetch('update_feedback_category.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        id,
                        category_id: categoryId
                    })
                });

                const data = await response.json();
                if (data.status === 'success') {
                    location.reload();
                } else {
                    throw new Error(data.message);
                }
            } catch (error) {
                alert('เกิดข้อผิดพลาด: ' + error.message);
            }
        }

        function openImageViewer(imageSrc) {
            const modal = document.getElementById('imageViewerModal');
            const fullImage = document.getElementById('fullImage');
            fullImage.src = imageSrc;
            modal.classList.remove('hidden');
        }
    </script>
     <?php include '../../components/footer/footer.php'; ?>
</body>

</html>