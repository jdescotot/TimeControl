<?php
/**
 * SCRABBLE - Juego interactivo de palabras
 * Archivo: scrbl.php
 * Descripción: Juego de Scrabble completo e independiente del proyecto TimeControl
 */
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🎮 SCRABBLE - Juego de Palabras</title>
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
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            padding: 30px;
            max-width: 1100px;
            width: 100%;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
        }

        .header h1 {
            font-size: 48px;
            color: #667eea;
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.1);
        }

        .header p {
            color: #666;
            font-size: 16px;
        }

        .game-content {
            display: grid;
            grid-template-columns: 1.2fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }

        .board-section {
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .board-title {
            font-size: 20px;
            font-weight: bold;
            color: #333;
            margin-bottom: 15px;
        }

        .board {
            display: grid;
            grid-template-columns: repeat(15, 1fr);
            gap: 2px;
            background: #333;
            padding: 5px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .cell {
            width: 40px;
            height: 40px;
            background: #fff;
            border: 1px solid #999;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.2s;
            position: relative;
            user-select: none;
        }

        .cell:hover:not(.occupied) {
            background: #f0f0f0;
            transform: scale(1.05);
        }

        .cell.double-letter {
            background: #b3d9ff;
        }

        .cell.triple-letter {
            background: #0066cc;
            color: white;
        }

        .cell.double-word {
            background: #ffcccc;
        }

        .cell.triple-word {
            background: #ff3333;
            color: white;
        }

        .cell.center {
            background: #ffeb99;
            font-size: 20px;
        }

        .cell.occupied {
            background: #f9f3e6;
            font-weight: bold;
            color: #000;
            font-size: 18px;
            border: 2px solid #d4af37;
        }

        .cell.selected-pos {
            background: #667eea;
            color: white;
            border: 2px solid #764ba2;
            box-shadow: 0 0 10px rgba(102, 126, 234, 0.5);
        }

        .tile-points {
            font-size: 8px;
            position: absolute;
            bottom: 2px;
            right: 3px;
            color: #666;
        }

        .controls {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .control-group {
            background: #f5f5f5;
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #667eea;
        }

        .control-label {
            font-size: 13px;
            font-weight: bold;
            color: #999;
            text-transform: uppercase;
            margin-bottom: 12px;
            letter-spacing: 1px;
        }

        .rack {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 8px;
            margin-top: 10px;
        }

        .tile {
            aspect-ratio: 1;
            background: linear-gradient(135deg, #ffd700 0%, #ffed4e 100%);
            border: 3px solid #b8860b;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 20px;
            cursor: pointer;
            transition: all 0.2s;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.2);
            position: relative;
            user-select: none;
        }

        .tile:hover:not(.empty) {
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.3);
        }

        .tile.selected {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-color: #4a5568;
            transform: translateY(-10px);
        }

        .tile.empty {
            background: #e0e0e0;
            border-color: #999;
            color: #999;
            cursor: not-allowed;
        }

        .score-board {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 15px;
        }

        .score-item {
            display: flex;
            justify-content: space-between;
            margin: 12px 0;
            font-size: 16px;
        }

        .score-item.total {
            border-top: 2px solid rgba(255, 255, 255, 0.3);
            padding-top: 12px;
            margin-top: 15px;
            font-weight: bold;
            font-size: 20px;
        }

        .info-panel {
            background: #e8f5e9;
            border-left: 4px solid #4CAF50;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 15px;
            font-size: 14px;
        }

        .info-panel.warning {
            background: #fff3cd;
            border-left-color: #ffc107;
        }

        .info-panel.error {
            background: #f8d7da;
            border-left-color: #f44336;
        }

        .buttons {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }

        .btn {
            padding: 14px 20px;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-primary {
            background: #667eea;
            color: white;
        }

        .btn-primary:hover {
            background: #5568d3;
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(102, 126, 234, 0.4);
        }

        .btn-secondary {
            background: #f0f0f0;
            color: #333;
            border: 2px solid #ddd;
        }

        .btn-secondary:hover {
            background: #e0e0e0;
        }

        .btn-danger {
            background: #f44336;
            color: white;
        }

        .btn-danger:hover {
            background: #da190b;
        }

        .btn-full {
            grid-column: 1 / -1;
        }

        .word-list {
            background: white;
            padding: 15px;
            border-radius: 8px;
            max-height: 250px;
            overflow-y: auto;
            margin-top: 10px;
        }

        .word-item {
            display: flex;
            justify-content: space-between;
            padding: 10px;
            border-bottom: 1px solid #e0e0e0;
            font-size: 14px;
        }

        .word-item:last-child {
            border-bottom: none;
        }

        .word-name {
            font-weight: bold;
            color: #333;
        }

        .word-points {
            color: #667eea;
            font-weight: bold;
        }

        .legend {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 10px;
            margin-top: 20px;
            padding: 20px;
            background: #f5f5f5;
            border-radius: 8px;
        }

        .legend-item {
            text-align: center;
            font-size: 11px;
        }

        .legend-box {
            width: 30px;
            height: 30px;
            margin: 0 auto 5px;
            border-radius: 4px;
            border: 1px solid #999;
        }

        @media (max-width: 1000px) {
            .game-content {
                grid-template-columns: 1fr;
            }
            .board {
                grid-template-columns: repeat(15, 30px);
            }
            .cell {
                width: 30px;
                height: 30px;
                font-size: 12px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎮 SCRABBLE</h1>
            <p>¡Forma palabras y gana puntos!</p>
        </div>

        <div class="game-content">
            <!-- TABLERO -->
            <div class="board-section">
                <div class="board-title">📋 Tablero (15x15)</div>
                <div class="board" id="board"></div>
            </div>

            <!-- CONTROLES -->
            <div class="controls">
                <!-- PUNTUACIÓN -->
                <div class="score-board">
                    <div class="score-item">
                        <span>Turno actual:</span>
                        <span id="turnNumber">1</span>
                    </div>
                    <div class="score-item">
                        <span>Última jugada:</span>
                        <span id="lastScore">0</span>
                    </div>
                    <div class="score-item total">
                        <span>PUNTOS TOTALES:</span>
                        <span id="totalPoints">0</span>
                    </div>
                </div>

                <!-- INFORMACIÓN -->
                <div id="infoPanel" class="info-panel">
                    <strong>ℹ️ Instrucciones</strong><br>
                    <span id="infoText">1. Selecciona fichas de tu rack<br>2. Haz clic en el tablero para colocarlas<br>3. Presiona 'Jugar Turno' cuando termines</span>
                </div>

                <!-- RACK DE FICHAS -->
                <div class="control-group">
                    <div class="control-label">🎯 Tus Fichas</div>
                    <div class="rack" id="rack"></div>
                </div>

                <!-- PALABRAS JUGADAS -->
                <div class="control-group">
                    <div class="control-label">📝 Historial (últimas 10 palabras)</div>
                    <div class="word-list" id="wordList">
                        <div style="color: #999; text-align: center; padding: 20px;">
                            Aún no has jugado palabras
                        </div>
                    </div>
                </div>

                <!-- BOTONES -->
                <div class="buttons">
                    <button class="btn btn-primary" onclick="playTurn()">✅ Jugar Turno</button>
                    <button class="btn btn-secondary" onclick="undoMove()">↩️ Deshacer</button>
                    <button class="btn btn-primary" onclick="shuffleRack()">🔀 Mezclar</button>
                    <button class="btn btn-secondary" onclick="exchangeTiles()">🔄 Cambiar</button>
                    <button class="btn btn-danger btn-full" onclick="newGame()">🎮 Nuevo Juego</button>
                </div>
            </div>
        </div>

        <!-- LEYENDA -->
        <div class="legend">
            <div class="legend-item">
                <div class="legend-box" style="background: white;"></div>
                <span>Normal</span>
            </div>
            <div class="legend-item">
                <div class="legend-box" style="background: #b3d9ff;"></div>
                <span>x2 Letra</span>
            </div>
            <div class="legend-item">
                <div class="legend-box" style="background: #0066cc; color: white;">×3</div>
                <span>x3 Letra</span>
            </div>
            <div class="legend-item">
                <div class="legend-box" style="background: #ffcccc;"></div>
                <span>x2 Palabra</span>
            </div>
            <div class="legend-item">
                <div class="legend-box" style="background: #ff3333; color: white;">×3</div>
                <span>x3 Palabra</span>
            </div>
        </div>
    </div>

    <script>
        // ============ CONFIGURACIÓN DEL JUEGO ============
        const BOARD_SIZE = 15;
        const RACK_SIZE = 7;
        
        // Valores de las letras en puntos
        const LETTER_VALUES = {
            'A': 1, 'E': 1, 'I': 1, 'O': 1, 'U': 1,
            'L': 1, 'N': 1, 'R': 1, 'S': 1, 'T': 1,
            'D': 2, 'G': 2, 'C': 3, 'B': 3, 'M': 3,
            'P': 3, 'H': 4, 'Y': 4, 'V': 4, 'F': 4,
            'K': 5, 'J': 8, 'X': 8, 'Q': 10, 'Z': 10
        };

        // Distribución de fichas en la bolsa (total: 100 fichas)
        const TILE_DISTRIBUTION = {
            'A': 12, 'E': 12, 'O': 9, 'I': 6, 'S': 6,
            'N': 5, 'L': 4, 'R': 5, 'U': 5, 'T': 4,
            'D': 5, 'G': 2, 'C': 4, 'B': 2, 'M': 2,
            'P': 2, 'H': 2, 'F': 1, 'Q': 1, 'V': 1,
            'Y': 1, 'Z': 1, 'X': 1, 'J': 1, 'K': 1
        };

        // Palabras válidas en español (diccionario ampliado)
        const VALID_WORDS = new Set([
            'HOLA', 'CASA', 'GATO', 'PERRO', 'AGUA', 'FUEGO', 'TIERRA', 'AIRE',
            'AMOR', 'VIDA', 'MUERTE', 'DINERO', 'TIEMPO', 'NOCHE', 'DIA', 'SOL',
            'LUNA', 'ESTRELLA', 'CIELO', 'MONTANA', 'RIO', 'MAR', 'PLAYA', 'ARENA',
            'ARBOL', 'FLOR', 'FRUTA', 'VERDURA', 'PAN', 'QUESO', 'VINO', 'CERVEZA',
            'LIBRO', 'PLUMA', 'PAPEL', 'TINTA', 'MESA', 'SILLA', 'PUERTA', 'VENTANA',
            'CALLE', 'PLAZA', 'PARQUE', 'ESCUELA', 'IGLESIA', 'HOSPITAL', 'TIENDA', 'BANCO',
            'AUTO', 'MOTO', 'BICICLETA', 'AVION', 'TREN', 'BARCO', 'ZAPATO', 'ROPA',
            'CABEZA', 'MANO', 'PIE', 'OJO', 'OREJA', 'NARIZ', 'BOCA', 'DIENTE', 'PELO',
            'VASO', 'PLATO', 'CUCHARA', 'TENEDOR', 'CUCHILLO', 'OLLA', 'SARTEN', 'FUENTE',
            'ROJO', 'AZUL', 'VERDE', 'AMARILLO', 'NEGRO', 'BLANCO', 'GRIS', 'ROSA', 'NARANJA',
            'UNO', 'DOS', 'TRES', 'CUATRO', 'CINCO', 'SEIS', 'SIETE', 'OCHO', 'NUEVE', 'DIEZ',
            'NORTE', 'SUR', 'ESTE', 'OESTE', 'CENTRO', 'LADO', 'ARRIBA', 'ABAJO',
            'PADRE', 'MADRE', 'HIJO', 'HIJA', 'HERMANO', 'HERMANA', 'TIO', 'TIA', 'PRIMO',
            'GENTE', 'PERSONA', 'HOMBRE', 'MUJER', 'NIÑO', 'NIÑA', 'BEBE', 'JOVEN', 'VIEJO',
            'COMER', 'BEBER', 'DORMIR', 'VIVIR', 'MORIR', 'AMAR', 'ODIAR', 'TENER', 'HACER',
            'GRANDE', 'PEQUEÑO', 'ALTO', 'BAJO', 'LARGO', 'CORTO', 'ANCHO', 'FINO',
            'BUENO', 'MALO', 'NUEVO', 'VIEJO', 'JOVEN', 'RICO', 'POBRE', 'FELIZ', 'TRISTE'
        ]);

        // Configuración del tablero 15x15 con casillas especiales
        // 0 = normal, 1 = x2 letra, 2 = x3 letra, 3 = x2 palabra, 4 = x3 palabra, 5 = centro
        const BOARD_CONFIG = [
            [4,0,0,1,0,0,0,4,0,0,0,1,0,0,4],
            [0,3,0,0,0,2,0,0,0,2,0,0,0,3,0],
            [0,0,3,0,0,0,1,0,1,0,0,0,3,0,0],
            [1,0,0,3,0,0,0,1,0,0,0,3,0,0,1],
            [0,0,0,0,3,0,0,0,0,0,3,0,0,0,0],
            [0,2,0,0,0,2,0,0,0,2,0,0,0,2,0],
            [0,0,1,0,0,0,1,0,1,0,0,0,1,0,0],
            [4,0,0,1,0,0,0,5,0,0,0,1,0,0,4],
            [0,0,1,0,0,0,1,0,1,0,0,0,1,0,0],
            [0,2,0,0,0,2,0,0,0,2,0,0,0,2,0],
            [0,0,0,0,3,0,0,0,0,0,3,0,0,0,0],
            [1,0,0,3,0,0,0,1,0,0,0,3,0,0,1],
            [0,0,3,0,0,0,1,0,1,0,0,0,3,0,0],
            [0,3,0,0,0,2,0,0,0,2,0,0,0,3,0],
            [4,0,0,1,0,0,0,4,0,0,0,1,0,0,4]
        ];

        // Estado del juego
        let gameBoard = [];
        let playerRack = [];
        let tileBag = [];
        let selectedTileIndex = null;
        let playedWords = [];
        let totalPoints = 0;
        let lastScore = 0;
        let turnNumber = 1;
        let currentMove = [];

        // ============ INICIALIZACIÓN ============
        function initGame() {
            gameBoard = Array(BOARD_SIZE).fill(null).map(() => Array(BOARD_SIZE).fill(null));
            tileBag = createTileBag();
            playerRack = drawTiles(RACK_SIZE);
            selectedTileIndex = null;
            playedWords = [];
            totalPoints = 0;
            lastScore = 0;
            turnNumber = 1;
            currentMove = [];
            render();
            showInfo('success', '🎮 ¡Nuevo juego iniciado! Coloca tu primera palabra en el centro (★)');
        }

        function createTileBag() {
            let bag = [];
            for (let letter in TILE_DISTRIBUTION) {
                for (let i = 0; i < TILE_DISTRIBUTION[letter]; i++) {
                    bag.push(letter);
                }
            }
            return shuffle(bag);
        }

        function shuffle(array) {
            for (let i = array.length - 1; i > 0; i--) {
                const j = Math.floor(Math.random() * (i + 1));
                [array[i], array[j]] = [array[j], array[i]];
            }
            return array;
        }

        function drawTiles(count) {
            let tiles = [];
            for (let i = 0; i < count && tileBag.length > 0; i++) {
                tiles.push(tileBag.pop());
            }
            return tiles;
        }

        // ============ RENDERIZADO ============
        function render() {
            renderBoard();
            renderRack();
            updateScores();
            renderWordList();
        }

        function renderBoard() {
            const boardEl = document.getElementById('board');
            boardEl.innerHTML = '';

            for (let row = 0; row < BOARD_SIZE; row++) {
                for (let col = 0; col < BOARD_SIZE; col++) {
                    const cell = document.createElement('div');
                    cell.className = 'cell';
                    
                    const cellType = BOARD_CONFIG[row][col];
                    if (cellType === 1) cell.classList.add('double-letter');
                    else if (cellType === 2) cell.classList.add('triple-letter');
                    else if (cellType === 3) cell.classList.add('double-word');
                    else if (cellType === 4) cell.classList.add('triple-word');
                    else if (cellType === 5) {
                        cell.classList.add('center');
                        if (!gameBoard[row][col]) {
                            cell.textContent = '★';
                        }
                    }

                    if (gameBoard[row][col]) {
                        const letter = gameBoard[row][col];
                        cell.innerHTML = letter + '<span class="tile-points">' + LETTER_VALUES[letter] + '</span>';
                        cell.classList.add('occupied');
                    }

                    cell.addEventListener('click', () => placeTile(row, col));
                    boardEl.appendChild(cell);
                }
            }
        }

        function renderRack() {
            const rackEl = document.getElementById('rack');
            rackEl.innerHTML = '';

            for (let i = 0; i < RACK_SIZE; i++) {
                const tile = document.createElement('div');
                tile.className = 'tile';

                if (i < playerRack.length && playerRack[i]) {
                    const letter = playerRack[i];
                    tile.innerHTML = letter + '<span class="tile-points">' + LETTER_VALUES[letter] + '</span>';
                    
                    if (selectedTileIndex === i) {
                        tile.classList.add('selected');
                    }
                    
                    tile.addEventListener('click', () => selectTile(i));
                } else {
                    tile.classList.add('empty');
                    tile.textContent = '—';
                }

                rackEl.appendChild(tile);
            }
        }

        function updateScores() {
            document.getElementById('turnNumber').textContent = turnNumber;
            document.getElementById('lastScore').textContent = lastScore;
            document.getElementById('totalPoints').textContent = totalPoints;
        }

        function renderWordList() {
            const listEl = document.getElementById('wordList');
            
            if (playedWords.length === 0) {
                listEl.innerHTML = '<div style="color: #999; text-align: center; padding: 20px;">Aún no has jugado palabras</div>';
                return;
            }

            const recentWords = playedWords.slice(-10).reverse();
            listEl.innerHTML = recentWords.map(w => 
                '<div class="word-item"><span class="word-name">' + w.word + '</span><span class="word-points">+' + w.points + ' pts</span></div>'
            ).join('');
        }

        // ============ MECÁNICA DEL JUEGO ============
        function selectTile(index) {
            if (index >= playerRack.length || !playerRack[index]) return;
            
            selectedTileIndex = selectedTileIndex === index ? null : index;
            renderRack();
            
            if (selectedTileIndex !== null) {
                showInfo('success', '✅ Ficha seleccionada: ' + playerRack[selectedTileIndex]);
            } else {
                showInfo('success', 'ℹ️ Ficha deseleccionada');
            }
        }

        function placeTile(row, col) {
            if (selectedTileIndex === null) {
                showInfo('warning', '⚠️ Selecciona primero una ficha de tu rack');
                return;
            }

            if (gameBoard[row][col]) {
                showInfo('error', '❌ Esta casilla ya está ocupada');
                return;
            }

            const letter = playerRack[selectedTileIndex];
            gameBoard[row][col] = letter;
            currentMove.push({ row, col, letter, rackIndex: selectedTileIndex });
            playerRack.splice(selectedTileIndex, 1);
            selectedTileIndex = null;
            
            render();
            showInfo('success', '✅ Ficha "' + letter + '" colocada en (' + row + ', ' + col + ')');
        }

        function undoMove() {
            if (currentMove.length === 0) {
                showInfo('warning', '⚠️ No hay movimientos para deshacer');
                return;
            }

            const lastMove = currentMove.pop();
            gameBoard[lastMove.row][lastMove.col] = null;
            playerRack.splice(lastMove.rackIndex, 0, lastMove.letter);
            
            render();
            showInfo('success', '↩️ Movimiento deshecho');
        }

        function playTurn() {
            if (currentMove.length === 0) {
                showInfo('warning', '⚠️ Coloca al menos una ficha antes de jugar');
                return;
            }

            const words = extractWords();
            if (words.length === 0) {
                showInfo('error', '❌ No se formaron palabras válidas (mínimo 2 letras)');
                return;
            }

            let allValid = true;
            let invalidWord = '';
            for (let word of words) {
                if (!isValidWord(word.text)) {
                    allValid = false;
                    invalidWord = word.text;
                    break;
                }
            }

            if (!allValid) {
                showInfo('error', '❌ "' + invalidWord + '" no es una palabra válida del diccionario');
                return;
            }

            const points = calculatePoints(words);
            lastScore = points;
            totalPoints += points;
            
            for (let word of words) {
                playedWords.push({ word: word.text, points: word.points });
            }

            // Rellenar rack con nuevas fichas
            const drawn = drawTiles(RACK_SIZE - playerRack.length);
            playerRack = playerRack.concat(drawn);

            currentMove = [];
            turnNumber++;
            
            render();
            showInfo('success', '✅ ¡Turno jugado! ' + words.map(w => w.text).join(', ') + ' = +' + points + ' puntos');
        }

        function extractWords() {
            let words = [];
            
            // Extraer palabras horizontales
            for (let row = 0; row < BOARD_SIZE; row++) {
                let word = '';
                let positions = [];
                for (let col = 0; col < BOARD_SIZE; col++) {
                    if (gameBoard[row][col]) {
                        word += gameBoard[row][col];
                        positions.push({ row, col });
                    } else if (word.length >= 2) {
                        words.push({ text: word, positions });
                        word = '';
                        positions = [];
                    } else {
                        word = '';
                        positions = [];
                    }
                }
                if (word.length >= 2) {
                    words.push({ text: word, positions });
                }
            }

            // Extraer palabras verticales
            for (let col = 0; col < BOARD_SIZE; col++) {
                let word = '';
                let positions = [];
                for (let row = 0; row < BOARD_SIZE; row++) {
                    if (gameBoard[row][col]) {
                        word += gameBoard[row][col];
                        positions.push({ row, col });
                    } else if (word.length >= 2) {
                        words.push({ text: word, positions });
                        word = '';
                        positions = [];
                    } else {
                        word = '';
                        positions = [];
                    }
                }
                if (word.length >= 2) {
                    words.push({ text: word, positions });
                }
            }

            // Calcular puntos para cada palabra
            words = words.map(w => ({
                text: w.text,
                positions: w.positions,
                points: calculateWordPoints(w.text, w.positions)
            }));

            return words;
        }

        function isValidWord(word) {
            return VALID_WORDS.has(word.toUpperCase());
        }

        function calculatePoints(words) {
            return words.reduce((sum, word) => sum + word.points, 0);
        }

        function calculateWordPoints(word, positions) {
            let points = 0;
            let wordMultiplier = 1;

            for (let i = 0; i < word.length; i++) {
                const letter = word[i];
                const pos = positions[i];
                const cellType = BOARD_CONFIG[pos.row][pos.col];
                
                let letterValue = LETTER_VALUES[letter] || 0;
                
                if (cellType === 1) letterValue *= 2; // Double Letter
                else if (cellType === 2) letterValue *= 3; // Triple Letter
                else if (cellType === 3) wordMultiplier *= 2; // Double Word
                else if (cellType === 4) wordMultiplier *= 3; // Triple Word
                
                points += letterValue;
            }

            return points * wordMultiplier;
        }

        function shuffleRack() {
            playerRack = shuffle(playerRack);
            renderRack();
            showInfo('success', '🔀 Fichas mezcladas en tu rack');
        }

        function exchangeTiles() {
            if (currentMove.length > 0) {
                showInfo('error', '❌ No puedes cambiar fichas si ya colocaste algunas. Deshaz primero.');
                return;
            }

            if (tileBag.length < playerRack.length) {
                showInfo('error', '❌ No hay suficientes fichas en la bolsa para cambiar');
                return;
            }

            // Devolver fichas actuales a la bolsa
            tileBag = tileBag.concat(playerRack);
            tileBag = shuffle(tileBag);
            
            // Tomar nuevas fichas
            playerRack = drawTiles(RACK_SIZE);
            turnNumber++;
            
            render();
            showInfo('success', '🔄 Fichas cambiadas. Turno perdido. Quedan ' + tileBag.length + ' fichas en la bolsa');
        }

        function newGame() {
            if (totalPoints > 0) {
                if (confirm('¿Seguro que quieres iniciar un nuevo juego? Se perderá tu puntuación actual de ' + totalPoints + ' puntos.')) {
                    initGame();
                }
            } else {
                initGame();
            }
        }

        function showInfo(type, message) {
            const panel = document.getElementById('infoPanel');
            const text = document.getElementById('infoText');
            
            panel.className = 'info-panel' + (type !== 'success' ? (' ' + type) : '');
            text.innerHTML = message;
        }

        // ============ INICIAR JUEGO AL CARGAR ============
        initGame();
    </script>
</body>
</html>
