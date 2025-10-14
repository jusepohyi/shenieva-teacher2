<script>
    import { language } from '$lib/store/story_lang_audio';

    const slide = {
        english: { text: "When Royce came home from school, he decided to open his piggy bank." },
        cebuano: { text: "Usa ka adlaw, pag-uli niya gikan sa tulunghaan, nakahukom si Royce nga ablihan ang iyang alkansiya." },
        image: '/src/assets/LEVEL_2/STORY_3/Pic2.jpg'
    };

    $: currentText = $language === 'cebuano' ? slide.cebuano.text : slide.english.text;
</script>

<div class="slide-container">
    <div class="image-wrapper"><img src={slide.image} alt="Piggy bank" class="story-image"/></div>
    <div class="story-text">{currentText}</div>
</div>

<style>
    .slide-container { width:100%; height:100%; display:flex; flex-direction:column; justify-content:center; align-items:center; gap:1rem; padding:1rem; box-sizing:border-box; }
    .image-wrapper { flex:1; width:100%; display:flex; align-items:center; justify-content:center; min-height:0; }
    .story-image { max-width:100%; max-height:100%; width:auto; height:auto; object-fit:contain; border-radius:0.5rem; }
    .story-text { font-size:clamp(1rem,2vw,1.25rem); line-height:1.6; color:#1f2937; text-align:center; }
</style>