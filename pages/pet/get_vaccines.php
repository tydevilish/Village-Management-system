    <?php
    session_start();
    require_once '../../config/config.php';
    require_once '../../includes/auth.php';

    if (!isset($_GET['type_id'])) {
        echo json_encode(['status' => 'no_vaccines', 'message' => 'ไม่พบวัคซีนสำหรับสัตว์เลี้ยงประเภทนี้']);
        exit;
    }

    try {
        $stmt = $conn->prepare("
            SELECT 
                v.id,
                v.name,
                v.description,
                CASE 
                    WHEN v.created_by = :user_id THEN 'custom'
                    ELSE 'system'
                END as type
            FROM vaccines v
            WHERE (v.for_type_id = :type_id OR v.for_type_id IS NULL)
            AND (v.created_by IS NULL OR v.created_by = :user_id)
            ORDER BY v.name
        ");
        
        $stmt->execute([
            'user_id' => $_SESSION['user_id'],
            'type_id' => $_GET['type_id']
        ]);
        $vaccines = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($vaccines)) {
            echo json_encode(['status' => 'no_vaccines', 'message' => 'ไม่พบวัคซีนสำหรับสัตว์เลี้ยงประเภทนี้']);
            exit;
        }
        
        header('Content-Type: application/json');
        echo json_encode(['status' => 'success', 'data' => $vaccines]);

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()
        ]);
    }
    ?>