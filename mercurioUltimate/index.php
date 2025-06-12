<?php
// Inicia a sessão
session_start();

// Verifica se o usuário está logado, caso contrário redireciona para a página de login
if (!isset($_SESSION['login'])) {
    header('location:login.php');
}

// Inclui o arquivo de conexão com o banco de dados
include "conn.php";

// Busca o nome do usuário logado
$cons_nome = $conn->prepare('SELECT * FROM login WHERE id_log = :pid');
$cons_nome->bindValue(':pid', $_SESSION['login']);
$cons_nome->execute();
$row_nome = $cons_nome->fetch();

// Exibe mensagem de boas-vindas
echo "Olá, " . $row_nome['user_log'] . " seja bem vindo!";
echo "<a href='index.php?logout'>Logout</a>";

// Encerra a sessão caso o usuário clique em logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('location:login.php');
}
?>

<!-- Formulário de envio de dados com upload de imagem -->
<form action="index.php" method="POST" enctype="multipart/form-data">
    Nome: <input type="text" name="nome" /><br />
    E-mail: <input type="email" name="email" /><br />
    Foto: <input type="file" name="arquivo"><br />
    <input type="submit" name="grava" value="Enviar" />
</form>

<?php
// Inclui novamente a conexão com o banco (caso o include anterior seja comentado)
include "conn.php";

// Se o botão de envio for clicado
if (isset($_POST['grava'])) {

    // Recebe dados do formulário
    $nome = $_POST['nome'];
    $email = $_POST['email'];

    // Configurações para upload
    $_UP['pasta'] = "uploads/";
    $_UP['tamanho'] = 1024 * 1024 * 2; // 2MB
    $_UP['extensao'] = array('jpg', 'png', 'jpeg', 'gif');
    $_UP['renomear'] = true;

    // Verifica a extensão do arquivo enviado
    $explode = explode('.', $_FILES['arquivo']['name']);
    $aponta = end($explode);
    $extensao = strtolower($aponta);

    // Valida extensão
    if (array_search($extensao, $_UP['extensao']) === false) {
        echo "Extensão não aceita";
    }

    // Valida tamanho
    if ($_UP['tamanho'] <= $_FILES['arquivo']['size']) {
        echo "Arquivo muito grande";
    }

    // Define o nome final do arquivo
    if ($_UP['renomear'] === true) {
        $nome_final = md5(time()) . ".$extensao";
    } else {
        $nome_final = $_FILES['arquivo']['name'];
    }

    // Move o arquivo para a pasta de uploads e grava os dados
    if (move_uploaded_file($_FILES['arquivo']['tmp_name'], $_UP['pasta'] . $nome_final)) {
        $url = $_UP['pasta'] . $nome_final;
        $grava = $conn->prepare('INSERT INTO `cadastro` (`nome_cad`, `email_cad`, `url_cad`) VALUES (:pnome, :pemail, :purl)');
        $grava->bindValue(':pnome', $nome);
        $grava->bindValue(':pemail', $email);
        $grava->bindValue(':purl', $url);
        $grava->execute();
        echo "Gravado com sucesso!<br>";
    }
}

// Confirmação de exclusão
if (isset($_GET['excluir'])) {
    $id = $_GET['id'];
    $nome = base64_decode($_GET['nome']);
    echo "Deseja realmente excluir $nome ? <br/>";
    echo "<a href='index.php?exclusao&id=" . $id . "'>Sim</a>";
    echo "<a href='index.php'>Não</a>";
}

// Exclusão de registro
if (isset($_GET['exclusao'])) {
    $id = base64_decode($_GET['id']);
    $excluir = $conn->prepare('DELETE FROM cadastro WHERE `cadastro`.`id_cad` = :pid');
    $excluir->bindValue(':pid', $id);
    $excluir->execute();
    echo "Excluído com sucesso!";
}

// Formulário de edição
if (isset($_GET['alterar'])) {
    $id = base64_decode($_GET['id']);
    $alterar = $conn->prepare('SELECT * FROM `cadastro` WHERE `id_cad`= :pid');
    $alterar->bindValue(':pid', $id);
    $alterar->execute();
    $row_alterar = $alterar->fetch()
?>
    <form action="index.php" method="POST">
        <input type="hidden" name="id" value="<?php echo base64_encode($row_alterar['id_cad']); ?>">
        Nome: <input type="text" name="nome" value="<?php echo $row_alterar['nome_cad']; ?>" /><br />
        E-mail: <input type="email" name="email" value="<?php echo $row_alterar['email_cad']; ?>" /><br />
        <input type="submit" name="altera" value="Alterar" />
    </form>
<?php
}

// Processa atualização de dados
if (isset($_POST['altera'])) {
    $id = base64_decode($_POST['id']);
    $nome = $_POST['nome'];
    $email = $_POST['email'];
    $altera = $conn->prepare('UPDATE `cadastro` SET 
    `nome_cad` = :pnome, 
    `email_cad` = :pemail 
    WHERE `cadastro`.`id_cad` = :pid;');
    $altera->bindValue(':pnome', $nome);
    $altera->bindValue(':pemail', $email);
    $altera->bindValue(':pid', $id);
    $altera->execute();
    echo "Alterado com sucesso!";
}
?>

<!-- Formulário de busca -->
<form action="index.php" method="POST">
    <input type="text" name="procura">
    <input type="submit" name="busca" value="Buscar">
</form>

<!-- Tabela de exibição de dados -->
<table border="1">
    <tr>
        <th>Foto</th>
        <th>Nome</th>
        <th>E-mail</th>
    </tr>
    <?php
    // Verifica se foi feita uma busca
    if (isset($_POST['busca'])) {
        $busca = "%" . $_POST['procura'] . "%";
        $exib = $conn->prepare('SELECT * FROM `cadastro` WHERE nome_cad LIKE :pbusca');
        $exib->bindValue(':pbusca', $busca);
    } else {
        $exib = $conn->prepare('SELECT * FROM `cadastro`');
    }
    $exib->execute();

    // Exibe dados se existirem
    if ($exib->rowCount() == 0) {
        echo "Não há registros!";
    } else {
        while ($row = $exib->fetch()) {
            echo "<tr>";
            echo "<td><img src='" . $row['url_cad'] . "' width='30'></td>";
            echo "<td>" . $row['nome_cad'] . "</td>";
            echo "<td>" . $row['email_cad'] . "</td>";
            echo "<td><a href='index.php?excluir&id=" . base64_encode($row['id_cad']) . "&nome=" . base64_encode($row['nome_cad']) . "'>Excluir</a></td>";
            echo "<td><a href='index.php?alterar&id=" . base64_encode($row['id_cad']) . "'>Alterar</a></td>";
            echo "</tr>";
        }
    }
    ?>
</table>
