<?php

$base = "../";

require_once "../includes/autenticar.php";

exigirPerfil("Administrador");

$conexao = require "../includes/conexao.php";

/*
|--------------------------------------------------------------------------
| VALIDAR ID
|--------------------------------------------------------------------------
*/

if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    header("Location: listar.php");
    exit;
}

$id = intval($_GET["id"]);

/*
|--------------------------------------------------------------------------
| BUSCAR PRODUTO
|--------------------------------------------------------------------------
*/

$sql = "SELECT * FROM produtos WHERE id = ?";

$stmt = mysqli_prepare($conexao, $sql);

if (!$stmt) {
    header("Location: listar.php");
    exit;
}

mysqli_stmt_bind_param($stmt, "i", $id);

mysqli_stmt_execute($stmt);

$resultado = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($resultado) === 0) {

    mysqli_stmt_close($stmt);

    header("Location: listar.php");
    exit;
}

$produto = mysqli_fetch_assoc($resultado);

mysqli_stmt_close($stmt);

/*
|--------------------------------------------------------------------------
| FUNÇÃO PARA EXIBIR DADOS COM SEGURANÇA
|--------------------------------------------------------------------------
*/

function e($valor) {
    return htmlspecialchars(
        $valor ?? "",
        ENT_QUOTES,
        "UTF-8"
    );
}

?>

<!DOCTYPE html>
<html lang="pt-br">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>Cronex - Editar Produto</title>

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">

    <link
        rel="stylesheet"
        href="../css/style.css">

</head>

<body>

<div class="app">

<?php

$base = "../";

include "../includes/menu.php";

?>

<main class="main-content">

    <header class="topbar">

        <div>

            <h1>Editar Produto</h1>

            <p>
                Atualize as informações do produto cadastrado.
            </p>

        </div>

        <div class="user-box">

            <span>Administrador</span>

        </div>

    </header>

    <section class="panel">

        <div class="panel-header">

            <h2>Editar Produto</h2>

            <a href="listar.php">
                Ver produtos
            </a>

        </div>

        <!-- ABAS -->

        <div class="tabs">

            <button
                type="button"
                class="tab-btn active"
                onclick="abrirAba(event, 'dados')">
                Dados gerais
            </button>

            <button
                type="button"
                class="tab-btn"
                onclick="abrirAba(event, 'classificacao')">
                Classificação
            </button>

            <button
                type="button"
                class="tab-btn"
                onclick="abrirAba(event, 'grade')">
                Grade
            </button>

            <button
                type="button"
                class="tab-btn"
                onclick="abrirAba(event, 'materiais')">
                Materiais
            </button>

            <button
                type="button"
                class="tab-btn"
                onclick="abrirAba(event, 'producao')">
                Produção
            </button>

        </div>

        <form
            action="atualizar.php"
            method="post"
            class="form-cronex">

            <input
                type="hidden"
                name="id"
                value="<?= intval($produto["id"]) ?>">

            <!-- =========================================================
                 DADOS GERAIS
            ========================================================== -->

            <div
                id="dados"
                class="tab-content active">

                <div class="form-row">

                    <div class="form-group">

                        <label for="codigo">
                            Código / SKU
                        </label>

                        <input
                            type="text"
                            id="codigo"
                            value="<?= e($produto["codigo"]) ?>"
                            readonly>

                    </div>

                    <div class="form-group">

                        <label for="data_cadastro">
                            Data de cadastro
                        </label>

                        <input
                            type="date"
                            id="data_cadastro"
                            name="data_cadastro"
                            value="<?= e($produto["data_cadastro"]) ?>"
                            required>

                    </div>

                    <div class="form-group">

                        <label for="nome">
                            Nome do produto
                        </label>

                        <input
                            type="text"
                            id="nome"
                            name="nome"
                            value="<?= e($produto["nome"]) ?>"
                            required>

                    </div>

                </div>

                <div class="form-group">

                    <label for="observacoes">
                        Observações curtas
                    </label>

                    <textarea
                        id="observacoes"
                        name="observacoes"><?= e($produto["observacoes"]) ?></textarea>

                </div>

            </div>

            <!-- =========================================================
                 CLASSIFICAÇÃO
            ========================================================== -->

            <div
                id="classificacao"
                class="tab-content">

                <div class="form-row">

                    <div class="form-group">

                        <label for="linha">
                            Linha
                        </label>

                        <select
                            id="linha"
                            name="linha"
                            required>

                            <option value="">
                                Selecione
                            </option>

                            <option
                                value="Lingerie"
                                <?= $produto["linha"] === "Lingerie" ? "selected" : "" ?>>
                                Lingerie
                            </option>

                            <option
                                value="Fitness"
                                <?= $produto["linha"] === "Fitness" ? "selected" : "" ?>>
                                Fitness
                            </option>

                        </select>

                    </div>

                    <div class="form-group">

                        <label for="categoria">
                            Categoria
                        </label>

                        <select
                            id="categoria"
                            name="categoria"
                            required>

                            <option value="">
                                Selecione
                            </option>

                            <option
                                value="Sutiã"
                                <?= $produto["categoria"] === "Sutiã" ? "selected" : "" ?>>
                                Sutiã
                            </option>

                            <option
                                value="Calcinha"
                                <?= $produto["categoria"] === "Calcinha" ? "selected" : "" ?>>
                                Calcinha
                            </option>

                            <option
                                value="Conjunto"
                                <?= $produto["categoria"] === "Conjunto" ? "selected" : "" ?>>
                                Conjunto
                            </option>

                            <option
                                value="Body"
                                <?= $produto["categoria"] === "Body" ? "selected" : "" ?>>
                                Body
                            </option>

                            <option
                                value="Top"
                                <?= $produto["categoria"] === "Top" ? "selected" : "" ?>>
                                Top
                            </option>

                            <option
                                value="Legging"
                                <?= $produto["categoria"] === "Legging" ? "selected" : "" ?>>
                                Legging
                            </option>

                            <option
                                value="Short"
                                <?= $produto["categoria"] === "Short" ? "selected" : "" ?>>
                                Short
                            </option>

                            <option
                                value="Cropped"
                                <?= $produto["categoria"] === "Cropped" ? "selected" : "" ?>>
                                Cropped
                            </option>

                            <option
                                value="Regata"
                                <?= $produto["categoria"] === "Regata" ? "selected" : "" ?>>
                                Regata
                            </option>

                            <option
                                value="Macacão"
                                <?= $produto["categoria"] === "Macacão" ? "selected" : "" ?>>
                                Macacão
                            </option>

                            <option
                                value="Outro"
                                <?= $produto["categoria"] === "Outro" ? "selected" : "" ?>>
                                Outro
                            </option>

                        </select>

                    </div>

                    <div class="form-group">

                        <label for="status">
                            Status
                        </label>

                        <select
                            id="status"
                            name="status">

                            <option
                                value="Ativo"
                                <?= $produto["status"] === "Ativo" ? "selected" : "" ?>>
                                Ativo
                            </option>

                            <option
                                value="Inativo"
                                <?= $produto["status"] === "Inativo" ? "selected" : "" ?>>
                                Inativo
                            </option>

                        </select>

                    </div>

                </div>

            </div>

            <!-- =========================================================
                 GRADE
            ========================================================== -->

            <div
                id="grade"
                class="tab-content">

                <div class="form-row">

                    <div class="form-group">

                        <label for="cores">
                            Cores disponíveis
                        </label>

                        <textarea
                            id="cores"
                            name="cores"
                            placeholder="Ex: Preto, branco, chocolate, marinho, vermelho..."><?= e($produto["cores"]) ?></textarea>

                    </div>

                    <div class="form-group">

                        <label for="tamanhos">
                            Tamanhos disponíveis
                        </label>

                        <textarea
                            id="tamanhos"
                            name="tamanhos"
                            placeholder="Ex: P, M, G, GG, 40, 42, 44, 46..."><?= e($produto["tamanhos"]) ?></textarea>

                    </div>

                </div>

                <div class="info-box">

                    As cores e tamanhos cadastrados ajudam a organizar
                    os pedidos e o controle da produção.

                </div>

            </div>

            <!-- =========================================================
                 MATERIAIS
            ========================================================== -->

            <div
                id="materiais"
                class="tab-content">

                <div class="form-row">

                    <div class="form-group">

                        <label for="tecido_principal">
                            Tecido principal
                        </label>

                        <select
                            id="tecido_principal"
                            name="tecido_principal">

                            <option value="">
                                Selecione
                            </option>

                            <option
                                value="Renda"
                                <?= $produto["tecido_principal"] === "Renda" ? "selected" : "" ?>>
                                Renda
                            </option>

                            <option
                                value="Microfibra"
                                <?= $produto["tecido_principal"] === "Microfibra" ? "selected" : "" ?>>
                                Microfibra
                            </option>

                            <option
                                value="Suplex"
                                <?= $produto["tecido_principal"] === "Suplex" ? "selected" : "" ?>>
                                Suplex
                            </option>

                            <option
                                value="Poliamida"
                                <?= $produto["tecido_principal"] === "Poliamida" ? "selected" : "" ?>>
                                Poliamida
                            </option>

                            <option
                                value="Algodão"
                                <?= $produto["tecido_principal"] === "Algodão" ? "selected" : "" ?>>
                                Algodão
                            </option>

                            <option
                                value="Lycra"
                                <?= $produto["tecido_principal"] === "Lycra" ? "selected" : "" ?>>
                                Lycra
                            </option>

                            <option
                                value="Outro"
                                <?= $produto["tecido_principal"] === "Outro" ? "selected" : "" ?>>
                                Outro
                            </option>

                        </select>

                    </div>

                    <div class="form-group">

                        <label for="consumo_tecido">
                            Consumo médio de tecido por peça
                        </label>

                        <input
                            type="text"
                            id="consumo_tecido"
                            name="consumo_tecido"
                            value="<?= e($produto["consumo_tecido"]) ?>"
                            placeholder="Ex: 0,80 m">

                    </div>

                    <div class="form-group">

                        <label for="linha_costura">
                            Linha
                        </label>

                        <input
                            type="text"
                            id="linha_costura"
                            name="linha_costura"
                            value="<?= e($produto["linha_costura"]) ?>"
                            placeholder="Ex: Poliéster preta">

                    </div>

                </div>

                <div class="form-row">

                    <div class="form-group">

                        <label for="elastico">
                            Elástico
                        </label>

                        <input
                            type="text"
                            id="elastico"
                            name="elastico"
                            value="<?= e($produto["elastico"]) ?>"
                            placeholder="Ex: Elástico 10mm">

                    </div>

                    <div class="form-group">

                        <label for="bojo">
                            Bojo
                        </label>

                        <select
                            id="bojo"
                            name="bojo">

                            <option
                                value="Não"
                                <?= $produto["bojo"] === "Não" ? "selected" : "" ?>>
                                Não
                            </option>

                            <option
                                value="Sim"
                                <?= $produto["bojo"] === "Sim" ? "selected" : "" ?>>
                                Sim
                            </option>

                        </select>

                    </div>

                    <div class="form-group">

                        <label for="alca">
                            Alça
                        </label>

                        <input
                            type="text"
                            id="alca"
                            name="alca"
                            value="<?= e($produto["alca"]) ?>"
                            placeholder="Ex: Alça regulável 12mm">

                    </div>

                </div>

                <div class="form-row">

                    <div class="form-group">

                        <label for="fecho">
                            Fecho
                        </label>

                        <input
                            type="text"
                            id="fecho"
                            name="fecho"
                            value="<?= e($produto["fecho"]) ?>"
                            placeholder="Ex: Fecho duplo">

                    </div>

                    <div class="form-group">

                        <label for="forro">
                            Forro
                        </label>

                        <input
                            type="text"
                            id="forro"
                            name="forro"
                            value="<?= e($produto["forro"]) ?>"
                            placeholder="Ex: Forro em algodão">

                    </div>

                    <div class="form-group">

                        <label for="etiqueta">
                            Etiqueta
                        </label>

                        <input
                            type="text"
                            id="etiqueta"
                            name="etiqueta"
                            value="<?= e($produto["etiqueta"]) ?>"
                            placeholder="Ex: Etiqueta interna">

                    </div>

                </div>

                <div class="form-group">

                    <label for="embalagem">
                        Embalagem
                    </label>

                    <input
                        type="text"
                        id="embalagem"
                        name="embalagem"
                        value="<?= e($produto["embalagem"]) ?>"
                        placeholder="Ex: Saco plástico individual">

                </div>

                <div class="info-box">

                    Os materiais cadastrados ajudam a organizar
                    o envio dos insumos para os terceirizados.

                </div>

            </div>

            <!-- =========================================================
                 PRODUÇÃO
            ========================================================== -->

            <div
                id="producao"
                class="tab-content">

                <div class="form-row">

                    <div class="form-group">

                        <label for="tempo_medio">
                            Tempo médio por peça (minutos)
                        </label>

                        <input
                            type="number"
                            id="tempo_medio"
                            name="tempo_medio"
                            value="<?= e($produto["tempo_medio"]) ?>"
                            min="1"
                            step="1"
                            placeholder="Ex: 18"
                            required>

                    </div>

                    <div class="form-group">

                        <label for="complexidade">
                            Complexidade
                        </label>

                        <select
                            id="complexidade"
                            name="complexidade">

                            <option
                                value="Baixa"
                                <?= $produto["complexidade"] === "Baixa" ? "selected" : "" ?>>
                                Baixa
                            </option>

                            <option
                                value="Média"
                                <?= $produto["complexidade"] === "Média" ? "selected" : "" ?>>
                                Média
                            </option>

                            <option
                                value="Alta"
                                <?= $produto["complexidade"] === "Alta" ? "selected" : "" ?>>
                                Alta
                            </option>

                        </select>

                    </div>

                </div>

                <div class="form-group">

                    <label for="etapas_producao">
                        Etapas de produção
                    </label>

                    <textarea
                        id="etapas_producao"
                        name="etapas_producao"
                        placeholder="Ex: Corte, costura, aplicação de elástico, acabamento, revisão e embalagem..."><?= e($produto["etapas_producao"]) ?></textarea>

                </div>

                <div class="form-group">

                    <label for="observacoes_processo">
                        Observações do processo
                    </label>

                    <textarea
                        id="observacoes_processo"
                        name="observacoes_processo"
                        placeholder="Ex: Produto exige cuidado especial no acabamento das laterais..."><?= e($produto["observacoes_processo"]) ?></textarea>

                </div>

                <div class="info-box">

                    O tempo médio por peça poderá ser utilizado futuramente
                    para auxiliar no cálculo de prazos e na análise da
                    capacidade produtiva das terceirizadas.

                </div>

            </div>

            <!-- =========================================================
                 AÇÕES
            ========================================================== -->

            <div class="form-actions">

                <button
                    type="submit"
                    class="btn-primary">

                    Salvar Alterações

                </button>

                <a
                    href="listar.php"
                    class="btn-secondary">

                    Cancelar

                </a>

            </div>

        </form>

    </section>

</main>

</div>

<script>

function abrirAba(event, abaId) {

    const conteudos =
        document.querySelectorAll(".tab-content");

    const botoes =
        document.querySelectorAll(".tab-btn");

    conteudos.forEach(function(conteudo) {

        conteudo.classList.remove("active");

    });

    botoes.forEach(function(botao) {

        botao.classList.remove("active");

    });

    document
        .getElementById(abaId)
        .classList
        .add("active");

    event
        .currentTarget
        .classList
        .add("active");
}

</script>

</body>

</html>