// src/routes/api/update-profile/+server.ts
import type { RequestHandler } from './$types'; // Correct import for SvelteKit
import { json, error } from '@sveltejs/kit';
import mysql from 'mysql2/promise';

const dbConfig = {
  host: process.env.DB_HOST || 'localhost',
  user: process.env.DB_USER || 'root',
  password: process.env.DB_PASS || '',
  database: process.env.DB_NAME || 'shenieva_db'
};

interface Teacher extends mysql.RowDataPacket {
  pk_teacherID: number;
  idNo: string;
  teacherName: string;
  teacherEmail: string;
  teacherPass: string;
  teacherGender: 'Male' | 'Female' | 'Other';
}

export const POST: RequestHandler = async ({ request, cookies }) => {
  const teacherId = cookies.get('teacherId');
  if (!teacherId) {
    throw error(401, 'Unauthorized: Please log in');
  }

  const { name, email, currentPassword, newPassword } = await request.json();

  let connection;
  try {
    connection = await mysql.createConnection(dbConfig);
    await connection.connect();

    // Verify current password
    const [rows] = await connection.execute<Teacher[]>(
      'SELECT teacherPass FROM teacher_table WHERE pk_teacherID = ?',
      [teacherId]
    );
    if (!rows.length || rows[0].teacherPass !== currentPassword) {
      throw error(401, 'Incorrect current password');
    }

    // Build update query dynamically
    const updates: string[] = [];
    const values: (string | number)[] = [];

    if (name && name !== rows[0].teacherName) {
      updates.push('teacherName = ?');
      values.push(name);
    }
    if (email && email !== rows[0].teacherEmail) {
      updates.push('teacherEmail = ?');
      values.push(email);
    }
    if (newPassword) {
      updates.push('teacherPass = ?');
      values.push(newPassword);
    }

    if (updates.length === 0) {
      return json({ success: true, message: 'No changes to save' });
    }

    values.push(teacherId);
    const query = `UPDATE teacher_table SET ${updates.join(', ')} WHERE pk_teacherID = ?`;
    await connection.execute(query, values);

    return json({ success: true, message: 'Profile updated successfully' });
  } catch (err) {
    console.error('Update profile error:', err);
    throw error(500, err instanceof Error ? err.message : 'Failed to update profile');
  } finally {
    if (connection) {
      await connection.end().catch(err => console.error('Error closing connection:', err));
    }
  }
};