// Temporary client hook to disable audio features across the app.
// Creating this file stubs the global Audio constructor so that
// `new Audio(...)` returns a harmless stub with play/pause methods.
// To re-enable audio, remove or edit this file and set `AUDIO_ENABLED = true`.

const AUDIO_ENABLED = false; // flip to true to re-enable audio playback

if (typeof window !== 'undefined' && !AUDIO_ENABLED) {
  // Preserve original in case we need to restore it dynamically.
  (window as any).__OriginalAudio = (window as any).Audio;

  class StubAudio {
    src: string;
    currentTime = 0;
    paused = true;
    volume = 1;
    constructor(src?: string) {
      this.src = src || '';
    }
    play() {
      this.paused = false;
      // return a resolved promise to match HTMLMediaElement.play()
      return Promise.resolve();
    }
    pause() {
      this.paused = true;
    }
    addEventListener() {}
    removeEventListener() {}
  }

  try {
    (window as any).Audio = StubAudio;
  } catch (e) {
    // In some strict environments assignment may fail; ignore silently.
    console.warn('Could not stub Audio:', e);
  }
}
