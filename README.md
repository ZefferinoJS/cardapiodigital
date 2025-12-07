# loja

Projeto PHP simples. O conteúdo público do site fica em `public/`.

Como usar (desenvolvimento):

- Rode o servidor embutido do PHP apontando para `public/` como document root:

```bash
php -S localhost:8000 -t public
```

- Ou configure seu servidor (Apache / Nginx) para apontar o document root para `public/`.

Estrutura principal:

- `public/` — arquivos públicos (entrada, CSS, JS, imagens)
- `app/` — código da aplicação (controllers, models, views)
- `config/` — configurações
- `vendor/` — dependências (Composer)

Nota: Há um `index.php` na raiz que redireciona para `public/` para compatibilidade.

Clean URLs / API
- O diretório `public/` contém um `.htaccess` com regras para reescrever `api/*` para `api/index.php/*`.
- Para habilitar URLs limpas (ex.: `/api/visits`) você precisa ativar `mod_rewrite` no Apache e permitir `AllowOverride` para o `public/`.

Exemplo (Ubuntu):
```bash
sudo a2enmod rewrite
sudo systemctl restart apache2
# Certifique-se de que sua configuração de VirtualHost permite .htaccess (AllowOverride All)
```
