<script>
    import { language } from '$lib/store/story_lang_audio';

    const slide = {
        english: { text: "Maya returned home to her family and shared the story of how she helped the old woman. Her parents were proud and praised Maya for being compassionate and helping a person in need. Maya learned that small acts of kindness can make a big difference." },
        cebuano: { text: "Mibalik si Maya sa iyang panimalay ug gisultian ang iyang pamilya sa nahinabo kung giunsa niya pagtabang ang tigulang. Nagmalipay ang iyang mga ginikanan ug gipasidunggan si Maya sa iyang pagkamaayo ug pagtabang sa nanginahanglan. Nakakat-on si Maya nga ang gagmay'ng buhat sa kaayo makadala ug dakong kalainan." },
    image: '/assets/LEVEL_2/STORY_2/PIC6.jpg'
    };

    $: currentText = $language === 'cebuano' ? slide.cebuano.text : slide.english.text;
</script>

<div class="slide-container">
    <div class="image-wrapper"><img src={slide.image} alt="Home" class="story-image"/></div>
    <div class="story-text">{currentText}</div>
</div>

<style>
    .slide-container { width:100%; height:100%; display:flex; flex-direction:column; justify-content:center; align-items:center; gap:1rem; padding:1rem; box-sizing:border-box; }
    .image-wrapper { flex:1; width:100%; display:flex; align-items:center; justify-content:center; min-height:0; }
    .story-image { max-width:100%; max-height:100%; width:auto; height:auto; object-fit:contain; border-radius:0.5rem; }
    .story-text { font-size:clamp(1rem,2vw,1.25rem); line-height:1.6; color:#1f2937; text-align:center; }
</style>