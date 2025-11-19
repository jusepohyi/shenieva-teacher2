<script>
    import { language } from "$lib/store/story_lang_audio";

    const slide = {
        text: {
            english: "While sleeping, her cheek became swollen and she felt the pain in her teeth. Candice run towards her mother who said, \“I told you, it’s not good to eat too much sweets and candies without brushing your teeth. Let’s go to the dentist tomorrow and remember to brush your teeth from now on\".",
            cebuano: "Sa usa ka hapon, usa sa iyang suki ug kasaligan nga higala nga si Maria miabot sa tindahan ug nipalit ug usa ka pakete sa asin, usa ka kilo nga bugas, ug usa ka pakete nga asukal."
        },
    image: "/assets/LEVEL_1/STORY_2/PIC4.jpg"
    };
</script>

<div class="slide-container">
    {#if slide.image}
        <div class="image-wrapper">
            <img
                src={slide.image}
                alt="Story Scene"
                class="story-image"
            />
        </div>
    {/if}
    <div class="story-text" transition:fade>
        {$language === 'english' ? slide.text.english : slide.text.cebuano}
    </div>
</div>

<style>
    .slide-container {
        width: 100%;
        height: 100%;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        align-items: center;
        gap: 1rem;
        padding: 1rem;
        box-sizing: border-box;
        animation: fadeIn 1000ms ease-in forwards;
    }

    .image-wrapper {
        flex: 1;
        width: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 0;
    }

    .story-image {
        max-width: 100%;
        max-height: 100%;
        width: auto;
        height: auto;
        object-fit: contain;
        border-radius: 0.5rem;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }

    .story-text {
        font-size: clamp(1rem, 2vw, 1.25rem);
        line-height: 1.6;
        color: #1f2937;
        text-align: center;
        padding: 0 1rem;
        margin: 0;
        animation: textFadeIn 1000ms ease-in forwards;
    }

    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }

    @keyframes textFadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
</style>