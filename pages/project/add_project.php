<?php
session_start();
require_once '../../config/config.php';
require_once '../../includes/auth.php';
include '../../components/menu/Menu.php';

checkPageAccess(PAGE_MANAGE_PROJECT);
// ดึงรายชื่อผู้ใช้ทั้งหมดสำหรับเลือกผู้รับผิดชอบ
$stmt = $conn->query("SELECT user_id, fullname , username FROM users WHERE director = 1 ORDER BY fullname");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
        <div id="sidebar" class="fixed top-0 left-0 z-20 h-full w-20 transition-all duration-300 ease-in-out bg-gradient-to-b from-blue-600 to-blue-500 shadow-xl">
            <button id="toggleSidebar" class="absolute -right-3 bottom-24 bg-blue-800 text-white rounded-full p-1 shadow-lg hover:bg-blue-900">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </button>
            <?php renderMenu(); ?>
        </div>
    </div>

    <div class="flex-1 ml-20">
        <!-- Top Navigation -->
        <nav class="bg-white shadow-sm px-6 py-3">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold text-gray-800">เพิ่มโครงการใหม่</h1>
            </div>
        </nav>

        <!-- Form Section -->
        <div class="p-6">
            <form id="projectForm" action="../../actions/project/process_project.php" method="POST" enctype="multipart/form-data" class="max-w-full mx-auto bg-white rounded-xl shadow-lg p-6">
                <input type="hidden" name="action" value="add">
                
                <!-- ข้อมูลพื้นฐาน -->
                <div class="mb-8">
                    <h2 class="text-xl font-semibold text-gray-800 mb-4">ข้อมูลพื้นฐาน</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">ชื่อโครงการ <span class="text-red-500">*</span></label>
                            <input type="text" name="name" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">ประเภทโครงการ <span class="text-red-500">*</span></label>
                            <select name="type" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                <option value="">เลือกประเภท</option>
                                <option value="พัฒนาชุมชน">พัฒนาชุมชน</option>
                                <option value="สาธารณูปโภค">สาธารณูปโภค</option>
                                <option value="สิ่งแวดล้อม">สิ่งแวดล้อม</option>
                                <option value="การศึกษา">การศึกษา</option>
                                <option value="สุขภาพ">สุขภาพ</option>
                                <option value="วัฒนธรรม">วัฒนธรรม</option>
                                <option value="อื่นๆ">อื่นๆ</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- รูปภาพโครงการ -->
                <div class="mb-8">
                    <h2 class="text-xl font-semibold text-gray-800 mb-4">รูปภาพโครงการ</h2>
                    <div class="flex items-center space-x-6">
                        <div class="w-40 h-40 relative">
                            <img id="preview-image" src="../../src/img/default_project.jpg" 
                                class="w-full h-full object-cover rounded-lg border-2 border-gray-300">
                            <label class="absolute bottom-2 right-2 cursor-pointer bg-blue-500 rounded-full p-2 shadow-lg hover:bg-blue-600">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                        d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                        d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                <input type="file" name="project_image" class="hidden" accept="image/*"
                                    onchange="document.getElementById('preview-image').src = window.URL.createObjectURL(this.files[0])">
                            </label>
                        </div>
                        <div class="text-sm text-gray-500">
                            <p>• อัพโหลดรูปภาพโครงการ</p>
                            <p>• ขนาดไฟล์ไม่เกิน 5MB</p>
                            <p>• รองรับไฟล์: JPG, PNG</p>
                        </div>
                    </div>
                </div>

                <!-- ผู้รับผิดชอบโครงการ -->
                <div class="mb-8">
                    <h2 class="text-xl font-semibold text-gray-800 mb-4">ผู้รับผิดชอบโครงการ</h2>
                    <div class="grid grid-cols-1 gap-4">
                        <div class="border border-gray-200 rounded-lg p-4">
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-1">เลือกผู้รับผิดชอบ <span class="text-red-500">*</span></label>
                                <select name="project_managers[]" multiple required
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                    <?php foreach ($users as $user): ?>
                                        <option value="<?php echo $user['user_id']; ?>">
                                            <?php echo htmlspecialchars($user['fullname']); ?>
                                            (<?php echo htmlspecialchars($user['username']); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <p class="text-sm text-gray-500 mt-1">กด Ctrl (Windows) หรือ Command (Mac) เพื่อเลือกหลายคน</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- งบประมาณ -->
                <div class="mb-8">
                    <h2 class="text-xl font-semibold text-gray-800 mb-4">งบประมาณ</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">งบประมาณที่ต้องการ (บาท) <span class="text-red-500">*</span></label>
                            <input type="number" name="budget" required min="0" step="0.01"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        </div>
                    </div>
                </div>

                <!-- รายละเอียดโครงการ -->
                <div class="mb-8">
                    <h2 class="text-xl font-semibold text-gray-800 mb-4">รายละเอียดโครงการ</h2>
                    <div class="space-y-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">หลักการและเหตุผล <span class="text-red-500">*</span></label>
                            <textarea name="principle" required rows="4"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"></textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">วัตถุประสงค์ <span class="text-red-500">*</span></label>
                            <textarea name="objective" required rows="4"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"></textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">เป้าหมาย <span class="text-red-500">*</span></label>
                            <textarea name="target" required rows="4"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"></textarea>
                        </div>
                    </div>
                </div>

                <!-- วิธีดำเนินการ -->
                <div class="mb-8">
                    <h2 class="text-xl font-semibold text-gray-800 mb-4">วิธีดำเนินการ</h2>
                    <div id="methods-container">
                        <div class="method-item mb-4">
                            <div class="flex items-start space-x-4">
                                <div class="flex-grow">
                                    <input type="text" name="method_descriptions[]" placeholder="รายละเอียดวิธีดำเนินการ"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                </div>
                                <button type="button" onclick="removeMethod(this)"
                                    class="text-red-500 hover:text-red-700">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                    <button type="button" onclick="addMethod()"
                        class="mt-2 text-blue-600 hover:text-blue-800 flex items-center">
                        <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        เพิ่มวิธีดำเนินการ
                    </button>
                </div>

                <!-- สถานที่และเวลา -->
                <div class="mb-8">
                    <h2 class="text-xl font-semibold text-gray-800 mb-4">สถานที่และเวลา</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">สถานที่ดำเนินการ <span class="text-red-500">*</span></label>
                            <input type="text" name="location" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">วันที่เริ่ม <span class="text-red-500">*</span></label>
                                <input type="date" name="start_date" required
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">วันที่สิ้นสุด <span class="text-red-500">*</span></label>
                                <input type="date" name="end_date" required
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- แผนการปฏิบัติงาน -->
                <div class="mb-8">
                    <h2 class="text-xl font-semibold text-gray-800 mb-4">แผนการปฏิบัติงาน</h2>
                    <div id="plans-container">
                        <div class="plan-item mb-4 p-4 border border-gray-200 rounded-lg">
                            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                                <div class="md:col-span-2">
                                    <input type="text" name="plan_descriptions[]" placeholder="รายละเอียดแผนงาน"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                </div>
                                <div>
                                    <select name="plan_users[]"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                        <option value="">เลือกผู้รับผิดชอบ</option>
                                        <?php foreach ($users as $user): ?>
                                            <option value="<?php echo $user['user_id']; ?>">
                                                <?php echo htmlspecialchars($user['fullname']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <input type="date" name="plan_start_dates[]" placeholder="วันที่เริ่ม"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                    <input type="date" name="plan_end_dates[]" placeholder="วันที่สิ้นสุด"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                    <button type="button" onclick="removePlan(this)"
                                        class="text-red-500 hover:text-red-700">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <button type="button" onclick="addPlan()"
                        class="mt-2 text-blue-600 hover:text-blue-800 flex items-center">
                        <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        เพิ่มแผนการปฏิบัติงาน
                    </button>
                </div>

                <!-- ปุ่มบันทึก -->
                <div class="flex justify-end space-x-4 border-t pt-6">
                    <button type="button" onclick="window.location.href='manage_project.php'"
                        class="px-6 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">
                        ยกเลิก
                    </button>
                    <button type="submit"
                        class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        บันทึกโครงการ
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // ฟังก์ชันสำหรับจัดการวิธีดำเนินการ
        function addMethod() {
            const container = document.getElementById('methods-container');
            const newMethod = document.createElement('div');
            newMethod.className = 'method-item mb-4';
            newMethod.innerHTML = `
                <div class="flex items-start space-x-4">
                    <div class="flex-grow">
                        <input type="text" name="method_descriptions[]" placeholder="รายละเอียดวิธีดำเนินการ"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    </div>
                    <button type="button" onclick="removeMethod(this)"
                        class="text-red-500 hover:text-red-700">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </button>
                </div>
            `;
            container.appendChild(newMethod);
        }

        function removeMethod(button) {
            const methodItem = button.closest('.method-item');
            methodItem.remove();
        }

        // ฟังก์ชันสำหรับจัดการแผนการปฏิบัติงาน
        function addPlan() {
            const container = document.getElementById('plans-container');
            const newPlan = document.createElement('div');
            newPlan.className = 'plan-item mb-4 p-4 border border-gray-200 rounded-lg';
            newPlan.innerHTML = `
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div class="md:col-span-2">
                        <input type="text" name="plan_descriptions[]" placeholder="รายละเอียดแผนงาน"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <select name="plan_users[]"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                            <option value="">เลือกผู้รับผิดชอบ</option>
                            <?php foreach ($users as $user): ?>
                                <option value="<?php echo $user['user_id']; ?>">
                                    <?php echo htmlspecialchars($user['fullname']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="flex items-center space-x-2">
                        <input type="date" name="plan_start_dates[]" placeholder="วันที่เริ่ม"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        <input type="date" name="plan_end_dates[]" placeholder="วันที่สิ้นสุด"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        <button type="button" onclick="removePlan(this)"
                            class="text-red-500 hover:text-red-700">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </button>
                    </div>
                </div>
            `;
            container.appendChild(newPlan);
        }

        function removePlan(button) {
            const planItem = button.closest('.plan-item');
            planItem.remove();
        }

        // Sidebar Toggle
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

        // Form Validation
        document.getElementById('projectForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            // ตรวจสอบวารกรอกข้อมูล
            let isValid = true;
            const requiredFields = document.querySelectorAll('[required]');
            
            requiredFields.forEach(field => {
                if (!field.value) {
                    isValid = false;
                    field.classList.add('border-red-500');
                } else {
                    field.classList.remove('border-red-500');
                }
            });

            if (!isValid) {
                alert('กรุณากรอกข้อมูลให้ครบถ้วน');
                return;
            }

            // ส่งฟอร์ม
            fetch('../../actions/project/process_project.php', {
                method: 'POST',
                body: new FormData(this)
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    alert(data.message);
                    if (data.redirect) {
                        window.location.href = data.redirect;
                    }
                } else {
                    alert(data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง');
            });
        });
    </script>
</body>

</html>