<!-- Formulário de Login -->
<form action="login.php" method="POST">
    <input type="text" name="user" placeholder="Usuário"><br/>
    <input type="password" name="senha" placeholder="Senha"><br/>
    <input type="submit" name="logar" value="Logar">
</form>

<?php
// Verifica se o formulário de login foi enviado
if (isset($_POST['logar'])) {
    
    // Recebe os dados do formulário
    $user = $_POST['user'];
    $senha = $_POST['senha'];

    // Inclui o arquivo de conexão com o banco de dados (conn.php deve instanciar a variável $conn com PDO)
    include "conn.php";

    // Prepara a consulta para verificar se existe um usuário com esse login e senha (a senha é verificada com MD5)
    $ver_login = $conn->prepare('SELECT * FROM `login` WHERE `user_log` = :puser AND `senha_log` = md5(:psenha)');

    // Associa os valores dos parâmetros com os dados informados
    $ver_login->bindValue(':puser', $user);
    $ver_login->bindValue(':psenha', $senha);

    // Executa a consulta
    $ver_login->execute();

    // Verifica se algum resultado foi retornado (ou seja, se o login é válido)
    if ($ver_login->rowCount() == 0) {
        // Caso não haja correspondência, exibe mensagem de erro
        echo "Login ou senha inválido!";
    } else {
        // Caso o login seja válido, inicia a sessão
        session_start();

        // Recupera os dados do usuário autenticado
        $row = $ver_login->fetch();
        $id_login = $row['id_log'];

        // Salva o ID do usuário na sessão
        $_SESSION['login'] = $id_login;

        // Redireciona para a página principal
        header('location:index.php');
    }
}
?>
