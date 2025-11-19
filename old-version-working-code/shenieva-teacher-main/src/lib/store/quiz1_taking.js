// src/lib/store/quiz1_taking.js
import { writable } from 'svelte/store';
import { browser } from '$app/environment';

const STORAGE_KEY = 'quiz1_taking_state';

const initialState = {
    quizTake: 1,
    maxTakes: 3,
    score: 0,
    totalPoints: 0,
    quizCompleted: false,
    showModal: false
};

const getInitialState = () => {
    if (browser) {
        const savedState = localStorage.getItem(STORAGE_KEY);
        return savedState ? JSON.parse(savedState) : initialState;
    }
    return initialState;
};

export const quiz1Taking = writable(getInitialState());

if (browser) {
    quiz1Taking.subscribe(state => {
        localStorage.setItem(STORAGE_KEY, JSON.stringify(state));
    });
}

export function resetQuiz1() {
    quiz1Taking.update(state => ({
        ...state,
        quizTake: state.quizTake < state.maxTakes ? state.quizTake + 1 : state.quizTake,
        score: 0,
        quizCompleted: false,
        showModal: false
    }));
}

export function submitQuiz1(newScore, totalPoints) {
    quiz1Taking.update(state => ({
        ...state,
        score: newScore,
        totalPoints: totalPoints,
        quizCompleted: true,
        showModal: newScore < totalPoints
    }));
}

export function closeModal1() {
    quiz1Taking.update(state => ({
        ...state,
        showModal: false
    }));
}

// Updated clear function to also remove from localStorage
export function clearQuiz1State() {
    quiz1Taking.set(initialState);
    if (browser) {
        localStorage.removeItem(STORAGE_KEY); // Clear from localStorage
    }
}