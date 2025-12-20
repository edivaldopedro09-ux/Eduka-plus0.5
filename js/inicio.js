
function toggleMenu() {
  document.getElementById('menu').classList.toggle('active');
}

// Fechar menu ao clicar em um link no mobile
document.querySelectorAll('#menu a').forEach(link => {
  link.addEventListener('click', () => {
    if(window.innerWidth <= 768) {
      document.getElementById('menu').classList.remove('active');
    }
  });
});