// Story Asset Manifest for Preloading
// This file contains all assets (images + audio) for each story

export interface StoryAssets {
  images: string[];
  audio: string[];
}

export interface LevelAssets {
  [storyKey: string]: StoryAssets;
}

export const STORY_ASSETS: Record<string, LevelAssets> = {
  Level1: {
    'story1-1': {
      images: [
        '/converted/assets/LEVEL_1/STORY_1/PIC1.webp',
        '/converted/assets/LEVEL_1/STORY_1/PIC2.webp',
        '/converted/assets/LEVEL_1/STORY_1/PIC3.webp',
        '/converted/assets/LEVEL_1/STORY_1/PIC4.webp',
        '/converted/assets/LEVEL_1/STORY_1/PIC5.webp',
        '/converted/assets/LEVEL_1/STORY_1/PIC6.webp',
        '/converted/assets/LEVEL_1/STORY_1/PIC7.webp'
      ],
      audio: [
        // Title audio
        "/assets/audio/story-telling/Level_1/story_1/title/MARIA_S PROMISE TITLE.mp3",
        
        // Normal speed slides (only slides that exist: 1,2,3,4,6,7,10)
        '/assets/audio/story-telling/Level_1/story_1/normal/slide_1/M1.mp3',
        '/assets/audio/story-telling/Level_1/story_1/normal/slide_2/M2.mp3',
        '/assets/audio/story-telling/Level_1/story_1/normal/slide_3/M3.mp3',
        '/assets/audio/story-telling/Level_1/story_1/normal/slide_4/M4.mp3',
        '/assets/audio/story-telling/Level_1/story_1/normal/slide_6/M6.mp3',
        '/assets/audio/story-telling/Level_1/story_1/normal/slide_7/M7.mp3',
        '/assets/audio/story-telling/Level_1/story_1/normal/slide_10/M10.mp3',
        
        // Slow speed slides (only slides that exist: 1,2,3,4,6,7,10)
        '/assets/audio/story-telling/Level_1/story_1/slow/slide_1/M1.mp3',
        '/assets/audio/story-telling/Level_1/story_1/slow/slide_2/M2.mp3',
        '/assets/audio/story-telling/Level_1/story_1/slow/slide_3/M3.mp3',
        '/assets/audio/story-telling/Level_1/story_1/slow/slide_4/M4.mp3',
        '/assets/audio/story-telling/Level_1/story_1/slow/slide_6/M6.mp3',
        '/assets/audio/story-telling/Level_1/story_1/slow/slide_7/M7.mp3',
        '/assets/audio/story-telling/Level_1/story_1/slow/slide_10/M10.mp3',
        
        // Fast speed slides (only slides that exist: 1,2,3,4,6)
        '/assets/audio/story-telling/Level_1/story_1/fast/slide_1/M1.mp3',
        '/assets/audio/story-telling/Level_1/story_1/fast/slide_2/M2.mp3',
        '/assets/audio/story-telling/Level_1/story_1/fast/slide_3/M3.mp3',
        '/assets/audio/story-telling/Level_1/story_1/fast/slide_4/M4.mp3',
        '/assets/audio/story-telling/Level_1/story_1/fast/slide_6/M6.mp3'
      ]
    },
    'story1-2': {
      images: [
        '/converted/assets/LEVEL_1/STORY_2/PIC1.webp',
        '/converted/assets/LEVEL_1/STORY_2/PIC2.webp',
        '/converted/assets/LEVEL_1/STORY_2/PIC3.webp',
        '/converted/assets/LEVEL_1/STORY_2/PIC4.webp',
        '/converted/assets/LEVEL_1/STORY_2/PIC5.webp',
        '/converted/assets/LEVEL_1/STORY_2/PIC6.webp'
      ],
      audio: [
        // Title audio
        '/assets/audio/story-telling/Level_1/story_2/title/CANDIES AND CANDIES TITLE.mp3',
        
        // Normal speed (only slides that exist: 1,2,3,5,8,9)
        '/assets/audio/story-telling/Level_1/story_2/normal/slide_1/M1.mp3',
        '/assets/audio/story-telling/Level_1/story_2/normal/slide_2/M2.mp3',
        '/assets/audio/story-telling/Level_1/story_2/normal/slide_3/M3.mp3',
        '/assets/audio/story-telling/Level_1/story_2/normal/slide_5/M5.mp3',
        '/assets/audio/story-telling/Level_1/story_2/normal/slide_8/M8.mp3',
        '/assets/audio/story-telling/Level_1/story_2/normal/slide_9/M9.mp3',
        
        // Slow speed (only slides that exist: 1,2,3,5,8,9)
        '/assets/audio/story-telling/Level_1/story_2/slow/slide_1/M1.mp3',
        '/assets/audio/story-telling/Level_1/story_2/slow/slide_2/M2.mp3',
        '/assets/audio/story-telling/Level_1/story_2/slow/slide_3/M3.mp3',
        '/assets/audio/story-telling/Level_1/story_2/slow/slide_5/M5.mp3',
        '/assets/audio/story-telling/Level_1/story_2/slow/slide_8/M8.mp3',
        '/assets/audio/story-telling/Level_1/story_2/slow/slide_9/M9.mp3',
        
        // Fast speed (only slides that exist: 1,2,3,5,8,9)
        '/assets/audio/story-telling/Level_1/story_2/fast/slide_1/M1.mp3',
        '/assets/audio/story-telling/Level_1/story_2/fast/slide_2/M2.mp3',
        '/assets/audio/story-telling/Level_1/story_2/fast/slide_3/M3.mp3',
        '/assets/audio/story-telling/Level_1/story_2/fast/slide_5/M5.mp3',
        '/assets/audio/story-telling/Level_1/story_2/fast/slide_8/M8.mp3',
        '/assets/audio/story-telling/Level_1/story_2/fast/slide_9/M9.mp3'
      ]
    },
    'story1-3': {
      images: [
        '/converted/assets/LEVEL_1/STORY_3/PIC1.webp',
        '/converted/assets/LEVEL_1/STORY_3/PIC2.webp',
        '/converted/assets/LEVEL_1/STORY_3/PIC3.webp',
        '/converted/assets/LEVEL_1/STORY_3/PIC4.webp',
        '/converted/assets/LEVEL_1/STORY_3/PIC5.webp',
        '/converted/assets/LEVEL_1/STORY_3/PIC6.webp'
      ],
      audio: [
        // Title audio
        '/assets/audio/story-telling/Level_1/story_3/title/HANNAH, THE HONEST VENDOR TITLE.mp3',
        
        // Normal speed (only slides that exist: 1,2,3,5,8,9)
        '/assets/audio/story-telling/Level_1/story_3/normal/slide_1/M1.mp3',
        '/assets/audio/story-telling/Level_1/story_3/normal/slide_2/M2.mp3',
        '/assets/audio/story-telling/Level_1/story_3/normal/slide_3/M3.mp3',
        '/assets/audio/story-telling/Level_1/story_3/normal/slide_5/M5.mp3',
        '/assets/audio/story-telling/Level_1/story_3/normal/slide_8/M8.mp3',
        '/assets/audio/story-telling/Level_1/story_3/normal/slide_9/M9.mp3',
        
        // Slow speed (only slides that exist: 1,2,3,5,8,9)
        '/assets/audio/story-telling/Level_1/story_3/slow/slide_1/M1.mp3',
        '/assets/audio/story-telling/Level_1/story_3/slow/slide_2/M2.mp3',
        '/assets/audio/story-telling/Level_1/story_3/slow/slide_3/M3.mp3',
        '/assets/audio/story-telling/Level_1/story_3/slow/slide_5/M5.mp3',
        '/assets/audio/story-telling/Level_1/story_3/slow/slide_8/M8.mp3',
        '/assets/audio/story-telling/Level_1/story_3/slow/slide_9/M9.mp3',
        
        // Fast speed (only slides that exist: 1,2,3,5,8,9)
        '/assets/audio/story-telling/Level_1/story_3/fast/slide_1/M1.mp3',
        '/assets/audio/story-telling/Level_1/story_3/fast/slide_2/M2.mp3',
        '/assets/audio/story-telling/Level_1/story_3/fast/slide_3/M3.mp3',
        '/assets/audio/story-telling/Level_1/story_3/fast/slide_5/M5.mp3',
        '/assets/audio/story-telling/Level_1/story_3/fast/slide_8/M8.mp3',
        '/assets/audio/story-telling/Level_1/story_3/fast/slide_9/M9.mp3'
      ]
    }
  },
  
  Level2: {
    'story2-1': {
      images: [
        '/converted/assets/LEVEL_2/STORY_1/PIC1.webp',
        '/converted/assets/LEVEL_2/STORY_1/PIC2.webp',
        '/converted/assets/LEVEL_2/STORY_1/PIC3.webp',
        '/converted/assets/LEVEL_2/STORY_1/PIC4.webp',
        '/converted/assets/LEVEL_2/STORY_1/PIC5.webp',
        '/converted/assets/LEVEL_2/STORY_1/PIC6.webp'
      ],
      audio: [
        '/assets/audio/story-telling/Level_2/story_1/title/english.mp3',
        '/assets/audio/story-telling/Level_2/story_1/title/cebuano.mp3',
        
        ...Array.from({ length: 6 }, (_, i) => 
          `/assets/audio/story-telling/Level_2/story_1/normal/slide_${i + 1}/M${i + 1}.mp3`
        ),
        ...Array.from({ length: 6 }, (_, i) => 
          `/assets/audio/story-telling/Level_2/story_1/slow/slide_${i + 1}/M${i + 1}.mp3`
        ),
        ...Array.from({ length: 6 }, (_, i) => 
          `/assets/audio/story-telling/Level_2/story_1/fast/slide_${i + 1}/M${i + 1}.mp3`
        )
      ]
    },
    'story2-2': {
      images: [
        '/converted/assets/LEVEL_2/STORY_2/PIC1.webp',
        '/converted/assets/LEVEL_2/STORY_2/PIC2.webp',
        '/converted/assets/LEVEL_2/STORY_2/PIC3.webp',
        '/converted/assets/LEVEL_2/STORY_2/PIC4.webp',
        '/converted/assets/LEVEL_2/STORY_2/PIC5.webp',
        '/converted/assets/LEVEL_2/STORY_2/PIC6.webp'
      ],
      audio: [
        '/assets/audio/story-telling/Level_2/story_2/title/english.mp3',
        '/assets/audio/story-telling/Level_2/story_2/title/cebuano.mp3',
        
        ...Array.from({ length: 6 }, (_, i) => 
          `/assets/audio/story-telling/Level_2/story_2/normal/slide_${i + 1}/M${i + 1}.mp3`
        ),
        ...Array.from({ length: 6 }, (_, i) => 
          `/assets/audio/story-telling/Level_2/story_2/slow/slide_${i + 1}/M${i + 1}.mp3`
        ),
        ...Array.from({ length: 6 }, (_, i) => 
          `/assets/audio/story-telling/Level_2/story_2/fast/slide_${i + 1}/M${i + 1}.mp3`
        )
      ]
    },
    'story2-3': {
      images: [
        '/converted/assets/LEVEL_2/STORY_3/Pic1.webp',
        '/converted/assets/LEVEL_2/STORY_3/Pic2.webp',
        '/converted/assets/LEVEL_2/STORY_3/Pic3.webp',
        '/converted/assets/LEVEL_2/STORY_3/Pic4.webp',
        '/converted/assets/LEVEL_2/STORY_3/Pic5.webp',
        '/converted/assets/LEVEL_2/STORY_3/Pic6.webp',
        '/converted/assets/LEVEL_2/STORY_3/Pic7.webp',
        '/converted/assets/LEVEL_2/STORY_3/Pic8.webp'
      ],
      audio: [
        '/assets/audio/story-telling/Level_2/story_3/title/english.mp3',
        '/assets/audio/story-telling/Level_2/story_3/title/cebuano.mp3',
        
        ...Array.from({ length: 8 }, (_, i) => 
          `/assets/audio/story-telling/Level_2/story_3/normal/slide_${i + 1}/M${i + 1}.mp3`
        ),
        ...Array.from({ length: 8 }, (_, i) => 
          `/assets/audio/story-telling/Level_2/story_3/slow/slide_${i + 1}/M${i + 1}.mp3`
        ),
        ...Array.from({ length: 8 }, (_, i) => 
          `/assets/audio/story-telling/Level_2/story_3/fast/slide_${i + 1}/M${i + 1}.mp3`
        )
      ]
    }
  },
  
  Level3: {
    'story3-1': {
      images: [
        '/converted/assets/LEVEL_3/STORY_1/PIC1.webp',
        '/converted/assets/LEVEL_3/STORY_1/PIC2.webp',
        '/converted/assets/LEVEL_3/STORY_1/PIC3.webp',
        '/converted/assets/LEVEL_3/STORY_1/PIC4.webp',
        '/converted/assets/LEVEL_3/STORY_1/PIC5.webp',
        '/converted/assets/LEVEL_3/STORY_1/PIC6.webp'
      ],
      audio: [
        '/assets/audio/story-telling/Level_3/story_1/title/english.mp3',
        '/assets/audio/story-telling/Level_3/story_1/title/cebuano.mp3',
        
        ...Array.from({ length: 6 }, (_, i) => 
          `/assets/audio/story-telling/Level_3/story_1/normal/slide_${i + 1}/M${i + 1}.mp3`
        ),
        ...Array.from({ length: 6 }, (_, i) => 
          `/assets/audio/story-telling/Level_3/story_1/slow/slide_${i + 1}/M${i + 1}.mp3`
        ),
        ...Array.from({ length: 6 }, (_, i) => 
          `/assets/audio/story-telling/Level_3/story_1/fast/slide_${i + 1}/M${i + 1}.mp3`
        )
      ]
    },
    'story3-2': {
      images: [
        '/converted/assets/LEVEL_3/STORY_2/PIC1.webp',
        '/converted/assets/LEVEL_3/STORY_2/PIC2.webp',
        '/converted/assets/LEVEL_3/STORY_2/PIC3.webp',
        '/converted/assets/LEVEL_3/STORY_2/PIC4.webp',
        '/converted/assets/LEVEL_3/STORY_2/PIC5.webp',
        '/converted/assets/LEVEL_3/STORY_2/PIC6.webp'
      ],
      audio: [
        '/assets/audio/story-telling/Level_3/story_2/title/english.mp3',
        '/assets/audio/story-telling/Level_3/story_2/title/cebuano.mp3',
        
        ...Array.from({ length: 6 }, (_, i) => 
          `/assets/audio/story-telling/Level_3/story_2/normal/slide_${i + 1}/M${i + 1}.mp3`
        ),
        ...Array.from({ length: 6 }, (_, i) => 
          `/assets/audio/story-telling/Level_3/story_2/slow/slide_${i + 1}/M${i + 1}.mp3`
        ),
        ...Array.from({ length: 6 }, (_, i) => 
          `/assets/audio/story-telling/Level_3/story_2/fast/slide_${i + 1}/M${i + 1}.mp3`
        )
      ]
    },
    'story3-3': {
      images: [
        '/converted/assets/LEVEL_3/STORY_3/pic1.webp',
        '/converted/assets/LEVEL_3/STORY_3/pic2.webp',
        '/converted/assets/LEVEL_3/STORY_3/pic3.webp',
        '/converted/assets/LEVEL_3/STORY_3/pic4.webp',
        '/converted/assets/LEVEL_3/STORY_3/pic5.webp',
        '/converted/assets/LEVEL_3/STORY_3/pic6.webp'
      ],
      audio: [
        '/assets/audio/story-telling/Level_3/story_3/title/english.mp3',
        '/assets/audio/story-telling/Level_3/story_3/title/cebuano.mp3',
        
        ...Array.from({ length: 6 }, (_, i) => 
          `/assets/audio/story-telling/Level_3/story_3/normal/slide_${i + 1}/M${i + 1}.mp3`
        ),
        ...Array.from({ length: 6 }, (_, i) => 
          `/assets/audio/story-telling/Level_3/story_3/slow/slide_${i + 1}/M${i + 1}.mp3`
        ),
        ...Array.from({ length: 6 }, (_, i) => 
          `/assets/audio/story-telling/Level_3/story_3/fast/slide_${i + 1}/M${i + 1}.mp3`
        )
      ]
    }
  }
};

// Track which levels have been preloaded in this session
const preloadedLevels = new Set<string>();

/**
 * Preload all assets for a specific level
 * @param level - The level name (Level1, Level2, Level3)
 * @param onProgress - Callback for progress updates
 * @param forceReload - Force reload even if already preloaded
 * @returns Promise that resolves when all assets are loaded
 */
export async function preloadLevelAssets(
  level: string,
  onProgress?: (loaded: number, total: number, type: 'image' | 'audio', url: string) => void,
  forceReload: boolean = false
): Promise<void> {
  // Skip if already preloaded in this session (unless forced)
  if (!forceReload && preloadedLevels.has(level)) {
    console.log(`✅ ${level} already preloaded in this session, skipping...`);
    const levelAssets = STORY_ASSETS[level];
    if (levelAssets && onProgress) {
      // Simulate instant completion for progress bar
      const total = Object.values(levelAssets).reduce((sum, story) => 
        sum + story.images.length + story.audio.length, 0
      );
      onProgress(total, total, 'audio', '');
    }
    return;
  }

  const levelAssets = STORY_ASSETS[level];
  if (!levelAssets) {
    console.warn(`No assets found for level: ${level}`);
    return;
  }

  const allAssets: { type: 'image' | 'audio'; url: string }[] = [];

  // Collect all assets from all stories in this level
  Object.values(levelAssets).forEach(story => {
    story.images.forEach(url => allAssets.push({ type: 'image', url }));
    story.audio.forEach(url => allAssets.push({ type: 'audio', url }));
  });

  let loaded = 0;
  const total = allAssets.length;

  console.log(`Preloading ${total} assets for ${level}...`);

  // Preload all assets in parallel
  const promises = allAssets.map(({ type, url }) => {
    return new Promise<void>((resolve) => {
      if (type === 'image') {
        const img = new Image();
        img.onload = () => {
          loaded++;
          if (onProgress) onProgress(loaded, total, type, url);
          resolve();
        };
        img.onerror = () => {
          console.warn(`Failed to load image: ${url}`);
          loaded++;
          if (onProgress) onProgress(loaded, total, type, url);
          resolve(); // Continue even if one fails
        };
        img.src = url;
      } else {
        // Preload audio with timeout
        const audio = new Audio();
        audio.preload = 'auto';
        
        let resolved = false;
        
        // Set a timeout to prevent hanging (30 seconds max per audio file)
        const timeout = setTimeout(() => {
          if (!resolved) {
            console.warn(`Audio load timeout: ${url}`);
            resolved = true;
            loaded++;
            if (onProgress) onProgress(loaded, total, type, url);
            resolve();
          }
        }, 30000);
        
        audio.oncanplaythrough = () => {
          if (!resolved) {
            resolved = true;
            clearTimeout(timeout);
            loaded++;
            if (onProgress) onProgress(loaded, total, type, url);
            resolve();
          }
        };
        audio.onerror = () => {
          if (!resolved) {
            console.warn(`Failed to load audio: ${url}`);
            resolved = true;
            clearTimeout(timeout);
            loaded++;
            if (onProgress) onProgress(loaded, total, type, url);
            resolve(); // Continue even if one fails
          }
        };
        audio.src = url;
      }
    });
  });

  await Promise.all(promises);
  console.log(`✅ All ${total} assets preloaded for ${level}`);
  
  // Mark this level as preloaded
  preloadedLevels.add(level);
}

/**
 * Preload assets for a specific story only
 * @param level - The level name (Level1, Level2, Level3)
 * @param storyKey - The story key (story1-1, story1-2, etc.)
 * @param onProgress - Callback for progress updates
 * @returns Promise that resolves when all assets are loaded
 */
export async function preloadStoryAssets(
  level: string,
  storyKey: string,
  onProgress?: (loaded: number, total: number, type: 'image' | 'audio', url: string) => void
): Promise<void> {
  const storyAssets = STORY_ASSETS[level]?.[storyKey];
  if (!storyAssets) {
    console.warn(`No assets found for ${level}/${storyKey}`);
    return;
  }

  const allAssets: { type: 'image' | 'audio'; url: string }[] = [];
  storyAssets.images.forEach(url => allAssets.push({ type: 'image', url }));
  storyAssets.audio.forEach(url => allAssets.push({ type: 'audio', url }));

  let loaded = 0;
  const total = allAssets.length;

  console.log(`Preloading ${total} assets for ${level}/${storyKey}...`);

  const promises = allAssets.map(({ type, url }) => {
    return new Promise<void>((resolve) => {
      if (type === 'image') {
        const img = new Image();
        img.onload = () => {
          loaded++;
          if (onProgress) onProgress(loaded, total, type, url);
          resolve();
        };
        img.onerror = () => {
          console.warn(`Failed to load image: ${url}`);
          loaded++;
          if (onProgress) onProgress(loaded, total, type, url);
          resolve();
        };
        img.src = url;
      } else {
        // Preload audio with timeout
        const audio = new Audio();
        audio.preload = 'auto';
        
        let resolved = false;
        
        // Set a timeout to prevent hanging (30 seconds max per audio file)
        const timeout = setTimeout(() => {
          if (!resolved) {
            console.warn(`Audio load timeout: ${url}`);
            resolved = true;
            loaded++;
            if (onProgress) onProgress(loaded, total, type, url);
            resolve();
          }
        }, 30000);
        
        audio.oncanplaythrough = () => {
          if (!resolved) {
            resolved = true;
            clearTimeout(timeout);
            loaded++;
            if (onProgress) onProgress(loaded, total, type, url);
            resolve();
          }
        };
        audio.onerror = () => {
          if (!resolved) {
            console.warn(`Failed to load audio: ${url}`);
            resolved = true;
            clearTimeout(timeout);
            loaded++;
            if (onProgress) onProgress(loaded, total, type, url);
            resolve();
          }
        };
        audio.src = url;
      }
    });
  });

  await Promise.all(promises);
  console.log(`✅ All ${total} assets preloaded for ${level}/${storyKey}`);
}
