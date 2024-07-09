<?php
$gamesDirectory = '/var/www/html/strefagier/gry/';
$games = array_map('basename', array_filter(glob($gamesDirectory . '*'), 'is_dir'));

function getTop3Scores($game) {
    $scoresFile = $GLOBALS['gamesDirectory'] . $game . '/scores.json';
    if (file_exists($scoresFile)) {
        $scores = json_decode(file_get_contents($scoresFile), true);
        if (is_array($scores)) {
            usort($scores, function($a, $b) { return $b['score'] - $a['score']; });
            return array_slice($scores, 0, 3);
        }
    }
    return [];
}

function getPopularGame() {
    $popularityFile = 'game_popularity.json';
    if (file_exists($popularityFile)) {
        $popularity = json_decode(file_get_contents($popularityFile), true);
        if (is_array($popularity)) {
            arsort($popularity);
            return key($popularity);
        }
    }
    return '';
}

function updatePopularity($game) {
    $popularityFile = 'game_popularity.json';
    $popularity = file_exists($popularityFile) ? json_decode(file_get_contents($popularityFile), true) : [];
    $currentWeek = date('oW'); // Year and week number

    if (!is_array($popularity)) {
        $popularity = [];
    }

    if (!isset($popularity[$currentWeek])) {
        $popularity[$currentWeek] = [];
    }

    if (!isset($popularity[$currentWeek][$game])) {
        $popularity[$currentWeek][$game] = 0;
    }

    $popularity[$currentWeek][$game]++;
    file_put_contents($popularityFile, json_encode($popularity));
}

if (isset($_POST['play_game'])) {
    $game = $_POST['play_game'];
    if (in_array($game, $games)) {
        updatePopularity($game);
        header('Location: gry/' . $game);
        exit;
    }
}

$popularGame = getPopularGame();

function getAllTopScores() {
    global $games, $gamesDirectory;
    $allScores = [];
    foreach ($games as $game) {
        $scoresFile = $gamesDirectory . $game . '/scores.json';
        if (file_exists($scoresFile)) {
            $scores = json_decode(file_get_contents($scoresFile), true);
            if (is_array($scores)) {
                foreach ($scores as $score) {
                    $allScores[] = ['game' => $game, 'name' => $score['name'], 'score' => $score['score']];
                }
            }
        }
    }
    usort($allScores, function($a, $b) { return $b['score'] - $a['score']; });
    return array_slice($allScores, 0, 10);
}

$overallTopScores = getAllTopScores();
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Futurystyczne Arcade</title>
    <link href="https://fonts.googleapis.com/css2?family=Audiowide&display=swap" rel="stylesheet">
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
            font-family: 'Audiowide', cursive;
            background-color: var(--background-color);
            color: var(--text-color);
            overflow-x: hidden;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        h1, h2 {
            font-size: 3rem;
            text-align: center;
            margin-bottom: 2rem;
            color: var(--primary-color);
            text-shadow: 0 0 10px var(--primary-color);
            animation: pulsate 2s infinite alternate;
        }

        h2 {
            font-size: 2rem;
        }

        @keyframes pulsate {
            0% { text-shadow: 0 0 10px var(--primary-color); }
            100% { text-shadow: 0 0 20px var(--primary-color), 0 0 30px var(--secondary-color); }
        }

        .game-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 2rem;
        }

        .game-card {
            background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
            border-radius: 15px;
            overflow: hidden;
            transition: all 0.3s ease;
            position: relative;
        }

        .game-card.popular {
            box-shadow: 0 0 20px var(--secondary-color);
        }

        .game-card::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: conic-gradient(
                transparent, 
                rgba(255, 255, 255, 0.3), 
                transparent 30%
            );
            animation: rotate 4s linear infinite;
        }

        @keyframes rotate {
            100% { transform: rotate(1turn); }
        }

        .game-card-inner {
            background-color: rgba(10, 10, 42, 0.8);
            padding: 1.5rem;
            position: relative;
            z-index: 1;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            align-items: center;
        }

        .game-name {
            font-size: 1.2rem;
            text-align: center;
            color: var(--text-color);
            text-shadow: 0 0 5px var(--primary-color);
            margin-bottom: 1rem;
        }

        .top-scores {
            width: 100%;
            font-size: 0.9rem;
        }

        .top-scores li {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
        }

        .play-button {
            background: var(--secondary-color);
            color: var(--text-color);
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .play-button:hover {
            background: var(--primary-color);
            transform: scale(1.05);
        }

        .game-card:hover {
            transform: translateY(-10px) scale(1.05);
            box-shadow: 0 10px 20px rgba(0, 255, 255, 0.3);
        }

        .game-card:hover .game-name {
            animation: glitch 1s linear infinite;
        }

        @keyframes glitch {
            2%, 64% {
                transform: translate(2px,0) skew(0deg);
            }
            4%, 60% {
                transform: translate(-2px,0) skew(0deg);
            }
            62% {
                transform: translate(0,0) skew(5deg); 
            }
        }

        .overall-top {
            margin-top: 3rem;
            background: rgba(255, 255, 255, 0.1);
            padding: 2rem;
            border-radius: 15px;
        }

        .overall-top ul {
            list-style-type: none;
        }

        .overall-top li {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
            padding: 0.5rem;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 5px;
        }

        @media (max-width: 768px) {
            h1 {
                font-size: 2rem;
            }
            h2 {
                font-size: 1.5rem;
            }
            .game-grid {
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
                gap: 1rem;
            }
        }
    </style>
</head>
<div class="container">
        <h1>Futurystyczne Arcade</h1>
        <div class="game-grid">
            <?php foreach ($games as $game): 
                $top3 = getTop3Scores($game);
            ?>
                <div class="game-card <?= $game === $popularGame ? 'popular' : '' ?>">
                    <div class="game-card-inner">
                        <span class="game-name"><?= ucfirst(htmlspecialchars($game)) ?></span>
                        <div class="top-scores">
                            <h3>Top 3:</h3>
                            <ul>
                                <?php if (empty($top3)): ?>
                                    <li>Brak wyników</li>
                                <?php else: ?>
                                    <?php foreach ($top3 as $index => $score): ?>
                                        <li>
                                            <span><?= $index + 1 ?>. <?= htmlspecialchars($score['name']) ?></span>
                                            <span><?= htmlspecialchars($score['score']) ?></span>
                                        </li>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </ul>
                        </div>
                        <form method="post">
                            <input type="hidden" name="play_game" value="<?= htmlspecialchars($game) ?>">
                            <button type="submit" class="play-button">Graj</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="overall-top">
            <h2>Ogólna Topka Gier</h2>
            <ul>
                <?php if (empty($overallTopScores)): ?>
                    <li>Brak wyników</li>
                <?php else: ?>
                    <?php foreach ($overallTopScores as $index => $score): ?>
                        <li>
                            <span><?= $index + 1 ?>. <?= htmlspecialchars($score['name']) ?> (<?= ucfirst(htmlspecialchars($score['game'])) ?>)</span>
                            <span><?= htmlspecialchars($score['score']) ?></span>
                        </li>
                    <?php endforeach; ?>
                <?php endif; ?>
            </ul>
        </div>
    </div>

    <script>
    function updateGameList() {
        fetch(window.location.href, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=update_games'
        })
        .then(response => response.text())
        .then(html => {
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const newGameGrid = doc.querySelector('.game-grid');
            document.querySelector('.game-grid').innerHTML = newGameGrid.innerHTML;
        })
        .catch(error => console.error('Error:', error));
    }

    setInterval(updateGameList, 30000); // Aktualizuj co 30 sekund
    </script>

    <?php
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_games') {
        foreach ($games as $game) {
            $top3 = getTop3Scores($game);
            echo "<div class='game-card " . ($game === $popularGame ? 'popular' : '') . "'>";
            echo "<div class='game-card-inner'>";
            echo "<span class='game-name'>" . ucfirst(htmlspecialchars($game)) . "</span>";
            echo "<div class='top-scores'><h3>Top 3:</h3><ul>";
            if (empty($top3)) {
                echo "<li>Brak wyników</li>";
            } else {
                foreach ($top3 as $index => $score) {
                    echo "<li><span>" . ($index + 1) . ". " . htmlspecialchars($score['name']) . "</span><span>" . htmlspecialchars($score['score']) . "</span></li>";
                }
            }
            echo "</ul></div>";
            echo "<form method='post'><input type='hidden' name='play_game' value='" . htmlspecialchars($game) . "'><button type='submit' class='play-button'>Graj</button></form>";
            echo "</div></div>";
        }
        exit;
    }
    ?>
</body>
</html>