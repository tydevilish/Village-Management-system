<?php
session_start();
require_once '../../config/config.php';
require_once '../../includes/auth.php';

try {
    $conn->beginTransaction();

    if ($_POST['action'] === 'remove_photo') {
        // อัพเดทให้ photo เป็น null
        $stmt = $conn->prepare("
            UPDATE pet_vaccines 
            SET photo = NULL
            WHERE pet_id = :pet_id 
            AND vaccine_id = :vaccine_id
        ");
        $stmt->execute([
            ':pet_id' => $_POST['pet_id'],
            ':vaccine_id' => $_POST['vaccine_id']
        ]);
    }

    $conn->commit();
    echo json_encode(['status' => 'success']);

} catch (Exception $e) {
    $conn->rollBack();
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>