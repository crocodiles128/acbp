<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "acbp";

// Cria conexão
$conn = new mysqli($servername, $username, $password, $dbname);

// Verifica conexão
if ($conn->connect_error) {
    die("Conexão falhou: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nome = $_POST['nome'];
    $nome_de_pista = $_POST['nome_de_pista'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
    $curso = $_POST['curso'];
    $habilitacao = $_POST['habilitacao'];
    $cargo = $_POST['cargo'];

    // Insere o usuário no banco de dados
    $sql = "INSERT INTO users (nome, nome_de_pista, email, senha, curso, habilitacao, cargo) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssss", $nome, $nome_de_pista, $email, $password, $curso, $habilitacao, $cargo);

    if ($stmt->execute()) {
        echo "Usuário registrado com sucesso.";
    } else {
        echo "Erro ao registrar usuário: " . $stmt->error;
    }

    $stmt->close();
}

$conn->close();
?>
