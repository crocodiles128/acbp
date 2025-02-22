<?php
session_start();
include 'db_connection.php';

$alert_message = '';
$alert_type = '';

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
    } elseif (isset($_POST['check_date'], $_POST['check_start_time'], $_POST['check_aircraft_id'])) {
        // Check availability form submission
        $check_date = $_POST['check_date'];
        $check_start_time = $_POST['check_start_time'];
        $check_aircraft_id = $_POST['check_aircraft_id'];

        $stmt = $conn->prepare("SELECT COUNT(*) FROM schedules WHERE date = ? AND start_time = ? AND aircraft_id = ?");
        $stmt->bind_param("ssi", $check_date, $check_start_time, $check_aircraft_id);
        $stmt->execute();
        $stmt->bind_result($count);
        $stmt->fetch();
        $stmt->close();

        if ($count > 0) {
            $alert_message = 'Aeronave já agendada para este horário.';
            $alert_type = 'danger';
        } else {
            $alert_message = 'Aeronave disponível para este horário.';
            $alert_type = 'success';
        }
    } else {
        // Validate required fields
        if (isset($_POST['date'], $_POST['start_time'], $_POST['aircraft_id']) && !empty($_POST['date']) && !empty($_POST['start_time']) && !empty($_POST['aircraft_id'])) {
            $user_id = $_SESSION['user_id'];
            $date = $_POST['date'];
            $start_time = $_POST['start_time'];
            $end_time = date('H:i', strtotime($start_time) + 3600); // 1 hour duration
            $track_name = $_SESSION['nome_de_pista'];
            $aircraft_id = $_POST['aircraft_id'];

            // Check if the aircraft is already scheduled at the same time
            $stmt = $conn->prepare("SELECT COUNT(*) FROM schedules WHERE date = ? AND start_time = ? AND aircraft_id = ?");
            $stmt->bind_param("ssi", $date, $start_time, $aircraft_id);
            $stmt->execute();
            $stmt->bind_result($count);
            $stmt->fetch();
            $stmt->close();

            // Check if the user already has a schedule at the same time
            $stmt = $conn->prepare("SELECT COUNT(*) FROM schedules WHERE date = ? AND start_time = ? AND user_id = ?");
            $stmt->bind_param("ssi", $date, $start_time, $user_id);
            $stmt->execute();
            $stmt->bind_result($user_count);
            $stmt->fetch();
            $stmt->close();

            if ($user_count > 0) {
                $alert_message = 'Você já tem um voo agendado para este horário.';
                $alert_type = 'danger';
            } elseif ($count > 0) {
                $alert_message = 'Aeronave já agendada para este horário.';
                $alert_type = 'danger';
            } else {
                $stmt = $conn->prepare("INSERT INTO schedules (user_id, date, start_time, end_time, track_name, aircraft_id, status) VALUES (?, ?, ?, ?, ?, ?, 'Não Iniciado')");
                $stmt->bind_param("issssi", $user_id, $date, $start_time, $end_time, $track_name, $aircraft_id);
                $stmt->execute();
                $stmt->close();
            }
        } else {
            // Handle the error case where required fields are missing
            $alert_message = 'All fields are required.';
            $alert_type = 'danger';
        }
    }
}

$selected_date = isset($_POST['selected_date']) ? $_POST['selected_date'] : date('Y-m-d');
$schedules = $conn->query("SELECT schedules.*, users.nome, users.nome_de_pista, aeronaves.matricula, aeronaves.modelo FROM schedules JOIN users ON schedules.user_id = users.id JOIN aeronaves ON schedules.aircraft_id = aeronaves.id WHERE date = '$selected_date' AND schedules.status != 'Fechado' ORDER BY date, start_time");

$aircrafts = $conn->query("SELECT id, matricula, modelo FROM aeronaves");

$aircrafts_array = [];
while ($aircraft = $aircrafts->fetch_assoc()) {
    $aircrafts_array[] = $aircraft;
}

$time_slots = ["08:00", "09:30", "11:00", "12:30", "14:00", "15:30", "17:00"];

$flight_summary = $conn->query("SELECT schedules.*, users.nome_de_pista AS instructor_track_name, aeronaves.matricula FROM schedules JOIN users ON schedules.instructor_id = users.id JOIN aeronaves ON schedules.aircraft_id = aeronaves.id WHERE schedules.user_id = " . $_SESSION['user_id'] . " AND schedules.status = 'Fechado' ORDER BY date DESC");

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <title>Agendamentos</title>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const alert = document.getElementById('alert');
            if (alert) {
                setTimeout(() => {
                    alert.style.display = 'none';
                }, 3000); // Hide after 3 seconds
            }
        });
    </script>
</head>
<body>
    <header>
        <h1>Agendamentos</h1>
    </header>
    <section class="schedule-container">
        <?php if ($alert_message): ?>
            <div class="alert alert-<?php echo $alert_type; ?>" id="alert"><?php echo $alert_message; ?></div>
        <?php endif; ?>
        <h2>Extrato de Voos</h2>
        <div class="table-responsive">
            <table class="user-schedule-table">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Hora do Voo</th>
                        <th>Instrutor</th>
                        <th>N° de Pousos</th>
                        <th>Função em Voo do Aluno</th>
                        <th>Horas de Voo</th>
                        <th>Matrícula</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($flight = $flight_summary->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo date('d/m/Y', strtotime($flight['date'])); ?></td>
                            <td><?php echo date('H:i', strtotime($flight['start_time'])); ?></td>
                            <td><?php echo $flight['instructor_track_name']; ?></td>
                            <td><?php echo $flight['num_landings']; ?></td>
                            <td><?php echo $flight['student_flight_role']; ?></td>
                            <td><?php echo $flight['flight_hours']; ?></td>
                            <td><?php echo $flight['matricula']; ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <hr>
        <h2>Meus Agendamentos</h2>
        <div class="table-responsive">
            <table class="user-schedule-table">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Hora de Início</th>
                        <th>Hora de Término</th>
                        <th>Pista</th>
                        <th>Matrícula</th>
                        <th>Modelo</th>
                        <th>Ação</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $user_schedules = $conn->query("SELECT schedules.*, aeronaves.matricula, aeronaves.modelo FROM schedules JOIN aeronaves ON schedules.aircraft_id = aeronaves.id WHERE schedules.user_id = " . $_SESSION['user_id'] . " AND schedules.status != 'Fechado' ORDER BY date, start_time");
                    while ($schedule = $user_schedules->fetch_assoc()) {
                        $schedule_time = strtotime($schedule['date'] . ' ' . $schedule['start_time']);
                        $current_time = time();
                        $can_cancel = ($schedule_time - $current_time) >= 86400; // 24 hours in seconds
                        echo '<tr>
                            <td>' . date('d/m/Y', strtotime($schedule['date'])) . '</td>
                            <td>' . date('H:i', strtotime($schedule['start_time'])) . '</td>
                            <td>' . date('H:i', strtotime($schedule['end_time'])) . '</td>
                            <td>' . $schedule['track_name'] . '</td>
                            <td>' . $schedule['matricula'] . '</td>
                            <td>' . $schedule['modelo'] . '</td>';
                        if ($can_cancel) {
                            echo '<td>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="cancel_schedule_id" value="' . $schedule['id'] . '">
                                    <button type="submit">Cancelar</button>
                                </form>
                            </td>';
                        } else {
                            echo '<td>Não pode cancelar</td>';
                        }
                        echo '</tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>
        <hr>
        <h2>Agendar Horário</h2>
        <form class="schedule-form" method="POST">
            <label for="date">Data:</label>
            <input type="date" id="date" name="date" required>
            <label for="start_time">Hora de Início:</label>
            <select id="start_time" name="start_time" required>
                <?php foreach ($time_slots as $slot): ?>
                    <option value="<?php echo $slot; ?>"><?php echo $slot; ?></option>
                <?php endforeach; ?>
            </select>
            <label for="aircraft_id">Aeronave:</label>
            <select id="aircraft_id" name="aircraft_id" required>
                <?php foreach ($aircrafts_array as $aircraft): ?>
                    <option value="<?php echo $aircraft['id']; ?>"><?php echo $aircraft['matricula']; ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit">Agendar</button>
        </form>
        <hr>
        <h2>Verificar Disponibilidade</h2>
        <form class="schedule-form" method="POST">
            <label for="check_date">Data:</label>
            <input type="date" id="check_date" name="check_date" required>
            <label for="check_start_time">Hora de Início:</label>
            <select id="check_start_time" name="check_start_time" required>
                <?php foreach ($time_slots as $slot): ?>
                    <option value="<?php echo $slot; ?>"><?php echo $slot; ?></option>
                <?php endforeach; ?>
            </select>
            <label for="check_aircraft_id">Aeronave:</label>
            <select id="check_aircraft_id" name="check_aircraft_id" required>
                <?php foreach ($aircrafts_array as $aircraft): ?>
                    <option value="<?php echo $aircraft['id']; ?>"><?php echo $aircraft['matricula']; ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit">Verificar</button>
        </form>
    </section>
    
<br>
    
    <footer>
        <p>&copy; 2025 ACBP</p>
    </footer>
</body>
</html>
