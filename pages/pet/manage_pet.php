<?php
session_start();
require_once '../../config/config.php';
require_once '../../includes/auth.php';
include '../../components/menu/Menu.php';

checkPageAccess(PAGE_MANAGE_PET);

// ดึงจำนวนสัตว์เลี้ยงที่รอการอนุมัติ
$stmt = $conn->prepare("
    SELECT COUNT(*) as pending_pets
    FROM pets
    WHERE status = 'pending'
");
$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_ASSOC);
$pending_pets = $result['pending_pets'];

// ดึงข้อมูลสัตว์เลี้ยงทั้งหมด
$stmt = $conn->prepare("
    SELECT 
        p.*, 
        pt.name as type_name, 
        s.name as species_name,
        u.username,
        u.fullname,
        CASE 
            WHEN p.gender = 'male' THEN 'เพศผู้'
            WHEN p.gender = 'female' THEN 'เพศเมีย'
            ELSE ''
        END as gender_name
    FROM pets p
    LEFT JOIN pet_types pt ON p.type_id = pt.id
    LEFT JOIN species s ON p.species_id = s.id
    LEFT JOIN users u ON p.user_id = u.user_id
    ORDER BY p.created_at DESC
");
$stmt->execute();
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
    <style>
        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }
    </style>
</head>

<body class="bg-modern">
    <div class="flex">
        <div id="sidebar" class="fixed top-0 left-0 z-20 h-full w-20 transition-all duration-300 ease-in-out bg-gradient-to-b from-blue-600 to-blue-500 shadow-xl">
            <!-- ป่ม toggle -->
            <button id="toggleSidebar" class="absolute -right-3 bottom-24 bg-blue-800 text-white rounded-full p-1 shadow-lg hover:bg-blue-900">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </button>
            <?php renderMenu(); ?>
        </div>

        <!-- Main Content -->
        <div class="flex-1 ml-20">
            <!-- Navbar -->
            <nav class="bg-white shadow-sm px-8 py-6">
                <div class="flex justify-between items-center">
                    <div>
                        <h1 class="text-xl font-bold bg-gradient-to-r from-blue-600 to-blue-800 bg-clip-text text-transparent">
                            จัดการข้อมูลสัตว์เลี้ยง
                        </h1>
                        <p class="text-sm text-gray-500 mt-2">ตรวจสอบและอนุมัติข้อมูลสัตว์เลี้ยงภายในหมู่บ้าน</p>
                    </div>
                    <div class="flex items-center space-x-4">
                        <div class=" p-0.5 rounded-xl">
                            <div class="bg-white px-4 py-2 rounded-lg flex items-center space-x-3">
                                <div class="w-8 h-8 bg-amber-50 rounded-lg flex items-center justify-center">
                                    <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div>
                                    <div class="text-xs text-gray-500">รอการตรวจสอบความถูกต้อง</div>
                                    <div class="text-sm font-semibold text-gray-800"><?php echo $pending_pets; ?> รายการ</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </nav>

            <!-- Tabs & Content -->
            <div class="p-8 bg-gray-50 min-h-[calc(100vh-5rem)]">
                <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
                    <!-- Tabs -->
                    <div class="border-b bg-white/80 backdrop-blur-sm sticky top-0 z-10">
                        <div class="flex px-2">
                            <button onclick="showTab('all')" class="tab-btn group relative px-6 py-4">
                                <div class="relative flex items-center space-x-2">
                                    <svg class="w-5 h-5 text-gray-400 group-hover:text-blue-600 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                                    </svg>
                                    <span class="text-sm font-medium text-gray-600 group-hover:text-blue-600 transition-colors">ทั้งหมด</span>
                                </div>
                                <div class="absolute bottom-0 left-0 w-full h-0.5 bg-blue-600 transform scale-x-0 group-hover:scale-x-100 transition-transform"></div>
                            </button>
                            <button onclick="showTab('pending')" class="tab-btn group relative px-6 py-4">
                                <div class="relative flex items-center space-x-2">
                                    <svg class="w-5 h-5 text-gray-400 group-hover:text-yellow-600 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <span class="text-sm font-medium text-gray-600 group-hover:text-yellow-600 transition-colors">รอการตรวจสอบความถูกต้อง</span>
                                </div>
                                <div class="absolute bottom-0 left-0 w-full h-0.5 bg-yellow-500 transform scale-x-0 group-hover:scale-x-100 transition-transform"></div>
                            </button>
                            <!-- เพิ่ม tab อื่นๆ ในรูปแบบเดียวกัน -->
                        </div>
                    </div>

                    <!-- Content -->
                    <div class="p-6">
                        <!-- Search -->
                        <div class="mb-6 max-w-2xl mx-auto">
                            <div class="relative">
                                <input type="text" id="searchPets"
                                    placeholder="ค้นหาสัตว์เลี้ยงด้วยชื่อ, ประเภท, หรือสายพันธุ์..."
                                    class="w-full pl-12 pr-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-100 focus:border-blue-400 transition-all duration-200">
                                <svg class="w-5 h-5 text-gray-400 absolute left-4 top-1/2 transform -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>
                        </div>

                        <!-- Pet List -->
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <?php foreach ($pets as $pet): ?>
                                <div class="pet-card bg-white rounded-2xl shadow-sm hover:shadow-lg transition-all duration-300 transform hover:-translate-y-1 overflow-hidden border border-gray-100"
                                    data-status="<?php echo $pet['status']; ?>">
                                    <!-- Pet Image -->
                                    <div class="relative h-56 group">
                                        <img src="<?php echo $pet['photo'] ?: '../../assets/images/pet-placeholder.jpg'; ?>"
                                            alt="<?php echo htmlspecialchars($pet['pet_name']); ?>"
                                            class="w-full h-full object-cover transition-transform duration-500 ">
                                        <div class="absolute inset-0 bg-gradient-to-t from-black/30 via-black/10 to-transparent"></div>

                                        <!-- Status Badge -->
                                        <div class="absolute top-4 right-4">
                                            <?php
                                            $statusConfig = [
                                                'approved' => [
                                                    'bg' => 'bg-gradient-to-r from-green-500 to-emerald-500',
                                                    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>',
                                                    'text' => 'ได้รับการยืนยันความถูกต้องแล้ว'
                                                ],
                                                'pending' => [
                                                    'bg' => 'bg-gradient-to-r from-amber-500 to-orange-500',
                                                    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>',
                                                    'text' => 'รอการตรวจสอบความถูกต้อง'
                                                ],
                                                'rejected' => [
                                                    'bg' => 'bg-gradient-to-r from-red-500 to-pink-500',
                                                    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>',
                                                    'text' => 'ข้อมูลไม่ถูกต้อง'
                                                ]
                                            ];
                                            $config = $statusConfig[$pet['status']];
                                            ?>
                                            <div class="<?php echo $config['bg']; ?> text-white px-4 py-2 rounded-xl shadow-lg flex items-center space-x-2">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <?php echo $config['icon']; ?>
                                                </svg>
                                                <span class="text-sm font-medium"><?php echo $config['text']; ?></span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Pet Info -->
                                    <div class="p-6">
                                        <div class="flex items-center justify-between mb-4">
                                            <h3 class="text-xl font-bold text-gray-800">
                                                <?php echo htmlspecialchars($pet['pet_name']); ?>
                                            </h3>
                                            <div class="flex items-center space-x-2">
                                                <?php if ($pet['gender'] === 'male'): ?>
                                                    <div class="w-8 h-8 bg-blue-50 rounded-full flex items-center justify-center">
                                                        <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <circle cx="9" cy="16" r="5" stroke-width="2" />
                                                            <path d="M12 12L18 6" stroke-width="2" stroke-linecap="round" />
                                                            <path d="M13 6h5v5" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                                        </svg>
                                                    </div>
                                                <?php else: ?>
                                                    <div class="w-8 h-8 bg-pink-50 rounded-full flex items-center justify-center">
                                                        <svg class="w-5 h-5 text-pink-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <circle cx="12" cy="10" r="6" stroke-width="2" />
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 16v6" />
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19h6" />
                                                        </svg>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>

                                        <div class="flex items-center space-x-2 mb-4">
                                            <div class="w-8 h-8 bg-gray-100 rounded-full flex items-center justify-center">
                                                <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                                </svg>
                                            </div>
                                            <div>
                                                <div class="text-xs text-gray-500">เจ้าของ</div>
                                                <div class="text-sm font-medium text-gray-800">
                                                    <?php echo htmlspecialchars($pet['fullname']); ?>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="space-y-3">
                                            <div class="flex items-center p-3 bg-gray-50 rounded-xl">
                                                <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center mr-3">
                                                    <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                            d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                                    </svg>
                                                </div>
                                                <div>
                                                    <div class="text-xs text-gray-500">ประเภท</div>
                                                    <div class="text-sm font-medium text-gray-800">
                                                        <?php echo htmlspecialchars($pet['type_name']); ?>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="flex items-center p-3 bg-gray-50 rounded-xl">
                                                <div class="w-8 h-8 bg-purple-100 rounded-lg flex items-center justify-center mr-3">
                                                    <svg class="w-5 h-5 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                            d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
                                                    </svg>
                                                </div>
                                                <div>
                                                    <div class="text-xs text-gray-500">สายพันธุ์</div>
                                                    <div class="text-sm font-medium text-gray-800">
                                                        <?php echo htmlspecialchars($pet['species_name']); ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <button onclick="viewPetDetails(<?php echo $pet['id']; ?>)"
                                            class="mt-6 w-full bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white px-6 py-3 rounded-xl flex items-center justify-center space-x-2 transform transition-all duration-200 hover:scale-[1.02] focus:ring-2 focus:ring-blue-300 focus:ring-offset-2">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                            <span>ดูรายละเอียด</span>
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Pet Details Modal -->
    <div id="petDetailsModal" class="fixed inset-0 bg-black/50 z-50 hidden">
        <div class="flex items-center justify-center min-h-screen p-4">
            <!-- Modal Content -->
            <div class="bg-white rounded-2xl max-w-4xl w-full max-h-[90vh] overflow-hidden relative">
                <!-- Modal Header -->
                <div class="sticky top-0 bg-white z-10">
                    <div class="flex justify-between items-center p-6 bg-gradient-to-r from-blue-500 to-blue-600">
                        <h2 class="text-xl font-bold text-white">รายละเอียดสัตว์เลี้ยง</h2>
                        <button onclick="closePetDetails()"
                            class="text-white hover:text-gray-300 p-2 hover:bg-gray-100 rounded-xl transition-colors">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Modal Body -->
                <div id="petDetailsContent" class="overflow-y-auto max-h-[calc(90vh-8rem)]">
                    <!-- Content will be loaded here -->
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


        function showTab(status) {
            const cards = document.querySelectorAll('.pet-card');
            cards.forEach(card => {
                if (status === 'all' || card.dataset.status === status) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });

            // Update active tab
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('border-blue-500', 'text-blue-600');
                btn.classList.add('text-gray-500');
            });
            event.currentTarget.classList.add('border-blue-500', 'text-blue-600');
            event.currentTarget.classList.remove('text-gray-500');
        }

        async function viewPetDetails(petId) {
            try {
                const modal = document.getElementById('petDetailsModal');
                const content = document.getElementById('petDetailsContent');

                // แสดง loading
                content.innerHTML = `
                    <div class="flex items-center justify-center p-8">
                        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500"></div>
                    </div>
                `;

                modal.classList.remove('hidden');
                document.body.style.overflow = 'hidden'; // ปิด scroll ของ body

                const response = await fetch(`get_pet_details.php?id=${petId}`);
                const data = await response.json();

                if (data.status === 'success') {
                    content.innerHTML = data.html;
                } else {
                    content.innerHTML = `
                        <div class="p-8 text-center text-red-500">
                            ${data.message}
                        </div>
                    `;
                }
            } catch (error) {
                console.error('Error:', error);
                document.getElementById('petDetailsContent').innerHTML = `
                    <div class="p-8 text-center text-red-500">
                        เกิดข้อผิดพลาดในการโหลดข้อมูล
                    </div>
                `;
            }
        }

        function closePetDetails() {
            const modal = document.getElementById('petDetailsModal');
            modal.classList.add('hidden');
            document.body.style.overflow = 'auto'; // เปิด scroll ของ body กลับมา
        }

        // Search functionality
        document.getElementById('searchPets').addEventListener('input', function(e) {
            const searchText = e.target.value.toLowerCase();
            const cards = document.querySelectorAll('.pet-card');

            cards.forEach(card => {
                const text = card.textContent.toLowerCase();
                if (text.includes(searchText)) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        });

        async function updatePetStatus(petId, status) {
            let reject_reason = '';
            if (status === 'rejected') {
                reject_reason = prompt('กรุณาระบุเหตุผลที่ไม่อนุมัติ:');
                if (reject_reason === null) return; // ยกเลิกถ้าผู้ใช้กด Cancel
            }

            if (!confirm('คุณต้องการ' + (status === 'approved' ? 'อนุมัติ' : 'ไม่อนุมัติ') + 'สัตว์เลี้ยงนี้ใช่หรือไม่?')) {
                return;
            }

            try {
                const response = await fetch('update_pet_status.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        pet_id: petId,
                        status: status,
                        reject_reason: reject_reason
                    })
                });

                const data = await response.json();
                if (data.status === 'success') {
                    alert(data.message);
                    closePetDetails();
                    location.reload();
                } else {
                    alert('เกิดข้อผิดพลาด: ' + data.message);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('เกิดข้อผิดพลาดในการอัพเดทสถานะ');
            }
        }
    </script>
</body>

</html>