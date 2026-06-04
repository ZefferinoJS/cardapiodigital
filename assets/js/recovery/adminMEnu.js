// Admin - Estender e encolher menu lateral
(function() {
    'use strict';

    const API_BASE = '/api';
    const RESTAURANT_ID = 1;

    const sideLeft = document.querySelector('.side-left');
    const hamburguer = document.querySelector('.hamburguer');
    const adminSidebar = document.querySelector('.admin-sidebar');

    hamburguer.addEventListener('click', () => {
        adminSidebar.classList.toggle('menu-collapsed');
        sideLeft.classList.toggle('display');
    });

})();