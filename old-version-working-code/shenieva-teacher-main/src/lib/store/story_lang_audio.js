import { writable } from 'svelte/store';

// Controls for story audio features. Set audioEnabled to false to hide
// audio controls in the UI. Audio playback is globally stubbed in
// `src/hooks.client.ts` when we want audio disabled entirely.
export const audioEnabled = writable(false);
export const language = writable('english');
export const isFast = writable(true);