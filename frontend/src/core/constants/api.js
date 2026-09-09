export const API_BASE = '/api';

export const ENDPOINTS = {
  // Auth
  register:   `${API_BASE}/auth/register`,
  login:      `${API_BASE}/auth/login`,
  verify2fa:  `${API_BASE}/auth/verify-2fa`,
  logout:     `${API_BASE}/auth/logout`,
  me:         `${API_BASE}/auth/me`,

  // Master
  departments: `${API_BASE}/master/departments`,
  courses:     `${API_BASE}/master/courses`,
  classes:     `${API_BASE}/master/classes`,
  semesters:   `${API_BASE}/master/semesters`,

  // Students
  students:    `${API_BASE}/students`,
  student: (id) => `${API_BASE}/students/${id}`,

  // Attendance
  attendance:       `${API_BASE}/attendance`,
  attendanceRecap:  `${API_BASE}/attendance/recap`,
  attendanceById:   (id) => `${API_BASE}/attendance/${id}`,

  // Dashboard
  dashboard: `${API_BASE}/dashboard`,
};
