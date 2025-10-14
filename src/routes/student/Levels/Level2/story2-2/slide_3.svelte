<script>
    import { language } from '$lib/store/story_lang_audio';

    const slide = {
        english: { text: "One day, on her way home, Maya was crossing the street when she noticed an old woman sitting under a tree, appearing weak." },
        cebuano: { text: "Usa ka adlaw, samtang pauli si Maya ug padulong mutabok siya sa dalan, nakamatikod siya sa usa ka tigulang nga babaye nga naglingkod ilawom sa kahoy, kini makitang luya." },
        image: '/src/assets/LEVEL_2/STORY_2/PIC2.jpg'
    };

    let currentLanguage;
    language.subscribe(v => currentLanguage = v);
    $: currentText = slide[currentLanguage].text;
</script>

<div class="slide-container">
    <div class="image-wrapper"><img src={slide.image} alt="Old woman" class="story-image"/></div>
    <div class="story-text">{currentText}</div>
</div>

<style>
    .slide-container { width:100%; height:100%; display:flex; flex-direction:column; justify-content:center; align-items:center; gap:1rem; padding:1rem; box-sizing:border-box; }
    .image-wrapper { flex:1; width:100%; display:flex; align-items:center; justify-content:center; min-height:0; }
    .story-image { max-width:100%; max-height:100%; width:auto; height:auto; object-fit:contain; border-radius:0.5rem; }
    .story-text { font-size:clamp(1rem,2vw,1.25rem); line-height:1.6; color:#1f2937; text-align:center; }
</style>