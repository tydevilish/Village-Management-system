<?php

date_default_timezone_set('Asia/Bangkok');
function recordPageView($page_path, $page_name)
{
    global $conn;

    $visitor_ip = $_SERVER['REMOTE_ADDR'];
    $user_agent = $_SERVER['HTTP_USER_AGENT'];
    $user_id = $_SESSION['user_id'] ?? null;
    $session_id = session_id();

    $stmt = $conn->prepare("
        INSERT INTO page_views (
            page_path, 
            page_name, 
            visitor_ip, 
            user_agent, 
            user_id, 
            session_id
        ) VALUES (?, ?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $page_path,
        $page_name,
        $visitor_ip,
        $user_agent,
        $user_id,
        $session_id
    ]);

    // อัพเดทสถิติรายวัน
    $today = date('Y-m-d');
    $conn->query("
        INSERT INTO daily_page_stats (
            page_path, 
            page_name, 
            view_date, 
            view_count, 
            unique_visitors
        ) 
        VALUES ('$page_path', '$page_name', '$today', 1, 1)
        ON DUPLICATE KEY UPDATE 
            view_count = view_count + 1,
            unique_visitors = (
                SELECT COUNT(DISTINCT visitor_ip) 
                FROM page_views 
                WHERE page_path = '$page_path' 
                AND DATE(viewed_at) = '$today'
            )
    ");
}
