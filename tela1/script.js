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

    // --- ELEMENTOS DO RESPONSÁVEL ---
const dataNascimentoInput = document.getElementById("dataNascimento");
const secaoResponsavel = document.getElementById("secaoResponsavel");
const nomeResponsavel = document.getElementById("nomeResponsavel");
const cpfResponsavel = document.getElementById("cpfResponsavel");
const emailResponsavel = document.getElementById("emailResponsavel");
const celularResponsavel = document.getElementById("celularResponsavel");

// Função para calcular idade e mostrar/ocultar campos do responsável
function verificarIdade() {
    if (!dataNascimentoInput.value) return;

    const hoje = new Date();
    const nascimento = new Date(dataNascimentoInput.value);
    let idade = hoje.getFullYear() - nascimento.getFullYear();
    const m = hoje.getMonth() - nascimento.getMonth();

    if (m < 0 || (m === 0 && hoje.getDate() < nascimento.getDate())) {
        idade--;
    }

    if (idade < 18) {
        secaoResponsavel.style.display = "block";
        nomeResponsavel.required = true;
        cpfResponsavel.required = true;
        emailResponsavel.required = true;
        celularResponsavel.required = true;
    } else {
        secaoResponsavel.style.display = "none";
        nomeResponsavel.required = false;
        cpfResponsavel.required = false;
        emailResponsavel.required = false;
        celularResponsavel.required = false;
        
        // Limpa os campos se o usuário mudar a data
        nomeResponsavel.value = "";
        cpfResponsavel.value = "";
        emailResponsavel.value = "";
        celularResponsavel.value = "";
    }
}

if (dataNascimentoInput) {
    dataNascimentoInput.addEventListener("change", verificarIdade);
}

// Aplica a restrição de apenas números no CPF e celular do responsável
somenteNumeros(cpfResponsavel, 11);
somenteNumeros(celularResponsavel, 11);

    // ELEMENTOS DOS REQUISITOS DE SENHA
    const reqTamanho = document.getElementById('tamanho');
    const reqMaiuscula = document.getElementById('maiuscula');
    const reqMinuscula = document.getElementById('minuscula');
    const reqNumero = document.getElementById('numero');
    const reqEspecial = document.getElementById('especial');

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

        campo.value = campo.value.replace(/\D/g, "").slice(0, limite);
    }

    somenteNumeros(cpf, 11);
    somenteNumeros(celular, 11);

    // --- VALIDAÇÃO DE SENHA FORTE EM TEMPO REAL ---
    if (senha && reqTamanho && reqMaiuscula && reqMinuscula && reqNumero && reqEspecial) {
        senha.addEventListener("input", function() {
            const valor = senha.value;

            // 1. Mínimo de 8 caracteres
            if (valor.length >= 8) {
                reqTamanho.classList.add('valido');
                reqTamanho.classList.remove('invalido');
            } else {
                reqTamanho.classList.remove('valido');
                reqTamanho.classList.add('invalido');
            }

            // 2. Letra maiúscula
            if (/[A-Z]/.test(valor)) {
                reqMaiuscula.classList.add('valido');
                reqMaiuscula.classList.remove('invalido');
            } else {
                reqMaiuscula.classList.remove('valido');
                reqMaiuscula.classList.add('invalido');
            }

            // 3. Letra minúscula
            if (/[a-z]/.test(valor)) {
                reqMinuscula.classList.add('valido');
                reqMinuscula.classList.remove('invalido');
            } else {
                reqMinuscula.classList.remove('valido');
                reqMinuscula.classList.add('invalido');
            }

            // 4. Número
            if (/[0-9]/.test(valor)) {
                reqNumero.classList.add('valido');
                reqNumero.classList.remove('invalido');
            } else {
                reqNumero.classList.remove('valido');
                reqNumero.classList.add('invalido');
            }

            // 5. Caractere especial
            if (/[\W_]/.test(valor)) {
                reqEspecial.classList.add('valido');
                reqEspecial.classList.remove('invalido');
            } else {
                reqEspecial.classList.remove('valido');
                reqEspecial.classList.add('invalido');
            }
        });
    }

    // --- VALIDAR AS SENHAS EM TEMPO REAL (COINCIDÊNCIA) ---
    function validarSenhasTempoReal() {
        if (senha.value !== confirmarSenha.value) {
            confirmarSenha.setCustomValidity("As senhas não são iguais.");
        } else {
            confirmarSenha.setCustomValidity("");
        }
    }

    senha.addEventListener("input", validarSenhasTempoReal);
    confirmarSenha.addEventListener("input", validarSenhasTempoReal);

    // --- MOSTRAR / OCULTAR SENHA (OLHINHOS) ---
    const botoesToggle = document.querySelectorAll('.btn-toggle-senha');

    botoesToggle.forEach(botao => {
        botao.addEventListener('click', function() {
            const targetId = this.getAttribute('data-target');
            const inputTarget = document.getElementById(targetId);

            if (inputTarget) {
                if (inputTarget.type === 'password') {
                    inputTarget.type = 'text';
                    this.textContent = '🙈';
                } else {
                    inputTarget.type = 'password';
                    this.textContent = '👁️';
                }
            }
        });
    });

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

        // VALIDAR REQUISITOS DA SENHA FORTE NO SUBMIT
        const atendeTamanho = senha.value.length >= 8;
        const atendeMaiuscula = /[A-Z]/.test(senha.value);
        const atendeMinuscula = /[a-z]/.test(senha.value);
        const atendeNumero = /[0-9]/.test(senha.value);
        const atendeEspecial = /[\W_]/.test(senha.value);

        if (!atendeTamanho || !atendeMaiuscula || !atendeMinuscula || !atendeNumero || !atendeEspecial) {
            mostrarMensagem("A senha não atende a todos os requisitos de segurança!", "erro");
            senha.focus();
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

                // Limpa os estados visuais dos requisitos da senha
                [reqTamanho, reqMaiuscula, reqMinuscula, reqNumero, reqEspecial].forEach(req => {
                    if (req) {
                        req.classList.remove('valido');
                        req.classList.add('invalido');
                    }
                });

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
