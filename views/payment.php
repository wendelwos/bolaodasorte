<?php
/**
 * Bolão da Sorte v3.0 - Payment View
 * PIX payment with receipt upload
 */

function viewPayment($pdo, $user, $eventId) {
    // Get event
    $stmt = $pdo->prepare("SELECT * FROM events WHERE id = ?");
    $stmt->execute([$eventId]);
    $event = $stmt->fetch();
    
    if (!$event) {
        redirect('?action=dashboard');
        return;
    }
    
    $config = getGameConfig($event['game_type']);
    
    // Check if contest has been drawn
    $winningNumbers = null;
    if ($event['contest_number']) {
        $result = getLotteryResult($event['game_type'], $event['contest_number']);
        if ($result && !empty($result['dezenas'])) {
            $winningNumbers = $result['dezenas'];
        }
    }
    $isDrawn = !empty($winningNumbers);
    
    // Get user's quota/participation request
    $stmt = $pdo->prepare("SELECT * FROM quotas WHERE event_id = ? AND user_id = ?");
    $stmt->execute([$eventId, $user['id']]);
    $quota = $stmt->fetch();
    
    // Get all payments for this event/user
    $stmt = $pdo->prepare("SELECT * FROM payments WHERE event_id = ? AND user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$eventId, $user['id']]);
    $allPayments = $stmt->fetchAll();
    $payment = !empty($allPayments) ? $allPayments[0] : null; // Latest for status check
    
    // Calculate amounts
    $totalAmount = $quota ? $quota['games_allowed'] * $event['game_price'] : $event['game_price'];
    
    // Get total already paid (sum of approved payments)
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) as paid FROM payments WHERE event_id = ? AND user_id = ? AND status = 'approved'");
    $stmt->execute([$eventId, $user['id']]);
    $paidAmount = (float)$stmt->fetch()['paid'];
    
    // Get total pending (sum of pending payments)
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) as pending FROM payments WHERE event_id = ? AND user_id = ? AND status = 'pending'");
    $stmt->execute([$eventId, $user['id']]);
    $pendingAmount = (float)$stmt->fetch()['pending'];
    
    // Remaining to pay (total - approved - pending)
    $amount = max(0, $totalAmount - $paidAmount - $pendingAmount);
    ?>
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-bold">💳 Pagamento PIX</h2>
            <a href="?action=view_event&id=<?= $eventId ?>" class="text-sm text-gray-500 hover:underline">Voltar</a>
        </div>
        
        <!-- Event Info with Payment Breakdown -->
        <div class="card p-4">
            <div class="flex justify-between items-start mb-4">
                <div>
                    <span class="inline-block px-2 py-0.5 rounded text-xs text-white <?= $config['color'] ?> mb-1"><?= $config['name'] ?></span>
                    <h3 class="text-lg font-bold"><?= h($event['name']) ?></h3>
                </div>
                <div class="text-right">
                    <?php if ($amount > 0): ?>
                        <p class="text-sm text-gray-500">A Pagar</p>
                        <p class="text-2xl font-bold text-yellow-600"><?= formatMoney($amount) ?></p>
                    <?php else: ?>
                        <p class="text-sm text-gray-500">Status</p>
                        <p class="text-xl font-bold text-green-600">✅ Quitado</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Detailed Breakdown -->
            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-3 space-y-2 text-sm">
                <div class="flex justify-between">
                    <span class="text-gray-600 dark:text-gray-300">Total de jogos:</span>
                    <span class="font-bold"><?= $quota ? $quota['games_allowed'] : 1 ?> jogos</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600 dark:text-gray-300">Valor por jogo:</span>
                    <span class="font-bold"><?= formatMoney($event['game_price']) ?></span>
                </div>
                <div class="flex justify-between border-t pt-2">
                    <span class="text-gray-600 dark:text-gray-300">Valor total:</span>
                    <span class="font-bold"><?= formatMoney($totalAmount) ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600 dark:text-gray-300">Já pago:</span>
                    <span class="font-bold text-green-600"><?= formatMoney($paidAmount) ?> ✅</span>
                </div>
                <?php if ($pendingAmount > 0): ?>
                <div class="flex justify-between">
                    <span class="text-gray-600 dark:text-gray-300">Em análise:</span>
                    <span class="font-bold text-yellow-600"><?= formatMoney($pendingAmount) ?> ⏳</span>
                </div>
                <?php endif; ?>
                <div class="flex justify-between border-t pt-2 text-base">
                    <span class="font-bold">Falta enviar:</span>
                    <span class="font-bold <?= $amount > 0 ? 'text-red-600' : 'text-green-600' ?>"><?= formatMoney($amount) ?></span>
                </div>
            </div>
        </div>
        
        <?php if ($paidAmount + $pendingAmount >= $totalAmount && $amount <= 0): ?>
        <!-- Fully Covered (paid + pending) -->
        <div class="card p-6 text-center bg-green-50 dark:bg-green-900/20 border-2 border-green-500">
            <p class="text-4xl mb-2"><?= $paidAmount >= $totalAmount ? '✅' : '⏳' ?></p>
            <h3 class="text-xl font-bold text-green-700 dark:text-green-400">
                <?= $paidAmount >= $totalAmount ? 'Pagamento Quitado!' : 'Pagamentos Enviados!' ?>
            </h3>
            <p class="text-sm text-gray-600 dark:text-gray-300 mt-2">
                <?= $paidAmount >= $totalAmount ? 'Todos os pagamentos foram confirmados.' : 'Aguarde a confirmação do administrador.' ?>
            </p>
            <a href="?action=view_event&id=<?= $eventId ?>" class="btn-primary inline-block mt-4">🎲 Fazer Apostas</a>
        </div>
        <?php endif; ?>
        
        <!-- List of All Payments -->
        <?php if (!empty($allPayments)): ?>
        <div class="card overflow-hidden">
            <h3 class="font-bold p-4 border-b bg-gray-50 dark:bg-gray-700">📋 Seus Pagamentos</h3>
            <div class="divide-y">
                <?php foreach ($allPayments as $p): ?>
                <div class="p-3 flex justify-between items-center">
                    <div>
                        <span class="font-bold"><?= formatMoney($p['amount']) ?></span>
                        <span class="text-xs text-gray-500 ml-2"><?= date('d/m/Y H:i', strtotime($p['created_at'])) ?></span>
                    </div>
                    <div>
                        <?php if ($p['status'] === 'approved'): ?>
                            <span class="bg-green-100 text-green-700 px-2 py-0.5 rounded-full text-xs">✅ Aprovado</span>
                        <?php elseif ($p['status'] === 'pending'): ?>
                            <span class="bg-yellow-100 text-yellow-700 px-2 py-0.5 rounded-full text-xs">⏳ Em análise</span>
                        <?php else: ?>
                            <span class="bg-red-100 text-red-700 px-2 py-0.5 rounded-full text-xs">❌ Rejeitado</span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if ($payment && $payment['status'] === 'rejected'): ?>
        <!-- Payment Rejected Notice -->
        <div class="card p-4 bg-red-50 dark:bg-red-900/20 border-2 border-red-500">
            <div class="flex items-start gap-3">
                <p class="text-2xl">❌</p>
                <div>
                    <h3 class="font-bold text-red-700 dark:text-red-400">Pagamento Rejeitado</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-300"><?= h($payment['admin_notes'] ?: 'Seu último comprovante foi rejeitado. Envie um novo.') ?></p>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if ($isDrawn): ?>
        <!-- Contest Already Drawn - Block Payments -->
        <div class="card p-6 text-center bg-orange-50 dark:bg-orange-900/20 border-2 border-orange-400">
            <p class="text-4xl mb-2">🎰</p>
            <h3 class="text-xl font-bold text-orange-700 dark:text-orange-400">Concurso Já Sorteado</h3>
            <p class="text-sm text-gray-600 dark:text-gray-300 mt-2">Não é possível realizar pagamentos após o resultado ser divulgado.</p>
            <a href="?action=view_event&id=<?= $eventId ?>" class="btn-primary inline-block mt-4">🎲 Ver Resultado</a>
        </div>
        
        <?php elseif ($amount > 0): // Show PIX info if there's any remaining amount ?>
        <!-- PIX Info -->
        <div class="card p-6">
            <h3 class="font-bold text-lg mb-4">📱 Dados para Pagamento</h3>
            <div class="bg-gray-100 dark:bg-gray-700 p-4 rounded-lg space-y-3">
                <div>
                    <p class="text-sm text-gray-500">Chave PIX (<?= PIX_KEY_TYPE ?>)</p>
                    <p class="text-xl font-mono font-bold text-theme-green flex items-center gap-2">
                        <?= PIX_KEY ?>
                        <button onclick="copyPix()" class="text-sm bg-gray-200 dark:bg-gray-600 px-2 py-1 rounded hover:bg-gray-300">📋 Copiar</button>
                    </p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Titular</p>
                    <p class="font-bold"><?= PIX_HOLDER ?></p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Valor</p>
                    <p class="font-bold text-lg"><?= formatMoney($amount) ?></p>
                </div>
            </div>
        </div>
        
        <!-- Upload Receipt (Optional) -->
        <div class="card p-6">
            <h3 class="font-bold text-lg mb-2">📤 Enviar Pagamento</h3>
            <p class="text-sm text-gray-500 mb-4">Você também pode enviar o comprovante por WhatsApp ou outro meio ao administrador.</p>
            <form method="POST" action="?action=upload_receipt" enctype="multipart/form-data" class="space-y-4">
                <?= csrfField() ?>
                <input type="hidden" name="event_id" value="<?= $eventId ?>">
                <input type="hidden" name="amount" value="<?= $amount ?>">
                
                <div>
                    <label class="label">Comprovante PIX (imagem)</label>
                    <input type="file" name="receipt" accept="image/*" 
                           class="w-full p-3 border rounded-lg bg-gray-50 dark:bg-gray-700">
                    <p class="text-xs text-gray-500 mt-1">Formatos aceitos: JPG, PNG, GIF (máx. 5MB)</p>
                </div>
                
                <button type="submit" class="btn-primary w-full py-3">📤 Enviar Pagamento</button>
            </form>
        </div>
        <?php endif; ?>
    </div>
    
    <script>
    function copyPix() {
        navigator.clipboard.writeText('<?= PIX_KEY ?>').then(() => {
            showToast('Chave PIX copiada!', 'success');
        });
    }
    </script>
    <?php
}

// My Bets History
function viewMyBets($pdo, $user) {
    // Get all user's bets with event info
    $stmt = $pdo->prepare("
        SELECT b.*, e.name as event_name, e.game_type, e.status as event_status, 
               e.draw_number, e.prize_gross, e.prize_net
        FROM bets b
        JOIN events e ON b.event_id = e.id
        WHERE b.user_id = ?
        ORDER BY b.created_at DESC
    ");
    $stmt->execute([$user['id']]);
    $bets = $stmt->fetchAll();
    
    // Group by event and fetch results
    $betsByEvent = [];
    $resultsByEvent = [];
    foreach ($bets as $bet) {
        $betsByEvent[$bet['event_id']][] = $bet;
        if (!isset($resultsByEvent[$bet['event_id']]) && $bet['draw_number']) {
            $apiResult = getLotteryResult($bet['game_type'], $bet['draw_number']);
            if ($apiResult && isset($apiResult['dezenas'])) {
                $resultsByEvent[$bet['event_id']] = [
                    'numbers' => array_map('intval', $apiResult['dezenas']),
                    'date' => $apiResult['data'] ?? null
                ];
            }
        }
    }
    ?>
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-bold">📊 Meu Histórico de Apostas</h2>
            <a href="?action=dashboard" class="text-sm text-gray-500 hover:underline">Voltar</a>
        </div>
        
        <?php if (empty($bets)): ?>
        <div class="card p-8 text-center">
            <p class="text-4xl mb-2">📭</p>
            <p class="text-gray-500">Você ainda não fez nenhuma aposta.</p>
            <a href="?action=public_events" class="btn-primary inline-block mt-4">Ver Bolões Abertos</a>
        </div>
        <?php else: ?>
        
        <!-- Summary -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="card p-4 text-center">
                <p class="text-2xl font-bold text-theme-green"><?= count($bets) ?></p>
                <p class="text-sm text-gray-500">Total de Jogos</p>
            </div>
            <div class="card p-4 text-center">
                <p class="text-2xl font-bold text-purple-600"><?= count($betsByEvent) ?></p>
                <p class="text-sm text-gray-500">Bolões</p>
            </div>
        </div>
        
        <!-- Bets by Event -->
        <?php foreach ($betsByEvent as $eventId => $eventBets): 
            $firstBet = $eventBets[0];
            $config = getGameConfig($firstBet['game_type']);
            $winningNumbers = $resultsByEvent[$eventId]['numbers'] ?? [];
            $resultDate = $resultsByEvent[$eventId]['date'] ?? null;
        ?>
        <div class="card overflow-hidden">
            <div class="p-4 bg-gray-50 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-600 cursor-pointer" onclick="toggleEventDetails(<?= $eventId ?>)">
                <div class="flex justify-between items-center">
                    <div>
                        <span class="inline-block px-2 py-0.5 rounded text-xs text-white <?= $config['color'] ?> mb-1"><?= $config['name'] ?></span>
                        <h3 class="font-bold"><?= h($firstBet['event_name']) ?></h3>
                        <?php if ($firstBet['draw_number']): ?>
                        <p class="text-xs text-gray-500">🎫 Concurso: <b><?= $firstBet['draw_number'] ?></b></p>
                        <?php endif; ?>
                    </div>
                    <div class="text-right">
                        <span class="text-sm <?= $firstBet['event_status'] === 'finished' ? 'text-gray-500' : 'text-green-600' ?>">
                            <?= $firstBet['event_status'] === 'finished' ? 'Finalizado' : ($firstBet['event_status'] === 'closed' ? 'Fechado' : 'Aberto') ?>
                        </span>
                        <p class="text-sm text-gray-500"><?= count($eventBets) ?> jogos</p>
                        <span class="text-xs text-blue-500">Clique para detalhes ▼</span>
                    </div>
                </div>
            </div>
            
            <!-- Hidden Details -->
            <div id="event-details-<?= $eventId ?>" class="hidden">
                <?php if ($winningNumbers): ?>
                <!-- Result Section -->
                <div class="p-4 bg-green-50 dark:bg-green-900/20 border-b border-gray-200 dark:border-gray-600">
                    <h4 class="font-bold text-green-700 dark:text-green-400 mb-2">🏆 Resultado Oficial</h4>
                    <div class="flex flex-wrap gap-2 mb-2">
                        <?php foreach ($winningNumbers as $n): ?>
                        <span class="w-10 h-10 flex items-center justify-center font-bold rounded-full bg-green-600 text-white shadow">
                            <?= str_pad($n, 2, '0', STR_PAD_LEFT) ?>
                        </span>
                        <?php endforeach; ?>
                    </div>
                    <?php if ($resultDate): ?>
                    <p class="text-xs text-gray-500">Data: <?= $resultDate ?></p>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
                <!-- Bets List -->
                <div class="p-4 space-y-3 max-h-80 overflow-y-auto">
                    <?php foreach ($eventBets as $index => $bet): 
                        $betNumbers = explode(',', $bet['numbers']);
                        $matches = $winningNumbers ? array_intersect($betNumbers, $winningNumbers) : [];
                        $hits = count($matches);
                    ?>
                    <div class="p-3 bg-gray-50 dark:bg-gray-700 rounded-lg <?= $hits >= 4 ? 'border-2 border-amber-400' : '' ?>">
                        <div class="flex justify-between items-center mb-2">
                            <span class="text-xs font-bold">Jogo #<?= $index + 1 ?></span>
                            <div class="flex items-center gap-2">
                                <?php if ($winningNumbers): ?>
                                <span class="text-xs px-2 py-0.5 rounded-full <?= $hits >= 4 ? 'bg-amber-500 text-white' : 'bg-gray-200 dark:bg-gray-600' ?>">
                                    <?= $hits ?> acerto<?= $hits != 1 ? 's' : '' ?>
                                </span>
                                <?php endif; ?>
                                <?php if ($bet['type'] === 'auto'): ?>
                                <span class="text-xs text-purple-500">🎰 Auto</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="flex flex-wrap gap-1.5">
                            <?php foreach ($betNumbers as $num): 
                                $isMatch = in_array((int)$num, $winningNumbers);
                            ?>
                            <span class="w-9 h-9 flex items-center justify-center text-sm font-bold rounded-full shadow <?= $isMatch ? 'bg-green-600 text-white ring-2 ring-green-300' : 'bg-theme-yellow text-green-900' ?>">
                                <?= str_pad($num, 2, '0', STR_PAD_LEFT) ?>
                            </span>
                            <?php endforeach; ?>
                        </div>
                        <p class="text-xs text-gray-400 mt-2"><?= date('d/m/Y H:i', strtotime($bet['created_at'])) ?></p>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        
        <?php endif; ?>
    </div>
    
    <script>
    function toggleEventDetails(eventId) {
        const details = document.getElementById('event-details-' + eventId);
        details.classList.toggle('hidden');
    }
    </script>
    <?php
}

// Admin: Manage Payments
function viewManagePayments($pdo) {
    $statusFilter = $_GET['status'] ?? 'pending';
    
    $sql = "SELECT p.*, u.name as user_name, u.email as user_email, e.name as event_name, e.game_type
            FROM payments p
            JOIN users u ON p.user_id = u.id
            JOIN events e ON p.event_id = e.id";
    
    if ($statusFilter !== 'all') {
        $sql .= " WHERE p.status = :status";
    }
    $sql .= " ORDER BY p.created_at DESC";
    
    $stmt = $pdo->prepare($sql);
    if ($statusFilter !== 'all') {
        $stmt->execute(['status' => $statusFilter]);
    } else {
        $stmt->execute();
    }
    $payments = $stmt->fetchAll();
    ?>
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-bold">💳 Gestão de Pagamentos</h2>
            <a href="?action=dashboard" class="text-sm text-gray-500 hover:underline">Voltar</a>
        </div>
        
        <!-- Filter -->
        <div class="flex gap-2 flex-wrap">
            <a href="?action=manage_payments&status=pending" class="px-4 py-2 rounded-lg text-sm font-medium <?= $statusFilter === 'pending' ? 'bg-yellow-500 text-white' : 'bg-gray-200 dark:bg-gray-700' ?>">
                ⏳ Pendentes
            </a>
            <a href="?action=manage_payments&status=approved" class="px-4 py-2 rounded-lg text-sm font-medium <?= $statusFilter === 'approved' ? 'bg-green-500 text-white' : 'bg-gray-200 dark:bg-gray-700' ?>">
                ✅ Aprovados
            </a>
            <a href="?action=manage_payments&status=rejected" class="px-4 py-2 rounded-lg text-sm font-medium <?= $statusFilter === 'rejected' ? 'bg-red-500 text-white' : 'bg-gray-200 dark:bg-gray-700' ?>">
                ❌ Rejeitados
            </a>
            <a href="?action=manage_payments&status=all" class="px-4 py-2 rounded-lg text-sm font-medium <?= $statusFilter === 'all' ? 'bg-blue-500 text-white' : 'bg-gray-200 dark:bg-gray-700' ?>">
                📋 Todos
            </a>
        </div>
        
        <?php if (empty($payments)): ?>
        <div class="card p-8 text-center">
            <p class="text-4xl mb-2">📭</p>
            <p class="text-gray-500">Nenhum pagamento encontrado.</p>
        </div>
        <?php else: ?>
        <div class="space-y-4">
            <?php foreach ($payments as $p): 
                $config = getGameConfig($p['game_type']);
            ?>
            <div class="card p-4">
                <div class="flex justify-between items-start mb-3">
                    <div>
                        <p class="font-bold"><?= h($p['user_name']) ?></p>
                        <p class="text-xs text-gray-500"><?= h($p['user_email']) ?></p>
                    </div>
                    <div class="text-right">
                        <p class="text-lg font-bold text-theme-green"><?= formatMoney($p['amount']) ?></p>
                        <span class="text-xs px-2 py-0.5 rounded <?= $config['color'] ?> text-white"><?= h($p['event_name']) ?></span>
                    </div>
                </div>
                
                <?php if ($p['receipt_path']): ?>
                <div class="mb-3">
                    <a href="/bolaodasorte/uploads/receipts/<?= basename($p['receipt_path']) ?>" target="_blank">
                        <img src="/bolaodasorte/uploads/receipts/<?= basename($p['receipt_path']) ?>" alt="Comprovante" class="max-h-40 rounded-lg border">
                    </a>
                </div>
                <?php endif; ?>
                
                <div class="flex justify-between items-center pt-3 border-t border-gray-200 dark:border-gray-600">
                    <span class="text-xs text-gray-500"><?= date('d/m/Y H:i', strtotime($p['created_at'])) ?></span>
                    
                    <?php if ($p['status'] === 'pending'): ?>
                    <div class="flex gap-2">
                        <form method="POST" action="?action=approve_payment" class="inline">
                            <?= csrfField() ?>
                            <input type="hidden" name="payment_id" value="<?= $p['id'] ?>">
                            <button type="submit" class="px-3 py-1 bg-green-500 hover:bg-green-600 text-white rounded text-sm">✓ Aprovar</button>
                        </form>
                        <button onclick="openRejectModal(<?= $p['id'] ?>)" class="px-3 py-1 bg-red-500 hover:bg-red-600 text-white rounded text-sm">✗ Rejeitar</button>
                    </div>
                    <?php else: ?>
                    <span class="px-3 py-1 rounded text-sm <?= $p['status'] === 'approved' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                        <?= $p['status'] === 'approved' ? '✅ Aprovado' : '❌ Rejeitado' ?>
                    </span>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Reject Modal -->
    <div id="rejectModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50" onclick="if(event.target===this)closeRejectModal()">
        <div class="card p-6 w-full max-w-md mx-4">
            <h3 class="text-lg font-bold mb-4">Rejeitar Pagamento</h3>
            <form method="POST" action="?action=reject_payment">
                <?= csrfField() ?>
                <input type="hidden" name="payment_id" id="rejectPaymentId">
                <div class="mb-4">
                    <label class="label">Motivo (opcional)</label>
                    <textarea name="notes" class="input-std" rows="3" placeholder="Ex: Valor incorreto, comprovante ilegível..."></textarea>
                </div>
                <div class="flex gap-2">
                    <button type="button" onclick="closeRejectModal()" class="btn-secondary flex-1">Cancelar</button>
                    <button type="submit" class="flex-1 px-4 py-2 bg-red-500 hover:bg-red-600 text-white rounded">Rejeitar</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
    function openRejectModal(id) {
        document.getElementById('rejectPaymentId').value = id;
        document.getElementById('rejectModal').classList.remove('hidden');
        document.getElementById('rejectModal').classList.add('flex');
    }
    function closeRejectModal() {
        document.getElementById('rejectModal').classList.add('hidden');
        document.getElementById('rejectModal').classList.remove('flex');
    }
    </script>
    <?php
}
