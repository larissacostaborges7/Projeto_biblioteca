document.addEventListener("DOMContentLoaded", () => {
    // Referências do DOM
    const cartao = document.getElementById("cartao");
    const formCadastro = document.getElementById("formCadastro");
    const formLogin = document.getElementById("formLogin");

    const tipo = document.getElementById("tipo");
    const cargo = document.getElementById("cargo");
    const campoCargo = document.getElementById("campoCargo");

    const dataNascimento = document.getElementById("dataNascimento");
    const secaoResponsavel = document.getElementById("secaoResponsavel");

    const senha = document.getElementById("senha");
    const confirmarSenha = document.getElementById("confirmarSenha");

    const mensagemCadastro = document.getElementById("mensagemCadastro");
    const mensagemLogin = document.getElementById("mensagemLogin");

    // ====================================================
    // 1. ALTERNAR ENTRE LOGIN E CADASTRO (CARTÃO 3D)
    // ====================================================
    const btnAbrirCadastro = document.getElementById("abrirCadastro");
    const btnAbrirLogin = document.getElementById("abrirLogin");

    if (btnAbrirCadastro) {
        btnAbrirCadastro.addEventListener("click", () => {
            cartao.classList.add("is-flipped");
            limparMensagem(mensagemCadastro);
        });
    }

    if (btnAbrirLogin) {
        btnAbrirLogin.addEventListener("click", () => {
            cartao.classList.remove("is-flipped");
            limparMensagem(mensagemLogin);
        });
    }

    // ====================================================
    // 2. MOSTRAR OU ESCONDER SENHA (BOTÃO OLHO)
    // ====================================================
    document.querySelectorAll(".btn-olho").forEach((botao) => {
        botao.addEventListener("click", () => {
            const campo = document.getElementById(botao.dataset.target);
            if (!campo) return;

            const vaiMostrar = campo.type === "password";
            campo.type = vaiMostrar ? "text" : "password";
            botao.setAttribute(
                "aria-label",
                vaiMostrar ? "Esconder senha" : "Mostrar senha"
            );
        });
    });

    // ====================================================
    // 3. MÁSCARAS E FORMATAÇÃO DE CAMPOS NUMÉRICOS
    // ====================================================
    function somenteNumeros(valor) {
        return valor.replace(/\D/g, "");
    }

    function configurarCampoNumerico(id, maximo) {
        const campo = document.getElementById(id);
        if (!campo) return;

        campo.addEventListener("input", () => {
            campo.value = somenteNumeros(campo.value).slice(0, maximo);
        });
    }

    configurarCampoNumerico("cpf", 11);
    configurarCampoNumerico("cpfLogin", 11);
    configurarCampoNumerico("cpfResponsavel", 11);
    configurarCampoNumerico("celular", 11);
    configurarCampoNumerico("celularResponsavel", 11);

    function formatarCPF(campo) {
        if (!campo) return;

        campo.addEventListener("blur", () => {
            const numeros = somenteNumeros(campo.value);
            if (numeros.length === 11) {
                campo.value = numeros.replace(
                    /(\d{3})(\d{3})(\d{3})(\d{2})/,
                    "$1.$2.$3-$4"
                );
            }
        });

        campo.addEventListener("focus", () => {
            campo.value = somenteNumeros(campo.value);
        });
    }

    formatarCPF(document.getElementById("cpf"));
    formatarCPF(document.getElementById("cpfLogin"));
    formatarCPF(document.getElementById("cpfResponsavel"));

    // ====================================================
    // 4. VALIDAÇÃO DE IDADE E RESPONSÁVEL LEGAL
    // ====================================================
    const hoje = new Date();
    if (dataNascimento) {
        const dataHoje = [
            hoje.getFullYear(),
            String(hoje.getMonth() + 1).padStart(2, "0"),
            String(hoje.getDate()).padStart(2, "0")
        ].join("-");
        dataNascimento.max = dataHoje;
    }

    function calcularIdade(data) {
        if (!data) return null;
        const nascimento = new Date(data + "T00:00:00");
        if (Number.isNaN(nascimento.getTime())) return null;

        let idade = hoje.getFullYear() - nascimento.getFullYear();
        const aindaNaoFezAniversario =
            hoje.getMonth() < nascimento.getMonth() ||
            (hoje.getMonth() === nascimento.getMonth() && hoje.getDate() < nascimento.getDate());

        if (aindaNaoFezAniversario) {
            idade--;
        }

        return idade;
    }

    function atualizarResponsavel() {
        if (!dataNascimento || !secaoResponsavel) return;

        const idade = calcularIdade(dataNascimento.value);
        const menorDeIdade = idade !== null && idade < 18;

        secaoResponsavel.hidden = !menorDeIdade;

        const camposResponsavel = secaoResponsavel.querySelectorAll("input");
        camposResponsavel.forEach((campo) => {
            campo.required = menorDeIdade;
            if (!menorDeIdade) {
                campo.value = "";
            }
        });
    }

    if (dataNascimento) {
        dataNascimento.addEventListener("change", atualizarResponsavel);
    }

    // ====================================================
    // 5. EXIBIÇÃO DE CARGO DE FUNCIONÁRIO
    // ====================================================
    function atualizarCargo() {
        if (!tipo || !campoCargo || !cargo) return;

        const eFuncionario = tipo.value === "funcionario";
        campoCargo.hidden = !eFuncionario;
        cargo.required = eFuncionario;

        if (!eFuncionario) {
            cargo.value = "";
        }
    }

    if (tipo) {
        tipo.addEventListener("change", atualizarCargo);
    }

    // ====================================================
    // 6. VALIDAÇÃO DOS REQUISITOS DA SENHA
    // ====================================================
    function verificarSenha() {
        if (!senha) return false;
        const valor = senha.value;

        const requisitos = {
            tamanho: valor.length >= 8,
            maiuscula: /[A-Z]/.test(valor),
            minuscula: /[a-z]/.test(valor),
            numero: /\d/.test(valor),
            especial: /[^A-Za-z0-9]/.test(valor)
        };

        Object.entries(requisitos).forEach(([id, valido]) => {
            const elemento = document.getElementById(id);
            if (elemento) {
                elemento.classList.toggle("validado", valido);
                elemento.textContent =
                    (valido ? "✓ " : "○ ") +
                    {
                        tamanho: "Pelo menos 8 caracteres",
                        maiuscula: "Uma letra maiúscula",
                        minuscula: "Uma letra minúscula",
                        numero: "Um número",
                        especial: "Um caractere especial"
                    }[id];
            }
        });

        return Object.values(requisitos).every(Boolean);
    }

    if (senha) {
        senha.addEventListener("input", verificarSenha);
    }

    // ====================================================
    // 7. FUNÇÕES AUXILIARES DE MENSAGEM E CPF
    // ====================================================
    function mostrarMensagem(elemento, texto, tipoMensagem) {
        if (!elemento) return;
        elemento.textContent = texto;
        elemento.className = "mensagem " + tipoMensagem;
        elemento.style.display = "block";
    }

    function limparMensagem(elemento) {
        if (!elemento) return;
        elemento.textContent = "";
        elemento.className = "mensagem";
        elemento.style.display = "none";
    }

    function cpfValido(valor) {
        const cpf = somenteNumeros(valor);
        if (cpf.length !== 11) return false;
        if (/^(\d)\1{10}$/.test(cpf)) return false;

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

    function celularValido(valor) {
        const celular = somenteNumeros(valor);
        return celular.length === 10 || celular.length === 11;
    }

    // ====================================================
    // 8. ENVIO VIA FETCH: FORMULÁRIO DE LOGIN
    // ====================================================
    if (formLogin) {
        formLogin.addEventListener("submit", (evento) => {
            evento.preventDefault();
            limparMensagem(mensagemLogin);

            const cpf = document.getElementById("cpfLogin").value;
            if (!cpfValido(cpf)) {
                mostrarMensagem(mensagemLogin, "Confira o CPF digitado.", "erro");
                return;
            }

            const formData = new FormData(formLogin);

            fetch("processar_login.php", {
                method: "POST",
                body: formData
            })
            .then(res => res.json())
            .then(dados => {
                if (dados.sucesso) {
                    mostrarMensagem(mensagemLogin, dados.mensagem, "sucesso");
                    setTimeout(() => {
                        window.location.href = dados.redirecionar;
                    }, 1000);
                } else {
                    mostrarMensagem(mensagemLogin, dados.mensagem, "erro");
                }
            })
            .catch(() => {
                mostrarMensagem(mensagemLogin, "Erro ao conectar com o servidor.", "erro");
            });
        });
    }

    // ====================================================
    // 9. ENVIO VIA FETCH: FORMULÁRIO DE CADASTRO
    // ====================================================
    if (formCadastro) {
        formCadastro.addEventListener("submit", (evento) => {
            evento.preventDefault();
            limparMensagem(mensagemCadastro);

            const cpf = document.getElementById("cpf").value;
            const celular = document.getElementById("celular").value;
            const idade = calcularIdade(dataNascimento.value);

            if (!cpfValido(cpf)) {
                mostrarMensagem(mensagemCadastro, "Confira o CPF informado.", "erro");
                return;
            }

            if (!celularValido(celular)) {
                mostrarMensagem(mensagemCadastro, "Confira o número do celular.", "erro");
                return;
            }

            if (idade === null || idade < 0) {
                mostrarMensagem(mensagemCadastro, "Informe uma data de nascimento válida.", "erro");
                return;
            }

            if (tipo.value === "funcionario" && !cargo.value) {
                mostrarMensagem(mensagemCadastro, "Selecione o cargo do funcionário.", "erro");
                return;
            }

            if (idade < 18) {
                const cpfResponsavel = document.getElementById("cpfResponsavel").value;
                const celularResponsavel = document.getElementById("celularResponsavel").value;

                if (!cpfValido(cpfResponsavel)) {
                    mostrarMensagem(mensagemCadastro, "Confira o CPF do responsável legal.", "erro");
                    return;
                }

                if (!celularValido(celularResponsavel)) {
                    mostrarMensagem(mensagemCadastro, "Confira o celular do responsável legal.", "erro");
                    return;
                }
            }

            if (!verificarSenha()) {
                mostrarMensagem(mensagemCadastro, "Sua senha ainda não atende a todos os requisitos.", "erro");
                return;
            }

            if (senha.value !== confirmarSenha.value) {
                mostrarMensagem(mensagemCadastro, "As senhas não são iguais.", "erro");
                return;
            }

            // Envio para o PHP
            const formData = new FormData(formCadastro);

            fetch("processar_cadastro.php", {
                method: "POST",
                body: formData
            })
           
.then(res => res.json())
.then(dados => {
    if (dados.sucesso) {
        mostrarMensagem(
            mensagemCadastro,
            dados.mensagem,
            "sucesso"
        );

        formCadastro.reset();

        if (secaoResponsavel) {
            secaoResponsavel.hidden = true;
        }

        if (campoCargo) {
            campoCargo.hidden = true;
        }

        setTimeout(() => {
            cartao.classList.remove("is-flipped");
        }, 2200);

    } else {
        mostrarMensagem(
            mensagemCadastro,
            dados.mensagem,
            "erro"
        );
    }
})
.catch(() => {
    mostrarMensagem(
        mensagemCadastro,
        "Erro ao processar o cadastro.",
        "erro"
    );
});
        })
    }})
