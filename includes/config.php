<?php
/**
 * Bolão da Sorte - Configuration File
 * Database: MySQL | Security: bcrypt, CSRF
 */

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'bolaodasorte');
define('DB_USER', 'root');
define('DB_PASS', '');

// Application Settings
define('APP_NAME', 'Bolão da Sorte');
define('APP_VERSION', '2.0');
define('ADMIN_EMAIL', 'admin@bolaodasorte.com');
define('ADMIN_PASSWORD', 'admin123');

// Paths
define('BASE_PATH', dirname(__DIR__));
define('ASSETS_PATH', '/bolaodasorte/assets');
define('UPLOADS_PATH', BASE_PATH . '/uploads');

// PIX Configuration
define('PIX_KEY_TYPE', 'telefone');
define('PIX_KEY', '61983273047');
define('PIX_HOLDER', 'Wendel O Silva');
define('PIX_BANK', 'Nubank');

// Tax Configuration (Lottery prizes)
define('TAX_EXEMPT_LIMIT', 1903.98); // Valores até este limite são isentos
define('TAX_RATE', 0.138); // 13,8% para valores acima do limite

// SMTP Email Configuration
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'wendelwos2.0@gmail.com');
define('SMTP_PASS', 'xaqw qvrc tiys jyqk');
define('SMTP_FROM_NAME', 'Bolão da Sorte');

// Load PHPMailer
require_once BASE_PATH . '/vendor/autoload.php';

// Session & Security
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);

// CSRF Token Generation
function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function csrfField() {
    return '<input type="hidden" name="csrf_token" value="' . generateCsrfToken() . '">';
}

// Flash Messages
function setFlash($type, $message) {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

// Database Connection
function getDatabase() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $pdo = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch (PDOException $e) {
            try {
                $pdo = new PDO("mysql:host=" . DB_HOST, DB_USER, DB_PASS);
                $pdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                $pdo = new PDO(
                    "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                    DB_USER,
                    DB_PASS,
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                    ]
                );
            } catch (PDOException $e2) {
                die("Database Error: " . $e2->getMessage());
            }
        }
    }
    return $pdo;
}

// Initialize Database Schema
function initializeDatabase() {
    $pdo = getDatabase();
    
    $tables = [
        "CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            phone VARCHAR(20) DEFAULT NULL,
            address VARCHAR(255) DEFAULT NULL,
            is_admin TINYINT DEFAULT 0,
            status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB",
        
        "CREATE TABLE IF NOT EXISTS events (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(200) NOT NULL,
            game_type ENUM('mega', 'quina', 'lotofacil') NOT NULL,
            total_target_value DECIMAL(10,2) DEFAULT 0,
            game_price DECIMAL(10,2) NOT NULL,
            status ENUM('open', 'closed', 'finished') DEFAULT 'open',
            draw_number INT DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB",
        
        "CREATE TABLE IF NOT EXISTS quotas (
            id INT AUTO_INCREMENT PRIMARY KEY,
            event_id INT NOT NULL,
            user_id INT NOT NULL,
            games_allowed INT DEFAULT 0,
            amount_paid DECIMAL(10,2) DEFAULT 0,
            FOREIGN KEY(event_id) REFERENCES events(id) ON DELETE CASCADE,
            FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
            UNIQUE KEY unique_quota (event_id, user_id)
        ) ENGINE=InnoDB",
        
        "CREATE TABLE IF NOT EXISTS bets (
            id INT AUTO_INCREMENT PRIMARY KEY,
            event_id INT NOT NULL,
            user_id INT DEFAULT NULL,
            numbers VARCHAR(100) NOT NULL,
            type ENUM('manual', 'auto') DEFAULT 'manual',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY(event_id) REFERENCES events(id) ON DELETE CASCADE,
            FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB",
        
        "CREATE TABLE IF NOT EXISTS participation_requests (
            id INT AUTO_INCREMENT PRIMARY KEY,
            event_id INT NOT NULL,
            user_id INT NOT NULL,
            message TEXT,
            status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY(event_id) REFERENCES events(id) ON DELETE CASCADE,
            FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
            UNIQUE KEY unique_request (event_id, user_id)
        ) ENGINE=InnoDB",
        
        "CREATE TABLE IF NOT EXISTS password_resets (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            token VARCHAR(64) NOT NULL UNIQUE,
            expires_at DATETIME NOT NULL,
            used TINYINT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB",
        
        "CREATE TABLE IF NOT EXISTS payments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            event_id INT NOT NULL,
            amount DECIMAL(10,2) NOT NULL,
            receipt_path VARCHAR(255) DEFAULT NULL,
            status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
            admin_notes TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY(event_id) REFERENCES events(id) ON DELETE CASCADE
        ) ENGINE=InnoDB"
    ];
    
    foreach ($tables as $sql) {
        $pdo->exec($sql);
    }
    
    // Migration: Add phone and address columns if they don't exist
    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN phone VARCHAR(20) DEFAULT NULL AFTER password");
    } catch (Exception $e) { /* Column exists */ }
    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN address VARCHAR(255) DEFAULT NULL AFTER phone");
    } catch (Exception $e) { /* Column exists */ }
    
    // Migration: Add prize columns to events
    try {
        $pdo->exec("ALTER TABLE events ADD COLUMN prize_gross DECIMAL(15,2) DEFAULT 0");
    } catch (Exception $e) { /* Column exists */ }
    try {
        $pdo->exec("ALTER TABLE events ADD COLUMN prize_net DECIMAL(15,2) DEFAULT 0");
    } catch (Exception $e) { /* Column exists */ }
    try {
        $pdo->exec("ALTER TABLE events ADD COLUMN prize_tax DECIMAL(15,2) DEFAULT 0");
    } catch (Exception $e) { /* Column exists */ }
    try {
        $pdo->exec("ALTER TABLE events ADD COLUMN estimated_prize DECIMAL(15,2) DEFAULT 0");
    } catch (Exception $e) { /* Column exists */ }
    
    // Create default admin if not exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([ADMIN_EMAIL]);
    if (!$stmt->fetch()) {
        $hash = password_hash(ADMIN_PASSWORD, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password, is_admin, status) VALUES (?, ?, ?, 1, 'approved')");
        $stmt->execute(['Administrador', ADMIN_EMAIL, $hash]);
    }
}

// Helper Functions
function getCurrentUser() {
    if (!isset($_SESSION['user_id'])) return null;
    $pdo = getDatabase();
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND status = 'approved'");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

function getGameConfig($type) {
    $configs = [
        'mega' => ['name' => 'Mega Sena', 'min' => 1, 'max' => 60, 'draw' => 6, 'color' => 'bg-green-600'],
        'quina' => ['name' => 'Quina', 'min' => 1, 'max' => 80, 'draw' => 5, 'color' => 'bg-purple-600'],
        'lotofacil' => ['name' => 'Lotofácil', 'min' => 1, 'max' => 25, 'draw' => 15, 'color' => 'bg-pink-600'],
    ];
    return $configs[$type] ?? $configs['mega'];
}

function formatMoney($val) {
    return 'R$ ' . number_format($val, 2, ',', '.');
}

function h($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

function redirect($url) {
    header("Location: $url");
    exit;
}

function getLotteryResult($type, $drawNumber) {
    if (!$drawNumber) return null;
    
    $map = ['mega' => 'megasena', 'quina' => 'quina', 'lotofacil' => 'lotofacil'];
    $apiType = $map[$type] ?? $type;
    $url = "https://loteriascaixa-api.herokuapp.com/api/$apiType/$drawNumber";
    
    if (function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_FOLLOWLOCATION => true
        ]);
        $json = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($json && $httpCode === 200) {
            return json_decode($json, true);
        }
    }
    return null;
}

function getBaseUrl() {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $script = dirname($_SERVER['SCRIPT_NAME']);
    return $protocol . '://' . $host . $script;
}

function sendPasswordResetEmail($email, $name, $token) {
    $resetUrl = getBaseUrl() . '?action=reset_password&token=' . $token;
    
    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;
        $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = SMTP_PORT;
        $mail->CharSet    = 'UTF-8';
        
        // Recipients
        $mail->setFrom(SMTP_USER, SMTP_FROM_NAME);
        $mail->addAddress($email, $name);
        $mail->addReplyTo(SMTP_USER, SMTP_FROM_NAME);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = APP_NAME . ' - Recuperação de Senha';
        $mail->Body    = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; }
                .container { max-width: 500px; margin: 0 auto; padding: 20px; }
                .header { background: #009e4a; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
                .content { padding: 20px; background: #f9f9f9; }
                .button { display: inline-block; background: #009e4a; color: white !important; padding: 12px 30px; text-decoration: none; border-radius: 5px; margin: 20px 0; }
                .footer { font-size: 12px; color: #666; padding: 20px; text-align: center; border-radius: 0 0 8px 8px; background: #eee; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>🎲 " . APP_NAME . "</h1>
                </div>
                <div class='content'>
                    <p>Olá <strong>$name</strong>,</p>
                    <p>Recebemos uma solicitação para redefinir sua senha.</p>
                    <p>Clique no botão abaixo para criar uma nova senha:</p>
                    <p style='text-align: center;'>
                        <a href='$resetUrl' class='button'>🔑 Redefinir Senha</a>
                    </p>
                    <p>Ou copie e cole este link no navegador:</p>
                    <p style='word-break: break-all; font-size: 12px; background: #fff; padding: 10px; border-radius: 4px;'>$resetUrl</p>
                    <p><strong>⏰ Este link expira em 1 hora.</strong></p>
                    <p>Se você não solicitou esta redefinição, ignore este email.</p>
                </div>
                <div class='footer'>
                    <p>&copy; " . date('Y') . " " . APP_NAME . " - Todos os direitos reservados</p>
                </div>
            </div>
        </body>
        </html>";
        
        $mail->AltBody = "Olá $name,\n\nRecebemos uma solicitação para redefinir sua senha.\n\nAcesse o link abaixo para criar uma nova senha:\n$resetUrl\n\nEste link expira em 1 hora.\n\nSe você não solicitou esta redefinição, ignore este email.\n\n" . APP_NAME;
        
        $mail->send();
        return true;
        
    } catch (Exception $e) {
        error_log("Email sending failed for $email: " . $mail->ErrorInfo);
        error_log("PASSWORD RESET LINK for $email: $resetUrl");
        return false;
    }
}

function sendPaymentApprovedEmail($email, $name, $eventName) {
    $dashboardUrl = getBaseUrl() . '?action=dashboard';
    
    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
    
    try {
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;
        $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = SMTP_PORT;
        $mail->CharSet    = 'UTF-8';
        
        $mail->setFrom(SMTP_USER, SMTP_FROM_NAME);
        $mail->addAddress($email, $name);
        
        $mail->isHTML(true);
        $mail->Subject = APP_NAME . ' - Pagamento Confirmado! ✅';
        $mail->Body    = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; }
                .container { max-width: 500px; margin: 0 auto; padding: 20px; }
                .header { background: #009e4a; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
                .content { padding: 20px; background: #f9f9f9; }
                .button { display: inline-block; background: #009e4a; color: white !important; padding: 12px 30px; text-decoration: none; border-radius: 5px; margin: 20px 0; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>✅ Pagamento Confirmado!</h1>
                </div>
                <div class='content'>
                    <p>Olá <strong>$name</strong>,</p>
                    <p>Seu pagamento para o bolão <strong>$eventName</strong> foi confirmado!</p>
                    <p>Agora você já pode fazer suas apostas.</p>
                    <p style='text-align: center;'>
                        <a href='$dashboardUrl' class='button'>🎲 Fazer Apostas</a>
                    </p>
                    <p>Boa sorte! 🍀</p>
                </div>
            </div>
        </body>
        </html>";
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Payment email failed for $email: " . $mail->ErrorInfo);
        return false;
    }
}

// Initialize on load
initializeDatabase();
