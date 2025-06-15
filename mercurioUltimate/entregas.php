<?php
session_start();

if (!isset($_SESSION['login'])) {
    header('location:login.php');
    exit();
}

include "conn.php";

// Buscar nome do usuário
$cons_nome = $conn->prepare('SELECT * FROM login WHERE id_log = :pid');
$cons_nome->bindValue(':pid', $_SESSION['login']);
$cons_nome->execute();
$row_nome = $cons_nome->fetch();

echo "Olá, " . htmlspecialchars($row_nome['user_log']) . ", seja bem-vindo! ";
echo "<a href='entregas.php?logout'>Logout</a><br>";

if (isset($_GET['logout'])) {
    session_destroy();
    header('location:login.php');
    exit();
}
?>

<a href="motorista.php">Motorista</a>

<form action="entregas.php" method="POST">
    Nome do Motorista: 
    <input type="text" name="motorista" required
        pattern="^[A-Za-zÀ-ÿ\s]+$"
        title="Somente letras e espaços"><br />

    Telefone: 
    <input type="tel" name="telefone" required
        pattern="^\(?\d{2}\)?\s?\d{4,5}-?\d{4}$"
        title="Formato esperado: (41) 99999-9999"><br />

    Empresa: 
    <input type="text" name="empresa" required><br />

    Horário: 
    <input type="time" name="horario" required
        pattern="^\d{2}:\d{2}\s?-\s?\d{2}:\d{2}$"
        title="Formato: 08:00 - 17:00"><br />

    <input type="submit" name="grava" value="Cadastrar Entrega">
</form>


<?php
// Cadastrar nova entrega
if (isset($_POST['grava'])) {
    $motorista = $_POST['motorista'];
    $telefone = $_POST['telefone'];
    $empresa = $_POST['empresa'];
    $horario = $_POST['horario'];

    $grava = $conn->prepare('INSERT INTO entregas (empresa_ent, motorista_ent, telefone_ent, horario_ent) VALUES (:pempresa, :pmotorista, :ptelefone, :phorario)');
    $grava->bindValue(':pempresa', $empresa);
    $grava->bindValue(':pmotorista', $motorista);
    $grava->bindValue(':ptelefone', $telefone);
    $grava->bindValue(':phorario', $horario);
    $grava->execute();
    echo "Entrega cadastrada com sucesso!<br>";
}

// Confirmação de exclusão
if (isset($_GET['excluir'])) {
    $id = $_GET['id'];
    $nome = base64_decode($_GET['nome']);
    echo "Deseja realmente excluir a entrega de $nome?<br>";
    echo "<a href='entregas.php?exclusao&id=$id'>Sim</a> | <a href='entregas.php'>Não</a>";
}

// Excluir entrega
if (isset($_GET['exclusao'])) {
    $id = base64_decode($_GET['id']);
    $excluir = $conn->prepare('DELETE FROM entregas WHERE id_ent = :pid');
    $excluir->bindValue(':pid', $id);
    $excluir->execute();
    echo "Entrega excluída com sucesso!";
}

// Formulário para alterar entrega
if (isset($_GET['alterar'])) {
    $id = base64_decode($_GET['id']);
    $busca = $conn->prepare('SELECT * FROM entregas WHERE id_ent = :pid');
    $busca->bindValue(':pid', $id);
    $busca->execute();
    $row = $busca->fetch();
?>
    <form action="entregas.php" method="POST">
        <input type="hidden" name="id" value="<?php echo base64_encode($row['id_ent']); ?>">
        Nome do Motorista: <input type="text" name="motorista" value="<?php echo $row['motorista_ent']; ?>" required><br />
        Telefone: <input type="tel" name="telefone" value="<?php echo $row['telefone_ent']; ?>" required><br />
        Empresa: <input type="text" name="empresa" value="<?php echo $row['empresa_ent']; ?>" required><br />
        Horário: <input type="text" name="horario" value="<?php echo $row['horario_ent']; ?>" required><br />
        <input type="submit" name="altera" value="Alterar">
    </form>
<?php
}

// Atualizar entrega
if (isset($_POST['altera'])) {
    $id = base64_decode($_POST['id']);
    $motorista = $_POST['motorista'];
    $telefone = $_POST['telefone'];
    $empresa = $_POST['empresa'];
    $horario = $_POST['horario'];

    $altera = $conn->prepare('UPDATE entregas SET empresa_ent = :pempresa, motorista_ent = :pmotorista, telefone_ent = :ptelefone, horario_ent = :phorario WHERE id_ent = :pid');
    $altera->bindValue(':pempresa', $empresa);
    $altera->bindValue(':pmotorista', $motorista);
    $altera->bindValue(':ptelefone', $telefone);
    $altera->bindValue(':phorario', $horario);
    $altera->bindValue(':pid', $id);
    $altera->execute();
    echo "Entrega atualizada com sucesso!";
}
?>

<form action="entregas.php" method="POST">
    <input type="text" name="procura" placeholder="Buscar por motorista">
    <input type="submit" name="busca" value="Buscar">
</form>

<table border="1">
    <tr>
        <th>Empresa</th>
        <th>Motorista</th>
        <th>Telefone</th>
        <th>Horário</th>
        <th>Ações</th>
    </tr>
    <?php
    if (isset($_POST['busca'])) {
        $procura = "%" . $_POST['procura'] . "%";
        $consulta = $conn->prepare('SELECT * FROM entregas WHERE motorista_ent LIKE :pbusca');
        $consulta->bindValue(':pbusca', $procura);
    } else {
        $consulta = $conn->prepare('SELECT * FROM entregas');
    }
    $consulta->execute();

    if ($consulta->rowCount() == 0) {
        echo "<tr><td colspan='5'>Nenhum registro encontrado!</td></tr>";
    } else {
        while ($linha = $consulta->fetch()) {
            echo "<tr>";
            echo "<td>" . $linha['empresa_ent'] . "</td>";
            echo "<td>" . $linha['motorista_ent'] . "</td>";
            echo "<td>" . $linha['telefone_ent'] . "</td>";
            echo "<td>" . $linha['horario_ent'] . "</td>";
            echo "<td>
                <a href='entregas.php?excluir&id=" . base64_encode($linha['id_ent']) . "&nome=" . base64_encode($linha['motorista_ent']) . "'>Excluir</a> |
                <a href='entregas.php?alterar&id=" . base64_encode($linha['id_ent']) . "'>Alterar</a>
            </td>";
            echo "</tr>";
        }
    }
    ?>
</table>
