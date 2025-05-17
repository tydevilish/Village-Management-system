<?php
session_start();
require_once '../../config/config.php';
require_once '../../includes/auth.php';

if (!isset($_GET['id'])) {
    echo json_encode(['status' => 'error', 'message' => 'ไม่พบ ID สัตว์เลี้ยง']);
    exit;
}

try {
    // ดึงข้อมูลสัตว์เลี้ยงและเจ้าของ
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
        WHERE p.id = :id
    ");
    
    $stmt->execute([':id' => $_GET['id']]);
    $pet = $stmt->fetch(PDO::FETCH_ASSOC);

    // ดึงข้อมูลวัคซีน
    $vaccineStmt = $conn->prepare("
        SELECT 
            pv.*,
            v.name as vaccine_name,
            v.description as vaccine_description
        FROM pet_vaccines pv
        JOIN vaccines v ON pv.vaccine_id = v.id
        WHERE pv.pet_id = :pet_id
    ");
    
    $vaccineStmt->execute([':pet_id' => $pet['id']]);
    $vaccines = $vaccineStmt->fetchAll(PDO::FETCH_ASSOC);

    // สร้าง HTML สำหรับแสดงรายละเอียด
    $html = '
    <div class="space-y-8 p-6">
        <!-- Header Section with Image -->
        <div class="relative overflow-hidden bg-gradient-to-br from-blue-600 to-indigo-600 rounded-3xl">
            <div class="absolute inset-0 bg-grid-white/10"></div>
            <div class="relative p-8">
                <!-- ส่วนบน: ชื่อและสถานะ -->
                <div class="flex justify-between items-start mb-8">
                    <div class="text-white">
                        <h2 class="text-3xl font-bold">' . htmlspecialchars($pet['pet_name']) . '</h2>
                        <p class="mt-2 text-blue-100 flex items-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14" />
                            </svg>
                            รหัส: #' . str_pad($pet['id'], 5, '0', STR_PAD_LEFT) . '
                        </p>
                    </div>
                    <span class="px-5 py-2.5 rounded-xl text-sm font-semibold ' . 
                        ($pet['gender'] === 'male' 
                            ? 'bg-blue-400/20 text-blue-100 ring-1 ring-blue-400/50' 
                            : 'bg-pink-400/20 text-pink-100 ring-1 ring-pink-400/50') . '">
                        ' . htmlspecialchars($pet['gender_name']) . '
                    </span>
                </div>

                <!-- ส่วนล่าง: Quick Info -->
                <div class="grid grid-cols-3 gap-6 text-white">
                    <div class="bg-white/10 backdrop-blur-sm rounded-2xl p-4">
                        <div class="text-sm opacity-80">ประเภท</div>
                        <div class="font-semibold mt-1">' . htmlspecialchars($pet['type_name']) . '</div>
                    </div>
                    <div class="bg-white/10 backdrop-blur-sm rounded-2xl p-4">
                        <div class="text-sm opacity-80">สายพันธุ์</div>
                        <div class="font-semibold mt-1">' . htmlspecialchars($pet['species_name']) . '</div>
                    </div>
                    <div class="bg-white/10 backdrop-blur-sm rounded-2xl p-4">
                        <div class="text-sm opacity-80">เจ้าของ</div>
                        <div class="font-semibold mt-1">' . htmlspecialchars($pet['fullname']) . '</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-5 gap-8">
            <!-- Left Column: รูปภาพและข้อมูลเจ้าของ -->
            <div class="lg:col-span-2 space-y-6">
                <!-- รูปภาพสัตว์เลี้ยง -->
                <div class=" p-0.5 rounded-3xl">
                    <div class="bg-white rounded-3xl overflow-hidden">
                        <div class="relative aspect-square">';
if (!empty($pet['photo'])) {
    $html .= '<img src="' . htmlspecialchars($pet['photo']) . '" 
                   alt="' . htmlspecialchars($pet['pet_name']) . '"
                   class="w-full h-full object-cover transition-all duration-700 hover:scale-110">';
} else {
    $html .= '<div class="absolute inset-0 bg-gradient-to-br from-gray-100 to-gray-200 flex items-center justify-center">
                <div class="text-center">
                    <div class="w-24 h-24 mx-auto bg-white rounded-2xl shadow-sm flex items-center justify-center">
                        <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" 
                                d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <p class="mt-4 text-gray-500 font-medium">ไม่ได้แนบรูปภาพ</p>
                </div>
            </div>';
}
$html .= '</div>
                    </div>
                </div>

                <!-- ข้อมูลเจ้าของ -->
                <div class="p-0.5 rounded-3xl">
                    <div class="bg-white p-6 rounded-3xl">
                        <h3 class="text-lg font-semibold text-gray-800 mb-6">ข้อมูลเจ้าของ</h3>
                        <div class="space-y-4">
                            <div class="flex items-center p-3 bg-gray-50 rounded-xl hover:bg-gray-100 transition-colors">
                                <div class="w-10 h-10 bg-orange-100 rounded-lg flex items-center justify-center mr-4">
                                    <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                    </svg>
                                </div>
                                <div>
                                    <div class="text-xs text-gray-500">ชื่อเจ้าของ</div>
                                    <div class="font-medium text-gray-800">' . htmlspecialchars($pet['fullname']) . '</div>
                                </div>
                            </div>
                            <div class="flex items-center p-3 bg-gray-50 rounded-xl hover:bg-gray-100 transition-colors">
                                <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center mr-4">
                                    <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                            d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207" />
                                    </svg>
                                </div>
                                <div>
                                    <div class="text-xs text-gray-500">ชื่อผู้ใช้</div>
                                    <div class="font-medium text-gray-800">' . htmlspecialchars($pet['username']) . '</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column: ประวัติวัคซีน -->
            <div class="lg:col-span-3">
                <div class=" p-0.5 rounded-3xl h-full">
                    <div class="bg-white p-6 rounded-3xl h-full">
                        <h3 class="text-lg font-semibold text-gray-800 mb-6 flex items-center">
                            <svg class="w-6 h-6 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                    d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                            </svg>
                            ประวัติการฉีดวัคซีน
                        </h3>
                        <div class="space-y-4">';

if (!empty($vaccines)) {
    foreach ($vaccines as $vaccine) {
        $html .= '<div class="group bg-gray-50 rounded-2xl p-5 hover:bg-gray-100 transition-all duration-300">
            <div class="flex items-start justify-between">
                <div class="flex items-start space-x-4">
                    <div class="w-10 h-10 bg-green-100 rounded-xl flex items-center justify-center group-hover:bg-green-200 transition-colors">
                        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <h4 class="font-medium text-gray-800">' . htmlspecialchars($vaccine['vaccine_name']) . '</h4>
                        <p class="text-sm text-gray-500 mt-1">' . htmlspecialchars($vaccine['vaccine_description']) . '</p>
                    </div>
                </div>
            </div>';
        
        if (!empty($vaccine['photo'])) {
            $html .= '<div class="mt-4 relative rounded-xl overflow-hidden">
                <img src="' . htmlspecialchars($vaccine['photo']) . '" 
                     alt="หลักฐานการฉีดวัคซีน" 
                     class="w-full h-40 object-cover transition-transform duration-500 group-hover:scale-105">
            </div>';
        }
        $html .= '</div>';
    }
} else {
    $html .= '<div class="text-center py-10 bg-gray-50 rounded-2xl">
        <div class="w-16 h-16 mx-auto bg-gray-100 rounded-full flex items-center justify-center mb-4">
            <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                    d="M20 12H4m8-8v16m-4-8h.01M15 12h.01M19 12h.01M8 12h.01" />
            </svg>
        </div>
        <p class="text-gray-500 font-medium">ยังไม่มีประวัติการฉีดวัคซีน</p>
    </div>';
}

$html .= '</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="border-t p-8">
        <div class="flex justify-end space-x-4">
            <button onclick="updatePetStatus(' . $pet['id'] . ', \'rejected\')" 
                class="group relative px-6 py-3 bg-white border-2 border-red-500 rounded-xl hover:bg-red-50 transition-all duration-300">
                <div class="flex items-center space-x-2">
                    <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                    <span class="text-red-500 font-medium">ไม่อนุมัติ</span>
                </div>
                
            </button>
            <button onclick="updatePetStatus(' . $pet['id'] . ', \'approved\')" 
                class="relative px-6 py-3 bg-gradient-to-r from-green-500 to-emerald-500 text-white rounded-xl 
                    hover:from-green-600 hover:to-emerald-600 transform hover:-translate-y-0.5 transition-all duration-300">
                <div class="flex items-center space-x-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    <span class="font-medium">อนุมัติ</span>
                </div>
            </button>
        </div>
    </div>
</div>';

echo json_encode(['status' => 'success', 'html' => $html]);

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()]);
}
?> 