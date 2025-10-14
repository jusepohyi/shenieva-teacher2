<script>
    import { language } from '$lib/store/story_lang_audio';

    const slide = {
        english: { text: "Thankfully, the woman was brought to the nearest clinic to receive aid while Maya watched over her. When the old woman woke up, she felt grateful to Maya for her kindness and help. Maya smiled and felt happy." },
        cebuano: { text: "Sa pagpasalamat, ang tigulang gidala sa pinakaduol nga klinika arun matabangan samtang si Maya nagbantay kaniya. Sa pagkahuman, ningmata ang tigulang ug nakabati siya ug dakong pasalamat kang Maya tungod sa iyang kaayo ug tabang. Si Maya mipahiyom ug nakabati siya ug kalipay." },
        image: '/src/assets/LEVEL_2/STORY_2/PIC5.jpg'
    };

    $: currentText = $language === 'cebuano' ? slide.cebuano.text : slide.english.text;
</script>

<div class="slide-container">
    <div class="image-wrapper"><img src={slide.image} alt="Clinic" class="story-image"/></div>
    <div class="story-text">{currentText}</div>
</div>

<style>
    .slide-container { width:100%; height:100%; display:flex; flex-direction:column; justify-content:center; align-items:center; gap:1rem; padding:1rem; box-sizing:border-box; }
    .image-wrapper { flex:1; width:100%; display:flex; align-items:center; justify-content:center; min-height:0; }
    .story-image { max-width:100%; max-height:100%; width:auto; height:auto; object-fit:contain; border-radius:0.5rem; }
    .story-text { font-size:clamp(1rem,2vw,1.25rem); line-height:1.6; color:#1f2937; text-align:center; }
</style>