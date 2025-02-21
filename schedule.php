<?php
session_start();
include 'db_connection.php';

if ($_SESSION['cargo'] !== 'Aluno') {
    header('Location: index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['cancel_schedule_id'])) {
        $schedule_id = $_POST['cancel_schedule_id'];
        $stmt = $conn->prepare("DELETE FROM schedules WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $schedule_id, $_SESSION['user_id']);
        $stmt->execute();
        $stmt->close();
    } else {
        $user_id = $_SESSION['user_id'];
        $date = $_POST['date'];
        $start_time = $_POST['start_time'];
        $end_time = date('H:i:s', strtotime($start_time) + 3600); // 1 hour duration
        $track_name = $_SESSION['nome_de_pista'];
        $aircraft_id = $_POST['aircraft_id'];

        $stmt = $conn->prepare("INSERT INTO schedules (user_id, date, start_time, end_time, track_name, aircraft_id) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issssi", $user_id, $date, $start_time, $end_time, $track_name, $aircraft_id);
        $stmt->execute();
        $stmt->close();
    }
}

$schedules = $conn->query("SELECT schedules.*, users.nome, users.nome_de_pista, aeronaves.matricula, aeronaves.modelo FROM schedules JOIN users ON schedules.user_id = users.id JOIN aeronaves ON schedules.aircraft_id = aeronaves.id WHERE date >= CURDATE() ORDER BY date, start_time");
$aircrafts = $conn->query("SELECT id, matricula, modelo FROM aeronaves");
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
            <label for="aircraft_id">Aeronave:</label>
            <select id="aircraft_id" name="aircraft_id" required>
                <?php while ($aircraft = $aircrafts->fetch_assoc()): ?>
                    <option value="<?php echo $aircraft['id']; ?>"><?php echo $aircraft['matricula'] . ' - ' . $aircraft['modelo']; ?></option>
                <?php endwhile; ?>
            </select>
            <button type="submit">Agendar</button>
        </form>
        <h2>Horários Agendados</h2>
        <table class="schedule-table">
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Hora de Início</th>
                    <th>Hora de Término</th>
                    <th>Nome</th>
                    <th>Nome de Pista</th>
                    <th>Matrícula da Aeronave</th>
                    <th>Modelo da Aeronave</th>
                    <th>Ação</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $schedules->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $row['date']; ?></td>
                        <td><?php echo $row['start_time']; ?></td>
                        <td><?php echo $row['end_time']; ?></td>
                        <td><?php echo $row['nome']; ?></td>
                        <td><?php echo $row['nome_de_pista']; ?></td>
                        <td><?php echo $row['matricula']; ?></td>
                        <td><?php echo $row['modelo']; ?></td>
                        <td>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="cancel_schedule_id" value="<?php echo $row['id']; ?>">
                                <button type="submit">Cancelar</button>
                            </form>
                        </td>
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
