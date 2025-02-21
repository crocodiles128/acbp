<?php
session_start();
include 'db_connection.php';

if ($_SESSION['cargo'] !== 'Aluno') {
    header('Location: index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $date = $_POST['date'];
    $start_time = $_POST['start_time'];
    $end_time = date('H:i:s', strtotime($start_time) + 3600); // 1 hour duration

    $stmt = $conn->prepare("INSERT INTO schedules (user_id, date, start_time, end_time) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $user_id, $date, $start_time, $end_time);
    $stmt->execute();
    $stmt->close();
}

$schedules = $conn->query("SELECT * FROM schedules WHERE date >= CURDATE() ORDER BY date, start_time");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <title>Agendamentos</title>
</head>
<body>
    <header>
        <h1>Agendamentos</h1>
    </header>
    <section class="schedule-container">
        <h2>Agendar Horário</h2>
        <form class="schedule-form" method="POST">
            <label for="date">Data:</label>
            <input type="date" id="date" name="date" required>
            <label for="start_time">Hora de Início:</label>
            <input type="time" id="start_time" name="start_time" required>
            <button type="submit">Agendar</button>
        </form>
        <h2>Horários Agendados</h2>
        <table class="schedule-table">
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Hora de Início</th>
                    <th>Hora de Término</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $schedules->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $row['date']; ?></td>
                        <td><?php echo $row['start_time']; ?></td>
                        <td><?php echo $row['end_time']; ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </section>
    <footer>
        <p>&copy; 2025 ACBP</p>
    </footer>
</body>
</html>
