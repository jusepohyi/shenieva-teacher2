<script>
    import { language } from '$lib/store/story_lang_audio';

    const slide = {
        text: {
            english: "He walked to the fence and called out, \"Lingling! Lingling! Where are you?\".",
            cebuano: "Niadto siya sa kural ug nagsinggit, \"Lingling! Lingling! Asa ka?\"."
        },
        image: "/src/assets/LEVEL_3/STORY_3/PIC5.jpg"
    };
</script>

<div class="slide-container">
    {#if slide.image}
        <div class="image-wrapper">
            <img src={slide.image} alt="Story Scene" class="story-image" />
        </div>
    {/if}
    <div class="story-text">
        {$language === 'english' ? slide.text.english : slide.text.cebuano}
    </div>
</div>

<style>
    .slide-container { width: 100%; height: 100%; display:flex; flex-direction:column; justify-content:space-between; align-items:center; gap:1rem; padding:1rem; box-sizing:border-box; animation: fadeIn 1000ms ease-in forwards; }
    .image-wrapper { flex:1; width:100%; display:flex; align-items:center; justify-content:center; min-height:0; }
    .story-image { max-width:100%; max-height:100%; width:auto; height:auto; object-fit:contain; border-radius:0.5rem; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
    .story-text { font-size:clamp(1rem,2vw,1.25rem); line-height:1.6; color:#1f2937; text-align:center; padding:0 1rem; margin:0; }
    @keyframes fadeIn { from { opacity:0 } to { opacity:1 } }
</style>
