function marcarComoLida(id) {
    fetch('marcar_lida.php?id=' + id)
        .then(response => response.json())
        .then(data => {
            if (data.sucesso) {
                const elemento = document.getElementById('notif-' + id);
                if (elemento) {
                    elemento.style.opacity = '0';
                    setTimeout(() => elemento.remove(), 300);
                }
            }
        })
        .catch(error => console.error('Erro:', error));
}