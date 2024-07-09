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

$gridSize = 20;
$initialSnakeLength = 5;
$snake = array_fill(0, $initialSnakeLength, [$gridSize / 2, $gridSize / 2]);
$food = [rand(0, $gridSize - 1), rand(0, $gridSize - 1)];

// Wczytaj aktualne wyniki
$currentScores = getScores();
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Futurystyczny Snake</title>
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

        html, body {
            height: 100%;
            overflow: hidden;
        }

        body {
            font-family: 'Orbitron', sans-serif;
            background-color: var(--background-color);
            color: var(--text-color);
        }

        .page-container {
            height: 100%;
            overflow-y: auto;
            padding: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .game-container {
            text-align: center;
            background: rgba(255, 255, 255, 0.1);
            padding: 2rem;
            border-radius: 20px;
            box-shadow: 0 0 20px rgba(0, 255, 255, 0.3);
            margin-top: 60px;
            width: 100%;
            max-width: 800px;
        }

        h1 {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: var(--primary-color);
            text-shadow: 0 0 10px var(--primary-color);
            animation: pulsate 2s infinite alternate;
        }

        @keyframes pulsate {
            0% { text-shadow: 0 0 10px var(--primary-color); }
            100% { text-shadow: 0 0 20px var(--primary-color), 0 0 30px var(--secondary-color); }
        }

        #gameCanvas {
            border: 2px solid var(--primary-color);
            border-radius: 10px;
            box-shadow: 0 0 20px var(--primary-color);
            max-width: 100%;
            height: auto;
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

        @media (max-width: 600px) {
            .game-container {
                padding: 1rem;
                margin-top: 80px;
            }
            h1 {
                font-size: 2rem;
            }
            #returnToHub {
                font-size: 0.9rem;
                padding: 0.5rem 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="page-container">
        <button id="returnToHub" onclick="window.location.href='/'">Powrót do Hub</button>
        <div class="game-container">
            <h1>Futurystyczny Snake</h1>
            <canvas id="gameCanvas" width="400" height="400"></canvas>
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
    </div>

    <script>
        const canvas = document.getElementById('gameCanvas');
        const ctx = canvas.getContext('2d');
        const scoreElement = document.getElementById('score');
        const highScoreElement = document.getElementById('highScore');
        const leaderboardElement = document.getElementById('leaderboardList');
        const startButton = document.getElementById('startButton');
        const restartButton = document.getElementById('restartButton');
        const playerNameInput = document.getElementById('playerName');

        const gridSize = <?php echo $gridSize; ?>;
        const cellSize = canvas.width / gridSize;
        let snake = <?php echo json_encode($snake); ?>;
        let food = <?php echo json_encode($food); ?>;
        let direction = 'right';
        let nextDirection = 'right';
        let score = 0;
        let highScore = 0;
        let gameLoop;
        let gameStarted = false;

        function drawSnake() {
            ctx.fillStyle = '#00ffff';
            snake.forEach((segment, index) => {
                const gradient = ctx.createLinearGradient(
                    segment[0] * cellSize, 
                    segment[1] * cellSize, 
                    (segment[0] + 1) * cellSize, 
                    (segment[1] + 1) * cellSize
                );
                gradient.addColorStop(0, '#00ffff');
                gradient.addColorStop(1, '#ff00ff');
                ctx.fillStyle = gradient;
                ctx.fillRect(segment[0] * cellSize, segment[1] * cellSize, cellSize - 1, cellSize - 1);
            });
        }

        function drawFood() {
            const gradient = ctx.createRadialGradient(
                (food[0] + 0.5) * cellSize, 
                (food[1] + 0.5) * cellSize, 
                0, 
                (food[0] + 0.5) * cellSize, 
                (food[1] + 0.5) * cellSize, 
                cellSize / 2
            );
            gradient.addColorStop(0, '#ff00ff');
            gradient.addColorStop(1, '#00ffff');
            ctx.fillStyle = gradient;
            ctx.beginPath();
            ctx.arc((food[0] + 0.5) * cellSize, (food[1] + 0.5) * cellSize, cellSize / 2, 0, 2 * Math.PI);
            ctx.fill();
        }

        function moveSnake() {
            direction = nextDirection;
            const head = [...snake[0]];
            switch (direction) {
                case 'up': head[1]--; break;
                case 'down': head[1]++; break;
                case 'left': head[0]--; break;
                case 'right': head[0]++; break;
            }
            snake.unshift(head);

            if (head[0] === food[0] && head[1] === food[1]) {
                score++;
                updateScore();
                placeFood();
            } else {
                snake.pop();
            }
        }

        function placeFood() {
            do {
                food = [Math.floor(Math.random() * gridSize), Math.floor(Math.random() * gridSize)];
            } while (snake.some(segment => segment[0] === food[0] && segment[1] === food[1]));
        }

        function checkCollision() {
            const [headX, headY] = snake[0];
            return headX < 0 || headX >= gridSize || headY < 0 || headY >= gridSize ||
                snake.slice(1).some(segment => segment[0] === headX && segment[1] === headY);
        }

        function updateScore() {
            scoreElement.textContent = `Wynik: ${score}`;
            if (score > highScore) {
                highScore = score;
                highScoreElement.textContent = `Najlepszy wynik: ${highScore}`;
            }
        }

        function gameLoopFunction() {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            moveSnake();
            if (checkCollision()) {
                endGame();
                return;
            }
            drawSnake();
            drawFood();
        }

        function startGame() {
            if (playerNameInput.value.trim() === '') {
                alert('Proszę wprowadzić imię!');
                return;
            }
            gameStarted = true;
            startButton.style.display = 'none';
            restartButton.style.display = 'none';
            playerNameInput.disabled = true;
            gameLoop = setInterval(gameLoopFunction, 100);
        }

        function endGame() {
            clearInterval(gameLoop);
            gameStarted = false;
            restartButton.style.display = 'inline-block';
            saveScore(playerNameInput.value, score);
            playerNameInput.disabled = false;
        }

        function resetGame() {
            snake = Array(5).fill().map((_, i) => [Math.floor(gridSize / 2) - i, Math.floor(gridSize / 2)]);
            direction = 'right';
            nextDirection = 'right';
            score = 0;
            updateScore();
            placeFood();
            startGame();
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

        document.addEventListener('keydown', event => {
            if (gameStarted) {
                switch (event.key) {
                    case 'ArrowUp':
                    case 'w':
                    case 'W':
                        if (direction !== 'down') nextDirection = 'up';
                        break;
                    case 'ArrowDown':
                    case 's':
                    case 'S':
                        if (direction !== 'up') nextDirection = 'down';
                        break;
                    case 'ArrowLeft':
                    case 'a':
                    case 'A':
                        if (direction !== 'right') nextDirection = 'left';
                        break;
                    case 'ArrowRight':
                    case 'd':
                    case 'D':
                        if (direction !== 'left') nextDirection = 'right';
                        break;
                }
            }
        });

        startButton.addEventListener('click', startGame);
        restartButton.addEventListener('click', resetGame);

        // Inicjalizacja gry i wyników
        const initialScores = <?php echo json_encode($currentScores); ?>;
        updateLeaderboard(initialScores);
        
        // Ustawienie najwyższego wyniku
        if (initialScores.length > 0) {
            highScore = initialScores[0].score;
            highScoreElement.textContent = `Najlepszy wynik: ${highScore}`;
        }

        drawSnake();
        drawFood();
    </script>
</body>
</html>