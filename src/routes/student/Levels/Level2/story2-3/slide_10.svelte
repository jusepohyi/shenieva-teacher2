<script>
    import { language } from '$lib/store/story_lang_audio';

    const slide = {
        english: { text: "Since then, Royce started saving money again for his dream toy. He did not regret helping his brother because he believes that it is important to help others first before his own wants." },
        cebuano: { text: "Sukad niadto, nagsugod si Royce pagtipid pag-usab ug kwarta alang sa iyang pangandoy nga dulaan. Wala siya nagbasol sa pagtabang sa iyang igsoon kay nagtuo siya nga importante ang magtabang sa uban una kaysa sa iyang kaugalingong gusto." },
    image: '/converted/assets/LEVEL_2/STORY_3/Pic8.webp'
    };

    $: currentText = $language === 'cebuano' ? slide.cebuano.text : slide.english.text;
</script>

<div class="slide-container">
    <div class="image-wrapper"><img src={slide.image} alt="Saving again" class="story-image"/></div>
    <div class="story-text">{currentText}</div>
</div>

<style>
    .slide-container { width:100%; height:100%; display:flex; flex-direction:column; justify-content:center; align-items:center; gap:1rem; padding:1rem; box-sizing:border-box; }
    .image-wrapper { flex:1; width:100%; display:flex; align-items:center; justify-content:center; min-height:0; }
    .story-image { max-width:100%; max-height:100%; width:auto; height:auto; object-fit:contain; border-radius:0.5rem; }
    .story-text { font-size:clamp(1rem,2vw,1.25rem); line-height:1.6; color:#1f2937; text-align:center; }
</style>