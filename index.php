<?php
session_start();

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

// Seeder untuk tabel master_kata
function seedMasterKata($pdo)
{
    // Cek apakah tabel sudah terisi
    $stmt = $pdo->query("SELECT COUNT(*) FROM master_kata");
    $count = $stmt->fetchColumn();

    // Jika tabel kosong, masukkan data contoh
    if ($count == 0) {
        $words = [
            ['kata' => 'GITAR', 'clue' => 'Alat musik petik'],
            ['kata' => 'GAJAH', 'clue' => 'Hewan terbesar di darat'],
            ['kata' => 'KAPAL', 'clue' => 'Transportasi laut'],
            ['kata' => 'BUNGA', 'clue' => 'Bagian dari tanaman'],
            ['kata' => 'BOLA', 'clue' => 'Alat permainan bulat']
        ];

        $stmt = $pdo->prepare("INSERT INTO master_kata (kata, clue) VALUES (:kata, :clue)");
        foreach ($words as $word) {
            $stmt->execute(['kata' => $word['kata'], 'clue' => $word['clue']]);
        }
    }
}

// Panggil fungsi seeder
seedMasterKata($pdo);

function getRandomWord($pdo)
{
    $stmt = $pdo->query("SELECT * FROM master_kata ORDER BY RAND() LIMIT 1");
    return $stmt->fetch();
}

function calculateScore($userAnswer, $correctAnswer)
{
    $score = 0;
    for ($i = 0; $i < strlen($correctAnswer); $i++) {
        if ($i == 2 || $i == 3) {
            continue; // Huruf petunjuk, tidak dihitung
        }
        if ($userAnswer[$i] == $correctAnswer[$i]) {
            $score += 10; // Huruf benar
        } else {
            $score -= 2; // Huruf salah
        }
    }
    return $score;
}

// Pilih kata baru hanya jika belum ada dalam session
if (!isset($_SESSION['word'])) {
    $_SESSION['word'] = getRandomWord($pdo);
}

$word = $_SESSION['word']['kata'];
$clue = $_SESSION['word']['clue'];

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['answer'])) {
    $userAnswer = strtoupper(implode('', $_POST['answer']));
    $score = calculateScore($userAnswer, $word);
    $_SESSION['score'] = $score;
    $_SESSION['game_over'] = true;
    $_SESSION['user_answer'] = $userAnswer;
}

if (isset($_POST['save_score']) && isset($_POST['username'])) {
    $stmt = $pdo->prepare("INSERT INTO point_game (nama_user, total_point) VALUES (:nama_user, :total_point)");
    $stmt->execute([
        'nama_user' => $_POST['username'],
        'total_point' => $_SESSION['score']
    ]);
    session_destroy();
    header("Location: index.php");
    exit;
}

if (isset($_POST['play_again'])) {
    session_destroy();
    header("Location: index.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Asah Otak Game</title>
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
            max-width: 400px;
            width: 100%;
        }

        h1 {
            color: #1f2937;
            font-size: 2rem;
            margin-bottom: 1.5rem;
            text-align: center;
        }

        .clue {
            font-size: 1.25rem;
            font-weight: 600;
            color: #4b5563;
            margin-bottom: 1.5rem;
            text-align: center;
        }

        .answer-inputs {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
        }

        input[type="text"] {
            width: 2.5rem;
            height: 2.5rem;
            font-size: 1.25rem;
            text-align: center;
            border: 2px solid #d1d5db;
            border-radius: 0.375rem;
            outline: none;
        }

        input[type="text"]:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.5);
        }

        input[type="text"][readonly] {
            background-color: #f3f4f6;
        }

        button {
            width: 100%;
            padding: 0.75rem 1rem;
            font-size: 1rem;
            font-weight: 500;
            color: white;
            background-color: #3b82f6;
            border: none;
            border-radius: 0.375rem;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        button:hover {
            background-color: #2563eb;
        }

        .result {
            font-size: 1.125rem;
            color: #4b5563;
            margin-bottom: 1rem;
            text-align: center;
        }

        .answer-display {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
        }

        .answer-letter {
            width: 2.5rem;
            height: 2.5rem;
            font-size: 1.25rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid;
            border-radius: 0.375rem;
        }

        .correct {
            background-color: #d1fae5;
            color: #065f46;
            border-color: #34d399;
        }

        .incorrect {
            background-color: #fee2e2;
            color: #991b1b;
            border-color: #f87171;
        }

        .button-group {
            display: flex;
            gap: 1rem;
        }

        .button-group button {
            flex: 1;
        }

        .save-score {
            background-color: #10b981;
        }

        .save-score:hover {
            background-color: #059669;
        }

        .leaderboard-link {
            display: block;
            text-align: center;
            margin-top: 1rem;
            color: #3b82f6;
            text-decoration: none;
        }

        .leaderboard-link:hover {
            text-decoration: underline;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>Asah Otak Game</h1>
        <?php if (!isset($_SESSION['game_over'])): ?>
            <p class="clue"><?php echo $clue; ?></p>
            <form method="post">
                <div class="answer-inputs">
                    <?php
                    for ($i = 0; $i < strlen($word); $i++) {
                        if ($i == 2 || $i == 3) {
                            echo '<input type="text" name="answer[]" value="' . $word[$i] . '" readonly>';
                        } else {
                            echo '<input type="text" name="answer[]" maxlength="1" required>';
                        }
                    }
                    ?>
                </div>
                <button type="submit">Jawab</button>
            </form>
        <?php else: ?>
            <p class="result">Poin yang Anda dapat adalah <strong><?php echo $_SESSION['score']; ?></strong></p>
            <p class="result">Jawaban yang benar: <strong><?php echo $word; ?></strong></p>
            <p class="result">Jawaban Anda:</p>
            <div class="answer-display">
                <?php
                for ($i = 0; $i < strlen($word); $i++) {
                    $class = ($_SESSION['user_answer'][$i] == $word[$i]) ? 'correct' : 'incorrect';
                    echo "<span class='answer-letter {$class}'>{$_SESSION['user_answer'][$i]}</span>";
                }
                ?>
            </div>
            <form method="post" class="button-group">
                <button type="submit" name="save_score" class="save-score">Simpan Poin</button>
                <button type="submit" name="play_again">Ulangi</button>
            </form>
        <?php endif; ?>
        <a href="leaderboard.php" class="leaderboard-link">Lihat Papan Peringkat</a>
    </div>

    <script>
        <?php if (isset($_POST['save_score'])): ?>
            let username = prompt("Masukkan nama Anda:");
            if (username) {
                let form = document.createElement('form');
                form.method = 'post';
                form.innerHTML = '<input type="hidden" name="save_score" value="1"><input type="hidden" name="username" value="' + username + '">';
                document.body.appendChild(form);
                form.submit();
            }
        <?php endif; ?>
    </script>
</body>

</html>