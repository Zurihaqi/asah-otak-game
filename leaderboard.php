<?php
// Konfigurasi Database
$host = 'localhost';
$db = 'asah_otak';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    throw new \PDOException($e->getMessage(), (int) $e->getCode());
}

// Ambil data leaderboard
$stmt = $pdo->query("SELECT nama_user, total_point FROM point_game ORDER BY total_point DESC LIMIT 10");
$leaderboard = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Papan Peringkat - Asah Otak Game</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f3f4f6;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
            box-sizing: border-box;
        }

        .container {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 2rem;
            max-width: 600px;
            width: 100%;
        }

        h1 {
            color: #1f2937;
            font-size: 2rem;
            margin-bottom: 1.5rem;
            text-align: center;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 1.5rem;
        }

        th,
        td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }

        th {
            background-color: #f9fafb;
            font-weight: 600;
            color: #4b5563;
        }

        tr:nth-child(even) {
            background-color: #f9fafb;
        }

        .back-link {
            display: block;
            text-align: center;
            color: #3b82f6;
            text-decoration: none;
        }

        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>Papan Peringkat</h1>
        <table>
            <thead>
                <tr>
                    <th>Peringkat</th>
                    <th>Nama</th>
                    <th>Poin</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($leaderboard as $index => $score): ?>
                    <tr>
                        <td><?php echo $index + 1; ?></td>
                        <td><?php echo htmlspecialchars($score['nama_user']); ?></td>
                        <td><?php echo $score['total_point']; ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <a href="index.php" class="back-link">Kembali ke Permainan</a>
    </div>
</body>

</html>