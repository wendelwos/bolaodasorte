<?php
/**
 * Bolão da Sorte v2.0 - Profile View
 */

function viewProfile($user) {
    // Check if profile is incomplete (phone missing)
    $isIncomplete = empty($user['phone']);
    ?>
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-bold">👤 Meu Perfil</h2>
            <a href="?action=dashboard" class="text-sm text-gray-500 hover:underline">Voltar</a>
        </div>
        
        <?php if ($isIncomplete): ?>
        <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-800 p-4 rounded">
            <p class="font-bold">⚠️ Complete seu cadastro!</p>
            <p class="text-sm">Preencha seu telefone para continuar usando o sistema.</p>
        </div>
        <?php endif; ?>
        
        <div class="card p-6">
            <form method="POST" action="?action=update_profile" class="space-y-4">
                <?= csrfField() ?>
                
                <div>
                    <label class="label">Nome Completo</label>
                    <input type="text" name="name" value="<?= h($user['name']) ?>" required class="input-std" minlength="3">
                </div>
                
                <div>
                    <label class="label">Email</label>
                    <input type="email" value="<?= h($user['email']) ?>" disabled class="input-std bg-gray-100 dark:bg-gray-600 cursor-not-allowed">
                    <p class="text-xs text-gray-400 mt-1">Email não pode ser alterado</p>
                </div>
                
                <div>
                    <label class="label">Telefone <span class="text-red-500">*</span></label>
                    <input type="tel" name="phone" value="<?= h($user['phone'] ?? '') ?>" required 
                           class="input-std" placeholder="(11) 99999-9999"
                           pattern="[\d\s\(\)\-\+]+" title="Apenas números, espaços, parênteses e hífens">
                </div>
                
                <div>
                    <label class="label">Endereço <span class="text-gray-400 text-xs">(opcional)</span></label>
                    <input type="text" name="address" value="<?= h($user['address'] ?? '') ?>" 
                           class="input-std" placeholder="Rua, número, bairro, cidade">
                </div>
                
                <hr class="my-4">
                
                <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg">
                    <h3 class="font-bold text-sm mb-3">🔐 Alterar Senha (opcional)</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="label">Nova Senha</label>
                            <input type="password" name="new_password" class="input-std" placeholder="Deixe em branco para manter" minlength="6">
                        </div>
                        <div>
                            <label class="label">Confirmar Nova Senha</label>
                            <input type="password" name="confirm_password" class="input-std" placeholder="Repita a nova senha">
                        </div>
                    </div>
                </div>
                
                <button type="submit" class="btn-primary w-full py-3">💾 Salvar Alterações</button>
            </form>
        </div>
        
        <!-- Account Info -->
        <div class="card p-4 text-sm text-gray-500">
            <div class="flex justify-between">
                <span>📅 Membro desde:</span>
                <span><?= date('d/m/Y', strtotime($user['created_at'])) ?></span>
            </div>
            <div class="flex justify-between mt-1">
                <span>🆔 ID:</span>
                <span>#<?= $user['id'] ?></span>
            </div>
        </div>
    </div>
    <?php
}
