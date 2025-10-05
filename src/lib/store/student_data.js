import { writable } from 'svelte/store';
import { browser } from '$app/environment';

/**
 * @typedef {Object} StudentData
 * @property {number} pk_studentID
 * @property {string} idNo
 * @property {string} studentName
 * @property {string} studentPass
 * @property {'Male' | 'Female'} studentGender
 * @property {number} studentLevel
 * @property {number | null} studentRibbon
 * @property {number | null} studentColtrash
 * @property {number | null} studentProgress
 * @property {Record<string, string>} answeredQuestions
 */

const storedData = browser ? localStorage.getItem('studentData') : null;
const initialData = storedData !== null ? JSON.parse(storedData) : null;

/** @type {import('svelte/store').Writable<StudentData | null>} */
export const studentData = writable(initialData);

if (browser) {
  studentData.subscribe(value => {
    localStorage.setItem('studentData', JSON.stringify(value));
  });
}

export function resetStudentData() {
  studentData.set(null);
  if (browser) {
    localStorage.removeItem('studentData');
    
    // Remove all retake-related keys
    const storyKeys = ['story1-1', 'story1-2', 'story1-3'];
    storyKeys.forEach(key => {
      localStorage.removeItem(`retake${key}Count`);
      localStorage.removeItem(`retake${key}`);
    });
    
    // Remove any level-based retake keys
    const levelKeys = ['Level1', 'Level2', 'Level3'];
    levelKeys.forEach(level => {
      localStorage.removeItem(`retake${level}`);
      localStorage.removeItem(`retake${level}Count`);
    });
    
    // Remove modal and progress keys
    localStorage.removeItem('openStory1Modal');
    localStorage.removeItem('retakeLevel1');
    
    // Clear any other retake-related keys that might exist
    Object.keys(localStorage).forEach(key => {
      if (key.startsWith('retake') || key.includes('retake')) {
        localStorage.removeItem(key);
      }
    });
  }
}

/**
 * Reset saved answers for specific prefixes
 * @param {string | string[]} prefixes - One or more key prefixes to clear (e.g., 'story1-1_')
 */
export function resetAnswersByPrefix(prefixes) {
  const list = Array.isArray(prefixes) ? prefixes : [prefixes];
  studentData.update((data) => {
    if (!data) return data;
    const answered = data.answeredQuestions || {};
    const filtered = Object.fromEntries(
      Object.entries(answered).filter(([k]) => !list.some((p) => k.startsWith(p)))
    );
    return { ...data, answeredQuestions: filtered };
  });
}

/**
 * Reset all saved answers for a given level number or story key.
 * Examples:
 *  - resetLevelAnswers(1) clears keys starting with 'story1_'
 *  - resetLevelAnswers('story1-1') clears keys starting with 'story1-1_'
 * @param {number | string} levelOrStory
 */
export function resetLevelAnswers(levelOrStory) {
  const prefix = typeof levelOrStory === 'number' ? `story${levelOrStory}_` : `${levelOrStory}_`;
  resetAnswersByPrefix(prefix);
}