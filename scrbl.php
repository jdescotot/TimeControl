<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scrabble Game</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
            overflow-x: hidden;
        }

        /* Selector de jugador */
        #playerSelector {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: #000;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 40px;
            z-index: 10000;
            animation: fadeIn 0.3s ease;
        }

        .playerButton {
            width: 300px;
            height: 300px;
            font-size: 80px;
            border: none;
            border-radius: 20px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            gap: 20px;
            font-weight: bold;
            box-shadow: 0 10px 40px rgba(0,0,0,0.5);
        }

        .playerButton:hover {
            transform: scale(1.1) rotate(2deg);
            box-shadow: 0 20px 60px rgba(0,0,0,0.7);
        }

        #viejoBtn {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
        }

        #stinkyBtn {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            color: white;
        }

        .playerButton .emoji {
            font-size: 120px;
            line-height: 1;
        }

        .playerButton .name {
            font-size: 40px;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes fadeOut {
            from { opacity: 1; }
            to { opacity: 0; }
        }

        .fadeOut {
            animation: fadeOut 0.3s ease forwards;
        }

        /* Contenedor principal del juego */
        #gameContainer {
            display: none;
            max-width: 1400px;
            margin: 0 auto;
        }

        .header {
            text-align: center;
            color: white;
            margin-bottom: 20px;
        }

        .header h1 {
            font-size: 3em;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
            margin-bottom: 10px;
        }

        .scores {
            display: flex;
            justify-content: center;
            gap: 40px;
            margin-bottom: 20px;
        }

        .score {
            background: rgba(255,255,255,0.2);
            padding: 15px 30px;
            border-radius: 15px;
            backdrop-filter: blur(10px);
        }

        .score h3 {
            font-size: 1.2em;
            margin-bottom: 5px;
        }

        .score p {
            font-size: 2em;
            font-weight: bold;
        }

        .currentPlayer {
            background: rgba(255,255,255,0.4);
            box-shadow: 0 0 20px rgba(255,255,255,0.5);
        }

        .gameArea {
            display: grid;
            grid-template-columns: 1fr 300px;
            gap: 20px;
            margin-bottom: 20px;
        }

        .boardContainer {
            background: rgba(255,255,255,0.95);
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }

        #board {
            display: grid;
            grid-template-columns: repeat(15, 1fr);
            gap: 2px;
            background: #bcaaa4;
            padding: 2px;
            border-radius: 8px;
        }

        .cell {
            aspect-ratio: 1;
            background: #ddd;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 0.7em;
            font-weight: bold;
            position: relative;
            transition: all 0.2s ease;
            user-select: none;
        }

        .cell:hover {
            transform: scale(1.05);
            z-index: 10;
        }

        .cell.center {
            background: linear-gradient(135deg, #ff6b9d 0%, #c06c84 100%);
            color: white;
        }

        .cell.doubleWord {
            background: linear-gradient(135deg, #ffeaa7 0%, #fdcb6e 100%);
            color: #d63031;
        }

        .cell.tripleWord {
            background: linear-gradient(135deg, #ff7675 0%, #d63031 100%);
            color: white;
        }

        .cell.doubleLetter {
            background: linear-gradient(135deg, #74b9ff 0%, #0984e3 100%);
            color: white;
        }

        .cell.tripleLetter {
            background: linear-gradient(135deg, #a29bfe 0%, #6c5ce7 100%);
            color: white;
        }

        .cell.occupied {
            background: #f9e4b7;
            cursor: default;
        }

        .cell.temporary {
            background: #c3f7c3;
        }

        .tile {
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #ffeaa7 0%, #fdcb6e 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5em;
            font-weight: bold;
            border-radius: 4px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            position: relative;
        }

        .tile .letter {
            font-size: 1.2em;
        }

        .tile .points {
            position: absolute;
            bottom: 2px;
            right: 4px;
            font-size: 0.4em;
        }

        .sidebar {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .controls {
            background: rgba(255,255,255,0.95);
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }

        .controls h3 {
            margin-bottom: 15px;
            color: #667eea;
        }

        button {
            width: 100%;
            padding: 12px;
            margin-bottom: 10px;
            border: none;
            border-radius: 8px;
            font-size: 1em;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            color: white;
        }

        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        }

        button:active {
            transform: translateY(0);
        }

        .btnPlay {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .btnUndo {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }

        .btnShuffle {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }

        .btnExchange {
            background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
        }

        .btnNewGame {
            background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
        }

        .btnDispute {
            background: linear-gradient(135deg, #ff9a56 0%, #ff6a00 100%);
            display: none;
        }

        #rack {
            background: rgba(255,255,255,0.95);
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            min-height: 100px;
        }

        #rack h3 {
            margin-bottom: 15px;
            color: #667eea;
        }

        #rackTiles {
            display: flex;
            gap: 8px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .rackTile {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #ffeaa7 0%, #fdcb6e 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5em;
            font-weight: bold;
            border-radius: 8px;
            cursor: pointer;
            position: relative;
            transition: all 0.3s ease;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }

        .rackTile:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(0,0,0,0.3);
        }

        .rackTile.selected {
            background: linear-gradient(135deg, #74b9ff 0%, #0984e3 100%);
            transform: translateY(-5px);
        }

        .rackTile .points {
            position: absolute;
            bottom: 2px;
            right: 4px;
            font-size: 0.5em;
        }

        .history {
            background: rgba(255,255,255,0.95);
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            max-height: 300px;
            overflow-y: auto;
        }

        .history h3 {
            margin-bottom: 15px;
            color: #667eea;
        }

        .historyItem {
            padding: 10px;
            margin-bottom: 8px;
            background: linear-gradient(135deg, #f6f6f6 0%, #e9e9e9 100%);
            border-radius: 8px;
            font-size: 0.9em;
        }

        .historyItem strong {
            color: #667eea;
        }

        #bagInfo {
            background: rgba(255,255,255,0.95);
            padding: 15px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            text-align: center;
        }

        #bagInfo h4 {
            color: #667eea;
            margin-bottom: 5px;
        }

        #bagInfo p {
            font-size: 1.5em;
            font-weight: bold;
            color: #764ba2;
        }

        .message {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            padding: 30px 50px;
            border-radius: 15px;
            box-shadow: 0 10px 50px rgba(0,0,0,0.5);
            z-index: 1000;
            text-align: center;
            animation: slideIn 0.3s ease;
        }

        @keyframes slideIn {
            from {
                transform: translate(-50%, -50%) scale(0.8);
                opacity: 0;
            }
            to {
                transform: translate(-50%, -50%) scale(1);
                opacity: 1;
            }
        }

        .message h2 {
            color: #667eea;
            margin-bottom: 20px;
        }

        .message button {
            width: auto;
            padding: 10px 30px;
            margin: 0 10px;
        }

        .overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.7);
            z-index: 999;
            display: none;
        }

        .overlay.show {
            display: block;
        }

        @media (max-width: 1200px) {
            .gameArea {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Selector de jugador -->
    <div id="playerSelector">
        <button class="playerButton" id="viejoBtn" onclick="selectPlayer('viejo')">
            <div class="emoji">👴</div>
            <div class="name">VIEJO</div>
        </button>
        <button class="playerButton" id="stinkyBtn" onclick="selectPlayer('stinky')">
            <div class="emoji">😼</div>
            <div class="name">STINKY</div>
        </button>
    </div>

    <!-- Contenedor del juego -->
    <div id="gameContainer">
        <div class="header">
            <h1 id="gameTitle">Scrabble</h1>
            <div class="scores">
                <div class="score" id="score1">
                    <h3 id="player1Name">👴 VIEJO</h3>
                    <p id="player1Score">0</p>
                </div>
                <div class="score" id="score2">
                    <h3 id="player2Name">😼 STINKY</h3>
                    <p id="player2Score">0</p>
                </div>
            </div>
        </div>

        <div class="gameArea">
            <div class="boardContainer">
                <div id="board"></div>
            </div>

            <div class="sidebar">
                <div class="controls">
                    <h3 id="controlsTitle">Controles</h3>
                    <button class="btnPlay" onclick="playTurn()" id="btnPlay">▶️ Jugar Turno</button>
                    <button class="btnDispute" onclick="showDisputeConfirm()" id="btnDispute">⚠️ Disputar Palabra</button>
                    <button class="btnUndo" onclick="undoMove()" id="btnUndo">↩️ Deshacer</button>
                    <button class="btnShuffle" onclick="shuffleRack()" id="btnShuffle">🔀 Mezclar</button>
                    <button class="btnExchange" onclick="exchangeTiles()" id="btnExchange">🔄 Cambiar Fichas</button>
                    <button class="btnNewGame" onclick="newGame()" id="btnNewGame">🎮 Nuevo Juego</button>
                </div>

                <div id="bagInfo">
                    <h4 id="bagTitle">Fichas restantes</h4>
                    <p id="bagCount">100</p>
                </div>

                <div class="history">
                    <h3 id="historyTitle">Historial</h3>
                    <div id="historyList"></div>
                </div>
            </div>
        </div>

        <div id="rack">
            <h3 id="rackTitle">Tus Fichas</h3>
            <div id="rackTiles"></div>
        </div>
    </div>

    <div class="overlay" id="overlay"></div>

    <script>
        // Configuración de idiomas
        const translations = {
            spanish: {
                gameTitle: 'Scrabble',
                player1Name: '👴 VIEJO',
                player2Name: '😼 STINKY',
                controlsTitle: 'Controles',
                btnPlay: '▶️ Jugar Turno',
                btnDispute: '⚠️ Disputar Palabra',
                btnUndo: '↩️ Deshacer',
                btnShuffle: '🔀 Mezclar',
                btnExchange: '🔄 Cambiar Fichas',
                btnNewGame: '🎮 Nuevo Juego',
                bagTitle: 'Fichas restantes',
                rackTitle: 'Tus Fichas',
                historyTitle: 'Historial',
                msgNoTiles: 'Coloca fichas en el tablero primero',
                msgInvalidPlacement: 'Las fichas deben estar en línea (fila o columna)',
                msgNotConnected: 'Las fichas deben estar conectadas al centro o palabras existentes',
                msgSuccess: '¡Palabra jugada!',
                msgPoints: 'Puntos',
                msgExchangeSelect: 'Selecciona fichas para cambiar',
                msgExchangeSuccess: 'Fichas cambiadas',
                msgGameOver: '¡Juego Terminado!',
                msgWinner: 'Ganador',
                msgTie: '¡Empate!',
                msgPlayAgain: '¿Jugar de nuevo?',
                btnYes: 'Sí',
                btnNo: 'No',
                btnOk: 'OK',
                btnCancel: 'Cancelar',
                msgDisputeConfirm: '¿Estás seguro de que quieres disputar esta palabra?',
                msgDisputeTitle: 'Disputar Palabra',
                msgDisputeSuccess: 'Palabra disputada y revertida',
                msgNoLastMove: 'No hay jugada para disputar'
            },
            russian: {
                gameTitle: 'Эрудит',
                player1Name: '👴 СТАРИК',
                player2Name: '😼 ВОНЮЧКА',
                controlsTitle: 'Управление',
                btnPlay: '▶️ Играть',
                btnDispute: '⚠️ Оспорить слово',
                btnUndo: '↩️ Отменить',
                btnShuffle: '🔀 Перемешать',
                btnExchange: '🔄 Обменять фишки',
                btnNewGame: '🎮 Новая игра',
                bagTitle: 'Осталось фишек',
                rackTitle: 'Ваши фишки',
                historyTitle: 'История',
                msgNoTiles: 'Сначала поместите фишки на доску',
                msgInvalidPlacement: 'Фишки должны быть в линию (ряд или столбец)',
                msgNotConnected: 'Фишки должны быть соединены с центром или существующими словами',
                msgSuccess: 'Слово сыграно!',
                msgPoints: 'Очки',
                msgExchangeSelect: 'Выберите фишки для обмена',
                msgExchangeSuccess: 'Фишки обменены',
                msgGameOver: 'Игра окончена!',
                msgWinner: 'Победитель',
                msgTie: 'Ничья!',
                msgPlayAgain: 'Сыграть еще раз?',
                btnYes: 'Да',
                btnNo: 'Нет',
                btnOk: 'ОК',
                btnCancel: 'Отмена',
                msgDisputeConfirm: 'Вы уверены, что хотите оспорить это слово?',
                msgDisputeTitle: 'Оспорить слово',
                msgDisputeSuccess: 'Слово оспорено и отменено',
                msgNoLastMove: 'Нет хода для оспаривания'
            }
        };

        let currentLanguage = 'spanish';
        let currentPlayer = 1;
        let selectedPlayer = null;

        // Distribución de letras para español
        const spanishTiles = {
            'A': { count: 12, points: 1 }, 'E': { count: 12, points: 1 }, 'O': { count: 9, points: 1 },
            'I': { count: 6, points: 1 }, 'S': { count: 6, points: 1 }, 'N': { count: 5, points: 1 },
            'L': { count: 4, points: 1 }, 'R': { count: 5, points: 1 }, 'U': { count: 5, points: 1 },
            'T': { count: 4, points: 1 }, 'D': { count: 5, points: 2 }, 'G': { count: 2, points: 2 },
            'C': { count: 4, points: 3 }, 'B': { count: 2, points: 3 }, 'M': { count: 2, points: 3 },
            'P': { count: 2, points: 3 }, 'H': { count: 2, points: 4 }, 'F': { count: 1, points: 4 },
            'V': { count: 1, points: 4 }, 'Y': { count: 1, points: 4 }, 'Q': { count: 1, points: 5 },
            'J': { count: 1, points: 8 }, 'Ñ': { count: 1, points: 8 }, 'X': { count: 1, points: 8 },
            'Z': { count: 1, points: 10 }, ' ': { count: 2, points: 0 }
        };

        // Distribución de letras para ruso
        const russianTiles = {
            'А': { count: 10, points: 1 }, 'Е': { count: 9, points: 1 }, 'И': { count: 8, points: 1 },
            'О': { count: 10, points: 1 }, 'Н': { count: 8, points: 1 }, 'Р': { count: 6, points: 1 },
            'Т': { count: 5, points: 1 }, 'С': { count: 6, points: 1 }, 'Л': { count: 4, points: 2 },
            'В': { count: 5, points: 2 }, 'К': { count: 4, points: 2 }, 'Д': { count: 5, points: 2 },
            'П': { count: 4, points: 2 }, 'М': { count: 3, points: 2 }, 'У': { count: 3, points: 3 },
            'Я': { count: 3, points: 3 }, 'Ы': { count: 2, points: 3 }, 'Б': { count: 2, points: 3 },
            'Г': { count: 2, points: 3 }, 'Ь': { count: 2, points: 3 }, 'Й': { count: 2, points: 4 },
            'Ч': { count: 2, points: 5 }, 'Х': { count: 2, points: 5 }, 'Ж': { count: 1, points: 5 },
            'Ю': { count: 1, points: 8 }, 'Ш': { count: 1, points: 10 }, ' ': { count: 2, points: 0 }
        };

        let tileDistribution = spanishTiles;
        let bag = [];
        let playerRacks = [[], []];
        let playerScores = [0, 0];
        let board = [];
        let selectedTile = null;
        let temporaryPlacements = [];
        let history = [];
        let lastMove = null;

        // Inicializar el tablero
        function initBoard() {
            board = [];
            for (let i = 0; i < 15; i++) {
                board[i] = [];
                for (let j = 0; j < 15; j++) {
                    board[i][j] = { letter: null, permanent: false };
                }
            }
        }

        // Crear la bolsa de fichas
        function createBag() {
            bag = [];
            for (let letter in tileDistribution) {
                for (let i = 0; i < tileDistribution[letter].count; i++) {
                    bag.push(letter);
                }
            }
            shuffleBag();
        }

        // Mezclar la bolsa
        function shuffleBag() {
            for (let i = bag.length - 1; i > 0; i--) {
                const j = Math.floor(Math.random() * (i + 1));
                [bag[i], bag[j]] = [bag[j], bag[i]];
            }
        }

        // Sacar fichas de la bolsa
        function drawTiles(count) {
            const tiles = [];
            for (let i = 0; i < count && bag.length > 0; i++) {
                tiles.push(bag.pop());
            }
            return tiles;
        }

        // Rellenar el rack del jugador
        function fillRack(playerIndex) {
            while (playerRacks[playerIndex].length < 7 && bag.length > 0) {
                playerRacks[playerIndex].push(bag.pop());
            }
        }

        // Renderizar el tablero
        function renderBoard() {
            const boardEl = document.getElementById('board');
            boardEl.innerHTML = '';

            for (let i = 0; i < 15; i++) {
                for (let j = 0; j < 15; j++) {
                    const cell = document.createElement('div');
                    cell.className = 'cell';
                    cell.dataset.row = i;
                    cell.dataset.col = j;

                    // Asignar tipo de casilla
                    const cellType = getCellType(i, j);
                    if (cellType) {
                        cell.classList.add(cellType);
                        cell.textContent = getCellLabel(cellType);
                    }

                    // Agregar letra si existe
                    if (board[i][j].letter) {
                        const tile = document.createElement('div');
                        tile.className = 'tile';
                        const letter = document.createElement('span');
                        letter.className = 'letter';
                        letter.textContent = board[i][j].letter === ' ' ? '★' : board[i][j].letter;
                        const points = document.createElement('span');
                        points.className = 'points';
                        points.textContent = tileDistribution[board[i][j].letter].points;
                        tile.appendChild(letter);
                        tile.appendChild(points);
                        cell.appendChild(tile);

                        if (board[i][j].permanent) {
                            cell.classList.add('occupied');
                        } else {
                            cell.classList.add('temporary');
                        }
                    }

                    cell.addEventListener('click', () => handleCellClick(i, j));
                    boardEl.appendChild(cell);
                }
            }
        }

        // Obtener tipo de casilla especial
        function getCellType(row, col) {
            // Centro
            if (row === 7 && col === 7) return 'center';

            // Triple palabra
            const tripleWord = [[0,0],[0,7],[0,14],[7,0],[7,14],[14,0],[14,7],[14,14]];
            if (tripleWord.some(([r,c]) => r === row && c === col)) return 'tripleWord';

            // Doble palabra
            const doubleWord = [[1,1],[2,2],[3,3],[4,4],[1,13],[2,12],[3,11],[4,10],
                               [13,1],[12,2],[11,3],[10,4],[13,13],[12,12],[11,11],[10,10],
                               [7,7]];
            if (doubleWord.some(([r,c]) => r === row && c === col)) return 'doubleWord';

            // Triple letra
            const tripleLetter = [[1,5],[1,9],[5,1],[5,5],[5,9],[5,13],[9,1],[9,5],[9,9],[9,13],[13,5],[13,9]];
            if (tripleLetter.some(([r,c]) => r === row && c === col)) return 'tripleLetter';

            // Doble letra
            const doubleLetter = [[0,3],[0,11],[2,6],[2,8],[3,0],[3,7],[3,14],[6,2],[6,6],[6,8],[6,12],
                                 [7,3],[7,11],[8,2],[8,6],[8,8],[8,12],[11,0],[11,7],[11,14],[12,6],[12,8],[14,3],[14,11]];
            if (doubleLetter.some(([r,c]) => r === row && c === col)) return 'doubleLetter';

            return null;
        }

        // Obtener etiqueta de casilla
        function getCellLabel(type) {
            switch(type) {
                case 'center': return '★';
                case 'tripleWord': return '3W';
                case 'doubleWord': return '2W';
                case 'tripleLetter': return '3L';
                case 'doubleLetter': return '2L';
                default: return '';
            }
        }

        // Manejar clic en casilla
        function handleCellClick(row, col) {
            if (selectedTile !== null) {
                // Colocar ficha
                if (!board[row][col].letter) {
                    const tile = playerRacks[currentPlayer - 1][selectedTile];
                    board[row][col].letter = tile;
                    board[row][col].permanent = false;
                    temporaryPlacements.push({ row, col, letter: tile, rackIndex: selectedTile });
                    playerRacks[currentPlayer - 1].splice(selectedTile, 1);
                    selectedTile = null;
                    renderBoard();
                    renderRack();
                }
            } else {
                // Quitar ficha temporal
                if (board[row][col].letter && !board[row][col].permanent) {
                    const placement = temporaryPlacements.find(p => p.row === row && p.col === col);
                    if (placement) {
                        playerRacks[currentPlayer - 1].push(placement.letter);
                        board[row][col].letter = null;
                        temporaryPlacements = temporaryPlacements.filter(p => p.row !== row || p.col !== col);
                        renderBoard();
                        renderRack();
                    }
                }
            }
        }

        // Renderizar rack
        function renderRack() {
            const rackEl = document.getElementById('rackTiles');
            rackEl.innerHTML = '';

            playerRacks[currentPlayer - 1].forEach((letter, index) => {
                const tile = document.createElement('div');
                tile.className = 'rackTile';
                if (selectedTile === index) {
                    tile.classList.add('selected');
                }

                const letterSpan = document.createElement('span');
                letterSpan.textContent = letter === ' ' ? '★' : letter;
                const pointsSpan = document.createElement('span');
                pointsSpan.className = 'points';
                pointsSpan.textContent = tileDistribution[letter].points;

                tile.appendChild(letterSpan);
                tile.appendChild(pointsSpan);
                tile.addEventListener('click', () => selectRackTile(index));
                rackEl.appendChild(tile);
            });
        }

        // Seleccionar ficha del rack
        function selectRackTile(index) {
            if (selectedTile === index) {
                selectedTile = null;
            } else {
                selectedTile = index;
            }
            renderRack();
        }

        // Jugar turno
        function playTurn() {
            if (temporaryPlacements.length === 0) {
                showMessage(translations[currentLanguage].msgNoTiles);
                return;
            }

            // Validar colocación
            if (!validatePlacement()) {
                return;
            }

            // Calcular puntos
            const points = calculatePoints();
            playerScores[currentPlayer - 1] += points;

            // Hacer permanentes las fichas
            temporaryPlacements.forEach(p => {
                board[p.row][p.col].permanent = true;
            });

            // Guardar movimiento para posible disputa
            lastMove = {
                player: currentPlayer,
                placements: [...temporaryPlacements],
                points: points
            };

            // Agregar al historial
            const word = getPlayedWord();
            addToHistory(currentPlayer, word, points);

            // Limpiar colocaciones temporales
            temporaryPlacements = [];

            // Rellenar rack
            fillRack(currentPlayer - 1);

            // Cambiar turno
            currentPlayer = currentPlayer === 1 ? 2 : 1;

            // Mostrar botón de disputa
            document.getElementById('btnDispute').style.display = 'block';

            updateUI();
            showMessage(`${translations[currentLanguage].msgSuccess} +${points} ${translations[currentLanguage].msgPoints}`);

            // Verificar fin del juego
            checkGameOver();
        }

        // Validar colocación de fichas
        function validatePlacement() {
            if (temporaryPlacements.length === 0) return false;

            // Verificar que estén en línea
            const rows = [...new Set(temporaryPlacements.map(p => p.row))];
            const cols = [...new Set(temporaryPlacements.map(p => p.col))];

            if (rows.length > 1 && cols.length > 1) {
                showMessage(translations[currentLanguage].msgInvalidPlacement);
                return false;
            }

            // Verificar conexión (primer movimiento debe estar en el centro)
            const isFirstMove = history.length === 0;
            if (isFirstMove) {
                const hasCenter = temporaryPlacements.some(p => p.row === 7 && p.col === 7);
                if (!hasCenter) {
                    showMessage(translations[currentLanguage].msgNotConnected);
                    return false;
                }
            } else {
                // Debe conectar con alguna ficha existente
                let connected = false;
                temporaryPlacements.forEach(p => {
                    const adjacent = [
                        [p.row - 1, p.col], [p.row + 1, p.col],
                        [p.row, p.col - 1], [p.row, p.col + 1]
                    ];
                    adjacent.forEach(([r, c]) => {
                        if (r >= 0 && r < 15 && c >= 0 && c < 15) {
                            if (board[r][c].letter && board[r][c].permanent) {
                                connected = true;
                            }
                        }
                    });
                });
                if (!connected) {
                    showMessage(translations[currentLanguage].msgNotConnected);
                    return false;
                }
            }

            return true;
        }

        // Calcular puntos
        function calculatePoints() {
            let totalPoints = 0;
            let wordMultiplier = 1;

            temporaryPlacements.forEach(p => {
                let letterPoints = tileDistribution[p.letter].points;
                const cellType = getCellType(p.row, p.col);

                if (cellType === 'doubleLetter') {
                    letterPoints *= 2;
                } else if (cellType === 'tripleLetter') {
                    letterPoints *= 3;
                } else if (cellType === 'doubleWord' || cellType === 'center') {
                    wordMultiplier *= 2;
                } else if (cellType === 'tripleWord') {
                    wordMultiplier *= 3;
                }

                totalPoints += letterPoints;
            });

            totalPoints *= wordMultiplier;

            // Bonus por usar todas las fichas
            if (temporaryPlacements.length === 7) {
                totalPoints += 50;
            }

            return totalPoints;
        }

        // Obtener palabra jugada
        function getPlayedWord() {
            if (temporaryPlacements.length === 0) return '';

            const rows = temporaryPlacements.map(p => p.row);
            const cols = temporaryPlacements.map(p => p.col);

            let word = '';
            if (rows.every(r => r === rows[0])) {
                // Horizontal
                const row = rows[0];
                const minCol = Math.min(...cols);
                const maxCol = Math.max(...cols);
                for (let c = minCol; c <= maxCol; c++) {
                    if (board[row][c].letter) {
                        word += board[row][c].letter === ' ' ? '★' : board[row][c].letter;
                    }
                }
            } else {
                // Vertical
                const col = cols[0];
                const minRow = Math.min(...rows);
                const maxRow = Math.max(...rows);
                for (let r = minRow; r <= maxRow; r++) {
                    if (board[r][col].letter) {
                        word += board[r][col].letter === ' ' ? '★' : board[r][col].letter;
                    }
                }
            }

            return word;
        }

        // Agregar al historial
        function addToHistory(player, word, points) {
            const playerName = player === 1 ? translations[currentLanguage].player1Name : translations[currentLanguage].player2Name;
            history.unshift({ player: playerName, word, points });
            if (history.length > 10) {
                history.pop();
            }
            updateHistory();
        }

        // Actualizar historial en UI
        function updateHistory() {
            const historyEl = document.getElementById('historyList');
            historyEl.innerHTML = '';

            history.forEach(item => {
                const div = document.createElement('div');
                div.className = 'historyItem';
                div.innerHTML = `<strong>${item.player}:</strong> ${item.word} (${item.points})`;
                historyEl.appendChild(div);
            });
        }

        // Deshacer movimiento
        function undoMove() {
            if (temporaryPlacements.length === 0) return;

            const placement = temporaryPlacements.pop();
            playerRacks[currentPlayer - 1].push(placement.letter);
            board[placement.row][placement.col].letter = null;
            renderBoard();
            renderRack();
        }

        // Mezclar rack
        function shuffleRack() {
            const rack = playerRacks[currentPlayer - 1];
            for (let i = rack.length - 1; i > 0; i--) {
                const j = Math.floor(Math.random() * (i + 1));
                [rack[i], rack[j]] = [rack[j], rack[i]];
            }
            renderRack();
        }

        // Cambiar fichas
        function exchangeTiles() {
            if (bag.length < 7) {
                showMessage(translations[currentLanguage].msgExchangeSelect);
                return;
            }

            // Aquí simplificamos: cambia todas las fichas seleccionadas
            // En una implementación más compleja, permitirías seleccionar múltiples
            if (selectedTile !== null) {
                const tile = playerRacks[currentPlayer - 1][selectedTile];
                bag.push(tile);
                playerRacks[currentPlayer - 1].splice(selectedTile, 1);
                fillRack(currentPlayer - 1);
                shuffleBag();
                selectedTile = null;
                
                currentPlayer = currentPlayer === 1 ? 2 : 1;
                updateUI();
                showMessage(translations[currentLanguage].msgExchangeSuccess);
            }
        }

        // Mostrar confirmación de disputa
        function showDisputeConfirm() {
            if (!lastMove) {
                showMessage(translations[currentLanguage].msgNoLastMove);
                return;
            }

            const overlay = document.getElementById('overlay');
            overlay.classList.add('show');

            const msg = document.createElement('div');
            msg.className = 'message';
            msg.innerHTML = `
                <h2>${translations[currentLanguage].msgDisputeTitle}</h2>
                <p>${translations[currentLanguage].msgDisputeConfirm}</p>
                <button class="btnPlay" onclick="disputeWord()">${translations[currentLanguage].btnYes}</button>
                <button class="btnUndo" onclick="closeMessage()">${translations[currentLanguage].btnCancel}</button>
            `;
            document.body.appendChild(msg);
        }

        // Disputar palabra
        function disputeWord() {
            if (!lastMove) return;

            // Revertir puntos
            playerScores[lastMove.player - 1] -= lastMove.points;

            // Devolver fichas al rack del jugador anterior
            const prevPlayer = lastMove.player - 1;
            lastMove.placements.forEach(p => {
                playerRacks[prevPlayer].push(p.letter);
                board[p.row][p.col].letter = null;
                board[p.row][p.col].permanent = false;
            });

            // Eliminar del historial
            if (history.length > 0) {
                history.shift();
            }

            // Cambiar turno de vuelta
            currentPlayer = lastMove.player;

            // Limpiar último movimiento
            lastMove = null;

            // Ocultar botón de disputa
            document.getElementById('btnDispute').style.display = 'none';

            closeMessage();
            updateUI();
            showMessage(translations[currentLanguage].msgDisputeSuccess);
        }

        // Actualizar UI
        function updateUI() {
            document.getElementById('player1Score').textContent = playerScores[0];
            document.getElementById('player2Score').textContent = playerScores[1];
            document.getElementById('bagCount').textContent = bag.length;

            const score1 = document.getElementById('score1');
            const score2 = document.getElementById('score2');
            
            if (currentPlayer === 1) {
                score1.classList.add('currentPlayer');
                score2.classList.remove('currentPlayer');
            } else {
                score2.classList.add('currentPlayer');
                score1.classList.remove('currentPlayer');
            }

            renderBoard();
            renderRack();
        }

        // Verificar fin del juego
        function checkGameOver() {
            // El juego termina cuando la bolsa está vacía y un jugador no tiene fichas
            if (bag.length === 0) {
                if (playerRacks[0].length === 0 || playerRacks[1].length === 0) {
                    endGame();
                }
            }
        }

        // Terminar juego
        function endGame() {
            const overlay = document.getElementById('overlay');
            overlay.classList.add('show');

            let winner = '';
            if (playerScores[0] > playerScores[1]) {
                winner = translations[currentLanguage].player1Name;
            } else if (playerScores[1] > playerScores[0]) {
                winner = translations[currentLanguage].player2Name;
            } else {
                winner = translations[currentLanguage].msgTie;
            }

            const msg = document.createElement('div');
            msg.className = 'message';
            msg.innerHTML = `
                <h2>${translations[currentLanguage].msgGameOver}</h2>
                <p><strong>${translations[currentLanguage].msgWinner}:</strong> ${winner}</p>
                <p>${translations[currentLanguage].player1Name}: ${playerScores[0]}</p>
                <p>${translations[currentLanguage].player2Name}: ${playerScores[1]}</p>
                <p>${translations[currentLanguage].msgPlayAgain}</p>
                <button class="btnPlay" onclick="newGame(); closeMessage();">${translations[currentLanguage].btnYes}</button>
                <button class="btnUndo" onclick="closeMessage()">${translations[currentLanguage].btnNo}</button>
            `;
            document.body.appendChild(msg);
        }

        // Nuevo juego
        function newGame() {
            initBoard();
            createBag();
            playerRacks = [[], []];
            playerScores = [0, 0];
            currentPlayer = 1;
            selectedTile = null;
            temporaryPlacements = [];
            history = [];
            lastMove = null;

            fillRack(0);
            fillRack(1);

            document.getElementById('btnDispute').style.display = 'none';
            updateUI();
            updateHistory();
        }

        // Mostrar mensaje
        function showMessage(text) {
            const overlay = document.getElementById('overlay');
            overlay.classList.add('show');

            const msg = document.createElement('div');
            msg.className = 'message';
            msg.innerHTML = `
                <h2>${text}</h2>
                <button class="btnPlay" onclick="closeMessage()">${translations[currentLanguage].btnOk}</button>
            `;
            document.body.appendChild(msg);

            setTimeout(() => {
                closeMessage();
            }, 2000);
        }

        // Cerrar mensaje
        function closeMessage() {
            const overlay = document.getElementById('overlay');
            overlay.classList.remove('show');

            const messages = document.querySelectorAll('.message');
            messages.forEach(msg => msg.remove());
        }

        // Seleccionar jugador
        function selectPlayer(player) {
            selectedPlayer = player;
            
            if (player === 'viejo') {
                currentLanguage = 'spanish';
                tileDistribution = spanishTiles;
            } else {
                currentLanguage = 'russian';
                tileDistribution = russianTiles;
            }

            // Actualizar textos
            updateLanguage();

            // Ocultar selector con animación
            const selector = document.getElementById('playerSelector');
            selector.classList.add('fadeOut');
            
            setTimeout(() => {
                selector.style.display = 'none';
                document.getElementById('gameContainer').style.display = 'block';
                
                // Iniciar juego
                newGame();
            }, 300);
        }

        // Actualizar idioma
        function updateLanguage() {
            const t = translations[currentLanguage];
            
            document.getElementById('gameTitle').textContent = t.gameTitle;
            document.getElementById('player1Name').textContent = t.player1Name;
            document.getElementById('player2Name').textContent = t.player2Name;
            document.getElementById('controlsTitle').textContent = t.controlsTitle;
            document.getElementById('btnPlay').textContent = t.btnPlay;
            document.getElementById('btnDispute').textContent = t.btnDispute;
            document.getElementById('btnUndo').textContent = t.btnUndo;
            document.getElementById('btnShuffle').textContent = t.btnShuffle;
            document.getElementById('btnExchange').textContent = t.btnExchange;
            document.getElementById('btnNewGame').textContent = t.btnNewGame;
            document.getElementById('bagTitle').textContent = t.bagTitle;
            document.getElementById('rackTitle').textContent = t.rackTitle;
            document.getElementById('historyTitle').textContent = t.historyTitle;
        }

        // Inicializar
        window.addEventListener('load', () => {
            // El juego no se inicia hasta que se seleccione un jugador
        });
    </script>
</body>
</html>
