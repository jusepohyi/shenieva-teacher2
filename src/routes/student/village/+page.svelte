<script lang="ts">
    import { onMount, onDestroy } from 'svelte';
    import { goto } from '$app/navigation';
    import { studentData } from '$lib/store/student_data';
    import { audioStore } from '$lib/store/audio_store';
    import type { StudentData } from '$lib/store/student_data';
    import GiftShop from './GiftShop.svelte';

    // Scene configuration
    const scenes = [
        { name: 'Readville Village School', path: '/src/assets/Level_Walkthrough/places/school.webp', isLevel: false },
        { name: 'Plains', path: '/src/assets/Level_Walkthrough/places/plain.webp', isLevel: false },
        { name: 'Sari-sari Store', path: '/src/assets/Level_Walkthrough/places/sarisaristore.webp', isLevel: true, level: 1, title: 'Sari-Sari Store' },
        { name: 'Readville Village', path: '/src/assets/Level_Walkthrough/places/houses1.webp', isLevel: false },
        { name: 'Wet Market', path: '/src/assets/Level_Walkthrough/places/wetmarket.webp', isLevel: true, level: 2, title: 'Wet Market' },
        { name: 'Readville Village', path: '/src/assets/Level_Walkthrough/places/houses2.webp', isLevel: false },
        { name: 'Plaza', path: '/src/assets/Level_Walkthrough/places/plaza.webp', isLevel: true, level: 3, title: 'Plaza' },
        { name: 'Plains', path: '/src/assets/Level_Walkthrough/places/plain.webp', isLevel: false },
        { name: "Shenievia's Home", path: '/src/assets/Level_Walkthrough/places/home.webp', isLevel: false, canEnter: true },
        { name: "Shenievia's Home", path: '/src/assets/Level_Walkthrough/places/home-inside.png', isLevel: false, isInterior: true }
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
    let showFrontTransition = false; // Show front sprite on first press when changing direction
    let pendingDirection: 'left' | 'right' | null = null; // Store the new direction
    let frontTransitionCount = 0; // Counter for front transition frames
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
    let showEntranceFade = true; // White fade transition when entering village
    
    // Gift box interaction in home
    let showGiftBoxPrompt = false; // Show gift box purchase prompt
    let hasTriggeredGiftBox = false; // Prevent multiple gift box triggers
    let showGiftShop = false; // Show gift shop modal
    let currentGifts: { giftID: number; gift: string }[] = []; // Store all gifts
    let showGiftDisplay = false; // Show gift display modal
    
    // Loading screen state
    let showLoading = true; // Show loading screen
    let loadingProgress = 0; // Loading progress (0-100)
    let loadingText = 'Loading Readville Village...'; // Loading text

    // Character sprite configuration
    let gender: 'boy' | 'girl' = 'boy';
    let animationInterval: number;

    // Movement tuning (easy to tweak)
    let movementSpeed: number = 0.25; // how much the target moves per frame (smaller = slower)
    let movementLerp: number = 0.18; // interpolation factor toward target (0-1) (smaller = smoother)
    let targetX: number = characterX; // desired position we smoothly move toward
    
    // Pronoun helpers based on gender
    $: pronouns = {
        subject: gender === 'boy' ? 'he' : 'she',
        object: gender === 'boy' ? 'him' : 'her',
        possessive: gender === 'boy' ? 'his' : 'her',
        reflexive: gender === 'boy' ? 'himself' : 'herself',
        Subject: gender === 'boy' ? 'He' : 'She',
        Object: gender === 'boy' ? 'Him' : 'Her',
        Possessive: gender === 'boy' ? 'His' : 'Her'
    };

    // Always start at school - students walk through the village naturally
    function getStartingScene(studentLevel: number): number {
        return 0; // Always start at School
    }

    // Initialize from studentData
    onMount(() => {
        // Stop all audio first, then restart default BGM during loading screen
        audioStore.stopAll();
        setTimeout(() => {
            audioStore.playTrack('default');
        }, 100);
        
        // Simulate loading progress - at least 4 seconds
        const loadingSteps = [
            { progress: 15, text: 'Loading Readville Village...', delay: 0 },
            { progress: 30, text: 'Preparing scenes...', delay: 800 },
            { progress: 50, text: 'Loading characters...', delay: 800 },
            { progress: 70, text: 'Setting up environment...', delay: 800 },
            { progress: 90, text: 'Almost ready...', delay: 800 },
            { progress: 100, text: 'Ready!', delay: 800 }
        ];
        
        let currentStep = 0;
        
        const progressInterval = setInterval(() => {
            if (currentStep < loadingSteps.length) {
                const step = loadingSteps[currentStep];
                loadingProgress = step.progress;
                loadingText = step.text;
                currentStep++;
            } else {
                clearInterval(progressInterval);
                // Hide loading screen and show entrance fade (total: ~4 seconds)
                setTimeout(() => {
                    showLoading = false;
                    // Play village background music after loading screen
                    audioStore.playTrack('village');
                    // Fade out the white entrance transition
                    setTimeout(() => {
                        showEntranceFade = false;
                    }, 500);
                }, 500);
            }
        }, 800);
        
        const student = $studentData as StudentData | null;
        if (student) {
            gender = student.studentGender === 'Female' ? 'girl' : 'boy';
            
            // Check if coming from dashboard with direct home access
            const goDirectToHome = localStorage.getItem('goDirectlyToHome');
            if (goDirectToHome === 'true') {
                localStorage.removeItem('goDirectlyToHome');
                // Go directly to home interior scene
                currentScene = 9; // Home interior
                characterX = 50;
                sceneNarrationClosed = false;
                setTimeout(() => {
                    showSceneNarration(currentScene);
                }, 500);
            } else {
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
                    // Refresh student data from localStorage to get updated level
                    const freshStudentData = localStorage.getItem('studentData');
                    if (freshStudentData) {
                        const parsedData = JSON.parse(freshStudentData);
                        studentData.set(parsedData);
                        console.log('Refreshed student data after returning from level. Current level:', parsedData.studentLevel);
                    }
                    
                    currentScene = parseInt(savedScene);
                    localStorage.removeItem('villageReturnScene');
                    // Spawn at center when returning from a level
                    characterX = 50;
                    // Mark scene narration as closed initially
                    sceneNarrationClosed = true;
                } else {
                    // First time entering village - always start at school
                    // Spawn slightly right of center so student walks forward
                    characterX = 60;
                    // Show school scene narration
                    sceneNarrationClosed = false;
                    setTimeout(() => {
                        showSceneNarration(currentScene);
                    }, 500);
                }
                
                // Trigger level interaction after a brief delay (for returning from levels)
                setTimeout(() => {
                    checkLevelInteraction();
                    checkHomeEntry();
                }, 200);
            }
        }

        // Start animation loop for walking (slower animation for smoother look)
        animationInterval = setInterval(() => {
            if (isMoving && !showFrontTransition) {
                // Normal animation cycling
                animationFrame = (animationFrame + 1) % 3;
                lastAnimationFrame = animationFrame;
            }
        }, 260) as unknown as number; // Slightly slower frame timing for a calmer walk cycle

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
            
            // If in front transition mode and pressing the same new direction, complete the transition
            if (showFrontTransition && pendingDirection === newDirection) {
                showFrontTransition = false;
                direction = pendingDirection;
                pendingDirection = null;
                animationFrame = 0;
            }
            // Check if changing direction (idle to new direction or opposite direction)
            else if ((direction === 'idle' && lastDirection && lastDirection !== newDirection) ||
                     (direction !== 'idle' && direction !== newDirection)) {
                // First press: Show front sprite
                showFrontTransition = true;
                pendingDirection = newDirection;
                direction = 'idle'; // Temporarily idle to stop movement
                animationFrame = 0;
            } else {
                // Same direction or starting fresh
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
                // If we were in front transition, save the pending direction
                if (showFrontTransition && pendingDirection) {
                    lastDirection = pendingDirection;
                    showFrontTransition = false;
                    pendingDirection = null;
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
            // Update target position when player is actively moving
            if (isMoving && !isTransitioning && !showFrontTransition) {
                if (direction === 'right') {
                    targetX = Math.min(95, targetX + movementSpeed);
                    if (targetX >= 95) {
                        checkSceneTransition('right');
                    }
                } else if (direction === 'left') {
                    targetX = Math.max(5, targetX - movementSpeed);
                    if (targetX <= 5) {
                        checkSceneTransition('left');
                    }
                }
            } else {
                // If not moving (or blocked), keep the target anchored to current position
                targetX = characterX;
            }

            // Smoothly interpolate current position toward target
            characterX = characterX + (targetX - characterX) * movementLerp;

            // Clamp character position
            characterX = Math.max(5, Math.min(95, characterX));

            // Continuously check for level interactions and home entry when at center
            if (!isTransitioning && characterX >= 45 && characterX <= 55) {
                checkLevelInteraction();
                checkHomeEntry();
            } else if (characterX < 45 || characterX > 55) {
                // Reset flags when character moves away from center
                hasTriggeredInteraction = false;
                justReturnedFromPlay = false;
            }

            // Check for gift box interaction when in home interior
            if (scenes[currentScene].isInterior) {
                checkGiftBoxInteraction();
            }
        }, 1000 / 60); // 60 FPS

        return () => clearInterval(updateInterval);
    });

    // Scene transition with fade effect
    async function checkSceneTransition(dir: 'left' | 'right') {
        if (isTransitioning) return;
        
        // If in home interior (scene 9), block scene transitions
        const scene = scenes[currentScene];
        if (scene.isInterior) {
            return; // Can't transition out of interior, must use exit button
        }

        const nextScene = dir === 'right' ? currentScene + 1 : currentScene - 1;
        
        // Check boundaries
        if (nextScene < 0 || nextScene >= scenes.length) {
            return;
        }

        // Check level locks
        const student = $studentData as StudentData | null;
        const studentLevel = student?.studentLevel ?? 0;
        
        if (dir === 'right') {
            // Block progression if trying to go past a level scene without completing it
            
            // Can't pass sarisaristore (scene 2) without completing it (need level 1)
            if (currentScene === 2 && nextScene === 3 && studentLevel < 1) {
                characterX = 95;
                showLockDialogue("Complete the Sari-Sari Store adventure to continue!");
                return;
            }
            
            // Can't pass wetmarket (scene 4) without completing it (need level 2)
            if (currentScene === 4 && nextScene === 5 && studentLevel < 2) {
                characterX = 95;
                showLockDialogue("Complete the Wet Market adventure to continue!");
                return;
            }
            
            // Can't pass plaza (scene 6) without completing it (need level 3)
            if (currentScene === 6 && nextScene === 7 && studentLevel < 3) {
                characterX = 95;
                showLockDialogue("Complete the Plaza adventure to continue!");
                return;
            }
        }
        
        // If going LEFT, check if trying to go back past a level scene without completing it
        if (dir === 'left') {
            // Can't go back past plaza (from scene 7 to 6) without completing it
            if (currentScene === 7 && nextScene === 6 && studentLevel < 3) {
                characterX = 5;
                showLockDialogue("You need to complete the Plaza adventure first!");
                return;
            }
            
            // Can't go back past wetmarket (from scene 5 to 4) without completing it
            if (currentScene === 5 && nextScene === 4 && studentLevel < 2) {
                characterX = 5;
                showLockDialogue("You need to complete the Wet Market adventure first!");
                return;
            }
            
            // Can't go back past sarisaristore (from scene 3 to 2) without completing it
            if (currentScene === 3 && nextScene === 2 && studentLevel < 1) {
                characterX = 5;
                showLockDialogue("You need to complete the Sari-Sari Store adventure first!");
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

        // Only show scene narration when going FORWARD (right), not backward (left)
        if (dir === 'right') {
            setTimeout(() => {
                showSceneNarration(currentScene);
            }, 200);
        } else {
            // Going backward - mark narration as closed so level interactions can work
            sceneNarrationClosed = true;
        }

        // Check for level interaction trigger after narration
        checkLevelInteraction();
    }

    // Check if character is at a level location and should trigger interaction
    function checkLevelInteraction() {
        const scene = scenes[currentScene];
        
        console.log('checkLevelInteraction called:', {
            isLevelScene: scene.isLevel,
            characterX,
            atCenter: characterX >= 45 && characterX <= 55,
            showDialogue,
            isTransitioning,
            hasTriggeredInteraction,
            sceneNarrationClosed
        });
        
        // Skip if not a level scene or not at center
        if (!scene.isLevel || characterX < 45 || characterX > 55) {
            return;
        }
        
        // Skip if dialogue is already showing or we're transitioning
        if (showDialogue || isTransitioning) {
            return;
        }
        
        // Skip if we already triggered for this center visit
        if (hasTriggeredInteraction) {
            return;
        }
        
        // Skip if scene narration hasn't been shown/closed yet
        if (!sceneNarrationClosed) {
            return;
        }
        
        // Mark as triggered for this center visit
        hasTriggeredInteraction = true;
        
        const student = $studentData as StudentData | null;
        const studentLevel = student?.studentLevel ?? 0;
        
        // Determine what dialogue to show based on student's progress
        const sceneLevel = scene.level!;
        const hasCompletedLevel = studentLevel >= sceneLevel;
        // For Level 1, allow if studentLevel is 0 OR if they haven't completed it yet
        // For other levels, check if they just completed the previous level
        const canAttemptLevel = sceneLevel === 1 
            ? studentLevel < sceneLevel  // Level 1: can attempt if not completed yet
            : studentLevel === (sceneLevel - 1);  // Other levels: must have completed previous
        const isFirstVisit = !levelVisited.has(sceneLevel);
        
        console.log('Center trigger:', {
            scene: scene.name,
            sceneLevel,
            studentLevel,
            hasCompletedLevel,
            canAttemptLevel,
            isFirstVisit
        });
        
        if (hasCompletedLevel) {
            // Already completed - ask if they want to revisit
            console.log('Showing revisit confirmation (completed)');
            showLevelConfirmation(sceneLevel, scene.title!);
        } else if (canAttemptLevel && isFirstVisit) {
            // First visit and can attempt - auto-open level chooser
            console.log('First visit - auto opening level');
            levelVisited.add(sceneLevel);
            try {
                localStorage.setItem('villageVisitedLevels', JSON.stringify(Array.from(levelVisited)));
            } catch (e) {
                console.warn('Failed to save visited levels', e);
            }
            openLevelStories(sceneLevel);
        } else if (canAttemptLevel) {
            // Return visit and can attempt - ask if they want to enter
            console.log('Return visit - showing confirmation');
            showLevelConfirmation(sceneLevel, scene.title!);
        } else {
            // Not at correct level yet, but still show the prompt so they know about it
            console.log('Wrong level - showing info prompt');
            showLevelConfirmation(sceneLevel, scene.title!);
        }
    }

    // Check if player is at home and can enter
    function checkHomeEntry() {
        const scene = scenes[currentScene];
        
        console.log('checkHomeEntry called:', {
            sceneIndex: currentScene,
            canEnter: scene.canEnter,
            characterX,
            atCenter: characterX >= 45 && characterX <= 55,
            showDialogue,
            isTransitioning,
            hasTriggeredInteraction,
            sceneNarrationClosed
        });
        
        // Skip if not at home exterior or not at center
        if (!scene.canEnter || characterX < 45 || characterX > 55) {
            return;
        }
        
        // Skip if dialogue is already showing or we're transitioning
        if (showDialogue || isTransitioning) {
            return;
        }
        
        // Skip if we already triggered for this center visit
        if (hasTriggeredInteraction) {
            return;
        }
        
        // Skip if scene narration hasn't been shown/closed yet
        if (!sceneNarrationClosed) {
            return;
        }
        
        // Mark as triggered for this center visit
        hasTriggeredInteraction = true;
        
        console.log('Showing home entry prompt');
        
        // Show prompt to enter home
        dialogueText = "Do you want to enter your home?";
        dialogueType = 'interaction';
        showDialogue = true;
    }

    // Check for gift box interaction when in home interior
    async function checkGiftBoxInteraction() {
        const scene = scenes[currentScene];
        
        // Only check if we're in the home interior (scene 9)
        if (!scene.isInterior) {
            return;
        }
        
        // Check if character is on the right side of the screen (near gift box)
        if (characterX < 70 || characterX > 85) {
            // Reset flag when character moves away
            hasTriggeredGiftBox = false;
            return;
        }
        
        // Skip if already triggered or if prompt/display is already showing
        if (hasTriggeredGiftBox || showGiftBoxPrompt || showGiftDisplay) {
            return;
        }
        
        // Mark as triggered
        hasTriggeredGiftBox = true;
        
        const student = $studentData;
        if (!student) {
            console.error('Student data not found');
            return;
        }
        
        try {
            // Check if student already has a gift
            const response = await fetch(`http://localhost/shenieva-teacher/src/lib/api/check_student_gift.php?studentID=${student.pk_studentID}`);
            const result = await response.json();
            
            if (result.hasGift) {
                // Student has gifts - show gift display
                currentGifts = result.gifts;
                showGiftDisplay = true;
            } else {
                // No gift yet - show prompt to buy
                showGiftBoxPrompt = true;
            }
        } catch (error) {
            console.error('Error checking gift:', error);
        }
    }

    // Handle gift box click
    async function handleGiftBoxClick() {
        const scene = scenes[currentScene];
        if (!scene.isInterior) return;
        
        const student = $studentData;
        if (!student) {
            alert('Error: Student data not found');
            return;
        }
        
        try {
            // Check if student already has a gift
            const response = await fetch(`http://localhost/shenieva-teacher/src/lib/api/check_student_gift.php?studentID=${student.pk_studentID}`);
            const result = await response.json();
            
            if (result.hasGift) {
                // Student has gifts - show gift display
                currentGifts = result.gifts;
                showGiftDisplay = true;
            } else {
                // No gift yet - show prompt to buy
                showGiftBoxPrompt = true;
            }
        } catch (error) {
            console.error('Error checking gift:', error);
            alert('Failed to check gift status. Please try again.');
        }
    }

    // Handle gift box purchase decision
    function handleGiftBoxYes() {
        showGiftBoxPrompt = false;
        // Open gift shop
        showGiftShop = true;
    }

    function handleGiftBoxNo() {
        showGiftBoxPrompt = false;
        // Don't reset hasTriggeredGiftBox here - it will reset when character moves away
    }
    
    // Handle opening gift shop from gift display
    function handleBuyAnotherGift() {
        showGiftDisplay = false;
        showGiftShop = true;
    }
    
    // Handle closing gift display
    function handleCloseGiftDisplay() {
        showGiftDisplay = false;
    }
    
    // Handle gift shop close
    function handleGiftShopClose(event: CustomEvent) {
        showGiftShop = false;
        
        // If a gift was purchased, show success message
        if (event.detail?.purchased) {
            // Array of different thankful dialogues emphasizing school supplies usefulness
            const thankfulDialogues = [
                "Oh my! Thank you so much! 🥰 This school supply will help me so much with my studies! I can already imagine using it in class and doing my homework better. You're so thoughtful and kind! Your generosity makes my heart so happy! ��💝",
                "Wow! I can't believe you got this for me! 😊 This is exactly what I need for school! Now I can be more organized and do better in my classes. You're the best! Thank you from the bottom of my heart! �✨",
                "Amazing! This will make studying so much easier! 🤩 I've been needing this for my schoolwork! Now I can complete my assignments properly and learn better. You always know how to help me! Thank you so very much! �💕",
                "This is incredible! Thank you, thank you! 😍 This school supply is going to be so useful in class! My teachers will be proud when they see how prepared I am now. You've really helped me with my education! �💖",
                "Oh wow, this will help me ace my tests! 🥺 With this, I can practice and study more effectively! School is going to be so much better now! I can't thank you enough for helping me learn and grow! �✨",
                "I'm so happy! This is perfect for my homework! 😊 Now I can keep my notes organized and study properly! This will really help me get good grades! Thank you for caring about my education! You're amazing! 📖�",
                "What a wonderful gift! 🤗 This school supply is going to make my lessons so much easier! I'll use it every day in class and think of your kindness! Thank you for supporting my dreams to learn! 🎒💕",
                "Thank you so much! This will help me with my school projects! 🌟 Now I have everything I need to succeed in my studies! You're not just giving me a gift, you're helping me build my future! 📚💖"
            ];
            
            // Pick a random thankful dialogue
            const randomDialogue = thankfulDialogues[Math.floor(Math.random() * thankfulDialogues.length)];
            
            // Show Shenievia's happy and thankful dialogue
            dialogueText = randomDialogue;
            dialogueType = 'lock'; // Use 'lock' type to only show close button
            showDialogue = true;
        }
    }
 
    // Get contextual dialogue for each scene
    function getSceneDialogue(sceneIndex: number, isRevisit: boolean, studentLevel: number): string {
        const scene = scenes[sceneIndex];
        
        // Scene-specific dialogues - telling about Readville Village
        const dialogues: Record<number, { first: string; revisit: string }> = {
            0: { // School
                first: studentLevel === 0
                    ? "This is my school in Readville Village! Classes are done for the day. Time to head home through our wonderful village. I've heard there are three special places along the way - the Sari-Sari Store, the Wet Market, and the Plaza - where I can learn valuable lessons!"
                    : studentLevel === 1
                    ? "Another day at school in Readville Village! I've already learned about honesty at the Sari-Sari Store. I wonder what other lessons await me in Readville on my way home today!"
                    : studentLevel === 2
                    ? "What a great day at school! I've already discovered lessons about honesty and health in Readville Village. Just one more adventure awaits me at the Plaza before I reach home!"
                    : "School's over for today in Readville Village! I've completed all my learning adventures here. Now I can enjoy a peaceful walk home through our beautiful village!",
                revisit: "Another wonderful day at school in Readville Village! Time to continue my journey home through our special community."
            },
            1: { // Plain 1
                first: "I love walking through these open fields of Readville Village! The fresh air and peaceful scenery always make me feel calm. Up ahead is the Sari-Sari Store where many villagers of Readville shop every day!",
                revisit: "The beautiful plains of Readville Village never get old. This path always leads me to interesting places in our community!"
            },
            2: { // Sari-Sari Store - Level 1
                first: studentLevel >= 1 
                    ? "The Sari-Sari Store is one of Readville Village's most beloved shops! I learned so much here about honesty and keeping promises - values that everyone in Readville cherishes!" 
                    : "Here's the Sari-Sari Store, a cornerstone of Readville Village! Mrs. Lena runs this shop and knows everyone by name. This is where I'll discover important lessons about honesty and trust!",
                revisit: studentLevel >= 1
                    ? "The Sari-Sari Store - where Readville Village's values of honesty shine bright! The lessons I learned here will stay with me forever."
                    : "The Sari-Sari Store in Readville Village awaits! I need to complete the stories here to continue my journey home."
            },
            3: { // Houses 1
                first: "These cozy homes belong to some of Readville Village's kindest families. Everyone here looks out for one another - that's what makes Readville such a special place!",
                revisit: "Passing by these familiar homes in Readville Village. I love how everyone here feels like family!"
            },
            4: { // Wet Market - Level 2
                first: studentLevel >= 2
                    ? "The Wet Market is where Readville Village truly comes alive! I discovered the importance of health and helping others here. Our Readville community really cares about each other's wellbeing!" 
                    : "The bustling Wet Market of Readville Village! This is where villagers buy fresh food and catch up on news. Important lessons about health and kindness are waiting for me here!",
                revisit: studentLevel >= 2
                    ? "The Wet Market - the heart of healthy living in Readville Village! The lessons about caring for ourselves and others were truly valuable."
                    : "The Wet Market in Readville has more to teach me. I need to complete these stories to continue my journey home."
            },
            5: { // Houses 2
                first: "More wonderful homes in Readville Village! I'm getting closer to my own house now. Each building here holds stories of kindness and community spirit that make Readville special.",
                revisit: "These friendly houses remind me why Readville Village is such a wonderful place to call home!"
            },
            6: { // Plaza - Level 3
                first: studentLevel >= 3
                    ? "The Readville Village Plaza! The heart of our village where everyone gathers for festivals and celebrations. Here I learned about making good choices and the importance of family - the foundation of Readville's community!" 
                    : "The grand Plaza of Readville Village! This is where our village celebrates festivals and important events. The final lessons about choices and family await me here at the center of Readville!",
                revisit: studentLevel >= 3
                    ? "The Plaza - Readville Village's pride and joy! Completing my journey here taught me values I'll carry for life."
                    : "The Plaza in Readville Village holds my final lessons. I need to complete these stories to reach home."
            },
            7: { // Plain 2
                first: studentLevel >= 3
                    ? "The peaceful fields of Readville Village again! Just a little further and I'll be home. What a wonderful journey through Readville this has been - I've learned so much!"
                    : "These beautiful plains would lead me home, but I haven't finished my journey through Readville Village yet. I should complete my lessons at the Plaza first!",
                revisit: studentLevel >= 3
                    ? "Almost home now! Walking through Readville Village has taught me so much. I can't wait to tell my family about all the lessons I learned in our wonderful village!"
                    : "I should head back to the Plaza in Readville Village and complete my lessons before going home."
            },
            8: { // Home Exterior
                first: "Home sweet home in beautiful Readville Village! My journey through the village has taught me about honesty at the Sari-Sari Store, health at the Wet Market, and making good choices at the Plaza. I'm so grateful to live in such a wonderful community like Readville! I can go inside if I want.",
                revisit: "There's no place like home in Readville Village! Every journey through our village reminds me how lucky I am to live in Readville, surrounded by caring neighbors and valuable lessons at every corner. I can enter my home anytime."
            },
            9: { // Home Interior
                first: "It feels so good to be inside my home! This is where I rest, study, and spend time with my family. All the lessons I learned in Readville Village - about honesty, health, and making good choices - start here at home with the love and support of my family. Home is truly the best place in Readville!",
                revisit: "Back inside my cozy home! This is my safe space in Readville Village where I can relax and reflect on all the wonderful lessons I've learned. Family and home are what make Readville truly special!"
            }
        };
        
        const dialogue = dialogues[sceneIndex];
        if (!dialogue) return '';
        
        return isRevisit ? dialogue.revisit : dialogue.first;
    }

    function showLevelConfirmation(level: number, title: string) {
        console.log('showLevelConfirmation called with:', { level, title });
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

    function showSchoolIntroduction() {
        const student = $studentData as StudentData | null;
        const studentLevel = student?.studentLevel ?? 0;
        
        let introText = '';
        if (studentLevel === 0) {
            introText = "School's out! Time to head home. I wonder what adventures await me in Readville today. I heard there are three special places along the way where I can learn important lessons!";
        } else if (studentLevel === 1) {
            introText = "Another day at school done! I've already learned about honesty at the Sari-Sari Store. I wonder what other lessons Readville has for me on my way home today!";
        } else if (studentLevel === 2) {
            introText = "What a great day at school! I've learned so much about honesty and health already. Just one more adventure awaits me in Readville before I reach home!";
        } else {
            introText = "School's finished for the day! I've completed all my adventures in Readville and learned so much about honesty, health, and making good choices. Time to walk home with all these wonderful lessons!";
        }
        
        dialogueText = introText;
        dialogueType = 'lock';
        showDialogue = true;
        isFirstScene = true;
    }

    function showSceneNarration(sceneIndex: number) {
        const student = $studentData as StudentData | null;
        const studentLevel = student?.studentLevel ?? 0;
        const isRevisit = sceneVisited.has(sceneIndex);
        
        console.log('Scene narration debug:', {
            sceneIndex,
            studentLevel,
            isRevisit,
            sceneVisitedSet: Array.from(sceneVisited)
        });
        
        // Check if this is the very first scene ever visited
        isFirstScene = sceneVisited.size === 0 && sceneIndex === 0;
        
        dialogueText = getSceneDialogue(sceneIndex, isRevisit, studentLevel);
        
        console.log('Dialogue text:', dialogueText);
        
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
            // Reset the hasTriggeredInteraction flag so level interaction can trigger
            hasTriggeredInteraction = false;
            // After closing narration, check if we should trigger level interaction
            setTimeout(() => {
                checkLevelInteraction();
            }, 100);
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
        console.log('handleDialogueYes - type:', dialogueType, 'promptLocation:', promptLocation);
        closeDialogueWithAnimation();
        justReturnedFromPlay = false; // Reset flag
        
        // Check if this is a home entry request
        const scene = scenes[currentScene];
        if (scene.canEnter && dialogueText.includes("enter your home")) {
            // Enter home - transition to home interior scene
            enterHome();
        } else if (dialogueType === 'interaction' || dialogueType === 'instruction') {
            // Regular level entry
            openLevelStories(promptLocation.level);
        }
    }

    function handleDialogueNo() {
        closeDialogueWithAnimation();
        justReturnedFromPlay = false; // Reset flag
        // Don't reset hasTriggeredInteraction - let it stay closed until player leaves center
    }

    function handleDialogueClose() {
        closeDialogueWithAnimation();
        justReturnedFromPlay = false; // Reset flag
        // Don't reset hasTriggeredInteraction - let it stay closed until player leaves center
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

    // Enter home - transition to home interior
    async function enterHome() {
        if (isTransitioning) return;
        
        console.log('Entering home...');
        
        // Start transition
        isTransitioning = true;
        showFade = true;
        
        // Wait for fade out
        await new Promise(resolve => setTimeout(resolve, 300));
        
        // Change to home interior scene (scene 9)
        currentScene = 9;
        
        // Place character in center
        characterX = 50;
        
        // Reset flags
        hasTriggeredInteraction = false;
        promptDismissedForScene = null;
        showDialogue = false;
        showLocationPrompt = false;
        sceneNarrationClosed = false;
        
        // Fade in
        await new Promise(resolve => setTimeout(resolve, 100));
        showFade = false;
        
        await new Promise(resolve => setTimeout(resolve, 300));
        isTransitioning = false;

        // Show home interior narration
        setTimeout(() => {
            showSceneNarration(currentScene);
        }, 200);
    }

    // Exit home - return to home exterior
    async function exitHome() {
        if (isTransitioning) return;
        
        console.log('Exiting home...');
        
        // Start transition
        isTransitioning = true;
        showFade = true;
        
        // Wait for fade out
        await new Promise(resolve => setTimeout(resolve, 300));
        
        // Return to home exterior scene (scene 8)
        currentScene = 8;
        
        // Place character in center
        characterX = 50;
        
        // Reset flags
        hasTriggeredInteraction = false;
        promptDismissedForScene = null;
        showDialogue = false;
        showLocationPrompt = false;
        sceneNarrationClosed = true; // Skip narration when exiting
        
        // Fade in
        await new Promise(resolve => setTimeout(resolve, 100));
        showFade = false;
        
        await new Promise(resolve => setTimeout(resolve, 300));
        isTransitioning = false;
    }

    // Get current sprite path
    $: currentSprite = (() => {
        const basePath = `/src/assets/Level_Walkthrough/shenievia/${gender}`;
        
        // Show front sprite when transitioning between directions
        if (showFrontTransition) {
            return `${basePath}/front/1.png`;
        }
        
        // Show turning sprite when changing direction (legacy - kept for compatibility)
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
        
        // Log student level for debugging
        console.log('Current student level in village:', studentLevel);
        
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

<!-- Loading Screen -->
{#if showLoading}
    <div class="loading-screen">
        <div class="loading-content">
            <!-- Logo or Title -->
            <div class="loading-logo">
                <h1 class="loading-title">Readville Village</h1>
                <div class="loading-subtitle">✨ An Adventure Awaits ✨</div>
            </div>
            
            <!-- Character Preview -->
            <div class="loading-character">
                <img src="/src/assets/Level_Walkthrough/shenievia/{gender}/front/1.png" alt="Character" />
            </div>
            
            <!-- Progress Bar -->
            <div class="loading-bar-container">
                <div class="loading-bar">
                    <div class="loading-bar-fill" style="width: {loadingProgress}%"></div>
                </div>
                <div class="loading-percentage">{loadingProgress}%</div>
            </div>
            
            <!-- Loading Text -->
            <div class="loading-text">{loadingText}</div>
            
            <!-- Animated Dots -->
            <div class="loading-dots">
                <span class="dot"></span>
                <span class="dot"></span>
                <span class="dot"></span>
            </div>
        </div>
    </div>
{/if}

<main class="village-container">
    <!-- White entrance fade transition -->
    {#if showEntranceFade}
        <div class="entrance-fade-overlay"></div>
    {/if}
    
    <!-- Background scene -->
    <div class="scene-background" style="background-image: url({scenes[currentScene].path})"></div>

    <!-- Center arrow indicator for key level scenes (Sari-Sari Store, Wet Market, Plaza) -->
    {#if (currentScene === 2 || currentScene === 4 || currentScene === 6) && !scenes[currentScene].isInterior}
        <div class="scene-center-arrow" aria-hidden="true">⬇️</div>
    {/if}

    <!-- White fade overlay -->
    {#if showFade}
        <div class="fade-overlay"></div>
    {/if}

    <!-- Character sprite -->
    <div class="character {scenes[currentScene].isInterior ? 'character-inside' : ''}" style="left: {characterX}%">
        <img src={currentSprite} alt="Character" />
    </div>

    <!-- Gift box in home interior (right corner) -->
    {#if scenes[currentScene].isInterior}
        <div class="gift-box" on:click={handleGiftBoxClick} on:keydown={(e) => e.key === 'Enter' && handleGiftBoxClick()} role="button" tabindex="0" aria-label="Gift box">
            <div class="gift-box-label">Gifts for Shenievia!</div>
            <img src="/src/assets/Level_Walkthrough/gift/gift-box.gif" alt="Gift Box" />
        </div>
    {/if}

    <!-- Scene indicator (for debugging) -->
    <div class="scene-indicator">
        Scene: {currentScene + 1}/{scenes.length} - {scenes[currentScene].name}
    </div>

    <!-- Exit Home button (only in home interior) - positioned near scene indicator -->
    {#if scenes[currentScene].isInterior}
        <button class="exit-home-btn" on:click={exitHome}>
            ← Outside
        </button>
    {/if}

    <!-- Controls hint - only show when no dialogue is active -->
    {#if !showDialogue && !scenes[currentScene].isInterior}
        <div class="controls-hint">
            ← → Arrow keys to move
        </div>
    {/if}

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
                
                <!-- Action buttons (for interaction and instruction types) -->
                {#if dialogueType === 'interaction'}
                    <div class="dialogue-actions">
                        <button class="dialogue-btn yes" on:click={handleDialogueYes}>
                            <span class="btn-icon">✓</span> Yes
                        </button>
                        <button class="dialogue-btn no" on:click={handleDialogueNo}>
                            <span class="btn-icon">✕</span> Not now
                        </button>
                    </div>
                {:else if dialogueType === 'instruction'}
                    <div class="dialogue-actions">
                        <button class="dialogue-btn yes" on:click={handleDialogueYes}>
                            <span class="btn-icon">▶</span> Start Journey
                        </button>
                        <button class="dialogue-btn no" on:click={handleDialogueNo}>
                            <span class="btn-icon">✕</span> Later
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

    <!-- Gift Box Purchase Prompt -->
    {#if showGiftBoxPrompt}
        <div class="dialogue-overlay" on:click={handleGiftBoxNo} on:keydown={(e) => e.key === 'Escape' && handleGiftBoxNo()} role="button" tabindex="0" aria-label="Close prompt"></div>
        <div class="gift-box-prompt">
            <div class="gift-prompt-content">
                <div class="gift-prompt-icon">🎁</div>
                <h3 class="gift-prompt-title">Gift Shop</h3>
                <p class="gift-prompt-text">Do you want to buy gifts for Shenievia?</p>
                <div class="gift-prompt-actions">
                    <button class="gift-btn yes" on:click={handleGiftBoxYes}>
                        <span class="btn-icon">✓</span> Yes, let's shop!
                    </button>
                    <button class="gift-btn no" on:click={handleGiftBoxNo}>
                        <span class="btn-icon">✕</span> Not now
                    </button>
                </div>
            </div>
        </div>
    {/if}

    <!-- Gift Shop Modal -->
    {#if showGiftShop}
        <GiftShop 
            studentTrash={$studentData?.studentColtrash || 0}
            on:close={handleGiftShopClose}
        />
    {/if}

    <!-- Gift Display Modal (when already has a gift) -->
    {#if showGiftDisplay && currentGifts.length > 0}
        <div class="dialogue-overlay" on:click={handleCloseGiftDisplay} on:keydown={(e) => e.key === 'Escape' && handleCloseGiftDisplay()} role="button" tabindex="0" aria-label="Close gift display"></div>
        <div class="gift-display-modal">
            <button class="gift-display-close-btn" on:click={handleCloseGiftDisplay} aria-label="Close">✕</button>
            
            <div class="gift-display-content">
                <div class="gift-display-header">
                    <h2>🎁 Shenievia's Gifts 🎁</h2>
                    <p class="gift-display-subtitle">You gave Shenievia {currentGifts.length} wonderful {currentGifts.length === 1 ? 'gift' : 'gifts'}!</p>
                </div>
                
                <div class="gifts-collection">
                    {#each currentGifts as gift}
                        <div class="gift-item">
                            <div class="gift-item-image-container">
                                <img src="/src/assets/Level_Walkthrough/gift/gifts/{gift.gift}.png" alt={gift.gift} class="gift-item-image" />
                            </div>
                            <div class="gift-item-name">{gift.gift.charAt(0).toUpperCase() + gift.gift.slice(1)}</div>
                        </div>
                    {/each}
                </div>
                
                <div class="gift-display-message">
                    <p>✨ Shenievia loves your gifts! ✨</p>
                    <p>{pronouns.Subject} uses them every day and thinks of your kindness!</p>
                </div>
                
                <div class="gift-display-actions">
                    <button class="gift-display-btn buy-another" on:click={handleBuyAnotherGift}>
                        <span class="btn-icon">🛍️</span> Buy Another Gift
                    </button>
                    <button class="gift-display-btn close" on:click={handleCloseGiftDisplay}>
                        <span class="btn-icon">✓</span> Okay
                    </button>
                </div>
            </div>
        </div>
    {/if}
</main>

<style>
    /* Loading Screen Styles */
    .loading-screen {
        position: fixed;
        top: 0;
        left: 0;
        width: 100vw;
        height: 100vh;
        background: linear-gradient(135deg, #84cc16 0%, #65a30d 50%, #4d7c0f 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 9999;
        animation: loadingFadeOut 0.5s ease-out 0s forwards;
    }

    @keyframes loadingFadeOut {
        from { opacity: 1; }
        to { opacity: 1; }
    }

    .loading-content {
        text-align: center;
        color: white;
        max-width: 600px;
        padding: 20px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
    }

    .loading-logo {
        margin-bottom: 30px;
        animation: fadeInScale 0.8s ease-out;
    }

    @keyframes fadeInScale {
        from {
            opacity: 0;
            transform: scale(0.8);
        }
        to {
            opacity: 1;
            transform: scale(1);
        }
    }

    .loading-title {
        font-size: 3rem;
        font-weight: bold;
        margin: 0;
        text-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
        background: linear-gradient(45deg, #fef08a, #fde047, #facc15);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        animation: titleGlow 2s ease-in-out infinite;
    }

    @keyframes titleGlow {
        0%, 100% {
            filter: drop-shadow(0 0 10px rgba(253, 224, 71, 0.6));
        }
        50% {
            filter: drop-shadow(0 0 20px rgba(253, 224, 71, 0.9));
        }
    }

    .loading-subtitle {
        font-size: 1.2rem;
        margin-top: 10px;
        opacity: 0.95;
        font-style: italic;
        color: #fef3c7;
        text-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
    }

    .loading-character {
        margin: 40px 0;
        animation: characterBounce 2s ease-in-out infinite;
        display: flex;
        justify-content: center;
        align-items: center;
    }

    .loading-character img {
        height: 120px;
        width: auto;
        filter: drop-shadow(0 10px 25px rgba(0, 0, 0, 0.4));
    }

    @keyframes characterBounce {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-15px); }
    }

    .loading-bar-container {
        margin: 30px 0;
        width: 100%;
        max-width: 500px;
    }

    .loading-bar {
        width: 100%;
        height: 24px;
        background-color: rgba(255, 255, 255, 0.3);
        border-radius: 15px;
        overflow: hidden;
        box-shadow: inset 0 3px 6px rgba(0, 0, 0, 0.3);
        border: 3px solid rgba(254, 243, 199, 0.5);
    }

    .loading-bar-fill {
        height: 100%;
        background: linear-gradient(90deg, #fef08a, #fde047, #facc15);
        border-radius: 12px;
        transition: width 0.3s ease-out;
        box-shadow: 0 0 20px rgba(253, 224, 71, 0.8);
        position: relative;
        overflow: hidden;
    }

    .loading-bar-fill::after {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent);
        animation: shimmer 1.5s infinite;
    }

    @keyframes shimmer {
        0% { transform: translateX(-100%); }
        100% { transform: translateX(100%); }
    }

    .loading-percentage {
        margin-top: 15px;
        font-size: 2rem;
        font-weight: bold;
        text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
    }

    .loading-text {
        font-size: 1.2rem;
        margin: 20px 0 10px;
        opacity: 0.95;
        min-height: 30px;
        animation: fadeInOut 1s ease-in-out infinite;
    }

    .loading-dots {
        display: flex;
        justify-content: center;
        gap: 8px;
        margin-top: 10px;
    }

    .dot {
        width: 10px;
        height: 10px;
        background-color: #fef08a;
        border-radius: 50%;
        animation: dotPulse 1.4s ease-in-out infinite;
        opacity: 0.7;
        box-shadow: 0 0 8px rgba(253, 224, 71, 0.6);
    }

    .dot:nth-child(1) { animation-delay: 0s; }
    .dot:nth-child(2) { animation-delay: 0.2s; }
    .dot:nth-child(3) { animation-delay: 0.4s; }

    @keyframes dotPulse {
        0%, 80%, 100% {
            transform: scale(1);
            opacity: 0.7;
            background-color: #fef08a;
        }
        40% {
            transform: scale(1.3);
            opacity: 1;
            background-color: #fde047;
        }
    }

    /* Village Container Styles */
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

    .entrance-fade-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: white;
        animation: entranceFadeOut 1s ease-out forwards;
        pointer-events: none;
        z-index: 100;
    }

    @keyframes entranceFadeOut {
        0% { opacity: 1; }
        100% { opacity: 0; }
    }

    .character {
        position: absolute;
        bottom: 20%;
        transform: translateX(-50%);
        z-index: 5;
        transition: left 0.016s linear; /* Smoother transition - 1 frame at 60fps */
    }

    /* Character inside home - larger and positioned lower */
    .character-inside {
        /* Lower the character further so the body appears larger and closer to the camera */
        bottom: -30% !important; /* more of the character is visible inside the house */
    }

    .character img {
        height: 150px;
        width: auto;
        image-rendering: pixelated;
        filter: drop-shadow(2px 2px 4px rgba(0, 0, 0, 0.3));
        transition: transform 0.15s ease-out; /* Smooth transform transitions */
    }

    /* Character inside home - much bigger */
    .character-inside img {
        height: 520px !important; /* Much larger to appear closer inside the home */
        width: auto !important;
        transform-origin: bottom center;
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

    /* Exit Home button (shown in home interior) */
    .exit-home-btn {
        position: absolute;
        top: 70px;
        left: 20px;
        background: linear-gradient(135deg, #10b981, #059669);
        color: white;
        padding: 10px 20px;
        border-radius: 10px;
        font-size: 1.1rem;
        font-weight: bold;
        border: 2px solid white;
        cursor: pointer;
        z-index: 30;
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
    }

    .exit-home-btn:hover {
        background: linear-gradient(135deg, #059669, #047857);
        transform: scale(1.05);
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.4);
    }

    .exit-home-btn:active {
        transform: scale(0.95);
    }

    /* Gift Box in Home Interior */
    .gift-box {
        position: absolute;
        right: 10%;
        bottom: 25%;
        cursor: pointer;
        z-index: 25;
        transition: all 0.3s ease;
        animation: giftBounce 2s ease-in-out infinite;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 10px;
    }

    .gift-box-label {
        background: linear-gradient(135deg, #fbbf24, #f59e0b);
        color: white;
        padding: 8px 16px;
        border-radius: 20px;
        font-size: 1.1rem;
        font-weight: bold;
        text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
        box-shadow: 0 4px 15px rgba(251, 191, 36, 0.5);
        animation: floatText 2s ease-in-out infinite;
        white-space: nowrap;
        border: 2px solid rgba(255, 255, 255, 0.5);
    }

    @keyframes floatText {
        0%, 100% {
            transform: translateY(0);
        }
        50% {
            transform: translateY(-8px);
        }
    }

    .gift-box img {
        width: 150px;
        height: auto;
        filter: drop-shadow(0 4px 10px rgba(0, 0, 0, 0.3));
    }

    .gift-box:hover {
        transform: scale(1.1);
    }

    .gift-box:hover .gift-box-label {
        background: linear-gradient(135deg, #f59e0b, #d97706);
        box-shadow: 0 6px 20px rgba(251, 191, 36, 0.7);
    }

    @keyframes giftBounce {
        0%, 100% {
            transform: translateY(0);
        }
        50% {
            transform: translateY(-10px);
        }
    }

    /* Gift Box Purchase Prompt */
    .gift-box-prompt {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        background: linear-gradient(135deg, #fef3c7, #fde68a);
        border: 4px solid #f59e0b;
        border-radius: 20px;
        padding: 30px;
        max-width: 500px;
        z-index: 55;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
        animation: fadeIn 0.3s ease-out;
    }

    .gift-prompt-content {
        text-align: center;
    }

    .gift-prompt-icon {
        font-size: 4rem;
        margin-bottom: 15px;
        animation: giftBounce 2s ease-in-out infinite;
    }

    .gift-prompt-title {
        font-size: 2rem;
        font-weight: bold;
        color: #92400e;
        margin: 0 0 15px 0;
    }

    .gift-prompt-text {
        font-size: 1.3rem;
        color: #78350f;
        margin: 0 0 25px 0;
    }

    .gift-prompt-actions {
        display: flex;
        gap: 15px;
        justify-content: center;
    }

    .gift-btn {
        padding: 12px 30px;
        border-radius: 12px;
        font-size: 1.1rem;
        font-weight: bold;
        border: none;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .gift-btn.yes {
        background: linear-gradient(135deg, #10b981, #059669);
        color: white;
        box-shadow: 0 4px 15px rgba(16, 185, 129, 0.4);
    }

    .gift-btn.yes:hover {
        background: linear-gradient(135deg, #059669, #047857);
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(16, 185, 129, 0.6);
    }

    .gift-btn.no {
        background: linear-gradient(135deg, #ef4444, #dc2626);
        color: white;
        box-shadow: 0 4px 15px rgba(239, 68, 68, 0.4);
    }

    .gift-btn.no:hover {
        background: linear-gradient(135deg, #dc2626, #b91c1c);
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(239, 68, 68, 0.6);
    }

    .gift-btn:active {
        transform: translateY(0);
    }

    /* Gift Display Modal Styles */
    .gift-display-modal {
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: 90%;
        max-width: 450px;
        background: linear-gradient(135deg, #fef3c7, #fde68a);
        border: 5px solid #f59e0b;
        border-radius: 20px;
        padding: 20px;
        z-index: 56;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
        animation: slideIn 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
    }

    @keyframes slideIn {
        from {
            opacity: 0;
            transform: translate(-50%, -60%);
        }
        to {
            opacity: 1;
            transform: translate(-50%, -50%);
        }
    }

    .gift-display-close-btn {
        position: absolute;
        top: 10px;
        right: 10px;
        background: rgba(239, 68, 68, 0.9);
        color: white;
        border: 2px solid white;
        border-radius: 50%;
        width: 35px;
        height: 35px;
        font-size: 1.3rem;
        font-weight: bold;
        cursor: pointer;
        transition: all 0.2s ease;
        z-index: 57;
    }

    .gift-display-close-btn:hover {
        background: rgba(220, 38, 38, 1);
        transform: scale(1.1);
    }

    .gift-display-content {
        text-align: center;
    }

    .gift-display-header h2 {
        font-size: 1.8rem;
        color: #92400e;
        margin: 0 0 8px 0;
        text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.1);
    }

    .gift-display-subtitle {
        font-size: 1rem;
        color: #78350f;
        margin: 0 0 15px 0;
        font-style: italic;
    }

    .gifts-collection {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
        gap: 12px;
        margin-bottom: 15px;
        max-height: 250px;
        overflow-y: auto;
        padding: 10px;
        background: rgba(255, 255, 255, 0.5);
        border-radius: 12px;
    }

    .gift-item {
        background: white;
        border: 2px solid #f59e0b;
        border-radius: 10px;
        padding: 10px;
        text-align: center;
        transition: all 0.2s ease;
    }

    .gift-item:hover {
        transform: translateY(-3px);
        box-shadow: 0 4px 15px rgba(245, 158, 11, 0.3);
    }

    .gift-item-image-container {
        width: 100%;
        height: 80px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 8px;
    }

    .gift-item-image {
        max-width: 100%;
        max-height: 100%;
        object-fit: contain;
    }

    .gift-item-name {
        font-size: 0.9rem;
        font-weight: bold;
        color: #92400e;
        text-transform: capitalize;
    }

    .gift-display-message {
        background: linear-gradient(135deg, #d1fae5, #a7f3d0);
        border: 2px solid #10b981;
        border-radius: 12px;
        padding: 15px;
        margin: 0 0 15px 0;
    }

    .gift-display-message p {
        font-size: 1rem;
        color: #065f46;
        margin: 0 0 8px 0;
        font-weight: 600;
    }

    .gift-display-message p:last-child {
        margin: 0;
    }

    .gift-display-actions {
        display: flex;
        gap: 10px;
        justify-content: center;
        flex-wrap: wrap;
    }

    .gift-display-btn {
        padding: 10px 20px;
        border-radius: 12px;
        font-size: 1rem;
        font-weight: bold;
        border: none;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .gift-display-btn.buy-another {
        background: linear-gradient(135deg, #10b981, #059669);
        color: white;
        box-shadow: 0 4px 15px rgba(16, 185, 129, 0.4);
    }

    .gift-display-btn.buy-another:hover {
        background: linear-gradient(135deg, #059669, #047857);
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(16, 185, 129, 0.6);
    }

    .gift-display-btn.close {
        background: linear-gradient(135deg, #f59e0b, #d97706);
        color: white;
        box-shadow: 0 4px 15px rgba(245, 158, 11, 0.4);
    }

    .gift-display-btn.close:hover {
        background: linear-gradient(135deg, #d97706, #b45309);
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(245, 158, 11, 0.6);
    }

    .gift-display-btn:active {
        transform: translateY(0);
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
        .loading-title {
            font-size: 2.5rem;
        }

        .loading-subtitle {
            font-size: 1.1rem;
        }

        .loading-character img {
            height: 100px;
        }

        .loading-bar {
            height: 20px;
        }

        .loading-text {
            font-size: 1.1rem;
        }

        .loading-percentage {
            font-size: 1.8rem;
        }

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
        .loading-title {
            font-size: 1.8rem;
        }

        .loading-subtitle {
            font-size: 1rem;
        }

        .loading-character img {
            height: 90px;
        }

        .loading-bar {
            height: 18px;
        }

        .loading-percentage {
            font-size: 1.5rem;
        }

        .loading-text {
            font-size: 1rem;
        }

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

    /* Center arrow indicator styles (decorative, non-interactive) */
    .scene-center-arrow {
        position: absolute;
        left: 50%;
        top: 28%; /* moved noticeably higher */
        transform: translate(-50%, -50%);
        z-index: 30;
        pointer-events: none; /* never block interactions */
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0.92; /* visible */
        will-change: transform, opacity;
        /*
            Combined animation:
            - arrow-bounce: continuous (1.1s loop)
            - arrow-cycle: overall 13s loop where first ~5s is visible (with internal fade), then ~8s hidden
        */
        animation: arrow-bounce 1.1s ease-in-out infinite, arrow-cycle 13s linear infinite;
    }

    .scene-center-arrow {
        /* emoji styling */
        font-size: 72px;
        line-height: 1;
        text-align: center;
        filter: drop-shadow(0 6px 12px rgba(0,0,0,0.35));
    }

    @keyframes arrow-bounce {
        0% { transform: translate(-50%, -50%) translateY(0); }
        50% { transform: translate(-50%, -50%) translateY(-10px); }
        100% { transform: translate(-50%, -50%) translateY(0); }
    }

    /* Fades the arrow in/out subtly so it appears to breathe and partially disappear */
    /* keyframes for short fade breathing used inside the visible window (first ~5s) */
    @keyframes arrow-fade-short {
        0% { opacity: 0.92; }
        50% { opacity: 0.18; }
        100% { opacity: 0.92; }
    }

    /* overall cycle: 0-38% (~5s of 13s) -> visible breathing (map to arrow-fade-short), 38%-100% -> hidden
       We emulate this by stepping opacity across the long animation timeline. */
    @keyframes arrow-cycle {
        /* Visible breathing window (0% - 38%) */
        0%   { opacity: 0.92; }
        6%   { opacity: 0.5; }
        12%  { opacity: 0.2; }
        18%  { opacity: 0.6; }
        24%  { opacity: 0.25; }
        30%  { opacity: 0.7; }
        36%  { opacity: 0.3; }
        38%  { opacity: 0.0; } /* start hidden */
        /* Hidden period (38% - 100%) - remain invisible */
        100% { opacity: 0.0; }
    }
</style>