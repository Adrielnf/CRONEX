<?php

function gerarCodigo($conexao, $tabela, $prefixo) {
    $sql = "SELECT id FROM $tabela ORDER BY id DESC LIMIT 1";
    $resultado = mysqli_query($conexao, $sql);

    if ($resultado && mysqli_num_rows($resultado) > 0) {
        $linha = mysqli_fetch_assoc($resultado);
        $proximoId = $linha["id"] + 1;
    } else {
        $proximoId = 1;
    }

    return $prefixo . str_pad($proximoId, 4, "0", STR_PAD_LEFT);
}

function limparTexto($texto) {
    return trim($texto);
}

function formatarMoeda($valor) {
    return "R$ " . number_format($valor, 2, ",", ".");
}

function calcularTempoTotalProducao($quantidade, $tempoMedio) {
    $quantidade = max(0, intval($quantidade));
    $tempoMedio = max(0, floatval($tempoMedio));

    return $quantidade * $tempoMedio;
}

function calcularCapacidadeMinutosDia(
    $funcionarios,
    $jornadaMinutos,
    $produtividade = 100
) {
    $funcionarios = max(1, intval($funcionarios));
    $jornadaMinutos = max(1, intval($jornadaMinutos));

    $produtividade = floatval($produtividade);

    if ($produtividade <= 0) {
        $produtividade = 100;
    }

    return $funcionarios
        * $jornadaMinutos
        * ($produtividade / 100);
}

function calcularDiasProducao(
    $tempoTotalMinutos,
    $capacidadeMinutosDia
) {
    if ($capacidadeMinutosDia <= 0 || $tempoTotalMinutos <= 0) {
        return 0;
    }

    return (int) ceil(
        $tempoTotalMinutos / $capacidadeMinutosDia
    );
}

function calcularPrevisaoEntrega(
    $dataEnvio,
    $diasProducao,
    $diasTrabalho = 5
) {
    if (empty($dataEnvio)) {
        return null;
    }

    $diasProducao = max(1, intval($diasProducao));
    $diasTrabalho = intval($diasTrabalho);

    if ($diasTrabalho < 1 || $diasTrabalho > 7) {
        $diasTrabalho = 5;
    }

    $data = new DateTime($dataEnvio);
    $diasAdicionados = 0;

    while ($diasAdicionados < $diasProducao) {
        $data->modify("+1 day");

        $diaSemana = intval($data->format("N"));

        if ($diaSemana <= $diasTrabalho) {
            $diasAdicionados++;
        }
    }

    return $data->format("Y-m-d");
}

function calcularConsumoTecido(
    $quantidade,
    $consumoPorPeca
) {
    $quantidade = max(0, intval($quantidade));
    $consumoPorPeca = max(0, floatval($consumoPorPeca));

    return $quantidade * $consumoPorPeca;
}

function calcularTempoEmbalagem(
    $quantidade,
    $tempoEmbalagem
) {
    $quantidade = max(0, intval($quantidade));
    $tempoEmbalagem = max(0, floatval($tempoEmbalagem));

    return $quantidade * $tempoEmbalagem;
}

function calcularCargaTotal(
    $quantidade,
    $tempoMedio,
    $tempoEmbalagem = 0
) {
    $tempoProducao = calcularTempoTotalProducao(
        $quantidade,
        $tempoMedio
    );

    $tempoEmbalagemTotal = calcularTempoEmbalagem(
        $quantidade,
        $tempoEmbalagem
    );

    return $tempoProducao + $tempoEmbalagemTotal;
}

function formatarTempoProducao($minutos) {
    $minutos = max(0, intval(round($minutos)));

    $horas = floor($minutos / 60);
    $minutosRestantes = $minutos % 60;

    return $horas . "h "
        . str_pad(
            $minutosRestantes,
            2,
            "0",
            STR_PAD_LEFT
        )
        . "min";
}

function calcularDadosProducao(
    $quantidade,
    $tempoMedio,
    $tempoEmbalagem,
    $funcionarios,
    $jornadaMinutos,
    $produtividade,
    $diasTrabalho,
    $dataEnvio,
    $consumoTecido = 0
) {
    $tempoProducao = calcularTempoTotalProducao(
        $quantidade,
        $tempoMedio
    );

    $tempoEmbalagemTotal = calcularTempoEmbalagem(
        $quantidade,
        $tempoEmbalagem
    );

    $cargaTotal = $tempoProducao + $tempoEmbalagemTotal;

    $capacidadeDia = calcularCapacidadeMinutosDia(
        $funcionarios,
        $jornadaMinutos,
        $produtividade
    );

    $diasProducao = calcularDiasProducao(
        $cargaTotal,
        $capacidadeDia
    );

    $previsaoEntrega = calcularPrevisaoEntrega(
        $dataEnvio,
        $diasProducao,
        $diasTrabalho
    );

    $tecidoTotal = calcularConsumoTecido(
        $quantidade,
        $consumoTecido
    );

    return [
        "tempo_producao" => $tempoProducao,
        "tempo_embalagem" => $tempoEmbalagemTotal,
        "carga_total" => $cargaTotal,
        "carga_total_formatada" => formatarTempoProducao($cargaTotal),
        "capacidade_minutos_dia" => $capacidadeDia,
        "dias_producao" => $diasProducao,
        "previsao_entrega" => $previsaoEntrega,
        "consumo_tecido_total" => $tecidoTotal
    ];
}

function calcularCargaOcupadaTerceirizado(
    $conexao,
    $terceirizadoId,
    $dataReferencia,
    $ignorarProducaoId = 0
) {
    $terceirizadoId = intval($terceirizadoId);
    $ignorarProducaoId = intval($ignorarProducaoId);

    if ($terceirizadoId <= 0 || empty($dataReferencia)) {
        return 0;
    }

    $sql = "
        SELECT
            pr.id,
            pr.quantidade,
            pr.data_envio,
            p.tempo_medio,
            p.tempo_embalagem
        FROM producao pr
        INNER JOIN produtos p
            ON p.id = pr.produto_id
        WHERE pr.terceirizado_id = ?
        AND pr.status IN ('Aguardando', 'Em produção')
        AND pr.data_envio <= ?
    ";

    if ($ignorarProducaoId > 0) {
        $sql .= " AND pr.id <> ?";
    }

    $sql .= "
        ORDER BY
            pr.data_envio ASC,
            pr.id ASC
    ";

    $stmt = mysqli_prepare($conexao, $sql);

    if (!$stmt) {
        return 0;
    }

    if ($ignorarProducaoId > 0) {
        mysqli_stmt_bind_param(
            $stmt,
            "isi",
            $terceirizadoId,
            $dataReferencia,
            $ignorarProducaoId
        );
    } else {
        mysqli_stmt_bind_param(
            $stmt,
            "is",
            $terceirizadoId,
            $dataReferencia
        );
    }

    mysqli_stmt_execute($stmt);

    $resultado = mysqli_stmt_get_result($stmt);

    $cargaTotal = 0;

    while ($producao = mysqli_fetch_assoc($resultado)) {
        $cargaTotal += calcularCargaTotal(
            $producao["quantidade"],
            $producao["tempo_medio"],
            $producao["tempo_embalagem"]
        );
    }

    mysqli_stmt_close($stmt);

    return $cargaTotal;
}

function calcularFilaTerceirizado(
    $conexao,
    $terceirizadoId,
    $funcionarios,
    $jornadaMinutos,
    $produtividade,
    $diasTrabalho,
    $dataReferencia,
    $ignorarProducaoId = 0
) {
    $cargaOcupada = calcularCargaOcupadaTerceirizado(
        $conexao,
        $terceirizadoId,
        $dataReferencia,
        $ignorarProducaoId
    );

    $capacidadeDia = calcularCapacidadeMinutosDia(
        $funcionarios,
        $jornadaMinutos,
        $produtividade
    );

    $diasOcupados = calcularDiasProducao(
        $cargaOcupada,
        $capacidadeDia
    );

    if ($diasOcupados > 0) {
        $dataDisponivel = calcularPrevisaoEntrega(
            $dataReferencia,
            $diasOcupados,
            $diasTrabalho
        );
    } else {
        $dataDisponivel = $dataReferencia;
    }

    return [
        "carga_ocupada" => $cargaOcupada,
        "carga_ocupada_formatada" => formatarTempoProducao(
            $cargaOcupada
        ),
        "capacidade_minutos_dia" => $capacidadeDia,
        "dias_ocupados" => $diasOcupados,
        "data_disponivel" => $dataDisponivel
    ];
}

function calcularPrevisaoComFila(
    $conexao,
    $terceirizadoId,
    $quantidade,
    $tempoMedio,
    $tempoEmbalagem,
    $funcionarios,
    $jornadaMinutos,
    $produtividade,
    $diasTrabalho,
    $dataEnvio,
    $tempoEntrega = 0,
    $ignorarProducaoId = 0
) {
    $fila = calcularFilaTerceirizado(
        $conexao,
        $terceirizadoId,
        $funcionarios,
        $jornadaMinutos,
        $produtividade,
        $diasTrabalho,
        $dataEnvio,
        $ignorarProducaoId
    );

    $cargaNovaProducao = calcularCargaTotal(
        $quantidade,
        $tempoMedio,
        $tempoEmbalagem
    );

    $capacidadeDia = $fila["capacidade_minutos_dia"];

    $diasNovaProducao = calcularDiasProducao(
        $cargaNovaProducao,
        $capacidadeDia
    );

    $previsaoEntrega = calcularPrevisaoEntrega(
        $fila["data_disponivel"],
        $diasNovaProducao,
        $diasTrabalho
    );

    $tempoEntrega = max(0, intval($tempoEntrega));

    if ($tempoEntrega > 0 && !empty($previsaoEntrega)) {
        $previsaoEntrega = calcularPrevisaoEntrega(
            $previsaoEntrega,
            $tempoEntrega,
            $diasTrabalho
        );
    }

    return [
        "carga_ocupada" => $fila["carga_ocupada"],
        "carga_ocupada_formatada" => $fila["carga_ocupada_formatada"],
        "dias_ocupados" => $fila["dias_ocupados"],
        "data_disponivel" => $fila["data_disponivel"],
        "carga_nova_producao" => $cargaNovaProducao,
        "carga_nova_formatada" => formatarTempoProducao(
            $cargaNovaProducao
        ),
        "dias_nova_producao" => $diasNovaProducao,
        "previsao_entrega" => $previsaoEntrega
    ];
}

?>