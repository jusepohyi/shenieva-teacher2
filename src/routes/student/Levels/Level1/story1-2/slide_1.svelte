<script>
    import { fade } from "svelte/transition";
    import { language } from "$lib/store/story_lang_audio";
    import { audioStore } from '$lib/store/audio_store';
    

    const story = {
        title: {
            english: "Candice and Candies",
            cebuano: "Si Candice ug ang mga Kendi"
        },
        text: {
            english: "There was once a child who loved candies, her name is Candice. She eats candies for breakfast, lunch and dinner..",
            cebuano: "Adunay tag-iya sa usa ka sari-sari nga tindahan nga ginganlan ug Lena. Nagaserbisyo siya sa mga kustomer matag adlaw uban ang usa ka pahiyom."
        },
    image: "/assets/LEVEL_1/STORY_2/PIC1.jpg"
    };
</script>

<div class="slide-container">
    <h1 class="title">
        {$language === 'english' ? story.title.english : story.title.cebuano}
    </h1>

    <div class="image-wrapper">
        <img
            src={story.image}
            alt="Story Scene"
            class="story-image"
        />
    </div>

    <div class="story-text" transition:fade>
        {$language === 'english' ? story.text.english : story.text.cebuano}
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
    }

    .title {
        font-size: clamp(1.5rem, 3vw, 2.5rem);
        font-weight: bold;
        color: #1e40af;
        text-align: center;
        margin: 0;
    }

    .image-wrapper {
        flex: 1;
        width: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 0;  /* This is important for flex child to shrink */
    }

    .story-image {
        max-width: 100%;
        max-height: 100%;
        width: auto;
        height: auto;
        object-fit: contain;
        border-radius: 0.5rem;
    }

    .story-text {
        font-size: clamp(1rem, 2vw, 1.25rem);
        line-height: 1.6;
        color: #1f2937;
        text-align: center;
        padding: 0 1rem;
        margin: 0;
    }

    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }

    .slide-container {
        animation: fadeIn 0.5s ease-in-out;
    }
</style>