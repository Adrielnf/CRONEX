/* =========================================================
   MODAL - CONFIRMAÇÃO DA COLETA / COLETOR
   ========================================================= */

document.addEventListener("DOMContentLoaded", function () {

    const modal = document.getElementById("modalColeta");

    if (!modal) {
        return;
    }

    const botoesAbrir =
        document.querySelectorAll(".btn-abrir-coleta");

    const botaoFechar =
        document.getElementById("fecharModalColeta");

    const botaoCancelar =
        document.getElementById("cancelarModalColeta");

    const overlay =
        document.getElementById("fecharModalOverlay");

    const campoId =
        document.getElementById("modalProducaoId");

    const campoCodigo =
        document.getElementById("modalCodigo");

    const campoEmpresa =
        document.getElementById("modalEmpresa");

    const campoProduto =
        document.getElementById("modalProduto");

    const campoQuantidade =
        document.getElementById("modalQuantidade");

    const formulario =
        document.getElementById("formConfirmarColeta");


    function abrirModal(botao) {

        campoId.value =
            botao.dataset.id;

        campoCodigo.textContent =
            botao.dataset.codigo;

        campoEmpresa.textContent =
            botao.dataset.empresa;

        campoProduto.textContent =
            botao.dataset.produto;

        campoQuantidade.textContent =
            botao.dataset.quantidade + " peças";

        modal.classList.add("ativo");

        modal.setAttribute(
            "aria-hidden",
            "false"
        );

        document.body.classList.add(
            "modal-aberto"
        );
    }


    function fecharModal() {

        modal.classList.remove("ativo");

        modal.setAttribute(
            "aria-hidden",
            "true"
        );

        document.body.classList.remove(
            "modal-aberto"
        );

        campoId.value = "";
    }


    botoesAbrir.forEach(function (botao) {

        botao.addEventListener(
            "click",
            function () {

                abrirModal(botao);

            }
        );

    });


    if (botaoFechar) {

        botaoFechar.addEventListener(
            "click",
            fecharModal
        );

    }


    if (botaoCancelar) {

        botaoCancelar.addEventListener(
            "click",
            fecharModal
        );

    }


    if (overlay) {

        overlay.addEventListener(
            "click",
            fecharModal
        );

    }


    document.addEventListener(
        "keydown",
        function (event) {

            if (
                event.key === "Escape" &&
                modal.classList.contains("ativo")
            ) {

                fecharModal();

            }

        }
    );


    if (formulario) {

        formulario.addEventListener(
            "submit",
            function () {

                const botaoConfirmar =
                    formulario.querySelector(
                        'button[type="submit"]'
                    );

                if (botaoConfirmar) {

                    botaoConfirmar.disabled = true;

                    botaoConfirmar.innerHTML =
                        '<i class="fa-solid fa-spinner fa-spin"></i> Registrando...';

                }

            }
        );

    }

});


/* =========================================================
   COLETA PARCIAL - INFORMAR PEÇAS PRONTAS / TERCEIRIZADA
   ========================================================= */

document.addEventListener("DOMContentLoaded", function () {

    const modal =
        document.getElementById("modalPecasProntas");

    const botoesAbrir =
        document.querySelectorAll(".btn-liberar-pecas");

    const botaoCancelar =
        document.getElementById("cancelarPecasProntas");

    const formulario =
        document.getElementById("formLiberarPecas");

    const campoProducaoId =
        document.getElementById("modalProducaoId");

    const campoQuantidade =
        document.getElementById("quantidade_pronta");

    const textoTotal =
        document.getElementById("modalQuantidadeTotal");

    const textoLiberada =
        document.getElementById("modalQuantidadeLiberada");

    const textoRestante =
        document.getElementById("modalQuantidadeRestante");


    if (!modal) {
        return;
    }


    /* =====================================================
       ABRIR MODAL
       ===================================================== */

    function abrirModal(botao) {

        const producaoId =
            parseInt(
                botao.dataset.id,
                10
            ) || 0;

        const total =
            parseInt(
                botao.dataset.total,
                10
            ) || 0;

        const liberadas =
            parseInt(
                botao.dataset.liberadas,
                10
            ) || 0;

        const restante =
            parseInt(
                botao.dataset.restante,
                10
            ) || 0;


        campoProducaoId.value =
            producaoId;


        textoTotal.textContent =
            total + " peças";


        textoLiberada.textContent =
            liberadas + " peças";


        textoRestante.textContent =
            "Restam " +
            restante +
            " peças para liberar.";


        campoQuantidade.value = "";

        campoQuantidade.min = "1";

        campoQuantidade.max =
            restante.toString();


        modal.classList.add("ativo");

        modal.setAttribute(
            "aria-hidden",
            "false"
        );

        document.body.classList.add(
            "modal-aberto"
        );


        setTimeout(function () {

            campoQuantidade.focus();

        }, 100);

    }


    /* =====================================================
       FECHAR MODAL
       ===================================================== */

    function fecharModal() {

        modal.classList.remove("ativo");

        modal.setAttribute(
            "aria-hidden",
            "true"
        );

        document.body.classList.remove(
            "modal-aberto"
        );

        campoProducaoId.value = "";

        campoQuantidade.value = "";

        campoQuantidade.removeAttribute(
            "max"
        );

    }


    /* =====================================================
       BOTÕES INFORMAR PEÇAS PRONTAS
       ===================================================== */

    botoesAbrir.forEach(function (botao) {

        botao.addEventListener(
            "click",
            function () {

                abrirModal(botao);

            }
        );

    });


    /* =====================================================
       CANCELAR
       ===================================================== */

    if (botaoCancelar) {

        botaoCancelar.addEventListener(
            "click",
            fecharModal
        );

    }


    /* =====================================================
       CLICAR FORA DO MODAL
       ===================================================== */

    modal.addEventListener(
        "click",
        function (evento) {

            if (evento.target === modal) {

                fecharModal();

            }

        }
    );


    /* =====================================================
       ESC
       ===================================================== */

    document.addEventListener(
        "keydown",
        function (evento) {

            if (
                evento.key === "Escape" &&
                modal.classList.contains("ativo")
            ) {

                fecharModal();

            }

        }
    );


    /* =====================================================
       VALIDAR QUANTIDADE
       ===================================================== */

    if (formulario) {

        formulario.addEventListener(
            "submit",
            function (evento) {

                const quantidade =
                    parseInt(
                        campoQuantidade.value,
                        10
                    ) || 0;

                const quantidadeMaxima =
                    parseInt(
                        campoQuantidade.max,
                        10
                    ) || 0;


                if (quantidade <= 0) {

                    evento.preventDefault();

                    alert(
                        "Informe uma quantidade válida."
                    );

                    campoQuantidade.focus();

                    return;

                }


                if (
                    quantidadeMaxima > 0 &&
                    quantidade > quantidadeMaxima
                ) {

                    evento.preventDefault();

                    alert(
                        "A quantidade informada não pode ser maior que as peças restantes."
                    );

                    campoQuantidade.focus();

                    return;

                }


                const botaoConfirmar =
                    formulario.querySelector(
                        'button[type="submit"]'
                    );


                if (botaoConfirmar) {

                    botaoConfirmar.disabled = true;

                    botaoConfirmar.innerHTML =
                        '<i class="fa-solid fa-spinner fa-spin"></i> Liberando...';

                }

            }
        );

    }

});


/* =========================================================
   ABAS - CADASTRO DE TERCEIRIZADOS
   ========================================================= */

function abrirAba(evento, nomeAba) {

    const conteudos =
        document.querySelectorAll(".tab-content");


    conteudos.forEach(function (conteudo) {

        conteudo.classList.remove("active");

    });


    const botoes =
        document.querySelectorAll(".tab-btn");


    botoes.forEach(function (botao) {

        botao.classList.remove("active");

    });


    const aba =
        document.getElementById(nomeAba);


    if (aba) {

        aba.classList.add("active");

    }


    if (
        evento &&
        evento.currentTarget
    ) {

        evento.currentTarget.classList.add(
            "active"
        );

    }

}


/* =========================================================
   MENU MOBILE
   ========================================================= */

function toggleMenuMobile() {

    const menu =
        document.querySelector(".menu-mobile");

    const botao =
        document.querySelector(".menu-mobile-toggle");

    if (!botao) {
        return;
    }

    const icone =
        botao.querySelector("i");

    if (!menu || !icone) {
        return;
    }


    menu.classList.toggle("aberto");


    if (menu.classList.contains("aberto")) {

        icone.classList.remove("fa-bars");

        icone.classList.add("fa-xmark");

    } else {

        icone.classList.remove("fa-xmark");

        icone.classList.add("fa-bars");

    }

}


/* =========================================================
   CALENDÁRIO - MODAL
   ========================================================= */

document.addEventListener("DOMContentLoaded", function () {

    const modal =
        document.getElementById(
            "modalCalendario"
        );

    const botaoAbrir =
        document.getElementById(
            "abrirCalendario"
        );

    const botaoAbrirMobile =
        document.getElementById(
            "abrirCalendarioMobile"
        );

    const botaoFechar =
        document.getElementById(
            "fecharCalendario"
        );

    const overlay =
        document.querySelector(
            ".modal-calendario-overlay"
        );

    const botaoAnterior =
        document.getElementById(
            "calendarioAnterior"
        );

    const botaoProximo =
        document.getElementById(
            "calendarioProximo"
        );

    const tituloMes =
        document.getElementById(
            "calendarioMes"
        );

    const grade =
        document.getElementById(
            "calendarioGrade"
        );


    if (
        !modal ||
        !tituloMes ||
        !grade
    ) {

        return;

    }


    /* =====================================================
       CONFIGURAÇÕES
       ===================================================== */

    let dataCalendario =
        new Date();


    const meses = [

        "Janeiro",
        "Fevereiro",
        "Março",
        "Abril",
        "Maio",
        "Junho",
        "Julho",
        "Agosto",
        "Setembro",
        "Outubro",
        "Novembro",
        "Dezembro"

    ];


    const diasSemana = [

        "Dom",
        "Seg",
        "Ter",
        "Qua",
        "Qui",
        "Sex",
        "Sáb"

    ];


    /* =====================================================
       ABRIR CALENDÁRIO
       ===================================================== */

    function abrirCalendario() {

        modal.classList.add(
            "ativo"
        );

        modal.setAttribute(
            "aria-hidden",
            "false"
        );

        document.body.classList.add(
            "modal-aberto"
        );

        renderizarCalendario();

    }


    /* =====================================================
       FECHAR CALENDÁRIO
       ===================================================== */

    function fecharCalendario() {

        modal.classList.remove(
            "ativo"
        );

        modal.setAttribute(
            "aria-hidden",
            "true"
        );

        document.body.classList.remove(
            "modal-aberto"
        );

    }


    /* =====================================================
       ABRIR DETALHES DO DIA
       ===================================================== */

    function abrirDetalhesDia(
        data,
        eventos
    ) {

        const modalDetalhes =
            document.getElementById(
                "modalDetalhesDia"
            );

        const titulo =
            document.getElementById(
                "tituloDetalhesDia"
            );

        const subtitulo =
            document.getElementById(
                "subtituloDetalhesDia"
            );

        const lista =
            document.getElementById(
                "listaDetalhesDia"
            );


        if (
            !modalDetalhes ||
            !titulo ||
            !subtitulo ||
            !lista
        ) {

            return;

        }


        /* =================================================
           FORMATA DATA
           ================================================= */

        const partes =
            data.split("-");


        const dataFormatada =
            partes[2] +
            "/" +
            partes[1] +
            "/" +
            partes[0];


        titulo.textContent =
            dataFormatada;


        /* =================================================
           SUBTÍTULO
           ================================================= */

        if (
            eventos.length === 0
        ) {

            subtitulo.textContent =
                "Nenhum evento registrado para esta data.";

        } else if (
            eventos.length === 1
        ) {

            subtitulo.textContent =
                "1 evento programado para esta data.";

        } else {

            subtitulo.textContent =
                eventos.length +
                " eventos programados para esta data.";

        }


        lista.innerHTML = "";


        /* =================================================
           NENHUM EVENTO
           ================================================= */

        if (
            eventos.length === 0
        ) {

            const vazio =
                document.createElement(
                    "div"
                );


            vazio.className =
                "detalhes-dia-vazio";


            vazio.innerHTML =
                `
                    <i class="fa-regular fa-calendar-xmark"></i>

                    <strong>
                        Nenhum evento neste dia
                    </strong>

                    <span>
                        Não há produção ou coleta registrada para esta data.
                    </span>
                `;


            lista.appendChild(
                vazio
            );


        } else {


            /* =================================================
               EVENTOS
               ================================================= */

            eventos.forEach(
                function (evento) {

                    const card =
                        document.createElement(
                            "div"
                        );


                    card.className =
                        "detalhe-evento " +
                        evento.tipo;


                    /* =========================================
                       ÍCONE
                       ========================================= */

                    let icone =
                        "fa-shirt";


                    if (
                        evento.tipo === "atrasado"
                    ) {

                        icone =
                            "fa-triangle-exclamation";

                    } else if (
                        evento.tipo === "coleta"
                    ) {

                        icone =
                            "fa-truck";

                    } else if (
                        evento.tipo === "finalizado"
                    ) {

                        icone =
                            "fa-circle-check";

                    }


                    /* =========================================
                       TEXTO DO TIPO
                       ========================================= */

                    let tipoTexto =
                        "Produção prevista";


                    if (
                        evento.descricao
                    ) {

                        tipoTexto =
                            evento.descricao;

                    }


                    /* =========================================
                       QUANTIDADE
                       ========================================= */

                    const quantidadeFormatada =
                        Number(
                            evento.quantidade
                        ).toLocaleString(
                            "pt-BR"
                        );


                    /* =========================================
                       ALERTA DE ATRASO
                       ========================================= */

                    let alertaAtraso =
                        "";


                    if (
                        evento.tipo === "atrasado"
                    ) {

                        const dataEvento =
                            new Date(
                                evento.data +
                                "T00:00:00"
                            );


                        const dataHoje =
                            new Date();


                        dataHoje.setHours(
                            0,
                            0,
                            0,
                            0
                        );


                        const diferenca =
                            dataHoje.getTime() -
                            dataEvento.getTime();


                        const diasAtraso =
                            Math.max(
                                1,
                                Math.floor(
                                    diferenca /
                                    (
                                        1000 *
                                        60 *
                                        60 *
                                        24
                                    )
                                )
                            );


                        const dataPrevista =
                            dataEvento.toLocaleDateString(
                                "pt-BR"
                            );


                        alertaAtraso =
                            `
                                <div class="detalhe-evento-alerta">

                                    <strong>
                                        <i class="fa-solid fa-triangle-exclamation"></i>
                                        ${tipoTexto}
                                    </strong>

                                    <span>
                                        A previsão era ${dataPrevista}.
                                    </span>

                                    <strong>
                                        ${diasAtraso}
                                        ${diasAtraso === 1
                                ? "dia"
                                : "dias"
                            }
                                        de atraso
                                    </strong>

                                    <small>
                                        É necessário verificar e atualizar o prazo.
                                    </small>

                                </div>
                            `;

                    }


                    /* =========================================
                       CONTEÚDO DO EVENTO
                       ========================================= */

                    const iconeDiv =
                        document.createElement(
                            "div"
                        );


                    iconeDiv.className =
                        "detalhe-evento-icone";


                    iconeDiv.innerHTML =
                        `
                            <i class="fa-solid ${icone}"></i>
                        `;


                    const conteudo =
                        document.createElement(
                            "div"
                        );


                    conteudo.className =
                        "detalhe-evento-conteudo";


                    const tituloEvento =
                        document.createElement(
                            "strong"
                        );

                    tituloEvento.textContent =
                        evento.titulo;


                    const produtoEvento =
                        document.createElement(
                            "span"
                        );

                    produtoEvento.textContent =
                        evento.produto;


                    const terceirizadaEvento =
                        document.createElement(
                            "small"
                        );

                    terceirizadaEvento.textContent =
                        evento.terceirizada;


                    conteudo.appendChild(
                        tituloEvento
                    );

                    conteudo.appendChild(
                        produtoEvento
                    );

                    conteudo.appendChild(
                        terceirizadaEvento
                    );


                    /*
                     * Adiciona o tipo do evento
                     * somente quando não for atraso.
                     */

                    if (
                        evento.tipo !== "atrasado"
                    ) {

                        const descricaoEvento =
                            document.createElement(
                                "small"
                            );

                        descricaoEvento.textContent =
                            tipoTexto;


                        conteudo.appendChild(
                            descricaoEvento
                        );

                    }


                    const quantidadeEvento =
                        document.createElement(
                            "small"
                        );

                    quantidadeEvento.textContent =
                        quantidadeFormatada +
                        " peças";


                    conteudo.appendChild(
                        quantidadeEvento
                    );


                    /*
                     * Adiciona alerta de atraso.
                     */

                    if (
                        alertaAtraso !== ""
                    ) {

                        const alerta =
                            document.createElement(
                                "div"
                            );


                        alerta.innerHTML =
                            alertaAtraso;


                        conteudo.appendChild(
                            alerta.firstElementChild
                        );

                    }


                    card.appendChild(
                        iconeDiv
                    );

                    card.appendChild(
                        conteudo
                    );


                    lista.appendChild(
                        card
                    );

                }
            );

        }


        /* =================================================
           ABRE O MODAL DE DETALHES
           ================================================= */

        modalDetalhes.classList.add(
            "ativo"
        );

        modalDetalhes.setAttribute(
            "aria-hidden",
            "false"
        );

    }


    /* =====================================================
       FECHAR DETALHES DO DIA
       ===================================================== */

    function fecharDetalhesDia() {

        const modalDetalhes =
            document.getElementById(
                "modalDetalhesDia"
            );


        if (!modalDetalhes) {
            return;
        }


        modalDetalhes.classList.remove(
            "ativo"
        );

        modalDetalhes.setAttribute(
            "aria-hidden",
            "true"
        );

    }


    /* =====================================================
       RENDERIZAR CALENDÁRIO
       ===================================================== */

    function renderizarCalendario() {

        const ano =
            dataCalendario.getFullYear();

        const mes =
            dataCalendario.getMonth();


        tituloMes.textContent =
            meses[mes] +
            " " +
            ano;


        grade.innerHTML = "";


        /* ================================================
           CABEÇALHO DOS DIAS
           ================================================ */

        diasSemana.forEach(
            function (dia) {

                const elemento =
                    document.createElement(
                        "div"
                    );


                elemento.className =
                    "calendario-dia-semana";


                elemento.textContent =
                    dia;


                grade.appendChild(
                    elemento
                );

            }
        );


        /* ================================================
           PRIMEIRO DIA DO MÊS
           ================================================ */

        const primeiroDia =
            new Date(
                ano,
                mes,
                1
            ).getDay();


        /* ================================================
           QUANTIDADE DE DIAS
           ================================================ */

        const ultimoDia =
            new Date(
                ano,
                mes + 1,
                0
            ).getDate();


        /* ================================================
           HOJE
           ================================================ */

        const hoje =
            new Date();


        const hojeDia =
            hoje.getDate();

        const hojeMes =
            hoje.getMonth();

        const hojeAno =
            hoje.getFullYear();


        /* ================================================
           EVENTOS DO PHP
           ================================================ */

        const eventos =
            Array.isArray(
                window.cronexCalendario
            )
                ? window.cronexCalendario
                : [];


        /* ================================================
           CÉLULAS VAZIAS
           ================================================ */

        for (
            let i = 0;
            i < primeiroDia;
            i++
        ) {

            const vazio =
                document.createElement(
                    "div"
                );


            vazio.className =
                "calendario-dia vazio";


            grade.appendChild(
                vazio
            );

        }


        /* ================================================
           CRIA OS DIAS
           ================================================ */

        for (
            let dia = 1;
            dia <= ultimoDia;
            dia++
        ) {

            const elemento =
                document.createElement(
                    "div"
                );


            elemento.className =
                "calendario-dia";


            /* ============================================
               DATA YYYY-MM-DD
               ============================================ */

            const mesFormatado =
                String(
                    mes + 1
                ).padStart(
                    2,
                    "0"
                );


            const diaFormatado =
                String(
                    dia
                ).padStart(
                    2,
                    "0"
                );


            const dataDia =
                ano +
                "-" +
                mesFormatado +
                "-" +
                diaFormatado;


            /* ============================================
               NÚMERO
               ============================================ */

            const numero =
                document.createElement(
                    "div"
                );


            numero.className =
                "calendario-numero";


            numero.textContent =
                dia;


            elemento.appendChild(
                numero
            );


            /* ============================================
               HOJE
               ============================================ */

            if (
                dia === hojeDia &&
                mes === hojeMes &&
                ano === hojeAno
            ) {

                elemento.classList.add(
                    "hoje"
                );

            }


            /* ============================================
               EVENTOS DO DIA
               ============================================ */

            const eventosDoDia =
                eventos.filter(
                    function (evento) {

                        return (
                            evento.data ===
                            dataDia
                        );

                    }
                );


            /* ============================================
               MOSTRA EVENTOS
               ============================================ */

            eventosDoDia.forEach(
                function (evento) {

                    const item =
                        document.createElement(
                            "div"
                        );


                    item.className =
                        "calendario-evento " +
                        evento.tipo;


                    /* ====================================
                       ÍCONE
                       ==================================== */

                    const icone =
                        document.createElement(
                            "i"
                        );


                    if (
                        evento.tipo ===
                        "atrasado"
                    ) {

                        icone.className =
                            "fa-solid fa-triangle-exclamation";

                    } else if (
                        evento.tipo ===
                        "coleta"
                    ) {

                        icone.className =
                            "fa-solid fa-truck";

                    } else if (
                        evento.tipo ===
                        "finalizado"
                    ) {

                        icone.className =
                            "fa-solid fa-circle-check";

                    } else {

                        icone.className =
                            "fa-solid fa-shirt";

                    }


                    item.appendChild(
                        icone
                    );


                    /* ====================================
                       TEXTO
                       ==================================== */

                    const texto =
                        document.createElement(
                            "span"
                        );


                    texto.textContent =
                        evento.titulo;


                    item.appendChild(
                        texto
                    );


                    /* ====================================
                       TOOLTIP
                       ==================================== */

                    item.title =
                        evento.titulo +
                        " - " +
                        evento.produto +
                        " - " +
                        evento.descricao +
                        " - " +
                        evento.quantidade +
                        " peças";


                    elemento.appendChild(
                        item
                    );

                }
            );


            /* ============================================
               CLIQUE NO DIA
               ============================================ */

            elemento.addEventListener(
                "click",
                function () {

                    abrirDetalhesDia(
                        dataDia,
                        eventosDoDia
                    );

                }
            );


            /* ============================================
               ADICIONA À GRADE
               ============================================ */

            grade.appendChild(
                elemento
            );

        }

    }


    /* =====================================================
       BOTÃO DESKTOP
       ===================================================== */

    if (botaoAbrir) {

        botaoAbrir.addEventListener(
            "click",
            function (evento) {

                /*
                 * Se o modal existir nesta página,
                 * abre normalmente.
                 */

                if (modal) {

                    evento.preventDefault();

                    abrirCalendario();

                }

                /*
                 * Se o modal não existir,
                 * deixa o link seguir normalmente
                 * para dashboard.php?calendario=1.
                 */

            }
        );

    }


    /* =====================================================
       BOTÃO MOBILE
       ===================================================== */

    if (botaoAbrirMobile) {

        botaoAbrirMobile.addEventListener(
            "click",
            function (evento) {

                /*
                 * Se o modal existir nesta página,
                 * abre normalmente.
                 */

                if (modal) {

                    evento.preventDefault();

                    abrirCalendario();

                }

            }
        );

    }


    /* =====================================================
       FECHAR CALENDÁRIO
       ===================================================== */

    if (botaoFechar) {

        botaoFechar.addEventListener(
            "click",
            fecharCalendario
        );

    }


    if (overlay) {

        overlay.addEventListener(
            "click",
            fecharCalendario
        );

    }


    /* =====================================================
       FECHAR DETALHES
       ===================================================== */

    const botaoFecharDetalhes =
        document.getElementById(
            "fecharDetalhesDia"
        );


    const overlayDetalhes =
        document.querySelector(
            ".modal-detalhes-dia-overlay"
        );


    if (botaoFecharDetalhes) {

        botaoFecharDetalhes.addEventListener(
            "click",
            fecharDetalhesDia
        );

    }


    if (overlayDetalhes) {

        overlayDetalhes.addEventListener(
            "click",
            fecharDetalhesDia
        );

    }


    /* =====================================================
       MÊS ANTERIOR
       ===================================================== */

    if (botaoAnterior) {

        botaoAnterior.addEventListener(
            "click",
            function () {

                dataCalendario.setMonth(
                    dataCalendario.getMonth() - 1
                );

                renderizarCalendario();

            }
        );

    }


    /* =====================================================
       PRÓXIMO MÊS
       ===================================================== */

    if (botaoProximo) {

        botaoProximo.addEventListener(
            "click",
            function () {

                dataCalendario.setMonth(
                    dataCalendario.getMonth() + 1
                );

                renderizarCalendario();

            }
        );

    }


    /* =====================================================
       ESC
       ===================================================== */

    document.addEventListener(
        "keydown",
        function (evento) {

            if (
                evento.key === "Escape"
            ) {

                const modalDetalhes =
                    document.getElementById(
                        "modalDetalhesDia"
                    );


                if (
                    modalDetalhes &&
                    modalDetalhes.classList.contains(
                        "ativo"
                    )
                ) {

                    fecharDetalhesDia();

                    return;

                }


                if (
                    modal.classList.contains(
                        "ativo"
                    )
                ) {

                    fecharCalendario();

                }

            }

        }
    );


    /* =====================================================
       INICIALIZA
       ===================================================== */

    renderizarCalendario();

});