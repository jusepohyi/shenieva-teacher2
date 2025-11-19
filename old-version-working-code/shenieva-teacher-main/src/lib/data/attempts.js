// Simple client-side attempt recorder. Stores attempt records in localStorage under 'attemptRecords'.
// Each attempt record: { id, studentId, storyKey, answers, score, retakeCount, timestamp }

export function addAttempt(record) {
  try {
    const raw = localStorage.getItem('attemptRecords');
    const arr = raw ? JSON.parse(raw) : [];
    arr.push({ ...record, id: `${Date.now()}-${Math.floor(Math.random()*10000)}` });
    localStorage.setItem('attemptRecords', JSON.stringify(arr));
    return true;
  } catch (e) {
    console.warn('Failed to save attempt record', e);
    return false;
  }
}

export function getAttempts() {
  try {
    const raw = localStorage.getItem('attemptRecords');
    return raw ? JSON.parse(raw) : [];
  } catch (e) {
    console.warn('Failed to read attempt records', e);
    return [];
  }
}
