-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 22/02/2025 às 01:56
-- Versão do servidor: 10.4.32-MariaDB
-- Versão do PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `acbp`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `aeronaves`
--

CREATE TABLE `aeronaves` (
  `id` int(11) NOT NULL,
  `matricula` varchar(50) NOT NULL,
  `modelo` varchar(100) NOT NULL,
  `horas_totais` float NOT NULL, -- Changed to float
  `horas_desde_ultima_revisao` float NOT NULL, -- Changed to float
  `horas_ate_proxima_revisao` int(11) NOT NULL,
  `ultima_manutencao` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `aeronaves`
--

INSERT INTO `aeronaves` (`id`, `matricula`, `modelo`, `horas_totais`, `horas_desde_ultima_revisao`, `horas_ate_proxima_revisao`, `ultima_manutencao`) VALUES
(1, 'PT-MDN', 'C-152', 1200.0, 200.0, 1000, '2025-01-01'), -- Updated to use decimal values
(2, 'PT-XYZ', 'C-172', 1500.0, 300.0, 1200, '2025-02-01'), -- Updated to use decimal values
(3, 'PT-ABC', 'PA-28', 1800.0, 400.0, 1400, '2025-03-01'); -- Updated to use decimal values

-- --------------------------------------------------------

--
-- Estrutura para tabela `schedules`
--

CREATE TABLE `schedules` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `track_name` varchar(100) NOT NULL,
  `aircraft_id` int(11) NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'Não Iniciado',
  `instructor_id` int(11) DEFAULT NULL,
  `num_landings` int(11) DEFAULT NULL,
  `student_flight_role` varchar(100) DEFAULT NULL,
  `instructor_flight_role` varchar(100) DEFAULT NULL,
  `flight_hours` float DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `schedules`
--

INSERT INTO `schedules` (`id`, `user_id`, `date`, `start_time`, `end_time`, `track_name`, `aircraft_id`, `status`, `instructor_id`, `num_landings`, `student_flight_role`, `instructor_flight_role`, `flight_hours`) VALUES
(25, 3, '2025-04-19', '08:00:00', '09:00:00', 'ADMIN', 1, 'Não Iniciado', NULL, NULL, NULL, NULL, NULL),
(26, 4, '2025-04-19', '08:00:00', '09:00:00', 'Joaninha', 3, 'Não Iniciado', NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Estrutura para tabela `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `nome_de_pista` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `senha` varchar(255) NOT NULL,
  `curso` varchar(100) DEFAULT NULL,
  `habilitacao` varchar(100) DEFAULT NULL,
  `cargo` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `users`
--

INSERT INTO `users` (`id`, `nome`, `nome_de_pista`, `email`, `senha`, `curso`, `habilitacao`, `cargo`) VALUES
(2, 'nome teste', 'nome de pista teste', 'emial@gmail.com', '$2y$10$GK/xrsiQdHdsYzJ5znz2B.nwIv3M3olHgMOWislb/S1ROpC8P6xWK', 'PPA tri', 'N/A', 'Aluno'),
(3, 'João', 'ADMIN', 'email@gmail.com', '$2y$10$33MRFqwDBq7QVMFElem38.nh/oi.Uauuk4Bk3a.gxaGhHfyMU8iPe', 'ALL', 'ALL', 'Admin'),
(4, 'Joana', 'Joaninha', 'joana@gmail.com', '$2y$10$nBGiDSHDvUC5dxKb1Vqf0.QzeHiG5iHnBnnZhv.L8XkkpvsGgGMYO', 'PP-A ', 'N/A', 'Aluno'),
(5, 'Arthur', 'TUTU', 'arthur@gmail.com', '$2y$10$H1W/lq.mYb9iYULK1ETa0eZluYmLZyXnqPSRnnUYirR4z1820z/Sq', 'ALL', 'INVA', 'INVA'),
(6, 'operacoes', 'OPERAÇÕES', 'operacoes@gmail.com', '$2y$10$OuY/qPXiilLLa3.VySHtpO/GgtBXh5h6onumIs.j5hnSRS/BBw6ay', 'ALL', 'ALL', 'Operacoes');

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `aeronaves`
--
ALTER TABLE `aeronaves`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `schedules`
--
ALTER TABLE `schedules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `aircraft_id` (`aircraft_id`),
  ADD KEY `instructor_id` (`instructor_id`),
  ADD KEY `num_landings` (`num_landings`),
  ADD KEY `student_flight_role` (`student_flight_role`),
  ADD KEY `instructor_flight_role` (`instructor_flight_role`),
  ADD KEY `flight_hours` (`flight_hours`);

--
-- Índices de tabela `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `aeronaves`
--
ALTER TABLE `aeronaves`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de tabela `schedules`
--
ALTER TABLE `schedules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT de tabela `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
