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

// Exibe mensagem de boas-vindas com link de logout
echo "Olá, " . $row_nome['user_log'] . " seja bem vindo!";
echo "<a href='motorista.php?logout'>Logout</a>";

// Encerra a sessão caso o usuário clique em logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('location:login.php');
}
?>

<a href="entregas.php">Entregas</a>

<!-- Formulário de cadastro de motorista -->
<form action="motorista.php" method="POST">
    Nome do Motorista: 
    <input type="text" name="motorista" required
        pattern="^[A-Za-zÀ-ÿ\s]+$"
        title="Somente letras e espaços"><br />

    Veículo: 
    <input type="text" name="veiculo" required
        pattern="^[A-Za-z0-9\s\-]+$"
        title="Somente letras, números e traços"><br />

    Telefone: 
    <input type="tel" name="telefone" required
        pattern="^\(?\d{2}\)?\s?\d{4,5}-?\d{4}$"
        title="Formato esperado: (41) 99999-9999"><br />

    <input type="submit" name="grava" value="Cadastrar Motorista" />
</form>

<?php
include "conn.php";

// Grava novo motorista no banco
if (isset($_POST['grava'])) {
    $motorista = $_POST['motorista'];
    $veiculo = $_POST['veiculo'];
    $telefone = $_POST['telefone'];

    $grava = $conn->prepare('INSERT INTO `motorista` (`nome_mot`, `veiculo_mot`, `tel_mot`) VALUES (:pmotorista, :pveiculo, :ptelefone)');
    $grava->bindValue(':pmotorista', $motorista);
    $grava->bindValue(':pveiculo', $veiculo);
    $grava->bindValue(':ptelefone', $telefone);
    $grava->execute();
    echo "Gravado com sucesso!<br>";
}

// Exibe confirmação para exclusão
if (isset($_GET['excluir'])) {
    $id = $_GET['id'];
    $nome = base64_decode($_GET['nome']);
    echo "Deseja realmente excluir $nome ? <br/>";
    echo "<a href='motorista.php?exclusao&id=" . $id . "'>Sim</a>";
    echo "<a href='motorista.php'>Não</a>";
}

// Exclui motorista
if (isset($_GET['exclusao'])) {
    $id = base64_decode($_GET['id']);
    $excluir = $conn->prepare('DELETE FROM motorista WHERE `id_mot` = :pid');
    $excluir->bindValue(':pid', $id);
    $excluir->execute();
    echo "Excluído com sucesso!";
}

// Exibe formulário para alteração
if (isset($_GET['alterar'])) {
    $id = base64_decode($_GET['id']);
    $alterar = $conn->prepare('SELECT * FROM `motorista` WHERE `id_mot`= :pid');
    $alterar->bindValue(':pid', $id);
    $alterar->execute();
    $row_alterar = $alterar->fetch();
?>
    <form action="motorista.php" method="POST">
        <input type="hidden" name="id" value="<?php echo base64_encode($row_alterar['id_mot']); ?>">
        Nome do Motorista: <input type="text" name="motorista" value="<?php echo $row_alterar['nome_mot']; ?>" /><br />
        Veículo: <input type="text" name="veiculo" value="<?php echo $row_alterar['veiculo_mot']; ?>" /><br />
        Telefone: <input type="tel" name="telefone" value="<?php echo $row_alterar['tel_mot']; ?>" /><br />
        <input type="submit" name="altera" value="Alterar" />
    </form>
<?php
}

// Processa atualização de dados
if (isset($_POST['altera'])) {
    $id = base64_decode($_POST['id']);
    $motorista = $_POST['motorista'];
    $veiculo = $_POST['veiculo'];
    $telefone = $_POST['telefone'];
    $altera = $conn->prepare('UPDATE `motorista` SET 
        `nome_mot` = :pmotorista, 
        `veiculo_mot` = :pveiculo,
        `tel_mot` = :ptelefone 
        WHERE `id_mot` = :pid');
    $altera->bindValue(':pmotorista', $motorista);
    $altera->bindValue(':pveiculo', $veiculo);
    $altera->bindValue(':ptelefone', $telefone);
    $altera->bindValue(':pid', $id);
    $altera->execute();
    echo "Alterado com sucesso!";
}
?>

<!-- Formulário de busca -->
<form action="motorista.php" method="POST">
    <input type="text" name="procura">
    <input type="submit" name="busca" value="Buscar">
</form>

<!-- Exibição de motoristas cadastrados -->
<table border="1">
    <tr>
        <th>Nome</th>
        <th>Veículo</th>
        <th>Telefone</th>
    </tr>
    <?php
    if (isset($_POST['busca'])) {
        $busca = "%" . $_POST['procura'] . "%";
        $exib = $conn->prepare('SELECT * FROM `motorista` WHERE nome_mot LIKE :pbusca');
        $exib->bindValue(':pbusca', $busca);
    } else {
        $exib = $conn->prepare('SELECT * FROM `motorista`');
    }
    $exib->execute();

    if ($exib->rowCount() == 0) {
        echo "Não há registros!";
    } else {
        while ($row = $exib->fetch()) {
            echo "<tr>";
            echo "<td>" . $row['nome_mot'] . "</td>";
            echo "<td>" . $row['veiculo_mot'] . "</td>";
            echo "<td>" . $row['tel_mot'] . "</td>";
            echo "<td><a href='motorista.php?excluir&id=" . base64_encode($row['id_mot']) . "&nome=" . base64_encode($row['nome_mot']) . "'>Excluir</a></td>";
            echo "<td><a href='motorista.php?alterar&id=" . base64_encode($row['id_mot']) . "'>Alterar</a></td>";
            echo "</tr>";
        }
    }
    ?>
</table>
