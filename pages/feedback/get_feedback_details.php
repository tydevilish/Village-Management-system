<?php
session_start();
require_once '../../config/config.php';
require_once '../../includes/auth.php';

try {
    $id = $_GET['id'] ?? null;
    if (!$id) throw new Exception('ไม่พบ ID ข้อเสนอแนะ');

    // ดึงข้อมูลข้อเสนอแนะ
    $stmt = $conn->prepare("
        SELECT 
            f.*,
            u.fullname,
            u.username,
            (SELECT COUNT(*) FROM feedback_images WHERE feedback_id = f.id) as image_count
        FROM feedback f
        LEFT JOIN users u ON f.user_id = u.user_id
        WHERE f.id = ?
    ");
    $stmt->execute([$id]);
    $feedback = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$feedback) {
        throw new Exception('คุณไม่มีสิทธิ์ดูรูปภาพนี้');
    }

    // เพิ่มการดึงรูปภาพ
    $stmt = $conn->prepare("
        SELECT image_path 
        FROM feedback_images 
        WHERE feedback_id = ?
    ");
    $stmt->execute([$id]);
    $images = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // เพิ่มการตรวจสอบประเภท
    $hasCategory = !empty($feedback['category_id']);
    $buttonDisabledClass = $hasCategory ? '' : 'opacity-50 cursor-not-allowed';
    $buttonDisabledAttr = $hasCategory ? '' : 'disabled';
    $warningMessage = $hasCategory ? '' : '<div class="text-red-500 text-sm mb-3">* กรุณาเลือกประเภทข้อเสนอแนะก่อนดำเนินการ</div>';

    // สร้าง HTML สำหรับแสดงรายละเอียด
    $html = <<<HTML
    <div class="relative max-h-[90vh] overflow-y-auto">
        <div class="p-6">
            <div class="flex justify-between items-center mb-6 bg-red">
                <h3 class="text-xl font-semibold text-gray-800">รายละเอียดข้อเสนอแนะ</h3>
                <button onclick="document.getElementById('feedbackDetailsModal').classList.add('hidden')" 
                    class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <!-- ข้อมูลผู้แจ้ง -->
            <div class="flex items-start space-x-4 mb-6">
                <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                    <svg class="w-6 h-6 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                </div>
                <div>
                    <h4 class="text-sm font-medium text-gray-900">{$feedback['fullname']}</h4>
                    <p class="text-sm text-gray-500">@{$feedback['username']}</p>
                </div>
            </div>

            <!-- รายละเอียดข้อเสนอแนะ -->
            <div class="bg-gray-50 rounded-lg p-4 mb-6">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">หัวข้อ</label>
                    <p class="text-gray-900">{$feedback['title']}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">รายละเอียด</label>
                    <p class="text-gray-900 whitespace-pre-line">{$feedback['description']}</p>
                </div>
            </div>

            <!-- รูปภาพ -->
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 mb-6">
HTML;

    // เพิ่ม HTML สำหรับแสดงรูปภาพ
    if (!empty($images)) {
        foreach ($images as $image) {
            $html .= sprintf('
                <div class="relative aspect-square">
                    <img src="%s" 
                        class="w-full h-full object-cover rounded-lg cursor-pointer hover:opacity-90" 
                        onclick="openImageViewer(this.src)"
                        alt="Feedback Image">
                </div>
            ', $image['image_path']);
        }
    }

    $html .= <<<HTML
            </div>

            <!-- การดำเนินการ -->
            <div class="border-t pt-6">
                <h4 class="text-sm font-medium text-gray-900 mb-4">ดำเนินการ</h4>
                {$warningMessage}
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <button onclick="updateFeedbackStatus({$feedback['id']}, 'in_progress')"
                        class="px-4 py-2 bg-yellow-100 text-yellow-700 rounded-lg hover:bg-yellow-200 transition-colors {$buttonDisabledClass}"
                        {$buttonDisabledAttr}>
                        กำลังดำเนินการ
                    </button>
                    <button onclick="completedFeedback({$feedback['id']})"
                        class="px-4 py-2 bg-green-100 text-green-700 rounded-lg hover:bg-green-200 transition-colors {$buttonDisabledClass}"
                        {$buttonDisabledAttr}>
                        เสร็จสิ้น
                    </button>
                    <button onclick="rejectFeedback({$feedback['id']})"
                        class="px-4 py-2 bg-red-100 text-red-700 rounded-lg hover:bg-red-200 transition-colors {$buttonDisabledClass}"
                        {$buttonDisabledAttr}>
                        ไม่อนุมัติ
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Image Viewer Modal -->
    <div id="imageViewerModal" class="fixed inset-0 bg-black/90 z-50 hidden" onclick="this.classList.add('hidden')">
        <div class="absolute inset-0 flex items-center justify-center p-4">
            <img id="fullImage" src="" class="max-h-[90vh] max-w-full" alt="Full size image">
        </div>
    </div>

HTML;

    echo json_encode([
        'status' => 'success',
        'html' => $html
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}