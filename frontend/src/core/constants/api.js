export const API_BASE = '/api';

export const ENDPOINTS = {
  // Auth
  register:   `${API_BASE}/auth/register`,
  login:      `${API_BASE}/auth/login`,
  verify2fa:  `${API_BASE}/auth/verify-2fa`,
  logout:     `${API_BASE}/auth/logout`,
  me:         `${API_BASE}/auth/me`,

  // Master — read
  years:       `${API_BASE}/master/years`,
  departments: `${API_BASE}/master/departments`,
  courses:     `${API_BASE}/master/courses`,
  classes:     `${API_BASE}/master/classes`,
  semesters:   `${API_BASE}/master/semesters`,

  // Master — write (departments)
  departmentStore:   `${API_BASE}/master/departments`,
  departmentUpdate:  (id) => `${API_BASE}/master/departments/${id}`,
  departmentDestroy: (id) => `${API_BASE}/master/departments/${id}`,

  // Master — write (courses)
  courseStore:   `${API_BASE}/master/courses`,
  courseUpdate:  (id) => `${API_BASE}/master/courses/${id}`,
  courseDestroy: (id) => `${API_BASE}/master/courses/${id}`,

  // Master — write (classes)
  classStore:   `${API_BASE}/master/classes`,
  classUpdate:  (id) => `${API_BASE}/master/classes/${id}`,
  classDestroy: (id) => `${API_BASE}/master/classes/${id}`,

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
