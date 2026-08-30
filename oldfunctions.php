<?php
require_once 'config.php';

function verifyRecaptcha($token) {
    $url = 'https://www.google.com/recaptcha/api/siteverify';
    $data = [
        'secret' => RECAPTCHA_SECRET_KEY,
        'response' => $token
    ];
    $options = [
        'http' => [
            'header' => "Content-type: application/x-www-form-urlencoded\r\n",
            'method' => 'POST',
            'content' => http_build_query($data)
        ]
    ];
    $context = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
    $response = json_decode($result);
    return $response->success ?? false;
}

function isAdmin() {
    return isset($_SESSION['user_id']) && $_SESSION['role'] === 'admin';
}

function generateOrderNumber() {
    return 'ORD-' . strtoupper(uniqid()) . '-' . date('YmdHis');
}

function sendEmail($to, $subject, $body) {
    require_once 'PHPMailer/PHPMailer.php';
    require_once 'PHPMailer/SMTP.php';
    require_once 'PHPMailer/Exception.php';
    
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USER;
        $mail->Password = SMTP_PASS;
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = SMTP_PORT;
        
        $mail->setFrom(SMTP_FROM, SITE_NAME);
        $mail->addAddress($to);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $body;
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email failed: " . $mail->ErrorInfo);
        return false;
    }
}

function getCart() {
    return $_SESSION['cart'] ?? [];
}

function addToCart($variant_id, $quantity = 1) {
    if (!isset($_SESSION['cart'][$variant_id])) {
        $_SESSION['cart'][$variant_id] = 0;
    }
    $_SESSION['cart'][$variant_id] += $quantity;
}

function removeFromCart($variant_id) {
    unset($_SESSION['cart'][$variant_id]);
}

function clearCart() {
    unset($_SESSION['cart']);
}

function getCartTotal() {
    global $conn;
    $total = 0;
    foreach (getCart() as $variant_id => $qty) {
        $stmt = $conn->prepare("SELECT price FROM product_variants WHERE id = ?");
        $stmt->bind_param("i", $variant_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $total += $row['price'] * $qty;
        }
    }
    return $total;
}
?>