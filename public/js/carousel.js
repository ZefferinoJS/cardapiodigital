// Carousel functionality for product sections
(function(){
  const SCROLL_AMOUNT = 300; // pixels to scroll per click

  function qs(sel){ return document.querySelector(sel); }
  function qsa(sel){ return Array.from(document.querySelectorAll(sel)); }

  // Setup carousel scroll buttons for all sections
  function setupCarousels(){
    const sections = qsa('section.pratos');
    sections.forEach(section => {
      const carousel = section.querySelector('.prato-carousel');
      const scrollLeftBtn = section.querySelector('.scroll-left');
      const scrollRightBtn = section.querySelector('.scroll-right');

      if(!carousel || !scrollLeftBtn || !scrollRightBtn) return;

      scrollLeftBtn.addEventListener('click', () => {
        // Temporarily enable overflow for smooth scroll
        carousel.style.overflowX = 'auto';
        carousel.scrollBy({ left: -SCROLL_AMOUNT, behavior: 'smooth' });
        setTimeout(() => { carousel.style.overflowX = 'hidden'; }, 500);
      });

      scrollRightBtn.addEventListener('click', () => {
        // Temporarily enable overflow for smooth scroll
        carousel.style.overflowX = 'auto';
        carousel.scrollBy({ left: SCROLL_AMOUNT, behavior: 'smooth' });
        setTimeout(() => { carousel.style.overflowX = 'hidden'; }, 500);
      });

      // Update button state based on scroll position
      function updateButtonState(){
        const atStart = carousel.scrollLeft <= 0;
        const atEnd = carousel.scrollLeft >= carousel.scrollWidth - carousel.clientWidth - 10;

        scrollLeftBtn.disabled = atStart;
        scrollRightBtn.disabled = atEnd;

        scrollLeftBtn.style.opacity = atStart ? '0.5' : '1';
        scrollRightBtn.style.opacity = atEnd ? '0.5' : '1';
        scrollLeftBtn.style.cursor = atStart ? 'not-allowed' : 'pointer';
        scrollRightBtn.style.cursor = atEnd ? 'not-allowed' : 'pointer';
      }

      carousel.addEventListener('scroll', updateButtonState);
      window.addEventListener('resize', updateButtonState);
      
      // Initial state check after content loads
      setTimeout(updateButtonState, 100);
    });
  }

  // Run when DOM is ready
  document.addEventListener('DOMContentLoaded', setupCarousels);
  // Also run after menu is rendered (in case carousels are populated dynamically)
  document.addEventListener('menu:rendered', () => {
    setTimeout(setupCarousels, 100);
  });

})();
