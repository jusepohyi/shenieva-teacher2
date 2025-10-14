<script>
    import { language } from '$lib/store/story_lang_audio';

    const slide = {
        english: { text: "Royce is a child known for saving things and money. He has been saving money for a long time because he wants to buy his dream toy." },
        cebuano: { text: "Si Royce usa ka bata nga ilado sa pagtipid sa mga butang ug kwarta. Dugay na siya nagtipid sa iyang kwarta tungod kay gusto niya mupalit sa iyang pangandoy nga dulaan." },
        image: '/src/assets/LEVEL_2/STORY_3/Pic1.jpg'
    };

    $: currentText = $language === 'cebuano' ? slide.cebuano.text : slide.english.text;
</script>

<div class="slide-container">
    <div class="image-wrapper"><img src={slide.image} alt="Royce saves" class="story-image"/></div>
    <div class="story-text">{currentText}</div>
</div>

<style>
    .slide-container { width:100%; height:100%; display:flex; flex-direction:column; justify-content:center; align-items:center; gap:1rem; padding:1rem; box-sizing:border-box; }
    .image-wrapper { flex:1; width:100%; display:flex; align-items:center; justify-content:center; min-height:0; }
    .story-image { max-width:100%; max-height:100%; width:auto; height:auto; object-fit:contain; border-radius:0.5rem; }
    .story-text { font-size:clamp(1rem,2vw,1.25rem); line-height:1.6; color:#1f2937; text-align:center; }
</style>