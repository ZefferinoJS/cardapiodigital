(function(){
    'use strict';

    const API_BASE = '/api';
    const RESTAURANT_ID = 1;

    const totalEl = document.getElementById('total-pratos');
    const mediaEl = document.getElementById('media-avaliacao');
    const pedidosEl = document.getElementById('pedidos-hoje');
    const receitaEl = document.getElementById('receita');

    function formatPrice(v){
        try{
            return new Intl.NumberFormat('pt-AO', { style: 'currency', currency: 'AOA', maximumFractionDigits: 2 }).format(v);
        }catch(e){
            return (Number(v)||0).toFixed(2) + ' AOA';
        }
    }

    async function loadMetrics(){
        if(totalEl) totalEl.textContent = '…';
        if(mediaEl) mediaEl.textContent = '…';
        if(pedidosEl) pedidosEl.textContent = '…';
        if(receitaEl) receitaEl.textContent = '…';

        try{
            const today = new Date().toISOString().slice(0,10); // YYYY-MM-DD
            const resp = await fetch(`${API_BASE}/admin/metrics?restaurant_id=${RESTAURANT_ID}&date=${today}`);
            if(!resp.ok) throw new Error('metrics fetch failed');
            const data = await resp.json();

            if(totalEl) totalEl.textContent = (typeof data.total_items === 'number') ? data.total_items : '—';
            if(mediaEl) mediaEl.textContent = (data.average_rating || data.average_rating === 0) ? Number(data.average_rating).toFixed(2) : '—';
            if(pedidosEl) pedidosEl.textContent = (typeof data.orders_today === 'number') ? data.orders_today : '—';
            if(receitaEl) receitaEl.textContent = (typeof data.revenue_today === 'number') ? formatPrice(data.revenue_today) : '—';
        }catch(err){
            console.error('Erro ao carregar métricas do dashboard:', err);
            if(totalEl) totalEl.textContent = '—';
            if(mediaEl) mediaEl.textContent = '—';
            if(pedidosEl) pedidosEl.textContent = '—';
            if(receitaEl) receitaEl.textContent = '—';
        }
    }

    document.addEventListener('DOMContentLoaded', loadMetrics);
})();
