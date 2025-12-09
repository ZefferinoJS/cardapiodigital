<?php
// TODO: construir um dashboard próprio para "staff" (ex: pedidos em curso,
// notificações recentes). Por agora redirecionamos para a página de pedidos,
// que é a área de trabalho principal deste perfil.
header('Location: /admin.php?routa=pedidos');
exit;
