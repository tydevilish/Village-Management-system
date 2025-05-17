<?php
session_start();
require_once '../../config/config.php';
require_once '../../includes/auth.php';
include '../../components/menu/Menu.php';

checkPageAccess(PAGE_PERMISSION);
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
    <div class="flex ">
        <div id="sidebar" class="fixed top-0 left-0 z-50 h-full w-20 transition-all duration-300 ease-in-out bg-gradient-to-b from-blue-600 to-blue-500 shadow-xl">
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


    <div class="ml-20 flex-1">
        <!-- Top Navigation -->
        <nav class="bg-white shadow-sm px-6 py-3">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <h1 class="text-2xl font-bold text-eva ml-4">จัดการสิทธิ์การใช้งาน</h1>
                </div>
                <div class="flex items-center">
                    <a href="https://devcm.info" target="_blank" class="p-2 rounded-full hover:bg-gray-100">
                        <img src="https://devcm.info/img/favicon.png" class="h-6 w-6" alt="User icon">
                    </a>
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <div class="p-6">
            <!-- หัวข้อและปุ่มเพิ่มสิทธิ์ -->
            <div class="lg:flex justify-between items-center mb-6">
                <h2 class="text-xl font-semibold text-gray-800">รายการสิทธิ์การใช้งาน</h2>
                <button onclick="showAddModal()"
                    class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors duration-200 flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    เพิ่มสิทธิ์ใหม่
                </button>
            </div>

            <!-- ตารางสิทธิ์ -->
            <div class="bg-white rounded-lg shadow">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ชื่อสิทธิ์</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">เมนูที่เข้าถึงได้</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">จำนวนเมนู</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">จัดการ</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php
                            $stmt = $conn->query("
                                SELECT r.*, 
                                GROUP_CONCAT(DISTINCT m.menu_name) as menu_names,
                                COUNT(DISTINCT rp.menu_id) as menu_count
                                FROM roles r
                                LEFT JOIN role_permissions rp ON r.role_id = rp.role_id
                                LEFT JOIN menus m ON rp.menu_id = m.menu_id
                                GROUP BY r.role_id
                            ");
                            while ($role = $stmt->fetch(PDO::FETCH_ASSOC)):
                            ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">
                                            <?php echo htmlspecialchars($role['role_name']); ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex flex-wrap gap-2">
                                            <?php
                                            $menus = explode(',', $role['menu_names']);
                                            foreach ($menus as $menu) {
                                                if ($menu) {
                                                    echo '<span class="px-2 py-1 text-xs bg-blue-100 text-blue-800 rounded">' .
                                                        htmlspecialchars($menu) . '</span>';
                                                }
                                            }
                                            ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">
                                            <?php echo $role['menu_count']; ?> เมนู
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <button onclick="showEditModal(<?php echo $role['role_id']; ?>)"
                                            class="text-blue-600 hover:text-blue-900 mr-4">แก้ไข</button>
                                        <button onclick="deleteRole(<?php echo $role['role_id']; ?>)"
                                            class="text-red-600 hover:text-red-900">ลบ</button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <?php include '../../components/footer/footer.php'; ?>

    <!-- Modal เพิ่ม/แก้ไขสิทธิ์ -->
    <div id="permissionModal" class="hidden fixed inset-0 bg-black bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative min-h-screen flex items-center justify-center p-4">
            <div class="relative w-full max-w-2xl bg-white rounded-lg shadow-2xl mx-auto">
                <!-- ส่วนหัว Modal -->
                <div class="bg-gradient-to-r from-blue-600 to-blue-700 rounded-t-lg">
                    <div class="flex justify-between items-center p-6">
                        <h3 id="modalTitle" class="text-2xl font-semibold text-white">เพิ่มสิทธิ์ใหม่</h3>
                        <button onclick="closeModal()" class="text-white hover:text-gray-200 transition-colors">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- ส่วนเนื้อหา Modal -->
                <div class="p-6">
                    <form id="permissionForm" class="space-y-4">
                        <input type="hidden" id="roleId" name="role_id">

                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">
                                ชื่อสิทธิ์ <span class="text-red-500">*</span>
                            </label>
                            <input type="text" id="permissionName" name="permissionName" required
                                class="w-full p-2 rounded-lg border-2 border-gray-200 shadow-sm 
                                       focus:border-blue-500 focus:ring focus:ring-blue-200 
                                       transition-all duration-200 bg-gray-50 hover:bg-white">
                        </div>

                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">
                                เมนูที่สามารถเข้าถึง <span class="text-red-500">*</span>
                            </label>
                            <div class="space-y-2 max-h-60 overflow-y-auto p-4 border-2 border-gray-200 rounded-lg bg-gray-50">
                                <?php
                                $stmt = $conn->query("SELECT * FROM menus ORDER BY menu_order");
                                while ($menu = $stmt->fetch(PDO::FETCH_ASSOC)):
                                ?>
                                    <label class="flex items-center space-x-2 p-2 hover:bg-white rounded transition-colors">
                                        <input type="checkbox" name="menus[]" value="<?php echo $menu['menu_id']; ?>"
                                            class="rounded border-gray-300 text-blue-600 
                                                  focus:ring-blue-500 transition-colors">
                                        <span class="text-sm text-gray-700">
                                            <?php echo htmlspecialchars($menu['menu_name']); ?>
                                        </span>
                                    </label>
                                <?php endwhile; ?>
                            </div>
                        </div>

                        <!-- ปุ่มกดด้านล่าง -->
                        <div class="flex justify-end space-x-3 pt-4 border-t">
                            <button type="button" onclick="closeModal()"
                                class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border-2 
                                       border-gray-300 rounded-lg hover:bg-gray-50 hover:border-gray-400 
                                       focus:outline-none focus:ring-2 focus:ring-offset-2 
                                       focus:ring-blue-500 transition-all duration-200">
                                ยกเลิก
                            </button>
                            <button type="submit"
                                class="px-4 py-2 text-sm font-medium text-white 
                                       bg-gradient-to-r from-blue-600 to-blue-700 rounded-lg 
                                       hover:from-blue-700 hover:to-blue-800 
                                       focus:outline-none focus:ring-2 focus:ring-offset-2 
                                       focus:ring-blue-500 transition-all duration-200 shadow-lg">
                                บันทึก
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- เพิ่ม Script -->
    <script>
        function closeModal() {
            document.getElementById('permissionModal').classList.add('hidden');
            document.getElementById('permissionForm').reset();
        }

        async function showAddModal() {
            document.getElementById('modalTitle').textContent = 'เพิ่มสิทธิ์ใหม่';
            document.getElementById('permissionForm').reset();
            document.getElementById('roleId').value = '';
            document.getElementById('permissionModal').classList.remove('hidden');
        }

        async function showEditModal(id) {
            try {
                const response = await fetch(`../../actions/role/get_role.php?id=${id}`);
                const data = await response.json();

                if (data.success) {
                    document.getElementById('modalTitle').textContent = 'แก้ไขสิทธิ์';
                    document.getElementById('roleId').value = id;
                    document.getElementById('permissionName').value = data.role_name;

                    // รีเซ็ตและเลือก checkbox ตามสิทธิ์ที่มี
                    const checkboxes = document.querySelectorAll('input[name="menus[]"]');
                    checkboxes.forEach(cb => {
                        cb.checked = data.menu_access.includes(parseInt(cb.value));
                    });

                    document.getElementById('permissionModal').classList.remove('hidden');
                } else {
                    alert(data.message || 'เกิดข้อผิดพลาดในการโหลดข้อมูล');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('เกิดข้อผิดพลาดในการโหลดข้อมูล');
            }
        }

        async function deleteRole(id) {
            if (confirm('คุณต้องการลบสิทธิ์นี้ใช่หรือไม่?')) {
                try {
                    const response = await fetch('../../actions/role/delete_role.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            role_id: id
                        })
                    });

                    const result = await response.json();
                    if (result.success) {
                        alert('ลบข้อมูลเรียบร้อยแล้ว');
                        location.reload();
                    } else {
                        alert(result.message || 'เกิดข้อผิดพลาดในการลบข้อมูล');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    alert('เกิดข้อผิดพลาดในการลบข้อมูล');
                }
            }
        }

        document.getElementById('permissionForm').addEventListener('submit', async function(e) {
            e.preventDefault();

            const selectedMenus = document.querySelectorAll('input[name="menus[]"]:checked');
            if (selectedMenus.length === 0) {
                alert('กรุณาเลือกเมนูที่สามารถเข้าถึงอย่างน้อย 1 รายการ');
                return;
            }

            try {
                const formData = new FormData(this);
                const response = await fetch('../../actions/role/save_role.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();
                if (result.success) {
                    alert('บันทึกข้อมูลเรียบร้อย');
                    location.reload();
                } else {
                    alert(result.message || 'เกิดข้อผิดพลาดในการบันทึกข้อมูล');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('เกิดข้อผิดพลาดในการบันทึกข้อมูล');
            }
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
    </script>
</body>

</html>