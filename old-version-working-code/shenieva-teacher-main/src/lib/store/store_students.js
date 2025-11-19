// removed markdown fence (was ```javascript)
// import { writable, derived } from 'svelte/store';

// import { writable, derived } from 'svelte/store';

// /**
//  * @typedef {Object} Student
//  * @property {string} pk_studentID
//  * @property {string} idNo
//  * @property {string} studentName
//  * @property {string} studentPass
//  * @property {string} studentGender
//  * @property {string} studentLevel
//  * @property {string} studentRibbon
//  * @property {string} studentColtrash
//  * @property {string} studentProgress
//  */

// /** @type {import('svelte/store').Writable<Student[]>} */
// export const studentData = writable([]);

// /** @type {import('svelte/store').Readable<string[]>} */
// export const studentNames = derived(studentData, ($studentData) => {
//   return $studentData.map((student) => student.studentName);
// });
// src/lib/store/store_students.js

// src/lib/store/store_students.js
// removed markdown fence end (was ```)

// simple store for students
import { writable, derived } from 'svelte/store';
import { browser } from '$app/environment';

const storedData = browser ? localStorage.getItem('students') : null;
const initialData = storedData ? JSON.parse(storedData) : [];

export const studentData = writable(initialData);

if (browser) {
  studentData.subscribe((value) => {
    localStorage.setItem('students', JSON.stringify(value));
  });
}

export const studentNames = derived(studentData, ($studentData) => $studentData.map((s) => s.studentName));