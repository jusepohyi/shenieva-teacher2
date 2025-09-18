<script>
    import { goto } from '$app/navigation'; // Import goto for SvelteKit navigation
    import { studentData } from '$lib/store/student_data'; // Import studentData store

    const slide = {
        text: "Congratulations! You've finished Level 1 of Shenievia Reads' journey through Readville Village! 🎉",
        image: "../../assets/school-bg.gif" // Update to relative path
    };

    function continueToQuiz() {
        // Check studentLevel from the studentData store
        if ($studentData && $studentData.studentLevel >= 1) {
            goto('/student/game/trash_1'); // Navigate to game if level is 1 or higher
        } else {
            goto('/student/quizzes/quiz1'); // Navigate to quiz if level is below 1 or no data
        }
    }
</script>

<div class="flex flex-col justify-center items-center text-center slide">
    {#if slide.image}
        <div class="image-container">
            <img
                src={"/src/assets/school-bg.gif" }
                alt="Congrats Scene"
                class="block mx-auto rounded-[2vw] shadow-lg"
            />
        </div>
    {/if}
    <p class="text-[4vw] md:text-2xl text-gray-800 font-semibold text-fade">
        {slide.text}
    </p>
    <button
        class="mt-[2vh] bg-teal-300 text-gray-900 px-[6vw] py-[2vh] rounded-[3vw] text-[5vw] md:text-3xl font-bold shadow-md hover:bg-teal-400 hover:scale-105 transition-all duration-300 flex items-center justify-center kid-button"
        on:click={continueToQuiz}
    >
        Continue 🌟
    </button>
</div>

<style>
    .slide {
        animation: fadeIn 1000ms ease-in forwards;
        will-change: opacity;
    }

    .image-container {
        width: 65%;
        height: auto;
        max-width: 800px;
        max-height: 400px;
        margin-bottom: 2vh;
        display: flex;
        justify-content: center;
        align-items: center;
    }

    .image-container img {
        width: 100%;
        height: 100%;
        object-fit: contain;
    }

    .text-fade {
        white-space: pre-wrap;
        overflow-wrap: break-word;
        max-width: 80%;
        animation: textFadeIn 1000ms ease-in forwards;
        will-change: opacity;
        -webkit-font-smoothing: antialiased;
        -moz-osx-font-smoothing: grayscale;
        transform: translateZ(0);
    }

    .kid-button {
        background: linear-gradient(135deg, #5eead4, #2dd4bf); /* Soft teal gradient */
        border: 3px solid #14b8a6; /* Darker teal border */
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); /* Softer shadow */
    }

    .kid-button:hover {
        background: linear-gradient(135deg, #5eead4, #2dd4bf); /* Maintain gradient on hover */
        box-shadow: 0 6px 8px rgba(0, 0, 0, 0.15); /* Slightly larger shadow */
    }

    .kid-button:active {
        transform: scale(1.1); /* Bounce on click */
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
        }
        to {
            opacity: 1;
        }
    }

    @keyframes textFadeIn {
        from {
            opacity: 0;
        }
        to {
            opacity: 1;
        }
    }

    :global(.slide) {
        animation: fadeOut 1000ms ease-out forwards;
    }

    @keyframes fadeOut {
        from {
            opacity: 1;
        }
        to {
            opacity: 0;
        }
    }
</style>