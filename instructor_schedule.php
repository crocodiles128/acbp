<?php
session_start();
include 'db_connection.php';

if ($_SESSION['cargo'] !== 'INVA' && $_SESSION['cargo'] !== 'Admin' && $_SESSION['cargo'] !== 'Operacoes') {
    header('Location: index.php');
    exit();
}

$alert_message = '';
$alert_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['flight_id'], $_POST['action'])) {
        $flight_id = $_POST['flight_id'];
        $action = $_POST['action'];
        $status = ($action === 'open') ? 'Aberto' : 'Fechado';

        if ($status === 'Aberto') {
            // Check if there is already an open flight for this instructor
            $stmt = $conn->prepare("SELECT COUNT(*) FROM schedules WHERE instructor_id = ? AND status = 'Aberto'");
            $stmt->bind_param("i", $_SESSION['user_id']);
            $stmt->execute();
            $stmt->bind_result($open_flights_count);
            $stmt->fetch();
            $stmt->close();

            if ($open_flights_count > 0) {
                $alert_message = 'Você já tem um voo aberto. Feche-o antes de abrir outro.';
                $alert_type = 'danger';
            } else {
                $stmt = $conn->prepare("UPDATE schedules SET status = ? WHERE id = ?");
                $stmt->bind_param("si", $status, $flight_id);
                $stmt->execute();
                $stmt->close();

                $alert_message = 'Status do voo atualizado com sucesso.';
                $alert_type = 'success';
            }
        } else {
            if (isset($_POST['num_landings'], $_POST['student_flight_role'], $_POST['instructor_flight_role'], $_POST['flight_hours'])) {
                $num_landings = $_POST['num_landings'];
                $student_flight_role = $_POST['student_flight_role'];
                $instructor_flight_role = $_POST['instructor_flight_role'];
                $flight_hours = floatval($_POST['flight_hours']); // Ensure flight hours are treated as decimal

                $stmt = $conn->prepare("UPDATE schedules SET status = ?, num_landings = ?, student_flight_role = ?, instructor_flight_role = ?, flight_hours = ? WHERE id = ?");
                $stmt->bind_param("sisssi", $status, $num_landings, $student_flight_role, $instructor_flight_role, $flight_hours, $flight_id);
                $stmt->execute();
                $stmt->close();

                // Update aircraft information
                $stmt = $conn->prepare("SELECT aircraft_id FROM schedules WHERE id = ?");
                $stmt->bind_param("i", $flight_id);
                $stmt->execute();
                $stmt->bind_result($aircraft_id);
                $stmt->fetch();
                $stmt->close();

                $stmt = $conn->prepare("UPDATE aeronaves SET horas_totais = horas_totais + ?, horas_desde_ultima_revisao = horas_desde_ultima_revisao + ?, horas_ate_proxima_revisao = horas_ate_proxima_revisao - ? WHERE id = ?");
                $stmt->bind_param("dddi", $flight_hours, $flight_hours, $flight_hours, $aircraft_id);
                $stmt->execute();
                $stmt->close();

                $alert_message = 'Status do voo atualizado com sucesso.';
                $alert_type = 'success';
            } else {
                $alert_message = 'Todos os campos são obrigatórios para fechar o voo.';
                $alert_type = 'danger';
            }
        }
    }
}

$stmt = $conn->prepare("SELECT schedules.*, users.nome_de_pista, aeronaves.matricula, aeronaves.modelo FROM schedules JOIN users ON schedules.user_id = users.id JOIN aeronaves ON schedules.aircraft_id = aeronaves.id WHERE schedules.instructor_id = ? AND schedules.status != 'Fechado'");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$schedules = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <title>Instrutor Agendamentos</title>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const alert = document.getElementById('alert');
            if (alert) {
                setTimeout(() => {
                    alert.style.display = 'none';
                }, 3000); // Hide after 3 seconds
            }

            const closeButtons = document.querySelectorAll('.close-flight-button');
            closeButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const flightId = this.dataset.flightId;
                    document.getElementById('flight_id').value = flightId;
                    document.getElementById('closeFlightModal').style.display = 'block';
                });
            });

            document.getElementById('closeFlightModalClose').addEventListener('click', function() {
                document.getElementById('closeFlightModal').style.display = 'none';
            });
        });
    </script>
</head>
<body>
    <header>
        <h1>Instrutor Agendamentos</h1>
    </header>
    <section class="schedule-container">
        <?php if ($alert_message): ?>
            <div class="alert alert-<?php echo $alert_type; ?>" id="alert"><?php echo $alert_message; ?></div>
        <?php endif; ?>
        <div class="table-responsive">
            <table class="schedule-table">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Hora de Início</th>
                        <th>Hora de Término</th>
                        <th>Matrícula</th>
                        <th>Modelo</th>
                        <th>Aluno</th>
                        <th>Status</th>
                        <th>Ação</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($schedules as $schedule): ?>
                        <tr>
                            <td><?php echo date('d/m/Y', strtotime($schedule['date'])); ?></td>
                            <td><?php echo date('H:i', strtotime($schedule['start_time'])); ?></td>
                            <td><?php echo date('H:i', strtotime($schedule['end_time'])); ?></td>
                            <td><?php echo $schedule['matricula']; ?></td>
                            <td><?php echo $schedule['modelo']; ?></td>
                            <td><?php echo $schedule['nome_de_pista']; ?></td>
                            <td><?php echo $schedule['status']; ?></td>
                            <td>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="flight_id" value="<?php echo $schedule['id']; ?>">
                                    <?php if ($schedule['status'] === 'Aberto'): ?>
                                        <button type="button" class="close-flight-button" data-flight-id="<?php echo $schedule['id']; ?>">Fechar</button>
                                    <?php else: ?>
                                        <button type="submit" name="action" value="open">Abrir</button>
                                    <?php endif; ?>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
    <div id="closeFlightModal" class="modal">
        <div class="modal-content">
            <span class="close" id="closeFlightModalClose">&times;</span>
            <form method="POST">
                <input type="hidden" name="flight_id" id="flight_id">
                <label for="num_landings">N° de Pousos:</label>
                <input type="number" id="num_landings" name="num_landings" required>
                <label for="student_flight_role">Função em Voo do Aluno:</label>
                <input type="text" id="student_flight_role" name="student_flight_role" required>
                <label for="instructor_flight_role">Função em Voo do Instrutor:</label>
                <input type="text" id="instructor_flight_role" name="instructor_flight_role" required>
                <label for="flight_hours">Horas de Voo:</label>
                <input type="number" step="0.1" id="flight_hours" name="flight_hours" required>
                <button type="submit" name="action" value="close">Fechar Voo</button>
            </form>
        </div>
    </div>
    <footer>
        <p>&copy; 2025 ACBP</p>
    </footer>
</body>
</html>
