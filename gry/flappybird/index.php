<?php
session_start();

$scoresFile = 'scores.json';

function saveScore($name, $score) {
    global $scoresFile;
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
    if (file_exists($scoresFile)) {
        $scores = json_decode(file_get_contents($scoresFile), true);
        return is_array($scores) ? $scores : [];
    }
    return [];
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
    <title>Futurystyczny Flappy Bird</title>
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
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .page-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            width: 100%;
            max-width: 800px;
            height: 100vh;
            padding: 20px;
            overflow-y: auto;
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

        #gameCanvas {
            border: 2px solid var(--primary-color);
            border-radius: 10px;
            box-shadow: 0 0 20px var(--primary-color);
            max-width: 100%;
            height: auto;
        }

        #score, #highScore {
            font-size: 1.2rem;
            margin: 0.5rem 0;
            color: var(--secondary-color);
        }

        #playerName {
            font-family: 'Orbitron', sans-serif;
            background: rgba(255, 255, 255, 0.1);
            border: 2px solid var(--primary-color);
            color: var(--text-color);
            padding: 0.5rem;
            margin: 0.5rem 0;
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
            margin-top: 1rem;
            background: rgba(255, 255, 255, 0.1);
            padding: 1rem;
            border-radius: 10px;
            width: 100%;
            max-width: 300px;
        }

        #leaderboard h2 {
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }

        #leaderboardList {
            list-style-type: none;
            padding: 0;
            max-height: 200px;
            overflow-y: auto;
        }

        #leaderboardList li {
            background: rgba(255, 255, 255, 0.05);
            margin: 0.3rem 0;
            padding: 0.3rem;
            border-radius: 5px;
            display: flex;
            justify-content: space-between;
        }

        @media (max-width: 600px) {
            h1 {
                font-size: 2rem;
            }
            #gameCanvas {
                width: 100%;
                height: auto;
            }
            #returnToHub {
                font-size: 0.9rem;
                padding: 0.5rem 1rem;
            }
        }
    </style>
</head>
<body>
    <button id="returnToHub" onclick="window.location.href='/'">Powrót do Hub</button>
    <div class="page-container">
        <h1>Futurystyczny Flappy Bird</h1>
        <canvas id="gameCanvas" width="400" height="600"></canvas>
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
        const canvas = document.getElementById('gameCanvas');
        const ctx = canvas.getContext('2d');
        const scoreElement = document.getElementById('score');
        const highScoreElement = document.getElementById('highScore');
        const leaderboardElement = document.getElementById('leaderboardList');
        const startButton = document.getElementById('startButton');
        const restartButton = document.getElementById('restartButton');
        const playerNameInput = document.getElementById('playerName');

        let bird = { x: 50, y: 300, velocity: 0 };
        let pipes = [];
        let score = 0;
        let highScore = 0;
        let gameLoop;
        let gameStarted = false;

        const gravity = 0.5;
        const jump = -9;
        const pipeWidth = 50;
        const pipeGap = 150;

        function drawBird() {
            ctx.fillStyle = '#ff00ff';
            ctx.beginPath();
            ctx.moveTo(bird.x, bird.y);
            ctx.lineTo(bird.x - 15, bird.y + 10);
            ctx.lineTo(bird.x - 15, bird.y - 10);
            ctx.closePath();
            ctx.fill();

            // Oko ptaka
            ctx.fillStyle = '#ffffff';
            ctx.beginPath();
            ctx.arc(bird.x + 5, bird.y - 5, 3, 0, Math.PI * 2);
            ctx.fill();
        }

        function drawPipes() {
            ctx.fillStyle = '#00ffff';
            pipes.forEach(pipe => {
                // Górna rura
                ctx.fillRect(pipe.x, 0, pipeWidth, pipe.top);
                drawPipeEdge(pipe.x, pipe.top, true);

                // Dolna rura
                const bottomPipeY = pipe.top + pipeGap;
                ctx.fillRect(pipe.x, bottomPipeY, pipeWidth, canvas.height - bottomPipeY);
                drawPipeEdge(pipe.x, bottomPipeY, false);
            });
        }

        function drawPipeEdge(x, y, isTop) {
            const edgeHeight = 20;
            ctx.beginPath();
            if (isTop) {
                ctx.moveTo(x, y);
                ctx.lineTo(x + pipeWidth / 2, y + edgeHeight);
                ctx.lineTo(x + pipeWidth, y);
            } else {
                ctx.moveTo(x, y);
                ctx.lineTo(x + pipeWidth / 2, y - edgeHeight);
                ctx.lineTo(x + pipeWidth, y);
            }
            ctx.closePath();
            ctx.fill();
        }

        function updateBird() {
            bird.velocity += gravity;
            bird.y += bird.velocity;

            if (bird.y > canvas.height - 20) {
                bird.y = canvas.height - 20;
                endGame();
            }
        }

        function updatePipes() {
            if (pipes.length === 0 || pipes[pipes.length - 1].x < canvas.width - 200) {
                pipes.push({
                    x: canvas.width,
                    top: Math.random() * (canvas.height - pipeGap - 100) + 50
                });
            }

            pipes.forEach(pipe => {
                pipe.x -= 2;

                if (
                    bird.x + 20 > pipe.x &&
                    bird.x - 20 < pipe.x + pipeWidth &&
                    (bird.y - 20 < pipe.top || bird.y + 20 > pipe.top + pipeGap)
                ) {
                    endGame();
                }

                if (pipe.x + pipeWidth < bird.x && !pipe.passed) {
                    score++;
                    updateScore();
                    pipe.passed = true;
                }
            });

            pipes = pipes.filter(pipe => pipe.x > -pipeWidth);
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
            updateBird();
            updatePipes();
            drawBird();
            drawPipes();
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
            bird = { x: 50, y: 300, velocity: 0 };
            pipes = [];
            score = 0;
            updateScore();
            gameLoop = setInterval(gameLoopFunction, 20);
        }

        function endGame() {
            clearInterval(gameLoop);
            gameStarted = false;
            restartButton.style.display = 'inline-block';
            saveScore(playerNameInput.value, score);
            playerNameInput.disabled = false;
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
            if (gameStarted && event.code === 'Space') {
                bird.velocity = jump;
            }
        });

        canvas.addEventListener('click', () => {
            if (gameStarted) {
                bird.velocity = jump;
            }
        });

        startButton.addEventListener('click', startGame);
        restartButton.addEventListener('click', startGame);

        // Inicjalizacja gry i wyników
        const initialScores = <?php echo json_encode($currentScores); ?>;
        updateLeaderboard(initialScores);
        
        if (initialScores.length > 0) {
            highScore = initialScores[0].score;
            highScoreElement.textContent = `Najlepszy wynik: ${highScore}`;
        }

        // Początkowe rysowanie
        drawBird();
    </script>
</body>
</html>