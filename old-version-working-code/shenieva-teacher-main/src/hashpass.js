const bcrypt = require('bcryptjs');

console.log('Script starting...');
const plaintextPassword = 'password123';
bcrypt.hash(plaintextPassword, 10, (err, hash) => {
  if (err) {
    console.error('Error:', err);
    return;
  }
  console.log('Generated hash:', hash);
  process.exit(0);
});