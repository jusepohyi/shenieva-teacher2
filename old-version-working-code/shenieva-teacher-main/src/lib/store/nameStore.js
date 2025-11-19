// src/lib/store/nameStore.js
import { writable } from "svelte/store";
import { browser } from '$app/environment';

const storedName = browser ? localStorage.getItem('name') : null;
const initialName = storedName !== null ? storedName : "";

/** @type {import('svelte/store').Writable<string>} */
export const name = writable(initialName);

if (browser) {
  name.subscribe(value => {
    localStorage.setItem('name', value);
  });
}

// Function to reset the name store
export function resetName() {
  name.set(""); // Reset to initial value
  if (browser) {
    localStorage.removeItem('name'); // Clear from localStorage
  }
}