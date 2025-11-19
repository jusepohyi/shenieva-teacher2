<script>
    import { language } from '$lib/store/story_lang_audio';

    const slide = {
        english: { text: "His mother asked him,\"What's the problem, Hector?\" Hector replied weakly, \"I cannot breathe properly, mother.\"" },
        cebuano: { text: "Nangutana ang iyang inahan, \"Unsay problema, Hector?\" Si Hector luyang mitubag, \"Dili ako makatarung ginhawa, inahan.\"" },
    image: '/converted/assets/LEVEL_2/STORY_1/PIC4.webp'
    };

    let currentLanguage;
    language.subscribe(v => currentLanguage = v);
    $: currentText = slide[currentLanguage].text;
</script>

<div class="slide-container">
    <div class="image-wrapper"><img src={slide.image} alt="" class="story-image"/></div>
    <div class="story-text">{currentText}</div>
</div>

<style>
    .slide-container { width: 100%; height: 100%; display:flex; flex-direction:column; justify-content:center; align-items:center; gap:1rem; padding:1rem; box-sizing:border-box; }
    .image-wrapper { flex:1; width:100%; display:flex; align-items:center; justify-content:center; min-height:0; }
    .story-image { max-width:100%; max-height:100%; width:auto; height:auto; object-fit:contain; border-radius:0.5rem; }
    .story-text { font-size:clamp(1rem,2vw,1.25rem); line-height:1.6; color:#1f2937; text-align:center; padding:0 1rem; margin:0; }
</style>
