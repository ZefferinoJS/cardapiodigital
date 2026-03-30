# 🔍 Análise de Conflitos de Modais - Relatório Completo

## ⚠️ PROBLEMAS CRÍTICOS ENCONTRADOS

### 1. **CONFLITO DE SELETORES DUPLICADOS** 🔴 CRÍTICO

#### `.modal-overlay` definido em 2 arquivos:
- **modal-card.css** (linha 2): `z-index: 9999; background: rgba(30, 32, 37, 0.18)`
- **style.css** (linha 522): `z-index: 2000; background: rgba(0,0,0,0.7)`

**Resultado**: O arquivo importado por último (style.css) sobrescreve modal-card.css completamente.
```css
/* style.css vence sempre */
@import url('modal-card.css');  /* Importado primeiro */
/* Depois vem style.css que redefine .modal-overlay */
```

**Consequência**: Modal do cardápio (modal-card.css) não funciona corretamente.

---

### 2. **CONFLITO DE Z-INDEX** 🔴 CRÍTICO

Hierarquia quebrada:

```
.app-overlay:           z-index: 2200   ← Mais alto (App prompt/prato dia)
.app-overlay::before:   z-index: -1
.modal-overlay:         z-index: 2000   ← Conflita!
.cart-overlay:          z-index: 1900   ← Pode ficar escondido
.cart-drawer:           z-index: 2000   ← IGUAL ao modal!
.modal-card (modal-card.css): z-index: 9999  ← Nunca será usado (sobrescrito)
```

**Problema**: 
- `.modal-overlay` e `.cart-drawer` têm MESMO `z-index: 2000`
- Modal do prato do dia pode ficar escondido atrás do carrinho
- Se múltiplos modais abrem, ordem de aparição é imprevisível

---

### 3. **CONFLITO DE `.modal` INCOMPATÍVEL** 🟠 ALTO

- **modal-card.css** define `.modal` como FLEXBOX HORIZONTAL:
  ```css
  .modal {
    display: flex;
    flex-direction: row;
    min-width: 540px;
  }
  ```

- **style.css** define `.modal` como BLOCO genérico:
  ```css
  .modal {
    background: white;
    width: 90%;
    max-width: 720px;
  }
  ```

**Resultado**: O modal do cardápio (modal-card.css) perde seu layout, fica quebrado.

---

### 4. **BLUR DUPLO/CONFLITANTE** 🟠 ALTO

**style.css**:
```css
body.modal-aberto > * {
    filter: blur(4px);  /* Blur tudo */
}
body.modal-aberto .modal-overlay {
    filter: none;       /* Remove do modal */
}
```

**style.css** (app-overlay):
```css
.app-overlay::before {
    backdrop-filter: blur(4px);
}
```

**Problema**: 
- Dois sistemas de blur competindo
- Pode causar distorções visuais
- Performance ruim com múltiplos blurs

---

### 5. **ESTRUTURA HTML INCOMPATÍVEL** 🟠 ALTO

**modal-card.css espera**:
```html
<div class="modal-overlay">
  <div class="modal">
    <div class="modal-image-section">...</div>
    <div class="modal-content-section">...</div>
  </div>
</div>
```

**style.css + admin.css esperam**:
```html
<div class="modal-overlay" id="modal-prato">
  <div class="modal modal-large">
    <div class="modal-header">...</div>
    <form class="modal-body-form">...</form>
  </div>
</div>
```

**Resultado**: Estruturas incompatíveis = conflitos de layout.

---

### 6. **DISPLAY E OPACIDADE CONFLITANTES** 🟠 ALTO

**style.css**:
```css
.modal-overlay {
    opacity: 0;
    pointer-events: none;
    transition: opacity 0.2s;
}
.modal-overlay.show {
    opacity: 1;
    pointer-events: auto;
}
```

**modal-card.css**:
```css
.modal-overlay {
    z-index: 9999;
    display: flex;
    /* Sem controle de opacity */
}
```

**Problema**: modal-card.css espera estar sempre visível, style.css esconde com opacity.

---

### 7. **BACKGROUND CORES CONFLITANTES** 🟡 MÉDIO

| Arquivo | Seletor | Background |
|---------|---------|-----------|
| modal-card.css | `.modal-overlay` | `rgba(30, 32, 37, 0.18)` (quase transparente) |
| style.css | `.modal-overlay` | `rgba(0,0,0,0.7)` (muito escuro) |

**Resultado**: Inconsistência visual, um modal fica claro, outro escuro.

---

### 8. **VARIÁVEL CSS INTERNA x HARDCODED** 🟡 MÉDIO

**modal-card.css usa cores hardcoded**:
```css
.modal-title { color: #18181b; }
.modal-close:hover { color: #e11d48; }
```

**style.css usa variáveis**:
```css
.modal { background: var(--branco-puro); }
```

**Problema**: Inconsistência com tema laranja/preto/branco definido no root.css.

---

## 📊 TABELA RESUMIDA DE CONFLITOS

| Nº | Tipo | Arquivo | Seletor | Severidade | Impacto |
|----|------|---------|---------|-----------|---------|
| 1 | Duplicata | modal-card.css + style.css | `.modal-overlay` | 🔴 Crítico | Modal cardápio quebrado |
| 2 | Z-index | Vários | `.modal`, `.cart-drawer`, `.app-overlay` | 🔴 Crítico | Modais se sobrepõem errado |
| 3 | Flexbox | modal-card.css vs style.css | `.modal` | 🟠 Alto | Layout quebrado |
| 4 | Blur | style.css + app-overlay | `filter` + `backdrop-filter` | 🟠 Alto | Distorções visuais |
| 5 | HTML Incompatível | Vários | Estrutura diferentes | 🟠 Alto | Modais não funcionam |
| 6 | Opacity | style.css vs modal-card.css | `.modal-overlay` | 🟠 Alto | Modal invisível |
| 7 | Cores | modal-card.css | Hardcoded `#18181b`, `#e11d48` | 🟡 Médio | Inconsistência tema |
| 8 | CSS vars | Vários | Mix variáveis + hardcoded | 🟡 Médio | Manutenção difícil |

---

## 🚨 MODAIS AFETADOS

| Modal | Problema | Status |
|-------|----------|--------|
| **Modal Prato Cardápio** (modal-card.css) | Conflito completo de seletores | ❌ NÃO FUNCIONA |
| **Modal Prato Admin** (admin pratos.php) | Z-index conflita com carrinho | ⚠️ FUNCIONA MAS COM RISCO |
| **Modal Prato Dia** (prato-do-dia.js) | Z-index 2200, sobrepõe tudo | ✅ OK MAS PERIGOSO |
| **Modal Carrinho** (cart.js) | Z-index 2000, conflita com modal-overlay | ⚠️ FUNCIONA MAS COM RISCO |
| **Modal App Prompt** (app.js) | Z-index 2200, bem definido | ✅ OK |

---

## 💡 RECOMENDAÇÕES DE CORREÇÃO

### **Prioridade 1 - CRÍTICA: Renomear seletores**

Renomear em `modal-card.css`:
```css
/* Trocar */
.modal-overlay { ... }
.modal { ... }

/* Por */
.modal-card-overlay { ... }
.modal-card { ... }
```

### **Prioridade 2 - CRÍTICA: Reorganizar Z-index**

Estabelecer hierarquia clara:
```css
/* Ordem de prioridade (de baixo para cima) */
.cart-overlay:        z-index: 1900;
.modal-overlay:       z-index: 2000;
.cart-drawer:         z-index: 2001;
.app-overlay:         z-index: 2100;
.app-overlay::before: z-index: 2099;
```

### **Prioridade 3 - ALTA: Separar blur**

Usar apenas `backdrop-filter` no pseudo-elemento, não no body.

### **Prioridade 4 - ALTA: Unificar variáveis CSS**

Trocar hardcoded colors em modal-card.css por variáveis do root.css:
```css
.modal-title { color: var(--preto-suave); }
.modal-close:hover { color: var(--vermelho-pastel); }
```

### **Prioridade 5 - MÉDIO: Padronizar opacidade**

Usar classe `.show` consistentemente em todos os modais.

---

## ✅ CHECKLIST ANTES DE USAR

- [ ] Modal cardápio (modal-card.css) foi renomeado?
- [ ] Z-index foi reorganizado e documentado?
- [ ] Blur está apenas em `::before`, não em body?
- [ ] Todas as cores usam variáveis CSS?
- [ ] Múltiplos modais podem ser abertos sem conflito?
- [ ] Modal cardápio funciona 100%?
- [ ] Modal prato dia funciona 100%?
- [ ] Modal carrinho funciona 100%?

---

## 📝 CONCLUSÃO

**Risco Alto**: Múltiplos conflitos críticos podem causar distorções visuais e modais não funcionarem corretamente.

**Ação necessária**: Reorganização completa da arquitetura de modais recomendada.

