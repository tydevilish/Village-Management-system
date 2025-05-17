<?php
session_start();
require_once './config/config.php';

if (isset($_POST['submit'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    try {
        $stmt = $conn->prepare("
            SELECT u.*, r.role_name 
            FROM users u 
            LEFT JOIN roles r ON u.role_id = r.role_id 
            WHERE username = ? AND password = ?
        ");
        $stmt->execute([$username, $password]);
        $user = $stmt->fetch();

        if ($user) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role_id'] = $user['role_id'];
            $_SESSION['role_name'] = $user['role_name'];

            // ดึงสิทธิ์การเข้าถึงเมนู
            $stmt = $conn->prepare("
                SELECT menu_id 
                FROM role_permissions 
                WHERE role_id = :role_id
            ");
            $stmt->execute(['role_id' => $user['role_id']]);
            $_SESSION['menu_access'] = $stmt->fetchAll(PDO::FETCH_COLUMN);

            header('Location: ./pages/dashboard/dashboard');
            exit();
        } else {
            $_SESSION['error'] = 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง';
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = 'เกิดข้อผิดพลาด: ' . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>| ระบบจัดการหมู่บ้าน </title>
    <link rel="icon" href="https://devcm.info/img/favicon.png">
    <link rel="stylesheet" href="./src/style.css">
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="flex items-center justify-center min-h-screen bg-eva" style="background-image: url('./src/output-onlinepngtoolsnew.png'); background-size: cover; background-position: center;">

<body class="bg-modern">
    <!-- เพิ่ม spinner element -->
    <div id="loader" class="fixed inset-0 bg-white flex justify-center items-center z-50 transition-opacity duration-500">
        <div class="w-12 h-12 border-4 border-gray-200 border-t-blue-500 rounded-full animate-spin"></div>
    </div>

    <!-- เพิ่ม script ควบคุม spinner -->
    <script>
        window.addEventListener('load', function() {
            const loader = document.getElementById('loader');
            setTimeout(function() {
                loader.classList.add('opacity-0');
                // รอให้ animation จบก่อนซ่อน element
                setTimeout(() => {
                    loader.classList.add('hidden');
                }, 250);
            }, 500);
        });
    </script>

<div class="flex flex-col bg-white space-y-5 rounded-xl lg:mx-auto max-w-lg w-full px-12 py-10 mx-10">
    <div>
        <img
            src="https://devcm.info/img/favicon.png"
            alt="icons"
            class="mx-auto w-20 h-20">
    </div>
    <div>
        <p
            class="title">
            ยินดีต้อนรับเข้าสู่ระบบ
        </p>
    </div>
    <form method="post" class="flex flex-col space-y-5">
        <div>
            <input
                type="text"
                name="username"
                class="input-field <?php echo isset($_SESSION['error']) ? 'input-field-error' : ''; ?>"
                placeholder="Username"
                value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
        </div>
        <div>
            <input
                type="password"
                name="password"
                class="input-field <?php echo isset($_SESSION['error']) ? 'input-field-error' : ''; ?>"
                placeholder="••••••••••••">
        </div>
        <div class="flex items-start">
            <div class="flex items-center h-5">
                <input type="checkbox"
                    class="checkbox"
                    checked>
            </div>
            <div class="ml-2 text-sm">
                <label for="remember" class="text-eva">จดจำฉันไว้ในระบบ</label>
            </div>
        </div>
        <div>
            <button
                type="submit"
                name="submit"
                class="primary-button">
                เข้าสู่ระบบ
            </button>
        </div>
    </form>

    <?php if (isset($_SESSION['error'])) { ?>
        <div class="text-red-500 mt-5 text-center">
            <?php
            echo $_SESSION['error'];
            unset($_SESSION['error']);
            ?>
        </div>
    <?php } ?>
</div>

</body>

</html>