<?php
require_once '../../config/config.php';

function checkAndTagLatePayments() {
    global $conn;
    
    try {
        // 1. หา tag_id ของแท็ก "ค้างชำระบ่อย"
        $stmt = $conn->prepare("SELECT tag_id FROM user_tags WHERE name = 'ค้างชำระบ่อย'");
        $stmt->execute();
        $tag = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$tag) {
            // ถ้ายังไม่มีแท็ก ให้สร้างใหม่
            $stmt = $conn->prepare("INSERT INTO user_tags (name, color) VALUES ('ค้างชำระบ่อย', 'red')");
            $stmt->execute();
            $tag_id = $conn->lastInsertId();
        } else {
            $tag_id = $tag['tag_id'];
        }

        // 2. ดึงข้อมูลการชำระเงินที่ค้างชำระ (แก้ไขใหม่)
        $sql = "SELECT user_id, COUNT(*) as late_count
                FROM transactions 
                WHERE status = 'not_paid'
                GROUP BY user_id
                HAVING late_count >= 3";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $late_users = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 3. เพิ่มแท็กให้กับผู้ใช้ที่ค้างชำระ
        foreach ($late_users as $user) {
            // ตรวจสอบว่ามีแท็กอยู่แล้วหรือไม่
            $stmt = $conn->prepare("SELECT COUNT(*) FROM user_tag_relations WHERE user_id = ? AND tag_id = ?");
            $stmt->execute([$user['user_id'], $tag_id]);
            $exists = $stmt->fetchColumn();

            if (!$exists) {
                // ถ้ายังไม่มีแท็ก ให้เพิ่มใหม่
                $stmt = $conn->prepare("INSERT INTO user_tag_relations (user_id, tag_id) VALUES (?, ?)");
                $stmt->execute([$user['user_id'], $tag_id]);
            }
        }

        // 4. ลบแท็กออกจากผู้ใช้ที่ไม่ได้ค้างชำระแล้ว
        $user_ids = array_column($late_users, 'user_id');
        if (!empty($user_ids)) {
            $placeholders = str_repeat('?,', count($user_ids) - 1) . '?';
            $sql = "DELETE FROM user_tag_relations 
                    WHERE tag_id = ? 
                    AND user_id NOT IN ($placeholders)";
            
            $params = array_merge([$tag_id], $user_ids);
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
        }

        return true;
    } catch (PDOException $e) {
        error_log("Error checking late payments: " . $e->getMessage());
        return false;
    }
}

// เรียกใช้ฟังก์ชัน
checkAndTagLatePayments(); 