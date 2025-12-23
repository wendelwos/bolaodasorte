<?php
/**
 * Bolão da Sorte v2.0 - Controllers
 * Handles all POST/GET actions
 */

require_once __DIR__ . '/config.php';

$pdo = getDatabase();
$action = $_GET['action'] ?? 'home';
$user = getCurrentUser();

// Process POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF for all POST
    if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
        setFlash('error', 'Token de segurança inválido. Tente novamente.');
        redirect('?');
    }
    
    // LOGIN
    if ($action === 'login') {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        
        // First check if user exists at all
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $u = $stmt->fetch();
        
        if ($u) {
            // User exists, check password
            if (password_verify($password, $u['password'])) {
                // Password correct, check status
                if ($u['status'] === 'approved') {
                    $_SESSION['user_id'] = $u['id'];
                    setFlash('success', 'Bem-vindo, ' . h($u['name']) . '!');
                    redirect('?action=dashboard');
                } elseif ($u['status'] === 'pending') {
                    setFlash('error', 'Sua conta ainda está aguardando aprovação do administrador.');
                } else {
                    setFlash('error', 'Sua conta foi rejeitada. Entre em contato com o administrador.');
                }
            } else {
                setFlash('error', 'Senha incorreta!');
            }
        } else {
            setFlash('error', 'Email não encontrado!');
        }
    }
    
    // FORGOT PASSWORD - Request reset
    if ($action === 'forgot_password') {
        $email = trim($_POST['email'] ?? '');
        
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $stmt = $pdo->prepare("SELECT id, name FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $u = $stmt->fetch();
            
            if ($u) {
                // Generate token
                $token = bin2hex(random_bytes(32));
                $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));
                
                // Invalidate old tokens
                $stmt = $pdo->prepare("UPDATE password_resets SET used = 1 WHERE user_id = ?");
                $stmt->execute([$u['id']]);
                
                // Save new token
                $stmt = $pdo->prepare("INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)");
                $stmt->execute([$u['id'], $token, $expiresAt]);
                
                // Send email
                sendPasswordResetEmail($email, $u['name'], $token);
            }
        }
        
        // Always show success to not reveal if email exists
        setFlash('success', 'Se o email existir em nosso sistema, você receberá um link de recuperação.');
        redirect('?action=login');
    }
    
    // RESET PASSWORD - Set new password
    if ($action === 'reset_password') {
        $token = $_POST['token'] ?? '';
        $password = $_POST['password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        
        if (strlen($password) < 6) {
            setFlash('error', 'Senha deve ter pelo menos 6 caracteres.');
            redirect('?action=reset_password&token=' . urlencode($token));
        } elseif ($password !== $confirm) {
            setFlash('error', 'As senhas não conferem.');
            redirect('?action=reset_password&token=' . urlencode($token));
        } else {
            // Validate token
            $stmt = $pdo->prepare("SELECT pr.*, u.id as user_id FROM password_resets pr 
                                   JOIN users u ON pr.user_id = u.id 
                                   WHERE pr.token = ? AND pr.used = 0 AND pr.expires_at > NOW()");
            $stmt->execute([$token]);
            $reset = $stmt->fetch();
            
            if ($reset) {
                // Update password
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->execute([$hash, $reset['user_id']]);
                
                // Mark token as used
                $stmt = $pdo->prepare("UPDATE password_resets SET used = 1 WHERE id = ?");
                $stmt->execute([$reset['id']]);
                
                setFlash('success', 'Senha alterada com sucesso! Faça login com sua nova senha.');
                redirect('?action=login');
            } else {
                setFlash('error', 'Link inválido ou expirado. Solicite um novo link.');
                redirect('?action=forgot_password');
            }
        }
    }
    
    // REGISTER
    if ($action === 'register') {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        
        if (strlen($name) < 3) {
            setFlash('error', 'Nome deve ter pelo menos 3 caracteres.');
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            setFlash('error', 'Email inválido.');
        } elseif (strlen($password) < 6) {
            setFlash('error', 'Senha deve ter pelo menos 6 caracteres.');
        } elseif ($password !== $confirm) {
            setFlash('error', 'As senhas não conferem.');
        } else {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                setFlash('error', 'Este email já está cadastrado.');
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                // Auto-approve user accounts - only bolão participation needs approval
                $stmt = $pdo->prepare("INSERT INTO users (name, email, password, status) VALUES (?, ?, ?, 'approved')");
                $stmt->execute([$name, $email, $hash]);
                
                // Auto-login after registration
                $newUserId = $pdo->lastInsertId();
                $_SESSION['user_id'] = $newUserId;
                setFlash('success', 'Conta criada com sucesso! Bem-vindo, ' . h($name) . '!');
                redirect('?action=public_events');
            }
        }
    }
    
    // ADMIN: CREATE USER
    if ($action === 'create_user' && $user && $user['is_admin']) {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if ($name && $email && $password) {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            try {
                $stmt = $pdo->prepare("INSERT INTO users (name, email, password, status) VALUES (?, ?, ?, 'approved')");
                $stmt->execute([$name, $email, $hash]);
                setFlash('success', 'Usuário criado com sucesso!');
            } catch (Exception $e) {
                setFlash('error', 'Erro: Email já existe.');
            }
        }
        redirect('?action=manage_users');
    }
    
    // ADMIN: APPROVE/REJECT USER
    if ($action === 'update_user_status' && $user && $user['is_admin']) {
        $userId = (int)($_POST['user_id'] ?? 0);
        $status = $_POST['status'] ?? '';
        if (in_array($status, ['approved', 'rejected']) && $userId) {
            $stmt = $pdo->prepare("UPDATE users SET status = ? WHERE id = ? AND is_admin = 0");
            $stmt->execute([$status, $userId]);
            setFlash('success', 'Status atualizado!');
        }
        redirect('?action=pending_users');
    }
    
    // ADMIN: DELETE USER
    if ($action === 'delete_user' && $user && $user['is_admin']) {
        $userId = (int)($_POST['user_id'] ?? 0);
        if ($userId && $userId != $user['id']) {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND is_admin = 0");
            $stmt->execute([$userId]);
            setFlash('success', 'Usuário excluído!');
        }
        redirect('?action=manage_users');
    }
    
    // ADMIN: CREATE EVENT
    if ($action === 'create_event' && $user && $user['is_admin']) {
        $name = trim($_POST['name'] ?? '');
        $type = $_POST['game_type'] ?? 'mega';
        $price = floatval($_POST['game_price'] ?? 6);
        $draw = intval($_POST['draw_number'] ?? 0) ?: null;
        
        $stmt = $pdo->prepare("INSERT INTO events (name, game_type, game_price, draw_number) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $type, $price, $draw]);
        setFlash('success', 'Bolão criado com sucesso!');
        redirect('?action=dashboard');
    }
    
    // ADMIN: UPDATE EVENT
    if ($action === 'update_event' && $user && $user['is_admin']) {
        $id = (int)($_POST['event_id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $status = $_POST['status'] ?? 'open';
        $draw = intval($_POST['draw_number'] ?? 0) ?: null;
        $estimatedPrize = floatval($_POST['estimated_prize'] ?? 0);
        
        $stmt = $pdo->prepare("UPDATE events SET name = ?, status = ?, draw_number = ?, estimated_prize = ? WHERE id = ?");
        $stmt->execute([$name, $status, $draw, $estimatedPrize, $id]);
        setFlash('success', 'Bolão atualizado!');
        redirect("?action=manage_event&id=$id");
    }
    
    // ADMIN: DELETE EVENT
    if ($action === 'delete_event' && $user && $user['is_admin']) {
        $id = (int)($_POST['event_id'] ?? 0);
        
        if ($id) {
            // Delete related records first (to avoid foreign key constraints)
            $pdo->prepare("DELETE FROM bets WHERE event_id = ?")->execute([$id]);
            $pdo->prepare("DELETE FROM quotas WHERE event_id = ?")->execute([$id]);
            $pdo->prepare("DELETE FROM payments WHERE event_id = ?")->execute([$id]);
            $pdo->prepare("DELETE FROM participation_requests WHERE event_id = ?")->execute([$id]);
            
            // Now delete the event
            $stmt = $pdo->prepare("DELETE FROM events WHERE id = ?");
            $stmt->execute([$id]);
            
            setFlash('success', 'Bolão e todos os dados relacionados foram excluídos!');
        }
        redirect('?action=manage_events');
    }
    
    // ADMIN: ADD QUOTA
    if ($action === 'add_quota' && $user && $user['is_admin']) {
        $eventId = (int)($_POST['event_id'] ?? 0);
        $userId = (int)($_POST['user_id'] ?? 0);
        $games = (int)($_POST['games_allowed'] ?? 1);
        $paid = floatval($_POST['amount_paid'] ?? 0);
        
        $stmt = $pdo->prepare("INSERT INTO quotas (event_id, user_id, games_allowed, amount_paid) 
            VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE games_allowed = ?, amount_paid = ?");
        $stmt->execute([$eventId, $userId, $games, $paid, $games, $paid]);
        setFlash('success', 'Cota salva!');
        redirect("?action=manage_event&id=$eventId");
    }
    
    // REQUEST PARTICIPATION
    if ($action === 'request_participation' && $user) {
        $eventId = (int)($_POST['event_id'] ?? 0);
        $message = trim($_POST['message'] ?? '');
        
        try {
            $stmt = $pdo->prepare("INSERT INTO participation_requests (event_id, user_id, message) VALUES (?, ?, ?)");
            $stmt->execute([$eventId, $user['id'], $message]);
            setFlash('success', 'Solicitação enviada! Aguarde aprovação.');
        } catch (Exception $e) {
            setFlash('error', 'Você já solicitou participação neste bolão.');
        }
        redirect('?action=public_events');
    }
    
    // ADMIN: HANDLE PARTICIPATION REQUEST
    if ($action === 'handle_request' && $user && $user['is_admin']) {
        $requestId = (int)($_POST['request_id'] ?? 0);
        $status = $_POST['status'] ?? '';
        $games = (int)($_POST['games_allowed'] ?? 1);
        $paid = floatval($_POST['amount_paid'] ?? 0);
        
        if (in_array($status, ['approved', 'rejected'])) {
            $stmt = $pdo->prepare("UPDATE participation_requests SET status = ? WHERE id = ?");
            $stmt->execute([$status, $requestId]);
            
            if ($status === 'approved') {
                // Create quota with payment_status='pending' - user can bet but must pay
                $stmt = $pdo->prepare("SELECT event_id, user_id FROM participation_requests WHERE id = ?");
                $stmt->execute([$requestId]);
                $req = $stmt->fetch();
                if ($req) {
                    $stmt = $pdo->prepare("INSERT INTO quotas (event_id, user_id, games_allowed, amount_paid) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$req['event_id'], $req['user_id'], $games, $paid]);
                    
                    // Create pending payment record
                    $stmt = $pdo->prepare("INSERT INTO payments (user_id, event_id, amount, status) VALUES (?, ?, ?, 'pending')");
                    $stmt->execute([$req['user_id'], $req['event_id'], $paid]);
                }
            }
            setFlash('success', 'Solicitação ' . ($status === 'approved' ? 'aprovada!' : 'rejeitada!'));
        }
        redirect('?action=manage_requests');
    }
    
    // PLACE BET
    if ($action === 'place_bet' && $user) {
        $eventId = (int)($_POST['event_id'] ?? 0);
        $numbers = $_POST['numbers'] ?? [];
        
        // Get event to check for results
        $stmt = $pdo->prepare("SELECT * FROM events WHERE id = ?");
        $stmt->execute([$eventId]);
        $event = $stmt->fetch();
        
        // Check if results already exist
        if ($event) {
            $apiResult = getLotteryResult($event['game_type'], $event['draw_number']);
            if ($apiResult && isset($apiResult['dezenas'])) {
                setFlash('error', 'Não é possível apostar em concurso já sorteado!');
                redirect("?action=view_event&id=$eventId");
            }
        }
        
        if (is_array($numbers)) {
            sort($numbers);
            $numStr = implode(',', array_map('intval', $numbers));
            
            $stmt = $pdo->prepare("SELECT * FROM quotas WHERE event_id = ? AND user_id = ?");
            $stmt->execute([$eventId, $user['id']]);
            $quota = $stmt->fetch();
            
            $stmt = $pdo->prepare("SELECT COUNT(*) as c FROM bets WHERE event_id = ? AND user_id = ?");
            $stmt->execute([$eventId, $user['id']]);
            $count = $stmt->fetch()['c'];
            
            if ($quota && $count < $quota['games_allowed']) {
                $stmt = $pdo->prepare("INSERT INTO bets (event_id, user_id, numbers, type) VALUES (?, ?, ?, 'manual')");
                $stmt->execute([$eventId, $user['id'], $numStr]);
                setFlash('success', 'Aposta registrada com sucesso!');
            } else {
                setFlash('error', 'Cota excedida!');
            }
        }
        redirect("?action=view_event&id=$eventId");
    }
    
    // ADMIN: AUTOCOMPLETE
    if ($action === 'autocomplete' && $user && $user['is_admin']) {
        $eventId = (int)($_POST['event_id'] ?? 0);
        
        $stmt = $pdo->prepare("SELECT * FROM events WHERE id = ?");
        $stmt->execute([$eventId]);
        $event = $stmt->fetch();
        
        $stmt = $pdo->prepare("SELECT SUM(amount_paid) as total FROM quotas WHERE event_id = ?");
        $stmt->execute([$eventId]);
        $totalPaid = $stmt->fetch()['total'] ?? 0;
        
        $stmt = $pdo->prepare("SELECT COUNT(*) as c FROM bets WHERE event_id = ?");
        $stmt->execute([$eventId]);
        $totalBets = $stmt->fetch()['c'];
        
        $remaining = $totalPaid - ($totalBets * $event['game_price']);
        $gamesToGen = floor($remaining / $event['game_price']);
        
        $config = getGameConfig($event['game_type']);
        
        for ($i = 0; $i < $gamesToGen; $i++) {
            $nums = [];
            while (count($nums) < $config['draw']) {
                $n = rand($config['min'], $config['max']);
                if (!in_array($n, $nums)) $nums[] = $n;
            }
            sort($nums);
            $stmt = $pdo->prepare("INSERT INTO bets (event_id, user_id, numbers, type) VALUES (?, NULL, ?, 'auto')");
            $stmt->execute([$eventId, implode(',', $nums)]);
        }
        
        setFlash('success', "$gamesToGen jogos gerados automaticamente!");
        redirect("?action=manage_event&id=$eventId");
    }
    
    // UPDATE PROFILE
    if ($action === 'update_profile' && $user) {
        $name = trim($_POST['name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        if (strlen($name) < 3) {
            setFlash('error', 'Nome deve ter pelo menos 3 caracteres.');
        } elseif (empty($phone)) {
            setFlash('error', 'Telefone é obrigatório.');
        } elseif ($newPassword && strlen($newPassword) < 6) {
            setFlash('error', 'Nova senha deve ter pelo menos 6 caracteres.');
        } elseif ($newPassword && $newPassword !== $confirmPassword) {
            setFlash('error', 'As senhas não conferem.');
        } else {
            if ($newPassword) {
                $hash = password_hash($newPassword, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET name = ?, phone = ?, address = ?, password = ? WHERE id = ?");
                $stmt->execute([$name, $phone, $address, $hash, $user['id']]);
            } else {
                $stmt = $pdo->prepare("UPDATE users SET name = ?, phone = ?, address = ? WHERE id = ?");
                $stmt->execute([$name, $phone, $address, $user['id']]);
            }
            setFlash('success', 'Perfil atualizado com sucesso!');
        }
        redirect('?action=profile');
    }
    
    // REQUEST MORE GAMES
    if ($action === 'request_more_games' && $user) {
        $eventId = (int)($_POST['event_id'] ?? 0);
        $extraGames = (int)($_POST['extra_games'] ?? 1);
        
        if ($eventId && $extraGames > 0 && $extraGames <= 10) {
            // Get current quota
            $stmt = $pdo->prepare("SELECT * FROM quotas WHERE event_id = ? AND user_id = ?");
            $stmt->execute([$eventId, $user['id']]);
            $quota = $stmt->fetch();
            
            // Get event for price
            $stmt = $pdo->prepare("SELECT game_price FROM events WHERE id = ?");
            $stmt->execute([$eventId]);
            $event = $stmt->fetch();
            
            if ($quota && $event) {
                $additionalAmount = $extraGames * $event['game_price'];
                
                // Only update quota - don't touch payments!
                // The remaining amount is calculated automatically: total - paid - pending
                $stmt = $pdo->prepare("UPDATE quotas SET games_allowed = games_allowed + ?, amount_paid = amount_paid + ? WHERE event_id = ? AND user_id = ?");
                $stmt->execute([$extraGames, $additionalAmount, $eventId, $user['id']]);
                
                setFlash('success', "Adicionado $extraGames jogo(s)! Pague o valor adicional de " . formatMoney($additionalAmount));
            } else {
                setFlash('error', 'Quota não encontrada.');
            }
        } else {
            setFlash('error', 'Dados inválidos.');
        }
        redirect("?action=view_event&id=$eventId");
    }
    
    // UPLOAD RECEIPT (optional file)
    if ($action === 'upload_receipt' && $user) {
        $eventId = (int)($_POST['event_id'] ?? 0);
        
        if (!$eventId) {
            setFlash('error', 'Dados inválidos.');
            redirect('?action=dashboard');
        }
        
        // Calculate the remaining amount to pay
        $stmt = $pdo->prepare("SELECT * FROM quotas WHERE event_id = ? AND user_id = ?");
        $stmt->execute([$eventId, $user['id']]);
        $quota = $stmt->fetch();
        
        $stmt = $pdo->prepare("SELECT * FROM events WHERE id = ?");
        $stmt->execute([$eventId]);
        $event = $stmt->fetch();
        
        if (!$quota || !$event) {
            setFlash('error', 'Quota não encontrada.');
            redirect('?action=dashboard');
        }
        
        // Check if contest has been drawn - block payments
        if ($event['contest_number']) {
            $result = getLotteryResult($event['game_type'], $event['contest_number']);
            if ($result && !empty($result['dezenas'])) {
                setFlash('error', 'Não é possível realizar pagamentos após o concurso ser sorteado.');
                redirect("?action=payment&event_id=$eventId");
            }
        }
        
        $totalAmount = $quota['games_allowed'] * $event['game_price'];
        
        // Get approved and pending amounts
        $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) as paid FROM payments WHERE event_id = ? AND user_id = ? AND status = 'approved'");
        $stmt->execute([$eventId, $user['id']]);
        $paidAmount = (float)$stmt->fetch()['paid'];
        
        $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) as pending FROM payments WHERE event_id = ? AND user_id = ? AND status = 'pending'");
        $stmt->execute([$eventId, $user['id']]);
        $pendingAmount = (float)$stmt->fetch()['pending'];
        
        $remainingAmount = max(0, $totalAmount - $paidAmount - $pendingAmount);
        
        if ($remainingAmount <= 0) {
            setFlash('info', 'Nenhum valor pendente. Todos os pagamentos já foram enviados.');
            redirect("?action=payment&event_id=$eventId");
        }
        
        $filepath = null;
        
        // Check if file was uploaded (optional)
        if (isset($_FILES['receipt']) && $_FILES['receipt']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['receipt'];
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            
            if (!in_array($file['type'], $allowedTypes)) {
                setFlash('error', 'Tipo de arquivo não permitido. Use JPG, PNG ou GIF.');
                redirect("?action=payment&event_id=$eventId");
            }
            
            if ($file['size'] > 5 * 1024 * 1024) { // 5MB
                setFlash('error', 'Arquivo muito grande. Máximo 5MB.');
                redirect("?action=payment&event_id=$eventId");
            }
            
            // Create uploads directory if not exists
            $uploadDir = UPLOADS_PATH . '/receipts/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            // Generate unique filename
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = 'receipt_' . $user['id'] . '_' . $eventId . '_' . time() . '.' . $ext;
            $filepath = $uploadDir . $filename;
            
            if (!move_uploaded_file($file['tmp_name'], $filepath)) {
                setFlash('error', 'Erro ao salvar arquivo.');
                redirect("?action=payment&event_id=$eventId");
            }
        }
        
        // Always create NEW payment record with the remaining amount
        $stmt = $pdo->prepare("INSERT INTO payments (user_id, event_id, amount, receipt_path, status) VALUES (?, ?, ?, ?, 'pending')");
        $stmt->execute([$user['id'], $eventId, $remainingAmount, $filepath]);
        
        setFlash('success', 'Pagamento de ' . formatMoney($remainingAmount) . ' registrado! Aguarde a confirmação.');
        redirect("?action=payment&event_id=$eventId");
    }
    
    // ADMIN: APPROVE PAYMENT
    if ($action === 'approve_payment' && $user && $user['is_admin']) {
        $paymentId = (int)($_POST['payment_id'] ?? 0);
        
        // Get payment info
        $stmt = $pdo->prepare("SELECT p.*, u.email, u.name, e.name as event_name FROM payments p 
                               JOIN users u ON p.user_id = u.id 
                               JOIN events e ON p.event_id = e.id 
                               WHERE p.id = ?");
        $stmt->execute([$paymentId]);
        $payment = $stmt->fetch();
        
        if ($payment) {
            $stmt = $pdo->prepare("UPDATE payments SET status = 'approved' WHERE id = ?");
            $stmt->execute([$paymentId]);
            
            // Create or update quota
            $stmt = $pdo->prepare("SELECT id FROM quotas WHERE event_id = ? AND user_id = ?");
            $stmt->execute([$payment['event_id'], $payment['user_id']]);
            
            if (!$stmt->fetch()) {
                // Calculate games allowed based on payment
                $stmt2 = $pdo->prepare("SELECT game_price FROM events WHERE id = ?");
                $stmt2->execute([$payment['event_id']]);
                $event = $stmt2->fetch();
                $gamesAllowed = floor($payment['amount'] / $event['game_price']);
                
                $stmt = $pdo->prepare("INSERT INTO quotas (event_id, user_id, games_allowed, amount_paid) VALUES (?, ?, ?, ?)");
                $stmt->execute([$payment['event_id'], $payment['user_id'], $gamesAllowed, $payment['amount']]);
            }
            
            // Send email notification
            sendPaymentApprovedEmail($payment['email'], $payment['name'], $payment['event_name']);
            
            setFlash('success', 'Pagamento aprovado com sucesso!');
        }
        redirect('?action=manage_payments');
    }
    
    // ADMIN: REJECT PAYMENT
    if ($action === 'reject_payment' && $user && $user['is_admin']) {
        $paymentId = (int)($_POST['payment_id'] ?? 0);
        $notes = trim($_POST['notes'] ?? '');
        
        $stmt = $pdo->prepare("UPDATE payments SET status = 'rejected', admin_notes = ? WHERE id = ?");
        $stmt->execute([$notes, $paymentId]);
        
        setFlash('success', 'Pagamento rejeitado.');
        redirect('?action=manage_payments');
    }
    
    // ADMIN: SET PRIZE
    if ($action === 'set_prize' && $user && $user['is_admin']) {
        $eventId = (int)($_POST['event_id'] ?? 0);
        $prizeGross = (float)($_POST['prize_gross'] ?? 0);
        
        if ($eventId && $prizeGross > 0) {
            // Calculate tax (13.8% for prizes above R$1903.98)
            $tax = 0;
            if ($prizeGross > TAX_EXEMPT_LIMIT) {
                $tax = $prizeGross * TAX_RATE;
            }
            $prizeNet = $prizeGross - $tax;
            
            $stmt = $pdo->prepare("UPDATE events SET prize_gross = ?, prize_net = ?, prize_tax = ? WHERE id = ?");
            $stmt->execute([$prizeGross, $prizeNet, $tax, $eventId]);
            
            setFlash('success', 'Prêmio cadastrado! Bruto: ' . formatMoney($prizeGross) . ' | Imposto: ' . formatMoney($tax) . ' | Líquido: ' . formatMoney($prizeNet));
        } else {
            setFlash('error', 'Valor de prêmio inválido.');
        }
        redirect("?action=manage_event&id=$eventId");
    }
}

// LOGOUT
if ($action === 'logout') {
    session_destroy();
    redirect('?');
}
