<script>
    import { language } from '$lib/store/story_lang_audio';

    const slide = {
        english: {
            text: "On one sunny day, Hector and his friend were on their way to the market to assist their parents. \"Hector! Hector! Hurry up!\" his friend called out Hector as he frequently missed out on the walk.",
        },
        cebuano: {
            text: "Sa usa ka mainit nga adlaw, si Hector ug ang iyang higala naglakaw padulong sa merkado aron tabangan ang ilang mga ginikanan. “Hector! Hector! Dali na!”, tawag sa iyang higala kay pirmi man siya malangay sa paglakaw.",
        },
    image: '/converted/assets/LEVEL_2/STORY_1/PIC2.webp'
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
