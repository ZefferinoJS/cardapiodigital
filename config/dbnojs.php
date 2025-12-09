<?php

    $host = "localhost";
    $dbname = "cardapio";
    $user = "adminphp";
    $pass = "SenhaForte123!";

    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname",$user,$pass);
        $pdo -> setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
        
    } catch (PDOException $e) {
        "Conexão falhou".$e->getMessage();
    }

?>