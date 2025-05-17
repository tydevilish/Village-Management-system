<?php
session_start();
require_once '../../config/config.php';
require_once '../../includes/auth.php';
include '../../components/menu/Menu.php';


// ดึงข้อมูลข่าวสารทั้งหมด
$categoryStmt = $conn->query("SELECT * FROM news_categories ORDER BY category_name");
$categories = $categoryStmt->fetchAll(PDO::FETCH_ASSOC);

// ดึงพารามิเตอร์การค้นหาและการเรียงลำดับ
$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';
$sort = $_GET['sort'] ?? 'newest';

// สร้าง query พื้นฐาน
$query = "
    SELECT n.*, nc.category_name, u.fullname as author
    FROM news n
    LEFT JOIN news_categories nc ON n.category_id = nc.category_id
    LEFT JOIN users u ON n.created_by = u.user_id
    WHERE n.status = 'active'
";

// เพิ่มเงื่อนไขการค้นหา
$params = [];
if (!empty($search)) {
    $query .= " AND (n.title LIKE :search OR n.content LIKE :search)";
    $params[':search'] = "%$search%";
}
if (!empty($category)) {
    $query .= " AND n.category_id = :category";
    $params[':category'] = $category;
}

// เพิ่มการเรียงลำดับ
$query .= " ORDER BY n.created_at " . ($sort === 'oldest' ? 'ASC' : 'DESC');

$stmt = $conn->prepare($query);
$stmt->execute($params);
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

    <style>
        .aspect-w-16 {
            position: relative;
            padding-bottom: 56.25%;
            /* 16:9 Aspect Ratio */
        }

        .aspect-w-16>img {
            position: absolute;
            height: 100%;
            width: 100%;
            left: 0;
            top: 0;
        }
    </style>
</head>

<body class="bg-modern">
    <div class="flex">
    <?php if (isset($_SESSION['user_id'])) { ?>
        <div id="sidebar" class="fixed top-0 left-0 h-full w-20 z-30 transition-all duration-300 ease-in-out bg-gradient-to-b from-blue-600 to-blue-500 shadow-xl">
            <!-- ป่ม toggle -->
            <button id="toggleSidebar" class="absolute -right-3 bottom-24 bg-blue-800 text-white rounded-full p-1 shadow-lg hover:bg-blue-900">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
                </button>
                <?php renderMenu(); ?>
                </div>
        <?php } ?>

        <div class="flex-1 <?php echo isset($_SESSION['user_id']) ? 'ml-20' : ''; ?>">
            <nav class="bg-white shadow-sm px-6 py-4">
                <div class="flex justify-between items-center">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-800">ข้อมูลข่าวสาร</h1>
                        <p class="text-sm text-gray-500 mt-1">ข่าวสารและประกาศจากทางหมู่บ้าน</p>
                    </div>
                </div>
            </nav>

            <!-- เพิ่มส่วน Search และ Filter -->
            <div class="p-6 pb-0">
                <div class="bg-white rounded-lg shadow-sm p-4 mb-6">
                    <form class="flex flex-wrap gap-4 items-end">
                        <!-- ช่องค้นหา -->
                        <div class="flex-1 min-w-[200px]">
                            <label class="block text-sm font-medium text-gray-700 mb-1">ค้นหา</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <input type="text"
                                    name="search"
                                    value="<?php echo htmlspecialchars($search); ?>"
                                    class="w-full pl-10 pr-4 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200"
                                    placeholder="ค้นหาข่าวสาร...">
                            </div>
                        </div>

                        <!-- ตัวกรองประเภท -->
                        <div class="w-48">
                            <label class="block text-sm font-medium text-gray-700 mb-1">ประเภท</label>
                            <div class="relative">
                                <select name="category"
                                    class="appearance-none w-full pl-4 pr-10 py-2 rounded-lg border border-gray-300 bg-white shadow-sm 
                                               focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200">
                                    <option value="">ทั้งหมด</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?php echo $cat['category_id']; ?>"
                                            <?php echo $category == $cat['category_id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($cat['category_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                    <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                            </div>
                        </div>

                        <!-- ตัวเลือกการเรียงลำดับ -->
                        <div class="w-48">
                            <label class="block text-sm font-medium text-gray-700 mb-1">เรียงลำดับ</label>
                            <div class="relative">
                                <select name="sort"
                                    class="appearance-none w-full pl-4 pr-10 py-2 rounded-lg border border-gray-300 bg-white shadow-sm 
                                               focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200">
                                    <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>ล่าสุด</option>
                                    <option value="oldest" <?php echo $sort === 'oldest' ? 'selected' : ''; ?>>เก่าที่สุด</option>
                                </select>
                                <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                    <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                            </div>
                        </div>

                        <!-- ปุ่มค้นหา -->
                        <div>
                            <button type="submit"
                                class="inline-flex items-center px-6 py-2.5 rounded-lg bg-gradient-to-r from-blue-600 to-blue-700 
                                           text-white font-medium  
                                           hover:shadow-blue-500/50 hover:from-blue-700 hover:to-blue-800
                                           focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 
                                           transform transition-all duration-200 ease-in-out 
                                           hover:scale-[1.02] active:scale-[0.98]">
                                <svg class="w-5 h-5 mr-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
                                </svg>
                                ค้นหา
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- แสดงผลลัพธ์การค้นหา -->
            <div class="p-6 pt-0">
                <?php if (empty($news)): ?>
                    <div class="text-center py-8">
                        <p class="text-gray-500">ไม่พบข้มูลข่าวสาร</p>
                    </div>
                <?php else: ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <?php foreach ($news as $item): ?>
                            <div class="bg-white rounded-lg shadow-sm overflow-hidden hover:shadow-md transition-shadow duration-300 cursor-pointer"
                                onclick="showNewsDetails(<?php echo htmlspecialchars(json_encode($item)); ?>)">
                                <?php if ($item['image_path']): ?>
                                    <img src="<?php echo $item['image_path']; ?>" alt="News Image" class="w-full h-48 object-cover rounded-t-lg">
                                <?php elseif ($item['pdf_path']): ?>
                                    <div class="w-full h-48 bg-gray-100 rounded-t-lg flex items-center justify-center">
                                        <div class="text-center">
                                            <svg class="w-16 h-16 mx-auto text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                            </svg>
                                            <span class="block mt-2 text-sm text-gray-600">เอกสาร PDF</span>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <div class="p-4">
                                    <div class="flex justify-between items-center mb-2">
                                        <span class="text-xs font-medium text-blue-600 bg-blue-50 px-2 py-1 rounded">
                                            <?php echo htmlspecialchars($item['category_name']); ?>
                                        </span>
                                        <span class="text-sm text-gray-500">
                                            <?php echo date('d/m/Y', strtotime($item['created_at'])); ?>
                                        </span>
                                    </div>
                                    <h3 class="text-lg font-semibold text-gray-800 mb-2">
                                        <?php echo htmlspecialchars($item['title']); ?>
                                    </h3>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal แสดงรายละเอียดข่าว -->
    <div id="newsDetailModal" class="hidden fixed inset-0 bg-black bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative min-h-screen flex items-center justify-center p-4">
            <div class="relative w-full max-w-4xl bg-white rounded-lg shadow-2xl mx-auto">
                <!-- ส่วนหัว Modal -->
                <div class="bg-gradient-to-r from-blue-600 to-blue-700 rounded-t-lg">
                    <div class="flex justify-between items-center p-4 sm:p-6">
                        <h3 id="newsTitle" class="text-xl sm:text-2xl font-semibold text-white"></h3>
                        <button onclick="closeNewsDetailModal()" class="text-white hover:text-gray-200 transition-colors">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- เนื้อหา Modal -->
                <div class="p-4 sm:p-6 max-h-[calc(100vh-200px)] overflow-y-auto">
                    <div class="space-y-4">
                        <div class="flex items-center justify-between text-sm text-gray-500">
                            <span id="newsCategory" class="bg-blue-100 text-blue-800 px-2 py-1 rounded-full"></span>
                            <span id="newsDate"></span>
                        </div>

                        <!-- ส่วนรูปภาพ -->
                        <div id="newsImage" class="hidden">
                            <div>
                                <img src="" alt="News Image" class="w-full h-full object-contain cursor-pointer hover:opacity-90 transition-opacity hidden"
                                    onclick="showFullImage(this.src)">
                                <div id="pdfPreview" class="w-full h-full bg-gray-100 rounded-lg flex items-center justify-center hidden">
                                    <div class="text-center">
                                        <svg class="w-20 h-20 mx-auto text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                        </svg>
                                        <span class="block mt-1 text-base text-gray-600">เอกสาร PDF</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- เนื้อหาข่าว -->
                        <div id="newsContent" class="text-gray-600 whitespace-pre-line"></div>

                        <!-- ส่วนแสดง PDF -->
                        <div id="newsPDF" class="hidden mt-2">
                            <div class="border rounded-lg p-3 bg-gray-50">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-medium text-gray-700">เอกสารแนบ (PDF)</span>
                                    <div class="flex space-x-2">
                                        <a id="pdfDownloadLink" href="" target="_blank"
                                            class="inline-flex items-center px-2.5 py-1 text-sm font-medium text-blue-700 bg-blue-100 rounded-md hover:bg-blue-200">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                            </svg>
                                            ดาวน์โหลดเอกสาร
                                        </a>
                                        <button onclick="showPDFModal()"
                                            class="inline-flex items-center px-2.5 py-1 text-sm font-medium text-green-700 bg-green-100 rounded-md hover:bg-green-200">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                            ดูเอกสาร
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal แสดงรูปภาพเต็ม -->
    <div id="fullImageModal" class="hidden fixed inset-0 bg-black bg-opacity-90 z-[60] cursor-pointer" onclick="closeFullImage()">
        <div class="absolute top-4 right-4">
            <button class="text-white hover:text-gray-300 transition-colors">
                <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        <div class="flex items-center justify-center h-full p-4">
            <img id="fullImage" src="" alt="Full size image" class="max-h-[90vh] max-w-[90vw] object-contain">
        </div>
    </div>

    <!-- เพิ่ม Modal แสดง PDF -->
    <div id="pdfViewerModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-[70]">
        <div class="relative min-h-screen flex items-center justify-center p-4">
            <div class="relative w-full max-w-5xl bg-white rounded-lg shadow-2xl">
                <!-- ส่วนหัว Modal -->
                <div class="bg-gradient-to-r from-blue-600 to-blue-700 rounded-t-lg">
                    <div class="flex justify-between items-center p-4">
                        <h3 class="text-xl font-semibold text-white">เอกสาร PDF</h3>
                        <button onclick="closePDFModal()" class="text-white hover:text-gray-200 transition-colors">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>
                <!-- เนื้อหา PDF -->
                <div class="h-[80vh]">
                    <iframe id="pdfViewer" src="" class="w-full h-full rounded-b-lg"></iframe>
                </div>
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

        // เพิ่มฟังก์ชันสำหรับจัดการ PDF Modal
        let currentPDFPath = '';

        function showPDFModal() {
            document.getElementById('pdfViewer').src = currentPDFPath;
            document.getElementById('pdfViewerModal').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        function closePDFModal() {
            document.getElementById('pdfViewerModal').classList.add('hidden');
            document.body.style.overflow = 'auto';
        }

        function showNewsDetails(news) {
            document.getElementById('newsTitle').textContent = news.title;
            document.getElementById('newsCategory').textContent = news.category_name;
            document.getElementById('newsDate').textContent = new Date(news.created_at).toLocaleDateString('th-TH', {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
            document.getElementById('newsContent').textContent = news.content;

            const newsImage = document.getElementById('newsImage');
            const imageElement = newsImage.querySelector('img');
            const pdfPreview = document.getElementById('pdfPreview');
            
            if (news.image_path) {
                imageElement.src = news.image_path;
                imageElement.classList.remove('hidden');
                pdfPreview.classList.add('hidden');
                newsImage.classList.remove('hidden');
            } else if (news.pdf_path) {
                imageElement.classList.add('hidden');
                pdfPreview.classList.remove('hidden');
                newsImage.classList.remove('hidden');
            } else {
                newsImage.classList.add('hidden');
            }

            document.getElementById('newsDetailModal').classList.remove('hidden');
            document.body.style.overflow = 'hidden';

            // เพิ่มสารจัดการ PDF
            const newsPDF = document.getElementById('newsPDF');
            const pdfDownloadLink = document.getElementById('pdfDownloadLink');
            if (news.pdf_path) {
                currentPDFPath = news.pdf_path;
                pdfDownloadLink.href = news.pdf_path;
                newsPDF.classList.remove('hidden');
            } else {
                newsPDF.classList.add('hidden');
            }
        }

        function closeNewsDetailModal() {
            document.getElementById('newsDetailModal').classList.add('hidden');
            document.body.style.overflow = 'auto';

            function showNewsDetails(news) {
                // โค้ดเดิม...

                const newsImage = document.getElementById('newsImage');
                const imageElement = newsImage.querySelector('img');
                const pdfPreview = document.getElementById('pdfPreview');

                if (news.image_path) {
                    imageElement.src = news.image_path;
                    imageElement.classList.remove('hidden');
                    pdfPreview.classList.add('hidden');
                    newsImage.classList.remove('hidden');
                } else if (news.pdf_path) {
                    imageElement.classList.add('hidden');
                    pdfPreview.classList.remove('hidden');
                    newsImage.classList.remove('hidden');
                } else {
                    newsImage.classList.add('hidden');
                }

                // โค้ดเดิม...
            }
        }

        function showFullImage(src) {
            document.getElementById('fullImage').src = src;
            document.getElementById('fullImageModal').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        function closeFullImage() {
            document.getElementById('fullImageModal').classList.add('hidden');
            document.body.style.overflow = 'hidden'; // ยังคง hidden เพราะ modal หลักยังเปิดอยู่
        }

        // ปิด modal ด้วยการกด ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                if (!document.getElementById('fullImageModal').classList.contains('hidden')) {
                    closeFullImage();
                } else if (!document.getElementById('newsDetailModal').classList.contains('hidden')) {
                    closeNewsDetailModal();
                } else if (!document.getElementById('pdfViewerModal').classList.contains('hidden')) {
                    closePDFModal();
                }
            }
        });
    </script>
     <?php include '../../components/footer/footer.php'; ?>
</body>

</html>