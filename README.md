# O Cardápio — estrutura organizada

## Estrutura de pastas

```
/
├── admin.php                  # Router único do painel administrativo
├── index.php                  # Entrada do site público (cardápio do cliente)
├── .env.example                # Modelo de variáveis de ambiente (copiar para .env)
├── config/
│   ├── db.php                  # Configuração da BD (lê de variáveis de ambiente)
│   └── dbnojs.php               # Cria $pdo pronto a usar, a partir de db.php
├── database/
│   └── cardapio.sql             # Dump/schema da base de dados
├── templates/
│   ├── layout_portal.php        # Layout mestre do painel admin (sidebar + header)
│   ├── login.php                 # Página de login
│   ├── menu.php                  # Página do site público (cardápio do cliente)
│   └── pages/
│       ├── admin/                # Views completas do perfil "admin"
│       │   ├── dashboard.php
│       │   ├── pratos.php
│       │   ├── categorias.php
│       │   ├── pedidos.php
│       │   ├── avaliacoes.php
│       │   ├── relatorio.php
│       │   ├── usuarios.php
│       │   ├── configuracoes.php
│       │   └── notificacoes.php
│       ├── staff/                # Views reduzidas do perfil "staff" (ver TODOs)
│       │   ├── dashboard.php
│       │   ├── pedidos.php
│       │   └── notificacoes.php
│       └── kitchen/              # Views reduzidas do perfil "kitchen" (ver TODOs)
│           ├── dashboard.php
│           └── pedidos.php
├── public/
│   └── api/
│       └── index.php            # Front controller da API REST (/visits, /menu, /orders, /ratings)
├── assets/
│   ├── css/                     # (vazio — ver "O que falta" abaixo)
│   ├── js/                      # (vazio — ver "O que falta" abaixo)
│   └── images/                  # (vazio — ver "O que falta" abaixo)
└── logs/                        # php_errors.log é escrito aqui em runtime
```

Esta estrutura já corresponde exatamente aos caminhos que `admin.php` e as
próprias views esperam (`__DIR__ . '/config/...'`, `__DIR__ . '/templates/...'`,
`/assets/css/...` a partir da raiz do site, etc.) — não é preciso alterar
nenhum `require` ou URL.

## Configuração

1. Importe `database/cardapio.sql` na sua base de dados MySQL/MariaDB.
2. Copie `.env.example` para `.env` (ou defina as variáveis `DB_HOST`,
   `DB_NAME`, `DB_USER`, `DB_PASS` diretamente no seu servidor web/painel de
   hospedagem) e preencha com as credenciais reais.
3. Aponte o docroot do servidor para a raiz deste projeto.
4. Garanta que a pasta `logs/` tem permissão de escrita para o utilizador do
   servidor web.

## O que falta / pontos de atenção

- **`assets/images/` continua vazio.** Nenhuma imagem (logo, fotos de pratos,
  imagem de fundo do login, etc.) foi enviada — as pastas existem para os
  caminhos absolutos (`/assets/images/...`) já usados no código funcionarem
  assim que você copiar os ficheiros reais para lá.
- **`medianecessarios.css`** tem, no final do ficheiro, um segundo bloco de
  media queries "mobile-first" vazias (comentado como alternativa mais
  moderna). Não fazem nada — pode apagá-las ou preenchê-las, como preferir.
- **`templates/pages/staff/` e `templates/pages/kitchen/` são placeholders
  mínimos**, criados só para o site não crashar quando esses perfis fazem
  login (antes disso, essas páginas simplesmente não existiam e o `require`
  falhava). Reveja o desenho e as permissões antes de usar em produção —
  por exemplo, a cozinha provavelmente não deveria ver valores em Kz.
- **`cardapio.sql` ainda tem dados reais** (um e-mail e um hash de password).
  Se for versionar este projeto num repositório (mesmo privado), troque essa
  senha na base de dados de produção primeiro.
- O login em `admin.php` aceita senha em texto simples como fallback para
  contas "legado" sem hash. Se não tiver nenhuma conta nessas condições,
  considere remover esse fallback.

## Bugs já corrigidos nesta versão

1. Os dois `index.php` que colidiam (site público vs. API) foram separados.
2. Tabela `notifications` (usada por `admin.php` mas ausente do schema) foi
   adicionada a `cardapio.sql`.
3. `admin_users.role` passou a aceitar `superadmin`/`kitchen`, alinhado com o
   que o código já esperava; `usuarios.php` permite criar/editar `kitchen`
   pela UI (`superadmin` fica de fora da UI por segurança).
4. Credenciais da BD deixaram de estar em texto simples no código — agora
   vêm de variáveis de ambiente — e `dbnojs.php` deixou de duplicar a lógica
   de ligação de `db.php`.
5. Escaping inconsistente em `pratos.php` corrigido.
6. **Caminho da API inconsistente no front-end.** `app.js`, `main.js` e
   `prato-do-dia.js` apontavam para `/assets/api` (e `cart.js` para `/api`),
   caminhos que não existem no servidor — nenhum pedido ao carrinho, menu ou
   painel admin chegaria à API. Os quatro ficheiros foram corrigidos para
   apontar a `/public/api`, que é onde `public/api/index.php` (a API) de
   facto vive nesta estrutura.
7. **`admin-categorias.js` duplicava `main.js`.** Os dois ficheiros geriam os
   mesmos elementos da página de categorias; carregados juntos, cada ação
   disparava duas vezes (dois `POST`/`DELETE`, dois alertas). O script deixou
   de ser carregado em `layout_portal.php` — a gestão de categorias já é
   feita por `main.js`. O ficheiro original foi mantido em `assets/js/` só
   como referência.
8. **`medianecessarios.css` estava órfão** (nenhum template o carregava).
   Passou a ser incluído em `layout_portal.php`, `login.php` e `menu.php`.
9. **[CRÍTICO] A API `/public/api/index.php` não tinha nenhuma autenticação.**
   Qualquer pessoa que soubesse os URLs conseguia criar, editar e apagar
   pratos, categorias e mesas, e ver todos os pedidos e avaliações — sem
   fazer login. Agora, todas as rotas `/admin/...` exigem a mesma sessão
   criada pelo login em `admin.php` (perfil `admin` para pratos/categorias/
   mesas/avaliações/métricas; `admin`, `staff` ou `kitchen` para pedidos).
   Além disso, o `restaurant_id` deixou de vir do cliente (query string ou
   corpo do pedido) — passou a vir sempre da sessão autenticada, e as rotas
   de editar/apagar (pratos, categorias, mesas, pedidos, avaliações) agora
   verificam que o registo pertence mesmo a esse restaurante antes de
   alterar ou apagar nada.
10. **`.env` agora é lido de verdade.** Criei `config/env.php`, um loader
    mínimo (sem depender de `composer`, que não tem acesso à internet neste
    ambiente) que lê o `.env` e preenche `getenv()`/`$_ENV` — variáveis já
    definidas no servidor real continuam a ter prioridade sobre o `.env`.

## Segurança: o que ainda falta (recomendado, não aplicado ainda)

- CSRF token nos formulários POST do `admin.php` (logout, marcar notificação
  como lida, apagar notificação, etc.) — hoje dependem só do cookie de sessão.
- Rate limiting no login.
- Remover o fallback de senha em texto simples (login "legado") se não tiver
  nenhuma conta nessas condições.
