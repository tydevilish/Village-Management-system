<?php
function renderMenu($currentPage = '')
{
    global $conn;

    // เช็คว่ามีการ login หรือไม่
    if (!isset($_SESSION['user_id'])) {
        header('Location: ../../logout.php');
        exit();
    }

    // ดึงข้อมูลผู้ใช้และบทบาท
    try {
        $stmt = $conn->prepare("
            SELECT u.fullname, u.profile_image, r.role_name , u.director
            FROM users u
            LEFT JOIN roles r ON u.role_id = r.role_id 
            WHERE u.user_id = :user_id
        ");
        $stmt->execute(['user_id' => $_SESSION['user_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // ถ้าไม่มีรูปโปรไฟล์ ใช้รูปเริ่มต้น
        $profileImage = !empty($user['profile_image']) ? $user['profile_image'] : '../../src/img_avatar3.png';
    } catch (PDOException $e) {
        // ถ้าเกิดข้อผิดพลาด ใช้ค่าเริ่มต้น
        $user = [
            'fullname' => 'ไม่พบ้อมูล',
            'role_name' => 'ไม่พบข้อมูล',
            'profile_image' => 'https://img5.pic.in.th/file/secure-sv1/user_avatar.png'
        ];
    }

    $menuItems = [
        [
            'href' => '../dashboard/dashboard',
            'page_id' => PAGE_DASHBOARD,
            'icon' => '<path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" /><polyline points="9 22 9 12 15 12 15 22" />',
            'text' => 'หน้าหลัก'
        ],
        [
            'href' => '../payment/payment',
            'page_id' => PAGE_PAYMENT,
            'icon' => '<rect x="2" y="5" width="20" height="14" rx="2" /><line x1="2" y1="10" x2="22" y2="10" /><path d="M12 15a2 2 0 1 0 0-4 2 2 0 0 0 0 4z" />',
            'text' => 'ชำระค่าส่วนกลาง'
        ],
        [
            'href' => '../request/request',
            'page_id' => PAGE_REQUEST,
            'icon' => '<path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z" /><path d="M15 7l-8 8" />',
            'text' => 'การแจ้งซ่อม'
        ],
        [
            'href' => '../request/view_request',
            'page_id' => PAGE_VIEW_REQUEST,
            'icon' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" /><polyline points="14 2 14 8 20 8" /><line x1="16" y1="13" x2="8" y2="13" /><line x1="16" y1="17" x2="8" y2="17" /><polyline points="10 9 9 9 8 9" />',
            'text' => 'รายละเอียดการแจ้งซ่อม'
        ],
        [
            'href' => '../payment/manage_payment',
            'page_id' => PAGE_MANAGE_PAYMENT,
            'icon' => '<circle cx="9" cy="7" r="4" /><path d="M3 21v-2a4 4 0 0 1 4-4h4a4 4 0 0 1 4 4v2" /><line x1="19" y1="8" x2="19" y2="14" /><line x1="22" y1="11" x2="16" y2="11" />',
            'text' => 'จัดการค่าส่วนกลาง'
        ],
        [
            'href' => '../request/manage_request',
            'page_id' => PAGE_MANAGE_REQUEST,
            'icon' => '<circle cx="12" cy="12" r="3" /><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z" />',
            'text' => 'จัดการแจ้งซ่อม'
        ],
        [
            'href' => '../user/manage_users',
            'page_id' => PAGE_MANAGE_USERS,
            'icon' => '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" /><circle cx="9" cy="7" r="4" /><path d="M23 21v-2a4 4 0 0 0-3-3.87" /><path d="M16 3.13a4 4 0 0 1 0 7.75" />',
            'text' => 'จัดการข้อมูลผู้ใช้'
        ],
        [
            'href' => '../permission/permission',
            'page_id' => PAGE_PERMISSION,
            'icon' => '<rect x="3" y="11" width="18" height="11" rx="2" ry="2" /><path d="M7 11V7a5 5 0 0 1 10 0v4" /><circle cx="12" cy="16" r="1" />',
            'text' => 'จัดการสิทธิ์การใช้งาน'
        ],
        [
            'href' => '../visitor/manage_visitors',
            'page_id' => PAGE_MANAGE_VISITORS,
            'icon' => '<path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />',
            'text' => 'จัดการผู้มาเยือน'
        ],
        [
            'href' => '../news/news',
            'page_id' => PAGE_NEWS,
            'icon' => '<path d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v12a2 2 0 01-2 2z" /><path d="M16 8H8" /><path d="M16 12H8" /><path d="M10 16H8" />',
            'text' => 'ข่าวสาร'
        ],
        [
            'href' => '../news/manage_news',
            'page_id' => PAGE_MANAGE_NEWS,
            'icon' => '<path d="M12 19l7-7 3 3-7 7-3-3z" /><path d="M18 13l-1.5-7.5L2 2l3.5 14.5L13 18l5-5z" /><path d="M2 2l7.586 7.586" /><path d="M11 11l-4 4" />',
            'text' => 'จัดการข่าวสาร'
        ],
        [
            'href' => '../news/news_categories',
            'page_id' => PAGE_NEWS_CATEGORIES,
            'icon' => '<path d="M22 19a2 2 0 01-2 2H4a2 2 0 01-2-2V5a2 2 0 012-2h5l2 3h9a2 2 0 012 2z" /><line x1="8" y1="12" x2="16" y2="12" /><line x1="8" y1="16" x2="14" y2="16" />',
            'text' => 'จัดการหมวดหมู่ข่าวสาร'
        ],
        [
            'href' => '../payment/advance_payment_history',
            'page_id' => PAGE_ADVANCE_PAYMENT,
            'icon' => '<path d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>',
            'text' => 'ประวัติการชำระเงินล่วงหน้า'
        ],
        [
            'href' => '../payment/view_payment',
            'page_id' => PAGE_VIEW_PAYMENT,
            'icon' => '<path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>',
            'text' => 'ดูรายละเอียดค่าส่วนกลาง'
        ],
        [
            'href' => '../pet/pet',
            'page_id' => PAGE_PET,
            'icon' => '<path d="M17 4c-3 0-5 2-5 2s-2-2-5-2C3.5 4 1 6.5 1 10c0 3.199 3 6 3 6s4 4 8 4 8-4 8-4 3-2.801 3-6c0-3.5-2.5-6-6-6z"/>',
            'text' => 'สัตว์เลี้ยง'
        ],
        [
            'href' => '../pet/manage_pet',
            'page_id' => PAGE_MANAGE_PET,
            'icon' => '<path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/>',
            'text' => 'จัดการสัตว์เลี้ยง'
        ],
        [
            'href' => '../feedback/feedback',
            'page_id' => PAGE_FEEDBACK,
            'icon' => '<path d="M21 11.5a8.38 8.38 0 01-.9 3.8 8.5 8.5 0 01-7.6 4.7 8.38 8.38 0 01-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 01-.9-3.8 8.5 8.5 0 014.7-7.6 8.38 8.38 0 013.8-.9h.5a8.48 8.48 0 018 8v.5z" />',
            'text' => 'ส่งข้อเสนอแนะ'
        ],
        [
            'href' => '../feedback/manage_feedback',
            'page_id' => PAGE_MANAGE_FEEDBACK,
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />',
            'text' => 'จัดการข้อเสนอแนะ'
        ],
    ];


    if ($user['director'] == 1) {
        $menuItems[] = [
            'href' => '../project/manage_project',
            'page_id' => PAGE_MANAGE_PROJECT,
            'icon' => '<path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />',
            'text' => 'โครงการ'
        ];
    }


    echo '<div class="px-3 h-screen flex flex-col">
            <!-- Profile Section -->
            <div class="py-4 pl-1 mb-6">
                <div class="flex items-center">
                    <div class="relative flex-shrink-0">
                        <img src="' . $profileImage . '"javascript:void(0)
                            alt="Profile"
                            class="w-12 h-12 rounded-full border-2 border-white shadow-md hover:scale-105 transition-transform duration-200">
                    </div>
                    <div class="ml-4">
<h3 class="text-white font-semibold text-sm opacity-0 pointer-events-none transition-opacity duration-500 ease-in-out whitespace-nowrap overflow-hidden text-ellipsis max-w-[150px]">' . htmlspecialchars($user['fullname']) . '</h3>
                        <p class="text-blue-100 text-xs opacity-0 pointer-events-none transition-opacity duration-500 ease-in-out whitespace-nowrap">' . htmlspecialchars($user['role_name']) . '</p>
                    </div>
                </div>
            </div>

            <!-- Scrollable Menu Section -->
            <div class="flex-1 overflow-y-auto" style="scrollbar-width: thin;">
                <div class="mb-4">
                    <h2 class="text-xs font-bold text-white/80 px-4 mb-2">Menu</h2>
                    <nav class="space-y-2">';

    // ดึงสิทธิ์การเข้าถึงเมนูจาก session
    $menuAccess = $_SESSION['menu_access'] ?? [];

    foreach ($menuItems as $item) {
        // ตรวจสอบสิทธิ์เหมือนเดิม
        if (isset($item['page_id']) && !in_array($item['page_id'], $_SESSION['menu_access'])) {
            continue;
        }

        // เช็คว่าเป็นหน้าปัจจุบันหรือไม่ โดยเทียบกับ menu_path ในฐานข้อมูล
        $currentPath = basename($_SERVER['PHP_SELF']);
        $isCurrentPage = false;

        // ดึง menu_path จากฐานข้อมูล
        $stmt = $conn->prepare("SELECT menu_path FROM menus WHERE menu_id = :menu_id");
        $stmt->execute(['menu_id' => $item['page_id']]);
        $menuPath = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($menuPath) {
            $isCurrentPage = ($currentPath === $menuPath['menu_path']);
        }

        // กำหนด class ตามสถานะของเมนู
        $menuClass = $isCurrentPage
            ? 'flex items-center px-4 py-2.5 text-white bg-white/15 rounded-lg cursor-default'
            : 'flex items-center px-4 py-2.5 text-white hover:bg-white/10 rounded-lg transition-all duration-200';

        // สร้าง link หรือ div ตามสถานะ
        if ($isCurrentPage) {
            echo '<div class="' . $menuClass . '">';
        } else {
            echo '<a href="' . $item['href'] . '" class="' . $menuClass . '">';
        }

        // ไอคอนและข้อความ
        echo '<svg class="w-5 h-5 flex-shrink-0 ' . ($isCurrentPage ? 'text-white' : 'text-white/80') . ' transition-colors" 
                   xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" 
                   stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                ' . $item['icon'] . '
              </svg>
              <span class="ml-3 opacity-0 pointer-events-none transition-opacity duration-500 ease-in-out text-sm whitespace-nowrap">
                ' . $item['text'] . '
              </span>';

        // ปิด tag
        if ($isCurrentPage) {
            echo '</div>';
        } else {
            echo '</a>';
        }
    }

    echo '</nav></div>';

    // Others Section (now part of scrollable area)
    echo '<div class="mb-4">
            <h2 class="text-xs font-bold text-white/80 px-4 mb-2">Others</h2>
            <nav class="space-y-2">';

    // เช็คว่าเป็นหน้า profile หรือไม่
    $isProfilePage = basename($_SERVER['PHP_SELF']) === 'profile.php';

    if ($isProfilePage) {
        echo '<div class="flex items-center px-4 py-2.5 text-white bg-white/20 rounded-lg cursor-default">';
    } else {
        echo '<a href="../user/profile" class="flex items-center px-4 py-2.5 text-white hover:bg-white/10 rounded-lg transition-all duration-200">';
    }

    echo '<svg class="w-5 h-5 flex-shrink-0 ' . ($isProfilePage ? 'text-white' : 'text-white/80') . ' transition-colors" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
            <circle cx="12" cy="7" r="4" />
        </svg>
        <span class="ml-3 opacity-0 pointer-events-none transition-opacity duration-500 ease-in-out text-sm whitespace-nowrap">แก้ไขโปรไฟล์</span>';

    echo $isProfilePage ? '</div>' : '</a>';

    echo '<a href="../../logout.php" class="flex items-center px-4 py-2.5 text-white bg-red-400 hover:bg-red-500 rounded-lg transition-all duration-200">
            <svg class="w-5 h-5 flex-shrink-0 text-white transition-colors" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
                <polyline points="16 17 21 12 16 7" />
                <line x1="21" y1="12" x2="9" y2="12" />
            </svg>
            <span class="ml-3 opacity-0 pointer-events-none transition-opacity duration-500 ease-in-out text-sm whitespace-nowrap">ออกจากระบบ</span>
        </a>
    </nav>
</div>';

    echo '</div></div>';

    // เพิ่ม CSS สำหรับจัดการ scrollbar
    echo '<style>
    /* สำหรับ Firefox */
    .overflow-y-auto {
        scrollbar-width: thin;
        scrollbar-color: rgba(255, 255, 255, 0.2) transparent;
    }
    
    /* สำหรับ Chrome, Safari, และ Edge */
    .overflow-y-auto::-webkit-scrollbar {
        width: 6px;
    }
    
    .overflow-y-auto::-webkit-scrollbar-track {
        background: transparent;
    }
    
    .overflow-y-auto::-webkit-scrollbar-thumb {
        background-color: rgba(255, 255, 255, 0.2);
        border-radius: 3px;
    }
    
    .overflow-y-auto::-webkit-scrollbar-thumb:hover {
        background-color: rgba(255, 255, 255, 0.3);
    }
</style>';
}
