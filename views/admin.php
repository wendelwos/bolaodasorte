<?php
/**
 * Bolão da Sorte v2.0 - Admin Views
 */

// MANAGE USERS
function viewManageUsers($pdo) {
    $users = $pdo->query("SELECT * FROM users WHERE status = 'approved' ORDER BY name")->fetchAll();
    ?>
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-bold">Gerenciar Usuários</h2>
            <a href="?action=dashboard" class="text-sm text-gray-500 hover:underline">Voltar</a>
        </div>
        
        <div class="card p-6">
            <h3 class="font-bold text-gray-700 mb-3">Adicionar Novo Usuário</h3>
            <form method="POST" action="?action=create_user" class="flex flex-wrap gap-2 items-end">
                <?= csrfField() ?>
                <div class="flex-grow min-w-[150px]">
                    <label class="text-xs text-gray-500">Nome</label>
                    <input type="text" name="name" class="input-std" required>
                </div>
                <div class="flex-grow min-w-[180px]">
                    <label class="text-xs text-gray-500">Email</label>
                    <input type="email" name="email" class="input-std" required>
                </div>
                <div class="w-32">
                    <label class="text-xs text-gray-500">Senha</label>
                    <input type="text" name="password" class="input-std" value="<?= rand(100000, 999999) ?>" required>
                </div>
                <button type="submit" class="btn-primary h-[42px] px-4">Add</button>
            </form>
        </div>
        
        <div class="card overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="p-3 text-left">Nome</th>
                        <th class="p-3 text-left">Email</th>
                        <th class="p-3 text-center">Tipo</th>
                        <th class="p-3 text-center">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <?php foreach ($users as $u): ?>
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                        <td class="p-3 font-medium"><?= h($u['name']) ?></td>
                        <td class="p-3 text-gray-600"><?= h($u['email']) ?></td>
                        <td class="p-3 text-center">
                            <?php if ($u['is_admin']): ?>
                                <span class="bg-purple-100 text-purple-700 px-2 py-0.5 rounded-full text-xs">Admin</span>
                            <?php else: ?>
                                <span class="text-gray-400">User</span>
                            <?php endif; ?>
                        </td>
                        <td class="p-3 text-center">
                            <?php if (!$u['is_admin']): ?>
                            <form method="POST" action="?action=delete_user" class="inline" onsubmit="return confirm('Excluir este usuário?')">
                                <?= csrfField() ?>
                                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                <button type="submit" class="text-red-500 hover:underline text-xs">Excluir</button>
                            </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php
}

// PENDING USERS
function viewPendingUsers($pdo) {
    $users = $pdo->query("SELECT * FROM users WHERE status = 'pending' ORDER BY created_at DESC")->fetchAll();
    ?>
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-bold">Usuários Pendentes</h2>
            <a href="?action=dashboard" class="text-sm text-gray-500 hover:underline">Voltar</a>
        </div>
        
        <?php if (empty($users)): ?>
            <div class="card p-8 text-center text-gray-500">
                <p class="text-4xl mb-2">✅</p>
                <p>Nenhum usuário pendente de aprovação.</p>
            </div>
        <?php else: ?>
            <div class="space-y-3">
                <?php foreach ($users as $u): ?>
                <div class="card p-4 flex justify-between items-center">
                    <div>
                        <div class="font-bold"><?= h($u['name']) ?></div>
                        <div class="text-sm text-gray-500"><?= h($u['email']) ?></div>
                        <div class="text-xs text-gray-400"><?= $u['created_at'] ?></div>
                    </div>
                    <div class="flex gap-2">
                        <form method="POST" action="?action=update_user_status" class="inline">
                            <?= csrfField() ?>
                            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                            <input type="hidden" name="status" value="approved">
                            <button type="submit" class="btn-primary text-sm px-3 py-1">✓ Aprovar</button>
                        </form>
                        <form method="POST" action="?action=update_user_status" class="inline">
                            <?= csrfField() ?>
                            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                            <input type="hidden" name="status" value="rejected">
                            <button type="submit" class="btn-danger text-sm px-3 py-1">✗ Rejeitar</button>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    <?php
}

// MANAGE PARTICIPATION REQUESTS
function viewManageRequests($pdo) {
    $requests = $pdo->query("SELECT r.*, u.name as user_name, u.email, e.name as event_name, e.game_price FROM participation_requests r JOIN users u ON r.user_id = u.id JOIN events e ON r.event_id = e.id WHERE r.status = 'pending' ORDER BY r.created_at DESC")->fetchAll();
    ?>
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-bold">Solicitações de Participação</h2>
            <a href="?action=dashboard" class="text-sm text-gray-500 hover:underline">Voltar</a>
        </div>
        
        <?php if (empty($requests)): ?>
            <div class="card p-8 text-center text-gray-500">
                <p class="text-4xl mb-2">📭</p>
                <p>Nenhuma solicitação pendente.</p>
            </div>
        <?php else: ?>
            <div class="space-y-4">
                <?php foreach ($requests as $r): ?>
                <div class="card p-4">
                    <div class="flex justify-between items-start mb-3">
                        <div>
                            <div class="font-bold text-lg"><?= h($r['user_name']) ?></div>
                            <div class="text-sm text-gray-500"><?= h($r['email']) ?></div>
                        </div>
                        <div class="text-right">
                            <div class="font-bold text-theme-green"><?= h($r['event_name']) ?></div>
                            <div class="text-sm text-gray-500"><?= formatMoney($r['game_price']) ?>/jogo</div>
                        </div>
                    </div>
                    <?php if ($r['message']): ?>
                        <div class="bg-gray-50 dark:bg-gray-700 p-2 rounded text-sm mb-3">"<?= h($r['message']) ?>"</div>
                    <?php endif; ?>
                    <form method="POST" action="?action=handle_request" class="flex flex-wrap gap-2 items-end">
                        <?= csrfField() ?>
                        <input type="hidden" name="request_id" value="<?= $r['id'] ?>">
                        <div class="w-24">
                            <label class="text-xs text-gray-500">Jogos</label>
                            <input type="number" name="games_allowed" value="1" class="input-std" min="1">
                        </div>
                        <div class="w-28">
                            <label class="text-xs text-gray-500">Valor</label>
                            <input type="number" step="0.01" name="amount_paid" value="<?= $r['game_price'] ?>" class="input-std">
                        </div>
                        <button type="submit" name="status" value="approved" class="btn-primary px-4 py-2">✓ Aprovar</button>
                        <button type="submit" name="status" value="rejected" class="btn-danger px-4 py-2">✗ Rejeitar</button>
                    </form>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    <?php
}

// MANAGE EVENTS
function viewManageEvents($pdo) {
    $events = $pdo->query("SELECT * FROM events ORDER BY created_at DESC")->fetchAll();
    ?>
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-bold">📋 Gerenciar Bolões</h2>
            <div class="flex gap-2">
                <a href="?action=new_event" class="btn-primary px-4 py-2 text-sm">+ Novo Bolão</a>
                <a href="?action=dashboard" class="text-sm text-gray-500 hover:underline">Voltar</a>
            </div>
        </div>
        
        <?php if (empty($events)): ?>
        <div class="card p-6 text-center text-gray-500">
            <p class="text-4xl mb-2">📭</p>
            <p>Nenhum bolão cadastrado.</p>
        </div>
        <?php else: ?>
        <div class="space-y-4">
            <?php foreach ($events as $ev): 
                $config = getGameConfig($ev['game_type']);
            ?>
            <div class="card p-4">
                <div class="flex justify-between items-start mb-3">
                    <div>
                        <span class="inline-block px-2 py-0.5 rounded text-xs text-white <?= $config['color'] ?> mb-1"><?= $config['name'] ?></span>
                        <h3 class="text-lg font-bold"><?= h($ev['name']) ?></h3>
                        <p class="text-sm text-gray-500">
                            Concurso: <?= $ev['contest_number'] ?: $ev['draw_number'] ?: 'Não definido' ?> | 
                            Valor: <?= formatMoney($ev['game_price']) ?>
                        </p>
                    </div>
                    <span class="px-2 py-1 rounded text-xs <?= $ev['status'] === 'open' ? 'bg-green-100 text-green-800' : ($ev['status'] === 'closed' ? 'bg-orange-100 text-orange-800' : 'bg-gray-200 text-gray-600') ?>">
                        <?= ucfirst($ev['status']) ?>
                    </span>
                </div>
                
                <form method="POST" action="?action=update_event" class="space-y-3">
                    <?= csrfField() ?>
                    <input type="hidden" name="event_id" value="<?= $ev['id'] ?>">
                    
                    <div class="grid grid-cols-1 sm:grid-cols-5 gap-2">
                        <div class="sm:col-span-2">
                            <label class="text-xs text-gray-500">Nome</label>
                            <input type="text" name="name" value="<?= h($ev['name']) ?>" class="input-std" required>
                        </div>
                        <div>
                            <label class="text-xs text-gray-500">Status</label>
                            <select name="status" class="input-std">
                                <option value="open" <?= $ev['status'] === 'open' ? 'selected' : '' ?>>Aberto</option>
                                <option value="closed" <?= $ev['status'] === 'closed' ? 'selected' : '' ?>>Fechado</option>
                                <option value="finished" <?= $ev['status'] === 'finished' ? 'selected' : '' ?>>Finalizado</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-xs text-gray-500">Concurso Nº</label>
                            <input type="number" name="draw_number" value="<?= $ev['contest_number'] ?: $ev['draw_number'] ?>" class="input-std" placeholder="2654">
                        </div>
                        <div>
                            <label class="text-xs text-gray-500">🏆 Prêmio Est.</label>
                            <input type="number" step="0.01" name="estimated_prize" value="<?= $ev['estimated_prize'] ?? 0 ?>" class="input-std" placeholder="1000000">
                        </div>
                    </div>
                    
                    <div class="flex gap-2 justify-end">
                        <button type="submit" class="btn-primary px-4 py-2 text-sm">💾 Salvar</button>
                        <a href="?action=manage_event&id=<?= $ev['id'] ?>" class="btn-secondary px-4 py-2 text-sm">👥 Cotas</a>
                    </div>
                </form>
                
                <div class="mt-3 pt-3 border-t flex justify-between items-center">
                    <span class="text-xs text-gray-400">Criado: <?= date('d/m/Y H:i', strtotime($ev['created_at'])) ?></span>
                    <form method="POST" action="?action=delete_event" onsubmit="return confirm('⚠️ Excluir este bolão? Esta ação não pode ser desfeita!')">
                        <?= csrfField() ?>
                        <input type="hidden" name="event_id" value="<?= $ev['id'] ?>">
                        <button type="submit" class="text-red-500 hover:text-red-700 text-xs">🗑️ Excluir</button>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
    <?php
}
