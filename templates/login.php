<!DOCTYPE html>
<html lang="pt-AO">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="/assets/css/loginv2.css">
    <link rel="stylesheet" href="/assets/css/medianecessarios.css">
    <link rel="shortcut icon" href="/assets/images/logo/5noback.png" type="image/x-icon">
    <title><?= $escape($pageTitle) . " · " .  $nomesistema; ?></title>
</head>

<body data-restaurant="minha-lanchonete">
    <div class="wrap">
        <div class="left">
            <div class="logo-circle">
                <img src="/assets/images/logo/6noback.png" alt="" srcset="">
            </div>
            <p>
                Bem vindo ao seu espaço culinário. Faça a gestão das atividades que ocorrem no seu espaço com segurança e praticidade.
            </p>
        </div>

        <div class="right">
            <div class="card">
                <div class="card-header">
                    <h1>Bem-vindo de volta</h1>
                    <p>Faça login para aceder ao painel administrativo</p>
                </div>

                <div class="error-box" id="error-msg" style="display:none">

                    <span id="error-text">Email ou senha incorretos.</span>
                </div>

                <form id="login-form" action="/admin.php" method="post">
                    <div class="form-group">
                        <input type="hidden" name="action" value="login">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" placeholder="admin@ocardapio.ao" autocomplete="email" required>
                    </div>
                    <div class="form-group">
                        <label for="password">Senha</label>
                        <div class="pass-wrap">
                            <input type="password" id="password" name="password" placeholder="••••••••" autocomplete="current-password"
                                required>
                            <button type="button" class="eye" id="toggle-pass" aria-label="Mostrar senha">

                            </button>
                        </div>
                        <div class="forgot"><a href="#">Esqueceu a senha?</a></div>
                    </div>
                    <button type="submit" class="btn" id="submit-btn">Entrar</button>
                </form>

                <div class="footer-note">Problemas de acesso? <a href="#">Contacte o suporte</a></div>
            </div>
        </div>
    </div>

    <script>
        /*
        const toggle = document.getElementById('toggle-pass');
        const passInput = document.getElementById('password');
        const eyeIcon = document.getElementById('eye-icon');
        let visible = false;
        toggle.addEventListener('click', () => {
            visible = !visible;
            passInput.type = visible ? 'text' : 'password';
            eyeIcon.innerHTML = visible ?
             'D' : 'O';
        });

        document.getElementById('login-form').addEventListener('submit', e => {
            e.preventDefault();
            const email = document.getElementById('email').value.trim();
            const pass = document.getElementById('password').value.trim();
            const err = document.getElementById('error-msg');
            const btn = document.getElementById('submit-btn');
            err.style.display = 'none';
            if (!email || !pass) {
                document.getElementById('error-text').textContent = 'Email e senha são obrigatórios.';
                err.style.display = 'flex';
                return;
            }
            btn.textContent = 'A autenticar…';
            btn.disabled = true;
            setTimeout(() => {
                document.getElementById('error-text').textContent = 'Email ou senha incorretos, ou conta inativa.';
                err.style.display = 'flex';
                btn.textContent = 'Entrar';
                btn.disabled = false;
            }, 1200);
        });*/
    </script>
</body>

</html>