<script>
    import { language } from '$lib/store/story_lang_audio';

    const slide = {
        text: {
            english: "One sunny afternoon, Rosa and Juan were playing patintero in their front yard.",
            cebuano: "Usa ka init nga hapon, nagdula si Rosa ug Juan ug patintero sa ilang nataran."
        },
    image: "/assets/LEVEL_3/STORY_2/PIC1.jpg"
    };
</script>

<div class="slide-container">
    <div class="image-wrapper">
        <img src={slide.image} alt="Story Scene" class="story-image" />
    </div>

    <div class="story-text">{$language === 'english' ? slide.text.english : slide.text.cebuano}</div>
</div>

<style>
    .slide-container { width: 100%; height: 100%; display:flex; flex-direction:column; justify-content:space-between; align-items:center; gap:1rem; padding:1rem; box-sizing:border-box; }
    .image-wrapper { flex:1; width:100%; display:flex; align-items:center; justify-content:center; min-height:0; }
    .story-image { max-width:100%; max-height:100%; width:auto; height:auto; object-fit:contain; border-radius:0.5rem; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
    .story-text { font-size:clamp(1rem,2vw,1.25rem); line-height:1.6; color:#1f2937; text-align:center; padding:0 1rem; margin:0; }
    .slide-container { animation: fadeIn 0.5s ease-in-out; }
    @keyframes fadeIn { from { opacity:0 } to { opacity:1 } }
</style>
