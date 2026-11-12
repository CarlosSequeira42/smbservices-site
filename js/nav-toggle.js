/**
 * Menu hambúrguer para mobile. O botão só é visível abaixo de 860px
 * (ver css/style.css); em ecrãs maiores o menu mantém-se sempre
 * visível na horizontal, como antes.
 */
(function () {
  'use strict';

  document.addEventListener('DOMContentLoaded', function () {
    var nav = document.querySelector('nav.main-nav');
    if (!nav) {
      return;
    }

    var toggle = document.createElement('button');
    toggle.type = 'button';
    toggle.className = 'nav-toggle';
    toggle.setAttribute('aria-label', 'Abrir menu');
    toggle.setAttribute('aria-expanded', 'false');
    toggle.innerHTML = '<span></span><span></span><span></span>';

    nav.parentNode.insertBefore(toggle, nav);

    function closeMenu() {
      nav.classList.remove('is-open');
      toggle.setAttribute('aria-expanded', 'false');
    }

    function toggleMenu() {
      var isOpen = nav.classList.toggle('is-open');
      toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    }

    toggle.addEventListener('click', function (e) {
      e.stopPropagation();
      toggleMenu();
    });

    // Fecha o menu ao clicar num link (ex.: ao navegar para outra página).
    nav.querySelectorAll('a').forEach(function (link) {
      link.addEventListener('click', closeMenu);
    });

    // Fecha ao clicar fora do menu.
    document.addEventListener('click', function (e) {
      if (nav.classList.contains('is-open') && !nav.contains(e.target) && e.target !== toggle) {
        closeMenu();
      }
    });

    // Fecha com a tecla Escape.
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') {
        closeMenu();
      }
    });

    // Se o visitante aumentar a janela para desktop, garante que o
    // menu não fica preso "aberto" ao voltar para mobile mais tarde.
    window.addEventListener('resize', function () {
      if (window.innerWidth > 860) {
        closeMenu();
      }
    });
  });
})();
