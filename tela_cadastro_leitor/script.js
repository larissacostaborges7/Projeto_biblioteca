
document.addEventListener("DOMContentLoaded", () => {
    const form = document.getElementById("formCadastro");
    const dataNascimento = document.getElementById("dataNascimento");
    const secaoResponsavel = document.getElementById("secaoResponsavel");
    const senha = document.getElementById("senha");
    const confirmarSenha = document.getElementById("confirmarSenha");
    const mensagem = document.getElementById("mensagem");
    const mensagemConfirmacao = document.getElementById("mensagemConfirmacao");

    if (!form) return;

    function mostrarMensagem(texto, tipo = "erro") {
        mensagem.textContent = texto;
        mensagem.className = tipo;
    }

    function somenteNumeros(valor) {
        return valor.replace(/\D/g, "");
    }

    function cpfValido(valor) {
        const cpf = somenteNumeros(valor);

        if (cpf.length !== 11 || /^(\d)\1{10}$/.test(cpf)) return false;

        let soma = 0;

        for (let i = 0; i < 9; i++) {
            soma += Number(cpf[i]) * (10 - i);
        }

        let digito = (soma * 10) % 11;
        if (digito === 10) digito = 0;
        if (digito !== Number(cpf[9])) return false;

        soma = 0;

        for (let i = 0; i < 10; i++) {
            soma += Number(cpf[i]) * (11 - i);
        }

        digito = (soma * 10) % 11;
        if (digito === 10) digito = 0;

        return digito === Number(cpf[10]);
    }

    function atualizarResponsavel() {
        const valor = dataNascimento.value;
        const nascimento = valor ? new Date(valor + "T00:00:00") : null;

        let idade = null;

        if (nascimento && !Number.isNaN(nascimento.getTime())) {
            const hoje = new Date();

            idade = hoje.getFullYear() - nascimento.getFullYear();

            if (
                hoje.getMonth() < nascimento.getMonth() ||
                (
                    hoje.getMonth() === nascimento.getMonth() &&
                    hoje.getDate() < nascimento.getDate()
                )
            ) {
                idade--;
            }
        }

        const menor = idade !== null && idade < 18;

        secaoResponsavel.hidden = menor === false;

        secaoResponsavel.querySelectorAll("input").forEach((campo) => {
            campo.required = menor;

            if (!menor) campo.value = "";
        });
    }

    dataNascimento.addEventListener("change", atualizarResponsavel);

    document.querySelectorAll(".alternar-senha").forEach((botao) => {
        botao.addEventListener("click", () => {
            const campo = document.getElementById(botao.dataset.alvo);
            if (!campo) return;

            const mostrar = campo.type === "password";
            campo.type = mostrar ? "text" : "password";

            botao.setAttribute("aria-pressed", String(mostrar));
            botao.setAttribute(
                "aria-label",
                mostrar ? "Esconder senha" : "Mostrar senha"
            );
        });
    });

    function validarSenha() {
        const valor = senha.value;

        const requisitos = {
            reqTamanho: valor.length >= 8,
            reqMaiuscula: /[A-Z]/.test(valor),
            reqMinuscula: /[a-z]/.test(valor),
            reqNumero: /\d/.test(valor),
            reqEspecial: /[^A-Za-z0-9]/.test(valor)
        };

        Object.entries(requisitos).forEach(([id, valido]) => {
            const item = document.getElementById(id);

            if (item) {
                item.textContent =
                    (valido ? "✓ " : "○ ") +
                    {
                        reqTamanho: "Pelo menos 8 caracteres",
                        reqMaiuscula: "Uma letra maiúscula",
                        reqMinuscula: "Uma letra minúscula",
                        reqNumero: "Um número",
                        reqEspecial: "Um caractere especial"
                    }[id];

                item.classList.toggle("validado", valido);
            }
        });

        return Object.values(requisitos).every(Boolean);
    }

    senha.addEventListener("input", validarSenha);

    confirmarSenha.addEventListener("input", () => {
        const iguais = senha.value === confirmarSenha.value;

        mensagemConfirmacao.textContent = iguais
            ? "As senhas conferem."
            : "As senhas estão diferentes.";

        mensagemConfirmacao.className = iguais ? "sucesso" : "erro";
    });

    form.addEventListener("submit", async (evento) => {
        evento.preventDefault();
        mostrarMensagem("");

        const cpf = form.elements.cpf.value;
        const celular = form.elements.celular.value;

        if (!cpfValido(cpf)) {
            mostrarMensagem("Confira o CPF do leitor.");
            return;
        }

        const numerosCelular = somenteNumeros(celular);

        if (![10, 11].includes(numerosCelular.length)) {
            mostrarMensagem("Confira o número do celular.");
            return;
        }

        if (!validarSenha()) {
            mostrarMensagem("A senha não atende a todos os requisitos.");
            return;
        }

        if (senha.value !== confirmarSenha.value) {
            mostrarMensagem("As senhas não conferem.");
            return;
        }

        if (dataNascimento.value) {
            const nascimento = new Date(dataNascimento.value + "T00:00:00");

            if (nascimento > new Date()) {
                mostrarMensagem("A data de nascimento não pode ser futura.");
                return;
            }
        }

        const cpfResponsavel = form.elements.cpfResponsavel;

        if (cpfResponsavel && cpfResponsavel.required && !cpfValido(cpfResponsavel.value)) {
            mostrarMensagem("Confira o CPF do responsável legal.");
            return;
        }

        const dados = new FormData(form);
        dados.set("cpf", somenteNumeros(cpf));
        dados.set("celular", numerosCelular);

        if (cpfResponsavel) {
            dados.set("cpfResponsavel", somenteNumeros(cpfResponsavel.value));
        }

        const botao = form.querySelector('button[type="submit"]');
        botao.disabled = true;

        try {
            const resposta = await fetch("../tela_cadastro/processar_cadastro.php", {
                method: "POST",
                body: dados
            });

            const resultado = await resposta.json();

            if (resultado.sucesso) {
                mostrarMensagem(resultado.mensagem, "sucesso");
                form.reset();
                atualizarResponsavel();
                validarSenha();
                mensagemConfirmacao.textContent = "";
            } else {
                mostrarMensagem(resultado.mensagem);
            }
        } catch (erro) {
            mostrarMensagem("Não foi possível concluir o cadastro. Tente novamente.");
        } finally {
            botao.disabled = false;
        }
    });

    atualizarResponsavel();
    validarSenha();
});
