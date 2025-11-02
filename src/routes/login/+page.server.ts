// src/routes/login/+page.server.ts
import { fail } from '@sveltejs/kit';
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
  teacherGender: 'Male' | 'Female';
}

export const actions = {
  default: async ({ request, cookies }) => {
    let connection;
    try {
      const formData = await request.formData();
      const userType = formData.get('userType')?.toString();
      const idNo = formData.get('idNo')?.toString();
      const password = formData.get('password')?.toString();

      console.log('Received:', { userType, idNo, password });

      if (!userType || !idNo || !password) {
        console.log('Missing fields detected');
        return fail(400, { error: 'Missing required fields' });
      }

      if (userType === 'teacher') {
        connection = await mysql.createConnection(dbConfig);
        await connection.connect();
        console.log('Database connected');

        // Query with case-insensitive ID search using LOWER()
        const [rows] = await connection.execute<Teacher[]>(
          'SELECT * FROM teacher_table WHERE LOWER(idNo) = LOWER(?)',
          [idNo]
        );
        console.log('Query result:', rows);

        if (!Array.isArray(rows) || rows.length === 0) {
          console.log('No teacher found for idNo:', idNo);
          return fail(401, { error: 'Invalid teacher ID or password' });
        }

        const teacher = rows[0];
        console.log('Teacher data:', { idNo: teacher.idNo, teacherPass: teacher.teacherPass });

        // Case-sensitive password comparison using strict equality
        if (password !== teacher.teacherPass) {
          console.log('Password mismatch. Expected:', teacher.teacherPass, 'Received:', password);
          return fail(401, { error: 'Invalid teacher ID or password' });
        }

        console.log('Authentication successful for', teacher.idNo);
        cookies.set('teacherId', teacher.pk_teacherID.toString(), {
          path: '/',
          httpOnly: true,
          secure: process.env.NODE_ENV === 'production',
          maxAge: 60 * 60 * 24
        });

        const response = {
          success: true,
          userType: 'teacher',
          teacherId: teacher.pk_teacherID,
          teacherName: teacher.teacherName
        };
        console.log('Returning:', JSON.stringify(response, null, 2));
        return response;
      }

      console.log('Unhandled userType:', userType);
      return fail(400, { error: 'Student login not handled on server' });
    } catch (err) {
      console.error('Server error:', err);
      return fail(500, { error: 'Internal server error', details: err.message });
    } finally {
      if (connection) {
        await connection.end().catch(err => console.error('Error closing connection:', err));
      }
    }
  }
};