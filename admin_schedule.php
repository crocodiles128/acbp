<?php
session_start();
include 'db_connection.php';

if ($_SESSION['cargo'] !== 'Operacoes' && $_SESSION['cargo'] !== 'Admin') {
    header('Location: index.php');
    exit();
}

$alert_message = '';
$alert_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['cancel_schedule_id'])) {
        $schedule_id = $_POST['cancel_schedule_id'];
        $stmt = $conn->prepare("DELETE FROM schedules WHERE id = ?");
        $stmt->bind_param("i", $schedule_id);
        $stmt->execute();
        $stmt->close();
    } elseif (isset($_POST['assign_instructor_id'], $_POST['schedule_id'])) {
        $instructor_id = $_POST['assign_instructor_id'];
        $schedule_id = $_POST['schedule_id'];

        // Check if the instructor is already assigned to another flight at the same time
        $stmt = $conn->prepare("SELECT COUNT(*) FROM schedules WHERE id != ? AND instructor_id = ? AND date = (SELECT date FROM schedules WHERE id = ?) AND start_time = (SELECT start_time FROM schedules WHERE id = ?)");
        $stmt->bind_param("iiii", $schedule_id, $instructor_id, $schedule_id, $schedule_id);
        $stmt->execute();
        $stmt->bind_result($count);
        $stmt->fetch();
        $stmt->close();

        if ($count > 0) {
            $alert_message = 'Instrutor já designado para outro voo neste horário.';
            $alert_type = 'danger';
        } else {
            $stmt = $conn->prepare("UPDATE schedules SET instructor_id = ? WHERE id = ?");
            $stmt->bind_param("ii", $instructor_id, $schedule_id);
            $stmt->execute();
            $stmt->close();
        }
    } elseif (isset($_POST['date'], $_POST['start_time'], $_POST['aircraft_id'], $_POST['student_id']) && !empty($_POST['date']) && !empty($_POST['start_time']) && !empty($_POST['aircraft_id']) && !empty($_POST['student_id'])) {
        $user_id = $_POST['student_id'];
        $date = $_POST['date'];
        $start_time = $_POST['start_time'];
        $end_time = date('H:i', strtotime($start_time) + 3600); // 1 hour duration
        $aircraft_id = $_POST['aircraft_id'];

        // Fetch the track name for the selected student
        $stmt = $conn->prepare("SELECT nome_de_pista FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->bind_result($track_name);
        $stmt->fetch();
        $stmt->close();

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
            $alert_message = 'O aluno já tem um voo agendado para este horário.';
            $alert_type = 'danger';
        } elseif ($count > 0) {
            $alert_message = 'Aeronave já agendada para este horário.';
            $alert_type = 'danger';
        } else {
            $stmt = $conn->prepare("INSERT INTO schedules (user_id, date, start_time, end_time, track_name, aircraft_id) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("issssi", $user_id, $date, $start_time, $end_time, $track_name, $aircraft_id);
            $stmt->execute();
            $stmt->close();
        }
    } else {
        $alert_message = 'All fields are required.';
        $alert_type = 'danger';
    }
}

// Fetch students and special users for scheduling
$students = $conn->query("SELECT id, nome_de_pista FROM users WHERE cargo = 'Aluno' OR cargo = 'Admin' OR cargo = 'Operacoes' OR cargo = 'Manutencao'");
$aircrafts = $conn->query("SELECT id, matricula, modelo FROM aeronaves");
$instructors = $conn->query("SELECT id, nome_de_pista FROM users WHERE cargo = 'INVA'");

$students_array = [];
while ($student = $students->fetch_assoc()) {
    $students_array[] = $student;
}

$aircrafts_array = [];
while ($aircraft = $aircrafts->fetch_assoc()) {
    $aircrafts_array[] = $aircraft;
}

$instructors_array = [];
while ($instructor = $instructors->fetch_assoc()) {
    $instructors_array[] = $instructor;
}

$time_slots = ["08:00", "09:30", "11:00", "12:30", "14:00", "15:30", "17:00"];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <title>Admin Agendamentos</title>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const alert = document.getElementById('alert');
            if (alert) {
                setTimeout(() => {
                    alert.style.display = 'none';
                }, 3000); // Hide after 3 seconds
            }

            const studentSearch = document.getElementById('student_search');
            const studentDropdown = document.getElementById('student_id');

            studentSearch.addEventListener('input', function() {
                const filter = studentSearch.value.toLowerCase();
                const options = studentDropdown.options;

                for (let i = 0; i < options.length; i++) {
                    const option = options[i];
                    const text = option.text.toLowerCase();
                    option.style.display = text.includes(filter) ? '' : 'none';
                }
            });
        });
    </script>
</head>
<body>
    <header>
        <h1>Admin Agendamentos</h1>
        <?php if ($_SESSION['cargo'] === 'Admin'): ?>
            <nav>
                <a href="admin_users_aircrafts.php">Gerenciar Usuários e Aeronaves</a>
            </nav>
        <?php endif; ?>
    </header>
    <section class="schedule-container">
        <?php if ($alert_message): ?>
            <div class="alert alert-<?php echo $alert_type; ?>" id="alert"><?php echo $alert_message; ?></div>
        <?php endif; ?>
        <h2>Agendar Horário para Aluno</h2>
        <form class="schedule-form" method="POST">
            <label for="student_search">Pesquisar Aluno:</label>
            <input type="text" id="student_search" placeholder="Digite o nome do aluno">
            <label for="student_id">Aluno:</label>
            <select id="student_id" name="student_id" required>
                <?php foreach ($students_array as $student): ?>
                    <option value="<?php echo $student['id']; ?>"><?php echo $student['nome_de_pista']; ?></option>
                <?php endforeach; ?>
            </select>
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
        <h2>Cancelar Agendamento</h2>
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
                        <th>Instrutor</th>
                        <th>Ação</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $schedules = $conn->query("SELECT schedules.*, users.nome_de_pista, aeronaves.matricula, aeronaves.modelo FROM schedules JOIN users ON schedules.user_id = users.id JOIN aeronaves ON schedules.aircraft_id = aeronaves.id WHERE schedules.status != 'Fechado' ORDER BY date, start_time");
                    while ($schedule = $schedules->fetch_assoc()) {
                        echo '<tr>
                            <td>' . date('d/m/Y', strtotime($schedule['date'])) . '</td>
                            <td>' . date('H:i', strtotime($schedule['start_time'])) . '</td>
                            <td>' . date('H:i', strtotime($schedule['end_time'])) . '</td>
                            <td>' . $schedule['matricula'] . '</td>
                            <td>' . $schedule['modelo'] . '</td>
                            <td>' . $schedule['nome_de_pista'] . '</td>
                            <td>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="schedule_id" value="' . $schedule['id'] . '">
                                    <select name="assign_instructor_id" required>
                                        <option value="">Selecione</option>';
                                        foreach ($instructors_array as $instructor) {
                                            echo '<option value="' . $instructor['id'] . '">' . $instructor['nome_de_pista'] . '</option>';
                                        }
                        echo '      </select>
                                    <button type="submit">Designar</button>
                                </form>
                            </td>
                            <td>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="cancel_schedule_id" value="' . $schedule['id'] . '">
                                    <button type="submit">Cancelar</button>
                                </form>
                            </td>
                        </tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </section>
    <footer>
        <p>&copy; 2025 ACBP</p>
    </footer>
</body>
</html>
