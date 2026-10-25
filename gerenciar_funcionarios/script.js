

document.addEventListener('DOMContentLoaded', function () {
    const senha = document.getElementById('senha');
    const confirmar = document.getElementById('confirmar_senha');
    const mensagem = document.getElementById('mensagemConfirmacao');
    const formulario = document.getElementById('formFuncionario');

    if (!senha || !confirmar || !mensagem || !formulario) {
        console.error('Não foi possível localizar os campos de senha.');
        return;
    }

    const requisitos = {
        tamanho: valor => valor.length >= 8,
        maiuscula: valor => /[A-Z]/.test(valor),
        minuscula: valor => /[a-z]/.test(valor),
        numero: valor => /[0-9]/.test(valor),
        especial: valor => /[^a-zA-Z0-9]/.test(valor)
    };

    function atualizarRequisitos() {
        document.querySelectorAll('#requisitosSenha [data-regra]')
            .forEach(item => {
                const regra = item.dataset.regra;
                const valido = requisitos[regra](senha.value);
                const texto = item.textContent.replace(/^[✓✗]\s*/, '');

                item.textContent = (valido ? '✓ ' : '✗ ') + texto;
                item.classList.toggle('requisito-ok', valido);
                item.classList.toggle('requisito-pendente', !valido);
            });
    }

    function conferirSenhas() {
        if (confirmar.value === '') {
            mensagem.textContent =
                'Digite a senha nos dois campos para confirmar.';
            mensagem.className = 'mensagem-confirmacao';
            confirmar.setCustomValidity('');
            return;
        }

        const iguais = senha.value === confirmar.value;

        mensagem.textContent = iguais
            ? '✓ As senhas coincidem.'
            : '✗ As senhas estão diferentes.';

        mensagem.className = iguais
            ? 'mensagem-confirmacao confirmacao-ok'
            : 'mensagem-confirmacao confirmacao-erro';

        confirmar.setCustomValidity(
            iguais ? '' : 'As senhas não coincidem.'
        );
    }

    senha.addEventListener('input', function () {
        atualizarRequisitos();
        conferirSenhas();
    });

    confirmar.addEventListener('input', conferirSenhas);

    document.querySelectorAll('[data-toggle-senha]').forEach(botao => {
        botao.addEventListener('click', function () {
            const campo = document.getElementById(this.dataset.toggleSenha);
            if (!campo) return;

            const mostrar = campo.type === 'password';
            campo.type = mostrar ? 'text' : 'password';
            this.textContent = mostrar ? 'Ocultar' : 'Mostrar';
        });
    });

    formulario.addEventListener('submit', function (evento) {
         formulario.addEventListener('submit', function (evento) {
        atualizarRequisitos();
        conferirSenhas();

        const senhaValida = Object.values(requisitos)
            .every(regra => regra(senha.value));

        if (!senhaValida || senha.value !== confirmar.value) {
            evento.preventDefault();

            if (!senhaValida) {
                senha.reportValidity();
            } else {
                confirmar.reportValidity();
            }
        }
    });

    atualizarRequisitos();
});
})