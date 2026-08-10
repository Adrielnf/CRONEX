<?php

$base = "../";

require_once "../includes/autenticar.php";

exigirPerfil("Terceirizada");

$conexao = require "../includes/conexao.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: producoes.php");
    exit;
}


/* =========================================================
   DADOS RECEBIDOS
   ========================================================= */

$terceirizado_id = isset($_SESSION["terceirizado_id"])
    ? intval($_SESSION["terceirizado_id"])
    : 0;

$producao_id = isset($_POST["producao_id"])
    ? intval($_POST["producao_id"])
    : 0;

$acao = isset($_POST["acao"])
    ? trim($_POST["acao"])
    : "";

$confirmacao_prazo = isset($_POST["confirmacao_prazo"])
    ? trim($_POST["confirmacao_prazo"])
    : "";

$motivo_atraso = isset($_POST["motivo_atraso"])
    ? trim($_POST["motivo_atraso"])
    : "";

$nova_previsao = isset($_POST["nova_previsao"])
    ? trim($_POST["nova_previsao"])
    : "";

$pecas_prontas = isset($_POST["pecas_prontas"])
    ? trim($_POST["pecas_prontas"])
    : "Nao";

$quantidade_pronta = isset($_POST["quantidade_pronta"])
    ? intval($_POST["quantidade_pronta"])
    : 0;


/* =========================================================
   VALIDAÇÕES INICIAIS
   ========================================================= */

if (
    $terceirizado_id <= 0 ||
    $producao_id <= 0
) {
    header("Location: producoes.php?erro=producao");
    exit;
}


/* =========================================================
   BUSCAR PRODUÇÃO
   ========================================================= */

$sqlProducao = "
    SELECT
        id,
        quantidade,
        previsao_entrega,
        confirmacao_prazo,
        motivo_atraso,
        nova_previsao,
        pecas_prontas,
        coletado
    FROM producao
    WHERE id = ?
    AND terceirizado_id = ?
    LIMIT 1
";

$stmtProducao = mysqli_prepare(
    $conexao,
    $sqlProducao
);

if (!$stmtProducao) {
    header("Location: producoes.php?erro=banco");
    exit;
}

mysqli_stmt_bind_param(
    $stmtProducao,
    "ii",
    $producao_id,
    $terceirizado_id
);

mysqli_stmt_execute($stmtProducao);

$resultadoProducao =
    mysqli_stmt_get_result($stmtProducao);

if (
    mysqli_num_rows($resultadoProducao) !== 1
) {

    mysqli_stmt_close($stmtProducao);

    header("Location: producoes.php?erro=producao");
    exit;
}

$producao =
    mysqli_fetch_assoc($resultadoProducao);

mysqli_stmt_close($stmtProducao);


/* =========================================================
   NÃO PERMITIR ALTERAÇÃO DE PRODUÇÃO FINALIZADA
   ========================================================= */

if ($producao["coletado"] === "Sim") {

    header("Location: producoes.php?erro=finalizada");
    exit;
}


/* =========================================================
   NOVA FUNÇÃO - LIBERAÇÃO PARCIAL DE PEÇAS
   ========================================================= */

if ($acao === "liberar_parcial") {

    /*
     * Só pode liberar peças depois de o prazo
     * ter sido confirmado.
     */

    if (empty($producao["confirmacao_prazo"])) {

        header("Location: producoes.php?erro=prazo");
        exit;
    }


    /*
     * A quantidade precisa ser maior que zero.
     */

    if ($quantidade_pronta <= 0) {

        header("Location: producoes.php?erro=quantidade");
        exit;
    }


    /*
     * Descobrir quanto desta produção já foi liberado.
     *
     * IMPORTANTE:
     * Somamos todas as liberações, tanto as que ainda
     * aguardam coleta quanto as que já foram coletadas.
     */

    $sqlLiberadas = "
        SELECT
            COALESCE(SUM(quantidade), 0)
            AS quantidade_liberada
        FROM producao_coletas
        WHERE producao_id = ?
    ";

    $stmtLiberadas = mysqli_prepare(
        $conexao,
        $sqlLiberadas
    );

    if (!$stmtLiberadas) {

        header("Location: producoes.php?erro=banco");
        exit;
    }

    mysqli_stmt_bind_param(
        $stmtLiberadas,
        "i",
        $producao_id
    );

    mysqli_stmt_execute($stmtLiberadas);

    $resultadoLiberadas =
        mysqli_stmt_get_result($stmtLiberadas);

    $dadosLiberados =
        mysqli_fetch_assoc($resultadoLiberadas);

    mysqli_stmt_close($stmtLiberadas);

    $quantidadeLiberada =
        intval(
            $dadosLiberados["quantidade_liberada"]
            ?? 0
        );

    $quantidadeTotal =
        intval($producao["quantidade"]);

    $quantidadeRestante =
        $quantidadeTotal
        - $quantidadeLiberada;


    /*
     * Se não existe mais saldo, nada pode ser liberado.
     */

    if ($quantidadeRestante <= 0) {

        header("Location: producoes.php?erro=saldo");
        exit;
    }


    /*
     * Não permitir liberar mais peças do que restam.
     */

    if (
        $quantidade_pronta >
        $quantidadeRestante
    ) {

        header("Location: producoes.php?erro=saldo");
        exit;
    }


    /* =====================================================
       REGISTRAR A NOVA LIBERAÇÃO
       ===================================================== */

    mysqli_begin_transaction($conexao);

    try {

        $sqlInserir = "
            INSERT INTO producao_coletas
            (
                producao_id,
                quantidade,
                status,
                data_liberacao
            )
            VALUES
            (
                ?,
                ?,
                'Aguardando',
                NOW()
            )
        ";

        $stmtInserir = mysqli_prepare(
            $conexao,
            $sqlInserir
        );

        if (!$stmtInserir) {

            throw new Exception(
                "Erro ao preparar liberação."
            );
        }

        mysqli_stmt_bind_param(
            $stmtInserir,
            "ii",
            $producao_id,
            $quantidade_pronta
        );

        if (!mysqli_stmt_execute($stmtInserir)) {

            mysqli_stmt_close($stmtInserir);

            throw new Exception(
                "Erro ao registrar liberação."
            );
        }

        mysqli_stmt_close($stmtInserir);


        /* =================================================
           VERIFICAR SE TODA A PRODUÇÃO FOI LIBERADA
           ================================================= */

        $novoTotalLiberado =
            $quantidadeLiberada
            + $quantidade_pronta;


        /*
         * Mantemos os campos antigos da tabela producao
         * para compatibilidade com as telas que ainda
         * não foram adaptadas.
         *
         * pecas_prontas = Sim SOMENTE quando todas as
         * peças da produção já tiverem sido liberadas.
         */

        if (
            $novoTotalLiberado >=
            $quantidadeTotal
        ) {

            $sqlAtualizarPecas = "
                UPDATE producao
                SET
                    pecas_prontas = 'Sim',
                    data_pecas_prontas = NOW()
                WHERE id = ?
                AND terceirizado_id = ?
                AND coletado = 'Nao'
            ";

        } else {

            $sqlAtualizarPecas = "
                UPDATE producao
                SET
                    pecas_prontas = 'Nao'
                WHERE id = ?
                AND terceirizado_id = ?
                AND coletado = 'Nao'
            ";
        }


        $stmtAtualizarPecas =
            mysqli_prepare(
                $conexao,
                $sqlAtualizarPecas
            );

        if (!$stmtAtualizarPecas) {

            throw new Exception(
                "Erro ao atualizar produção."
            );
        }

        mysqli_stmt_bind_param(
            $stmtAtualizarPecas,
            "ii",
            $producao_id,
            $terceirizado_id
        );

        if (
            !mysqli_stmt_execute(
                $stmtAtualizarPecas
            )
        ) {

            mysqli_stmt_close(
                $stmtAtualizarPecas
            );

            throw new Exception(
                "Erro ao atualizar produção."
            );
        }

        mysqli_stmt_close(
            $stmtAtualizarPecas
        );


        mysqli_commit($conexao);

        header(
            "Location: producoes.php?sucesso=liberado"
        );

        exit;

    } catch (Throwable $erro) {

        mysqli_rollback($conexao);

        header(
            "Location: producoes.php?erro=banco"
        );

        exit;
    }
}


/* =========================================================
   DAQUI PARA BAIXO:
   FLUXO ANTIGO DE CONFIRMAÇÃO / ALTERAÇÃO DO PRAZO
   ========================================================= */


/* =========================================================
   PEÇAS PRONTAS - COMPATIBILIDADE COM FLUXO ANTIGO
   ========================================================= */

if (
    $pecas_prontas === "Sim" &&
    !empty($producao["confirmacao_prazo"])
) {

    if ($producao["pecas_prontas"] === "Sim") {

        header(
            "Location: producoes.php?sucesso=atualizado"
        );

        exit;
    }

    $sqlPecas = "
        UPDATE producao
        SET
            pecas_prontas = 'Sim',
            data_pecas_prontas = NOW()
        WHERE id = ?
        AND terceirizado_id = ?
        AND coletado = 'Nao'
    ";

    $stmtPecas = mysqli_prepare(
        $conexao,
        $sqlPecas
    );

    if (!$stmtPecas) {

        header("Location: producoes.php?erro=banco");
        exit;
    }

    mysqli_stmt_bind_param(
        $stmtPecas,
        "ii",
        $producao_id,
        $terceirizado_id
    );

    if (!mysqli_stmt_execute($stmtPecas)) {

        mysqli_stmt_close($stmtPecas);

        header("Location: producoes.php?erro=banco");
        exit;
    }

    mysqli_stmt_close($stmtPecas);

    header(
        "Location: producoes.php?sucesso=atualizado"
    );

    exit;
}


/* =========================================================
   VALIDAÇÃO DO PRAZO
   ========================================================= */

$statusPrazoPermitidos = [
    "No prazo",
    "Atraso"
];

if (
    !in_array(
        $confirmacao_prazo,
        $statusPrazoPermitidos,
        true
    )
) {

    header(
        "Location: atualizar_producao.php?id="
        . $producao_id
        . "&erro=campos"
    );

    exit;
}


if (
    $pecas_prontas !== "Sim" &&
    $pecas_prontas !== "Nao"
) {

    header(
        "Location: atualizar_producao.php?id="
        . $producao_id
        . "&erro=campos"
    );

    exit;
}


/* =========================================================
   VALIDAÇÃO DE ATRASO
   ========================================================= */

if ($confirmacao_prazo === "Atraso") {

    if ($motivo_atraso === "") {

        header(
            "Location: atualizar_producao.php?id="
            . $producao_id
            . "&erro=motivo"
        );

        exit;
    }


    if ($nova_previsao === "") {

        header(
            "Location: atualizar_producao.php?id="
            . $producao_id
            . "&erro=campos"
        );

        exit;
    }


    $dataNovaPrevisao =
        DateTime::createFromFormat(
            "Y-m-d",
            $nova_previsao
        );


    $dataValida =
        $dataNovaPrevisao &&
        $dataNovaPrevisao->format("Y-m-d")
        === $nova_previsao;


    if (!$dataValida) {

        header(
            "Location: atualizar_producao.php?id="
            . $producao_id
            . "&erro=data"
        );

        exit;
    }


    $previsaoReferencia =
        !empty($producao["nova_previsao"])
            ? $producao["nova_previsao"]
            : $producao["previsao_entrega"];


    if (
        empty($previsaoReferencia) ||
        $nova_previsao <= $previsaoReferencia
    ) {

        header(
            "Location: atualizar_producao.php?id="
            . $producao_id
            . "&erro=data"
        );

        exit;
    }

} else {

    $motivo_atraso = "";
    $nova_previsao = "";
}


/* =========================================================
   ATUALIZAÇÃO DO PRAZO
   ========================================================= */

mysqli_begin_transaction($conexao);

try {

    if ($confirmacao_prazo === "Atraso") {

        $sqlAtualizar = "
            UPDATE producao
            SET
                confirmacao_prazo = ?,
                motivo_atraso = ?,
                nova_previsao = ?,
                data_confirmacao_prazo = NOW()
            WHERE id = ?
            AND terceirizado_id = ?
            AND coletado = 'Nao'
        ";

        $stmtAtualizar = mysqli_prepare(
            $conexao,
            $sqlAtualizar
        );

        if (!$stmtAtualizar) {

            throw new Exception(
                "Erro ao preparar atualização."
            );
        }


        mysqli_stmt_bind_param(
            $stmtAtualizar,
            "sssii",
            $confirmacao_prazo,
            $motivo_atraso,
            $nova_previsao,
            $producao_id,
            $terceirizado_id
        );

    } else {

        $sqlAtualizar = "
            UPDATE producao
            SET
                confirmacao_prazo = ?,
                motivo_atraso = NULL,
                nova_previsao = NULL,
                data_confirmacao_prazo = NOW()
            WHERE id = ?
            AND terceirizado_id = ?
            AND coletado = 'Nao'
        ";

        $stmtAtualizar = mysqli_prepare(
            $conexao,
            $sqlAtualizar
        );

        if (!$stmtAtualizar) {

            throw new Exception(
                "Erro ao preparar atualização."
            );
        }


        mysqli_stmt_bind_param(
            $stmtAtualizar,
            "sii",
            $confirmacao_prazo,
            $producao_id,
            $terceirizado_id
        );
    }


    if (
        !mysqli_stmt_execute(
            $stmtAtualizar
        )
    ) {

        mysqli_stmt_close(
            $stmtAtualizar
        );

        throw new Exception(
            "Erro ao atualizar prazo."
        );
    }

    mysqli_stmt_close(
        $stmtAtualizar
    );


    /* =====================================================
       COMPATIBILIDADE COM A SITUAÇÃO DAS PEÇAS
       ===================================================== */

    if (
        $pecas_prontas === "Sim" &&
        $producao["pecas_prontas"] !== "Sim"
    ) {

        $sqlPecas = "
            UPDATE producao
            SET
                pecas_prontas = 'Sim',
                data_pecas_prontas = NOW()
            WHERE id = ?
            AND terceirizado_id = ?
            AND coletado = 'Nao'
        ";

        $stmtPecas = mysqli_prepare(
            $conexao,
            $sqlPecas
        );

        if (!$stmtPecas) {

            throw new Exception(
                "Erro ao preparar situação das peças."
            );
        }


        mysqli_stmt_bind_param(
            $stmtPecas,
            "ii",
            $producao_id,
            $terceirizado_id
        );


        if (
            !mysqli_stmt_execute(
                $stmtPecas
            )
        ) {

            mysqli_stmt_close(
                $stmtPecas
            );

            throw new Exception(
                "Erro ao atualizar situação das peças."
            );
        }


        mysqli_stmt_close(
            $stmtPecas
        );

    } elseif (
        $pecas_prontas === "Nao" &&
        $producao["pecas_prontas"] !== "Sim"
    ) {

        $sqlPecas = "
            UPDATE producao
            SET
                pecas_prontas = 'Nao',
                data_pecas_prontas = NULL
            WHERE id = ?
            AND terceirizado_id = ?
            AND coletado = 'Nao'
        ";

        $stmtPecas = mysqli_prepare(
            $conexao,
            $sqlPecas
        );

        if (!$stmtPecas) {

            throw new Exception(
                "Erro ao preparar situação das peças."
            );
        }


        mysqli_stmt_bind_param(
            $stmtPecas,
            "ii",
            $producao_id,
            $terceirizado_id
        );


        if (
            !mysqli_stmt_execute(
                $stmtPecas
            )
        ) {

            mysqli_stmt_close(
                $stmtPecas
            );

            throw new Exception(
                "Erro ao atualizar situação das peças."
            );
        }


        mysqli_stmt_close(
            $stmtPecas
        );
    }


    mysqli_commit($conexao);

    header(
        "Location: producoes.php?sucesso=atualizado"
    );

    exit;

} catch (Throwable $erro) {

    mysqli_rollback($conexao);

    header(
        "Location: atualizar_producao.php?id="
        . $producao_id
        . "&erro=banco"
    );

    exit;
}
?>