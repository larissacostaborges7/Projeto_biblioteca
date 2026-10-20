document.addEventListener("DOMContentLoaded", () => {
  console.log("Área do leitor carregada com sucesso.");

  // Exemplo: Ocultar alertas de sucesso ou erro automaticamente após 5 segundos
  const alertas = document.querySelectorAll('.alerta-caixa, .alert');
  if (alertas.length > 0) {
    setTimeout(() => {
      alertas.forEach(alerta => {
        alerta.style.transition = "opacity 0.5s ease";
        alerta.style.opacity = "0";
        setTimeout(() => alerta.remove(), 500);
      });
    }, 5000);
  }
});