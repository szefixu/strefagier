<?php
session_start();

$scoresFile = 'scores.json';

function ensureScoresFileExists() {
    global $scoresFile;
    if (!file_exists($scoresFile)) {
        file_put_contents($scoresFile, json_encode([]));
    }
}

function saveScore($name, $score) {
    global $scoresFile;
    ensureScoresFileExists();
    $scores = getScores();
    $playerIndex = array_search($name, array_column($scores, 'name'));
    if ($playerIndex !== false) {
        if ($score > $scores[$playerIndex]['score']) {
            $scores[$playerIndex]['score'] = $score;
        }
    } else {
        $scores[] = ['name' => $name, 'score' => $score];
    }
    usort($scores, function($a, $b) {
        return $b['score'] - $a['score'];
    });
    $scores = array_slice($scores, 0, 10);
    file_put_contents($scoresFile, json_encode($scores));
    return $scores;
}

function getScores() {
    global $scoresFile;
    ensureScoresFileExists();
    $scores = json_decode(file_get_contents($scoresFile), true);
    return is_array($scores) ? $scores : [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (isset($data['name']) && isset($data['score'])) {
        $updatedScores = saveScore($data['name'], $data['score']);
        echo json_encode($updatedScores);
        exit;
    }
}

$currentScores = getScores();
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Futurystyczny Saper</title>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #00ffff;
            --secondary-color: #ff00ff;
            --background-color: #0a0a2a;
            --text-color: #ffffff;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Orbitron', sans-serif;
            background-color: var(--background-color);
            color: var(--text-color);
            display: flex;
            flex-direction: column;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }

        .game-container {
            text-align: center;
            background: rgba(255, 255, 255, 0.1);
            padding: 2rem;
            border-radius: 20px;
            box-shadow: 0 0 20px rgba(0, 255, 255, 0.3);
            margin-top: 60px;
            width: 100%;
            max-width: 600px;
        }

        h1 {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            color: var(--primary-color);
            text-shadow: 0 0 10px var(--primary-color);
            animation: pulsate 2s infinite alternate;
        }

        @keyframes pulsate {
            0% { text-shadow: 0 0 10px var(--primary-color); }
            100% { text-shadow: 0 0 20px var(--primary-color), 0 0 30px var(--secondary-color); }
        }

        #gameBoard {
            display: grid;
            grid-template-columns: repeat(10, 1fr);
            gap: 2px;
            margin: 20px auto;
            max-width: 400px;
        }

        .cell {
            width: 100%;
            aspect-ratio: 1;
            background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
            border: none;
            color: var(--text-color);
            font-weight: bold;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .cell:hover {
            transform: scale(1.05);
            box-shadow: 0 0 10px var(--primary-color);
        }

        .cell.revealed {
            background: rgba(255, 255, 255, 0.2);
        }

        .cell.mine {
            background: #ff0000;
        }

        #score, #highScore {
            font-size: 1.2rem;
            margin: 1rem 0;
            color: var(--secondary-color);
        }

        #playerName {
            font-family: 'Orbitron', sans-serif;
            background: rgba(255, 255, 255, 0.1);
            border: 2px solid var(--primary-color);
            color: var(--text-color);
            padding: 0.5rem;
            margin: 1rem 0;
            width: 100%;
            max-width: 300px;
            font-size: 1rem;
            border-radius: 5px;
        }

        button {
            font-family: 'Orbitron', sans-serif;
            background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
            border: none;
            color: var(--text-color);
            padding: 0.7rem 1.5rem;
            font-size: 1rem;
            cursor: pointer;
            border-radius: 5px;
            transition: all 0.3s ease;
            margin: 0.5rem;
        }

        button:hover {
            transform: scale(1.05);
            box-shadow: 0 0 15px var(--primary-color);
        }

        #returnToHub {
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 10;
        }

        #leaderboard {
            margin-top: 2rem;
            background: rgba(255, 255, 255, 0.1);
            padding: 1rem;
            border-radius: 10px;
            width: 100%;
            max-width: 300px;
        }

        #leaderboard h2 {
            color: var(--primary-color);
            margin-bottom: 1rem;
        }

        #leaderboardList {
            list-style-type: none;
            padding: 0;
        }

        #leaderboardList li {
            background: rgba(255, 255, 255, 0.05);
            margin: 0.5rem 0;
            padding: 0.5rem;
            border-radius: 5px;
            display: flex;
            justify-content: space-between;
        }
    </style>
</head>
<body>
    <button id="returnToHub" onclick="window.location.href='/'">Powrót do Hub</button>
    <div class="game-container">
        <h1>Futurystyczny Saper</h1>
        <div id="gameBoard"></div>
        <div id="score">Wynik: 0</div>
        <div id="highScore">Najlepszy wynik: 0</div>
        <input type="text" id="playerName" placeholder="Wprowadź swoje imię" maxlength="20">
        <button id="startButton">Rozpocznij grę</button>
        <button id="restartButton" style="display:none;">Zagraj ponownie</button>
        <div id="leaderboard">
            <h2>Najlepsze wyniki</h2>
            <ol id="leaderboardList"></ol>
        </div>
    </div>

    <script>
        const boardSize = 10;
        const mineCount = 15;
        let board = [];
        let revealed = [];
        let score = 0;
        let highScore = 0;
        let gameStarted = false;

        const gameBoard = document.getElementById('gameBoard');
        const scoreElement = document.getElementById('score');
        const highScoreElement = document.getElementById('highScore');
        const leaderboardElement = document.getElementById('leaderboardList');
        const startButton = document.getElementById('startButton');
        const restartButton = document.getElementById('restartButton');
        const playerNameInput = document.getElementById('playerName');

        function createBoard() {
            board = Array(boardSize).fill().map(() => Array(boardSize).fill(0));
            revealed = Array(boardSize).fill().map(() => Array(boardSize).fill(false));

            // Place mines
            let minesPlaced = 0;
            while (minesPlaced < mineCount) {
                const x = Math.floor(Math.random() * boardSize);
                const y = Math.floor(Math.random() * boardSize);
                if (board[y][x] !== 'mine') {
                    board[y][x] = 'mine';
                    minesPlaced++;
                }
            }

            // Calculate numbers
            for (let y = 0; y < boardSize; y++) {
                for (let x = 0; x < boardSize; x++) {
                    if (board[y][x] !== 'mine') {
                        board[y][x] = countAdjacentMines(x, y);
                    }
                }
            }
        }

        function countAdjacentMines(x, y) {
            let count = 0;
            for (let dy = -1; dy <= 1; dy++) {
                for (let dx = -1; dx <= 1; dx++) {
                    const ny = y + dy;
                    const nx = x + dx;
                    if (ny >= 0 && ny < boardSize && nx >= 0 && nx < boardSize && board[ny][nx] === 'mine') {
                        count++;
                    }
                }
            }
            return count;
        }

        function revealCell(x, y) {
            if (x < 0 || x >= boardSize || y < 0 || y >= boardSize || revealed[y][x]) return;

            revealed[y][x] = true;
            const cell = gameBoard.children[y * boardSize + x];
            cell.classList.add('revealed');

            if (board[y][x] === 'mine') {
                cell.classList.add('mine');
                cell.textContent = '💣';
                endGame(false);
            } else {
                if (board[y][x] > 0) {
                    cell.textContent = board[y][x];
                } else {
                    // Reveal adjacent cells
                    for (let dy = -1; dy <= 1; dy++) {
                        for (let dx = -1; dx <= 1; dx++) {
                            revealCell(x + dx, y + dy);
                        }
                    }
                }
                score += 12
                updateScore();
                checkWin();
            }
        }

        function checkWin() {
            const totalCells = boardSize * boardSize;
            const revealedCount = revealed.flat().filter(cell => cell).length;
            if (revealedCount === totalCells - mineCount) {
                endGame(true);
            }
        }

        function updateScore() {
            scoreElement.textContent = `Wynik: ${score}`;
            if (score > highScore) {
                highScore = score;
                highScoreElement.textContent = `Najlepszy wynik: ${highScore}`;
            }
        }

        function startGame() {
            if (playerNameInput.value.trim() === '') {
                alert('Proszę wprowadzić imię!');
                return;
            }
            gameStarted = true;
            score = 0;
            updateScore();
            createBoard();
            renderBoard();
            startButton.style.display = 'none';
            restartButton.style.display = 'none';
            playerNameInput.disabled = true;
        }

        function endGame(isWin) {
            gameStarted = false;
            restartButton.style.display = 'inline-block';
            if (isWin) {
                alert('Gratulacje! Wygrałeś!');
                score += 500; // Bonus za wygraną
            } else {
                alert('Boom! Koniec gry.');
            }
            updateScore();
            saveScore(playerNameInput.value, score);
            playerNameInput.disabled = false;
        }

        function renderBoard() {
            gameBoard.innerHTML = '';
            for (let y = 0; y < boardSize; y++) {
                for (let x = 0; x < boardSize; x++) {
                    const cell = document.createElement('button');
                    cell.classList.add('cell');
                    cell.addEventListener('click', () => {
                        if (gameStarted) revealCell(x, y);
                    });
                    gameBoard.appendChild(cell);
                }
            }
        }

        function saveScore(name, score) {
            fetch('', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({name: name, score: score})
            })
            .then(response => response.json())
            .then(updateLeaderboard)
            .catch(error => console.error('Error:', error));
        }

        function updateLeaderboard(scores) {
            leaderboardElement.innerHTML = '';
            scores.forEach((score, index) => {
                const li = document.createElement('li');
                li.innerHTML = `<span>${index + 1}. ${score.name}</span> <span>${score.score}</span>`;
                leaderboardElement.appendChild(li);
            });
        }

        startButton.addEventListener('click', startGame);
        restartButton.addEventListener('click', startGame);

        // Inicjalizacja wyników
        const initialScores = <?php echo json_encode($currentScores); ?>;
        updateLeaderboard(initialScores);
        
        if (initialScores.length > 0) {
            highScore = initialScores[0].score;
            highScoreElement.textContent = `Najlepszy wynik: ${highScore}`;
        }
    </script>
</body>
</html>