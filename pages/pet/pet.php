<?php
session_start();
require_once '../../config/config.php';
require_once '../../includes/auth.php';
include '../../components/menu/Menu.php';

checkPageAccess(PAGE_PET);

// ดึงข้อมูลประเภทสัตว์
$typeStmt = $conn->query("SELECT * FROM pet_types ORDER BY name");
$petTypes = $typeStmt->fetchAll(PDO::FETCH_ASSOC);

// ดึงข้อมูลสัตว์เลี้ยงของผู้ใช้
$stmt = $conn->prepare("
    SELECT p.*, pt.name as type_name, s.name as species_name,
           CASE 
               WHEN p.gender = 'male' THEN 'เพศผู้'
               WHEN p.gender = 'female' THEN 'เพศเมีย'
               ELSE ''
           END as gender_name,
           p.reject_reason
    FROM pets p
    LEFT JOIN pet_types pt ON p.type_id = pt.id
    LEFT JOIN species s ON p.species_id = s.id
    WHERE p.user_id = :user_id
");
$stmt->execute([':user_id' => $_SESSION['user_id']]);
$pets = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
    <!-- Sidebar เหมือนกับใน news.php -->
    <div class="flex">
        <div id="sidebar" class="fixed top-0 left-0 h-full w-20 z-50 transition-all duration-300 ease-in-out bg-gradient-to-b from-blue-600 to-blue-500 shadow-xl">
            <!-- ป่ม toggle -->
            <button id="toggleSidebar" class="absolute -right-3 bottom-24 bg-blue-800 text-white rounded-full p-1 shadow-lg hover:bg-blue-900">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </button>
            <?php renderMenu(); ?>
        </div>

        <div class="flex-1 ml-20">
            <!-- Navbar -->
            <nav class="bg-white shadow-sm px-8 py-6">
                <div class="flex justify-between items-center">
                    <div>
                        <h1 class="text-xl font-bold bg-gradient-to-r from-blue-600 to-blue-800 bg-clip-text text-transparent">ข้อมูลสัตว์เลี้ยง</h1>
                        <p class="text-sm text-gray-500 mt-1">เพิ่มข้อมูลสัตว์เลี้ยงของคุณ</p>
                    </div>
                    <button onclick="openPetModal()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center space-x-2 transition duration-200">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                        </svg>
                        <span>เพิ่มสัตว์เลี้ยง</span>
                    </button>
                </div>
            </nav>

            <!-- เพิ่มส่วนแสดง Cards หลัง Navbar -->
            <div class="p-6 md:p-8 bg-gray-50 min-h-screen">
                <?php if (empty($pets)): ?>
                    <!-- ส่วนแสดงเมื่อไม่มีข้อมูล -->
                    <div class="text-center py-16 bg-white rounded-2xl shadow-sm">
                        <div class="mb-6">
                            <svg class="mx-auto h-16 w-16 text-gray-400 animate-bounce" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4m8-8v16m-4-8h.01M15 12h.01M19 12h.01M8 12h.01" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-semibold text-gray-900 mb-2">ยังไม่มีข้อมูลสัตว์เลี้ยง</h3>
                        <p class="text-gray-500 mb-6">เริ่มเพิ่มข้อมูลสัตว์เลี้ยงของคุณได้เลย</p>
                        <button onclick="openPetModal()" class="inline-flex items-center px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-full transition duration-200 transform hover:scale-105">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                            </svg>
                            เพิ่มสัตว์เลี้ยงตัวแรก
                        </button>
                    </div>
                <?php else: ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 md:gap-8">
                        <?php foreach ($pets as $pet): ?>
                            <div class="bg-white rounded-2xl shadow-sm hover:shadow-xl transition-all duration-300 overflow-hidden group transform hover:-translate-y-1">
                                <div class="relative">
                                    <!-- สถานะ -->
                                    <div class="absolute top-4 right-4 z-20">
                                        <?php
                                        $statusClass = '';
                                        $statusText = '';
                                        $statusIcon = '';
                                        switch ($pet['status']) {
                                            case 'approved':
                                                $statusClass = 'bg-gradient-to-r from-green-500 to-emerald-500 text-white shadow-lg ';
                                                $statusText = 'ได้รับการยืนยันความถูกต้องแล้ว';
                                                $statusIcon = '<svg class="w-3.5 h-3.5 mr-1.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                         </svg>';
                                                break;
                                            case 'pending':
                                                $statusClass = 'bg-gradient-to-r from-amber-400 to-orange-400 text-white shadow-lg';
                                                $statusText = 'รอการตรวจสอบความถูกต้อง';
                                                $statusIcon = '<svg class="w-3.5 h-3.5 mr-1.5 animate-spin" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                         </svg>';
                                                break;
                                            case 'rejected':
                                                $statusClass = 'bg-gradient-to-r from-red-500 to-pink-500 text-white shadow-lg';
                                                $statusText = 'ข้อมูลไม่ถูกต้อง';
                                                $statusIcon = '<svg class="w-3.5 h-3.5 mr-1.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                         </svg>';
                                                break;
                                        }
                                        ?>
                                        <span class="px-4 py-1.5 rounded-full text-xs font-medium flex items-center <?php echo $statusClass; ?>">
                                            <?php echo $statusIcon . $statusText; ?>
                                        </span>
                                    </div>

                                    <!-- รูปภาพ -->
                                    <div class="relative h-64 group-hover:h-72 transition-all duration-500 ease-in-out">
                                        <?php if (!empty($pet['photo'])): ?>
                                            <img src="<?php echo htmlspecialchars($pet['photo']); ?>"
                                                alt="<?php echo htmlspecialchars($pet['pet_name']); ?>"
                                                class="w-full h-full object-cover transition-transform duration-500 ">
                                        <?php else: ?>
                                            <div class="w-full h-full bg-gradient-to-br from-blue-50 to-blue-100 flex items-center justify-center">
                                                <img src="../assets/images/pet-logo.png" alt="Pet Logo" class="w-32 h-32 opacity-40 transition-all duration-300 group-hover:opacity-60">
                                            </div>
                                        <?php endif; ?>
                                        <div class="absolute inset-0 bg-gradient-to-t from-black/30 via-black/10 to-transparent"></div>
                                    </div>

                                    <!-- ข้อมูลหลัก -->
                                    <div class="p-6 relative z-10 -mt-20">
                                        <div class="bg-white/95 backdrop-blur-sm rounded-xl p-6 shadow-lg">
                                            <div class="flex justify-between items-start mb-4">
                                                <div>
                                                    <h3 class="text-xl font-bold text-gray-800 mb-2">
                                                        <?php echo htmlspecialchars($pet['pet_name']); ?>
                                                    </h3>
                                                    <div class="flex items-center gap-3">
                                                        <?php if ($pet['gender'] === 'male'): ?>
                                                            <div class="flex items-center px-3 py-1.5 bg-blue-50 text-blue-600 border border-blue-200 rounded-full">
                                                                <svg class="w-4 h-4 mr-1.5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                                                    <circle cx="9" cy="16" r="5" stroke-width="2" />
                                                                    <path d="M12 12L18 6" stroke-width="2" stroke-linecap="round" />
                                                                    <path d="M13 6h5v5" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                                                </svg>
                                                                <span class="text-xs font-medium">เพศผู้</span>
                                                            </div>
                                                        <?php else: ?>
                                                            <div class="flex items-center px-3 py-1.5 bg-pink-50 text-pink-600 border border-pink-200 rounded-full">
                                                                <svg class="w-4 h-4 mr-1.5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                                                    <circle cx="12" cy="10" r="6" stroke-width="2" />
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 16v6" />
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19h6" />
                                                                </svg>
                                                                <span class="text-xs font-medium">เพศเมีย</span>
                                                            </div>
                                                        <?php endif; ?>

                                                        <?php if (!empty($pet['birthdate'])):
                                                            $birthDate = new DateTime($pet['birthdate']);
                                                            $today = new DateTime();
                                                            $age = $birthDate->diff($today);
                                                        ?>
                                                            <span class="px-3 py-1.5 bg-gray-50 text-gray-600 border border-gray-200 rounded-full text-xs font-medium">
                                                                <?php
                                                                if ($age->y > 0) echo $age->y . ' ปี ';
                                                                if ($age->m > 0) echo $age->m . ' เดือน';
                                                                if ($age->y == 0 && $age->m == 0) echo $age->d . ' วัน';
                                                                ?>
                                                            </span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="space-y-2.5 text-sm">
                                                <!-- ประเภท -->
                                                <div class="flex items-center p-2 hover:bg-gray-50 rounded-lg transition-colors">
                                                    <div class="w-8 h-8 bg-blue-50 rounded-lg flex items-center justify-center mr-3">
                                                        <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                                d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                                        </svg>
                                                    </div>
                                                    <div class="flex-1">
                                                        <span class="text-xs text-gray-500">ประเภท</span>
                                                        <p class="font-medium text-gray-800"><?php echo htmlspecialchars($pet['type_name']); ?></p>
                                                    </div>
                                                </div>

                                                <!-- สายพันธุ์ -->
                                                <div class="flex items-center p-2 hover:bg-gray-50 rounded-lg transition-colors">
                                                    <div class="w-8 h-8 bg-indigo-50 rounded-lg flex items-center justify-center mr-3">
                                                        <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                                d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
                                                        </svg>
                                                    </div>
                                                    <div class="flex-1">
                                                        <span class="text-xs text-gray-500">สายพันธุ์</span>
                                                        <p class="font-medium text-gray-800"><?php echo htmlspecialchars($pet['species_name']); ?></p>
                                                    </div>
                                                </div>

                                                <!-- วันเกิด -->
                                                <?php if (!empty($pet['birthdate'])): ?>
                                                    <div class="flex items-center p-2 hover:bg-gray-50 rounded-lg transition-colors">
                                                        <div class="w-8 h-8 bg-rose-50 rounded-lg flex items-center justify-center mr-3">
                                                            <svg class="w-5 h-5 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                            </svg>
                                                        </div>
                                                        <div class="flex-1">
                                                            <span class="text-xs text-gray-500">วันเกิด</span>
                                                            <p class="font-medium text-gray-800"><?php echo date('d/m/Y', strtotime($pet['birthdate'])); ?></p>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>
                                            </div>

                                            <?php if (!empty($pet['description'])): ?>
                                                <p class="mt-4 text-sm text-gray-500 line-clamp-2 bg-gray-50 p-3 rounded-lg">
                                                    <?php echo nl2br(htmlspecialchars($pet['description'])); ?>
                                                </p>
                                            <?php endif; ?>

                                            <?php if ($pet['status'] === 'rejected' && !empty($pet['reject_reason'])): ?>
                                                <div class="mt-4 bg-red-50 p-4 rounded-lg border border-red-100">
                                                    <div class="flex items-start">
                                                        <svg class="w-5 h-5 text-red-400 mt-0.5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                        </svg>
                                                        <div>
                                                            <p class="text-sm font-medium text-red-800">เหตุผลที่ม่อนุมัติ</p>
                                                            <p class="text-sm text-red-600 mt-1">
                                                                <?php echo htmlspecialchars($pet['reject_reason']); ?>
                                                            </p>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endif; ?>

                                            <!-- ปุ่มดำเนินการ -->
                                            <div class="mt-6 space-y-3">
                                                <button onclick="editPet(<?php echo $pet['id']; ?>)"
                                                    class="w-full flex items-center justify-center px-4 py-2.5 text-sm font-medium text-blue-600 hover:text-white hover:bg-blue-600 rounded-lg transition-all duration-200 border-2 border-blue-600 hover:shadow-lg hover:shadow-blue-100">
                                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                                    </svg>
                                                    แก้ไขข้อมูล
                                                </button>

                                                <?php if ($pet['status'] === 'rejected'): ?>
                                                    <button onclick="resubmitPet(<?php echo $pet['id']; ?>)"
                                                        class="w-full flex items-center justify-center px-4 py-2.5 text-sm font-medium text-yellow-600 hover:text-white hover:bg-yellow-500 rounded-lg transition-all duration-200 border-2 border-yellow-500 hover:shadow-lg hover:shadow-yellow-100">
                                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                                        </svg>
                                                        ส่งตรวจสอบอีกครั้ง
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Modal เพิ่ม/แก้ไขสัตว์เลี้ยง -->
            <div id="petModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 overflow-y-auto">
                <div class="min-h-screen px-4 py-6">
                    <div class="relative max-w-4xl mx-auto">
                        <div class="bg-white rounded-xl shadow-lg">
                            <!-- ส่วนหัว Modal (fixed) -->
                            <div class="sticky top-0 bg-gradient-to-r from-blue-600 to-blue-700 rounded-t-xl px-6 py-4 flex justify-between items-center">
                                <h3 class="text-xl font-semibold text-white flex items-center" id="modalTitle">
                                    <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4m8-8v16" />
                                    </svg>
                                    เพิ่มสัตว์เลี้ยง
                                </h3>
                                <button onclick="closePetModal()" class="text-white hover:text-gray-200 transition-colors">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>

                            <!-- ส่วนเนื้อหา Modal (scrollable) -->
                            <div class="max-h-[calc(100vh-8rem)] overflow-y-auto">
                                <form id="petForm" class="p-6" enctype="multipart/form-data">
                                    <input type="hidden" name="pet_id" id="petId">

                                    <!-- ข้อมูลทั่วไป -->
                                    <div class="mb-8">
                                        <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                                            <svg class="w-5 h-5 mr-2 text-blue-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z" />
                                            </svg>
                                            ข้อมูลสัตว์เลี้ยง
                                        </h3>

                                        <!-- เพิ่มใน่วน grid ของข้อมูลทั่วไป หลังจากชื่อสัตว์เลี้ยง -->
                                        <div class="mb-4">
                                            <label class="block text-sm font-medium text-gray-700 mb-3">เพศ</label>
                                            <div class="flex space-x-4">
                                                <label class="relative flex-1">
                                                    <input type="radio" name="gender" value="male" required
                                                        class="peer absolute opacity-0 w-full h-full cursor-pointer">
                                                    <div class="flex items-center justify-center p-3 bg-white border-2 border-gray-200 rounded-lg cursor-pointer
                peer-checked:border-blue-500 peer-checked:bg-blue-50 hover:bg-gray-50 transition-all duration-200">
                                                        <svg class="w-5 h-5 mr-2 text-blue-600" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                                            <circle cx="9" cy="16" r="5" stroke-width="2" />
                                                            <path d="M12 12L18 6" stroke-width="2" stroke-linecap="round" />
                                                            <path d="M13 6h5v5" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                                        </svg>
                                                        <span class="font-medium text-gray-700">เพศผู้</span>
                                                    </div>
                                                </label>

                                                <label class="relative flex-1">
                                                    <input type="radio" name="gender" value="female" required
                                                        class="peer absolute opacity-0 w-full h-full cursor-pointer">
                                                    <div class="flex items-center justify-center p-3 bg-white border-2 border-gray-200 rounded-lg cursor-pointer
                peer-checked:border-pink-500 peer-checked:bg-pink-50 hover:bg-gray-50 transition-all duration-200">
                                                        <svg class="w-5 h-5 mr-2 text-pink-600" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                                            <circle cx="12" cy="10" r="6" stroke-width="2" />
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 16v6" />
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19h6" />
                                                        </svg>
                                                        <span class="font-medium text-gray-700">เพศเมีย</span>
                                                    </div>
                                                </label>
                                            </div>
                                        </div>

                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <!-- ชื่อสัตว์เลี้ยง -->
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-1">ชื่อสัตว์เลี้ยง</label>
                                                <input type="text" name="pet_name" id="petName" required
                                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                            </div>

                                            <!-- วันเกิด -->
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-1">วันเกิด</label>
                                                <input type="date" name="birthdate" id="petBirthdate" required
                                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                            </div>

                                            <!-- ประเภทสัตว์เลี้ยง -->
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-1">ประเภท</label>
                                                <select name="type_id" id="petType" onchange="handleTypeChange(this.value)"
                                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                                    <option value="">เลือกประเภท</option>
                                                    <?php foreach ($petTypes as $type): ?>
                                                        <option value="<?php echo $type['id']; ?>">
                                                            <?php echo htmlspecialchars($type['name']); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                    <option value="other">อื่นๆ</option>
                                                </select>
                                            </div>

                                            <!-- สายพันธุ์ -->
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-1">สายพันธุ์</label>
                                                <select name="species_id" id="petSpecies" onchange="handleSpeciesChange(this.value)"
                                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                                    <option value="">เลือกสายพันธุ์</option>
                                                </select>
                                            </div>

                                            <!-- ช่องกรอกประเภทอื่นๆ -->
                                            <div id="otherTypeDiv" class="hidden">
                                                <label class="block text-sm font-medium text-gray-700 mb-1">ระบุประเภทอื่นๆ</label>
                                                <input type="text" name="other_type" id="otherType"
                                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                            </div>

                                            <!-- ช่องกรอกสายพันธุ์อื่นๆ -->
                                            <div id="otherSpeciesDiv" class="hidden">
                                                <label class="block text-sm font-medium text-gray-700 mb-1">ระบุสายพันธุ์อื่นๆ</label>
                                                <input type="text" name="other_species" id="otherSpecies"
                                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                            </div>
                                        </div>
                                    </div>

                                    <!-- เพิ่มหลังจากส่วน grid ของข้อมูลทั่วไป -->
                                    <div class="mt-4">
                                        <label class="block text-sm font-medium text-gray-700 mb-1">รายละเอียดเพิ่มเติม</label>
                                        <textarea name="description" id="petDescription" rows="3"
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                            placeholder="เช่น นิสัย, โรคประจำตัว, อาหารที่แพ้ หรือข้อมูลสำคัญอื่นๆ"></textarea>
                                    </div>

                                    <!-- อัพโหลดรูปภาพ -->
                                    <div class="mb-5 mt-2">
                                        <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                                            <svg class="w-5 h-5 mr-2 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                            รูปภาพสัตว์เลี้ยง
                                        </h3>
                                        <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-lg">
                                            <div class="space-y-1 text-center">
                                                <div id="imagePreview" class="hidden mb-3">
                                                    <img src="" alt="Preview" class="mx-auto h-32 w-auto">
                                                </div>
                                                <div class="flex text-sm text-gray-600">
                                                    <label class="relative cursor-pointer bg-white rounded-md font-medium text-blue-600 hover:text-blue-500">
                                                        <span class="inline-flex items-center">
                                                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                                            </svg>
                                                            อัพโหลดรูปภาพ
                                                        </span>
                                                        <input type="file" name="pet_photo" class="sr-only" accept="image/*" onchange="previewImage(this)">
                                                    </label>
                                                </div>
                                                <p class="text-xs text-gray-500">PNG, JPG, GIF ไม่เกิน 10MB</p>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- ส่วนแสดงวัคซีน -->
                                    <div id="vaccineSection" class="mb-8 hidden">
                                        <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                                            <svg class="w-5 h-5 mr-2 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            ประวัติการฉีดวัคซีน
                                        </h3>
                                        <div id="vaccineList" class="space-y-3 bg-gray-50 p-4 rounded-lg">
                                            <!-- วัคซีนจะถูกเพิ่มที่นี่ด้วย JavaScript -->
                                        </div>
                                        <!-- ปุ่มเพิ่มวัคซีน -->
                                        <button type="button" onclick="toggleAddVaccineForm()"
                                            class="flex items-center text-sm text-blue-600 hover:text-blue-700 mb-3 mt-3">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                            </svg>
                                            เพิ่มวัคซีนใหม่
                                        </button>

                                        <!-- ฟอร์มเพิ่มวัคซีน -->
                                        <div id="addVaccineForm" class="hidden bg-blue-50 p-4 rounded-lg border border-blue-100">
                                            <div class="mb-4">
                                                <label class="block text-sm font-medium text-gray-700 mb-1">ชื่อวัคซีน</label>
                                                <input type="text" id="newVaccineName"
                                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                                    placeholder="ระบุชื่อวัคซีน">
                                            </div>
                                            <div class="mb-4">
                                                <label class="block text-sm font-medium text-gray-700 mb-1">รายละเอียด</label>
                                                <textarea id="newVaccineDescription" rows="2"
                                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                                    placeholder="รายละเอียดเพิ่มเติมเกี่ยวกับวัคซีน"></textarea>
                                            </div>
                                            <div class="flex justify-end space-x-2">
                                                <button type="button" onclick="toggleAddVaccineForm()"
                                                    class="px-3 py-1.5 text-sm text-gray-600 bg-white rounded-lg border border-gray-300 hover:bg-gray-50">
                                                    ยกเลิก
                                                </button>
                                                <button type="button" onclick="addCustomVaccine()"
                                                    class="px-3 py-1.5 text-sm text-white bg-blue-600 rounded-lg hover:bg-blue-700">
                                                    เพิ่มวัคซีน
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <script>
                                        function toggleAddVaccineForm() {
                                            const form = document.getElementById('addVaccineForm');
                                            form.classList.toggle('hidden');
                                        }

                                        function addCustomVaccine() {
                                            const name = document.getElementById('newVaccineName').value;
                                            const description = document.getElementById('newVaccineDescription').value;

                                            if (!name) {
                                                alert('กรุณากรอกชื่อวัคซีน');
                                                return;
                                            }

                                            // สร้าง index แทน customId
                                            const index = document.querySelectorAll('.custom-vaccine').length;

                                            // สร้าง element สำหรับวัคซีนใหม่
                                            const vaccineDiv = document.createElement('div');
                                            vaccineDiv.className = 'custom-vaccine flex items-start space-x-3 p-3 bg-white rounded-lg shadow-sm';
                                            vaccineDiv.innerHTML = `
                                                <div class="flex-1 flex items-start space-x-3">
                                                    <div class="flex items-center h-5">
                                                        <input type="checkbox" name="custom_vaccines[]" checked
                                                            class="h-4 w-4 text-blue-600 rounded border-gray-300"
                                                            onchange="toggleVaccineUpload(${index})">
                                                        <input type="hidden" name="custom_vaccine_names[]" value="${name}">
                                                        <input type="hidden" name="custom_vaccine_descriptions[]" value="${description}">
                                                    </div>
                                                    <div class="flex-1">
                                                        <label class="font-medium text-gray-700">${name}</label>
                                                        ${description ? `<p class="text-sm text-gray-500">${description}</p>` : ''}
                                                    </div>
                                                    <div id="vaccine_upload_${index}" class="">
                                                        <label class="relative cursor-pointer">
                                                            <span class="text-sm text-blue-600 hover:text-blue-500 bg-blue-100 rounded-md px-2 py-2">
                                                                <svg class="w-5 h-5 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                                                        d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                                </svg>
                                                                อัพโหลดรูป
                                                            </span>
                                                            <input type="file" name="vaccine_photo_custom_${index}" 
                                                                class="sr-only" accept="image/*" 
                                                                onchange="previewVaccineImage(this, ${index})">
                                                        </label>
                                                        <div id="vaccine_preview_${index}" class="hidden mt-2">
                                                            <img src="" alt="Preview" class="h-20 w-auto object-cover rounded">
                                                            <button type="button" onclick="removeVaccineImage(${index})"
                                                                class="text-xs text-red-600 hover:text-red-500 bg-red-100 rounded-md px-2 py-2 mt-1">
                                                                ลบรูป
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            `;

                                            // เพิ่มวัคซีนใหม่เข้าไปในรายการ
                                            document.getElementById('vaccineList').appendChild(vaccineDiv);
                                        }

                                        // แก้ไขฟังก์ชันที่เกี่ยวข้อง
                                        function toggleVaccineUpload(index) {
                                            const uploadDiv = document.getElementById(`vaccine_upload_${index}`);
                                            const checkbox = document.querySelector(`input[name="custom_vaccines[]"]:nth-of-type(${index + 1})`);
                                            uploadDiv.classList.toggle('hidden', !checkbox.checked);
                                        }

                                        function previewVaccineImage(input, index) {
                                            const preview = document.getElementById(`vaccine_preview_${index}`);
                                            const previewImg = preview.querySelector('img');

                                            if (input.files && input.files[0]) {
                                                const reader = new FileReader();
                                                reader.onload = function(e) {
                                                    previewImg.src = e.target.result;
                                                    preview.classList.remove('hidden');
                                                }
                                                reader.readAsDataURL(input.files[0]);
                                            }
                                        }

                                        function removeVaccineImage(index) {
                                            const preview = document.getElementById(`vaccine_preview_${index}`);
                                            const input = document.querySelector(`input[name="vaccine_photo_custom_${index}"]`);
                                            preview.classList.add('hidden');
                                            input.value = '';
                                        }
                                    </script>
                            </div>


                            <!-- ปุ่มดำเนินการ -->
                            <div class="flex justify-end space-x-3 pb-2 pr-2">
                                <button type="button" onclick="closePetModal()"
                                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">
                                    ยกเลิก
                                </button>
                                <button type="submit"
                                    class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors flex items-center">
                                    <svg class="w-5 h-5 mr-2 " fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                    บันทึก
                                </button>
                            </div>
                            </form>
                        </div>
                    </div>
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

            let currentPetData = null;

            // เปิด Modal เพิ่มสัตว์เลี้ยง
            function openPetModal() {
                document.getElementById('petModal').classList.remove('hidden');
                document.getElementById('modalTitle').textContent = 'เพิ่มสัตว์เลี้ยง';

                // รีเซ็ตฟอร์ม
                document.getElementById('petForm').reset();
                document.getElementById('petId').value = '';

                // รีเซ็ต dropdown สายพันธุ์
                const speciesSelect = document.getElementById('petSpecies');
                speciesSelect.innerHTML = '<option value="">เลือกสายพันธุ์</option>';

                // ซ่อนช่องกรอกอื่นๆ
                document.getElementById('otherTypeDiv').classList.add('hidden');
                document.getElementById('otherSpeciesDiv').classList.add('hidden');

                // ซ่อนตัวอย่างรูปภาพ
                document.getElementById('imagePreview').classList.add('hidden');

                // รีเซ็ตข้อมูลวัคซีน
                const vaccineList = document.getElementById('vaccineList');
                if (vaccineList) {
                    vaccineList.innerHTML = '';
                }

                // ซ่อนส่วนวัคซีน
                const vaccineSection = document.getElementById('vaccineSection');
                if (vaccineSection) {
                    vaccineSection.classList.add('hidden');
                }
            }

            // ปิด Modal
            function closePetModal() {
                document.getElementById('petModal').classList.add('hidden');
            }


            function handleTypeChange(value) {
                const otherTypeDiv = document.getElementById('otherTypeDiv');
                const speciesSelect = document.getElementById('petSpecies');
                const otherSpeciesDiv = document.getElementById('otherSpeciesDiv');

                // ซ่อนช่องกรอกสายพันธุ์อื่นๆ เสมอเมื่อเปลี่ยนประเภท
                otherSpeciesDiv.classList.add('hidden');

                if (value === '') {
                    // ถ้าไม่ได้เลือกประเภท
                    speciesSelect.innerHTML = '<option value="">เลือกสายพันธุ์</option>';
                    otherTypeDiv.classList.add('hidden');
                } else if (value === 'other') {
                    // ถ้าเลือกประเภทอื่นๆ
                    otherTypeDiv.classList.remove('hidden');
                    speciesSelect.innerHTML = `
            <option value="">เลือกสายพันธุ์</option>
            <option value="other">อื่นๆ</option>
        `;
                } else {
                    // ถ้าเลือกประเภทปกติ
                    otherTypeDiv.classList.add('hidden');
                    loadSpecies(value);
                    loadVaccines(value);
                }

                // โหลดข้อมูลวัคซีน
                loadVaccines(value);
            }

            // โหลดข้อมูลวัคซีนตามประเภทสัตว์
            async function loadVaccines(typeId) {
                try {
                    const response = await fetch(`get_vaccines.php?type_id=${typeId}`);
                    const result = await response.json();

                    const vaccineList = document.getElementById('vaccineList');
                    const vaccineSection = document.getElementById('vaccineSection');

                    // เคลียร์ข้อมูลเก่า
                    vaccineList.innerHTML = '';

                    if (result.status === 'error' || !result.data || result.data.length === 0) {
                        // กรณีไม่มีข้อมูลวัคซีน
                        vaccineList.innerHTML = `
                <div class="text-center py-4 bg-red-100 rounded-lg">
                    <svg class="mx-auto h-12 w-12 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                            d="M20 12H4m8-8v16m-4-8h.01M15 12h.01M19 12h.01M8 12h.01" />
                    </svg>
                    <p class="mt-2 text-sm text-red-500">ไม่พบข้อมูลวัคซีนสำหรับสัตว์ประเภทนี้ ในระบบ</p>
                </div>
            `;
                        vaccineSection.classList.remove('hidden');
                        return [];
                    }

                    vaccineList.innerHTML = '';

                    result.data.forEach(vaccine => {

                        const vaccineItem = document.createElement('div');
                        vaccineItem.className = 'flex items-start space-x-3 p-3 bg-white rounded-lg shadow-sm';
                        vaccineItem.innerHTML = `
                                <div class="flex-1 flex items-start space-x-3">
                                    <div class="flex items-center h-5">
                                        <input type="checkbox" id="vaccine_${vaccine.id}" 
                                               name="vaccines[]" value="${vaccine.id}"
                                               onchange="toggleVaccineUpload(${vaccine.id})"
                                               class="h-4 w-4 text-blue-600 rounded border-gray-300">
                                    </div>
                                    <div class="flex-1">
                                        <label class="font-medium text-gray-700">${vaccine.name}</label>
                                        <p class="text-sm text-gray-500">${vaccine.description}</p>
                                    </div>
                                    <div id="vaccine_upload_${vaccine.id}" class="hidden">
                                        <label class="relative cursor-pointer">
                                            <span class="text-sm text-blue-600 hover:text-blue-500 bg-blue-100 rounded-md px-2 py-2">
                                                <svg class="w-5 h-5 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                                          d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                </svg>
                                                อัพโหลดรูป
                                            </span>
                                            <input type="file" name="vaccine_photo_${vaccine.id}" 
                                                   class="sr-only" accept="image/*" 
                                                   onchange="previewVaccineImage(this, ${vaccine.id})">
                                        </label>
                                        <div id="vaccine_preview_${vaccine.id}" class="hidden mt-2">
                                            <img src="" alt="Preview" class="h-20 w-auto object-cover rounded">
                                            <button type="button" onclick="removeVaccineImage(${vaccine.id})"
                                                    class="text-xs text-red-600 hover:text-red-500 bg-red-100 rounded-md px-2 py-2 mt-1">
                                                ลบรูป
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            `;
                        vaccineList.appendChild(vaccineItem);
                    });

                    vaccineSection.classList.remove('hidden');
                    return result.data;
                } catch (error) {
                    console.error('Error loading vaccines:', error);
                    throw error;
                }
            }

            // ปิด/เปิดส่วนอัพโหลดรูปวัคซีน
            function toggleVaccineUpload(vaccineId) {
                const checkbox = document.getElementById(`vaccine_${vaccineId}`);
                const uploadDiv = document.getElementById(`vaccine_upload_${vaccineId}`);

                if (checkbox.checked) {
                    uploadDiv.classList.remove('hidden');
                } else {
                    uploadDiv.classList.add('hidden');
                    // ล้างรูปภาพเมื่อยกเลิกการติ๊ก
                    removeVaccineImage(vaccineId);
                }
            }

            // แสดตัวอย่างรูปภาพวัคซีน
            function previewVaccineImage(input, vaccineId) {
                const preview = document.getElementById(`vaccine_preview_${vaccineId}`);
                const previewImg = preview.querySelector('img');

                if (input.files && input.files[0]) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        previewImg.src = e.target.result;
                        preview.classList.remove('hidden');
                    }
                    reader.readAsDataURL(input.files[0]);
                }
            }

            async function removeVaccineImage(vaccineId) {
                const preview = document.getElementById(`vaccine_preview_${vaccineId}`);
                const input = document.querySelector(`input[name="vaccine_photo_${vaccineId}"]`);
                const petId = document.getElementById('petId').value;

                // ถ้าเป็นการเพิ่มใหม่ (ไม่มี petId) ให้แค่ซ่อนรูป
                if (!petId) {
                    preview.classList.add('hidden');
                    preview.querySelector('img').src = '';
                    input.value = '';
                    return;
                }

                try {
                    const formData = new FormData();
                    formData.append('action', 'remove_photo');
                    formData.append('pet_id', petId);
                    formData.append('vaccine_id', vaccineId);

                    const response = await fetch('update_vaccine.php', {
                        method: 'POST',
                        body: formData
                    });

                    const result = await response.json();
                    if (result.status === 'success') {
                        preview.classList.add('hidden');
                        preview.querySelector('img').src = '';
                        input.value = '';
                    } else {
                        throw new Error(result.message);
                    }
                } catch (error) {
                    console.error('Error:', error);
                    alert('เกิดข้อผิดพลาดในการลบรูปภาพ');
                }
            }

            // โหลดข้อมูลสายพันธุ์ตามประเภทสัตว์
            async function loadSpecies(typeId, selectedSpeciesId = '') {
                if (!typeId) return;

                try {
                    const response = await fetch(`get_species.php?type_id=${typeId}`);
                    const data = await response.json();

                    const speciesSelect = document.getElementById('petSpecies');
                    speciesSelect.innerHTML = '<option value="">เลือกสายพันธุ์</option>';

                    data.forEach(species => {
                        const option = new Option(species.name, species.id);
                        if (species.id === selectedSpeciesId) {
                            option.selected = true;
                        }
                        speciesSelect.appendChild(option);
                    });

                    speciesSelect.appendChild(new Option('อื่นๆ', 'other'));

                    return data;
                } catch (error) {
                    console.error('Error loading species:', error);
                    throw error;
                }
            }

            // จัดการการเปลี่ยนสายพันธุ์
            function handleSpeciesChange(value) {
                const otherSpeciesDiv = document.getElementById('otherSpeciesDiv');
                if (value === 'other') {
                    otherSpeciesDiv.classList.remove('hidden');
                } else {
                    otherSpeciesDiv.classList.add('hidden');
                }
            }

            // แสดงตัวอย่างรูปภาพ
            function previewImage(input) {
                const preview = document.getElementById('imagePreview');
                const previewImg = preview.querySelector('img');

                if (input.files && input.files[0]) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        previewImg.src = e.target.result;
                        preview.classList.remove('hidden');
                    }
                    reader.readAsDataURL(input.files[0]);
                }
            }


            // จัดการการส่งฟอร์ม
            document.getElementById('petForm').addEventListener('submit', function(e) {
                e.preventDefault();

                const formData = new FormData(this);

                fetch('save_pet.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            location.reload();
                        } else {
                            alert('เกิดข้อผิดพลาด: ' + data.message);
                        }
                    });
            });

            function openEditModal(petId) {
                // ดึงข้อมูลสัตว์เลี้ยงด้วย AJAX และแสดงใน Modal
                fetch(`get_pet.php?id=${petId}`)
                    .then(response => response.json())
                    .then(data => {
                        document.getElementById('editPetId').value = data.id;
                        // เติมข้อมูลในฟอร์ม
                        document.getElementById('editModal').classList.remove('hidden');
                        document.getElementById('editModal').classList.add('flex');
                    });
            }

            function closeEditModal() {
                document.getElementById('editModal').classList.add('hidden');
                document.getElementById('editModal').classList.remove('flex');
            }

            async function editPet(petId) {
                try {
                    const response = await fetch(`get_pet.php?id=${petId}`);
                    const petData = await response.json();

                    // นำข้อมูลไปใส่ใน form
                    document.getElementById('pet_id').value = petData.id;
                    document.getElementById('pet_name').value = petData.pet_name;
                    document.getElementById('type_id').value = petData.type_id;
                    await loadSpecies(petData.type_id, petData.species_id);
                    document.getElementById('description').value = petData.description;
                    document.querySelector(`input[name="gender"][value="${petData.gender}"]`).checked = true;
                    document.getElementById('birthdate').value = petData.birthdate;

                    // แสดงรูปภาพเดิม
                    if (petData.photo) {
                        const preview = document.getElementById('imagePreview');
                        const img = preview.querySelector('img') || document.createElement('img');
                        img.src = petData.photo.replace('../../', '../');
                        img.classList.add('w-full', 'h-full', 'object-cover');
                        if (!preview.querySelector('img')) {
                            preview.appendChild(img);
                        }
                    }

                    // เปิด modal
                    document.getElementById('petModal').classList.remove('hidden');

                } catch (error) {
                    console.error('Error:', error);
                    alert('เกิดข้อผิดพลาดในการโหลดข้อมูล');
                }
            }

            async function editPet(petId) {
                try {
                    const response = await fetch(`get_pet.php?id=${petId}`);
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }

                    const result = await response.json();
                    if (result.status === 'error') {
                        throw new Error(result.message);
                    }

                    const petData = result.data;

                    // เปิด modal และเปลี่ยนหัวข้อ
                    document.getElementById('petModal').classList.remove('hidden');
                    document.getElementById('modalTitle').textContent = 'แก้ไขข้อมูลสัตว์เลี้ยง';

                    // เติมข้อมูลในฟอร์ม
                    document.getElementById('petId').value = petData.id;
                    document.getElementById('petName').value = petData.pet_name || '';
                    document.getElementById('petType').value = petData.type_id || '';
                    document.getElementById('petBirthdate').value = petData.birthdate || '';
                    document.getElementById('petDescription').value = petData.description || '';

                    // เลือกเพศ
                    const genderInput = document.querySelector(`input[name="gender"][value="${petData.gender}"]`);
                    if (genderInput) genderInput.checked = true;

                    // โหลดและเลือกสายพันธุ์
                    if (petData.type_id) {
                        await loadSpecies(petData.type_id);
                        if (petData.species_id) {
                            const speciesSelect = document.getElementById('petSpecies');
                            speciesSelect.value = petData.species_id;
                        }
                    }

                    // แสดงรูปภาพเดิม
                    if (petData.photo) {
                        const preview = document.getElementById('imagePreview');
                        preview.classList.remove('hidden');
                        preview.querySelector('img').src = petData.photo;
                    }

                    // โหลดข้อมูลวัคซีน
                    if (petData.type_id) {
                        const vaccines = await loadVaccines(petData.type_id);

                        // ติ๊กวัคซีนที่เคยฉีด
                        if (petData.vaccines && petData.vaccines.length > 0) {
                            petData.vaccines.forEach(vaccine => {
                                const checkbox = document.getElementById(`vaccine_${vaccine.vaccine_id}`);
                                if (checkbox) {
                                    checkbox.checked = true;
                                    toggleVaccineUpload(vaccine.vaccine_id);

                                    // แสดงรูปภาพวัคซีนที่เคยอัพโหลด
                                    if (vaccine.photo) {
                                        const preview = document.getElementById(`vaccine_preview_${vaccine.vaccine_id}`);
                                        const previewImg = preview.querySelector('img');
                                        if (preview && previewImg) {
                                            preview.classList.remove('hidden');
                                            previewImg.src = vaccine.photo;
                                        }
                                    }
                                }
                            });
                        }
                    }

                } catch (error) {
                    console.error('Error:', error);
                    alert('เกิดข้อผิดพลาดในการโหลดข้อมูล: ' + error.message);
                }
            }

            // ... existing code ...

            async function resubmitPet(petId) {
                try {
                    const response = await fetch('resubmit_pet.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            pet_id: petId
                        })
                    });

                    const result = await response.json();

                    if (result.status === 'success') {
                        // รีโหลดหน้าเพื่อแสดงสถานะหม่
                        location.reload();
                    } else {
                        throw new Error(result.message || 'เกิดข้อผิดพลาดในการอัพเดทสถานะ');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    alert(error.message);
                }
            }
        </script>

</body>

</html>