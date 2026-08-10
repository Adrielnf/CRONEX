<?php

$base = "../";

require_once "../includes/autenticar.php";

exigirPerfil("Coletor");

$conexao = require "../includes/conexao.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: coletas.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| RECEBE O ID DA COLETA
|--------------------------------------------------------------------------
*/

$coleta_id = isset($_POST["coleta_id"])
    ? intval($_POST["coleta_id"])
    : 0;

if ($coleta_id <= 0) {
    header("Location: coletas.php?erro=coleta");
    exit;
}

/*
|--------------------------------------------------------------------------
| BUSCA A COLETA E A PRODUÇÃO
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        pc.id AS coleta_id,
        pc.producao_id,
        pc.quantidade AS quantidade_coleta,
        pc.status AS status_coleta,
        pc.data_liberacao,
        pc.data_coleta,

        p.quantidade AS quantidade_total

    FROM producao_coletas pc

    INNER JOIN producao p
        ON p.id = pc.producao_id

    WHERE pc.id = ?

    LIMIT 1
";

$stmt = mysqli_prepare(
    $conexao,
    $sql
);

if (!$stmt) {
    header("Location: coletas.php?erro=banco");
    exit;
}

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $coleta_id
);

mysqli_stmt_execute($stmt);

$resultado = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($resultado) !== 1) {

    mysqli_stmt_close($stmt);

    header("Location: coletas.php?erro=coleta");
    exit;
}

$coleta = mysqli_fetch_assoc($resultado);

mysqli_stmt_close($stmt);

/*
|--------------------------------------------------------------------------
| VERIFICA SE A COLETA AINDA ESTÁ DISPONÍVEL
|--------------------------------------------------------------------------
*/

if ($coleta["status_coleta"] !== "Aguardando") {
    header("Location: coletas.php?erro=ja_coletado");
    exit;
}

$producao_id = intval(
    $coleta["producao_id"]
);

$quantidadeColeta = intval(
    $coleta["quantidade_coleta"]
);

$quantidadeTotal = intval(
    $coleta["quantidade_total"]
);

if ($quantidadeColeta <= 0) {
    header("Location: coletas.php?erro=quantidade");
    exit;
}

/*
|--------------------------------------------------------------------------
| INICIA TRANSAÇÃO
|--------------------------------------------------------------------------
*/

mysqli_begin_transaction($conexao);

try {

    /*
    |--------------------------------------------------------------------------
    | MARCA ESTA LIBERAÇÃO COMO COLETADA
    |--------------------------------------------------------------------------
    */

    $sqlAtualizarColeta = "
        UPDATE producao_coletas
        SET
            status = 'Coletado',
            data_coleta = NOW()
        WHERE id = ?
        AND status = 'Aguardando'
    ";

    $stmtAtualizarColeta = mysqli_prepare(
        $conexao,
        $sqlAtualizarColeta
    );

    if (!$stmtAtualizarColeta) {
        throw new Exception(
            "Erro ao preparar atualização da coleta."
        );
    }

    mysqli_stmt_bind_param(
        $stmtAtualizarColeta,
        "i",
        $coleta_id
    );

    if (!mysqli_stmt_execute($stmtAtualizarColeta)) {

        mysqli_stmt_close($stmtAtualizarColeta);

        throw new Exception(
            "Erro ao registrar coleta."
        );
    }

    $linhasAlteradas =
        mysqli_stmt_affected_rows(
            $stmtAtualizarColeta
        );

    mysqli_stmt_close(
        $stmtAtualizarColeta
    );

    /*
    |--------------------------------------------------------------------------
    | EVITA DUPLICIDADE
    |--------------------------------------------------------------------------
    */

    if ($linhasAlteradas !== 1) {
        throw new Exception(
            "Esta coleta já foi registrada."
        );
    }

    /*
    |--------------------------------------------------------------------------
    | CALCULA QUANTAS PEÇAS JÁ FORAM COLETADAS
    |--------------------------------------------------------------------------
    */

    $sqlTotalColetado = "
        SELECT
            COALESCE(
                SUM(quantidade),
                0
            ) AS total_coletado
        FROM producao_coletas
        WHERE producao_id = ?
        AND status = 'Coletado'
    ";

    $stmtTotal = mysqli_prepare(
        $conexao,
        $sqlTotalColetado
    );

    if (!$stmtTotal) {
        throw new Exception(
            "Erro ao calcular peças coletadas."
        );
    }

    mysqli_stmt_bind_param(
        $stmtTotal,
        "i",
        $producao_id
    );

    if (!mysqli_stmt_execute($stmtTotal)) {

        mysqli_stmt_close($stmtTotal);

        throw new Exception(
            "Erro ao calcular peças coletadas."
        );
    }

    $resultadoTotal =
        mysqli_stmt_get_result($stmtTotal);

    $dadosTotal =
        mysqli_fetch_assoc($resultadoTotal);

    mysqli_stmt_close($stmtTotal);

    $totalColetado = intval(
        $dadosTotal["total_coletado"]
    );

    /*
    |--------------------------------------------------------------------------
    | SEGURANÇA
    |--------------------------------------------------------------------------
    |
    | Nunca podemos ter mais peças coletadas do que a quantidade da produção.
    |
    */

    if ($totalColetado > $quantidadeTotal) {
        throw new Exception(
            "Quantidade coletada superior à produção."
        );
    }

    /*
    |--------------------------------------------------------------------------
    | VERIFICA SE A PRODUÇÃO FOI TOTALMENTE COLETADA
    |--------------------------------------------------------------------------
    */

    if ($totalColetado === $quantidadeTotal) {

        /*
        |----------------------------------------------------------------------
        | PRODUÇÃO COMPLETAMENTE FINALIZADA
        |----------------------------------------------------------------------
        */

        $sqlFinalizar = "
            UPDATE producao
            SET
                pecas_prontas = 'Sim',
                coletado = 'Sim',
                data_coleta = NOW(),
                status = 'Finalizada'
            WHERE id = ?
        ";

        $stmtFinalizar = mysqli_prepare(
            $conexao,
            $sqlFinalizar
        );

        if (!$stmtFinalizar) {
            throw new Exception(
                "Erro ao preparar finalização."
            );
        }

        mysqli_stmt_bind_param(
            $stmtFinalizar,
            "i",
            $producao_id
        );

        if (!mysqli_stmt_execute($stmtFinalizar)) {

            mysqli_stmt_close($stmtFinalizar);

            throw new Exception(
                "Erro ao finalizar produção."
            );
        }

        mysqli_stmt_close($stmtFinalizar);

    } else {

        /*
        |----------------------------------------------------------------------
        | COLETA PARCIAL
        |----------------------------------------------------------------------
        |
        | A produção continua aberta.
        |
        | Não marcamos a produção inteira como coletada.
        |
        */

        $sqlManterProducao = "
            UPDATE producao
            SET
                pecas_prontas = 'Nao',
                data_pecas_prontas = NULL,
                coletado = 'Nao',
                data_coleta = NULL,
                status = 'Em produção'
            WHERE id = ?
        ";

        $stmtManter = mysqli_prepare(
            $conexao,
            $sqlManterProducao
        );

        if (!$stmtManter) {
            throw new Exception(
                "Erro ao atualizar produção."
            );
        }

        mysqli_stmt_bind_param(
            $stmtManter,
            "i",
            $producao_id
        );

        if (!mysqli_stmt_execute($stmtManter)) {

            mysqli_stmt_close($stmtManter);

            throw new Exception(
                "Erro ao atualizar produção."
            );
        }

        mysqli_stmt_close($stmtManter);
    }

    /*
    |--------------------------------------------------------------------------
    | CONFIRMA TUDO
    |--------------------------------------------------------------------------
    */

    mysqli_commit($conexao);

    header(
        "Location: coletas.php?sucesso=coletado"
    );

    exit;

} catch (Throwable $erro) {

    mysqli_rollback($conexao);

    header(
        "Location: coletas.php?erro=banco"
    );

    exit;
}
?>