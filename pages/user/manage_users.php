<?php
session_start();
require_once '../../config/config.php';
require_once '../../includes/auth.php';
include '../../components/menu/Menu.php';

// ตรวจสอบสิทธิ์การเข้าถึงหน้า dashboard
checkPageAccess(PAGE_MANAGE_USERS);
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>| ระบบจัดการหมู่บ้าน </title>
    <link rel="icon" href="https://devcm.info/img/favicon.png">
    <link rel="stylesheet" href="../../src/style.css">
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-modern">
    <div class="flex">
        <div id="sidebar"
            class="fixed top-0 left-0 h-full w-20 transition-all duration-300 ease-in-out bg-gradient-to-b from-blue-600 to-blue-500 shadow-xl">
            <!-- ย้ายปุ่ม toggle ไปด้านล่าง -->
            <button id="toggleSidebar"
                class="absolute -right-3 bottom-24 bg-blue-800 text-white rounded-full p-1 shadow-lg hover:bg-blue-900">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </button>
            <!-- Menu Section -->
            <?php renderMenu(); ?>
        </div>
    </div>

    </div>

    <div class="flex-1 ml-20">
        <!-- Top Navigation -->
        <nav class="bg-white shadow-sm px-6 py-3">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold text-eva">จัดการข้อมูลผู้ใช้</h1>
                <div class="flex items-center">

                    <a href="https://devcm.info" target="_blank" class="p-2 rounded-full hover:bg-gray-100">
                        <img src="https://devcm.info/img/favicon.png" class="h-6 w-6" alt="User icon">
                    </a>
                </div>
            </div>
        </nav>

        <!-- Users Table Section -->
        <div class="p-6">
            <?php
            // ดึงข้อมูลผู้ใช้ทั้งหมด
            $stmt = $conn->prepare("SELECT u.*, r.role_name 
                                   FROM users u 
                                   LEFT JOIN roles r ON u.role_id = r.role_id 
                                   ORDER BY u.user_id");
            $stmt->execute();
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $total_users = count($users);

            // ดึงข้อมูล roles ทั้งหมด
            $stmt = $conn->query("SELECT * FROM roles ORDER BY role_id");
            $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);

            include '../../actions/user/check_late_payments.php';
            checkAndTagLatePayments();
            ?>

            <div class="lg:flex justify-between items-center mb-4">
                <div class="items-center">
                    <div class="mb-2">
                        <h2 class="text-xl font-semibold text-gray-800">รายชื่อผู้ใช้ทั้งหมด</h2>
                        <p class="text-sm text-gray-500 mt-1">จำนวนผู้ใช้: <?php echo $total_users; ?> คน</p>
                    </div>
                    <div class="mb-2">
                        <div class="relative">
                            <input type="text" id="searchInput" placeholder="ค้นหาผู้ใช้..." class="w-full md:w-96 pl-10 pr-4 py-2 rounded-lg border-2 border-gray-200 
                   shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 
                   transition-all duration-200 bg-gray-50 hover:bg-white">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="flex space-x-2">
                    <button onclick="openTagManagementModal()" 
                        class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                        </svg>
                        จัดการแท็ก
                    </button>
                    <button onclick="openAddUserModal()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        เพิ่มผู้ใช้
                    </button>
                </div>
            </div>

            <!-- ตารางแสดงข้อมูล -->
            <div class="bg-white rounded-lg shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                    รหัสผู้ใช้</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                    ชื่อ-นามสกุล</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                    ชื่อผู้ใช้</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                    รหัสผ่าน</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                    แท็ก
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                    สิทธิ์การใช้งาน</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                    การกระทำ</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($users as $user): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <?php echo htmlspecialchars($user['user_id']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <?php echo htmlspecialchars($user['fullname']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <?php echo htmlspecialchars($user['username']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <?php echo htmlspecialchars($user['password']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php
                                        $tag_stmt = $conn->prepare("SELECT t.name, t.color 
                               FROM user_tags t 
                               JOIN user_tag_relations r ON t.tag_id = r.tag_id 
                               WHERE r.user_id = ?");
                                        $tag_stmt->execute([$user['user_id']]);
                                        $tags = $tag_stmt->fetchAll(PDO::FETCH_ASSOC);

                                        foreach ($tags as $tag):
                                        ?>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-<?= $tag['color'] ?>-200 text-<?= $tag['color'] ?>-800 mr-1">
                                                <?= htmlspecialchars($tag['name']) ?>
                                            </span>
                                        <?php endforeach; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span
                                            class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-purple-100 text-purple-800">
                                            <?php echo htmlspecialchars($user['role_name']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <button onclick="openEditUserModal(<?php echo $user['user_id']; ?>)"
                                            class="text-blue-600 hover:text-blue-900 mr-3 inline-flex items-center">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" viewBox="0 0 20 20"
                                                fill="currentColor">
                                                <path
                                                    d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                                            </svg>
                                            แก้ไข
                                        </button>
                                        <button onclick="confirmDelete(<?php echo $user['user_id']; ?>)"
                                            class="text-red-600 hover:text-red-900 inline-flex items-center">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" viewBox="0 0 20 20"
                                                fill="currentColor">
                                                <path fill-rule="evenodd"
                                                    d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z"
                                                    clip-rule="evenodd" />
                                            </svg>
                                            ลบ
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>


    <!-- Modal สำหรับเพิ่ม/แก้ไขผู้ใช้ -->
    <div id="userModal" class="hidden fixed inset-0 bg-black bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative min-h-screen flex items-center justify-center p-4">
            <div class="relative w-full max-w-6xl bg-white rounded-lg shadow-2xl">
                <!-- ส่วนหัว Modal -->
                <div class="bg-gradient-to-r from-blue-600 to-blue-700 rounded-t-lg">
                    <div class="flex justify-between items-center p-6">
                        <h3 class="text-2xl font-semibold text-white" id="modalTitle">จัดการข้อมูลผู้ใช้</h3>
                        <button onclick="closeModal()" class="text-white hover:text-gray-200 transition-colors">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- ส่วนเนื้อหา Modal -->
                <div class="p-8">
                    <form id="userForm" class="space-y-6">
                        <input type="hidden" name="action" id="formAction" value="add">
                        <input type="hidden" name="user_id" id="userId">

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- ข้อมูลพื้นฐาน -->
                            <div class="bg-white p-6 rounded-xl shadow-lg border border-gray-100 space-y-4">
                                <h4 class="text-lg font-medium text-blue-600 border-b pb-2">ข้อมูลพื้นฐาน</h4>

                                <div class="relative">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">ชื่อผู้ใช้ *</label>
                                    <div class="relative">
                                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">
                                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                            </svg>
                                        </span>
                                        <input type="text" name="username" required maxlength="10"
                                            class="pl-10 w-full rounded-lg border-2 border-gray-200 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 transition-all duration-200 bg-gray-50 hover:bg-white">
                                    </div>
                                </div>

                                <div class="relative">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">รหัสผ่าน *</label>
                                    <div class="relative">
                                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">
                                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                            </svg>
                                        </span>
                                        <input type="text" name="password" id="password" maxlength="255"
                                            class="pl-10 w-full rounded-lg border-2 border-gray-200 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 transition-all duration-200 bg-gray-50 hover:bg-white">
                                    </div>
                                </div>

                                <div class="relative">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">ชื่อ-นามสกุล</label>
                                    <div class="relative">
                                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">
                                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        </span>
                                        <input type="text" name="fullname" maxlength="255"
                                            class="pl-10 w-full rounded-lg border-2 border-gray-200 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 transition-all duration-200 bg-gray-50 hover:bg-white">
                                    </div>
                                </div>

                                <div class="relative">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">เบอร์โทรศัพท์</label>
                                    <div class="relative">
                                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">
                                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                            </svg>
                                        </span>
                                        <input type="tel" name="phone" maxlength="255"
                                            class="pl-10 w-full rounded-lg border-2 border-gray-200 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 transition-all duration-200 bg-gray-50 hover:bg-white">
                                    </div>
                                </div>

                                <div class="relative">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">แท็ก</label>
                                    <div class="relative">
                                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">
                                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                            </svg>
                                        </span>
                                        <div class="pl-10 w-full rounded-lg border-2 border-gray-200 shadow-sm focus-within:border-blue-500 focus-within:ring focus-within:ring-blue-200 transition-all duration-200 bg-gray-50 hover:bg-white p-2">
                                            <div class="flex flex-wrap gap-2">
                                                <?php
                                                $all_tags_stmt = $conn->query("SELECT * FROM user_tags ORDER BY name");
                                                $all_tags = $all_tags_stmt->fetchAll(PDO::FETCH_ASSOC);

                                                foreach ($all_tags as $tag):
                                                    $color = $tag['color'] ?? 'gray';
                                                ?>
                                                    <label class="inline-flex items-center px-3 py-1.5 rounded-full border border-<?= $color ?>-200 hover:bg-<?= $color ?>-200 transition-colors cursor-pointer group">
                                                        <input type="checkbox" name="tags[]" value="<?= $tag['tag_id'] ?>"
                                                            class="form-checkbox h-4 w-4 text-blue-600 rounded border-gray-300 focus:ring-blue-500 focus:ring-offset-0">
                                                        <span class="ml-2 text-sm text-<?= $color ?>-700 group-hover:text-<?= $color ?>-800">
                                                            <?= htmlspecialchars($tag['name']) ?>
                                                        </span>
                                                    </label>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- ข้อมูลที่อยู่และสิทธิ์ -->
                            <div class="bg-white p-6 rounded-xl shadow-lg border border-gray-100 space-y-4">
                                <h4 class="text-lg font-medium text-blue-600 border-b pb-2">ข้อมูลที่อยู่และสิทธิ์</h4>

                                <div class="relative">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">ซอย</label>
                                    <div class="relative">
                                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">
                                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                            </svg>
                                        </span>
                                        <input type="text" name="street" maxlength="10"
                                            class="pl-10 w-full rounded-lg border-2 border-gray-200 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 transition-all duration-200 bg-gray-50 hover:bg-white">
                                    </div>
                                </div>

                                <div class="relative">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">สิทธิ์การใช้งาน</label>
                                    <div class="relative">
                                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">
                                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                                            </svg>
                                        </span>
                                        <select name="role_id" required
                                            class="pl-10 w-full rounded-lg border-2 border-gray-200 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 transition-all duration-200 bg-gray-50 hover:bg-white">
                                            <option value="">-- เลือกสิทธิ์การใช้งาน --</option>
                                            <?php foreach ($roles as $role): ?>
                                                <option value="<?php echo $role['role_id']; ?>">
                                                    <?php echo htmlspecialchars($role['role_name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>

                                <div class="relative">
                                    <label
                                        class="block text-sm font-medium text-gray-700 mb-1">ที่อยู่ตามทะเบียนบ้าน</label>
                                    <textarea name="non_contact_address" rows="3"
                                        class="w-full rounded-lg border-2 border-gray-200 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 transition-all duration-200 bg-gray-50 hover:bg-white p-3"></textarea>
                                </div>

                                <div class="relative">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">ที่อยู่ปัจจุบัน</label>
                                    <textarea name="contact_address" rows="3"
                                        class="w-full rounded-lg border-2 border-gray-200 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200 transition-all duration-200 bg-gray-50 hover:bg-white p-3"></textarea>
                                </div>

                                <div class="relative">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">สถานะกรรมการ</label>
                                    <div class="relative flex items-center">
                                        <input type="checkbox" name="director" id="director" value="1" 
                                            class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500">
                                        <label for="director" class="ml-2 text-sm text-gray-700">
                                            คณะกรรมการ
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ปุ่มดำเนินการ -->
                        <div class="flex justify-end space-x-4 pt-6 border-t">
                            <button type="button" onclick="closeModal()"
                                class="px-6 py-2.5 text-sm font-medium text-gray-700 bg-white border-2 border-gray-300 rounded-lg hover:bg-gray-50 hover:border-gray-400 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200">
                                ยกเลิก
                            </button>
                            <button type="submit"
                                class="px-6 py-2.5 text-sm font-medium text-white bg-gradient-to-r from-blue-600 to-blue-700 rounded-lg hover:from-blue-700 hover:to-blue-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200 shadow-lg">
                                บันทึก
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- เพิ่ม Modal สำหรับจัดการแท็ก -->
    <div id="tagModal" class="hidden fixed inset-0 bg-black bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative min-h-screen flex items-center justify-center p-4">
            <div class="relative w-full max-w-2xl bg-white rounded-lg shadow-2xl">
                <div class="bg-gradient-to-r from-purple-600 to-purple-700 rounded-t-lg">
                    <div class="flex justify-between items-center p-6">
                        <h3 class="text-2xl font-semibold text-white">จัดการแท็ก</h3>
                        <button onclick="closeTagModal()" class="text-white hover:text-gray-200 transition-colors">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="p-6">
                    <!-- ฟอร์มเพิ่มแท็กใหม่ -->
                    <form id="tagForm" class="mb-6">
                        <div class="flex space-x-4">
                            <div class="flex-1">
                                <input type="text" id="newTagName" placeholder="ชื่อแท็ก" 
                                    class="w-full rounded-lg border-2 border-gray-200 p-2">
                            </div>
                            <div class="w-40">
                                <select id="newTagColor" class="w-full rounded-lg border-2 border-gray-200 p-2">
                                    <option value="blue">น้ำเงิน</option>
                                    <option value="green">เขียว</option>
                                    <option value="red">แดง</option>
                                    <option value="yellow">เหลือง</option>
                                    <option value="purple">ม่วง</option>
                                    <option value="pink">ชมพู</option>
                                    <option value="gray">เทา</option>
                                </select>
                            </div>
                            <button type="submit" class="bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700">
                                เพิ่มแท็ก
                            </button>
                        </div>
                    </form>

                    <!-- รายการแท็กทั้งหมด -->
                    <div id="tagList" class="space-y-2">
                        <!-- แท็กจะถูกเพิ่มที่นี่ด้วย JavaScript -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function openAddUserModal() {
            document.getElementById('modalTitle').textContent = 'เพิ่มผู้ใช้ใหม่';
            document.getElementById('formAction').value = 'add';
            document.getElementById('userId').value = '';
            document.getElementById('password').required = true;
            document.getElementById('userForm').reset();
            document.getElementById('userModal').classList.remove('hidden');
        }

        function openEditUserModal(userId) {
            document.getElementById('modalTitle').textContent = 'แก้ไขข้อมูลผู้ใช้';
            document.getElementById('formAction').value = 'edit';
            document.getElementById('userId').value = userId;
            document.getElementById('password').required = false;

            // ดึงข้อมูลผู้ใช้
            fetch(`../../actions/user/get_user.php?id=${userId}`)
                .then(response => response.json())
                .then(data => {
                    const form = document.getElementById('userForm');
                    // กำหนดค่าให้กับฟอร์ม
                    form.username.value = data.username || '';
                    form.fullname.value = data.fullname || '';
                    form.street.value = data.street || '';
                    form.phone.value = data.phone || '';
                    form.role_id.value = data.role_id || '';
                    form.non_contact_address.value = data.non_contact_address || '';
                    form.contact_address.value = data.contact_address || '';
                    form.director.checked = data.director == 1;
                    
                    // จัดการ tags (ถ้ามี)
                    document.querySelectorAll('input[name="tags[]"]').forEach(checkbox => {
                        checkbox.checked = false;
                    });

                    if (data.tags && Array.isArray(data.tags)) {
                        data.tags.forEach(tagId => {
                            const checkbox = document.querySelector(`input[name="tags[]"][value="${tagId}"]`);
                            if (checkbox) {
                                checkbox.checked = true;
                            }
                        });
                    }

                    document.getElementById('userModal').classList.remove('hidden');
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('เกิดข้อผิดพลาดในการดึงข้อมูล');
                });
        }

        function closeModal() {
            document.getElementById('userModal').classList.add('hidden');
            document.getElementById('userForm').reset();
        }

        function confirmDelete(userId) {
            if (confirm('คุณต้องการลบข้อมูลผู้ใช้นี้ใช่หรือไม่?')) {
                const formData = new FormData();
                formData.append('action', 'delete');
                formData.append('user_id', userId);

                fetch('../../actions/user/process_user.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            alert('ลบข้อมูลเรียบร้อย');
                            location.reload();
                        } else {
                            alert('เกิดข้อผิดพลาด: ' + data.message);
                        }
                    });
            }
        }

        // เพิ่ม event listener สำหรับฟอร์ม
        document.getElementById('userForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);

            fetch('../../actions/user/process_user.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        alert(data.message);
                        location.reload();
                    } else {
                        alert('เกิดข้อผิดพลาด: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('เกิดข้อผิดพลาดในการส่งข้อมูล');
                });
        });

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

        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('searchInput');
            const tableRows = document.querySelectorAll('tbody tr');

            searchInput.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase();

                tableRows.forEach(row => {
                    const text = row.textContent.toLowerCase();
                    row.style.display = text.includes(searchTerm) ? '' : 'none';
                });
            });
        });

        function openTagManagementModal() {
            document.getElementById('tagModal').classList.remove('hidden');
            loadTags();
        }

        function closeTagModal() {
            document.getElementById('tagModal').classList.add('hidden');
        }

        function loadTags() {
            fetch('../../actions/user/get_tags.php')
                .then(response => response.json())
                .then(tags => {
                    const tagList = document.getElementById('tagList');
                    tagList.innerHTML = '';
                    
                    tags.forEach(tag => {
                        const tagElement = document.createElement('div');
                        tagElement.className = 'flex items-center justify-between p-2 bg-gray-50 rounded-lg';
                        tagElement.innerHTML = `
                            <div class="flex items-center space-x-2">
                                <span class="inline-block w-4 h-4 rounded-full bg-${tag.color}-500"></span>
                                <span>${tag.name}</span>
                            </div>
                            <button onclick="deleteTag(${tag.tag_id})" class="text-red-600 hover:text-red-800">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </button>
                        `;
                        tagList.appendChild(tagElement);
                    });
                });
        }

        document.getElementById('tagForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const name = document.getElementById('newTagName').value;
            const color = document.getElementById('newTagColor').value;

            fetch('../../actions/user/process_tag.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'add',
                    name: name,
                    color: color
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    document.getElementById('newTagName').value = '';
                    loadTags();
                    window.location.reload();
                } else {
                    alert(data.message);
                }
            });
        });

        function deleteTag(tagId) {
            if (confirm('คุณต้องการลบแท็กนี้ใช่หรือไม่?')) {
                fetch('../../actions/user/process_tag.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'delete',
                        tag_id: tagId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        loadTags();
                        window.location.reload();
                    } else {
                        alert(data.message);
                    }
                });
            }
        }
    </script>
</body>

</html>