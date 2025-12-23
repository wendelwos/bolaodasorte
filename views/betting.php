<?php
/**
 * Bolão da Sorte v2.0 - Betting View
 */

function viewEvent($pdo, $user, $id) {
    $stmt = $pdo->prepare("SELECT * FROM events WHERE id = ?");
    $stmt->execute([$id]);
    $event = $stmt->fetch();
    if (!$event) { echo "<p>Evento não encontrado.</p>"; return; }
    
    $config = getGameConfig($event['game_type']);
    $apiResult = getLotteryResult($event['game_type'], $event['draw_number']);
    $winningNumbers = [];
    if ($apiResult && isset($apiResult['dezenas'])) {
        $winningNumbers = array_map('intval', $apiResult['dezenas']);
    }
    
    $stmt = $pdo->prepare("SELECT * FROM quotas WHERE event_id = ? AND user_id = ?");
    $stmt->execute([$id, $user['id']]);
    $quota = $stmt->fetch();
    
    $stmt = $pdo->prepare("SELECT * FROM bets WHERE event_id = ? AND user_id = ?");
    $stmt->execute([$id, $user['id']]);
    $myBets = $stmt->fetchAll();
    
    // Check payment status and calculate remaining amount
    $paymentStatus = null;
    $remainingAmount = 0;
    if ($quota) {
        $stmt = $pdo->prepare("SELECT * FROM payments WHERE event_id = ? AND user_id = ? ORDER BY created_at DESC LIMIT 1");
        $stmt->execute([$id, $user['id']]);
        $payment = $stmt->fetch();
        $paymentStatus = $payment ? $payment['status'] : 'pending';
        
        // Calculate remaining amount (total - approved - pending)
        $totalAmount = $quota['games_allowed'] * $event['game_price'];
        
        $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) as paid FROM payments WHERE event_id = ? AND user_id = ? AND status = 'approved'");
        $stmt->execute([$id, $user['id']]);
        $paidAmount = (float)$stmt->fetch()['paid'];
        
        $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) as pending FROM payments WHERE event_id = ? AND user_id = ? AND status = 'pending'");
        $stmt->execute([$id, $user['id']]);
        $pendingAmount = (float)$stmt->fetch()['pending'];
        
        $remainingAmount = max(0, $totalAmount - $paidAmount - $pendingAmount);
    }
    ?>
    <div class="space-y-6 pb-20">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-bold truncate"><?= h($event['name']) ?></h2>
            <a href="?action=dashboard" class="text-blue-600 text-sm">Voltar</a>
        </div>
        
        <!-- Info -->
        <div class="card p-4 text-sm">
            <div class="flex justify-between">
                <p>🔴 <b>Jogo:</b> <?= $config['name'] ?></p>
                <?php if ($event['draw_number']): ?>
                    <p>🎫 <b>Concurso:</b> <?= $event['draw_number'] ?></p>
                <?php endif; ?>
            </div>
            <p>💰 <b>Valor:</b> <?= formatMoney($event['game_price']) ?></p>
            
            <?php if ($winningNumbers): ?>
            <div class="mt-4 bg-gray-50 dark:bg-gray-700 border p-3 rounded-lg text-center">
                <h4 class="font-bold mb-2">RESULTADO OFICIAL</h4>
                <div class="flex flex-wrap justify-center gap-2">
                    <?php foreach ($winningNumbers as $n): ?>
                        <div class="w-10 h-10 rounded-full bg-theme-green text-white font-bold flex items-center justify-center shadow">
                            <?= str_pad($n, 2, '0', STR_PAD_LEFT) ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                <p class="text-xs text-gray-400 mt-2">Data: <?= $apiResult['data'] ?? $apiResult['dataSorteio'] ?? 'N/A' ?></p>
            </div>
            <?php elseif ($event['draw_number']): ?>
                <div class="mt-2 text-xs text-gray-400 italic">Aguardando resultado...</div>
            <?php endif; ?>
            
            <!-- Prize Calculator -->
            <?php 
            // Get total games in bolão
            $totalGamesInBolao = (int)$pdo->query("SELECT COUNT(*) FROM bets WHERE event_id = $id")->fetchColumn();
            $userGames = $quota ? (int)$quota['games_allowed'] : 0;
            
            // Get prize info
            $contestNum = $event['contest_number'] ?: $event['draw_number'];
            $prizeValue = 0;
            $isAccumulated = false;
            
            if ($apiResult) {
                // Check if there's a main prize
                if (isset($apiResult['premios']) && is_array($apiResult['premios'])) {
                    foreach ($apiResult['premios'] as $premio) {
                        if (stripos($premio['nome'] ?? '', 'Sena') !== false || 
                            stripos($premio['nome'] ?? '', 'Quina') !== false && $event['game_type'] === 'quina' ||
                            stripos($premio['nome'] ?? '', '15') !== false && $event['game_type'] === 'lotofacil') {
                            $prizeValue = (float)($premio['premio'] ?? 0);
                            break;
                        }
                    }
                }
                // If no winners, use estimated/accumulated value from API
                if ($prizeValue == 0) {
                    $prizeValue = (float)($apiResult['valorEstimadoProximoConcurso'] ?? $apiResult['valorAcumulado'] ?? 0);
                }
                $isAccumulated = !empty($apiResult['acumulado']);
            }
            
            // Fallback to admin's estimated_prize if API has no data
            if ($prizeValue == 0 && !empty($event['estimated_prize'])) {
                $prizeValue = (float)$event['estimated_prize'];
            }
            
            // Calculate user share
            $userShare = 0;
            $userShareNet = 0;
            if ($totalGamesInBolao > 0 && $prizeValue > 0 && $userGames > 0) {
                $userShare = ($prizeValue / $totalGamesInBolao) * $userGames;
                // Apply tax for prizes above R$ 1903.98
                if ($userShare > TAX_EXEMPT_LIMIT) {
                    $userShareNet = $userShare * (1 - TAX_RATE);
                } else {
                    $userShareNet = $userShare;
                }
            }
            ?>
            
            <!-- Prize Calculator - Always show if has games or quota -->
            <?php if ($totalGamesInBolao > 0 || $userGames > 0 || $prizeValue > 0): ?>
            <div class="mt-4 bg-gradient-to-br from-purple-50 to-indigo-50 dark:from-purple-900/20 dark:to-indigo-900/20 p-4 rounded-lg border border-purple-200 dark:border-purple-700">
                <h4 class="font-bold text-purple-800 dark:text-purple-300 mb-3 flex items-center gap-2">
                    🏆 Calculadora de Prêmio
                    <?php if ($isAccumulated): ?>
                        <span class="bg-red-500 text-white text-xs px-2 py-0.5 rounded-full animate-pulse">ACUMULADO!</span>
                    <?php endif; ?>
                </h4>
                
                <!-- Prize Value - PROMINENT DISPLAY -->
                <?php if ($prizeValue > 0): ?>
                <div class="bg-gradient-to-r from-yellow-400 to-amber-500 p-4 rounded-xl mb-4 text-center shadow-lg">
                    <div class="text-xs text-yellow-900 font-medium mb-1">💰 Prêmio <?= $winningNumbers ? 'Atual' : 'Estimado' ?></div>
                    <div class="text-2xl md:text-3xl font-black text-white drop-shadow"><?= formatMoney($prizeValue) ?></div>
                </div>
                <?php else: ?>
                <div class="bg-gray-100 dark:bg-gray-700 p-4 rounded-xl mb-4 text-center">
                    <div class="text-gray-500 text-sm">💰 Prêmio não definido</div>
                    <?php if ($user['is_admin']): ?>
                    <p class="text-xs text-gray-400 mt-1">Configure em <a href="?action=manage_events" class="text-purple-600 underline">📋 Bolões</a> → 🏆 Prêmio Est.</p>
                    <?php else: ?>
                    <p class="text-xs text-gray-400 mt-1">Aguardando informação do administrador</p>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
                <div class="grid grid-cols-2 gap-3 text-sm">
                    <div class="bg-white dark:bg-gray-800 p-3 rounded-lg text-center">
                        <div class="text-2xl font-bold text-purple-600"><?= $totalGamesInBolao ?></div>
                        <div class="text-xs text-gray-500">Jogos no Bolão</div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 p-3 rounded-lg text-center">
                        <div class="text-2xl font-bold text-green-600"><?= $userGames ?></div>
                        <div class="text-xs text-gray-500">Seus Jogos</div>
                    </div>
                </div>
                
                <?php if ($prizeValue > 0 && $userGames > 0 && $totalGamesInBolao > 0): ?>
                <!-- User Share Calculation -->
                <div class="mt-4 bg-gradient-to-r from-green-400 to-emerald-500 p-4 rounded-xl text-center shadow-lg">
                    <div class="text-xs text-green-900 font-medium mb-1">🎯 Sua Parte (<?= round(($userGames / $totalGamesInBolao) * 100, 1) ?>%)</div>
                    <?php if ($userShare > TAX_EXEMPT_LIMIT): ?>
                    <div class="text-lg text-green-100 line-through"><?= formatMoney($userShare) ?></div>
                    <div class="text-2xl md:text-3xl font-black text-white drop-shadow"><?= formatMoney($userShareNet) ?></div>
                    <div class="text-xs text-green-100 mt-1">Líquido após 13,8% IR</div>
                    <?php else: ?>
                    <div class="text-2xl md:text-3xl font-black text-white drop-shadow"><?= formatMoney($userShare) ?></div>
                    <?php endif; ?>
                </div>
                <?php elseif ($prizeValue > 0 && $userGames == 0): ?>
                <div class="mt-3 text-center text-sm text-gray-500 italic">
                    Faça apostas para ver quanto você pode ganhar!
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <?php if ($quota): ?>
                <div class="mt-2 pt-2 border-t flex justify-between items-center">
                    <span class="font-bold">Seus Jogos:</span>
                    <div class="flex items-center gap-2">
                        <span class="bg-theme-green text-white px-3 py-1 rounded-full text-sm"><?= count($myBets) ?> / <?= $quota['games_allowed'] ?></span>
                        <?php if ($remainingAmount > 0 && !$winningNumbers): ?>
                            <a href="?action=payment&event_id=<?= $id ?>" class="bg-yellow-500 hover:bg-yellow-600 text-white px-3 py-1 rounded-full text-xs font-bold">💰 Pagar <?= formatMoney($remainingAmount) ?></a>
                        <?php elseif ($remainingAmount > 0 && $winningNumbers): ?>
                            <span class="bg-gray-400 text-white px-2 py-0.5 rounded-full text-xs">⏹️ Encerrado</span>
                        <?php else: ?>
                            <span class="bg-green-500 text-white px-2 py-0.5 rounded-full text-xs">✅ Pago</span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="mt-4 p-4 bg-yellow-50 dark:bg-yellow-900/20 border-2 border-yellow-500 rounded-lg text-center">
                    <p class="text-yellow-800 dark:text-yellow-200 font-bold mb-2">📋 Solicite Participação</p>
                    <p class="text-sm text-gray-600 dark:text-gray-300 mb-3">Para apostar neste bolão, solicite participação ao administrador.</p>
                    <a href="?action=public_events" class="btn-primary inline-block">Ver Bolões</a>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Warning: Results Already Out -->
        <?php if ($winningNumbers && $event['status'] === 'open'): ?>
        <div class="card p-4 bg-orange-50 dark:bg-orange-900/20 border-2 border-orange-400 text-center">
            <p class="text-2xl mb-2">⚠️</p>
            <p class="font-bold text-orange-700 dark:text-orange-300">Concurso já sorteado!</p>
            <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">Não é possível fazer apostas após o resultado ser divulgado.</p>
        </div>
        <?php endif; ?>
        
        <!-- Betting Interface (only if no results yet) -->
        <?php if ($quota && count($myBets) < $quota['games_allowed'] && $event['status'] === 'open' && !$winningNumbers): ?>
        <div class="card border-2 border-theme-green overflow-hidden">
            <h3 class="font-bold text-theme-green p-4 text-center border-b">Fazer Aposta (<?= count($myBets) + 1 ?>/<?= $quota['games_allowed'] ?>)</h3>
            <form method="POST" action="?action=place_bet" id="betForm">
                <?= csrfField() ?>
                <input type="hidden" name="event_id" value="<?= $id ?>">
                <div class="p-4">
                    <div class="flex flex-wrap gap-2 justify-center">
                        <?php for ($i = $config['min']; $i <= $config['max']; $i++): ?>
                        <label class="cursor-pointer relative">
                            <input type="checkbox" name="numbers[]" value="<?= $i ?>" class="peer sr-only number-check" onchange="checkLimit(this)">
                            <div class="lottery-ball text-gray-600 bg-white hover:bg-gray-100 select-none">
                                <?= str_pad($i, 2, '0', STR_PAD_LEFT) ?>
                            </div>
                        </label>
                        <?php endfor; ?>
                    </div>
                </div>
                <div class="bg-gray-50 dark:bg-gray-700 p-4 border-t space-y-2">
                    <div class="flex gap-2">
                        <button type="button" onclick="feelingLucky()" class="flex-1 bg-gradient-to-r from-theme-yellow to-amber-400 text-green-900 font-bold py-3 rounded-xl shadow-lg">🍀 Estou com Sorte!</button>
                        <button type="button" onclick="clearSelection()" class="px-4 bg-gray-200 dark:bg-gray-600 font-bold py-3 rounded-xl">🗑️</button>
                    </div>
                    <button id="submitBtn" type="submit" disabled class="w-full bg-gray-400 text-white font-bold py-4 rounded-xl disabled:opacity-50">Escolha <?= $config['draw'] ?> números</button>
                </div>
            </form>
            <script>
            const MAX_NUMS = <?= $config['draw'] ?>;
            const MIN_NUM = <?= $config['min'] ?>;
            const MAX_NUM = <?= $config['max'] ?>;
            
            function feelingLucky() {
                document.querySelectorAll('.number-check').forEach(cb => cb.checked = false);
                const nums = new Set();
                while (nums.size < MAX_NUMS) nums.add(Math.floor(Math.random() * (MAX_NUM - MIN_NUM + 1)) + MIN_NUM);
                nums.forEach(num => { const cb = document.querySelector(`.number-check[value="${num}"]`); if (cb) cb.checked = true; });
                checkLimit(null);
            }
            
            function checkLimit(input) {
                const checked = document.querySelectorAll('.number-check:checked');
                const btn = document.getElementById('submitBtn');
                if (input && checked.length > MAX_NUMS) { input.checked = false; alert('Máximo ' + MAX_NUMS + ' números!'); return; }
                if (checked.length === MAX_NUMS) {
                    btn.disabled = false; btn.classList.remove('bg-gray-400'); btn.classList.add('bg-theme-green', 'hover:bg-green-700');
                    btn.innerText = 'CONFIRMAR JOGO ✅';
                } else {
                    btn.disabled = true; btn.classList.add('bg-gray-400'); btn.classList.remove('bg-theme-green', 'hover:bg-green-700');
                    btn.innerText = `Escolha ${MAX_NUMS} números (${checked.length}/${MAX_NUMS})`;
                }
            }
            
            function clearSelection() { document.querySelectorAll('.number-check').forEach(cb => cb.checked = false); checkLimit(null); }
            </script>
        </div>
        <?php endif; ?>
        
        <!-- Quota full - offer to add more games -->
        <?php if ($quota && count($myBets) >= $quota['games_allowed'] && $event['status'] === 'open' && !$winningNumbers): ?>
        <div class="card p-4 bg-blue-50 dark:bg-blue-900/20 border-2 border-blue-400 flex justify-between items-center">
            <div>
                <p class="font-bold text-blue-700 dark:text-blue-300">✅ Você completou seus <?= $quota['games_allowed'] ?> jogos!</p>
                <p class="text-xs text-gray-500">Quer mais? <?= formatMoney($event['game_price']) ?> por jogo adicional</p>
            </div>
            <form method="POST" action="?action=request_more_games">
                <?= csrfField() ?>
                <input type="hidden" name="event_id" value="<?= $id ?>">
                <input type="hidden" name="extra_games" value="1">
                <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-full font-bold text-sm">➕ +1 Jogo</button>
            </form>
        </div>
        <?php endif; ?>
        
        <!-- My Bets -->
        <div class="card overflow-hidden">
            <div class="p-4 border-b font-bold">🎯 Minhas Apostas</div>
            <div class="p-4 space-y-3">
                <?php if (empty($myBets)): ?>
                    <div class="text-center text-gray-400 py-8"><p class="text-4xl mb-2">🎰</p><p>Nenhuma aposta ainda.</p></div>
                <?php else: ?>
                    <?php $betCounter = 1; foreach ($myBets as $bet):
                        $nums = explode(',', $bet['numbers']);
                        $matches = $winningNumbers ? array_intersect($nums, $winningNumbers) : [];
                        $hits = count($matches);
                        $isWinner = $winningNumbers && $hits >= 4;
                    ?>
                    <div class="p-3 rounded-lg border-l-4 <?= $isWinner ? 'winner-card' : 'bg-gray-50 dark:bg-gray-700 border-theme-yellow' ?>">
                        <div class="flex justify-between mb-2">
                            <span class="text-xs font-bold">Jogo <?= $betCounter++ ?></span>
                            <?php if ($winningNumbers): ?><span class="text-xs font-bold <?= $hits >= 4 ? 'text-amber-600' : 'text-gray-500' ?>"><?= $hits ?> acerto<?= $hits != 1 ? 's' : '' ?></span><?php endif; ?>
                        </div>
                        <div class="flex flex-wrap gap-1.5">
                            <?php foreach ($nums as $n):
                                $hit = in_array((int)$n, $winningNumbers);
                                $bg = $winningNumbers ? ($hit ? 'bg-green-600 text-white' : 'bg-theme-yellow text-green-900') : 'bg-theme-yellow text-green-900';
                            ?>
                            <div class="w-9 h-9 rounded-full font-bold flex items-center justify-center text-sm shadow <?= $bg ?>"><?= str_pad($n, 2, '0', STR_PAD_LEFT) ?></div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- All Bets -->
        <div class="card overflow-hidden">
            <div class="p-4 border-b font-bold">📋 Todos os Jogos</div>
            <div class="p-4 space-y-3 max-h-96 overflow-y-auto">
                <?php
                $allBets = $pdo->query("SELECT b.*, u.name as user_name FROM bets b LEFT JOIN users u ON b.user_id = u.id WHERE b.event_id=$id ORDER BY b.id DESC")->fetchAll();
                if (empty($allBets)): ?>
                    <div class="text-center text-gray-400 py-8"><p class="text-4xl mb-2">📭</p><p>Nenhum jogo ainda.</p></div>
                <?php else: foreach ($allBets as $bet):
                    $nums = explode(',', $bet['numbers']);
                    $matches = $winningNumbers ? array_intersect($nums, $winningNumbers) : [];
                    $hits = count($matches);
                    $isAuto = $bet['type'] === 'auto';
                    $isMine = $bet['user_id'] == $user['id'];
                ?>
                <div class="bg-gray-50 dark:bg-gray-700 p-3 rounded-lg border-l-4 <?= $isAuto ? 'border-blue-400' : 'border-gray-300' ?>">
                    <div class="flex justify-between items-center mb-2">
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-bold <?= $isMine ? 'text-theme-green' : 'text-gray-600' ?>"><?= $isMine ? '⭐ Você' : ($bet['user_name'] ?? '🤖 Sistema') ?></span>
                            <?php if ($isAuto): ?><span class="bg-blue-100 text-blue-700 text-xs px-2 py-0.5 rounded-full font-bold">AUTO</span><?php endif; ?>
                        </div>
                        <?php if ($winningNumbers): ?><span class="text-xs font-bold <?= $hits >= 4 ? 'text-green-600' : 'text-gray-500' ?>"><?= $hits ?> acerto<?= $hits != 1 ? 's' : '' ?></span><?php endif; ?>
                    </div>
                    <div class="flex flex-wrap gap-1.5">
                        <?php foreach ($nums as $n):
                            $hit = in_array((int)$n, $winningNumbers);
                            if ($winningNumbers) { $bg = $hit ? 'bg-green-600 text-white' : 'bg-red-100 text-red-700'; }
                            else { $bg = $isAuto ? 'bg-blue-100 text-blue-800' : 'bg-theme-yellow text-green-900'; }
                        ?>
                        <div class="w-9 h-9 rounded-full font-bold flex items-center justify-center text-sm shadow <?= $bg ?>"><?= str_pad($n, 2, '0', STR_PAD_LEFT) ?></div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endforeach; endif; ?>
            </div>
        </div>
    </div>
    <?php
}
