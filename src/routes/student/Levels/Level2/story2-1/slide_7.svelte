<script>
    import { language } from '$lib/store/story_lang_audio';

    const slide = {
        english: { text: "Hector's mother brought him to the hospital. The doctor said, \"Hector's weight is above average for his age right now. He needs to practice eating healthy foods like vegetables and fruits, avoid eating too many sweets like chocolate and should start exercising.\" Hector listened to what the doctor said." },
        cebuano: { text: "Gidala si Hector ang iyang inahan sa balay-tambalanan. Miingon ang doktor, “Ang timbang ni Hector labaw sa insaktong timbang para iyang edad karon. Kinahanglan siya magkat-on sa pagkaon ug mga makahimsog nga pagkaon sama sa mga gulay ug prutas, likayan ang pagkaon ug daghang tam-is sama sa tsokolate, ug magsugod sa pag-ehersisyo.” Naminaw si Hector sa mga pulong sa doktor." },
        image: '/src/assets/LEVEL_2/STORY_1/PIC5.jpg'
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
