<script lang="ts">
    import { onMount, onDestroy } from 'svelte';
    import { goto } from '$app/navigation';
    import { studentData } from '$lib/store/student_data';
    import { audioStore } from '$lib/store/audio_store';
    import type { StudentData } from '$lib/store/student_data';

    // Scene configuration
    const scenes = [
        { name: 'school', path: '/src/assets/Level_Walkthrough/places/school.webp', isLevel: false },
        { name: 'plain1', path: '/src/assets/Level_Walkthrough/places/plain.webp', isLevel: false },
        { name: 'sarisaristore', path: '/src/assets/Level_Walkthrough/places/sarisaristore.webp', isLevel: true, level: 1, title: 'Sari-Sari Store' },
        { name: 'houses1', path: '/src/assets/Level_Walkthrough/places/houses1.webp', isLevel: false },
        { name: 'wetmarket', path: '/src/assets/Level_Walkthrough/places/wetmarket.webp', isLevel: true, level: 2, title: 'Wet Market' },
        { name: 'houses2', path: '/src/assets/Level_Walkthrough/places/houses2.webp', isLevel: false },
        { name: 'plaza', path: '/src/assets/Level_Walkthrough/places/plaza.webp', isLevel: true, level: 3, title: 'Plaza' },
        { name: 'plain2', path: '/src/assets/Level_Walkthrough/places/plain.webp', isLevel: false },
        { name: 'home', path: '/src/assets/Level_Walkthrough/places/home.webp', isLevel: false }
    ];

    // State variables
    let currentScene = 0;
    let characterX = 50; // Percentage from left (50 = center)
    let isMoving = false;
    let direction: 'left' | 'right' | 'idle' = 'idle';
    let lastDirection: 'left' | 'right' | null = null;
    let animationFrame = 0;
    let lastAnimationFrame = 0;
    let isTurning = false; // Flag for direction change animation
    let isTransitioning = false;
    let showFade = false;
    let hasTriggeredInteraction = false; // Prevent multiple triggers
    let showLocationPrompt = false; // Show comic bubble prompt
    let promptLocation = { level: 0, title: '' }; // Store location info for prompt
    let promptDismissedForScene: number | null = null; // Track which scene had prompt dismissed
    let dialogueText = ''; // Novel-style dialogue text
    let showDialogue = false; // Show dialogue box
    let dialogueType: 'interaction' | 'lock' | 'instruction' = 'interaction'; // Type of dialogue
    let justReturnedFromPlay = false; // Flag to prevent auto-open after returning from play page
    let sceneVisited: Set<number> = new Set(); // Track which scenes have been visited
    let autoCloseTimer: number | null = null; // Timer for auto-closing narration
    let isFirstScene = false; // Track if this is the very first scene
    let levelVisited: Set<number> = new Set(); // Track which level locations have been visited
    let sceneNarrationClosed = false; // Flag to track if scene narration has been closed

    // Character sprite configuration
    let gender: 'boy' | 'girl' = 'boy';
    let animationInterval: number;

    // Smart spawn: Determine starting scene based on studentLevel
    function getStartingScene(studentLevel: number): number {
        if (studentLevel >= 3) return 6; // Plaza
        if (studentLevel >= 2) return 4; // Wet Market
        if (studentLevel >= 1) return 2; // Sari-Sari Store
        return 0; // School
    }

    // Initialize from studentData
    onMount(() => {
        // Play village background music when entering the village
        audioStore.playTrack('village');
        
        const student = $studentData as StudentData | null;
        if (student) {
            gender = student.studentGender === 'Female' ? 'girl' : 'boy';
            const startScene = getStartingScene(student.studentLevel ?? 0);
            currentScene = startScene;
            
            // Load visited scenes from localStorage
            try {
                const saved = localStorage.getItem('villageVisitedScenes');
                if (saved) {
                    sceneVisited = new Set(JSON.parse(saved));
                }
                const savedLevels = localStorage.getItem('villageVisitedLevels');
                if (savedLevels) {
                    levelVisited = new Set(JSON.parse(savedLevels));
                }
            } catch (e) {
                console.warn('Failed to load visited scenes', e);
            }
            
            // If returning from a level, check localStorage
            const savedScene = localStorage.getItem('villageReturnScene');
            if (savedScene !== null) {
                currentScene = parseInt(savedScene);
                // Set flag to prevent auto-open and show dialogue instead
                justReturnedFromPlay = true;
                hasTriggeredInteraction = true; // Mark as triggered to prevent auto-open
                sceneNarrationClosed = true; // Mark as closed since we're not showing narration on return
                
                // Show the dialogue prompt again for the current location
                const scene = scenes[currentScene];
                if (scene.isLevel) {
                    const studentLevel = student?.studentLevel ?? 0;
                    const isLevelCompleted = studentLevel >= scene.level!;
                    
                    // Show prompt after a brief delay
                    setTimeout(() => {
                        if (isLevelCompleted) {
                            dialogueText = `Do you want to visit the ${scene.title}?`;
                            dialogueType = 'interaction';
                            promptLocation = { level: scene.level!, title: scene.title! };
                            showDialogue = true;
                        }
                    }, 500);
                }
                
                // Now remove the flag
                localStorage.removeItem('villageReturnScene');
            } else {
                // First time entering village - show initial scene narration
                setTimeout(() => {
                    showSceneNarration(currentScene);
                }, 500);
            }
        }

        // Start animation loop for walking (slower animation for smoother look)
        animationInterval = setInterval(() => {
            if (isMoving) {
                animationFrame = (animationFrame + 1) % 3;
                lastAnimationFrame = animationFrame;
            }
        }, 200) as unknown as number; // Increased from 150ms to 200ms for slower animation

        // Keyboard controls
        window.addEventListener('keydown', handleKeyDown);
        window.addEventListener('keyup', handleKeyUp);
    });

    onDestroy(() => {
        clearInterval(animationInterval);
        window.removeEventListener('keydown', handleKeyDown);
        window.removeEventListener('keyup', handleKeyUp);
    });

    // Keyboard handling
    let keysPressed = {
        ArrowLeft: false,
        ArrowRight: false
    };

    function handleKeyDown(e: KeyboardEvent) {
        // Handle spacebar to close narration and instruction dialogues (check first before blocking movement)
        if (e.key === ' ' && showDialogue && (dialogueType === 'lock' || dialogueType === 'instruction')) {
            e.preventDefault();
            if (dialogueType === 'instruction') {
                // For instruction, open the level stories
                handleDialogueYes();
            } else {
                handleDialogueClose();
            }
            return;
        }
        
        if (isTransitioning || showDialogue) return; // Don't allow movement when dialogue is open
        
        if (e.key === 'ArrowLeft' || e.key === 'ArrowRight') {
            const newDirection = e.key === 'ArrowLeft' ? 'left' : 'right';
            
            // Check if changing direction (trigger turning animation)
            if (isMoving && direction !== 'idle' && direction !== newDirection) {
                isTurning = true;
                // Brief pause showing front sprite, then change direction
                setTimeout(() => {
                    isTurning = false;
                    direction = newDirection;
                    animationFrame = 0; // Reset animation when changing direction
                }, 200); // Increased from 150ms to 200ms to match slower animation speed
            } else {
                direction = newDirection;
                if (!isMoving) {
                    animationFrame = 0;
                }
            }
            
            keysPressed[e.key as keyof typeof keysPressed] = true;
            isMoving = true;
        }
    }

    function handleKeyUp(e: KeyboardEvent) {
        if (e.key === 'ArrowLeft' || e.key === 'ArrowRight') {
            keysPressed[e.key as keyof typeof keysPressed] = false;
            
            // Check if any movement key is still pressed
            if (!keysPressed.ArrowLeft && !keysPressed.ArrowRight) {
                isMoving = false;
                // Save last direction before going idle (only if it was left or right)
                if (direction === 'left' || direction === 'right') {
                    lastDirection = direction;
                }
                direction = 'idle';
            } else if (keysPressed.ArrowLeft) {
                direction = 'left';
            } else if (keysPressed.ArrowRight) {
                direction = 'right';
            }
        }
    }

    // Movement update loop
    onMount(() => {
        const updateInterval = setInterval(() => {
            if (isMoving && !isTransitioning) {
                const speed = 0.5; // Reduced from 1 to 0.5 for slower, smoother movement
                
                if (direction === 'right') {
                    characterX += speed;
                    
                    // Check for scene transition (right edge)
                    if (characterX >= 95) {
                        checkSceneTransition('right');
                    }
                } else if (direction === 'left') {
                    characterX -= speed;
                    
                    // Check for scene transition (left edge)
                    if (characterX <= 5) {
                        checkSceneTransition('left');
                    }
                }
                
                // Clamp character position
                characterX = Math.max(5, Math.min(95, characterX));
            }
            
            // Continuously check for level interactions when at center
            if (!isTransitioning && characterX >= 45 && characterX <= 55) {
                checkLevelInteraction();
            } else if (characterX < 45 || characterX > 55) {
                // Reset flags when character moves away from center
                hasTriggeredInteraction = false;
                justReturnedFromPlay = false;
            }
        }, 1000 / 60); // 60 FPS

        return () => clearInterval(updateInterval);
    });

    // Scene transition with fade effect
    async function checkSceneTransition(dir: 'left' | 'right') {
        if (isTransitioning) return;

        const nextScene = dir === 'right' ? currentScene + 1 : currentScene - 1;
        
        // Check boundaries
        if (nextScene < 0 || nextScene >= scenes.length) {
            return;
        }

        // Check level locks
        const student = $studentData as StudentData | null;
        const studentLevel = student?.studentLevel ?? 0;
        
        if (dir === 'right') {
            // Block progression if trying to go TO OR PAST a level scene without completing previous level
            
            // Can't reach sarisaristore (scene 2) without completing level 1
            if (nextScene === 2 && studentLevel < 1) {
                characterX = 95;
                showLockDialogue("The path ahead is blocked! You need to complete your journey at the Sari-Sari Store first!");
                return;
            }
            // Can't pass sarisaristore without completing level 1
            if (currentScene === 2 && studentLevel < 1) {
                characterX = 95;
                showLockDialogue("Complete the Sari-Sari Store adventure to continue!");
                return;
            }
            
            // Can't reach wetmarket (scene 4) without completing level 2
            if (nextScene === 4 && studentLevel < 2) {
                characterX = 95;
                showLockDialogue("The path ahead is blocked! You need to complete your journey at the Wet Market first!");
                return;
            }
            // Can't pass wetmarket without completing level 2
            if (currentScene === 4 && studentLevel < 2) {
                characterX = 95;
                showLockDialogue("Complete the Wet Market adventure to continue!");
                return;
            }
            
            // Can't reach plaza (scene 6) without completing level 3
            if (nextScene === 6 && studentLevel < 3) {
                characterX = 95;
                showLockDialogue("The path ahead is blocked! You need to complete your journey at the Plaza first!");
                return;
            }
            // Can't pass plaza without completing level 3
            if (currentScene === 6 && studentLevel < 3) {
                characterX = 95;
                showLockDialogue("Complete the Plaza adventure to continue!");
                return;
            }
        }
        
        // If going LEFT, check if trying to enter a level scene that's locked
        if (dir === 'left') {
            // Can't enter sarisaristore from the right without level 1
            if (nextScene === 2 && studentLevel < 1) {
                characterX = 5;
                showLockDialogue("You need to start your journey from the beginning. Head back to school!");
                return;
            }
            
            // Can't enter wetmarket from the right without level 2
            if (nextScene === 4 && studentLevel < 2) {
                characterX = 5;
                showLockDialogue("You need to complete the Sari-Sari Store adventure first!");
                return;
            }
            
            // Can't enter plaza from the right without level 3
            if (nextScene === 6 && studentLevel < 3) {
                characterX = 5;
                showLockDialogue("You need to complete the Wet Market adventure first!");
                return;
            }
        }

        // Start transition
        isTransitioning = true;
        showFade = true;
        
        // Wait for fade out
        await new Promise(resolve => setTimeout(resolve, 300));
        
        // Change scene
        currentScene = nextScene;
        
        // Respawn character on opposite side
        characterX = dir === 'right' ? 5 : 95;
        
        // Reset interaction flag when changing scenes
        hasTriggeredInteraction = false;
        // Reset prompt dismissal when changing scenes
        promptDismissedForScene = null;
        // Close dialogue box if it's still open
        showDialogue = false;
        showLocationPrompt = false;
        // Reset scene narration flag
        sceneNarrationClosed = false;
        
        // Fade in
        await new Promise(resolve => setTimeout(resolve, 100));
        showFade = false;
        
        await new Promise(resolve => setTimeout(resolve, 300));
        isTransitioning = false;

        // Only show scene narration when going FORWARD (right), not backward
        if (dir === 'right') {
            setTimeout(() => {
                showSceneNarration(currentScene);
            }, 200);
        } else {
            // Going backward - mark as closed so level interaction can work
            sceneNarrationClosed = true;
        }

        // Check for level interaction trigger
        checkLevelInteraction();
    }

    // Check if character is at a level location and should trigger interaction
    function checkLevelInteraction() {
        const scene = scenes[currentScene];
        
        // Only trigger on level scenes when at center
        // Don't trigger if already triggered OR if dismissed for this scene OR if just returned from play
        // IMPORTANT: Also don't trigger if scene narration hasn't been closed yet
        if (scene.isLevel && characterX >= 45 && characterX <= 55 && !hasTriggeredInteraction && 
            promptDismissedForScene !== currentScene && !justReturnedFromPlay && sceneNarrationClosed) {
            hasTriggeredInteraction = true; // Set flag to prevent multiple triggers
            
            const student = $studentData as StudentData | null;
            const studentLevel = student?.studentLevel ?? 0;
            
            // Check if level is completed
            const isLevelCompleted = studentLevel >= scene.level!;
            const isFirstVisitToLevel = !levelVisited.has(scene.level!);
            
            if (isLevelCompleted) {
                // Completed level - Show question dialogue
                showLevelConfirmation(scene.level!, scene.title!);
            } else if (isFirstVisitToLevel) {
                // First time at incomplete level - Auto-open story chooser
                levelVisited.add(scene.level!);
                try {
                    localStorage.setItem('villageVisitedLevels', JSON.stringify(Array.from(levelVisited)));
                } catch (e) {
                    console.warn('Failed to save visited levels', e);
                }
                openLevelStories(scene.level!);
            } else {
                // Returning to incomplete level - Show instruction dialogue
                showLevelInstruction(scene.level!, scene.title!);
            }
        }
    }

    // Get contextual dialogue for each scene
    function getSceneDialogue(sceneIndex: number, isRevisit: boolean, studentLevel: number): string {
        const scene = scenes[sceneIndex];
        
        // Scene-specific dialogues - telling about Readville
        const dialogues: Record<number, { first: string; revisit: string }> = {
            0: { // School
                first: "This is my school in Readville! Classes are done for the day. Time to head home and see what adventures await me on the way!",
                revisit: "Another wonderful day at school! Readville is such a special place to learn and grow."
            },
            1: { // Plain 1
                first: "I love walking through these open fields of Readville. The fresh air and peaceful scenery always make me feel calm. Up ahead is the Sari-Sari Store where many villagers shop!",
                revisit: "The beautiful plains of Readville never get old. This path always leads me to interesting places!"
            },
            2: { // Sari-Sari Store - Level 1
                first: studentLevel >= 1 
                    ? "The Sari-Sari Store is one of Readville's most beloved shops! I learned so much here about honesty and keeping promises - values that everyone in Readville cherishes!" 
                    : "Here's the Sari-Sari Store, a cornerstone of our community in Readville! Mrs. Lena runs this shop and knows everyone by name. This is where I'll learn about honesty and trust!",
                revisit: studentLevel >= 1
                    ? "The Sari-Sari Store - a place where Readville's values of honesty shine bright! The lessons I learned here will stay with me forever."
                    : "The Sari-Sari Store awaits! I should complete the stories here to continue exploring Readville."
            },
            3: { // Houses 1
                first: "These cozy homes belong to some of Readville's kindest families. Everyone here looks out for one another - that's what makes our village so special!",
                revisit: "Passing by these familiar homes in Readville. I love how everyone here feels like family!"
            },
            4: { // Wet Market - Level 2
                first: studentLevel >= 2
                    ? "The Wet Market is where Readville comes alive! I discovered the importance of health and helping others here. Our community really cares about each other's wellbeing!" 
                    : "The bustling Wet Market of Readville! This is where villagers buy fresh food and catch up on news. There are important lessons about health and kindness waiting for me here!",
                revisit: studentLevel >= 2
                    ? "The Wet Market - the heart of healthy living in Readville! The lessons about caring for ourselves and others were truly valuable."
                    : "The Wet Market has more to teach me. I need to complete these stories to continue my journey through Readville."
            },
            5: { // Houses 2
                first: "More wonderful homes in Readville! I'm getting closer to my own house now. Each building here holds stories of kindness and community.",
                revisit: "These friendly houses remind me why Readville is such a wonderful place to call home!"
            },
            6: { // Plaza - Level 3
                first: studentLevel >= 3
                    ? "The Readville Plaza! The center of our village where everyone gathers. Here I learned about making good choices and the importance of family - the foundation of our community!" 
                    : "The grand Plaza of Readville! This is where our village celebrates festivals and important events. The final lessons about choices and family await me here!",
                revisit: studentLevel >= 3
                    ? "The Plaza - Readville's pride and joy! Completing my journey here taught me values I'll carry for life."
                    : "The Plaza holds the final lessons. I should complete these stories to finish my journey through Readville."
            },
            7: { // Plain 2
                first: studentLevel >= 3
                    ? "The peaceful fields again! Just a little further and I'll be home. What a wonderful journey through Readville this has been!"
                    : "These plains would lead me home, but I haven't finished my journey through Readville yet. I should complete my lessons at the Plaza first!",
                revisit: studentLevel >= 3
                    ? "Almost home now! Walking through Readville has taught me so much today. I can't wait to tell my family about everything I learned!"
                    : "I should head back to the Plaza and complete my lessons before going home."
            },
            8: { // Home
                first: "Home sweet home in beautiful Readville! My journey through the village has taught me about honesty, health, kindness, and making good choices. I'm so grateful to live in such a wonderful community!",
                revisit: "There's no place like home in Readville! Every journey through our village reminds me how lucky I am to live here, surrounded by caring neighbors and valuable lessons."
            }
        };
        
        const dialogue = dialogues[sceneIndex];
        if (!dialogue) return '';
        
        return isRevisit ? dialogue.revisit : dialogue.first;
    }

    function showLevelConfirmation(level: number, title: string) {
        promptLocation = { level, title };
        dialogueText = `Do you want to visit the ${title}?`;
        dialogueType = 'interaction';
        showDialogue = true;
    }

    function showLevelInstruction(level: number, title: string) {
        promptLocation = { level, title };
        const instructions: Record<number, string> = {
            1: `The ${title} is calling! Let me continue my adventure here and learn valuable lessons about honesty and trust.`,
            2: `Time to explore the ${title}! There are important lessons about health and helping others waiting for me.`,
            3: `The ${title} awaits! I need to complete this final journey to learn about making good choices and family values.`
        };
        dialogueText = instructions[level] || `I should visit the ${title} to continue my journey through Readville!`;
        dialogueType = 'instruction';
        showDialogue = true;
    }

    function showSceneNarration(sceneIndex: number) {
        const student = $studentData as StudentData | null;
        const studentLevel = student?.studentLevel ?? 0;
        const isRevisit = sceneVisited.has(sceneIndex);
        
        // Check if this is the very first scene ever visited
        isFirstScene = sceneVisited.size === 0 && sceneIndex === 0;
        
        dialogueText = getSceneDialogue(sceneIndex, isRevisit, studentLevel);
        dialogueType = 'lock'; // Use 'lock' type for narration
        showDialogue = true;
        
        // Mark scene as visited
        sceneVisited.add(sceneIndex);
        
        // Save to localStorage
        try {
            localStorage.setItem('villageVisitedScenes', JSON.stringify(Array.from(sceneVisited)));
        } catch (e) {
            console.warn('Failed to save visited scenes', e);
        }
        
        // Remove auto-close functionality
        // Dialogues will only close when user presses X or spacebar
    }
    
    function closeDialogueWithAnimation() {
        showDialogue = false;
        isFirstScene = false;
        // When dialogue is closed, mark scene narration as closed (allows level interaction)
        if (dialogueType === 'lock') {
            sceneNarrationClosed = true;
        }
        if (autoCloseTimer) {
            clearTimeout(autoCloseTimer);
            autoCloseTimer = null;
        }
    }

    function showLockDialogue(message: string) {
        dialogueText = message;
        dialogueType = 'lock';
        showDialogue = true;
    }

    function handleDialogueYes() {
        closeDialogueWithAnimation();
        justReturnedFromPlay = false; // Reset flag
        if (dialogueType === 'interaction' || dialogueType === 'instruction') {
            openLevelStories(promptLocation.level);
        }
    }

    function handleDialogueNo() {
        closeDialogueWithAnimation();
        justReturnedFromPlay = false; // Reset flag
        if (dialogueType === 'interaction') {
            promptDismissedForScene = currentScene; // Mark this scene as dismissed
            hasTriggeredInteraction = false; // Reset flag so we can check again later
        }
    }

    function handleDialogueClose() {
        closeDialogueWithAnimation();
        justReturnedFromPlay = false; // Reset flag
        if (dialogueType === 'lock') {
            // For narration dialogue, just close
        } else if (dialogueType === 'interaction') {
            promptDismissedForScene = currentScene;
            hasTriggeredInteraction = false;
        } else if (dialogueType === 'instruction') {
            // For instruction, treat like "No" - dismiss but allow re-trigger
            promptDismissedForScene = currentScene;
            hasTriggeredInteraction = false;
        }
    }

    function handlePromptYes() {
        showLocationPrompt = false;
        openLevelStories(promptLocation.level);
    }

    function handlePromptNo() {
        showLocationPrompt = false;
        promptDismissedForScene = currentScene; // Mark this scene as dismissed
        hasTriggeredInteraction = false; // Reset flag so we can check again later
    }

    function openLevelStories(level: number) {
        // Save current scene for return journey
        localStorage.setItem('villageReturnScene', currentScene.toString());
        
        // Navigate to play page with level selection
        goto(`/student/play?level=${level}`);
    }

    // Get current sprite path
    $: currentSprite = (() => {
        const basePath = `/src/assets/Level_Walkthrough/shenievia/${gender}`;
        
        // Show turning sprite when changing direction
        if (isTurning) {
            return `${basePath}/front/1.png`;
        }
        
        if (direction === 'idle') {
            // Keep showing last frame when idle
            if (lastDirection === 'right') {
                return `${basePath}/forward/${lastAnimationFrame + 1}.png`;
            } else if (lastDirection === 'left') {
                return `${basePath}/back/${lastAnimationFrame + 1}.png`;
            } else {
                // Default to front if no previous direction
                return `${basePath}/front/1.png`;
            }
        } else if (direction === 'right') {
            // Moving right: cycle through forward sprites only
            return `${basePath}/forward/${animationFrame + 1}.png`;
        } else {
            // Moving left: cycle through back sprites only
            return `${basePath}/back/${animationFrame + 1}.png`;
        }
    })();

    // Check if we're at a locked level location and should show dialogue
    $: {
        const student = $studentData as StudentData | null;
        const studentLevel = student?.studentLevel ?? 0;
        
        // Only show lock dialogue when at center of a locked level location
        if (!isTransitioning && !showDialogue && characterX >= 45 && characterX <= 55) {
            if (currentScene === 2 && studentLevel < 1) {
                // Don't auto-show, this will be handled by checkLevelInteraction
            } else if (currentScene === 4 && studentLevel < 2) {
                // Don't auto-show, this will be handled by checkLevelInteraction
            } else if (currentScene === 6 && studentLevel < 3) {
                // Don't auto-show, this will be handled by checkLevelInteraction
            }
        }
    }
</script>

<main class="village-container">
    <!-- Background scene -->
    <div class="scene-background" style="background-image: url({scenes[currentScene].path})"></div>

    <!-- White fade overlay -->
    {#if showFade}
        <div class="fade-overlay"></div>
    {/if}

    <!-- Character sprite -->
    <div class="character" style="left: {characterX}%">
        <img src={currentSprite} alt="Character" />
    </div>

    <!-- Scene indicator (for debugging) -->
    <div class="scene-indicator">
        Scene: {currentScene + 1}/{scenes.length} - {scenes[currentScene].name}
    </div>

    <!-- Controls hint -->
    <div class="controls-hint">
        ← → Arrow keys to move
    </div>

    <!-- Exit button -->
    <button class="exit-btn" on:click={() => {
        // Switch back to default music when exiting village
        audioStore.playTrack('default');
        goto('/student/dashboard');
    }}>
        Exit Village
    </button>

    <!-- Novel-style dialogue box -->
    {#if showDialogue}
        <div class="dialogue-overlay" on:click={handleDialogueClose} on:keydown={(e) => e.key === 'Escape' && handleDialogueClose()} role="button" tabindex="0" aria-label="Close dialogue"></div>
        <div class="dialogue-box">
            <!-- Character portrait -->
            <div class="dialogue-portrait">
                <img src="/src/assets/Level_Walkthrough/shenievia/{gender}/front/1.png" alt="Character" />
            </div>
            
            <!-- Dialogue content -->
            <div class="dialogue-content">
                <!-- Name tag -->
                <div class="dialogue-name">Shenievia</div>
                
                <!-- Dialogue text -->
                <div class="dialogue-text">
                    {dialogueText}
                    {#if dialogueType === 'lock' && isFirstScene}
                        <div class="spacebar-hint">(Press SPACE or click X to continue)</div>
                    {/if}
                </div>
                
                <!-- Action buttons (only for interaction type) -->
                {#if dialogueType === 'interaction'}
                    <div class="dialogue-actions">
                        <button class="dialogue-btn yes" on:click={handleDialogueYes}>
                            <span class="btn-icon">✓</span> Yes
                        </button>
                        <button class="dialogue-btn no" on:click={handleDialogueNo}>
                            <span class="btn-icon">✕</span> Not now
                        </button>
                    </div>
                {/if}
            </div>
            
            <!-- X button for closing (always visible) -->
            <button class="dialogue-close-btn" on:click={handleDialogueClose} aria-label="Close">
                ✕
            </button>
        </div>
    {/if}
</main>

<style>
    .village-container {
        position: relative;
        width: 100vw;
        height: 100vh;
        overflow: hidden;
        background-color: #000;
    }

    .scene-background {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-size: cover;
        background-position: center;
        background-repeat: no-repeat;
    }

    .fade-overlay {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: white;
        animation: fadeInOut 0.6s ease-in-out;
        pointer-events: none;
        z-index: 10;
    }

    @keyframes fadeInOut {
        0% { opacity: 0; }
        50% { opacity: 1; }
        100% { opacity: 0; }
    }

    .character {
        position: absolute;
        bottom: 20%;
        transform: translateX(-50%);
        z-index: 5;
        transition: left 0.016s linear; /* Smoother transition - 1 frame at 60fps */
    }

    .character img {
        height: 150px;
        width: auto;
        image-rendering: pixelated;
        filter: drop-shadow(2px 2px 4px rgba(0, 0, 0, 0.3));
        transition: transform 0.15s ease-out; /* Smooth transform transitions */
    }

    .lock-message {
        position: absolute;
        top: 20%;
        left: 50%;
        transform: translateX(-50%);
        background-color: rgba(255, 255, 255, 0.95);
        padding: 20px 40px;
        border-radius: 15px;
        font-size: 1.5rem;
        font-weight: bold;
        color: #d32f2f;
        text-align: center;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
        z-index: 20;
        animation: pulse 2s infinite;
    }

    @keyframes pulse {
        0%, 100% { transform: translateX(-50%) scale(1); }
        50% { transform: translateX(-50%) scale(1.05); }
    }

    .scene-indicator {
        position: absolute;
        top: 20px;
        left: 20px;
        background-color: rgba(0, 0, 0, 0.7);
        color: white;
        padding: 10px 20px;
        border-radius: 10px;
        font-size: 1rem;
        z-index: 30;
    }

    .controls-hint {
        position: absolute;
        bottom: 20px;
        left: 50%;
        transform: translateX(-50%);
        background-color: rgba(0, 0, 0, 0.7);
        color: white;
        padding: 10px 20px;
        border-radius: 10px;
        font-size: 1.2rem;
        z-index: 30;
    }

    .exit-btn {
        position: absolute;
        top: 20px;
        right: 20px;
        background-color: rgba(239, 68, 68, 0.9);
        color: white;
        padding: 12px 24px;
        border-radius: 10px;
        font-size: 1.1rem;
        font-weight: bold;
        border: 2px solid white;
        cursor: pointer;
        z-index: 30;
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
    }

    .exit-btn:hover {
        background-color: rgba(220, 38, 38, 1);
        transform: scale(1.05);
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.4);
    }

    .exit-btn:active {
        transform: scale(0.95);
    }

    /* Novel-style dialogue box */
    .dialogue-overlay {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.15);
        z-index: 45;
        animation: fadeIn 0.3s ease-out;
    }

    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }

    .dialogue-box {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        background: linear-gradient(180deg, rgba(20, 20, 40, 0.7) 0%, rgba(10, 10, 30, 0.7) 100%);
        border-top: 4px solid #fbbf24;
        padding: 20px;
        padding-right: 120px; /* Add extra padding on right to avoid audio button */
        z-index: 50;
        display: flex;
        gap: 20px;
        min-height: 200px;
        animation: slideUp 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
        box-shadow: 0 -10px 40px rgba(0, 0, 0, 0.5);
    }

    @keyframes slideUp {
        from {
            transform: translateY(100%);
            opacity: 0;
        }
        to {
            transform: translateY(0);
            opacity: 1;
        }
    }

    .dialogue-portrait {
        flex-shrink: 0;
        width: 120px;
        height: 120px;
        border: 3px solid #fbbf24;
        border-radius: 10px;
        overflow: hidden;
        background-color: rgba(255, 255, 255, 0.1);
        box-shadow: 0 0 20px rgba(251, 191, 36, 0.3);
    }

    .dialogue-portrait img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        image-rendering: pixelated;
    }

    .dialogue-content {
        flex: 1;
        display: flex;
        flex-direction: column;
        gap: 12px;
        padding: 10px 20px;
    }

    .dialogue-name {
        font-size: 1.4rem;
        font-weight: bold;
        color: #fbbf24;
        text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
        letter-spacing: 1px;
    }

    .dialogue-text {
        flex: 1;
        font-size: 1.3rem;
        line-height: 1.6;
        color: #ffffff;
        text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.8);
        padding: 10px 0;
        animation: textFadeIn 0.5s ease-out 0.2s both;
    }

    .spacebar-hint {
        margin-top: 15px;
        font-size: 0.85rem;
        color: #fbbf24;
        font-style: italic;
        opacity: 0.7;
    }

    @keyframes textFadeIn {
        from {
            opacity: 0;
            transform: translateY(10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .dialogue-actions {
        display: flex;
        gap: 15px;
        margin-top: 10px;
        animation: buttonsFadeIn 0.5s ease-out 0.4s both;
    }

    @keyframes buttonsFadeIn {
        from {
            opacity: 0;
            transform: translateY(10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .dialogue-btn {
        padding: 12px 30px;
        border: 3px solid transparent;
        border-radius: 8px;
        font-size: 1.2rem;
        font-weight: bold;
        cursor: pointer;
        transition: all 0.2s ease;
        display: flex;
        align-items: center;
        gap: 8px;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3);
    }

    .dialogue-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 15px rgba(0, 0, 0, 0.4);
    }

    .dialogue-btn:active {
        transform: translateY(0);
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.3);
    }

    .dialogue-btn.yes {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: white;
        border-color: #d1fae5;
    }

    .dialogue-btn.no {
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        color: white;
        border-color: #fecaca;
    }

    .dialogue-btn.ok {
        background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        color: white;
        border-color: #bfdbfe;
    }

    .btn-icon {
        font-size: 1.4rem;
        font-weight: bold;
    }

    /* X button for closing dialogue */
    .dialogue-close-btn {
        position: absolute;
        top: 15px;
        right: 15px;
        background: rgba(239, 68, 68, 0.9);
        color: white;
        border: 2px solid rgba(255, 255, 255, 0.3);
        border-radius: 50%;
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        font-weight: bold;
        cursor: pointer;
        transition: all 0.2s ease;
        z-index: 51;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
    }

    .dialogue-close-btn:hover {
        background: rgba(220, 38, 38, 1);
        transform: scale(1.1);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.4);
    }

    .dialogue-close-btn:active {
        transform: scale(0.95);
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
        .character img {
            height: 100px;
        }

        .dialogue-box {
            flex-direction: column;
            min-height: auto;
            padding: 15px;
            padding-right: 90px; /* Less padding on mobile since button is smaller */
        }

        .dialogue-portrait {
            width: 80px;
            height: 80px;
        }

        .dialogue-name {
            font-size: 1.2rem;
        }

        .dialogue-text {
            font-size: 1.1rem;
        }

        .dialogue-btn {
            padding: 10px 20px;
            font-size: 1rem;
        }

        .lock-message {
            font-size: 1.2rem;
            padding: 15px 30px;
        }

        .scene-indicator, .controls-hint {
            font-size: 0.9rem;
            padding: 8px 15px;
        }

        .exit-btn {
            padding: 10px 20px;
            font-size: 1rem;
        }
    }

    @media (max-width: 480px) {
        .character img {
            height: 80px;
        }

        .lock-message {
            font-size: 1rem;
            padding: 12px 25px;
        }

        .exit-btn {
            padding: 8px 16px;
            font-size: 0.9rem;
        }

        .bubble-text strong {
            font-size: 1.1rem;
        }

        .bubble-btn {
            padding: 8px 16px;
            font-size: 0.9rem;
        }
    }
</style>
