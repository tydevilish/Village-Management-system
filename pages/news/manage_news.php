<?php
session_start();
require_once '../../config/config.php';
require_once '../../includes/auth.php';
include '../../components/menu/Menu.php';

checkPageAccess(PAGE_MANAGE_NEWS);

// ดึงข้อมูลประเภทข่าวสาร
$categories = $conn->query("SELECT * FROM news_categories ORDER BY category_name")->fetchAll();

// ดึงข้อมูลข่าวสารทั้งหมด
$stmt = $conn->query("
    SELECT n.*, nc.category_name
    FROM news n
    LEFT JOIN news_categories nc ON n.category_id = nc.category_id
    ORDER BY n.created_at DESC
");
$news = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
    <div class="flex">
        <div id="sidebar" class="fixed top-0 left-0 h-full w-20 transition-all duration-300 ease-in-out bg-gradient-to-b from-blue-600 to-blue-500 shadow-xl">
            <button id="toggleSidebar" class="absolute -right-3 bottom-24 bg-blue-800 text-white rounded-full p-1 shadow-lg hover:bg-blue-900">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </button>
            <?php renderMenu(); ?>
        </div>
    </div>

    <div class="flex-1 ml-20">
        <nav class="bg-white shadow-sm px-6 py-3">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">จัดการข้อมูลข่าวสาร</h1>
                    <p class="text-sm text-gray-500 mt-1">เพิ่ม แก้ไข ลบ ข่าวสารและประกาศ</p>
                </div>
                <button onclick="showAddModal()"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    เพิ่มข่าวสาร
                </button>
            </div>
        </nav>

        <div class="p-6">
            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <!-- เพิ่ม responsive wrapper -->
                    <div class="min-w-full lg:w-auto"></div>
                    <!-- สำหรับหน้าจอขนาดใหญ่ -->
                    <table class="min-w-full divide-y divide-gray-200 hidden lg:table">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs  text-gray-500 uppercase">รูป</th>
                                <th class="px-6 py-3 text-left text-xs  text-gray-500 uppercase">PDF</th>
                                <th class="px-6 py-3 text-left text-xs  text-gray-500 uppercase">ชื่อ</th>
                                <th class="px-6 py-3 text-left text-xs  text-gray-500 uppercase">รายละเอียด</th>
                                <th class="px-6 py-3 text-left text-xs  text-gray-500 uppercase">วันที่สร้าง</th>
                                <th class="px-6 py-3 text-left text-xs  text-gray-500 uppercase">ประเภท</th>
                                <th class="px-6 py-3 text-left text-xs  text-gray-500 uppercase">สถานะ</th>
                                <th class="px-6 py-3 text-left text-xs  text-gray-500 uppercase">การกระทำ</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($news as $item): ?>
                                <tr class="hover:bg-gray-50 transition-colors duration-150">
                                    <td class="px-6 py-4">
                                        <?php if ($item['image_path']): ?>
                                            <img src="<?php echo htmlspecialchars($item['image_path']); ?>"
                                                class="h-10 w-10 object-cover rounded"
                                                alt="<?php echo htmlspecialchars($item['title']); ?>">
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4">
                                        <?php if ($item['pdf_path']): ?>
                                            <svg class="w-8 h-8 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                                    d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                            </svg>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 truncate max-w-xs "><?php echo htmlspecialchars($item['title']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 truncate max-w-xs"><?php echo htmlspecialchars($item['content']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 "><?php echo date('d/m/Y', strtotime($item['created_at'])); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($item['category_name']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php if ($item['status'] === 'active'): ?>
                                            <span class="px-2 py-1 text-sm  text-green-600 bg-green-100 rounded-full">เปิดใช้งาน</span>
                                        <?php else: ?>
                                            <span class="px-2 py-1 text-sm  text-red-600 bg-red-100 rounded-full">ปิดใช้งาน</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex space-x-2">
                                            <button onclick="editNews(<?php echo $item['news_id']; ?>)"
                                                class='inline-flex items-center text-sm  px-3 py-1.5 bg-blue-100 text-blue-700 rounded-md hover:bg-blue-200 transition-colors mr-3'>
                                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path>
                                                </svg>
                                                แก้ไข
                                            </button>
                                            <button onclick="deleteNews(<?php echo $item['news_id']; ?>)"
                                                class="inline-flex items-center text-sm  px-3 py-1.5 border-b-2 border-transparent rounded-md text-red-500 bg-red-100 hover:bg-red-200 hover:text-red-600 transition-colors mr-3">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                </svg>
                                                ลบ
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <!-- สำหรับหน้าจอมือถือ -->
                    <div class="lg:hidden space-y-4">
                        <?php foreach ($news as $item): ?>
                            <div class="bg-white p-4 rounded-lg shadow-sm">
                                <div class="flex items-center space-x-4 mb-3">
                                    <?php if ($item['image_path']): ?>
                                        <img src="<?php echo htmlspecialchars($item['image_path']); ?>"
                                            class="h-16 w-16 object-cover rounded"
                                            alt="<?php echo htmlspecialchars($item['title']); ?>">
                                    <?php elseif ($item['pdf_path']): ?>
                                        <div class="h-16 w-16 flex items-center justify-center bg-gray-100 rounded">
                                            <svg class="w-10 h-10 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                                    d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                            </svg>
                                        </div>
                                    <?php endif; ?>
                                    <div class="flex-1 min-w-0">
                                        <h3 class="text-sm  text-gray-900 truncate">
                                            <?php echo htmlspecialchars($item['title']); ?>
                                        </h3>
                                        <p class="text-xs text-gray-500">
                                            <?php echo date('d/m/Y', strtotime($item['created_at'])); ?>
                                        </p>
                                    </div>
                                    <span class="inline-flex px-2 py-1 text-xs rounded-full <?php echo $item['status'] == 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                        <?php echo $item['status'] == 'active' ? 'เปิดใช้งาน' : 'ปิดใช้งาน'; ?>
                                    </span>
                                </div>
                                <div class="mt-2 space-y-2">
                                    <p class="text-sm text-gray-600 line-clamp-2">
                                        <?php echo htmlspecialchars($item['content']); ?>
                                    </p>
                                    <div class="text-xs text-gray-500">
                                        ประเภท: <?php echo htmlspecialchars($item['category_name']); ?>
                                    </div>
                                    <div class="flex justify-end space-x-2 mt-3">
                                        <button onclick="editNews(<?php echo $item['news_id']; ?>)"
                                            class="px-3 py-1 text-sm bg-blue-50 text-blue-600 rounded hover:bg-blue-100">
                                            แก้ไข
                                        </button>
                                        <button onclick="deleteNews(<?php echo $item['news_id']; ?>)"
                                            class="px-3 py-1 text-sm bg-red-50 text-red-600 rounded hover:bg-red-100">
                                            ลบ
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>
    </div>

    <!-- Modal เพิ่ม/แก้ไขข่าว -->
    <div id="newsModal" class="hidden fixed inset-0 bg-black bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative min-h-screen flex items-center justify-center p-4">
            <div class="relative w-full max-w-2xl bg-white rounded-lg shadow-2xl mx-auto">
                <!-- ส่วนหัว Modal -->
                <div class="bg-gradient-to-r from-blue-600 to-blue-700 rounded-t-lg">
                    <div class="flex justify-between items-center p-4 sm:p-6">
                        <h3 class="text-xl sm:text-2xl font-semibold text-white" id="modalTitle">เพิ่มข่าวสาร</h3>
                        <button onclick="closeModal()" class="text-white hover:text-gray-200 transition-colors">
                            <svg class="h-5 w-5 sm:h-6 sm:w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- เนื้อหา Modal -->
                <div class="p-4 sm:p-6">
                    <form id="newsForm" class="p-6" enctype="multipart/form-data">
                        <input type="hidden" id="action" name="action" value="add">
                        <input type="hidden" id="news_id" name="news_id" value="">

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-2" for="title">
                                    หัวข้อข่าว
                                </label>
                                <input type="text" id="title" name="title" required
                                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                            </div>

                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-2" for="category_id">
                                    ประเภทข่าว
                                </label>
                                <select id="category_id" name="category_id" required
                                    class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                                    <option value="">เลือกประเภทข่าว</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo $category['category_id']; ?>">
                                            <?php echo htmlspecialchars($category['category_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="md:col-span-2">
                                <label class="block text-gray-700 text-sm font-bold mb-2" for="content">
                                    เนื้อหาข่าว
                                </label>
                                <textarea id="content" name="content" rows="6" required
                                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"></textarea>
                            </div>

                            <div class="mb-4">
                                <label class="block text-gray-700 text-sm font-bold mb-2" for="image">
                                    รูปภาพ
                                </label>
                                <input type="file" id="image" name="image" accept="image/*" class="w-full">
                            </div>

                            <!-- เพิ่มส่วนอัพโหลด PDF -->
                            <div class="mb-4">
                                <label class="block text-gray-700 text-sm font-bold mb-2" for="pdf_file">
                                    ไฟล์ PDF (ถ้ามี)
                                </label>
                                <input type="file" id="pdf_file" name="pdf_file" accept=".pdf" class="w-full">
                            </div>

                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-2" for="status">
                                    สถานะ
                                </label>
                                <select id="status" name="status" required
                                    class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                                    <option value="active">เผยแพร่</option>
                                    <option value="inactive">ไม่เผยแพร่</option>
                                </select>
                            </div>
                        </div>

                        <div class="flex justify-end space-x-2 mt-6 pt-6 border-t">
                            <button type="button" onclick="closeModal()"
                                class="px-6 py-2.5 text-sm  text-gray-700 bg-white border-2 border-gray-300 rounded-lg hover:bg-gray-50 hover:border-gray-400 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200">
                                ยกเลิก
                            </button>
                            <button type="submit"
                                class="px-6 py-2.5 text-sm  text-white bg-gradient-to-r from-blue-600 to-blue-700 rounded-lg hover:from-blue-700 hover:to-blue-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200 shadow-lg">
                                บันทึก
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <style>
        /* Mobile Responsive */
        @media (max-width: 640px) {

            /* ปรับตารางให้แสดงแบบ card view บนมือถือ */
            table,
            thead,
            tbody,
            th,
            td,
            tr {
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
            td:nth-of-type(1):before {
                content: "รูป";
            }

            td:nth-of-type(2):before {
                content: "PDF";
            }

            td:nth-of-type(3):before {
                content: "ชื่อ";
            }

            td:nth-of-type(4):before {
                content: "รายละเอียด";
            }

            td:nth-of-type(5):before {
                content: "วันที่สร้าง";
            }

            td:nth-of-type(6):before {
                content: "ประเภท";
            }

            td:nth-of-type(7):before {
                content: "สถานะ";
            }

            td:nth-of-type(8):before {
                content: "การกระทำ";
            }
        }

        /* ปรับแต่ง scrollbar สำหรับ Modal */
        #newsModal .relative::-webkit-scrollbar {
            width: 4px;
        }

        #newsModal .relative::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        #newsModal .relative::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 2px;
        }

        /* ปรับแต่ง animation */
        @keyframes slideIn {
            from {
                transform: translateY(-50px);
                opacity: 0;
            }

            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .modal-content {
            animation: slideIn 0.3s ease-out;
        }
    </style>

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
            document.getElementById('modalTitle').textContent = 'เพิ่มข่าวสาร';
            document.getElementById('action').value = 'add';
            document.getElementById('newsForm').reset();
            document.getElementById('newsModal').classList.remove('hidden');
        }

        function editNews(id) {
            document.getElementById('modalTitle').textContent = 'แก้ไขข่าวสาร';
            document.getElementById('action').value = 'edit';
            document.getElementById('news_id').value = id;

            // ดึงข้อมูลข่าวสาร
            fetch(`../../actions/news/get_news.php?id=${id}`)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('title').value = data.title;
                    document.getElementById('category_id').value = data.category_id;
                    document.getElementById('content').value = data.content;
                    document.getElementById('status').value = data.status;
                    document.getElementById('newsModal').classList.remove('hidden');
                });
        }

        function deleteNews(id) {
            if (confirm('คุณต้องการลบข่าวสารนี้ใช่หรือไม่?')) {
                const formData = new FormData();
                formData.append('action', 'delete');
                formData.append('news_id', id);

                fetch('../../actions/news/process_news.php', {
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
            document.getElementById('newsModal').classList.add('hidden');
        }

        document.getElementById('newsForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);

            fetch('../../actions/news/process_news.php', {
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
        });
    </script>

<?php include '../../components/footer/footer.php'; ?>
</body>

</html>