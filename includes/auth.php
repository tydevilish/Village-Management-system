<?php
function checkPageAccess($page_id) {
    // ตรวจสอบว่ามี session และ menu_access หรือไม่
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['menu_access'])) {
        header('Location: ../../logout.php');
        exit();
    }

    // ตรวจสอบว่ามีสิทธิ์เข้าถึงหน้านี้หรือไม่
    if (!in_array($page_id, $_SESSION['menu_access'])) {
        header('Location: ../../logout.php');
        exit();
    }

    // เพิ่มการตรวจสอบ role_id
    if ($page_id == PAGE_MANAGE_PAYMENT && $_SESSION['role_id'] != 1 && $_SESSION['role_id'] != 7) {
        header('Location: ../../logout.php');
        exit();
    }
}

// กำหนด ID ของแต่ละหน้า (ต้องตรงกับ ID ในฐานข้อมูล)
define('PAGE_DASHBOARD', 1);
define('PAGE_PAYMENT', 2);
define('PAGE_REQUEST', 3);
define('PAGE_VIEW_REQUEST', 4);
define('PAGE_MANAGE_PAYMENT', 5);
define('PAGE_MANAGE_REQUEST', 6);
define('PAGE_MANAGE_USERS', 7);
define('PAGE_PERMISSION', 8);
define('PAGE_MANAGE_VISITORS', 9);
define('PAGE_NEWS', 10);
define('PAGE_MANAGE_NEWS', 11);
define('PAGE_NEWS_CATEGORIES', 12);
define('PAGE_ADVANCE_PAYMENT', 13);
define('PAGE_VIEW_PAYMENT', 14);
define('PAGE_PET', 15);
define('PAGE_MANAGE_PET', 16);
define('PAGE_FEEDBACK', 17);
define('PAGE_MANAGE_FEEDBACK', 18);
define('PAGE_MANAGE_PROJECT', 19);

/**
 * ตรวจสอบว่าผู้ใช้ได้อนุมัติโครงการนี้แล้วหรือยัง
 */
function hasApproved($project_id) {
    global $conn;
    
    if (!isset($_SESSION['user_id'])) {
        return false;
    }

    $stmt = $conn->prepare("
        SELECT COUNT(*) as count 
        FROM project_approvals 
        WHERE project_id = ? AND user_id = ?
    ");
    
    $stmt->execute([$project_id, $_SESSION['user_id']]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $result['count'] > 0;
}

/**
 * ตรวจสอบว่าผู้ใช้เป็นกรรมการหรือไม่
 */
function isDirector() {
    global $conn;
    
    if (!isset($_SESSION['user_id'])) {
        return false;
    }

    $stmt = $conn->prepare("
        SELECT director 
        FROM users 
        WHERE user_id = ?
    ");
    
    $stmt->execute([$_SESSION['user_id']]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $result && $result['director'] == 1;
}
