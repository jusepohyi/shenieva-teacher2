<script lang="ts">
    import StoryModal from '../../components/modals/level_2.svelte';
    import { onMount } from 'svelte';

    const slide = {
        text: "Choose a story. 🌟",
    };

    const stories = [
        {
            id: 1,
            title: "Hector's Health",
            image: "/converted/assets/LEVEL_2/STORY_1/PIC6.webp",
            key: "story2-1"
        },
        {
            id: 2,
            title: "Helpful Maya", 
            image: "/converted/assets/LEVEL_2/STORY_2/PIC6.webp",
            key: "story2-2"
        },
        {
            id: 3,
            title: "Royce Choice",
            image: "/converted/assets/LEVEL_2/STORY_3/Pic8.webp",
            key: "story2-3"
        }
    ];

    let showModal = false;
    let selectedStoryKey: string | null = null;

    function handleStorySelect(storyKey: string): void {
        selectedStoryKey = storyKey;
        showModal = true;
    }

    function closeModal(): void {
        showModal = false;
        selectedStoryKey = null;
    }

    onMount(() => {
        try {
            const shouldOpen = localStorage.getItem('openStory2Modal');
            if (shouldOpen === 'true') {
                selectedStoryKey = null;
                showModal = true;
                localStorage.removeItem('openStory2Modal');
            }
        } catch {}
    });
</script>

<div class="flex flex-col justify-center items-center text-center slide">
    <p class="text-[6vw] md:text-3xl text-gray-800 font-semibold text-fade mb-8">
        {slide.text}
    </p>

    <div class="story-container">
        {#each stories as story}
            <button 
                class="story-button"
                on:click={() => handleStorySelect(story.key)}
            >
                <img
                    src={story.image}
                    alt={story.title}
                    class="story-image"
                />
                <span class="story-label">{story.title}</span>
            </button>
        {/each}
    </div>

    {#if showModal && selectedStoryKey}
        <StoryModal showModal={showModal} on:close={closeModal} storyKey={selectedStoryKey} />
    {/if}
</div>

<style>
    :global(body) {
        margin: 0;
        padding: 0;
        overflow-x: hidden;
        overflow-y: auto;
        background: none;
    }

    .slide {
        animation: fadeIn 1000ms ease-in forwards;
        will-change: opacity;
        padding: 1rem;
        box-sizing: border-box;
        width: 100%;
        height: 100%;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 1rem;
    }

    .text-fade {
        white-space: pre-wrap;
        overflow-wrap: break-word;
        width: fit-content;
        animation: textFadeIn 1000ms ease-in forwards;
        will-change: opacity;
        -webkit-font-smoothing: antialiased;
        -moz-osx-font-smoothing: grayscale;
    }

    .story-container {
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        gap: 1rem;
        flex: 1;
        width: 100%;
        max-width: 900px;
        box-sizing: border-box;
        align-items: center;
    }

    .story-button {
        background: #ffffff;
        border: 2px solid #e2e8f0;
        border-radius: 0.75rem;
        padding: 0.75rem;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        flex-direction: column;
        align-items: center;
        width: 30%;
        min-width: 150px;
        max-width: 200px;
        aspect-ratio: 3/4;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        box-sizing: border-box;
    }

    .story-button:hover, .story-button:focus {
        transform: scale(1.1);
        border-color: #4299e1;
        box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
        z-index: 10;
    }

    .story-image {
        width: 100%;
        height: 70%;
        object-fit: contain;
        margin-bottom: 0.5rem;
    }

    .story-label {
        color: #2d3748;
        font-size: clamp(0.9rem, 1.5vw, 1.1rem);
        font-weight: 500;
        text-align: center;
        white-space: normal;
        line-height: 1.2;
    }

    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }

    @keyframes textFadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }

    @keyframes fadeOut {
        from { opacity: 1; }
        to { opacity: 0; }
    }
</style>