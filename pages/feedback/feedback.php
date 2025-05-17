<?php
session_start();
require_once '../../config/config.php';
require_once '../../includes/auth.php';
include '../../components/menu/Menu.php';

checkPageAccess(PAGE_FEEDBACK);

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
        <div id="sidebar" class="fixed top-0 left-0 z-50 h-full w-20 transition-all duration-300 ease-in-out bg-gradient-to-b from-blue-600 to-blue-500 shadow-xl">
            <button id="toggleSidebar" class="absolute -right-3 bottom-24 bg-blue-800 text-white rounded-full p-1 shadow-lg hover:bg-blue-900">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </button>
            <?php renderMenu(); ?>
        </div>

        <!-- Main Content -->
        <div class="flex-1 ml-20">
            <!-- Top Navigation -->
            <nav class="bg-white shadow-sm px-6 py-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-xl font-bold bg-gradient-to-r from-blue-600 to-blue-400 bg-clip-text text-transparent">ข้อเสนอแนะ</h1>
                        <p class="text-sm text-gray-500">ส่งข้อเสนอแนะเพื่อพัฒนาหมู่บ้านร่วมกัน</p>
                    </div>
                    <!-- ปุ่มเพิ่มข้อเสนอแนะ -->
                    <button onclick="openFeedbackModal()"
                        class="bg-gradient-to-r from-blue-600 to-blue-400 text-white px-6 py-2.5 rounded-lg hover:opacity-90 transform hover:scale-105 transition-all duration-200 flex items-center shadow-lg">
                        <svg class="w-5 h-5 mr-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 4v16m8-8H4" />
                        </svg>
                        เพิ่มข้อเสนอแนะ
                    </button>
                </div>
            </nav>

            <!-- Feedback List Section -->
            <div class=" mx-auto px-5 py-6">
                <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                    <!-- Feedback List Header -->
                    <div class="flex flex-col md:flex-row md:items-center justify-between mb-8 gap-4">
                        <h2 class="text-xl font-semibold text-gray-800 flex items-center">
                            <svg class="w-6 h-6 mr-2 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                            </svg>
                            รายการข้อเสนอแนะของคุณ
                        </h2>
                        <!-- Filter Dropdown -->
                        <div class="flex items-center gap-3">
                            <span class="text-sm text-gray-500">กรองตามสถานะ:</span>
                            <select onchange="filterFeedback(this.value)"
                                class="px-4 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 bg-gray-50 text-gray-700 text-sm">
                                <option value="all">ทั้งหมด</option>
                                <option value="pending">รอดำเนินการ</option>
                                <option value="in_progress">กำลังดำเนินการ</option>
                                <option value="completed">เสร็จสิ้น</option>
                                <option value="rejected">ไม่อนุมัติ</option>
                            </select>
                        </div>
                    </div>

                    <!-- Feedback Cards -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" id="feedbackList">
                        <?php
                        // ดึงข้อมูล feedback ของ user
                        $stmt = $conn->prepare("
                SELECT f.*, COUNT(fi.id) as image_count 
                FROM feedback f 
                LEFT JOIN feedback_images fi ON f.id = fi.feedback_id 
                WHERE f.user_id = ? 
                GROUP BY f.id 
                ORDER BY f.created_at DESC
            ");
                        $stmt->execute([$_SESSION['user_id']]);
                        $feedbacks = $stmt->fetchAll();

                        foreach ($feedbacks as $feedback):
                            // กำหนดสีและข้อความตามสถานะ
                            $statusClass = match ($feedback['status']) {
                                'pending' => 'bg-yellow-100 text-yellow-800',
                                'in_progress' => 'bg-blue-100 text-blue-800',
                                'completed' => 'bg-green-100 text-green-800',
                                'rejected' => 'bg-red-100 text-red-800',
                                default => 'bg-gray-100 text-gray-800'
                            };
                            $statusText = match ($feedback['status']) {
                                'pending' => 'รอดำเนินการ',
                                'in_progress' => 'กำลังดำเนินการ',
                                'completed' => 'เสร็จสิ้น',
                                'rejected' => 'ไม่อนุมัติ',
                                default => 'ไม่ระบุ'
                            };
                        ?>
                            <div class="feedback-item bg-white rounded-xl border border-gray-200 hover:shadow-lg transition-all duration-300"
                                data-status="<?= $feedback['status'] ?>">
                                <div class="p-5">
                                    <!-- Status Badge -->
                                    <div class="flex justify-between items-start mb-4">
                                        <span class="px-3 py-1 rounded-full text-sm <?= $statusClass ?> font-medium">
                                            <?= $statusText ?>
                                        </span>
                                        <span class="text-xs text-gray-500">
                                            <?= date('d/m/Y H:i', strtotime($feedback['created_at'])) ?>
                                        </span>
                                    </div>

                                    <!-- Title & Description -->
                                    <h3 class="text-lg font-semibold text-gray-800 mb-2"><?= htmlspecialchars($feedback['title']) ?></h3>
                                    <p class="text-gray-600 text-sm mb-4 line-clamp-3"><?= nl2br(htmlspecialchars($feedback['description'])) ?></p>

                                    <!-- Images Preview -->
                                    <?php if ($feedback['image_count'] > 0): ?>
                                        <div class="flex items-center gap-2 mb-3 bg-gray-50 p-2 rounded-lg">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                            <button onclick="viewImages(<?= $feedback['id'] ?>)"
                                                class="text-blue-600 text-sm hover:underline flex items-center">
                                                ดูรูปภาพ (<?= $feedback['image_count'] ?>)
                                                <svg class="w-4 h-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                                </svg>
                                            </button>
                                        </div>
                                    <?php endif; ?>

                                    <!-- Reject Reason -->
                                    <?php if ($feedback['status'] === 'rejected' && $feedback['reject_reason']): ?>
                                        <div class="mt-3 p-3 bg-red-50 rounded-lg border border-red-100">
                                            <p class="text-sm text-red-800">
                                                <span class="font-medium block mb-1">เหตุผลที่ไม่อนุมัติ:</span>
                                                <?= htmlspecialchars($feedback['reject_reason']) ?>
                                            </p>
                                        </div>
                                    <?php endif; ?>

                                    <?php if ($feedback['status'] === 'completed' && $feedback['approval_note']): ?>
                                        <div class="mt-3 p-3 bg-green-50 rounded-lg border border-green-100">
                                            <p class="text-sm text-green-800">
                                                <span class="font-medium block mb-1">หมายเหตุ:</span>
                                                <?= htmlspecialchars($feedback['approval_note']) ?>
                                            </p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Empty State -->
                    <?php if (empty($feedbacks)): ?>
                        <div class="text-center py-12 bg-gray-50 rounded-xl border border-dashed border-gray-200">
                            <svg class="mx-auto h-16 w-16 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                            </svg>
                            <h3 class="mt-4 text-lg font-medium text-gray-900">ยังไม่มีข้อเสนอแนะ</h3>
                            <p class="mt-2 text-sm text-gray-500">เริ่มต้นส่งข้อเสนอแนะได้เลย</p>
                            <button onclick="openFeedbackModal()"
                                class="mt-4 bg-blue-500 text-white px-6 py-2 rounded-lg hover:bg-blue-600 transition-colors duration-200">
                                สร้างข้อเสนอแนะใหม่
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Modal แสดงรูปภาพ -->
            <div id="imageModal" class="fixed inset-0 bg-black bg-opacity-75 hidden z-50">
                <div class="min-h-screen px-4 text-center">
                    <!-- Close Button -->
                    <button onclick="closeImageModal()" class="absolute top-4 right-4 text-blue-500 hover:text-blue-300 z-50 transition-colors duration-200">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>

                    <!-- Modal Content -->
                    <div class="inline-block align-middle max-w-6xl w-full my-8">
                        <!-- รูปภาพหลัก -->
                        <div class="relative bg-white rounded-xl overflow-hidden shadow-2xl">
                            <div class="p-4 border-b flex justify-between items-center">
                                <h3 class="text-lg font-semibold text-gray-800 flex items-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    รูปภาพประกอบ
                                </h3>
                                <span id="imageCounter" class="text-sm text-gray-500"></span>
                            </div>

                            <!-- รูปภาพปัจจุบัน -->
                            <div class="relative">
                                <div id="mainImageContainer" class="flex items-center justify-center bg-gray-900 h-[60vh]">
                                    <img id="currentImage" class="max-h-full max-w-full object-contain" src="" alt="" />
                                </div>

                                <!-- ปุ่มเลื่อนรูป -->
                                <button id="prevButton" class="absolute left-4 top-1/2 -translate-y-1/2 bg-black bg-opacity-50 hover:bg-opacity-75 text-white p-2 rounded-full transition-all duration-200">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                                    </svg>
                                </button>
                                <button id="nextButton" class="absolute right-4 top-1/2 -translate-y-1/2 bg-black bg-opacity-50 hover:bg-opacity-75 text-white p-2 rounded-full transition-all duration-200">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                    </svg>
                                </button>
                            </div>

                            <!-- Thumbnails -->
                            <div class="p-4 bg-gray-50">
                                <div id="thumbnailContainer" class="flex gap-2 overflow-x-auto pb-2">
                                    <!-- Thumbnails จะถูกเพิ่มด้วย JavaScript -->
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal -->
            <div id="feedbackModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
                <div class="relative top-20 mx-auto p-5 w-full max-w-4xl">
                    <div class="bg-white rounded-xl shadow-lg p-6">
                        <!-- ปุ่มปิด Modal -->
                        <div class="flex justify-between items-center mb-4 ">
                            <h3 class="text-lg font-semibold text-gray-800 flex items-center ">
                                <svg class="w-5 h-5 mr-2 text-blue-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M21 11.5a8.38 8.38 0 01-.9 3.8 8.5 8.5 0 01-7.6 4.7 8.38 8.38 0 01-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 01-.9-3.8 8.5 8.5 0 014.7-7.6 8.38 8.38 0 013.8-.9h.5a8.48 8.48 0 018 8v.5z" />
                                </svg>
                                รายละเอียดข้อเสนอแนะ
                            </h3>
                            <button onclick="closeFeedbackModal()" class="text-gray-500 hover:text-gray-700">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <!-- Form Content -->
                        <form id="feedbackForm" action="submit_feedback.php" method="POST" onsubmit="return false;">
                            <div class="mb-8">
                                <div class="grid grid-cols-1 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">หัวข้อ</label>
                                        <input type="text" name="title" required
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                            placeholder="กรุณาระบุหัวข้อข้อเสนอแนะ">
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">รายละเอียด</label>
                                        <textarea name="description" rows="4" required
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                            placeholder="กรุณาระบุรายละเอียดข้อเสนอแนะของท่าน"></textarea>
                                    </div>
                                </div>
                            </div>

                            <!-- Image Upload Section -->
                            <div class="mb-8">
                                <label class="block text-sm font-medium text-gray-700 mb-1">รูปภาพประกอบ (ถ้ามี)</label>
                                <div class="mt-1 flex flex-col items-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-lg">
                                    <div class="space-y-1 text-center">
                                        <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                            <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                        <div class="flex text-sm text-gray-600">
                                            <label class="relative cursor-pointer bg-white rounded-md font-medium text-blue-600 hover:text-blue-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-blue-500">
                                                <span class="inline-flex items-center justify-center w-full">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                    </svg>
                                                    อัพโหลดรูปภาพ
                                                </span>
                                                <input type="file" name="images[]" id="imageInput" class="sr-only" multiple accept="image/*" onchange="handleImagePreview(this)">
                                            </label>
                                        </div>
                                        <p class="text-xs text-gray-500">PNG, JPG, GIF ไม่เกิน 10MB</p>

                                    </div>
                                    <div id="imagePreviewContainer" class="grid grid-cols-2 md:grid-cols-3 gap-4 mt-4">
                                    </div>
                                </div>
                            </div>

                            <!-- Submit Button -->
                            <div class="flex justify-end">
                                <button type="submit" class="bg-blue-500 text-white px-6 py-2 rounded-lg hover:bg-blue-600 transform hover:scale-105 transition-all duration-200 flex items-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                    </svg>
                                    ส่งข้อเสนอแนะ
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- เพิ่ม JavaScript สำหรับ Modal -->
    <script>
        function openFeedbackModal() {
            document.getElementById('feedbackModal').classList.remove('hidden');
            document.body.style.overflow = 'hidden'; // ป้องกันการเลื่อนหน้าเว็บ
        }

        function closeFeedbackModal() {
            document.getElementById('feedbackModal').classList.add('hidden');
            document.body.style.overflow = 'auto'; // อนญาตให้เลื่อนหน้าเว็บได้
        }

        // ปิด Modal เมื่อคลิกพื้นหลัง
        document.getElementById('feedbackModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeFeedbackModal();
            }
        });

        // ป้องกันการปิด Modal เมื่อคลิกที่เนื้อหาภายใน
        document.querySelector('#feedbackModal > div').addEventListener('click', function(e) {
            e.stopPropagation();
        });

        // Sidebar Toggle Script เหมือนกับ request.php
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

        // Form Submit Handler
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('feedbackForm');

            // เพิ่ม element แสดงจำนวนรูปภาพ
            const countElement = document.createElement('p');
            countElement.id = 'imageCountText';
            countElement.className = 'text-xs text-gray-500 mt-2';
            countElement.textContent = '0/5 รูป';
            document.querySelector('.space-y-1').appendChild(countElement);

            form.addEventListener('submit', async function(e) {
                e.preventDefault(); // ป้องกันการ submit แบบปกติ

                try {
                    const formData = new FormData(this);

                    // เก็บข้อมูลรูปภาพจาก hidden inputs
                    const imageData = [];
                    document.querySelectorAll('input[name="image_data[]"]').forEach(input => {
                        imageData.push(input.value);
                    });
                    formData.append('images', JSON.stringify(imageData));

                    const response = await fetch('submit_feedback.php', {
                        method: 'POST',
                        body: formData
                    });

                    const data = await response.json();

                    if (data.status === 'success') {
                        alert('ส่งข้อเสนอแนะเรียบร้อยแล้ว');
                        window.location.reload(); // รีโหลดหน้าเพื่อแสดงข้อมูลใหม่
                    } else {
                        throw new Error(data.message);
                    }
                } catch (error) {
                    alert('เกิดข้อผิดพลาด: ' + error.message);
                    console.error('Error:', error);
                }
            });
        });

        function handleImagePreview(input) {
            const previewContainer = document.getElementById('imagePreviewContainer');

            // ไม่ต้องล้างรูปภาพเดิมแล้ว
            // previewContainer.innerHTML = ''; 

            if (input.files) {
                Array.from(input.files).forEach(file => {
                    if (file.type.startsWith('image/')) {
                        const reader = new FileReader();

                        reader.onload = function(e) {
                            const previewWrapper = document.createElement('div');
                            previewWrapper.className = 'relative group';

                            const img = document.createElement('img');
                            img.src = e.target.result;
                            img.className = 'w-full h-32 object-cover rounded-lg';

                            // สร้าง input hidden เพื่อเก็บข้อมูลรูปภาพ
                            const hiddenInput = document.createElement('input');
                            hiddenInput.type = 'hidden';
                            hiddenInput.name = 'image_data[]';
                            hiddenInput.value = e.target.result;

                            // ปุ่มลบรูปภาพ
                            const deleteButton = document.createElement('button');
                            deleteButton.className = 'absolute top-1 right-1 bg-red-500 text-white rounded-full p-1 opacity-0 group-hover:opacity-100 transition-opacity';
                            deleteButton.innerHTML = `
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    `;
                            deleteButton.onclick = function(e) {
                                e.preventDefault();
                                previewWrapper.remove();
                                updateImageCount();
                            };

                            previewWrapper.appendChild(img);
                            previewWrapper.appendChild(hiddenInput);
                            previewWrapper.appendChild(deleteButton);
                            previewContainer.appendChild(previewWrapper);

                            // อัพเดทจำนวนรูปภาพ
                            updateImageCount();
                        }

                        reader.readAsDataURL(file);
                    }
                });
            }

            // เคลียร์ค่า input file เพื่อให้สามารถเลือกรูปเดิมซ้ำได้
            input.value = '';
        }

        // เพิ่มฟังก์ชันนับจำนวนรูปภาพ
        function updateImageCount() {
            const imageCount = document.querySelectorAll('#imagePreviewContainer .relative.group').length;
            const maxImages = 5; // กำหนดจำนวนรูปสูงสุด

            const imageInput = document.getElementById('imageInput');
            const uploadButton = document.querySelector('.cursor-pointer');

            if (imageCount >= maxImages) {
                uploadButton.classList.add('opacity-50', 'cursor-not-allowed');
                imageInput.disabled = true;
            } else {
                uploadButton.classList.remove('opacity-50', 'cursor-not-allowed');
                imageInput.disabled = false;
            }

            // อัพเดทข้อความแสดงจำนวนรูป
            const imageCountText = document.getElementById('imageCountText');
            if (imageCountText) {
                imageCountText.textContent = `${imageCount}/${maxImages} รูป`;
            }
        }

        function filterFeedback(status) {
            const items = document.querySelectorAll('.feedback-item');
            items.forEach(item => {
                if (status === 'all' || item.dataset.status === status) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        }

        let currentImages = [];
        let currentImageIndex = 0;

        function viewImages(feedbackId) {
            fetch(`get_feedback_images.php?feedback_id=${feedbackId}`)
                .then(response => response.json())
                .then(data => {
                    currentImages = data;
                    currentImageIndex = 0;

                    // แสดง Modal
                    document.getElementById('imageModal').classList.remove('hidden');

                    // สร้าง thumbnails
                    const thumbnailContainer = document.getElementById('thumbnailContainer');
                    thumbnailContainer.innerHTML = '';

                    data.forEach((image, index) => {
                        const thumb = document.createElement('div');
                        thumb.className = `flex-shrink-0 cursor-pointer rounded-lg overflow-hidden border-2 
                            ${index === 0 ? 'border-blue-500' : 'border-transparent'}`;
                        thumb.innerHTML = `
                            <img src="${image.image_path}" 
                                class="w-20 h-20 object-cover hover:opacity-90 transition-opacity" 
                                alt="thumbnail ${index + 1}">
                        `;
                        thumb.onclick = () => showImage(index);
                        thumbnailContainer.appendChild(thumb);
                    });

                    // แสดงรูปแรก
                    showImage(0);
                    updateNavigationButtons();
                });
        }

        function showImage(index) {
            if (index < 0 || index >= currentImages.length) return;

            currentImageIndex = index;
            const currentImage = document.getElementById('currentImage');
            currentImage.src = currentImages[index].image_path;

            // อัพเดท thumbnail ที่เลือก
            const thumbnails = document.querySelectorAll('#thumbnailContainer > div');
            thumbnails.forEach((thumb, i) => {
                thumb.className = `flex-shrink-0 cursor-pointer rounded-lg overflow-hidden border-2 
                    ${i === index ? 'border-blue-500' : 'border-transparent'}`;
            });

            // อัพเดทตัวนับรูป
            document.getElementById('imageCounter').textContent = `${index + 1} / ${currentImages.length}`;
            updateNavigationButtons();
        }

        function updateNavigationButtons() {
            document.getElementById('prevButton').style.display = currentImageIndex > 0 ? 'block' : 'none';
            document.getElementById('nextButton').style.display = currentImageIndex < currentImages.length - 1 ? 'block' : 'none';
        }

        // Event Listeners สำหรับปุ่มเลื่อนรูป
        document.getElementById('prevButton').onclick = () => showImage(currentImageIndex - 1);
        document.getElementById('nextButton').onclick = () => showImage(currentImageIndex + 1);

        // ปิด Modal
        function closeImageModal() {
            document.getElementById('imageModal').classList.add('hidden');
            currentImages = [];
            currentImageIndex = 0;
        }

        // รองรับการกดปุ่มลูกศรบนคีย์บอร์ด
        document.addEventListener('keydown', (e) => {
            if (document.getElementById('imageModal').classList.contains('hidden')) return;

            if (e.key === 'ArrowLeft') showImage(currentImageIndex - 1);
            if (e.key === 'ArrowRight') showImage(currentImageIndex + 1);
            if (e.key === 'Escape') closeImageModal();
        });
    </script>
    <?php include '../../components/footer/footer.php'; ?>
</body>

</html>