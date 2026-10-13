document.addEventListener("DOMContentLoaded", function () {

    // ELEMENTOS DO HTML
    const formulario = document.getElementById("formCadastro");
    const tipo = document.getElementById("tipo");
    const cargo = document.getElementById("cargo");
    const cpf = document.getElementById("cpf");
    const celular = document.getElementById("celular");
    const senha = document.getElementById("senha");
    const confirmarSenha = document.getElementById("confirmarSenha");
    const mensagem = document.getElementById("mensagem");

    // VERIFICAR SE OS ELEMENTOS ESSENCIAIS EXISTEM
    if (!formulario || !senha || !confirmarSenha || !mensagem) {
        console.error("Erro: confira os IDs fundamentais do HTML.");
        return;
    }

    // MOSTRAR MENSAGENS
    function mostrarMensagem(texto, classe) {
        mensagem.textContent = texto;
        mensagem.className = classe;
        mensagem.style.display = "block";
    }

    function limparMensagem() {
        mensagem.textContent = "";
        mensagem.className = "";
        mensagem.style.display = "none";
    }

    // MOSTRAR OU ESCONDER O CARGO
    function verificarTipo() {
        if (!tipo || !cargo) return;

        if (tipo.value === "funcionario") {
            cargo.style.display = "block";
            cargo.required = true;
        } else {
            cargo.style.display = "none";
            cargo.required = false;
            cargo.value = "";
        }
    }

    if (tipo) {
        tipo.addEventListener("change", verificarTipo);
        verificarTipo();
    }

    // PERMITIR SOMENTE NÚMEROS
    function somenteNumeros(campo, limite) {
        if (!campo) return;

        campo.setAttribute("inputmode", "numeric");
        campo.setAttribute("maxlength", limite);

        campo.addEventListener("input", function () {
            this.value = this.value.replace(/\D/g, "").slice(0, limite);
        });

        // Limpa caracteres inválidos que já estejam no campo
        campo.value = campo.value.replace(/\D/g, "").slice(0, limite);
    }

    somenteNumeros(cpf, 11);
    somenteNumeros(celular, 11);

    // VALIDAR AS SENHAS EM TEMPO REAL
    function validarSenhasTempoReal() {
        if (senha.value !== confirmarSenha.value) {
            confirmarSenha.setCustomValidity("As senhas não são iguais.");
        } else {
            confirmarSenha.setCustomValidity("");
        }
    }

    senha.addEventListener("input", validarSenhasTempoReal);
    confirmarSenha.addEventListener("input", validarSenhasTempoReal);

    // ENVIO DO FORMULÁRIO
    formulario.addEventListener("submit", async function (event) {

        // Impede o recarregamento padrão da página
        event.preventDefault();

        limparMensagem();

        // VALIDAR CAMPOS OBRIGATÓRIOS DO HTML5 PRIMEIRO
        if (!formulario.checkValidity()) {
            formulario.reportValidity();
            return;
        }

        // CONFIRMAR SENHAS
        if (senha.value !== confirmarSenha.value) {
            confirmarSenha.setCustomValidity("As senhas não são iguais.");
            mostrarMensagem("As senhas não são iguais!", "erro");
            confirmarSenha.focus();
            return;
        }

        confirmarSenha.setCustomValidity("");

        // VALIDAR CPF (11 dígitos)
        if (cpf && !/^\d{11}$/.test(cpf.value)) {
            mostrarMensagem("O CPF deve conter exatamente 11 números.", "erro");
            cpf.focus();
            return;
        }

        // VALIDAR TELEFONE (10 ou 11 dígitos)
        if (celular && !/^\d{10,11}$/.test(celular.value)) {
            mostrarMensagem("O telefone deve conter 10 ou 11 números.", "erro");
            celular.focus();
            return;
        }

        // VALIDAR CARGO DE FUNCIONÁRIO
        if (tipo && tipo.value === "funcionario" && cargo) {
            const cargosPermitidos = [
                "atendente",
                "acervo",
                "administracao"
            ];

            if (!cargosPermitidos.includes(cargo.value)) {
                mostrarMensagem("Selecione um cargo válido.", "erro");
                cargo.focus();
                return;
            }
        }

        // PREPARAR ENVIO
        const botao = formulario.querySelector('[type="submit"]');
        const dados = new FormData(formulario);

        if (botao) botao.disabled = true;

        try {
            mostrarMensagem("Enviando cadastro...", "aviso");

            // ENVIAR DADOS PARA O PHP
            const resposta = await fetch("cadastrar.php", {
                method: "POST",
                body: dados
            });

            // LER RESPOSTA DO PHP
            const resultado = await resposta.json();

            if (!resposta.ok) {
                mostrarMensagem(
                    resultado.mensagem || "Erro no servidor.",
                    "erro"
                );
                return;
            }

            // CADASTRO CONCLUÍDO
            if (resultado.sucesso === true) {
                mostrarMensagem(
                    resultado.mensagem || "Cadastro realizado com sucesso!",
                    "sucesso"
                );

                formulario.reset();
                verificarTipo();

            } else {
                // ERRO INFORMADO PELO PHP
                mostrarMensagem(
                    resultado.mensagem || "Não foi possível realizar o cadastro.",
                    "erro"
                );
            }

        } catch (erro) {
            console.error("Erro:", erro);

            mostrarMensagem(
                "Erro ao conectar com o servidor. Confira o XAMPP e o PHP.",
                "erro"
            );

        } finally {
            if (botao) botao.disabled = false;
        }
    });

});