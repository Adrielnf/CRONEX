<?php

$base = "../";

require_once "../includes/autenticar.php";

exigirPerfil("Administrador");

$conexao = require "../includes/conexao.php";
require "../includes/funcoes.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: cadastrar.php");
    exit;
}


/* =========================================================
   FUNÇÃO PARA RECEBER OS CAMPOS
   ========================================================= */

function campo($nome)
{
    return isset($_POST[$nome])
        ? trim($_POST[$nome])
        : "";
}


/* =========================================================
   DADOS DA TERCEIRIZADA
   ========================================================= */

$codigo = gerarCodigo(
    $conexao,
    "terceirizados",
    "TER"
);

$razao_social = campo("razao_social");
$cnpj = campo("cnpj");
$inscricao_estadual = campo("inscricao_estadual");
$responsavel = campo("responsavel");

$telefone = campo("telefone");
$whatsapp = campo("whatsapp");
$email = campo("email");

$cep = campo("cep");
$logradouro = campo("logradouro");
$numero = campo("numero");
$complemento = campo("complemento");
$bairro = campo("bairro");
$cidade = campo("cidade");
$uf = campo("uf");

$funcionarios = campo("funcionarios");
$dias_trabalho = campo("dias_trabalho");
$jornada_minutos = campo("jornada_minutos");
$produtividade = campo("produtividade");
$capacidade_dia = campo("capacidade_dia");
$tempo_entrega = campo("tempo_entrega");
$aceita_urgencia = campo("urgencia");

$processos = campo("processos");
$maquinas = campo("maquinas");

$forma_pagamento = campo("forma_pagamento");
$condicao_pagamento = campo("condicao_pagamento");
$banco = campo("banco");
$agencia = campo("agencia");
$conta = campo("conta");
$pix = campo("pix");
$favorecido = campo("favorecido");
$valor_minuto = campo("valor_minuto");

$status = campo("status");


/* =========================================================
   DADOS DE ACESSO AO PORTAL
   ========================================================= */

$usuario_acesso = campo("usuario_acesso");
$senha_acesso = campo("senha_acesso");
$confirmar_senha = campo("confirmar_senha");


/* =========================================================
   VALIDAÇÕES BÁSICAS
   ========================================================= */

if (
    $razao_social === "" ||
    $responsavel === "" ||
    $email === "" ||
    $usuario_acesso === "" ||
    $senha_acesso === "" ||
    $confirmar_senha === ""
) {

    header(
        "Location: cadastrar.php?erro=campos"
    );

    exit;
}


/* =========================================================
   VALIDAR E-MAIL
   ========================================================= */

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

    header(
        "Location: cadastrar.php?erro=email"
    );

    exit;
}


/* =========================================================
   VALIDAR SENHA
   ========================================================= */

if (strlen($senha_acesso) < 6) {

    header(
        "Location: cadastrar.php?erro=senha"
    );

    exit;
}


if ($senha_acesso !== $confirmar_senha) {

    header(
        "Location: cadastrar.php?erro=senhas"
    );

    exit;
}


/* =========================================================
   VERIFICAR SE USUÁRIO OU E-MAIL JÁ EXISTEM
   ========================================================= */

$sqlVerificar = "
    SELECT id
    FROM usuarios
    WHERE usuario = ?
       OR email = ?
    LIMIT 1
";

$stmtVerificar = mysqli_prepare(
    $conexao,
    $sqlVerificar
);

if (!$stmtVerificar) {

    header(
        "Location: cadastrar.php?erro=banco"
    );

    exit;
}


mysqli_stmt_bind_param(
    $stmtVerificar,
    "ss",
    $usuario_acesso,
    $email
);

mysqli_stmt_execute($stmtVerificar);

$resultadoVerificar =
    mysqli_stmt_get_result($stmtVerificar);


if (mysqli_num_rows($resultadoVerificar) > 0) {

    mysqli_stmt_close($stmtVerificar);

    header(
        "Location: cadastrar.php?erro=usuario"
    );

    exit;
}

mysqli_stmt_close($stmtVerificar);


/* =========================================================
   INICIAR TRANSAÇÃO

   A terceirizada e o usuário serão cadastrados juntos.
   Se alguma etapa falhar, nenhum dos dois será salvo.
   ========================================================= */

mysqli_begin_transaction($conexao);

try {


    /* =====================================================
       CADASTRAR TERCEIRIZADA
       ===================================================== */

    $sqlTerceirizado = "
        INSERT INTO terceirizados (
            codigo,
            razao_social,
            cnpj,
            inscricao_estadual,
            responsavel,
            telefone,
            whatsapp,
            email,
            cep,
            logradouro,
            numero,
            complemento,
            bairro,
            cidade,
            uf,
            funcionarios,
            dias_trabalho,
            jornada_minutos,
            produtividade,
            capacidade_dia,
            tempo_entrega,
            aceita_urgencia,
            processos,
            maquinas,
            forma_pagamento,
            condicao_pagamento,
            banco,
            agencia,
            conta,
            pix,
            favorecido,
            valor_minuto,
            status
        )
        VALUES (
            ?, ?, ?, ?, ?,
            ?, ?, ?,
            ?, ?, ?, ?, ?, ?, ?,
            ?, ?, ?, ?,
            ?, ?, ?,
            ?, ?,
            ?, ?, ?, ?, ?,
            ?, ?, ?, ?
        )
    ";


    $stmtTerceirizado = mysqli_prepare(
        $conexao,
        $sqlTerceirizado
    );


    if (!$stmtTerceirizado) {

        throw new Exception(
            "Erro ao preparar cadastro da terceirizada."
        );
    }


    mysqli_stmt_bind_param(
        $stmtTerceirizado,
        "sssssssssssssssssssssssssssssssss",
        $codigo,
        $razao_social,
        $cnpj,
        $inscricao_estadual,
        $responsavel,
        $telefone,
        $whatsapp,
        $email,
        $cep,
        $logradouro,
        $numero,
        $complemento,
        $bairro,
        $cidade,
        $uf,
        $funcionarios,
        $dias_trabalho,
        $jornada_minutos,
        $produtividade,
        $capacidade_dia,
        $tempo_entrega,
        $aceita_urgencia,
        $processos,
        $maquinas,
        $forma_pagamento,
        $condicao_pagamento,
        $banco,
        $agencia,
        $conta,
        $pix,
        $favorecido,
        $valor_minuto,
        $status
    );


    if (!mysqli_stmt_execute($stmtTerceirizado)) {

        throw new Exception(
            "Erro ao cadastrar terceirizada."
        );
    }


    /*
     * Pegamos o ID que acabou de ser criado.
     * Esse ID será vinculado ao usuário.
     */

    $terceirizado_id =
        mysqli_insert_id($conexao);


    mysqli_stmt_close(
        $stmtTerceirizado
    );


    if ($terceirizado_id <= 0) {

        throw new Exception(
            "Não foi possível identificar a terceirizada."
        );
    }


    /* =====================================================
       CRIPTOGRAFAR SENHA
       ===================================================== */

    $senha_hash =
        password_hash(
            $senha_acesso,
            PASSWORD_DEFAULT
        );


    if ($senha_hash === false) {

        throw new Exception(
            "Erro ao proteger a senha."
        );
    }


    /* =====================================================
       CRIAR USUÁRIO DA TERCEIRIZADA
       ===================================================== */

    $perfil = "Terceirizada";

    /*
     * Mantemos o usuário com o mesmo status da empresa.
     */

    $status_usuario =
        $status === "Inativo"
            ? "Inativo"
            : "Ativo";


    $sqlUsuario = "
        INSERT INTO usuarios (
            nome,
            email,
            usuario,
            senha,
            perfil,
            terceirizado_id,
            status
        )
        VALUES (
            ?, ?, ?, ?, ?, ?, ?
        )
    ";


    $stmtUsuario = mysqli_prepare(
        $conexao,
        $sqlUsuario
    );


    if (!$stmtUsuario) {

        throw new Exception(
            "Erro ao preparar usuário."
        );
    }


    mysqli_stmt_bind_param(
        $stmtUsuario,
        "sssssis",
        $responsavel,
        $email,
        $usuario_acesso,
        $senha_hash,
        $perfil,
        $terceirizado_id,
        $status_usuario
    );


    if (!mysqli_stmt_execute($stmtUsuario)) {

        throw new Exception(
            "Erro ao criar usuário."
        );
    }


    mysqli_stmt_close(
        $stmtUsuario
    );


    /* =====================================================
       CONFIRMAR TUDO
       ===================================================== */

    mysqli_commit($conexao);


    header(
        "Location: listar.php?sucesso=cadastrado"
    );

    exit;


} catch (Throwable $erro) {


    /* =====================================================
       SE QUALQUER PARTE DER ERRO, DESFAZER TUDO
       ===================================================== */

    mysqli_rollback($conexao);


    header(
        "Location: cadastrar.php?erro=cadastro"
    );

    exit;
}

?>