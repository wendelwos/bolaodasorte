<?php
/**
 * Bolão da Sorte v2.0 - Dashboard Views
 */

// DASHBOARD
function viewDashboard($pdo, $user) {
    ?>
    <div class="flex flex-col gap-6">
        <h2 class="text-2xl font-bold text-gray-800 border-b pb-2">Painel de Eventos</h2>
        
        <?php if ($user['is_admin']): ?>
        <div class="grid grid-cols-2 sm:grid-cols-5 gap-3">
            <a href="?action=new_event" class="bg-theme-yellow text-green-900 p-4 rounded-xl shadow-md font-bold flex flex-col items-center justify-center gap-2 hover:bg-yellow-400 transition">
                <span class="text-2xl">+</span> Novo Bolão
            </a>
            <a href="?action=manage_events" class="card p-4 font-bold flex flex-col items-center justify-center gap-2 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                <span>📋</span> Bolões
            </a>
            <a href="?action=manage_users" class="card p-4 font-bold flex flex-col items-center justify-center gap-2 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                <span>👥</span> Usuários
            </a>
            <a href="?action=pending_users" class="card p-4 font-bold flex flex-col items-center justify-center gap-2 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                <span>⏳</span> Pendentes
            </a>
            <a href="?action=manage_requests" class="card p-4 font-bold flex flex-col items-center justify-center gap-2 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                <span>✉️</span> Solicitações
            </a>
        </div>
        <?php endif; ?>
        
        <!-- Filter -->
        <div class="flex gap-2 text-sm">
            <a href="?action=dashboard&status=open" class="px-3 py-1 rounded-full <?= ($_GET['status'] ?? '') === 'open' ? 'bg-green-500 text-white' : 'bg-gray-200 dark:bg-gray-700' ?>">Abertos</a>
            <a href="?action=dashboard&status=closed" class="px-3 py-1 rounded-full <?= ($_GET['status'] ?? '') === 'closed' ? 'bg-orange-500 text-white' : 'bg-gray-200 dark:bg-gray-700' ?>">Fechados</a>
            <a href="?action=dashboard&status=finished" class="px-3 py-1 rounded-full <?= ($_GET['status'] ?? '') === 'finished' ? 'bg-gray-500 text-white' : 'bg-gray-200 dark:bg-gray-700' ?>">Finalizados</a>
            <a href="?action=dashboard" class="px-3 py-1 rounded-full <?= !isset($_GET['status']) ? 'bg-theme-green text-white' : 'bg-gray-200 dark:bg-gray-700' ?>">Todos</a>
        </div>
        
        <div class="space-y-4">
            <?php
            $statusFilter = $_GET['status'] ?? null;
            if ($user['is_admin']) {
                $sql = "SELECT * FROM events";
                if ($statusFilter) $sql .= " WHERE status = " . $pdo->quote($statusFilter);
                $sql .= " ORDER BY created_at DESC";
                $events = $pdo->query($sql)->fetchAll();
            } else {
                $stmt = $pdo->prepare("SELECT e.* FROM events e INNER JOIN quotas q ON e.id = q.event_id WHERE q.user_id = ?" . ($statusFilter ? " AND e.status = ?" : "") . " ORDER BY e.created_at DESC");
                $params = [$user['id']];
                if ($statusFilter) $params[] = $statusFilter;
                $stmt->execute($params);
                $events = $stmt->fetchAll();
            }
            
            if (empty($events)) {
                echo "<div class='text-center text-gray-500 py-10 card p-8'><p class='text-4xl mb-2'>📭</p><p>Nenhum bolão encontrado.</p><a href='?action=public_events' class='text-theme-green hover:underline'>Ver bolões disponíveis →</a></div>";
            }
            
            foreach ($events as $ev):
                $config = getGameConfig($ev['game_type']);
                $betsCount = $pdo->query("SELECT COUNT(*) FROM bets WHERE event_id = " . $ev['id'])->fetchColumn();
                $stmt = $pdo->prepare("SELECT * FROM quotas WHERE event_id = ? AND user_id = ?");
                $stmt->execute([$ev['id'], $user['id']]);
                $myQuota = $stmt->fetch();
            ?>
            <div class="card overflow-hidden border-l-8 <?= str_replace('bg-', 'border-', $config['color']) ?>">
                <div class="p-5">
                    <div class="flex justify-between items-start mb-2">
                        <div>
                            <span class="inline-block px-2 py-0.5 rounded text-xs text-white <?= $config['color'] ?> mb-1"><?= $config['name'] ?></span>
                            <?php if ($ev['draw_number']): ?>
                                <span class="inline-block px-2 py-0.5 rounded text-xs bg-gray-600 text-white mb-1">Conc. <?= $ev['draw_number'] ?></span>
                            <?php endif; ?>
                            <span class="inline-block px-2 py-0.5 rounded text-xs <?= $ev['status'] === 'open' ? 'bg-green-100 text-green-800' : ($ev['status'] === 'closed' ? 'bg-orange-100 text-orange-800' : 'bg-gray-200 text-gray-600') ?> mb-1"><?= ucfirst($ev['status']) ?></span>
                            <h3 class="text-xl font-bold text-gray-800"><?= h($ev['name']) ?></h3>
                        </div>
                        <div class="text-right">
                            <div class="text-sm text-gray-500">Valor Jogo</div>
                            <div class="font-bold text-green-600"><?= formatMoney($ev['game_price']) ?></div>
                        </div>
                    </div>
                    <div class="flex justify-between items-center mt-4 pt-4 border-t border-gray-100">
                        <div class="text-sm"><span class="text-gray-500">Jogos:</span> <b><?= $betsCount ?></b></div>
                        <div class="flex gap-2">
                            <?php if ($user['is_admin']): ?>
                                <a href="?action=manage_event&id=<?= $ev['id'] ?>" class="bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 px-4 py-2 rounded-lg text-sm font-semibold hover:bg-gray-200">Gerenciar</a>
                            <?php endif; ?>
                            <a href="?action=view_event&id=<?= $ev['id'] ?>" class="btn-primary px-4 py-2 text-sm"><?= $myQuota ? 'Jogar / Ver' : 'Ver' ?></a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php
}

// NEW EVENT FORM
function viewNewEvent() {
    ?>
    <div class="card p-6">
        <h2 class="text-xl font-bold mb-4">Criar Novo Bolão</h2>
        <form method="POST" action="?action=create_event" class="space-y-4">
            <?= csrfField() ?>
            <div>
                <label class="label">Nome do Bolão</label>
                <input type="text" name="name" class="input-std" required placeholder="Ex: Mega da Virada 2025">
            </div>
            <div>
                <label class="label">Tipo de Jogo</label>
                <select name="game_type" class="input-std">
                    <option value="mega">Mega Sena</option>
                    <option value="quina">Quina</option>
                    <option value="lotofacil">Lotofácil</option>
                </select>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="label">Valor do Jogo (R$)</label>
                    <input type="number" step="0.01" name="game_price" class="input-std" required value="6.00">
                </div>
                <div>
                    <label class="label">Concurso (Nº)</label>
                    <input type="number" name="draw_number" class="input-std" placeholder="Ex: 2650">
                </div>
            </div>
            <div class="flex gap-2 pt-2">
                <a href="?action=dashboard" class="btn-secondary w-full text-center">Cancelar</a>
                <button type="submit" class="btn-primary w-full">Criar Bolão</button>
            </div>
        </form>
    </div>
    <?php
}
