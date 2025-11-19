<script lang="ts">
    import { onMount, onDestroy } from 'svelte';
    import Preloader from '$lib/Preloader.svelte';
    import { goto } from '$app/navigation';
    import { fade } from 'svelte/transition';
    import { studentData } from '$lib/store/student_data';
    import { audioStore } from '$lib/store/audio_store';
    import { apiUrl } from '$lib/api_base';
    import { preloadImagesBatched } from '$lib/preload';

    interface StudentData {
        pk_studentID: number;
        idNo: string;
        studentName: string;
        studentPass: string;
        studentGender: 'Male' | 'Female';
        studentLevel: number;
        studentRibbon: number | null;
        studentColtrash: number | null;
        studentProgress: number | null;
    }

    const GRID_WIDTH = 160;
    const GRID_HEIGHT = 20;
    const TILE_SIZE = 32;
    const PLAYER_SIZE = 32;
    const PLAYER_SPEED = 2;
    const HOUSE_SIZE = 300;
    const TREE_SIZE = 180;
    const TRASH_SIZE = 50;

    interface Player {
        x: number;
        y: number;
        direction: 'right' | 'left' | 'front' | 'back';
        frame: number;
        isMoving: boolean;
    }

    interface Trash {
        x: number;
        y: number;
        type: number;
        scale: number;
        fade: number;
        bounce: number;
    }

    interface TrashPopup {
        x: number;
        y: number;
        name: string;
        opacity: number;
        time: number;
    }

    let player: Player = { x: 0, y: GRID_HEIGHT * TILE_SIZE / 2, direction: 'right', frame: 0, isMoving: false };
    let trashCollectedSession = 0;
    let trashCollectedTotal = 0;
    
    let gameCanvas: HTMLCanvasElement;
    let ctx: CanvasRenderingContext2D;
    const map: number[][] = Array(GRID_HEIGHT).fill(null).map(() => Array(GRID_WIDTH).fill(0));
    let trashes: Trash[] = [];
    let trashPopups: TrashPopup[] = [];
    let scaleFactor = 1;
    let cameraX = 0;
    let gameEnded = false;
    let isLoading = false;

    let startMessageOpacity = 1;
    let startMessageFadeDirection = -0.01;
    let arrowWiggleAngle = 0;
    let fadeTimer = 180;

    let collectSound: HTMLAudioElement;
    let bgMusic: HTMLAudioElement;
    let hasInteracted = false;

    interface CharacterAssets {
        walking_right: HTMLImageElement[];
        walking_left: HTMLImageElement[];
        walking_front: HTMLImageElement[];
        walking_back: HTMLImageElement[];
    }

    interface Assets {
        character: CharacterAssets;
        ground: { soil: HTMLImageElement; grass: HTMLImageElement };
        house: HTMLImageElement[];
        trees: HTMLImageElement[];
        trash: HTMLImageElement[];
    }

    let assets: Assets;
    let showPreloader = true;

    // Pre-expand critical URLs for this game scene (story2 house)
    const _crit1 = import.meta.glob('/static/trash_collect_game/boy/walking_sprite/**/*.{png,jpg,jpeg,webp,gif,svg}', { eager: true, as: 'url' });
    const _crit2 = import.meta.glob('/static/trash_collect_game/girl/walking_sprite/**/*.{png,jpg,jpeg,webp,gif,svg}', { eager: true, as: 'url' });
    const _crit3 = import.meta.glob('/static/trash_collect_game/ground/*.{png,jpg,jpeg,webp,gif,svg}', { eager: true, as: 'url' });
    const _crit4 = import.meta.glob('/static/trash_collect_game/house/story2/*.{png,jpg,jpeg,webp,gif,svg}', { eager: true, as: 'url' });
    const criticalUrls = Array.from(new Set([...Object.values(_crit1), ...Object.values(_crit2), ...Object.values(_crit3), ...Object.values(_crit4)].filter(Boolean)));

    const _non1 = import.meta.glob('/static/trash_collect_game/trash/*.{png,jpg,jpeg,webp,gif,svg}', { eager: true, as: 'url' });
    const nonCriticalUrls = Array.from(new Set([...Object.values(_non1)].filter(Boolean)));

    async function loadImage(src: string): Promise<HTMLImageElement> {
        return new Promise((resolve, reject) => {
            const img = new Image();
            img.src = src;
            img.onload = () => resolve(img);
            img.onerror = () => reject(new Error(`Failed to load ${src}`));
        });
    }

    async function loadAssets(): Promise<Assets> {
        const currentStudent = $studentData as StudentData | null;
        const gender = currentStudent?.studentGender ?? 'Male';

        const character = gender === 'Female' ? {
            walking_right: await Promise.all(Array(3).fill(null).map((_, i) => loadImage(`/converted/trash_collect_game/girl/walking_sprite/walking_right/${i+1}.webp`))),
            walking_left: await Promise.all(Array(3).fill(null).map((_, i) => loadImage(`/converted/trash_collect_game/girl/walking_sprite/walking_left/${i+1}.webp`))),
            walking_front: await Promise.all(Array(3).fill(null).map((_, i) => loadImage(`/converted/trash_collect_game/girl/walking_sprite/walking_front/${i+1}.webp`))),
            walking_back: await Promise.all(Array(3).fill(null).map((_, i) => loadImage(`/converted/trash_collect_game/girl/walking_sprite/walking_back/${i+1}.webp`)))
        } : {
            walking_right: await Promise.all(Array(3).fill(null).map((_, i) => loadImage(`/converted/trash_collect_game/boy/walking_sprite/walking_right/${i+1}.webp`))),
            walking_left: await Promise.all(Array(3).fill(null).map((_, i) => loadImage(`/converted/trash_collect_game/boy/walking_sprite/walking_left/${i+1}.webp`))),
            walking_front: await Promise.all(Array(3).fill(null).map((_, i) => loadImage(`/converted/trash_collect_game/boy/walking_sprite/walking_front/${i+1}.webp`))),
            walking_back: await Promise.all(Array(3).fill(null).map((_, i) => loadImage(`/converted/trash_collect_game/boy/walking_sprite/walking_back/${i+1}.webp`)))
        };

        const ground = {
            soil: await loadImage('/converted/trash_collect_game/ground/soil.webp'),
            grass: await loadImage('/converted/trash_collect_game/ground/grass.webp')
        };
        const house = await Promise.all(Array(3).fill(null).map((_, i) => loadImage(`/converted/trash_collect_game/house/story2/${i+1}.webp`)));
        const trees = await Promise.all(Array(5).fill(null).map((_, i) => loadImage(`/converted/trash_collect_game/trees/${i+1}.webp`)));
        const trash = await Promise.all(Array(25).fill(null).map((_, i) => loadImage(`/converted/trash_collect_game/trash/${String(i+1).padStart(2, '0')}-${getTrashName(i+1)}.webp`)));
        return { character, ground, house, trees, trash };
    }

    function getTrashName(i: number): string {
        const names = [
            'Eggshell', 'Banana Peel', 'Meat Bone', 'Fish Bone', 'Apple Waste', 'Cabbage Waste', 'Styrofoam Waste', 
            'Plastic Waste', 'Snack Wrapper', 'Milk Carton', 'Plastic Bottle', 'Rope Waste', 'Scrap Wood', 'Twigs', 
            'Fallen Leaf', 'Scratch Paper', 'Old Magazine', 'Cardboard Box', 'Empty Can', 'Fabric Scrap', 
            'Old T-shirt', 'Broken Glass Shard', 'Shattered Glass Cup', 'Discarded Plate', 'Cigarette Butt'
        ];
        return names[i-1];
    }

    function resizeCanvas() {
        if (!gameCanvas) return;
        const maxHeight = window.innerHeight * 0.9;
        gameCanvas.height = maxHeight;
        const tilePixelHeight = maxHeight / GRID_HEIGHT;
        const visibleTilesWide = Math.min(GRID_WIDTH, Math.floor(window.innerWidth * 0.9 / tilePixelHeight));
        gameCanvas.width = visibleTilesWide * tilePixelHeight;
        scaleFactor = tilePixelHeight / TILE_SIZE;
        if (ctx) draw();
    }

    function generateMap(): void {
        for (let y = 0; y < GRID_HEIGHT; y++) {
            for (let x = 0; x < GRID_WIDTH; x++) {
                if (Math.random() < 0.6) map[y][x] = 1;
            }
        }

        const placedObjects: { x: number; y: number; type: number }[] = [];
        const START_AREA_WIDTH = 5;

        for (let i = 0; i < 30; i++) {
            let x: number, y: number;
            let isValidPosition = false;
            const isHouse = Math.random() < 0.5;

            do {
                x = Math.floor(Math.random() * (GRID_WIDTH - START_AREA_WIDTH)) + START_AREA_WIDTH;
                y = Math.floor(Math.random() * GRID_HEIGHT);
                const objectSize = isHouse ? HOUSE_SIZE : TREE_SIZE;
                const objectLeft = x * TILE_SIZE + (TILE_SIZE - objectSize) / 2;
                const objectRight = objectLeft + objectSize;
                const objectTop = y * TILE_SIZE + (TILE_SIZE - objectSize) / 2;
                const objectBottom = objectTop + objectSize;

                isValidPosition = true;
                for (const placed of placedObjects) {
                    if (placed.type === 2) {
                        const placedSize = HOUSE_SIZE;
                        const placedLeft = placed.x * TILE_SIZE + (TILE_SIZE - placedSize) / 2;
                        const placedRight = placedLeft + placedSize;
                        const placedTop = placed.y * TILE_SIZE + (TILE_SIZE - placedSize) / 2;
                        const placedBottom = placedTop + placedSize;

                        if (
                            objectLeft < placedRight &&
                            objectRight > placedLeft &&
                            objectTop < placedBottom &&
                            objectBottom > placedTop
                        ) {
                            isValidPosition = false;
                            break;
                        }
                    }
                }

                if (x < START_AREA_WIDTH || x >= GRID_WIDTH || y < 0 || y >= GRID_HEIGHT) {
                    isValidPosition = false;
                }
            } while (!isValidPosition);

            map[y][x] = isHouse ? 2 : 3;
            placedObjects.push({ x, y, type: isHouse ? 2 : 3 });
        }

        trashes = [];
        for (let i = 0; i < 100; i++) {
            let x: number, y: number;
            let isValidPosition = false;

            do {
                x = Math.floor(Math.random() * GRID_WIDTH * TILE_SIZE);
                y = Math.floor(Math.random() * GRID_HEIGHT * TILE_SIZE);

                isValidPosition = true;
                for (let gridY = 0; gridY < GRID_HEIGHT; gridY++) {
                    for (let gridX = 0; gridX < GRID_WIDTH; gridX++) {
                        if (map[gridY][gridX] === 2 || map[gridY][gridX] === 3) {
                            const objectSize = map[gridY][gridX] === 2 ? HOUSE_SIZE : TREE_SIZE;
                            const objectLeft = gridX * TILE_SIZE + (TILE_SIZE - objectSize) / 2;
                            const objectRight = objectLeft + objectSize;
                            const objectTop = gridY * TILE_SIZE + (TILE_SIZE - objectSize) / 2;
                            const objectBottom = objectTop + objectSize;

                            const trashLeft = x - TRASH_SIZE / 2;
                            const trashRight = x + TRASH_SIZE / 2;
                            const trashTop = y - TRASH_SIZE / 2;
                            const trashBottom = y + TRASH_SIZE / 2;

                            if (
                                trashLeft < objectRight &&
                                trashRight > objectLeft &&
                                trashTop < objectBottom &&
                                trashBottom > objectTop
                            ) {
                                isValidPosition = false;
                                break;
                            }
                        }
                    }
                    if (!isValidPosition) break;
                }
            } while (!isValidPosition);

            trashes.push({ x, y, type: Math.floor(Math.random() * 25), scale: 1, fade: 1, bounce: 0 });
        }
    }

    function canMove(x: number, y: number): boolean {
        const playerLeft = x - PLAYER_SIZE / 2;
        const playerRight = x + PLAYER_SIZE / 2;
        const playerTop = y - PLAYER_SIZE / 2;
        const playerBottom = y + PLAYER_SIZE / 2;

        for (let gridY = 0; gridY < GRID_HEIGHT; gridY++) {
            for (let gridX = 0; gridX < GRID_WIDTH; gridX++) {
                if (map[gridY][gridX] === 2 || map[gridY][gridX] === 3) {
                    const objectSize = map[gridY][gridX] === 2 ? HOUSE_SIZE : TREE_SIZE;
                    const objectLeft = gridX * TILE_SIZE + (TILE_SIZE - objectSize) / 2;
                    const objectRight = objectLeft + objectSize;
                    const objectTop = gridY * TILE_SIZE + (TILE_SIZE - objectSize) / 2;
                    const objectBottom = objectTop + objectSize;

                    if (
                        playerLeft < objectRight &&
                        playerRight > objectLeft &&
                        playerTop < objectBottom &&
                        playerBottom > objectTop
                    ) {
                        const objectImg = map[gridY][gridX] === 2 ? assets.house[gridX % 3] : assets.trees[gridX % 5];
                        const overlapLeft = Math.max(playerLeft, objectLeft);
                        const overlapRight = Math.min(playerRight, objectRight);
                        const overlapTop = Math.max(playerTop, objectTop);
                        const overlapBottom = Math.min(playerBottom, objectBottom);

                        const tempCanvas = document.createElement('canvas');
                        tempCanvas.width = objectSize;
                        tempCanvas.height = objectSize;
                        const tempCtx = tempCanvas.getContext('2d', { willReadFrequently: true })!;
                        tempCtx.drawImage(objectImg, 0, 0, objectSize, objectSize);

                        for (let checkY = overlapTop; checkY < overlapBottom; checkY++) {
                            for (let checkX = overlapLeft; checkX < overlapRight; checkX++) {
                                const imgX = Math.floor(checkX - objectLeft);
                                const imgY = Math.floor(checkY - objectTop);
                                const pixelData = tempCtx.getImageData(imgX, imgY, 1, 1).data;
                                const alpha = pixelData[3];

                                if (alpha > 0) {
                                    return false;
                                }
                            }
                        }
                    }
                }
            }
        }

        const gridX = Math.floor(x / TILE_SIZE);
        const gridY = Math.floor(y / TILE_SIZE);
        if (gridX < 0 || gridX >= GRID_WIDTH || gridY < 0 || gridY >= GRID_HEIGHT) return false;

        return true;
    }

    function updateCamera() {
        const canvasWidthInTiles = gameCanvas.width / (TILE_SIZE * scaleFactor);
        cameraX = Math.max(0, Math.min(
            player.x - canvasWidthInTiles * TILE_SIZE / 2,
            GRID_WIDTH * TILE_SIZE - canvasWidthInTiles * TILE_SIZE
        ));
        if (player.x >= GRID_WIDTH * TILE_SIZE - TILE_SIZE) gameEnded = true;
    }

    function playBackgroundMusic() {
        if (bgMusic && !hasInteracted) {
            bgMusic.currentTime = 0;
            bgMusic.play().catch(error => console.error("Error playing background music:", error));
            hasInteracted = true;
        }
    }

    function update(): void {
        if (gameEnded) return;

        trashes = trashes.map(t => ({
            ...t,
            bounce: Math.sin(Date.now() / 200) * 2,
            scale: t.fade < 1 ? t.scale + 0.1 : 1,
            fade: t.fade < 1 ? t.fade - 0.05 : 1
        }));

        trashes = trashes.filter(t => {
            const dx = player.x - t.x;
            const dy = player.y - t.y;
            if (Math.sqrt(dx*dx + dy*dy) < (PLAYER_SIZE + TRASH_SIZE) / 2 && t.fade === 1) {
                trashCollectedSession++;
                t.fade = 0.95;
                trashPopups.push({
                    x: t.x,
                    y: t.y - 20,
                    name: getTrashName(t.type + 1),
                    opacity: 1,
                    time: 480
                });
                if (collectSound) {
                    collectSound.currentTime = 0;
                    collectSound.play().catch(error => console.error("Error playing collect sound:", error));
                }
                return true;
            }
            return t.fade > 0;
        });

        trashPopups = trashPopups.map(p => {
            const fadeStart = 300;
            return {
                ...p,
                y: p.y - 0.5,
                opacity: p.time > fadeStart ? 1 : (p.time / fadeStart),
                time: p.time - 1
            };
        }).filter(p => p.time > 0);

        if (player.isMoving) {
            player.frame = (player.frame + 0.2) % 3;
        }

        fadeTimer = Math.max(0, fadeTimer - 1);
        if (fadeTimer === 0) {
            startMessageOpacity += startMessageFadeDirection;
            if (startMessageOpacity <= 0 || startMessageOpacity >= 1) {
                startMessageFadeDirection *= -1;
            }
        }
        arrowWiggleAngle = Math.sin(Date.now() / 200) * 10;

        updateCamera();
        requestAnimationFrame(draw);
    }

    function draw(): void {
        if (!ctx || !gameCanvas || !assets) return;

        ctx.clearRect(0, 0, gameCanvas.width, gameCanvas.height);
        ctx.save();
        ctx.scale(scaleFactor, scaleFactor);
        ctx.translate(-cameraX, 0);

        for (let y = 0; y < GRID_HEIGHT; y++) {
            for (let x = 0; x < GRID_WIDTH; x++) {
                const groundImg = map[y][x] === 0 ? assets.ground.soil : assets.ground.grass;
                ctx.drawImage(groundImg, x * TILE_SIZE, y * TILE_SIZE, TILE_SIZE, TILE_SIZE);
            }
        }

        for (let y = 0; y < GRID_HEIGHT; y++) {
            for (let x = 0; x < GRID_WIDTH; x++) {
                if (map[y][x] === 2) {
                    const houseX = x * TILE_SIZE + (TILE_SIZE - HOUSE_SIZE) / 2;
                    const houseY = y * TILE_SIZE + (TILE_SIZE - HOUSE_SIZE) / 2;
                    ctx.drawImage(assets.house[x % 3], houseX, houseY, HOUSE_SIZE, HOUSE_SIZE);
                } else if (map[y][x] === 3) {
                    const treeX = x * TILE_SIZE + (TILE_SIZE - TREE_SIZE) / 2;
                    const treeY = y * TILE_SIZE + (TILE_SIZE - TREE_SIZE) / 2;
                    ctx.drawImage(assets.trees[x % 5], treeX, treeY, TREE_SIZE, TREE_SIZE);
                }
            }
        }

        trashes.forEach(t => {
            ctx.save();
            ctx.translate(t.x, t.y + t.bounce);
            ctx.scale(t.scale, t.scale);
            ctx.globalAlpha = t.fade;
            ctx.drawImage(assets.trash[t.type], -TRASH_SIZE / 2, -TRASH_SIZE / 2, TRASH_SIZE, TRASH_SIZE);
            ctx.restore();
        });

        const playerImg = assets.character[`walking_${player.direction}`][Math.floor(player.frame)];
        ctx.save();
        ctx.translate(player.x, player.y);
        const originalWidth = 30;
        const originalHeight = 60;
        const aspectRatio = originalWidth / originalHeight;
        const displayWidth = PLAYER_SIZE;
        const displayHeight = PLAYER_SIZE / aspectRatio;
        if (player.isMoving) {
            const bounceScale = 1 + Math.sin(Date.now() / 100) * 0.05;
            ctx.scale(1, bounceScale);
        }
        ctx.drawImage(playerImg, -displayWidth / 2, -displayHeight / 2, displayWidth, displayHeight);
        ctx.restore();

        ctx.fillStyle = 'rgba(0, 255, 0, 0.1)';
        ctx.fillRect(0, 0, TILE_SIZE, GRID_HEIGHT * TILE_SIZE);
        ctx.fillStyle = 'rgba(255, 0, 0, 0.5)';
        ctx.fillRect((GRID_WIDTH - 1) * TILE_SIZE, 0, TILE_SIZE, GRID_HEIGHT * TILE_SIZE);

        if (player.x < TILE_SIZE * 5) {
            ctx.save();
            ctx.globalAlpha = startMessageOpacity;
            const textX = TILE_SIZE + 20;
            const textY = GRID_HEIGHT * TILE_SIZE / 2;
            const text = "GO START!";
            ctx.font = '40px "Comic Sans MS", cursive';
            const textWidth = ctx.measureText(text).width;
            const bubblePadding = 15;
            const bubbleWidth = textWidth + bubblePadding * 2;
            const bubbleHeight = 50;
            const bubbleX = textX - bubblePadding;
            const bubbleY = textY - bubbleHeight / 2 - 10;

            ctx.fillStyle = 'rgba(255, 255, 0, 0.8)';
            ctx.beginPath();
            ctx.roundRect(bubbleX, bubbleY, bubbleWidth, bubbleHeight, 20);
            ctx.fill();
            ctx.strokeStyle = '#ff6f61';
            ctx.lineWidth = 4;
            ctx.stroke();

            ctx.fillStyle = '#ff6f61';
            ctx.textAlign = 'left';
            ctx.shadowColor = '#000';
            ctx.shadowBlur = 5;
            ctx.shadowOffsetX = 2;
            ctx.shadowOffsetY = 2;
            ctx.fillText(text, textX, textY);
            ctx.shadowBlur = 0;
            ctx.shadowOffsetX = 0;
            ctx.shadowOffsetY = 0;

            const arrowX = textX + textWidth + 20;
            const arrowY = textY - 10;
            const bounceOffset = Math.sin(Date.now() / 200) * 5;
            ctx.translate(arrowX, arrowY + bounceOffset);

            ctx.beginPath();
            ctx.moveTo(0, 0);
            ctx.lineTo(30, 0);
            ctx.lineTo(30, -20);
            ctx.lineTo(50, 0);
            ctx.lineTo(30, 20);
            ctx.lineTo(30, 0);
            ctx.closePath();
            ctx.fillStyle = '#00cc00';
            ctx.fill();
            ctx.strokeStyle = '#fff';
            ctx.lineWidth = 3;
            ctx.stroke();

            ctx.restore();
        }

        ctx.font = '16px "Comic Sans MS", cursive';
        ctx.textAlign = 'center';
        trashPopups.forEach(p => {
            ctx.save();
            ctx.globalAlpha = p.opacity;
            const textMetrics = ctx.measureText(p.name);
            const textWidth = textMetrics.width;
            const padding = 10;
            const backgroundWidth = textWidth + padding * 2;
            const backgroundHeight = 20;
            ctx.fillStyle = 'rgba(255, 255, 255, 1)';
            ctx.fillRect(p.x - backgroundWidth / 2, p.y - backgroundHeight / 1.5, backgroundWidth, backgroundHeight);
            ctx.fillStyle = '#ff6f61';
            ctx.fillText(p.name, p.x, p.y);
            ctx.restore();
        });

        ctx.restore();

        if (gameEnded) {
            ctx.fillStyle = 'rgba(0, 0, 0, 0.7)';
            ctx.fillRect(0, 0, gameCanvas.width, gameCanvas.height);
        }
    }

    interface Keys {
        ArrowUp: boolean;
        ArrowDown: boolean;
        ArrowLeft: boolean;
        ArrowRight: boolean;
        up: boolean;
        down: boolean;
        left: boolean;
        right: boolean;
    }

    let keys: Keys = {
        ArrowUp: false,
        ArrowDown: false,
        ArrowLeft: false,
        ArrowRight: false,
        up: false,
        down: false,
        left: false,
        right: false
    };

    function handleMovement(): void {
        if (gameEnded) return;

        let newX = player.x;
        let newY = player.y;
        player.isMoving = false;

        if (keys.ArrowUp || keys.up) {
            newY -= PLAYER_SPEED;
            player.direction = 'back';
            player.isMoving = true;
            playBackgroundMusic();
        }
        if (keys.ArrowDown || keys.down) {
            newY += PLAYER_SPEED;
            player.direction = 'front';
            player.isMoving = true;
            playBackgroundMusic();
        }
        if (keys.ArrowLeft || keys.left) {
            newX -= PLAYER_SPEED;
            player.direction = 'left';
            player.isMoving = true;
            playBackgroundMusic();
        }
        if (keys.ArrowRight || keys.right) {
            newX += PLAYER_SPEED;
            player.direction = 'right';
            player.isMoving = true;
            playBackgroundMusic();
        }

        if (canMove(newX, player.y)) player.x = newX;
        if (canMove(player.x, newY)) player.y = newY;
    }

    function exitGame() {
        if (bgMusic) {
            bgMusic.pause();
            bgMusic.currentTime = 0;
        }
        // Check if we should return to village
        const returnToVillage = localStorage.getItem('villageReturnScene');
        if (returnToVillage !== null) {
            goto('/student/village');
        } else {
            goto('/student/dashboard');
        }
    }

    async function fetchWithTimeout(url: string, options: RequestInit, timeout = 5000): Promise<Response> {
        const controller = new AbortController();
        const id = setTimeout(() => controller.abort(), timeout);
        const response = await fetch(url, { ...options, signal: controller.signal });
        clearTimeout(id);
        return response;
    }

    async function fetchInitialTrashCount(studentID: number) {
        try {
            const response = await fetchWithTimeout(
                apiUrl('trash2.php'),
                {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ pk_studentID: studentID })
                }
            );

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();
            if (data.error) {
                console.error('Error fetching initial trash count:', data.error);
                return 0;
            }
            return data.studentColtrash || 0;
        } catch (error) {
            console.error('Fetch initial trash count error:', error);
            if (error instanceof Error && error.name === 'AbortError') {
                console.error('Fetch timed out');
            }
            return 0;
        }
    }

    async function handleOk() {
        isLoading = true;

        if (bgMusic) {
            bgMusic.pause();
            bgMusic.currentTime = 0;
        }

        const currentStudent = $studentData as StudentData | null;
        if (!currentStudent?.pk_studentID) {
            console.error('No student ID found in studentData');
            isLoading = false;
            // Check if we should return to village
            const returnToVillage = localStorage.getItem('villageReturnScene');
            if (returnToVillage !== null) {
                goto('/student/village');
            } else {
                goto('/student/dashboard');
            }
            return;
        }

        try {
            const response = await fetchWithTimeout(
                apiUrl('trash2.php'),
                {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        pk_studentID: currentStudent.pk_studentID,
                        trashCollected: trashCollectedSession
                    })
                }
            );

            if (!response.ok) {
                const errorText = await response.text();
                console.error('Response not OK:', response.status, errorText);
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();
            if (data.error) {
                console.error('Update failed:', data.error);
            } else {
                console.log('Update successful:', data);
                studentData.update(current => {
                    if (current) {
                        return { 
                            ...current, 
                            studentColtrash: data.studentColtrash,
                            studentLevel: data.studentLevel
                        };
                    }
                    return current;
                });
                trashCollectedTotal = data.studentColtrash;
            }
        } catch (error) {
            console.error('Error in handleOk:', error);
            if (error instanceof Error && error.name === 'AbortError') {
                console.error('Fetch timed out');
            }
        } finally {
            isLoading = true;
            await new Promise(resolve => setTimeout(resolve, 1000));
            // Check if we should return to village
            const returnToVillage = localStorage.getItem('villageReturnScene');
            if (returnToVillage !== null) {
                goto('/student/village');
            } else {
                goto('/student/dashboard');
            }
        }
    }

    async function initGame() {
        // Stop global background music
        audioStore.stopAll();

        assets = await loadAssets();
        if (gameCanvas) ctx = gameCanvas.getContext('2d', { willReadFrequently: true }) as CanvasRenderingContext2D;

        // Use document.createElement instead of new Audio() to avoid Vite StubAudio
        collectSound = document.createElement('audio');
        collectSound.src = '/trash_collect_game/audio/collect_effect.mp3';
        collectSound.volume = 0.7;

        bgMusic = document.createElement('audio');
        bgMusic.src = '/trash_collect_game/audio/game_bg.mp3';
        bgMusic.loop = true;
        bgMusic.volume = 0.5;

        trashCollectedSession = 0;

        const currentStudent = $studentData as StudentData | null;
        if (currentStudent?.pk_studentID) {
            trashCollectedTotal = await fetchInitialTrashCount(currentStudent.pk_studentID);
            studentData.update(current => {
                if (current) {
                    return { ...current, studentColtrash: trashCollectedTotal };
                }
                return current;
            });
            console.log('Initial trash collected from DB:', trashCollectedTotal);
        }

        resizeCanvas();
        generateMap();
        window.addEventListener('keydown', (e: KeyboardEvent) => { keys[e.key as keyof Keys] = true; });
        window.addEventListener('keyup', (e: KeyboardEvent) => { keys[e.key as keyof Keys] = false; });
        window.addEventListener('resize', resizeCanvas);
        setInterval(() => { handleMovement(); update(); }, 1000 / 60);
    }

    async function handlePreloadDone() {
        showPreloader = false;
        if (nonCriticalUrls.length > 0) {
            preloadImagesBatched(nonCriticalUrls, () => {}, 6).catch((e) => console.warn('background preload failed', e));
        }
        await initGame();
    }

    onMount(async () => {
        if (!showPreloader) {
            await initGame();
            return;
        }
    });
    
    onDestroy(() => {
        console.log('🧹 Cleaning up trash game 2');
        
        // Stop game audio completely
        if (bgMusic) {
            bgMusic.pause();
            bgMusic.currentTime = 0;
            bgMusic.src = ''; // Clear source to release audio
        }
        if (collectSound) {
            collectSound.pause();
            collectSound.src = '';
        }
        
        // Resume global background music when leaving game
        audioStore.playTrack('default');
    });

    $: winMessage = (($studentData as StudentData)?.studentLevel ?? 0) < 2 
        ? "Congratulations! You have leveled up!\nGet ready for your next exciting adventure! 🏆✨" 
        : "Congratulations!\nGet ready for your next exciting adventure! 🏆✨";

    // Calculate player position percentage for the indicator
    $: playerPositionPercent = (player.x / (GRID_WIDTH * TILE_SIZE)) * 100;
</script>

{#if showPreloader}
    <Preloader assetUrls={criticalUrls} batchSize={6} onComplete={handlePreloadDone} allowSkip={true} />
{:else}

<main>
    <div class="game-wrapper">
        <h1 class="title">Shenievia Adventure!</h1>
        <div class="trash-indicator">
            <span class="trash-label">Collected Trash:</span>
            <div class="trash-count">
                <svg width="24" height="24" viewBox="0 0 24 24"><path d="M20 4H4v2h16V4zm-2 4H6v12h12V8z"/></svg>
                <span>{trashCollectedSession}</span>
            </div>
        </div>
        <button class="exit-btn" on:click={exitGame}>Exit Game</button>
        <div class="game-container">
            <canvas bind:this={gameCanvas}></canvas>
            <div class="controls">
                <div class="cross-layout">
                    <button class="control-btn up" aria-label="Move Up" on:mousedown={() => keys.up = true} on:mouseup={() => keys.up = false}>
                        <svg viewBox="0 0 24 24"><path d="M12 2l-8 10h16z"/></svg>
                    </button>
                    <button class="control-btn left" aria-label="Move Left" on:mousedown={() => keys.left = true} on:mouseup={() => keys.left = false}>
                        <svg viewBox="0 0 24 24"><path d="M2 12l10-8v16z"/></svg>
                    </button>
                    <div class="center-space"></div>
                    <button class="control-btn right" aria-label="Move Right" on:mousedown={() => keys.right = true} on:mouseup={() => keys.right = false}>
                        <svg viewBox="0 0 24 24"><path d="M22 12l-10-8v16z"/></svg>
                    </button>
                    <button class="control-btn down" aria-label="Move Down" on:mousedown={() => keys.down = true} on:mouseup={() => keys.down = false}>
                        <svg viewBox="0 0 24 24"><path d="M12 22l8-10H4z"/></svg>
                    </button>
                </div>
            </div>
            {#if gameEnded}
                <div class="win-message text-center" in:fade={{ duration: 500 }}>
                    🎉 {winMessage.split('\n')[0]} 🎉<br>
                    {winMessage.split('\n')[1]}<br>
                    {#if isLoading}
                        <span class="loading">Loading...</span>
                    {:else}
                        <button class="ok-btn" on:click={handleOk}>OK</button>
                    {/if}
                </div>
            {/if}
        </div>
        <!-- Position Indicator -->
        <div class="position-indicator">
            <div class="track">
                <div class="start-zone"></div>
                <div class="end-zone"></div>
                <div class="player-marker" style="left: {playerPositionPercent}%"></div>
            </div>
        </div>
    </div>
</main>

{/if}

<style>
    .game-wrapper {
        background: #e0f7e0;
        padding: 20px;
        height: 100vh;
        display: flex;
        flex-direction: column;
        align-items: center;
        box-sizing: border-box;
        position: relative;
    }

    .title {
        font-family: 'Comic Sans MS', cursive;
        font-size: clamp(24px, 5vw, 36px);
        color: #ff6f61;
        text-shadow: 2px 2px #fff;
        margin: 0 0 10px 0;
        text-align: center;
    }

    .trash-indicator {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: clamp(5px, 1vw, 10px);
        margin-bottom: 10px;
    }

    .trash-label {
        font-family: 'Comic Sans MS', cursive;
        font-size: clamp(16px, 3vw, 20px);
        color: #333;
        text-shadow: 1px 1px #fff;
    }

    .trash-count {
        background: #fff;
        padding: clamp(3px, 0.5vw, 5px) clamp(5px, 1vw, 10px);
        border-radius: 20px;
        display: flex;
        align-items: center;
        gap: clamp(3px, 0.5vw, 5px);
        font-family: 'Comic Sans MS', cursive;
        font-size: clamp(16px, 3vw, 20px);
        color: #333;
        border: 2px solid #ff6f61;
    }

    .trash-count svg {
        width: clamp(20px, 3vw, 24px);
        height: clamp(20px, 3vw, 24px);
        fill: #ff6f61;
    }

    .exit-btn {
        position: absolute;
        top: 20px;
        right: 20px;
        background: #ff4444;
        color: #fff;
        border: 3px solid #cc0000;
        border-radius: 10px;
        padding: 10px 20px;
        font-family: 'Comic Sans MS', cursive;
        font-size: 18px;
        cursor: pointer;
        transition: background 0.2s, transform 0.1s;
    }

    .exit-btn:hover {
        background: #ff6666;
    }

    .exit-btn:active {
        transform: scale(0.95);
    }

    .game-container {
        position: relative;
        background: #a8d5a8;
        border: 5px solid #6b9e6b;
        border-radius: 10px;
        padding: 10px;
        height: calc(90vh - 146px); /* Adjusted to account for position indicator */
        width: auto;
        max-width: 100%;
        overflow-x: auto;
        overflow-y: hidden;
        flex-grow: 1;
        display: flex;
        align-items: center;
    }

    canvas {
        display: block;
        height: 100%;
        width: auto;
    }

    .controls {
        position: absolute;
        bottom: clamp(10px, 2vw, 20px);
        right: clamp(10px, 2vw, 20px);
    }

    .cross-layout {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr;
        grid-template-rows: 1fr 1fr 1fr;
        gap: clamp(5px, 1vw, 10px);
        justify-items: center;
        align-items: center;
    }

    .control-btn.up { grid-column: 2; grid-row: 1; }
    .control-btn.left { grid-column: 1; grid-row: 2; }
    .center-space {
        grid-column: 2;
        grid-row: 2;
        width: clamp(40px, 5vw, 50px);
        height: clamp(40px, 5vw, 50px);
    }
    .control-btn.right { grid-column: 3; grid-row: 2; }
    .control-btn.down { grid-column: 2; grid-row: 3; }

    .control-btn {
        width: clamp(40px, 5vw, 50px);
        height: clamp(40px, 5vw, 50px);
        background: #ffcc00;
        border: 3px solid #ff9900;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: transform 0.1s, background 0.1s;
    }

    .control-btn:hover { background: #ffd633; }
    .control-btn:active { transform: scale(0.9); background: #ff9900; }

    .control-btn svg {
        width: clamp(20px, 3vw, 24px);
        height: clamp(20px, 3vw, 24px);
        fill: #fff;
    }

    .win-message {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        background: #ffcc00;
        padding: clamp(10px, 2vw, 20px);
        border-radius: 10px;
        font-family: 'Comic Sans MS', cursive;
        font-size: clamp(20px, 4vw, 28px);
        color: #fff;
        text-shadow: 2px 2px #ff6f61;
        animation: pop 0.5s infinite alternate;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 10px;
    }

    .ok-btn {
        background: #00cc00;
        color: #fff;
        border: 3px solid #009900;
        border-radius: 10px;
        padding: 8px 20px;
        font-family: 'Comic Sans MS', cursive;
        font-size: 18px;
        cursor: pointer;
        transition: background 0.2s, transform 0.1s;
    }

    .ok-btn:hover {
        background: #00ff00;
    }

    .ok-btn:active {
        transform: scale(0.95);
    }

    .loading {
        font-size: 18px;
        color: #fff;
        animation: pulse 1s infinite;
    }

    /* Position Indicator Styles */
    .position-indicator {
        width: 100%;
        max-width: calc(100% - 40px); /* Match game-container width minus padding */
        height: 20px;
        margin-top: 10px;
    }

    .track {
        position: relative;
        width: 100%;
        height: 100%;
        background: #ccc;
        border-radius: 5px;
        overflow: hidden;
    }

    .start-zone {
        position: absolute;
        left: 0;
        width: 5%; /* Matches START_AREA_WIDTH / GRID_WIDTH */
        height: 100%;
        background: rgba(0, 255, 0, 0.5);
    }

    .end-zone {
        position: absolute;
        right: 0;
        width: 5%;
        height: 100%;
        background: rgba(255, 0, 0, 0.5);
    }

    .player-marker {
        position: absolute;
        width: 10px;
        height: 100%;
        background: #ff6f61;
        border-radius: 2px;
        transform: translateX(-50%);
        transition: left 0.1s ease-out;
    }

    @keyframes pop {
        from { transform: translate(-50%, -50%) scale(1); }
        to { transform: translate(-50%, -50%) scale(1.05); }
    }

    @keyframes pulse {
        0% { opacity: 1; }
        50% { opacity: 0.5; }
        100% { opacity: 1; }
    }
</style>
