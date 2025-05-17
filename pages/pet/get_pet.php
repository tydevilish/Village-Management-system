<?php
session_start();
require_once '../../config/config.php';
require_once '../../includes/auth.php';

if (!isset($_GET['id'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'ไม่พบ ID สัตว์เลี้ยง'
    ]);
    exit;
}

try {
    // ดึงข้อมูลสัตว์เลี้ยงพร้อมข้อมูลที่เกี่ยวข้อง
    $stmt = $conn->prepare("
        SELECT 
            p.*,
            pt.name as type_name, 
            s.name as species_name
        FROM pets p
        LEFT JOIN pet_types pt ON p.type_id = pt.id
        LEFT JOIN species s ON p.species_id = s.id
        WHERE p.id = :id AND p.user_id = :user_id
    ");
    
    $stmt->execute([
        ':id' => $_GET['id'],
        ':user_id' => $_SESSION['user_id']
    ]);
    
    $pet = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$pet) {
        echo json_encode([
            'status' => 'error',
            'message' => 'ไม่พบข้อมูลสัตว์เลี้ยง'
        ]);
        exit;
    }

    // แปลงเส้นทางรูปภาพให้ถูกต้อง
    if (!empty($pet['photo'])) {
        $pet['photo'] = str_replace('../', '../', $pet['photo']);
    }

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

    // แปลงเส้นทางรูปภาพวัคซีน
    foreach ($vaccines as &$vaccine) {
        if (!empty($vaccine['photo'])) {
            $vaccine['photo'] = str_replace('../', '../', $vaccine['photo']);
        }
    }

    // เพิ่มข้อมูลวัคซีนเข้าไปในข้อมูลสัตว์เลี้ยง
    $pet['vaccines'] = $vaccines;
    
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'success',
        'data' => $pet
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()
    ]);
}
?> 