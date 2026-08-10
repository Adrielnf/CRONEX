<?php

$base = "../";

require_once "../includes/autenticar.php";

exigirPerfil("Administrador");

$conexao = require "../includes/conexao.php";

$sqlCodigo = "
    SELECT MAX(id) AS ultimo_id
    FROM produtos
";

$resultadoCodigo = mysqli_query($conexao, $sqlCodigo);

$ultimoId = 0;

if ($resultadoCodigo) {

    $dadosCodigo = mysqli_fetch_assoc($resultadoCodigo);

    if (!empty($dadosCodigo["ultimo_id"])) {
        $ultimoId = intval($dadosCodigo["ultimo_id"]);
    }
}

$proximoId = $ultimoId + 1;

$proximoCodigo = "PROD" . str_pad(
    $proximoId,
    4,
    "0",
    STR_PAD_LEFT
);

?>

<!DOCTYPE html>
<html lang="pt-br">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Cronex - Cadastro de Produtos</title>

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
                    <h1>Cadastro de Produtos</h1>
                    <p>Cadastre produtos das linhas lingerie e fitness.</p>
                </div>

                <div class="user-box">
                    <span>Administrador</span>
                </div>

            </header>

            <section class="panel">

                <div class="panel-header">

                    <h2>Novo Produto</h2>

                    <a href="listar.php">
                        Ver produtos
                    </a>

                </div>

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
                    action="salvar.php"
                    method="post"
                    class="form-cronex">

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
                                    name="codigo"
                                    value="<?= htmlspecialchars($proximoCodigo) ?>"
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
                                    value="<?= date("Y-m-d") ?>">

                            </div>

                            <div class="form-group">

                                <label for="nome">
                                    Nome do produto
                                </label>

                                <input
                                    type="text"
                                    id="nome"
                                    name="nome"
                                    placeholder="Ex: Sutiã Básico Comfort"
                                    required>

                            </div>

                        </div>

                        <div class="form-group">

                            <label for="observacoes">
                                Observações curtas
                            </label>

                            <textarea
                                id="observacoes"
                                name="observacoes"
                                placeholder="Ex: Produto com acabamento reforçado nas laterais."></textarea>

                        </div>

                    </div>

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

                                    <option value="Lingerie">
                                        Lingerie
                                    </option>

                                    <option value="Fitness">
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

                                    <option value="Sutiã">
                                        Sutiã
                                    </option>

                                    <option value="Calcinha">
                                        Calcinha
                                    </option>

                                    <option value="Conjunto">
                                        Conjunto
                                    </option>

                                    <option value="Body">
                                        Body
                                    </option>

                                    <option value="Top">
                                        Top
                                    </option>

                                    <option value="Legging">
                                        Legging
                                    </option>

                                    <option value="Short">
                                        Short
                                    </option>

                                    <option value="Cropped">
                                        Cropped
                                    </option>

                                    <option value="Regata">
                                        Regata
                                    </option>

                                    <option value="Macacão">
                                        Macacão
                                    </option>

                                    <option value="Outro">
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

                                    <option value="Ativo">
                                        Ativo
                                    </option>

                                    <option value="Inativo">
                                        Inativo
                                    </option>

                                </select>

                            </div>

                        </div>

                    </div>

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
                                    placeholder="Ex: Preto, branco, chocolate, marinho, vermelho..."></textarea>

                            </div>

                            <div class="form-group">

                                <label for="tamanhos">
                                    Tamanhos disponíveis
                                </label>

                                <textarea
                                    id="tamanhos"
                                    name="tamanhos"
                                    placeholder="Ex: P, M, G, GG..."></textarea>

                            </div>

                        </div>

                        <div class="info-box">
                            As cores e tamanhos cadastrados poderão ser utilizados para organizar as quantidades das ordens de produção.
                        </div>

                    </div>
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

                                    <option value="Renda">
                                        Renda
                                    </option>

                                    <option value="Microfibra">
                                        Microfibra
                                    </option>

                                    <option value="Suplex">
                                        Suplex
                                    </option>

                                    <option value="Poliamida">
                                        Poliamida
                                    </option>

                                    <option value="Algodão">
                                        Algodão
                                    </option>

                                    <option value="Lycra">
                                        Lycra
                                    </option>

                                    <option value="Outro">
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
                                    placeholder="Ex: Elástico 10mm">

                            </div>

                            <div class="form-group">

                                <label for="bojo">
                                    Bojo
                                </label>

                                <select
                                    id="bojo"
                                    name="bojo">

                                    <option value="Não">
                                        Não
                                    </option>

                                    <option value="Sim">
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
                                placeholder="Ex: Saco plástico individual">

                        </div>

                        <div class="info-box">
                            Os materiais cadastrados ajudam a organizar o envio dos insumos para os terceirizados.
                        </div>

                    </div>

                    <div id="producao" class="tab-content">

                        <div class="form-row">

                            <div class="form-group">

                                <label for="tempo_medio">
                                    Tempo médio por peça (minutos)
                                </label>

                                <input
                                    type="number"
                                    id="tempo_medio"
                                    name="tempo_medio"
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

                                    <option value="Baixa">
                                        Baixa
                                    </option>

                                    <option value="Média">
                                        Média
                                    </option>

                                    <option value="Alta">
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
                                placeholder="Ex: Corte, costura, aplicação de elástico, acabamento, revisão e embalagem..."></textarea>

                        </div>

                        <div class="form-group">

                            <label for="observacoes_processo">
                                Observações do processo
                            </label>

                            <textarea
                                id="observacoes_processo"
                                name="observacoes_processo"
                                placeholder="Ex: Produto exige cuidado especial no acabamento das laterais..."></textarea>

                        </div>

                        <div class="info-box">
                            O tempo médio por peça poderá ser utilizado futuramente para auxiliar no cálculo de prazos e na análise da capacidade produtiva das terceirizadas.
                        </div>

                    </div>

                    <div class="form-actions">

                        <button
                            type="submit"
                            class="btn-primary">
                            Salvar Produto
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