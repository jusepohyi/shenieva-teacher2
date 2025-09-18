<script>
    import { fade } from 'svelte/transition';
    import { language } from "$lib/store/story_lang_audio";

    const slide = {
        text: {
            english: "Maria went near and said, “Thank you for trusting me, Lena. This is my payment.” Lena nodded and told Maria, \"No problem at all. I’m just glad you were able to pay.\" Maria thanked her again, and Lena felt happy that she could trust her customer, knowing that trust is important in business.",
            cebuano: "Niduol si Maria ug niingon, “Daghang salamat sa pagsalig nako, Lena. Ania na ang akong bayad.” Nitando si Lena ug miingon, “Walay problema gyud. Nalipay ako nga nakabayad ka.” Si Maria mibalos ug pasalamat pag-usab, ug si Lena nabati ang kalipay nga nakasalig siya sa iyang kustomer kay nasabtan niya nga ang pagsalig importante kaayo sa negosyo."
        },
        image: "/src/assets/LEVEL_1/STORY_1/PIC7.jpg"
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
    }

    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
</style>
