<?php
// Inicia a sessão PHP
session_start();

// Verifica se o usuário está logado; se não estiver, redireciona para a página de login
if (!isset($_SESSION['login'])) {
    header('location:login.php');
    exit(); // Interrompe a execução do script
}

// Inclui o arquivo de conexão com o banco de dados
include "conn.php";

// Busca o nome do usuário logado com base no ID da sessão
$cons_nome = $conn->prepare('SELECT * FROM login WHERE id_log = :pid');
$cons_nome->bindValue(':pid', $_SESSION['login']);
$cons_nome->execute();
$row_nome = $cons_nome->fetch();

// Exibe mensagem de boas-vindas e link de logout
echo "Olá, " . htmlspecialchars($row_nome['user_log']) . ", seja bem-vindo! ";
echo "<a href='entregas.php?logout'>Logout</a><br>";

// Se o link de logout for acionado, destrói a sessão e redireciona para o login
if (isset($_GET['logout'])) {
    session_destroy();
    header('location:login.php');
    exit();
}
?>

<!-- Link para a página de motoristas -->
<a href="motorista.php">Motorista</a>

<!-- Formulário para cadastro de entregas -->
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
// Trata o cadastro de uma nova entrega
if (isset($_POST['grava'])) {
    $motorista = $_POST['motorista'];
    $telefone = $_POST['telefone'];
    $empresa = $_POST['empresa'];
    $horario = $_POST['horario'];

    // Prepara o SQL para inserir os dados da entrega
    $grava = $conn->prepare('INSERT INTO entregas (empresa_ent, motorista_ent, telefone_ent, horario_ent) VALUES (:pempresa, :pmotorista, :ptelefone, :phorario)');
    $grava->bindValue(':pempresa', $empresa);
    $grava->bindValue(':pmotorista', $motorista);
    $grava->bindValue(':ptelefone', $telefone);
    $grava->bindValue(':phorario', $horario);
    $grava->execute();
    echo "Entrega cadastrada com sucesso!<br>";
}

// Confirmação de exclusão de uma entrega
if (isset($_GET['excluir'])) {
    $id = $_GET['id'];
    $nome = base64_decode($_GET['nome']);
    echo "Deseja realmente excluir a entrega de $nome?<br>";
    echo "<a href='entregas.php?exclusao&id=$id'>Sim</a> | <a href='entregas.php'>Não</a>";
}

// Exclusão da entrega após confirmação
if (isset($_GET['exclusao'])) {
    $id = base64_decode($_GET['id']);
    $excluir = $conn->prepare('DELETE FROM entregas WHERE id_ent = :pid');
    $excluir->bindValue(':pid', $id);
    $excluir->execute();
    echo "Entrega excluída com sucesso!";
}

// Formulário de alteração de entrega
if (isset($_GET['alterar'])) {
    $id = base64_decode($_GET['id']);
    $busca = $conn->prepare('SELECT * FROM entregas WHERE id_ent = :pid');
    $busca->bindValue(':pid', $id);
    $busca->execute();
    $row = $busca->fetch();
?>
    <!-- Formulário preenchido com os dados atuais da entrega -->
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

// Atualiza os dados da entrega após alteração
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

<!-- Formulário de busca de entregas por nome do motorista -->
<form action="entregas.php" method="POST">
    <input type="text" name="procura" placeholder="Buscar por motorista">
    <input type="submit" name="busca" value="Buscar">
</form>

<!-- Tabela com lista das entregas -->
<table border="1">
    <tr>
        <th>Empresa</th>
        <th>Motorista</th>
        <th>Telefone</th>
        <th>Horário</th>
        <th>Ações</th>
    </tr>
    <?php
    // Se foi feita uma busca, filtra os resultados
    if (isset($_POST['busca'])) {
        $procura = "%" . $_POST['procura'] . "%";
        $consulta = $conn->prepare('SELECT * FROM entregas WHERE motorista_ent LIKE :pbusca');
        $consulta->bindValue(':pbusca', $procura);
    } else {
        // Caso contrário, busca todas as entregas
        $consulta = $conn->prepare('SELECT * FROM entregas');
    }

    $consulta->execute();

    // Verifica se existem resultados
    if ($consulta->rowCount() == 0) {
        echo "<tr><td colspan='5'>Nenhum registro encontrado!</td></tr>";
    } else {
        // Exibe cada entrega em uma linha da tabela
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
