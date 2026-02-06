<?php
/**
 * สร้าง Admin User ตัวแรก
 * รันไฟล์นี้ครั้งเดียวเพื่อสร้าง Admin
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../config/database.php';

// ข้อมูล Admin
$adminData = [
    'username' => 'admin',
    'email' => 'admin@pueankan.com',
    'password' => 'admin123',
    'full_name' => 'ผู้ดูแลระบบ',
    'role' => 'admin'
];

echo "<!DOCTYPE html>";
echo "<html lang='th'>";
echo "<head>";
echo "<meta charset='UTF-8'>";
echo "<meta name='viewport' content='width=device-width, initial-scale=1.0'>";
echo "<title>Create Admin</title>";
echo "<link href='https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;600;700&display=swap' rel='stylesheet'>";
echo "<style>";
echo "body { font-family: 'Kanit', Arial; padding: 50px; background: linear-gradient(135deg, #1a1a2e, #16213e); color: #fff; }";
echo ".container { max-width: 600px; margin: 0 auto; background: rgba(255,255,255,0.1); padding: 30px; border-radius: 15px; border: 1px solid rgba(255,107,0,0.3); }";
echo "h2 { color: #ff6b00; }";
echo ".success { background: rgba(40,167,69,0.2); border: 1px solid #28a745; color: #7dff9e; padding: 15px; border-radius: 8px; margin: 20px 0; }";
echo ".warning { background: rgba(255,193,7,0.2); border: 1px solid #ffc107; color: #ffd966; padding: 15px; border-radius: 8px; margin: 20px 0; }";
echo ".error { background: rgba(220,53,69,0.2); border: 1px solid #dc3545; color: #ff6b6b; padding: 15px; border-radius: 8px; margin: 20px 0; }";
echo ".info { background: rgba(0,173,181,0.1); padding: 10px; border-left: 3px solid #00adb5; margin: 10px 0; }";
echo ".btn { background: linear-gradient(135deg, #ff6b00, #ffc107); color: white; padding: 12px 30px; text-decoration: none; border-radius: 8px; display: inline-block; margin-top: 20px; }";
echo "code { background: rgba(255,107,0,0.2); padding: 3px 8px; border-radius: 4px; color: #ffc107; }";
echo "</style>";
echo "</head>";
echo "<body>";
echo "<div class='container'>";

try {
    // ตรวจสอบว่ามี User นี้แล้วหรือยัง
    $checkSql = "SELECT id, username, email FROM users WHERE username = :username LIMIT 1";
    $checkStmt = $pdo->prepare($checkSql);
    $checkStmt->bindValue(':username', $adminData['username'], PDO::PARAM_STR);
    $checkStmt->execute();
    
    $existingUser = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existingUser) {
        echo "<div class='warning'>";
        echo "<h2>⚠️ User Already Exists</h2>";
        echo "<p><strong>ID:</strong> {$existingUser['id']}</p>";
        echo "<p><strong>Username:</strong> {$existingUser['username']}</p>";
        echo "<p><strong>Email:</strong> {$existingUser['email']}</p>";
        echo "</div>";
        
        echo "<div class='info'>";
        echo "<p><strong>ต้องการรีเซ็ตรหัสผ่าน?</strong></p>";
        echo "<p>รันคำสั่ง SQL นี้ใน phpMyAdmin:</p>";
        
        $newHash = password_hash($adminData['password'], PASSWORD_DEFAULT);
        echo "<code style='display: block; margin-top: 10px; padding: 10px;'>";
        echo "UPDATE users SET password = '{$newHash}' WHERE username = '{$adminData['username']}';";
        echo "</code>";
        echo "</div>";
        
    } else {
        // Hash password
        $hashedPassword = password_hash($adminData['password'], PASSWORD_DEFAULT);
        
        // Insert User
        $sql = "INSERT INTO users (username, email, password, full_name, role, is_active, created_at) 
                VALUES (:username, :email, :password, :full_name, :role, 1, NOW())";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':username', $adminData['username'], PDO::PARAM_STR);
        $stmt->bindValue(':email', $adminData['email'], PDO::PARAM_STR);
        $stmt->bindValue(':password', $hashedPassword, PDO::PARAM_STR);
        $stmt->bindValue(':full_name', $adminData['full_name'], PDO::PARAM_STR);
        $stmt->bindValue(':role', $adminData['role'], PDO::PARAM_STR);
        $stmt->execute();
        
        $userId = $pdo->lastInsertId();
        
        echo "<div class='success'>";
        echo "<h2>✅ Admin User Created Successfully!</h2>";
        echo "<p><strong>User ID:</strong> {$userId}</p>";
        echo "<p><strong>Username:</strong> <code>{$adminData['username']}</code></p>";
        echo "<p><strong>Email:</strong> <code>{$adminData['email']}</code></p>";
        echo "<p><strong>Password:</strong> <code>{$adminData['password']}</code></p>";
        echo "<p><strong>Role:</strong> <code>{$adminData['role']}</code></p>";
        echo "</div>";
        
        echo "<div class='warning'>";
        echo "<p><strong>⚠️ Important:</strong> กรุณาเปลี่ยนรหัสผ่านหลังจาก Login ครั้งแรก!</p>";
        echo "</div>";
        
        echo "<a href='../pages/login.php' class='btn'>เข้าสู่หน้า Login</a>";
    }
    
} catch (PDOException $e) {
    echo "<div class='error'>";
    echo "<h2>❌ Database Error</h2>";
    echo "<p><strong>Message:</strong> {$e->getMessage()}</p>";
    echo "<p><strong>Code:</strong> {$e->getCode()}</p>";
    echo "</div>";
} catch (Exception $e) {
    echo "<div class='error'>";
    echo "<h2>❌ Error</h2>";
    echo "<p>{$e->getMessage()}</p>";
    echo "</div>";
}

echo "</div></body></html>";
```

---

## 🎯 ขั้นตอนการแก้ปัญหา

### 1. สร้าง Admin User ก่อน:
```
http://localhost/pueankan/setup/create-admin.php
```

### 2. ถ้าบอกว่ามีแล้ว ให้คัดลอก SQL จากหน้านั้น แล้วรันใน phpMyAdmin

### 3. Login ที่:
```
<?php
/**
 * สร้าง Admin User ตัวแรก
 * รันไฟล์นี้ครั้งเดียวเพื่อสร้าง Admin
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../config/database.php';

// ข้อมูล Admin
$adminData = [
    'username' => 'admin',
    'email' => 'admin@pueankan.com',
    'password' => 'admin123',
    'full_name' => 'ผู้ดูแลระบบ',
    'role' => 'admin'
];

echo "<!DOCTYPE html>";
echo "<html lang='th'>";
echo "<head>";
echo "<meta charset='UTF-8'>";
echo "<meta name='viewport' content='width=device-width, initial-scale=1.0'>";
echo "<title>Create Admin</title>";
echo "<link href='https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;600;700&display=swap' rel='stylesheet'>";
echo "<style>";
echo "body { font-family: 'Kanit', Arial; padding: 50px; background: linear-gradient(135deg, #1a1a2e, #16213e); color: #fff; }";
echo ".container { max-width: 600px; margin: 0 auto; background: rgba(255,255,255,0.1); padding: 30px; border-radius: 15px; border: 1px solid rgba(255,107,0,0.3); }";
echo "h2 { color: #ff6b00; }";
echo ".success { background: rgba(40,167,69,0.2); border: 1px solid #28a745; color: #7dff9e; padding: 15px; border-radius: 8px; margin: 20px 0; }";
echo ".warning { background: rgba(255,193,7,0.2); border: 1px solid #ffc107; color: #ffd966; padding: 15px; border-radius: 8px; margin: 20px 0; }";
echo ".error { background: rgba(220,53,69,0.2); border: 1px solid #dc3545; color: #ff6b6b; padding: 15px; border-radius: 8px; margin: 20px 0; }";
echo ".info { background: rgba(0,173,181,0.1); padding: 10px; border-left: 3px solid #00adb5; margin: 10px 0; }";
echo ".btn { background: linear-gradient(135deg, #ff6b00, #ffc107); color: white; padding: 12px 30px; text-decoration: none; border-radius: 8px; display: inline-block; margin-top: 20px; }";
echo "code { background: rgba(255,107,0,0.2); padding: 3px 8px; border-radius: 4px; color: #ffc107; }";
echo "</style>";
echo "</head>";
echo "<body>";
echo "<div class='container'>";

try {
    // ตรวจสอบว่ามี User นี้แล้วหรือยัง
    $checkSql = "SELECT id, username, email FROM users WHERE username = :username LIMIT 1";
    $checkStmt = $pdo->prepare($checkSql);
    $checkStmt->bindValue(':username', $adminData['username'], PDO::PARAM_STR);
    $checkStmt->execute();
    
    $existingUser = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existingUser) {
        echo "<div class='warning'>";
        echo "<h2>⚠️ User Already Exists</h2>";
        echo "<p><strong>ID:</strong> {$existingUser['id']}</p>";
        echo "<p><strong>Username:</strong> {$existingUser['username']}</p>";
        echo "<p><strong>Email:</strong> {$existingUser['email']}</p>";
        echo "</div>";
        
        echo "<div class='info'>";
        echo "<p><strong>ต้องการรีเซ็ตรหัสผ่าน?</strong></p>";
        echo "<p>รันคำสั่ง SQL นี้ใน phpMyAdmin:</p>";
        
        $newHash = password_hash($adminData['password'], PASSWORD_DEFAULT);
        echo "<code style='display: block; margin-top: 10px; padding: 10px;'>";
        echo "UPDATE users SET password = '{$newHash}' WHERE username = '{$adminData['username']}';";
        echo "</code>";
        echo "</div>";
        
    } else {
        // Hash password
        $hashedPassword = password_hash($adminData['password'], PASSWORD_DEFAULT);
        
        // Insert User
        $sql = "INSERT INTO users (username, email, password, full_name, role, is_active, created_at) 
                VALUES (:username, :email, :password, :full_name, :role, 1, NOW())";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':username', $adminData['username'], PDO::PARAM_STR);
        $stmt->bindValue(':email', $adminData['email'], PDO::PARAM_STR);
        $stmt->bindValue(':password', $hashedPassword, PDO::PARAM_STR);
        $stmt->bindValue(':full_name', $adminData['full_name'], PDO::PARAM_STR);
        $stmt->bindValue(':role', $adminData['role'], PDO::PARAM_STR);
        $stmt->execute();
        
        $userId = $pdo->lastInsertId();
        
        echo "<div class='success'>";
        echo "<h2>✅ Admin User Created Successfully!</h2>";
        echo "<p><strong>User ID:</strong> {$userId}</p>";
        echo "<p><strong>Username:</strong> <code>{$adminData['username']}</code></p>";
        echo "<p><strong>Email:</strong> <code>{$adminData['email']}</code></p>";
        echo "<p><strong>Password:</strong> <code>{$adminData['password']}</code></p>";
        echo "<p><strong>Role:</strong> <code>{$adminData['role']}</code></p>";
        echo "</div>";
        
        echo "<div class='warning'>";
        echo "<p><strong>⚠️ Important:</strong> กรุณาเปลี่ยนรหัสผ่านหลังจาก Login ครั้งแรก!</p>";
        echo "</div>";
        
        echo "<a href='../pages/login.php' class='btn'>เข้าสู่หน้า Login</a>";
    }
    
} catch (PDOException $e) {
    echo "<div class='error'>";
    echo "<h2>❌ Database Error</h2>";
    echo "<p><strong>Message:</strong> {$e->getMessage()}</p>";
    echo "<p><strong>Code:</strong> {$e->getCode()}</p>";
    echo "</div>";
} catch (Exception $e) {
    echo "<div class='error'>";
    echo "<h2>❌ Error</h2>";
    echo "<p>{$e->getMessage()}</p>";
    echo "</div>";
}

echo "</div></body></html>";
```

---

## 🎯 ขั้นตอนการแก้ปัญหา

### 1. สร้าง Admin User ก่อน:
```
http://localhost/pueankan/setup/create-admin.php
```

### 2. ถ้าบอกว่ามีแล้ว ให้คัดลอก SQL จากหน้านั้น แล้วรันใน phpMyAdmin

### 3. Login ที่:
```
http://localhost/pueankan/pages/login.php