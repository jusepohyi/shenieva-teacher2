// src/routes/admin/profile/+page.server.ts
import type { PageServerLoad } from './$types';
import { redirect } from '@sveltejs/kit';
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

export const load: PageServerLoad = async ({ cookies }) => {
  const teacherId = cookies.get('teacherId');
  console.log('Profile load - Teacher ID from cookie:', teacherId);

  if (!teacherId) {
    console.log('No teacherId found, redirecting to login');
    throw redirect(303, '/login');
  }

  let connection;
  try {
    console.log('Attempting database connection for profile...');
    connection = await mysql.createConnection(dbConfig);
    await connection.connect();
    console.log('Database connected successfully');

    const [rows] = await connection.execute<Teacher[]>(
      'SELECT * FROM teacher_table WHERE pk_teacherID = ?',
      [teacherId]
    );
    console.log('Profile query result:', rows);

    if (!Array.isArray(rows) || rows.length === 0) {
      console.log('No teacher found for teacherId:', teacherId);
      throw redirect(303, '/login');
    }

    const teacher = rows[0];
    console.log('Teacher data:', {
      id: teacher.pk_teacherID,
      name: teacher.teacherName,
      email: teacher.teacherEmail,
      idNo: teacher.idNo
    });

    return {
      name: teacher.teacherName,
      email: teacher.teacherEmail,
      role: 'Teacher',
      userId: teacher.idNo
    };
  } catch (error) {
    console.error('Profile load error:', error);
    throw redirect(303, '/login'); // Redirect instead of 500
  } finally {
    if (connection) {
      await connection.end().catch(err => console.error('Error closing connection:', err));
    }
  }
};