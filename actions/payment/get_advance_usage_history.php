<?php
session_start();
require_once '../../config/config.php';
require_once '../../includes/auth.php';

// ตรวจสอบสิทธิ์
checkPageAccess(PAGE_MANAGE_PAYMENT);

if (isset($_GET['advance_id'])) {
    try {
        $advance_id = intval($_GET['advance_id']);

        // ดึงข้อมูลประวัติการใช้งาน
        $stmt = $conn->prepare("
            SELECT 
                apt.*,
                p.month,
                p.year,
                p.description
            FROM advance_payment_transactions apt
            JOIN payments p ON apt.payment_id = p.payment_id
            WHERE apt.advance_id = ?
            ORDER BY apt.created_at DESC
        ");
        $stmt->execute([$advance_id]);
        $history = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // สร้าง HTML สำหรับแสดงประวัติ
        $html = '<div class="space-y-4">';
        
        foreach ($history as $item) {
            $html .= '<div class="border rounded-lg p-4 bg-gray-50">';
            $html .= '<div class="flex justify-between items-center">';
            $html .= '<div>';
            $html .= '<h4 class="font-semibold">ค่าส่วนกลางเดือน ' . $item['month'] . '/' . $item['year'] . '</h4>';
            $html .= '<p class="text-sm text-gray-600">' . htmlspecialchars($item['description']) . '</p>';
            $html .= '</div>';
            $html .= '<div class="text-right">';
            $html .= '<p class="font-semibold text-green-600">' . number_format($item['amount_used'], 2) . ' บาท</p>';
            $html .= '<p class="text-xs text-gray-500">' . date('d/m/Y H:i', strtotime($item['created_at'])) . '</p>';
            $html .= '</div>';
            $html .= '</div>';
            $html .= '</div>';
        }
        
        if (empty($history)) {
            $html .= '<p class="text-center text-gray-500">ยังไม่มีประวัติการใช้งาน</p>';
        }
        
        $html .= '</div>';

        echo json_encode([
            'success' => true,
            'html' => $html
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'ไม่พบข้อมูล advance_id'
    ]);
} 