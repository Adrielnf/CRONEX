<?php

if (!isset($base)) {
    $base = "";
}

?>

<aside class="sidebar">

    <div class="logo">

        <h2>CRONEX</h2>

        <span>
            Gestão Inteligente da Produção
        </span>

    </div>

    <nav class="menu">

        <a href="<?= $base ?>dashboard.php">

            <i class="fa-solid fa-house"></i>

            <span>
                Dashboard
            </span>

        </a>

        <a href="<?= $base ?>terceirizados/listar.php">

            <i class="fa-solid fa-industry"></i>

            <span>
                Terceirizados
            </span>

        </a>

        <a href="<?= $base ?>produtos/listar.php">

            <i class="fa-solid fa-shirt"></i>

            <span>
                Produtos
            </span>

        </a>

        <a href="<?= $base ?>producao/listar.php">

            <i class="fa-solid fa-gears"></i>

            <span>
                Produção
            </span>

        </a>

        <a href="<?= $base ?>previsao/listar.php">

            <i class="fa-solid fa-calendar-days"></i>

            <span>
                Previsão e Coleta
            </span>

        </a>

        <a href="<?= $base ?>relatorios/listar.php">

            <i class="fa-solid fa-chart-column"></i>

            <span>
                Relatórios
            </span>

        </a>

        <hr>

        <a href="<?= $base ?>logout.php">

            <i class="fa-solid fa-right-from-bracket"></i>

            <span>
                Sair
            </span>

        </a>

    </nav>

</aside>