<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Usuário temporário para desenvolvimento; trocar por redirecionamento ao login antes de publicar
if (!isset($_SESSION['id_usuario'])) {
    $_SESSION['id_usuario'] = 1;
}

// Título dinâmico
if (!isset($page_title)) {
    $page_title = 'Painel';
}

// Controle do botão Exportar
if (!isset($show_export_button)) {
    $show_export_button = false;
}

// Renderiza apenas o cabeçalho, sem html/head/body
$header_only = $header_only ?? false;
?>

<?php if (!$header_only): ?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Painel Finanças Já — Gerencie suas finanças com inteligência.">
    <meta name="theme-color" content="#0a0a0b">

    <title>Finanças Já • <?php echo htmlspecialchars($page_title); ?></title>

    <!-- Ícones do Bootstrap -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Fontes -->
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;800&display=swap" rel="stylesheet">

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- CSS do projeto -->
    <link rel="stylesheet" href="../CSS/variables.css">
    <link rel="stylesheet" href="../CSS/base.css">
    <link rel="stylesheet" href="../CSS/menu.css">

    <!-- Tailwind (gerado localmente) -->
    <link rel="stylesheet" href="../CSS/tailwind.css?v=<?php echo time(); ?>">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
</head>
<body>
<?php endif; ?>

<!-- Cabeçalho -->
<header class="top-header" role="banner">
    <div class="max-w-7xl mx-auto px-8 py-4">
        <div class="flex justify-between items-center">

            <!-- Logo -->
            <div class="flex items-center gap-3">
                <button id="sidebarToggle" class="btn btn-ghost lg:hidden text-2xl">
                    <i class="bi bi-list"></i>
                </button>

                <div class="flex items-center gap-2">
                    <a href="menu.php" class="flex items-center gap-2">
                    <div class="w-9 h-9 bg-[#ff9100] rounded-2xl flex items-center justify-center text-white text-3xl font-bold shadow-inner">
                        $
                    </div>
                    <span class="text-[#ff9100] font-semibold text-3xl tracking-tighter">
                        Finanças Já
                    </span>
                    </a>
                </div>
            </div>

            <!-- Botões -->
            <div class="flex items-center gap-3">
                <button id="themeToggle" class="w-10 h-10 rounded-2xl bg-white/10 hover:bg-white/20 flex items-center justify-center text-xl transition">
                    <i class="bi bi-moon-stars" id="themeIcon"></i>
                </button>

                <?php if ($show_export_button): ?>
                <button id="exportCsv"
                        class="flex items-center gap-2 bg-[#ff9100] hover:bg-[#ffb25a] text-white px-6 py-3 rounded-3xl font-medium transition">
                    <i class="bi bi-download"></i>
                    <span class="hidden sm:inline">Exportar</span>
                </button>
                <?php endif; ?>
            </div>

        </div>
    </div>
</header>