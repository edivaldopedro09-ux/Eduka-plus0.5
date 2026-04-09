
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

// Scroll suave para âncoras
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
  anchor.addEventListener('click', function(e) {
    e.preventDefault();
    const targetId = this.getAttribute('href');
    if(targetId === '#') return;
    
    const targetElement = document.querySelector(targetId);
    if(targetElement) {
      window.scrollTo({
        top: targetElement.offsetTop - 80,
        behavior: 'smooth'
      });
    }
  });
});