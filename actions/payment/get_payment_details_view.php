<?php
session_start();
require_once '../../config/config.php';
require_once '../../includes/auth.php';

// ตรวจสอบสิทธิ์
checkPageAccess(PAGE_MANAGE_PAYMENT);

if (isset($_GET['payment_id'])) {
    $payment_id = $_GET['payment_id'];

    // ดึงข้อมูลการชำระเงินทั้งหมด
    $stmt = $conn->prepare("
    SELECT 
        p.amount as payment_amount,
        p.description,
        p.month,
        p.year,
        t.transaction_id,
        t.status,
        t.created_at,
        t.slip_image,
        t.reject_reason,
        t.penalty,
        u.username,
        u.user_id,
        u.fullname,
        u.phone,
        p.amount as total_amount,
        GROUP_CONCAT(CONCAT(ut.name, ':', ut.color) SEPARATOR '|') as tags
    FROM payments p
    LEFT JOIN transactions t ON p.payment_id = t.payment_id
    LEFT JOIN users u ON t.user_id = u.user_id
    LEFT JOIN user_tag_relations utr ON u.user_id = utr.user_id
    LEFT JOIN user_tags ut ON utr.tag_id = ut.tag_id
    WHERE p.payment_id = ?
    GROUP BY p.payment_id, u.user_id
    ORDER BY u.username ASC
    ");
    $stmt->execute([$payment_id]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // จัดกลุ่มข้อมูล
    $payment_info = null;
    $users = [
        'not_paid' => [],
        'pending' => [],
        'approved' => [],
        'rejected' => []
    ];

    $total_amount = 0;
    $paid_amount = 0;

    // เพิ่มการคำนวณยอดรวมเบี้ยปรับ
    $total_penalty = 0;
    foreach ($results as $row) {
        if (!$payment_info) {
            $payment_info = [
                'amount' => $row['payment_amount'],
                'description' => $row['description'],
                'month' => $row['month'],
                'year' => $row['year'],
                'month_year_display' => sprintf("%02d/%04d", $row['month'], $row['year'])
            ];
            $total_amount = $row['total_amount'] * count($results);
        }

        $user_data = [
            'username' => $row['username'],
            'user_id' => $row['user_id'],
            'transaction_id' => $row['transaction_id'],
            'created_at' => $row['created_at'],
            'slip_image' => $row['slip_image'],
            'reject_reason' => $row['reject_reason'],
            'fullname' => $row['fullname'],
            'phone' => $row['phone'],
            'penalty' => $row['penalty'],
            'tags' => $row['tags']
        ];

        if (!$row['status']) {
            $users['not_paid'][] = $user_data;
        } else {
            $users[$row['status']][] = $user_data;
            if ($row['status'] === 'approved') {
                $paid_amount += $row['total_amount'];
            }
        }

        if ($row['penalty']) {
            $total_penalty += floatval($row['penalty']);
        }
    }
?>


    <style>
        .tab-content {
            opacity: 1;
            transition: all 150ms ease-in-out;
        }

        .tab-content.opacity-0 {
            opacity: 0;
        }

        .tab-content.hidden {
            display: none;
        }

        .tab-btn {
            transition: all 150ms ease-in-out;
        }

        .tab-btn .absolute {
            transition: transform 200ms ease-in-out;
        }

        .tag {
            display: inline-flex;
            align-items: center;
            padding: 0.125rem 0.625rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
            margin-left: 0.25rem;
        }
    </style>

    <div class="bg-white">
        <!-- ส่วนหัว -->
        <div class="px-6 py-4">
            <div class="flex justify-between items-center">
                <h3 class="text-xl font-semibold text-gray-900">
                    <?php echo htmlspecialchars($payment_info['description']); ?>
                </h3>
                <span class="text-sm text-gray-500">
                    <?php echo sprintf("%02d/%04d", $payment_info['month'], $payment_info['year']); ?>
                </span>
            </div>

            <!-- สรุปยอดเงิน -->
            <div class="mt-4 grid grid-cols-3 gap-4">
                <div class="bg-green-50 p-4 rounded-lg">
                    <p class="text-sm text-gray-600">ยอดรวมที่ชำระแล้ว ในเดือนนี้</p>
                    <p class="text-2xl font-bold text-green-600"><?php echo number_format($paid_amount, 2); ?> บาท</p>
                </div>
                <div class="bg-red-50 p-4 rounded-lg">
                    <p class="text-sm text-gray-600">ยอดรวมเบี้ยปรับ ในเดือนนี้</p>
                    <p class="text-2xl font-bold text-red-600"><?php echo number_format($total_penalty, 2); ?> บาท</p>
                </div>
                <div class="bg-gray-100 p-4 rounded-lg">
                    <p class="text-sm text-gray-600">ยอดรวมทั้งหมด ในเดือนนี้</p>
                    <p class="text-2xl font-bold text-gray-600"><?php echo number_format($total_amount, 2); ?> บาท</p>
                </div>
            </div>
        </div>

        <!-- แท็บเมนู -->
        <div class="sticky top-0 bg-white border-t border-b z-10">
            <div class="flex space-x-4 px-6">
                <button onclick="showPaymentTab('not_paid')"
                    class="tab-btn px-4 py-3 border-b-2 border-transparent text-gray-500 hover:text-blue-600 transition-colors"
                    data-tab="not_paid">
                    ยังไม่ชำระ (<?php echo count($users['not_paid']); ?>)
                </button>

                <button onclick="showPaymentTab('pending')"
                    class="tab-btn px-4 py-3 border-b-2 border-transparent text-gray-500 hover:text-blue-600 transition-colors"
                    data-tab="pending">
                    รอตรวจสอบ (<?php echo count($users['pending']); ?>)
                </button>

                <button onclick="showPaymentTab('approved')"
                    class="tab-btn px-4 py-3 border-b-2 border-transparent text-gray-500 hover:text-blue-600 transition-colors"
                    data-tab="approved">
                    ชำระแล้ว (<?php echo count($users['approved']); ?>)
                </button>

                <button onclick="showPaymentTab('rejected')"
                    class="tab-btn px-4 py-3 border-b-2 border-transparent text-gray-500 hover:text-blue-600 transition-colors"
                    data-tab="rejected">
                    ไม่อนุมัติ (<?php echo count($users['rejected']); ?>)
                </button>
                              <div class="px-6 py-2 flex-1">
                    <input type="text"
                        id="modalUserSearch"
                        placeholder="ค้นหาชื่อผู้ใช้..."
                        class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
            </div>
        </div>

        <!-- เนื้อหาแท็บ -->
        <div class="p-6">
            <?php foreach ($users as $status => $user_list): ?>
                <div id="tab-<?php echo $status; ?>"
                    class="tab-content <?php echo $status === 'not_paid' ? '' : 'hidden opacity-0'; ?>">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        รหัสผู้ใช้
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        ชื่อ-นามสกุล
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        เบอร์โทร
                                    </th>
                                    <?php if ($status !== 'not_paid'): ?>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            วันที่
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            หลักฐาน
                                        </th>
                                    <?php endif; ?>
                                    <?php if ($status === 'rejected'): ?>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            เหตุผล
                                        </th>
                                    <?php endif; ?>
                                    <?php if ($status === 'pending'): ?>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            การกระทำ
                                        </th>
                                    <?php endif; ?>
                                    <!-- <?php if ($status === 'approved' || $status === 'not_paid'): ?>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            พิมพ์ใบเสร็จ
                                        </th>
                                    <?php endif; ?> -->
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        จำนวนเบี้ยปรับ
                                    </th>
                                    <?php if ($status == 'not_paid'): ?>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            เบี้ยปรับ
                                        </th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php if (!empty($user_list)): ?>
                                    <?php foreach ($user_list as $user): ?>
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                <?php echo htmlspecialchars($user['username']); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                <?php echo htmlspecialchars($user['fullname'] ?: '-'); ?>
                                                <?php if (!empty($user['tags'])): ?>
                                                    <?php foreach (explode('|', $user['tags']) as $tag): ?>
                                                        <?php
                                                        list($tagName, $tagColor) = explode(':', $tag);
                                                        ?>
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-<?= $tagColor ?>-200 text-<?= $tagColor ?>-800 ml-1">
                                                            <?= htmlspecialchars($tagName) ?>
                                                        </span>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                <?php echo htmlspecialchars($user['phone'] ?: '-'); ?>
                                            </td>
                                            <?php if ($status !== 'not_paid'): ?>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                    <?php echo date('d/m/Y H:i', strtotime($user['created_at'])); ?>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                    <?php if (!empty($user['slip_image'])): ?>
                                                        <a href="../../uploads/slips/<?php echo htmlspecialchars($user['slip_image']); ?>"
                                                            target="_blank"
                                                            class="inline-flex items-center px-3 py-1.5 bg-blue-100 text-blue-700 rounded-md hover:bg-blue-200 transition-colors">
                                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                            </svg>
                                                            ดูหลักฐาน
                                                        </a>
                                                    <?php else: ?>
                                                        <!-- <div class="flex items-center">
                                                            <input type="file"
                                                                id="slip_<?php echo $user['transaction_id']; ?>"
                                                                accept="image/*"
                                                                onchange="uploadSlip(<?php echo $user['transaction_id']; ?>, this)"
                                                                class="hidden">
                                                            <label for="slip_<?php echo $user['transaction_id']; ?>"
                                                                class="inline-flex items-center px-3 py-1.5 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors cursor-pointer">
                                                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                                                                </svg>
                                                                อัพโหลดหลักฐาน
                                                            </label>
                                                        </div> -->
                                                        ยังไม่มีหลักฐาน
                                                    <?php endif; ?>
                                                </td>
                                            <?php endif; ?>
                                            <?php if ($status === 'rejected'): ?>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                    <?php echo htmlspecialchars($user['reject_reason']); ?>
                                                </td>
                                            <?php endif; ?>
                                            <?php if ($status === 'pending'): ?>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                                    <button
                                                        onclick="updateTransactionStatus(<?php echo $user['transaction_id']; ?>, 'approved')"
                                                        class="inline-flex items-center px-3 py-1.5 bg-green-100 text-green-700 rounded-md hover:bg-green-200 transition-colors">
                                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                        </svg>
                                                        อนุมัติ
                                                    </button>
                                                    <button
                                                        onclick="updateTransactionStatus(<?php echo $user['transaction_id']; ?>, 'rejected')"
                                                        class="inline-flex items-center px-3 py-1.5 bg-red-100 text-red-700 rounded-md hover:bg-red-200 transition-colors">
                                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                        </svg>
                                                        ไม่อนุมัติ
                                                    </button>
                                                </td>

                                            <?php endif; ?>
                                            <!-- <?php if ($status === 'approved'): ?>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                    <button
                                                        onclick="printReceipt(<?php echo $user['user_id']; ?>, <?php echo $payment_id; ?>, '<?php echo htmlspecialchars($user['transaction_id']); ?>')"
                                                        class="inline-flex items-center px-3 py-1.5 bg-green-100 text-green-700 rounded-md hover:bg-green-200 transition-colors">
                                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                                        </svg>
                                                        พิมพ์ใบเสร็จ
                                                    </button>
                                                </td>
                                            <?php endif; ?>
                                            <?php if ($status === 'not_paid'): ?>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                    <button
                                                         onclick="printInvoice(<?php echo $user['user_id']; ?>, <?php echo $payment_id; ?>,)"
                                                        class="inline-flex items-center px-3 py-1.5 bg-blue-100 text-blue-700 rounded-md hover:bg-blue-200 transition-colors">
                                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                                        </svg>
                                                        พิมพ์ใบวางบิล
                                                    </button>
                                                </td>
                                            <?php endif; ?> -->
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-red-600">
                                                <?php echo $user['penalty'] ?? 0; ?>
                                            </td>
                                            <?php if ($status === 'not_paid'): ?>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                    <button onclick="setPenalty(<?php echo $user['user_id']; ?>, <?php echo $payment_id; ?>, <?php echo $user['penalty'] ?? 0; ?>)"
                                                        class="text-red-600 hover:text-red-700 text-sm">
                                                        <i class="fas fa-exclamation-circle mr-1"></i>ตั้งเบี้ยปรับ
                                                    </button>
                                                </td>
                                            <?php endif; ?>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="<?php echo ($status !== 'not_paid' ? '7' : '4'); ?>"
                                            class="px-6 py-4 text-center text-sm text-gray-500">
                                            ไม่พบข้อมูล
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>



    <script>
        function showPaymentTab(tabName) {
            // ซ่อนทุก tab content
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.add('opacity-0');
            });

            // รอให้ animation เสร็จก่อน่อน
            setTimeout(() => {
                document.querySelectorAll('.tab-content').forEach(tab => {
                    tab.classList.add('hidden');
                });

                // แสดง tab ที่เลือก
                const selectedTab = document.getElementById('tab-' + tabName);
                if (selectedTab) {
                    selectedTab.classList.remove('hidden');
                    // รอให้ browser render การแสดงผลก่อน
                    requestAnimationFrame(() => {
                        selectedTab.classList.remove('opacity-0');
                    });
                }
            }, 150);

            // อัพเดทสานะปุ่ม
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('border-blue-500', 'text-blue-600', 'font-medium');
                btn.classList.add('border-transparent');
                const indicator = btn.querySelector('.absolute');
                if (indicator) {
                    indicator.classList.add('scale-x-0');
                }
            });

            // ไฮไลท์ปุ่มที่เลือก
            const activeBtn = document.querySelector(`[data-tab="${tabName}"]`);
            if (activeBtn) {
                activeBtn.classList.remove('border-transparent');
                activeBtn.classList.add('border-blue-500', 'text-blue-600', 'font-medium');
                const indicator = activeBtn.querySelector('.absolute');
                if (indicator) {
                    indicator.classList.remove('scale-x-0');
                }
            }
        }

        function viewPaymentDetails(paymentId) {
            fetch(`../../actions/payment/get_payment_details.php?payment_id=${paymentId}`)
                .then(response => response.text())
                .then(html => {
                    const modalContent = document.getElementById('paymentDetailsContent');
                    if (modalContent) {
                        modalContent.innerHTML = html;
                        // แสดง tab แรกหลังจากโหลดข้อมูล
                        showPaymentTab('not_paid');
                    }
                    // แดง modal
                    const modal = document.getElementById('paymentDetailsModal');
                    if (modal) {
                        modal.classList.remove('hidden');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('เกิดข้อผิดพลาดในการโหลดข้อมูล');
                });
        }

        function handleSlipUpload(transactionId) {
            const fileInput = document.getElementById(`slip_${transactionId}`);
            const file = fileInput.files[0];
            if (!file) {
                alert('กรุณาเลือกไฟล์');
                return;
            }

            const formData = new FormData();
            formData.append('transaction_id', transactionId);
            formData.append('slip_image', file);

            fetch('../../actions/payment/upload_slip.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // รีโหลดรายละเอียดการชำระเงิน
                        viewPaymentDetails(data.payment_id);
                    } else {
                        alert('เกิดข้อผิดพลาด: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('เกิดข้อผิดพลาดในการอัพโหลดไฟล์');
                });
        }

        // เรียกฟังก์ชันเมื่อโหลดหน้าเสร็จ
        document.addEventListener('DOMContentLoaded', function() {
            showPaymentTab('not_paid');
        });
    </script>
<?php
}
