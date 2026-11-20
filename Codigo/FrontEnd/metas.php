<?php
// Variáveis da página (definir antes do include do cabeçalho)
$page_title = "Metas";
$show_export_button = false; // Oculta o botão Exportar

include 'layouts/header.php';
?>

    <div class="max-w-7xl mx-auto pt-12 px-8 pb-20 grid grid-cols-1 lg:grid-cols-12 gap-12">
        <div class="lg:col-span-8 space-y-12">
            <div>
                <h2 class="text-6xl font-black tracking-tighter bg-gradient-to-r from-[var(--color-text-primary)] via-[var(--color-accent-primary)] to-[var(--color-accent-secondary)] bg-clip-text text-transparent">Suas Metas do Mês</h2>
                <p class="text-xl text-[var(--color-text-secondary)] mt-2">Metas automáticas • <?= date('F Y') ?></p>
            </div>
          
            <div id="resumoMes" class="glass p-8 rounded-3xl grid grid-cols-3 gap-6 text-center"></div>
          
            <div class="glass p-10">
                <h3 class="flex items-center gap-4 text-3xl font-semibold mb-10"><i class="fa-solid fa-rocket text-[var(--color-accent-primary)]"></i> Lançar Nova Meta do Mês</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div class="relative">
                        <input type="text" id="titulo" class="peer w-full bg-transparent border-b-2 border-white/20 pt-8 pb-5 text-2xl focus:border-[var(--color-accent-primary)] outline-none transition-all placeholder:text-transparent" placeholder=" ">
                        <label for="titulo" class="pointer-events-none absolute left-0 top-4 text-base text-[var(--color-text-secondary)] transition-all duration-300 peer-focus:top-0 peer-focus:text-sm peer-focus:text-[var(--color-accent-primary)] peer-not-placeholder-shown:top-0 peer-not-placeholder-shown:text-sm">Título da meta</label>
                    </div>
                    <div class="relative">
                        <input type="number" id="valor_meta" step="0.01" class="peer w-full bg-transparent border-b-2 border-white/20 pt-8 pb-5 text-2xl focus:border-[var(--color-accent-primary)] outline-none transition-all placeholder:text-transparent" placeholder=" ">
                        <label for="valor_meta" class="pointer-events-none absolute left-0 top-4 text-base text-[var(--color-text-secondary)] transition-all duration-300 peer-focus:top-0 peer-focus:text-sm peer-focus:text-[var(--color-accent-primary)] peer-not-placeholder-shown:top-0 peer-not-placeholder-shown:text-sm">Valor alvo (R$)</label>
                    </div>
                </div>
                <button id="btnCriar" class="btn-premium mt-12 w-full bg-gradient-to-r from-[var(--color-accent-primary)] via-[var(--color-accent-secondary)] to-[var(--color-accent-tertiary)] py-7 rounded-3xl text-2xl font-bold flex items-center justify-center gap-4 shadow-2xl">
                    <i class="fa-solid fa-rocket"></i> LANÇAR META ÉPICA
                </button>
            </div>
          
            <div id="mensagem"></div>
            <div id="listaMetas" class="space-y-8"></div>
        </div>
      
        <div class="lg:col-span-4">
            <div class="sticky top-28">
                <div class="glass p-9 ranking-box">
                    <h3 class="flex items-center gap-4 text-3xl font-bold mb-8"><i class="fa-solid fa-trophy text-yellow-300"></i> Ranking Global</h3>
                    <div id="ranking" class="space-y-8"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Carregando -->
    <div id="loading" class="loading-overlay hidden">
        <div class="flex flex-col items-center">
            <div class="loading-spinner"></div>
            <p class="mt-8 text-[var(--color-accent-primary)] text-lg font-medium">Processando conquista...</p>
        </div>
    </div>

    <script>const usuario_id = <?php echo (int) ($_SESSION['id_usuario'] ?? $_SESSION['user_id'] ?? 0); ?>;</script>
    <script src="../Java/metas.js"></script>
</body>
</html>