<?php
/**
 * Bolão da Sorte v2.0 - Event Views
 */

// MANAGE EVENT (Admin)
function viewManageEvent($pdo, $id) {
    $event = $pdo->prepare("SELECT * FROM events WHERE id = ?");
    $event->execute([$id]);
    $event = $event->fetch();
    if (!$event) { echo "<p>Evento não encontrado.</p>"; return; }
    
    $users = $pdo->query("SELECT * FROM users WHERE status = 'approved' ORDER BY name")->fetchAll();
    $quotas = $pdo->query("SELECT q.*, u.name FROM quotas q JOIN users u ON q.user_id = u.id WHERE q.event_id=$id")->fetchAll();
    
    $totalPaid = 0; $totalGames = 0;
    foreach ($quotas as $q) { $totalPaid += $q['amount_paid']; $totalGames += $q['games_allowed']; }
    $gamesMade = $pdo->query("SELECT COUNT(*) FROM bets WHERE event_id=$id")->fetchColumn();
    $moneyUsed = $gamesMade * $event['game_price'];
    $moneyRemaining = $totalPaid - $moneyUsed;
    ?>
    <div class="space-y-6 pb-20">
        <div class="flex items-center justify-between mb-2">
            <h2 class="text-xl font-bold truncate">Gerir: <?= h($event['name']) ?></h2>
            <a href="?action=dashboard" class="text-blue-600 text-sm">Voltar</a>
        </div>
        
        <!-- Stats -->
        <div class="grid grid-cols-3 gap-3 text-sm">
            <div class="bg-green-50 dark:bg-green-900/30 p-3 rounded-lg border border-green-200 dark:border-green-800">
                <div class="text-gray-500">Arrecadado</div>
                <div class="text-xl font-bold text-green-700"><?= formatMoney($totalPaid) ?></div>
            </div>
            <div class="bg-blue-50 dark:bg-blue-900/30 p-3 rounded-lg border border-blue-200 dark:border-blue-800">
                <div class="text-gray-500">Restante</div>
                <div class="text-xl font-bold text-blue-700"><?= formatMoney($moneyRemaining) ?></div>
            </div>
            <div class="bg-gray-50 dark:bg-gray-700 p-3 rounded-lg border border-gray-200 dark:border-gray-600">
                <div class="text-gray-500">Jogos</div>
                <div class="text-xl font-bold text-gray-700 dark:text-gray-300"><?= $gamesMade ?></div>
            </div>
        </div>
        
        <!-- Edit Event -->
        <div class="card p-4">
            <h3 class="font-bold mb-3">Editar Bolão</h3>
            <form method="POST" action="?action=update_event" class="space-y-3">
                <?= csrfField() ?>
                <input type="hidden" name="event_id" value="<?= $id ?>">
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="text-xs text-gray-500">Nome</label>
                        <input type="text" name="name" value="<?= h($event['name']) ?>" class="input-std">
                    </div>
                    <div>
                        <label class="text-xs text-gray-500">Status</label>
                        <select name="status" class="input-std">
                            <option value="open" <?= $event['status']==='open'?'selected':'' ?>>Aberto</option>
                            <option value="closed" <?= $event['status']==='closed'?'selected':'' ?>>Fechado</option>
                            <option value="finished" <?= $event['status']==='finished'?'selected':'' ?>>Finalizado</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="text-xs text-gray-500">Nº Concurso</label>
                    <input type="number" name="draw_number" value="<?= $event['draw_number'] ?>" class="input-std">
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="btn-primary flex-1">Salvar</button>
                    <form method="POST" action="?action=delete_event" onsubmit="return confirm('Excluir este bolão e todas as apostas?')" class="inline">
                        <?= csrfField() ?>
                        <input type="hidden" name="event_id" value="<?= $id ?>">
                        <button type="submit" class="btn-danger px-4">🗑️ Excluir</button>
                    </form>
                </div>
            </form>
        </div>
        
        <!-- Auto Complete -->
        <?php if ($moneyRemaining >= $event['game_price']): ?>
        <div class="bg-gradient-to-r from-theme-yellow to-amber-400 p-4 rounded-xl shadow text-center">
            <p class="text-green-900 font-bold mb-2">Dinheiro sobrando para <?= floor($moneyRemaining / $event['game_price']) ?> jogos!</p>
            <form method="POST" action="?action=autocomplete">
                <?= csrfField() ?>
                <input type="hidden" name="event_id" value="<?= $id ?>">
                <button type="submit" class="bg-green-800 text-white px-6 py-2 rounded-full font-bold shadow hover:bg-green-900 w-full">🎲 Completar com Jogos Aleatórios</button>
            </form>
        </div>
        <?php endif; ?>
        
        <!-- Add Quota -->
        <div class="card p-4">
            <h3 class="font-bold mb-3">Adicionar/Editar Cota</h3>
            <form method="POST" action="?action=add_quota" class="space-y-3">
                <?= csrfField() ?>
                <input type="hidden" name="event_id" value="<?= $id ?>">
                <div>
                    <label class="text-xs text-gray-500">Usuário</label>
                    <select name="user_id" class="input-std">
                        <?php foreach ($users as $u): ?>
                            <option value="<?= $u['id'] ?>"><?= h($u['name']) ?> (<?= h($u['email']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="text-xs text-gray-500">Jogos Permitidos</label>
                        <input type="number" name="games_allowed" class="input-std" value="1" min="1">
                    </div>
                    <div>
                        <label class="text-xs text-gray-500">Valor Pago (R$)</label>
                        <input type="number" step="0.01" name="amount_paid" class="input-std" value="<?= number_format($event['game_price'], 2, '.', '') ?>">
                    </div>
                </div>
                <button class="btn-primary w-full">Salvar Cota</button>
            </form>
        </div>
        
        <!-- Quotas List -->
        <div>
            <h3 class="font-bold mb-2">📊 Participantes e Pagamentos</h3>
            <div class="card overflow-hidden">
                <table class="w-full text-xs sm:text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="p-2 text-left">Nome</th>
                            <th class="p-2 text-center">Apostas</th>
                            <th class="p-2 text-center">Investido</th>
                            <th class="p-2 text-center">Cota</th>
                            <th class="p-2 text-center">Status</th>
                            <th class="p-2 text-center">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <?php foreach ($quotas as $q):
                            $userBets = $pdo->query("SELECT COUNT(*) FROM bets WHERE event_id=$id AND user_id=" . $q['user_id'])->fetchColumn();
                            $investedAmount = $userBets * $event['game_price'];
                            
                            // Get payment status
                            $paymentStmt = $pdo->prepare("SELECT status FROM payments WHERE event_id = ? AND user_id = ? ORDER BY created_at DESC LIMIT 1");
                            $paymentStmt->execute([$id, $q['user_id']]);
                            $paymentRecord = $paymentStmt->fetch();
                            $paymentStatus = $paymentRecord ? $paymentRecord['status'] : 'pending';
                        ?>
                        <tr>
                            <td class="p-2 font-medium"><?= h($q['name']) ?></td>
                            <td class="p-2 text-center">
                                <span class="<?= $userBets >= $q['games_allowed'] ? 'text-green-600 font-bold' : 'text-orange-500' ?>">
                                    <?= $userBets ?> / <?= $q['games_allowed'] ?>
                                </span>
                            </td>
                            <td class="p-2 text-center font-bold text-blue-600"><?= formatMoney($investedAmount) ?></td>
                            <td class="p-2 text-center"><?= formatMoney($q['amount_paid']) ?></td>
                            <td class="p-2 text-center">
                                <?php if ($paymentStatus === 'approved'): ?>
                                    <span class="bg-green-100 text-green-700 px-2 py-0.5 rounded-full text-xs">✅ Pago</span>
                                <?php elseif ($paymentStatus === 'pending'): ?>
                                    <span class="bg-yellow-100 text-yellow-700 px-2 py-0.5 rounded-full text-xs">⏳ Pendente</span>
                                <?php else: ?>
                                    <span class="bg-red-100 text-red-700 px-2 py-0.5 rounded-full text-xs">❌ Rejeitado</span>
                                <?php endif; ?>
                            </td>
                            <td class="p-2 text-center">
                                <form method="POST" action="?action=add_quota" class="inline">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="event_id" value="<?= $id ?>">
                                    <input type="hidden" name="user_id" value="<?= $q['user_id'] ?>">
                                    <input type="hidden" name="games_allowed" value="<?= $q['games_allowed'] + 1 ?>">
                                    <input type="hidden" name="amount_paid" value="<?= $q['amount_paid'] + $event['game_price'] ?>">
                                    <button type="submit" class="text-blue-600 hover:underline text-xs" title="Adicionar 1 jogo">➕</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot class="bg-gray-100 dark:bg-gray-600 font-bold">
                        <tr>
                            <td class="p-2">TOTAL</td>
                            <td class="p-2 text-center"><?= $gamesMade ?> jogos</td>
                            <td class="p-2 text-center text-blue-600"><?= formatMoney($moneyUsed) ?></td>
                            <td class="p-2 text-center"><?= formatMoney($totalPaid) ?></td>
                            <td class="p-2 text-center" colspan="2">
                                <span class="<?= $moneyRemaining >= 0 ? 'text-green-600' : 'text-red-600' ?>">
                                    Saldo: <?= formatMoney($moneyRemaining) ?>
                                </span>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
    <?php
}
