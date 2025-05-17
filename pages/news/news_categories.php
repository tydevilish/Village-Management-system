<?php
session_start();
require_once '../../config/config.php';
require_once '../../includes/auth.php';
include '../../components/menu/Menu.php';

checkPageAccess(PAGE_NEWS_CATEGORIES);

// ดึงข้อมูลประเภทข่าวสารทั้งหมด
$stmt = $conn->query("
    SELECT c.*, COUNT(n.news_id) as news_count
    FROM news_categories c
    LEFT JOIN news n ON c.category_id = n.category_id
    GROUP BY c.category_id
    ORDER BY c.category_name
");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
    <style>
        /* Mobile Responsive */
        @media (max-width: 640px) {
            /* ปรับตารางให้แสดงแบบ card view บนมือถือ */
            table, thead, tbody, th, td, tr {
                display: block;
            }

            thead tr {
                position: absolute;
                top: -9999px;
                left: -9999px;
            }

            tr {
                margin-bottom: 1rem;
                border: 1px solid #eee;
                border-radius: 0.5rem;
                box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
                background: white;
            }

            td {
                position: relative;
                padding-left: 50% !important;
                border: none !important;
                border-bottom: 1px solid #eee !important;
            }

            td:last-child {
                border-bottom: none !important;
            }

            td:before {
                position: absolute;
                left: 1rem;
                width: 45%;
                padding-right: 10px;
                font-weight: 600;
                color: #6b7280;
            }

            /* ใส่ label สำหรับแต่ละคอลัมน์ */
            td:nth-of-type(1):before { content: "ชื่อประเภท"; }
            td:nth-of-type(2):before { content: "จำนวนข่าว"; }
            td:nth-of-type(3):before { content: "วันที่สร้าง"; }
            td:nth-of-type(4):before { content: "การกระทำ"; }

            /* ปรับปุ่มกระทำให้แสดงเต็มความกว้าง */
            td:last-child .flex {
                flex-direction: column;
                gap: 0.5rem;
            }

            td:last-child button {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>

<body class="bg-modern">
    <div class="flex">
        <div id="sidebar" class="fixed top-0 left-0 h-full w-20 transition-all duration-300 ease-in-out bg-gradient-to-b from-blue-600 to-blue-500 shadow-xl">
            <!-- ปุ่ม toggle -->
            <button id="toggleSidebar" class="absolute -right-3 bottom-24 bg-blue-800 text-white rounded-full p-1 shadow-lg hover:bg-blue-900">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </button>
            <?php renderMenu(); ?>
        </div>

        <div class="flex-1 ml-20">
            <nav class="bg-white shadow-sm px-6 py-4">
                <div class="flex justify-between items-center">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-800">จัดประเภทข่าวสาร</h1>
                        <p class="text-sm text-gray-500 mt-1">จัดการประเภทข่าวสารและประกาศ</p>
                    </div>
                    <button onclick="showAddModal()"
                        class="bg-gradient-to-r from-blue-600 to-blue-700 text-white px-4 py-2 rounded-lg hover:from-blue-700 hover:to-blue-800 transition-all duration-200">
                        เพิ่มประเภทใหม่
                    </button>
                </div>
            </nav>

            <div class="p-6">
                <div class="bg-white rounded-lg shadow-sm hover:shadow-md transition-shadow duration-200">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-sm font-medium text-gray-500 uppercase">ชื่อประเภท</th>
                                <th class="px-6 py-3 text-left text-sm font-medium text-gray-500 uppercase">จำนวนข่าว</th>
                                <th class="px-6 py-3 text-left text-sm font-medium text-gray-500 uppercase">วันที่สร้าง</th>
                                <th class="px-6 py-3 text-left text-sm font-medium text-gray-500 uppercase">การกระทำ</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php foreach ($categories as $category): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($category['category_name']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo $category['news_count']; ?> รายการ</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo date('d/m/Y', strtotime($category['created_at'])); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <button onclick="editCategory(<?php echo $category['category_id']; ?>)"
                                            class='inline-flex items-center text-sm font-medium px-3 py-1.5 bg-blue-100 text-blue-700 rounded-md hover:bg-blue-200 transition-colors '>
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path>
                                            </svg>
                                            แก้ไข
                                        </button>
                                        <?php if ($category['news_count'] == 0): ?>
                                            <button onclick="deleteCategory(<?php echo $category['category_id']; ?>)"
                                                class="inline-flex items-center text-sm font-medium px-3 py-1.5 border-b-2 border-transparent rounded-md text-red-500 bg-red-100 hover:bg-red-200 hover:text-red-600 transition-colors ">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                </svg>
                                                ลบ
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal เพิ่ม/แก้ไขประเภท -->
    <div id="categoryModal" class="hidden fixed inset-0 bg-black bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative min-h-screen flex items-center justify-center p-4">
            <div class="relative w-full max-w-lg bg-white rounded-lg shadow-2xl mx-auto">
                <!-- ส่วนหัว Modal -->
                <div class="bg-gradient-to-r from-blue-600 to-blue-700 rounded-t-lg">
                    <div class="flex justify-between items-center p-6">
                        <h3 id="modalTitle" class="text-2xl font-semibold text-white">เพิ่มประเภทข่าวสาร</h3>
                        <button onclick="closeModal()" class="text-white hover:text-gray-200 transition-colors">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- ฟอร์ม -->
                <form id="categoryForm" class="p-6">
                    <input type="hidden" id="category_id" name="category_id">
                    <input type="hidden" id="action" name="action" value="add">

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="category_name">
                            ชื่อประเภท
                        </label>
                        <input type="text" id="category_name" name="category_name"
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                    </div>

                    <div class="flex justify-end space-x-2">
                        <button type="button" onclick="closeModal()"
                            class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded">
                            ยกเลิก
                        </button>
                        <button type="submit"
                            class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                            บันทึก
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // เพิ่ม JavaScript สำหรับ Sidebar Toggle
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

        function showAddModal() {
            document.getElementById('modalTitle').textContent = 'เพิ่มประเภทข่าวสาร';
            document.getElementById('action').value = 'add';
            document.getElementById('category_id').value = '';
            document.getElementById('category_name').value = '';
            document.getElementById('categoryModal').classList.remove('hidden');
        }

        function editCategory(id) {
            document.getElementById('modalTitle').textContent = 'แก้ไขประเภทข่าวสาร';
            document.getElementById('action').value = 'edit';
            document.getElementById('category_id').value = id;

            // ดึงข้อมูลประเภทข่าวสาร
            fetch(`../../actions/news/get_category.php?id=${id}`)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('category_name').value = data.category_name;
                    document.getElementById('categoryModal').classList.remove('hidden');
                });
        }

        function deleteCategory(id) {
            if (confirm('คุณต้องการลบประเภทข่าวสารนี้ใช่หรือไม่?')) {
                const formData = new FormData();
                formData.append('action', 'delete');
                formData.append('category_id', id);

                fetch('../../actions/news/process_category.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            window.location.reload();
                        } else {
                            alert(data.message);
                        }
                    });
            }
        }

        function closeModal() {
            document.getElementById('categoryModal').classList.add('hidden');
        }

        document.getElementById('categoryForm').addEventListener('submit', function(e) {
            e.preventDefault();

            fetch('../../actions/news/process_category.php', {
                    method: 'POST',
                    body: new FormData(this)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        window.location.reload();
                    } else {
                        alert(data.message);
                    }
                });
        });
    </script>

<?php include '../../components/footer/footer.php'; ?>
</body>

</html>