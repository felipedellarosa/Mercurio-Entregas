<?php
$nome = $_POST['nome'];
$email = $_POST['email'];

// Validação do nome (corrigido)
if (!preg_match('/^[^0-9]{2,80}$/i', $nome)) {
    echo "Nome inválido!<br>";
}

// Validação do e-mail (corrigido)
if (!preg_match('/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/', $email)) {
    echo "E-mail inválido!";
}
?>
