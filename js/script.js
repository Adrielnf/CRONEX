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

    /* Localiza todos os conteúdos das abas */

    const conteudos =
        document.querySelectorAll(".tab-content");


    /* Esconde todas as abas */

    conteudos.forEach(function (conteudo) {

        conteudo.classList.remove("active");

    });


    /* Localiza todos os botões */

    const botoes =
        document.querySelectorAll(".tab-btn");


    /* Remove o destaque de todos os botões */

    botoes.forEach(function (botao) {

        botao.classList.remove("active");

    });


    /* Localiza a aba que deverá ser aberta */

    const aba =
        document.getElementById(nomeAba);


    /* Se a aba existir, mostra */

    if (aba) {

        aba.classList.add("active");

    }


    /* Destaca o botão que foi clicado */

    if (
        evento &&
        evento.currentTarget
    ) {

        evento.currentTarget.classList.add(
            "active"
        );

    }

}