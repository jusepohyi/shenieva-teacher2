// @ts-nocheck
import { writable } from 'svelte/store';
import { createQuiz2Store } from './quiz2_taking';

let quiz2StoreInstance = null;

export function initializeQuiz2Store(quizData) {
  if (!quiz2StoreInstance) {
    quiz2StoreInstance = createQuiz2Store(quizData);
    console.log('Initialized singleton quiz2Store, attempts:', quiz2StoreInstance ? quiz2StoreInstance.attempts : 'null');
  }
  return quiz2StoreInstance;
}

export function getQuiz2Store() {
  return quiz2StoreInstance;
}

export function resetQuiz2Store() {
  quiz2StoreInstance = null;
  localStorage.removeItem('quiz2_attempts');
  localStorage.removeItem('quiz2_answers');
  localStorage.removeItem('quiz2_data');
  console.log('Reset quiz2Store');
}