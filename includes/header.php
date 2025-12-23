<?php
/**
 * Bolão da Sorte v2.0 - Header Template
 */
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= APP_NAME ?></title>
    <meta name="description" content="Sistema de bolões para loterias - Mega Sena, Quina e Lotofácil">
    <link rel="icon" type="image/png" href="<?= ASSETS_PATH ?>/images/favicon.png">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        theme: { green: '#009e4a', yellow: '#f4c90e', dark: '#1a1a1a' }
                    }
                }
            }
        }
    </script>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= ASSETS_PATH ?>/css/styles.css">
</head>
<body class="min-h-screen flex flex-col bg-gray-100 dark:bg-gray-900 transition-colors">

<!-- Toast Notifications -->
<?php $flash = getFlash(); if ($flash): ?>
<div class="toast fixed top-4 right-4 z-50 px-6 py-3 rounded-lg shadow-lg <?= $flash['type'] === 'success' ? 'bg-green-500' : 'bg-red-500' ?> text-white font-semibold">
    <?= h($flash['message']) ?>
</div>
<?php endif; ?>

<!-- Header -->
<header class="bg-theme-green dark:bg-gray-800 text-white shadow-lg sticky top-0 z-40">
    <div class="max-w-4xl mx-auto px-4 py-3 flex justify-between items-center">
        <a href="?" class="flex items-center gap-2">
            <img src="<?= ASSETS_PATH ?>/images/logo.png" alt="<?= APP_NAME ?>" class="header-logo">
        </a>
        <div class="flex items-center gap-3">
            <!-- Dark Mode Toggle -->
            <button onclick="toggleDarkMode()" class="p-2 rounded-lg hover:bg-white/10 transition" title="Alternar tema">
                <span id="theme-icon">🌙</span>
            </button>
            <?php if ($user): ?>
                <a href="?action=my_bets" class="text-xs bg-white/20 hover:bg-white/30 px-2 py-1 rounded transition hidden sm:inline" title="Minhas Apostas">📊</a>
                <?php if ($user['is_admin']): ?>
                    <a href="?action=manage_payments" class="text-xs bg-yellow-500 hover:bg-yellow-600 px-2 py-1 rounded transition" title="Pagamentos">💳</a>
                <?php endif; ?>
                <a href="?action=profile" class="text-sm hidden sm:inline hover:underline" title="Meu Perfil">
                    Olá, <b><?= h($user['name']) ?></b>
                    <?php if (empty($user['phone'])): ?><span class="text-yellow-300">⚠️</span><?php endif; ?>
                </a>
                <?php if ($user['is_admin']): ?>
                    <span class="bg-purple-500 text-xs px-2 py-0.5 rounded-full">Admin</span>
                <?php endif; ?>
                <a href="?action=profile" class="text-xs bg-white/20 hover:bg-white/30 px-3 py-1 rounded transition sm:hidden">👤</a>
                <a href="?action=logout" class="text-xs bg-red-500 hover:bg-red-600 px-3 py-1 rounded transition">Sair</a>
            <?php else: ?>
                <a href="?action=login" class="text-sm hover:underline">Entrar</a>
                <a href="?action=register" class="text-xs bg-theme-yellow text-green-900 px-3 py-1 rounded font-bold">Cadastrar</a>
            <?php endif; ?>
        </div>
    </div>
</header>

<main class="flex-grow p-4">
<div class="max-w-4xl mx-auto">
