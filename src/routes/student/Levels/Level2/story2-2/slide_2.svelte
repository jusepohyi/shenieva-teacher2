<script>
    import { language } from '$lib/store/story_lang_audio';

    const slide = {
        english: { text: "In their village, Maya and her parents live humbly. Maya’s parents are working in the market to provide for their needs. During school breaks, Maya enjoys helping her parents in the market and happily serves their customers." },
        cebuano: { text: "Sa ilang baryo, si Maya ug ang iyang mga ginikanan nagpuyo nga yano. Ang mga ginikanan ni Maya nagtrabaho sa merkado para sa ilang mga panginahanglanon. Sa panahon sa bakasyon sa eskwela, ganahan si Maya nga mutabang sa iyang mga ginikanan sa merkado ug malipayong nagaserbisyo sa ilang mga kustomer." },
    image: '/assets/LEVEL_2/STORY_2/PIC1.jpg'
    };

    let currentLanguage;
    language.subscribe(v => currentLanguage = v);
    $: currentText = slide[currentLanguage].text;
</script>

<div class="slide-container">
    <div class="image-wrapper"><img src={slide.image} alt="Market" class="story-image"/></div>
    <div class="story-text">{currentText}</div>
</div>

<style>
    .slide-container { width:100%; height:100%; display:flex; flex-direction:column; justify-content:center; align-items:center; gap:1rem; padding:1rem; box-sizing:border-box; }
    .image-wrapper { flex:1; width:100%; display:flex; align-items:center; justify-content:center; min-height:0; }
    .story-image { max-width:100%; max-height:100%; width:auto; height:auto; object-fit:contain; border-radius:0.5rem; }
    .story-text { font-size:clamp(1rem,2vw,1.25rem); line-height:1.6; color:#1f2937; text-align:center; }
</style>