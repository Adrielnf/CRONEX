<?php

$base = "../";

require_once "../includes/autenticar.php";

$id = $terceirizado["id"] ?? "";
$codigo = $terceirizado["codigo"] ?? "Automático";

$razao_social = $terceirizado["razao_social"] ?? "";
$cnpj = $terceirizado["cnpj"] ?? "";
$inscricao_estadual = $terceirizado["inscricao_estadual"] ?? "";
$responsavel = $terceirizado["responsavel"] ?? "";
$status = $terceirizado["status"] ?? "Ativo";

$telefone = $terceirizado["telefone"] ?? "";
$whatsapp = $terceirizado["whatsapp"] ?? "";
$email = $terceirizado["email"] ?? "";

$cep = $terceirizado["cep"] ?? "";
$logradouro = $terceirizado["logradouro"] ?? "";
$numero = $terceirizado["numero"] ?? "";
$complemento = $terceirizado["complemento"] ?? "";
$bairro = $terceirizado["bairro"] ?? "";
$cidade = $terceirizado["cidade"] ?? "";
$uf = $terceirizado["uf"] ?? "RJ";

$funcionarios = $terceirizado["funcionarios"] ?? 1;
$dias_trabalho = $terceirizado["dias_trabalho"] ?? 5;
$jornada_minutos = $terceirizado["jornada_minutos"] ?? 480;
$produtividade = $terceirizado["produtividade"] ?? 100;
$capacidade_dia = $terceirizado["capacidade_dia"] ?? "";
$tempo_entrega = $terceirizado["tempo_entrega"] ?? "";
$aceita_urgencia = $terceirizado["aceita_urgencia"] ?? "Não";

$processos = $terceirizado["processos"] ?? "";
$maquinas = $terceirizado["maquinas"] ?? "";

$forma_pagamento = $terceirizado["forma_pagamento"] ?? "PIX";
$condicao_pagamento = $terceirizado["condicao_pagamento"] ?? "Por produção";
$banco = $terceirizado["banco"] ?? "";
$agencia = $terceirizado["agencia"] ?? "";
$conta = $terceirizado["conta"] ?? "";
$pix = $terceirizado["pix"] ?? "";
$favorecido = $terceirizado["favorecido"] ?? "";
$valor_minuto = $terceirizado["valor_minuto"] ?? "";

/*
|--------------------------------------------------------------------------
| Dados de acesso
|--------------------------------------------------------------------------
| No cadastro de uma nova terceirizada estes campos ficam vazios.
| Na edição, por enquanto, não alteraremos o login por este formulário.
|--------------------------------------------------------------------------
*/

$usuario_acesso = "";

?>

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
        onclick="abrirAba(event, 'capacidade')">
        Capacidade produtiva
    </button>

    <button
        type="button"
        class="tab-btn"
        onclick="abrirAba(event, 'financeiro')">
        Financeiro
    </button>

    <?php if (empty($id)): ?>

        <button
            type="button"
            class="tab-btn"
            onclick="abrirAba(event, 'acesso')">
            Acesso ao Portal
        </button>

    <?php endif; ?>

</div>


<form
    action="<?= $acaoFormulario ?>"
    method="post"
    class="form-cronex">

    <input
        type="hidden"
        name="id"
        value="<?= $id ?>">


    <!-- =====================================================
         DADOS GERAIS
         ===================================================== -->

    <div
        id="dados"
        class="tab-content active">

        <div class="form-row">

            <div class="form-group">

                <label>Código</label>

                <input
                    type="text"
                    value="<?= $codigo ?>"
                    readonly>

            </div>

            <div class="form-group">

                <label>Razão social / Nome</label>

                <input
                    type="text"
                    name="razao_social"
                    value="<?= $razao_social ?>"
                    required>

            </div>

            <div class="form-group">

                <label>CPF/CNPJ</label>

                <input
                    type="text"
                    name="cnpj"
                    value="<?= $cnpj ?>">

            </div>

        </div>


        <div class="form-row">

            <div class="form-group">

                <label>Inscrição estadual</label>

                <input
                    type="text"
                    name="inscricao_estadual"
                    value="<?= $inscricao_estadual ?>">

            </div>

            <div class="form-group">

                <label>Responsável</label>

                <input
                    type="text"
                    name="responsavel"
                    value="<?= $responsavel ?>"
                    required>

            </div>

            <div class="form-group">

                <label>Status</label>

                <select name="status">

                    <option
                        value="Ativo"
                        <?= $status == "Ativo" ? "selected" : "" ?>>
                        Ativo
                    </option>

                    <option
                        value="Inativo"
                        <?= $status == "Inativo" ? "selected" : "" ?>>
                        Inativo
                    </option>

                </select>

            </div>

        </div>


        <div class="form-row">

            <div class="form-group">

                <label>Telefone</label>

                <input
                    type="text"
                    name="telefone"
                    value="<?= $telefone ?>">

            </div>

            <div class="form-group">

                <label>WhatsApp</label>

                <input
                    type="text"
                    name="whatsapp"
                    value="<?= $whatsapp ?>">

            </div>

            <div class="form-group">

                <label>E-mail</label>

                <input
                    type="email"
                    name="email"
                    value="<?= $email ?>"
                    required>

            </div>

        </div>


        <div class="form-row">

            <div class="form-group">

                <label>CEP</label>

                <input
                    type="text"
                    name="cep"
                    value="<?= $cep ?>">

            </div>

            <div class="form-group">

                <label>Endereço</label>

                <input
                    type="text"
                    name="logradouro"
                    value="<?= $logradouro ?>">

            </div>

            <div class="form-group">

                <label>Número</label>

                <input
                    type="text"
                    name="numero"
                    value="<?= $numero ?>">

            </div>

        </div>


        <div class="form-row">

            <div class="form-group">

                <label>Complemento</label>

                <input
                    type="text"
                    name="complemento"
                    value="<?= $complemento ?>">

            </div>

            <div class="form-group">

                <label>Bairro</label>

                <input
                    type="text"
                    name="bairro"
                    value="<?= $bairro ?>">

            </div>

            <div class="form-group">

                <label>Cidade</label>

                <input
                    type="text"
                    name="cidade"
                    value="<?= $cidade ?>">

            </div>

            <div class="form-group">

                <label>UF</label>

                <select name="uf">

                    <option value="RJ" <?= $uf == "RJ" ? "selected" : "" ?>>RJ</option>
                    <option value="SP" <?= $uf == "SP" ? "selected" : "" ?>>SP</option>
                    <option value="MG" <?= $uf == "MG" ? "selected" : "" ?>>MG</option>
                    <option value="ES" <?= $uf == "ES" ? "selected" : "" ?>>ES</option>
                    <option value="PR" <?= $uf == "PR" ? "selected" : "" ?>>PR</option>
                    <option value="SC" <?= $uf == "SC" ? "selected" : "" ?>>SC</option>
                    <option value="RS" <?= $uf == "RS" ? "selected" : "" ?>>RS</option>

                </select>

            </div>

        </div>

    </div>


    <!-- =====================================================
         CAPACIDADE PRODUTIVA
         ===================================================== -->

    <div
        id="capacidade"
        class="tab-content">

        <div class="form-row">

            <div class="form-group">

                <label>Quantidade de funcionários</label>

                <input
                    type="number"
                    name="funcionarios"
                    value="<?= $funcionarios ?>"
                    min="1">

            </div>

            <div class="form-group">

                <label>Dias trabalhados por semana</label>

                <input
                    type="number"
                    name="dias_trabalho"
                    value="<?= $dias_trabalho ?>"
                    min="1"
                    max="7">

            </div>

            <div class="form-group">

                <label>Jornada diária (minutos)</label>

                <input
                    type="number"
                    name="jornada_minutos"
                    value="<?= $jornada_minutos ?>"
                    min="1">

            </div>

        </div>


        <div class="form-row">

            <div class="form-group">

                <label>Produtividade (%)</label>

                <input
                    type="number"
                    name="produtividade"
                    value="<?= $produtividade ?>"
                    min="1"
                    max="100">

            </div>

            <div class="form-group">

                <label>Capacidade máxima por dia (peças)</label>

                <input
                    type="number"
                    name="capacidade_dia"
                    value="<?= $capacidade_dia ?>"
                    min="1">

            </div>

            <div class="form-group">

                <label>Tempo médio de entrega (dias)</label>

                <input
                    type="number"
                    name="tempo_entrega"
                    value="<?= $tempo_entrega ?>"
                    min="1">

            </div>

        </div>


        <div class="form-row">

            <div class="form-group">

                <label>Aceita urgência?</label>

                <select name="urgencia">

                    <option
                        value="Não"
                        <?= $aceita_urgencia == "Não" ? "selected" : "" ?>>
                        Não
                    </option>

                    <option
                        value="Sim"
                        <?= $aceita_urgencia == "Sim" ? "selected" : "" ?>>
                        Sim
                    </option>

                </select>

            </div>

        </div>


        <div class="form-group">

            <label>Processos realizados</label>

            <textarea name="processos"><?= $processos ?></textarea>

        </div>


        <div class="form-group">

            <label>Máquinas disponíveis</label>

            <textarea name="maquinas"><?= $maquinas ?></textarea>

        </div>


        <div class="info-box">

            Esses dados serão usados para calcular a capacidade produtiva
            e ajudar na previsão de conclusão dos pedidos.

        </div>

    </div>


    <!-- =====================================================
         FINANCEIRO
         ===================================================== -->

    <div
        id="financeiro"
        class="tab-content">

        <div class="form-row">

            <div class="form-group">

                <label>Forma de pagamento</label>

                <select name="forma_pagamento">

                    <option
                        value="PIX"
                        <?= $forma_pagamento == "PIX" ? "selected" : "" ?>>
                        PIX
                    </option>

                    <option
                        value="Transferência"
                        <?= $forma_pagamento == "Transferência" ? "selected" : "" ?>>
                        Transferência
                    </option>

                    <option
                        value="Dinheiro"
                        <?= $forma_pagamento == "Dinheiro" ? "selected" : "" ?>>
                        Dinheiro
                    </option>

                    <option
                        value="Boleto"
                        <?= $forma_pagamento == "Boleto" ? "selected" : "" ?>>
                        Boleto
                    </option>

                </select>

            </div>


            <div class="form-group">

                <label>Condição de pagamento</label>

                <select name="condicao_pagamento">

                    <option
                        value="Por produção"
                        <?= $condicao_pagamento == "Por produção" ? "selected" : "" ?>>
                        Por produção
                    </option>

                    <option
                        value="Semanal"
                        <?= $condicao_pagamento == "Semanal" ? "selected" : "" ?>>
                        Semanal
                    </option>

                    <option
                        value="Quinzenal"
                        <?= $condicao_pagamento == "Quinzenal" ? "selected" : "" ?>>
                        Quinzenal
                    </option>

                    <option
                        value="Mensal"
                        <?= $condicao_pagamento == "Mensal" ? "selected" : "" ?>>
                        Mensal
                    </option>

                </select>

            </div>


            <div class="form-group">

                <label>Valor por minuto (R$)</label>

                <input
                    type="number"
                    name="valor_minuto"
                    value="<?= $valor_minuto ?>"
                    step="0.01"
                    min="0">

            </div>

        </div>


        <div class="form-row">

            <div class="form-group">

                <label>Banco</label>

                <input
                    type="text"
                    name="banco"
                    value="<?= $banco ?>">

            </div>

            <div class="form-group">

                <label>Agência</label>

                <input
                    type="text"
                    name="agencia"
                    value="<?= $agencia ?>">

            </div>

            <div class="form-group">

                <label>Conta</label>

                <input
                    type="text"
                    name="conta"
                    value="<?= $conta ?>">

            </div>

        </div>


        <div class="form-row">

            <div class="form-group">

                <label>Chave PIX</label>

                <input
                    type="text"
                    name="pix"
                    value="<?= $pix ?>">

            </div>

            <div class="form-group">

                <label>Favorecido</label>

                <input
                    type="text"
                    name="favorecido"
                    value="<?= $favorecido ?>">

            </div>

        </div>


        <div class="info-box">

            Os dados financeiros serão utilizados para facilitar
            o controle de pagamento dos terceirizados.

        </div>

    </div>


    <!-- =====================================================
         ACESSO AO PORTAL
         Somente no cadastro de uma nova terceirizada
         ===================================================== -->

    <?php if (empty($id)): ?>

        <div
            id="acesso"
            class="tab-content">

            <div class="info-box">

                <strong>
                    <i class="fa-solid fa-lock"></i>
                    Acesso ao Portal da Terceirizada
                </strong>

                <br><br>

                Crie o usuário e a senha que serão utilizados
                pela terceirizada para acompanhar suas produções.

            </div>


            <div class="form-row">

                <div class="form-group">

                    <label>
                        Usuário de acesso
                    </label>

                    <input
                        type="text"
                        name="usuario_acesso"
                        value="<?= $usuario_acesso ?>"
                        placeholder="Ex.: powerfit"
                        autocomplete="off"
                        required>

                </div>


                <div class="form-group">

                    <label>
                        Senha
                    </label>

                    <input
                        type="password"
                        name="senha_acesso"
                        placeholder="Digite uma senha"
                        autocomplete="new-password"
                        minlength="6"
                        required>

                </div>


                <div class="form-group">

                    <label>
                        Confirmar senha
                    </label>

                    <input
                        type="password"
                        name="confirmar_senha"
                        placeholder="Digite novamente"
                        autocomplete="new-password"
                        minlength="6"
                        required>

                </div>

            </div>


            <div class="info-box">

                <i class="fa-solid fa-circle-info"></i>

                O acesso será vinculado automaticamente à
                terceirizada cadastrada.

            </div>

        </div>

    <?php endif; ?>


    <!-- =====================================================
         BOTÕES
         ===================================================== -->

    <div class="form-actions">

        <button
            type="submit"
            class="btn-primary">

            <i class="fa-solid fa-floppy-disk"></i>

            <?= $textoBotao ?>

        </button>

        <a
            href="listar.php"
            class="btn-secondary">

            Cancelar

        </a>

    </div>

</form>