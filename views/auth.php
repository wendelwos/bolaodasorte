<?php
/**
 * Bolão da Sorte v2.0 - Views
 * All page templates
 */

// LOGIN PAGE
function viewLogin() {
    ?>
    <div class="flex flex-col items-center justify-center pt-10">
        <div class="card p-8 w-full max-w-sm border-t-4 border-theme-yellow">
            <div class="text-center mb-6">
                <h2 class="text-2xl font-bold text-gray-800">Acesso ao Sistema</h2>
                <p class="text-gray-500 text-sm">Digite seu email e senha</p>
            </div>
            <form method="POST" action="?action=login" class="space-y-4">
                <?= csrfField() ?>
                <div>
                    <label class="label">Email</label>
                    <input type="email" name="email" required class="input-std" placeholder="seu@email.com">
                </div>
                <div>
                    <label class="label">Senha</label>
                    <input type="password" name="password" required class="input-std" placeholder="Sua senha">
                </div>
                <button type="submit" class="btn-primary w-full py-3">ENTRAR</button>
            </form>
            <div class="mt-2 text-center">
                <a href="?action=forgot_password" class="text-sm text-gray-400 hover:underline">Esqueci minha senha</a>
            </div>
            <div class="mt-4 text-center text-sm text-gray-500">
                Não tem conta? <a href="?action=register" class="text-theme-green font-bold hover:underline">Cadastre-se</a>
            </div>
            <div class="mt-2 text-center">
                <a href="?action=public_events" class="text-sm text-gray-400 hover:underline">Ver bolões abertos →</a>
            </div>
            
            <!-- Rules Button -->
            <div class="mt-4 pt-4 border-t border-gray-200">
                <button onclick="openRulesModal()" class="w-full py-2 text-sm bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 rounded-lg transition font-medium">
                    📖 Regras e Como Funciona
                </button>
            </div>
        </div>
    </div>
    
    <!-- Rules Modal -->
    <div id="rulesModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50 p-4" onclick="if(event.target===this)closeRulesModal()">
        <div class="card w-full max-w-2xl max-h-[85vh] overflow-hidden flex flex-col">
            <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center bg-theme-green text-white rounded-t-lg">
                <h3 class="text-lg font-bold">📖 Regras do Bolão da Sorte</h3>
                <button onclick="closeRulesModal()" class="text-2xl hover:opacity-70">&times;</button>
            </div>
            
            <!-- Tabs -->
            <div class="flex border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800">
                <button onclick="showRulesTab('funcionamento')" class="rules-tab active flex-1 py-3 text-sm font-medium" data-tab="funcionamento">🎯 Como Funciona</button>
                <button onclick="showRulesTab('apostas')" class="rules-tab flex-1 py-3 text-sm font-medium" data-tab="apostas">🎲 Apostas</button>
                <button onclick="showRulesTab('pagamentos')" class="rules-tab flex-1 py-3 text-sm font-medium" data-tab="pagamentos">💳 Pagamentos</button>
                <button onclick="showRulesTab('premios')" class="rules-tab flex-1 py-3 text-sm font-medium" data-tab="premios">🏆 Premiação</button>
            </div>
            
            <!-- Tab Content -->
            <div class="p-6 overflow-y-auto flex-1">
                <div id="tab-funcionamento" class="rules-content">
                    <h4 class="font-bold text-lg mb-3 text-theme-green">🎯 Como Funciona o Bolão</h4>
                    <ul class="space-y-2 text-sm text-gray-600 dark:text-gray-300">
                        <li>1️⃣ <b>Cadastro:</b> Crie sua conta gratuitamente (precisa aprovação do admin)</li>
                        <li>2️⃣ <b>Escolha o Bolão:</b> Veja os bolões abertos e solicite participação</li>
                        <li>3️⃣ <b>Aprovação:</b> O administrador aprova sua entrada e define quantos jogos você pode fazer</li>
                        <li>4️⃣ <b>Aposte:</b> Faça suas apostas escolhendo os números da sorte</li>
                        <li>5️⃣ <b>Pague:</b> Envie o pagamento via PIX (pode pagar antes ou depois de apostar)</li>
                        <li>6️⃣ <b>Resultado:</b> Acompanhe o sorteio e veja se ganhou!</li>
                    </ul>
                    <div class="mt-4 p-3 bg-green-50 dark:bg-green-900/20 rounded-lg border border-green-200 dark:border-green-800">
                        <p class="text-sm text-green-800 dark:text-green-300">💡 <b>Dica:</b> Você pode apostar primeiro e pagar depois! O sistema controla seu saldo devedor.</p>
                    </div>
                </div>
                
                <div id="tab-apostas" class="rules-content hidden">
                    <h4 class="font-bold text-lg mb-3 text-theme-green">🎲 Regras das Apostas</h4>
                    <ul class="space-y-2 text-sm text-gray-600 dark:text-gray-300">
                        <li>🔢 <b>Mega Sena:</b> Escolha 6 números de 01 a 60</li>
                        <li>🔢 <b>Quina:</b> Escolha 5 números de 01 a 80</li>
                        <li>🔢 <b>Lotofácil:</b> Escolha 15 números de 01 a 25</li>
                        <li>🎰 <b>Surpresinha:</b> Use o botão "🍀 Estou com Sorte" para números aleatórios</li>
                        <li>➕ <b>Mais Jogos:</b> Quer jogar mais? Clique em "+1 Jogo" para adicionar cotas extras</li>
                        <li>⏰ <b>Prazo:</b> As apostas devem ser feitas antes do sorteio oficial</li>
                    </ul>
                    <div class="mt-4 p-3 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg border border-yellow-200 dark:border-yellow-800">
                        <p class="text-sm text-yellow-800 dark:text-yellow-300">⚠️ <b>Atenção:</b> Após o sorteio, não é possível alterar apostas nem fazer pagamentos.</p>
                    </div>
                </div>
                
                <div id="tab-pagamentos" class="rules-content hidden">
                    <h4 class="font-bold text-lg mb-3 text-theme-green">💳 Pagamentos via PIX</h4>
                    <ul class="space-y-2 text-sm text-gray-600 dark:text-gray-300">
                        <li>💰 <b>Valor:</b> O sistema calcula automaticamente: Jogos × Valor do Jogo</li>
                        <li>📤 <b>Envio:</b> Faça o PIX e envie o comprovante pelo botão "Enviar Pagamento"</li>
                        <li>📊 <b>Múltiplos:</b> Você pode fazer vários pagamentos parciais</li>
                        <li>⏳ <b>Pendente:</b> Pagamentos ficam pendentes até o admin aprovar</li>
                        <li>✅ <b>Aprovado:</b> Após aprovação, seu saldo é atualizado automaticamente</li>
                    </ul>
                    <div class="mt-4 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
                        <p class="text-sm text-blue-800 dark:text-blue-300">ℹ️ <b>Flexível:</b> Aposte primeiro e pague até 3 horas antes do sorteio oficial! O pagamento não bloqueia a aposta.</p>
                    </div>
                </div>
                
                <div id="tab-premios" class="rules-content hidden">
                    <h4 class="font-bold text-lg mb-3 text-theme-green">🏆 Divisão de Prêmios</h4>
                    <ul class="space-y-2 text-sm text-gray-600 dark:text-gray-300">
                        <li>💰 <b>Proporcional:</b> O prêmio é dividido pelo número de jogos de cada participante</li>
                        <li>📊 <b>Cálculo:</b> Valor por Jogo = Prêmio Líquido ÷ Total de Jogos</li>
                        <li>💵 <b>Seu Prêmio:</b> Valor por Jogo × Seus Jogos</li>
                        <li>🏛️ <b>Impostos:</b> Prêmios acima de R$ 1.903,98 têm desconto de 13,8% na fonte</li>
                        <li>📧 <b>Notificação:</b> Você será avisado por email sobre prêmios e atualizações</li>
                    </ul>
                    <div class="mt-4 p-3 bg-purple-50 dark:bg-purple-900/20 rounded-lg border border-purple-200 dark:border-purple-800">
                        <p class="text-sm text-purple-800 dark:text-purple-300">🎉 <b>Transparência:</b> Veja todos os jogos do bolão e acompanhe os acertos em tempo real!</p>
                    </div>
                </div>
            </div>
            
            <div class="p-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800">
                <button onclick="closeRulesModal()" class="btn-primary w-full">Entendi!</button>
            </div>
        </div>
    </div>
    
    <script>
    function openRulesModal() {
        document.getElementById('rulesModal').classList.remove('hidden');
        document.getElementById('rulesModal').classList.add('flex');
    }
    function closeRulesModal() {
        document.getElementById('rulesModal').classList.add('hidden');
        document.getElementById('rulesModal').classList.remove('flex');
    }
    function showRulesTab(tabName) {
        document.querySelectorAll('.rules-content').forEach(c => c.classList.add('hidden'));
        document.querySelectorAll('.rules-tab').forEach(t => t.classList.remove('active', 'bg-white', 'dark:bg-gray-900', 'border-b-2', 'border-theme-green'));
        document.getElementById('tab-' + tabName).classList.remove('hidden');
        document.querySelector('[data-tab="' + tabName + '"]').classList.add('active', 'bg-white', 'dark:bg-gray-900', 'border-b-2', 'border-theme-green');
    }
    </script>
    
    <style>
    .rules-tab.active { background: white; border-bottom: 2px solid #009e4a; }
    .dark .rules-tab.active { background: #1a1a2e; }
    </style>
    <?php
}

// REGISTER PAGE
function viewRegister() {
    ?>
    <div class="flex flex-col items-center justify-center pt-10">
        <div class="card p-8 w-full max-w-sm border-t-4 border-theme-green">
            <div class="text-center mb-6">
                <h2 class="text-2xl font-bold text-gray-800">Criar Conta</h2>
                <p class="text-gray-500 text-sm">Preencha seus dados para se cadastrar</p>
            </div>
            <form method="POST" action="?action=register" class="space-y-4">
                <?= csrfField() ?>
                <div>
                    <label class="label">Nome Completo</label>
                    <input type="text" name="name" required class="input-std" placeholder="Seu nome">
                </div>
                <div>
                    <label class="label">Email</label>
                    <input type="email" name="email" required class="input-std" placeholder="seu@email.com">
                </div>
                <div>
                    <label class="label">Senha</label>
                    <input type="password" name="password" required class="input-std" placeholder="Mínimo 6 caracteres" minlength="6">
                </div>
                <div>
                    <label class="label">Confirmar Senha</label>
                    <input type="password" name="confirm_password" required class="input-std" placeholder="Repita a senha">
                </div>
                <button type="submit" class="btn-primary w-full py-3">CADASTRAR</button>
            </form>
            <div class="mt-4 text-center text-sm text-gray-500">
                Já tem conta? <a href="?action=login" class="text-theme-green font-bold hover:underline">Entrar</a>
            </div>
        </div>
    </div>
    <?php
}

// PUBLIC EVENTS PAGE
function viewPublicEvents($pdo, $user) {
    $events = $pdo->query("SELECT * FROM events WHERE status = 'open' ORDER BY created_at DESC")->fetchAll();
    ?>
    <div class="space-y-6">
        <div class="flex justify-between items-center">
            <h2 class="text-2xl font-bold text-gray-800">🎲 Bolões Abertos</h2>
            <?php if ($user): ?>
                <a href="?action=dashboard" class="text-theme-green text-sm font-bold">Meu Painel →</a>
            <?php endif; ?>
        </div>
        
        <?php if (empty($events)): ?>
            <div class="text-center text-gray-500 py-10 card p-8">
                <p class="text-4xl mb-2">📭</p>
                <p>Nenhum bolão aberto no momento.</p>
            </div>
        <?php else: ?>
            <div class="space-y-4">
                <?php foreach ($events as $ev):
                    $config = getGameConfig($ev['game_type']);
                    $betsCount = $pdo->query("SELECT COUNT(*) FROM bets WHERE event_id = " . $ev['id'])->fetchColumn();
                    
                    // Fetch prize info from API
                    $prizeInfo = null;
                    $contestNum = $ev['contest_number'] ?: $ev['draw_number'];
                    if ($contestNum) {
                        $prizeInfo = getLotteryResult($ev['game_type'], $contestNum);
                    }
                    
                    // Check if user already requested
                    $requested = false;
                    $hasQuota = false;
                    if ($user) {
                        $stmt = $pdo->prepare("SELECT status FROM participation_requests WHERE event_id = ? AND user_id = ?");
                        $stmt->execute([$ev['id'], $user['id']]);
                        $req = $stmt->fetch();
                        $requested = $req ? $req['status'] : false;
                        
                        $stmt = $pdo->prepare("SELECT id FROM quotas WHERE event_id = ? AND user_id = ?");
                        $stmt->execute([$ev['id'], $user['id']]);
                        $hasQuota = (bool)$stmt->fetch();
                    }
                ?>
                <div class="card overflow-hidden border-l-8 <?= str_replace('bg-', 'border-', $config['color']) ?>">
                    <div class="p-5">
                        <div class="flex justify-between items-start mb-2">
                            <div>
                                <span class="inline-block px-2 py-0.5 rounded text-xs text-white <?= $config['color'] ?> mb-1"><?= $config['name'] ?></span>
                                <?php if ($contestNum): ?>
                                    <span class="inline-block px-2 py-0.5 rounded text-xs bg-gray-600 text-white mb-1">Conc. <?= $contestNum ?></span>
                                <?php endif; ?>
                                <h3 class="text-xl font-bold text-gray-800"><?= h($ev['name']) ?></h3>
                            </div>
                            <div class="text-right">
                                <div class="text-sm text-gray-500">Valor Jogo</div>
                                <div class="font-bold text-green-600"><?= formatMoney($ev['game_price']) ?></div>
                            </div>
                        </div>
                        
                        <!-- Prize Info from API or Manual -->
                        <?php 
                        $estimatedValue = 0;
                        $isAccumulated = false;
                        if ($prizeInfo) {
                            $estimatedValue = (float)($prizeInfo['valorEstimadoProximoConcurso'] ?? $prizeInfo['valorAcumulado'] ?? 0);
                            $isAccumulated = !empty($prizeInfo['acumulado']);
                        }
                        // Fallback to admin's estimated_prize
                        if ($estimatedValue == 0 && !empty($ev['estimated_prize'])) {
                            $estimatedValue = (float)$ev['estimated_prize'];
                        }
                        ?>
                        
                        <?php if ($prizeInfo && !empty($prizeInfo['dezenas'])): ?>
                        <div class="bg-gradient-to-r from-yellow-50 to-amber-50 dark:from-yellow-900/20 dark:to-amber-900/20 p-3 rounded-lg mb-3 border border-yellow-200 dark:border-yellow-800">
                            <div class="flex justify-between items-center flex-wrap gap-2">
                                <div>
                                    <span class="text-xs text-gray-500">🎰 Resultado:</span>
                                    <div class="flex gap-1 mt-1">
                                        <?php foreach ($prizeInfo['dezenas'] as $num): ?>
                                            <span class="w-7 h-7 rounded-full <?= $config['color'] ?> text-white text-xs font-bold flex items-center justify-center"><?= $num ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <?php if (!empty($prizeInfo['dataSorteio'])): ?>
                                    <span class="text-xs text-gray-500">📅 <?= $prizeInfo['dataSorteio'] ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php elseif ($estimatedValue > 0): ?>
                        <div class="bg-gradient-to-r from-yellow-50 to-amber-50 dark:from-yellow-900/20 dark:to-amber-900/20 p-3 rounded-lg mb-3 border border-yellow-200 dark:border-yellow-800">
                            <div class="flex justify-between items-center flex-wrap gap-2">
                                <div>
                                    <span class="text-sm font-bold text-yellow-700 dark:text-yellow-400">
                                        🏆 Prêmio Estimado: <?= formatMoney($estimatedValue) ?>
                                    </span>
                                    <?php if ($isAccumulated): ?>
                                        <span class="ml-2 bg-red-500 text-white text-xs px-2 py-0.5 rounded-full animate-pulse">ACUMULADO!</span>
                                    <?php endif; ?>
                                </div>
                                <?php if ($prizeInfo && !empty($prizeInfo['dataSorteio'])): ?>
                                    <span class="text-xs text-gray-500">📅 <?= $prizeInfo['dataSorteio'] ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <div class="flex justify-between items-center mt-4 pt-4 border-t border-gray-100">
                            <div class="text-sm text-gray-500">
                                <span>🎫 <?= $betsCount ?> jogos registrados</span>
                            </div>
                            <div>
                                <?php if (!$user): ?>
                                    <a href="?action=login" class="btn-primary text-sm">Entrar para participar</a>
                                <?php elseif ($hasQuota): ?>
                                    <a href="?action=view_event&id=<?= $ev['id'] ?>" class="btn-primary text-sm">Jogar →</a>
                                <?php elseif ($requested === 'pending'): ?>
                                    <span class="bg-yellow-100 text-yellow-800 px-3 py-2 rounded text-sm font-bold">⏳ Aguardando aprovação</span>
                                <?php elseif ($requested === 'rejected'): ?>
                                    <span class="bg-red-100 text-red-800 px-3 py-2 rounded text-sm font-bold">❌ Solicitação negada</span>
                                <?php else: ?>
                                    <button onclick="openRequestModal(<?= $ev['id'] ?>, '<?= h($ev['name']) ?>')" class="btn-primary text-sm">🙋 Solicitar Participação</button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Request Modal -->
    <div id="requestModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50" onclick="if(event.target===this)closeRequestModal()">
        <div class="card p-6 w-full max-w-md mx-4">
            <h3 class="text-lg font-bold mb-4">Solicitar Participação</h3>
            <p class="text-sm text-gray-500 mb-4">Bolão: <b id="modalEventName"></b></p>
            <form method="POST" action="?action=request_participation">
                <?= csrfField() ?>
                <input type="hidden" name="event_id" id="modalEventId">
                <div class="mb-4">
                    <label class="label">Mensagem (opcional)</label>
                    <textarea name="message" class="input-std" rows="3" placeholder="Ex: Quero participar com 2 jogos"></textarea>
                </div>
                <div class="flex gap-2">
                    <button type="button" onclick="closeRequestModal()" class="btn-secondary flex-1">Cancelar</button>
                    <button type="submit" class="btn-primary flex-1">Enviar Solicitação</button>
                </div>
            </form>
        </div>
    </div>
    <script>
    function openRequestModal(id, name) {
        document.getElementById('modalEventId').value = id;
        document.getElementById('modalEventName').textContent = name;
        document.getElementById('requestModal').classList.remove('hidden');
        document.getElementById('requestModal').classList.add('flex');
    }
    function closeRequestModal() {
        document.getElementById('requestModal').classList.add('hidden');
        document.getElementById('requestModal').classList.remove('flex');
    }
    </script>
    <?php
}

// FORGOT PASSWORD PAGE
function viewForgotPassword() {
    ?>
    <div class="flex flex-col items-center justify-center pt-10">
        <div class="card p-8 w-full max-w-sm border-t-4 border-yellow-500">
            <div class="text-center mb-6">
                <h2 class="text-2xl font-bold text-gray-800">🔐 Esqueci Minha Senha</h2>
                <p class="text-gray-500 text-sm">Digite seu email para receber o link de recuperação</p>
            </div>
            <form method="POST" action="?action=forgot_password" class="space-y-4">
                <?= csrfField() ?>
                <div>
                    <label class="label">Email cadastrado</label>
                    <input type="email" name="email" required class="input-std" placeholder="seu@email.com">
                </div>
                <button type="submit" class="btn-primary w-full py-3">Enviar Link de Recuperação</button>
            </form>
            <div class="mt-4 text-center text-sm text-gray-500">
                Lembrou a senha? <a href="?action=login" class="text-theme-green font-bold hover:underline">Voltar ao login</a>
            </div>
        </div>
    </div>
    <?php
}

// RESET PASSWORD PAGE
function viewResetPassword($pdo) {
    $token = $_GET['token'] ?? '';
    
    // Validate token
    $stmt = $pdo->prepare("SELECT pr.*, u.email, u.name FROM password_resets pr 
                           JOIN users u ON pr.user_id = u.id 
                           WHERE pr.token = ? AND pr.used = 0 AND pr.expires_at > NOW()");
    $stmt->execute([$token]);
    $reset = $stmt->fetch();
    
    if (!$reset) {
        ?>
        <div class="flex flex-col items-center justify-center pt-10">
            <div class="card p-8 w-full max-w-sm text-center border-t-4 border-red-500">
                <p class="text-4xl mb-4">⚠️</p>
                <h2 class="text-xl font-bold text-gray-800 mb-2">Link Inválido ou Expirado</h2>
                <p class="text-gray-500 text-sm mb-6">Este link de recuperação não é válido ou já expirou.</p>
                <a href="?action=forgot_password" class="btn-primary inline-block">Solicitar Novo Link</a>
            </div>
        </div>
        <?php
        return;
    }
    ?>
    <div class="flex flex-col items-center justify-center pt-10">
        <div class="card p-8 w-full max-w-sm border-t-4 border-theme-green">
            <div class="text-center mb-6">
                <h2 class="text-2xl font-bold text-gray-800">🔑 Nova Senha</h2>
                <p class="text-gray-500 text-sm">Olá <b><?= h($reset['name']) ?></b>, defina sua nova senha</p>
            </div>
            <form method="POST" action="?action=reset_password" class="space-y-4">
                <?= csrfField() ?>
                <input type="hidden" name="token" value="<?= h($token) ?>">
                <div>
                    <label class="label">Nova Senha</label>
                    <input type="password" name="password" required class="input-std" placeholder="Mínimo 6 caracteres" minlength="6">
                </div>
                <div>
                    <label class="label">Confirmar Nova Senha</label>
                    <input type="password" name="confirm_password" required class="input-std" placeholder="Repita a nova senha">
                </div>
                <button type="submit" class="btn-primary w-full py-3">Alterar Senha</button>
            </form>
        </div>
    </div>
    <?php
}
