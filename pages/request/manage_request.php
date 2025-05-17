<?php 
session_start();
require_once '../../config/config.php';
require_once '../../includes/auth.php';
include '../../components/menu/Menu.php';

// ตรวจสอบสิทธิ์การเข้าถึงหน้า dashboard
checkPageAccess(PAGE_MANAGE_REQUEST);
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
        <div id="sidebar" class="fixed top-0 left-0 h-full w-20 transition-all duration-300 ease-in-out bg-gradient-to-b from-blue-600 to-blue-500 shadow-xl">
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

        <div class="flex-1 ml-20">
            <!-- Top Navigation -->
            <nav class="bg-white shadow-sm px-6 py-3">
                <div class="flex items-center justify-between">
                    <h1 class="text-2xl font-bold text-eva">จัดการการแจ้งซ่อม</h1>
                    <div class="flex items-center space-x-4">
                        <div class="relative">
                            <button class="p-2 rounded-full hover:bg-gray-100 relative" onclick="toggleNotifications()">
                                <!-- จุดแจ้งเตือนสีแดง -->
                                <div class="absolute top-2 right-2.5 w-2 h-2 bg-red-500 rounded-full flex items-center justify-center">
                                </div>
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                                </svg>
                            </button>

                            <!-- เพิ่มกล่องแจ้งเตือนใต้กร��ดิ่ง -->
                            <div id="notificationDropdown" class="hidden absolute right-0 top-full mt-2 w-80 bg-white rounded-lg shadow-xl z-50">
                                <div class="p-4">
                                    <div class="space-y-4">
                                        <!-- รายการแจ้งเตือน -->
                                        <a href="payment.php" class="block p-3 bg-blue-50 hover:bg-blue-100 rounded-lg transition-colors">
                                            <div class="flex items-center">
                                                <div class="flex-shrink-0">
                                                    <svg class="w-6 h-6 text-blue-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                        <rect x="2" y="5" width="20" height="14" rx="2" />
                                                        <line x1="2" y1="10" x2="22" y2="10" />
                                                    </svg>
                                                </div>
                                                <div class="ml-3">
                                                    <p class="text-sm font-medium text-gray-800">ค่าส่วนก��างประจำเดือนมีนาคม 2567</p>
                                                    <p class="text-xs text-gray-500">รอการชำระเงิน 500 บาท</p>
                                                </div>
                                            </div>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- เพิ่ม JavaScript ก่อน closing body tag -->
                        <script>
                            function toggleNotifications() {
                                const dropdown = document.getElementById('notificationDropdown');
                                dropdown.classList.toggle('hidden');

                                // ปิดเมื่อคลิกที่อื่น
                                document.addEventListener('click', function closeDropdown(e) {
                                    if (!e.target.closest('#notificationDropdown') && !e.target.closest('button')) {
                                        dropdown.classList.add('hidden');
                                        document.removeEventListener('click', closeDropdown);
                                    }
                                });
                            }
                        </script>
                        <a href="https://devcm.info" class="p-2 rounded-full hover:bg-gray-100">
                            <img src="https://devcm.info/img/favicon.png" class="h-6 w-6" alt="User icon">
                        </a>
                    </div>
                </div>
            </nav>

            <!-- Payment Table Section -->
            <div class="p-6">
                <div class="flex justify-between items-center mb-4">
                    <div>
                        <h2 class="text-xl font-semibold text-gray-800">รายการแจ้งซ่อม</h2>
                        <p class="text-sm text-yellow-600 mt-1">รอดำเนินการ: 5 รายการ</p>
                    </div>
                </div>

                <!-- ตารางแสดงข้อมูล -->
                <div class="bg-white rounded-lg shadow-sm">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">วันที่แจ้ง</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">บ้านเลขที่</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ผู้แจ้ง</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ประเภท</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">สถานะ</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">การกระทำ</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <!-- ข้อมูลจำลอง -->
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">01/03/2024 09:30</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">123/1</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">นายสมชาย ใจดี</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">ไฟฟ้า</td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                            รอดำเนินการ
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <button onclick="showDetailModal('1')" class="text-blue-600 hover:text-blue-900">ดูรายละเอียด</button>
                                    </td>
                                </tr>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">01/03/2024 10:15</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">123/4</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">นางนิภา สุขใจ</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">ประปา</td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                            สำเร็จแล้ว
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <button onclick="showCompletedModal('2')" class="text-blue-600 hover:text-blue-900">ดูรายละเอียด</button>
                                    </td>
                                </tr>
                                <!-- เพิ่มข้อมูลจำลองอื่นๆ -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal แสดงรายละเอียดการแจ้งซ่อม -->
        <div id="detailModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="relative p-8 bg-white w-full max-w-lg rounded-lg shadow-xl">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-xl font-bold text-gray-900">รายละเอียดการแจ้งซ่อม</h3>
                        <button onclick="closeDetailModal()" class="text-gray-400 hover:text-gray-500">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <div class="bg-gray-50 p-4 rounded-lg mb-4">
                        <div class="grid grid-cols-2 gap-4 text-sm">
                            <div class="text-gray-600">วันที่แจ้ง:</div>
                            <div class="font-medium">01/03/2024 09:30</div>
                            <div class="text-gray-600">บ้านเลขที่:</div>
                            <div class="font-medium">123/1</div>
                            <div class="text-gray-600">ผู้แจ้ง:</div>
                            <div class="font-medium">นายสมชาย ใจดี</div>
                            <div class="text-gray-600">เบอร์โทรติดต่อ:</div>
                            <div class="font-medium">081-234-5678</div>
                            <div class="text-gray-600">ประเภท:</div>
                            <div class="font-medium">ไฟฟ้า</div>
                            <div class="text-gray-600">สถานะ:</div>
                            <div class="font-medium">รอดำเนินการ</div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            รายละเอียดปัญหา
                        </label>
                        <p class="text-sm text-gray-600 bg-gray-50 p-3 rounded-lg">
                            ปลั๊กไฟฟ้าลัดวงจร ช็อตจนละลาย
                        </p>
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            รูปภาพประกอบ
                        </label>
                        <div class="mt-1 flex justify-center">
                            <img src="https://img5.pic.in.th/file/secure-sv1/images-1bfdbf256ae93a672.jpeg"
                                alt="รูปภาพปัญหา"
                                class="max-h-64 rounded-lg shadow-sm">
                        </div>
                    </div>

                    <div class="border-t pt-4">
                        <div class="flex justify-between items-center">
                            <button onclick="updateStatus('inProgress')" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">
                                สำเร็จแล้ว
                            </button>
                            <button onclick="closeDetailModal()" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">
                                ปิด
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal สำหรับรายการที่สำเร็จแล้ว -->
        <div id="completedModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="relative p-8 bg-white w-full max-w-lg rounded-lg shadow-xl">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-xl font-bold text-gray-900">รายละเอียดการแจ้งซ่อม</h3>
                        <button onclick="closeCompletedModal()" class="text-gray-400 hover:text-gray-500">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <div class="bg-gray-50 p-4 rounded-lg mb-4">
                        <div class="grid grid-cols-2 gap-4 text-sm">
                            <div class="text-gray-600">วันที่แจ้ง:</div>
                            <div class="font-medium">01/03/2024 10:15</div>
                            <div class="text-gray-600">บ้านเลขที่:</div>
                            <div class="font-medium">123/4</div>
                            <div class="text-gray-600">ผู้แจ้ง:</div>
                            <div class="font-medium">นางนิภา สุขใจ</div>
                            <div class="text-gray-600">เบอร์โทรติดต่อ:</div>
                            <div class="font-medium">082-345-6789</div>
                            <div class="text-gray-600">ประเภท:</div>
                            <div class="font-medium">ประปา</div>
                            <div class="text-gray-600">สถานะ:</div>
                            <div class="font-medium text-green-600">สำเร็จแล้ว</div>
                            <div class="text-gray-600">วันที่เสร็จสิ้น:</div>
                            <div class="font-medium">02/03/2024 15:30</div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            รายละเอียดปัญหา
                        </label>
                        <p class="text-sm text-gray-600 bg-gray-50 p-3 rounded-lg">
                            ท่อน้ำในห้องน้ำรั่ว น้ำหยดตลอดเวลา
                        </p>
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            รูปภาพประกอบ
                        </label>
                        <div class="mt-1 flex justify-center">
                            <img src="https://img2.pic.in.th/pic/41816969122937f01.jpg"
                                alt="รูปภาพปัญหา"
                                class="max-h-64 rounded-lg shadow-sm">
                        </div>
                    </div>

                    <div class="border-t pt-4">
                        <div class="flex justify-end">
                            <button onclick="closeCompletedModal()" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">
                                ปิด
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <script>
            function showDetailModal(id) {
                document.getElementById('detailModal').classList.remove('hidden');
            }

            function closeDetailModal() {
                document.getElementById('detailModal').classList.add('hidden');
            }

            function updateStatus(status) {
                // จำลองการอัพเดทสถานะ
                alert('อัพเดทสถานะเรียบร้อย');
                closeDetailModal();
            }

            function showCompletedModal(id) {
                document.getElementById('completedModal').classList.remove('hidden');
            }

            function closeCompletedModal() {
                document.getElementById('completedModal').classList.add('hidden');
            }

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
        </script>
</body>

</html>