<!-- Formulário HTML que envia dados via POST para o mesmo arquivo (index.php) -->
<form action="index.php" method="POST" enctype="multipart/form-data">
    Nome: <input type="text" name="nome" /><br />
    E-mail: <input type="email" name="email" /><br />
    Foto: <input type="file" name="arquivo"><br />
    <input type="submit" name="grava" value="Enviar" />
</form>

<?php
// Inclui o arquivo de conexão com o banco de dados (conn.php deve instanciar $conn como PDO)
include "conn.php";

// Verifica se o formulário foi enviado (botão "grava" foi clicado)
if (isset($_POST['grava'])) {

    // Recebe os dados do formulário
    $nome = $_POST['nome'];
    $email = $_POST['email'];
    $_UP['pasta'] = "uploads/";
    $_UP['tamanho'] = 1024 * 1024 * 2;
    $_UP['extensao'] = array('jpg', 'png', 'jpeg', 'gif');
    $_UP['renomear']=true;

    //validação de extensão
    $explode=explode('.',$_FILES['arquivo']['name']);
    $aponta=end($explode);
    $extensao=strtolower($aponta);
    if(array_search($extensao, $_UP['extensao'])===false){
        echo "Extensão não aceita";
    }else{
        echo "Extensão aceita";
    }
    exit();

    // Prepara a query com parâmetros nomeados
    $grava = $conn->prepare('INSERT INTO `cadastro` (`nome_cad`, `email_cad`) VALUES (:pnome, :pemail)');

    // Associa os valores aos parâmetros da query
    $grava->bindValue(':pnome', $nome);   // Corrigido: precisa dos dois-pontos (:) no nome do parâmetro
    $grava->bindValue(':pemail', $email);

    // Executa a query
    $grava->execute();

    // Exibe mensagem de sucesso
    echo "Gravado com sucesso!<br>";
}
if (isset($_GET['excluir'])) {
    $id = $_GET['id'];
    $nome = base64_decode($_GET['nome']);
    echo "Deseja realmente excluir $nome ? <br/>";
    echo "<a href='index.php?exclusao&id=" . $id . "'>Sim</a>";
    echo "<a href='index.php'>Não</a>";
}
if (isset($_GET['exclusao'])) {
    $id = base64_decode($_GET['id']);
    $excluir = $conn->prepare('DELETE FROM cadastro WHERE `cadastro`.`id_cad` = :pid');
    $excluir->bindValue(':pid', $id);
    $excluir->execute();
    echo "Excluído com sucesso!";
}
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
<form action="index.php" method="POST">
    <input type="text" name="procura">
    <input type="submit" name="busca" value="Buscar">
</form>
<table border="1">
    <tr>
        <th>Nome</th>
        <th>E-mail</th>
    </tr>
    <?php
    if (isset($_POST['busca'])) {
        $busca = "%" . $_POST['procura'] . "%";
        $exib = $conn->prepare('SELECT * FROM `cadastro` WHERE nome_cad LIKE :pbusca');
        $exib->bindValue(':pbusca', $busca);
    } else {
        $exib = $conn->prepare('SELECT * FROM `cadastro`');
    }
    $exib->execute();
    if ($exib->rowCount() == 0) {
        echo "Não há registros!";
    } else {
        while ($row = $exib->fetch()) {
            echo "<tr>";
            echo "<td>" . $row['nome_cad'] . "</td>";
            echo "<td>" . $row['email_cad'] . "</td>";
            echo "<td><a href='index.php?excluir&id=" . base64_encode($row['id_cad']) . "&nome=" . base64_encode($row['nome_cad']) . "'>Excluir</a></td>";
            echo "<td><a href='index.php?alterar&id=" . base64_encode($row['id_cad']) . "'>Alterar</a></td>";
            echo "</tr>";
        }
    }
    ?>
</table>