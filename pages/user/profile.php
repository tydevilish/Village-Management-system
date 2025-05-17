<?php 
session_start();
require_once '../../config/config.php';
require_once '../../includes/auth.php';
include '../../components/menu/Menu.php';

// ตรวจสอบสิทธิ์การเข้าถึงหน้า dashboard
checkPageAccess(PAGE_DASHBOARD);

// ดึงข้อมูลผู้ใช้จากฐานข้อมูล
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
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
        <div id="sidebar" class="fixed top-0 left-0 h-full w-20 z-30 transition-all duration-300 ease-in-out bg-gradient-to-b from-blue-600 to-blue-500 shadow-xl">
            <!-- ย้ายปุ่ม toggle ไปด้านล่าง -->
            <button id="toggleSidebar" class="absolute -right-3 bottom-24 bg-blue-800 text-white rounded-full p-1 shadow-lg hover:bg-blue-900">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </button>
                <!-- Menu Section -->
                <?php renderMenu(); ?>
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
                    toggleIcon.setAttribute('d', 'M15 19l-7-7 7-7'); // ลูกศรชี้ซ้าย
                    textElements.forEach(el => el.classList.remove('opacity-0'));
                } else {
                    sidebar.classList.remove('w-64');
                    sidebar.classList.add('w-20');
                    toggleIcon.setAttribute('d', 'M9 5l7 7-7 7'); // ลูกศรชี้ขวา
                    textElements.forEach(el => el.classList.add('opacity-0'));
                }
            });
        </script>
    </div>

    <div class="flex-1 ml-20">
        <!-- Top Navigation -->
        <nav class="bg-white shadow-sm px-6 py-3">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold text-eva">แก้ไขโปรไฟล์</h1>
                <div class="flex items-center">
                    <a href="https://devcm.info" target="_blank" class="p-2 rounded-full hover:bg-gray-100">
                        <img src="https://devcm.info/img/favicon.png" class="h-6 w-6" alt="User icon">
                    </a>
                </div>
            </div>
        </nav>

        <!-- Profile Edit Form -->
        <div class="container mx-auto px-4 py-5 lg:py-11">
            <div class="bg-white rounded-xl shadow-lg p-6 max-w-7xl mx-auto">
                <form action="update_profile.php" method="POST" enctype="multipart/form-data">
                    <!-- Profile Section -->
                    <div class="flex flex-col lg:flex-row gap-8">
                        <!-- Left Side - Profile Image -->
                        <div class="lg:w-1/4">
                            <div class="relative">
                                <img src="<?php echo !empty($user['profile_image']) ? $user['profile_image'] : '../../src/img_avatar3.png'; ?>"
                                    class="w-full aspect-square rounded-xl object-cover shadow-lg border-4 border-blue-500"
                                    id="preview-image"
                                    alt="Profile picture">
                                <label class="absolute bottom-2 right-2 cursor-pointer bg-blue-500 rounded-full p-2 shadow-lg hover:bg-blue-600 transition-colors">
                                    <svg class="w-4 h-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16">
                                        <path d="m13.498.795.149-.149a1.207 1.207 0 1 1 1.707 1.708l-.149.148a1.5 1.5 0 0 1-.059 2.059L4.854 14.854a.5.5 0 0 1-.233.131l-4 1a.5.5 0 0 1-.606-.606l1-4a.5.5 0 0 1 .131-.232l9.642-9.642a.5.5 0 0 0-.642.056L6.854 4.854a.5.5 0 1 1-.708-.708L9.44.854A1.5 1.5 0 0 1 11.5.796a1.5 1.5 0 0 1 1.998-.001" />
                                    </svg>
                                    <input type="file" name="profile_image" class="hidden" accept="image/*"
                                        onchange="document.getElementById('preview-image').src = window.URL.createObjectURL(this.files[0])">
                                </label>
                            </div>
                        </div>

                        <!-- Right Side - Form Fields -->
                        <div class="lg:w-3/4">
                            <!-- Account Info -->
                            <div class="mb-8">
                                <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-blue-500" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" />
                                    </svg>
                                    ข้อมูลบัญชี
                                </h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">ชื่อผู้ใช้</label>
                                        <input type="text" name="username" readonly
                                            class="w-full px-4 py-2 bg-gray-100 border border-gray-300 rounded-lg"
                                            value="<?php echo htmlspecialchars($user['username']); ?>">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">รหัสผ่าน</label>
                                        <input type="password" name="password" placeholder="ใส่รหัสผ่านใหม่หากต้องการเปลี่ยน"
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    </div>
                                </div>
                            </div>

                            <!-- Personal Info -->
                            <div class="mb-8">
                                <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-blue-500" viewBox="0 0 20 20" fill="currentColor">
                                        <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z" />
                                    </svg>
                                    ข้อมูลส่วนตัว
                                </h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">ชื่อ-นามสกุล</label>
                                        <input type="text" name="fullname"
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                            value="<?php echo htmlspecialchars($user['fullname']); ?>">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">เบอร์โทรศัพท์</label>
                                        <input type="tel" name="phone"
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                            value="<?php echo htmlspecialchars($user['phone']); ?>">
                                    </div>
                                </div>
                            </div>

                            <!-- Contact Address -->
                            <div class="mb-8">
                                <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-blue-500" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd" />
                                    </svg>
                                    ที่อยู่ติดต่อ
                                </h3>
                                <div class="space-y-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">ที่อยู่ตามทะเบียนบ้าน</label>
                                        <textarea name="contact_address" rows="3"
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                        ><?php echo htmlspecialchars($user['contact_address']); ?></textarea>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">ที่อยู่ปัจจุบัน</label>
                                        <textarea name="non_contact_address" rows="3"
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                        ><?php echo htmlspecialchars($user['non_contact_address']); ?></textarea>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">ซอย</label>
                                        <input type="text" name="street"
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                            value="<?php echo htmlspecialchars($user['street']); ?>">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="mt-8 flex justify-end">
                        <button type="submit"
                            class="bg-blue-500 text-white px-6 py-2 rounded-lg hover:bg-blue-600 transform hover:scale-105 transition-all duration-200 flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                            บันทึกข้อมูล
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- เพิ่ม script ก่อน closing body tag -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form');
            form.addEventListener('submit', function(e) {
                e.preventDefault(); // ป้องกันการ submit แบบปกติ

                // สร้าง FormData object
                const formData = new FormData(this);

                // ส่งข้อมูลด้วย fetch API
                fetch('update_profile.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            alert('บันทึกข้อมูลเรียบร้อยแล้ว');
                            window.location.reload();
                        } else {
                            alert('เกิดข้อผิดพลาด: ' + data.message);
                        }
                    })
                    .catch(error => {
                        alert('เกิดข้อผิดพลาดในการบันทึกข้อมูล');
                        console.error('Error:', error);
                    });
            });
        });
    </script>
</body>

</html>