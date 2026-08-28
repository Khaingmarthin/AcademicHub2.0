USE ucsmtla_academic_hub;

INSERT INTO university_profile
(
    name,
    short_name,
    history,
    vision,
    mission,
    rector_message,
    established_year,
    logo,
    hero_media,
    address,
    phone,
    email,
    admission_description
)
VALUES
(
    'University of Computer Studies, Meiktila',
    'UCSMTLA',
    'In October 2001, UCS-Mtla was established as government Computer College (GCC). In January 2007, it has been promoted from GCC to University of Computer Studies (Meiktila).',
    'To nurture computer technicians to support national human resource development.

To elevate the curriculum to international standards.

To upgrade classrooms, libraries, workshops, and teaching tools.

To train IT professionals to drive private-sector business and job growth.',
    '

To provide facilities for teacher improvement seminars and projects.

To use student-centered teaching to produce outstanding graduates.

To enhance student skills through regular practical tests, seminars, and projects.

To equip the institution with modern teaching aids.

To send qualified candidates abroad for advanced university studies.',
    'Welcome to the University of Computer Studies, Meiktila. Our university is committed to providing quality education and developing future professionals in computing and technology.',
    2007,
    'images/logo.png',
    'images/ucsmtla.jpg',
    'Meiktila, Myanmar',
    '09-123 456 789',
    'info@ucsmta.edu.mm',
    'Admission Information 2025-2026

Information regarding entrance admission and enrollment for the academic year 2025-2026.

Applicants must satisfy the university entrance requirements and provide the required documents.

Entrance information and important dates will be announced by the university.

Applicants should follow the official admission instructions provided by the university.'
);

INSERT INTO faculties
(name, description, status)
VALUES
(
    'Faculty of Computer Science (FCS)',
    'The Faculty of Computer Science is dedicated to advancing computer science education and research that creates real-world impact. It aims to develop future IT professionals, innovators, and leaders while contributing to local technological development. The faculty provides high-quality education, combines theory with practical applications, encourages research and innovation, and promotes effective teaching and learning through ICT. Students are trained to become skilled problem solvers, logical thinkers, and collaborative team members through a curriculum covering programming, artificial intelligence, cybersecurity, software engineering, geographic information systems, and other modern computing fields. In addition to classroom learning, students gain practical experience through research, internships, and community engagement.',
    TRUE
),
(
    'Faculty of Information Science (FIS)',
    'The Faculty of Information Science is committed to producing qualified computer scientists and IT professionals with strong academic knowledge and practical skills. The faculty follows a student-centered education system that emphasizes hands-on learning through practical sessions, tutorials, and project-based assessments. It also ensures the effective delivery of courses and practical training according to academic schedules, preparing students to meet the demands of the modern technology field.',
    TRUE
),
(
    'Faculty of Computing and Technology (FCST)',
    'The Faculty of Computing and Technology is committed to providing quality education for change, peace and progress through innovative education for a knowledgeable, pioneering and global society. It provides a holistic and empowering education system that enables all students to realize and appreciate their potential while contributing to peaceful and sustainable national development.',
    TRUE
),
(
    'Faculty of Computing (Mathematics)',
    'The Faculty of Computing (Mathematics) is dedicated to providing a strong foundation in mathematics that supports computing, science and technology. The faculty emphasizes logical thinking, analytical reasoning and problem-solving skills through both theoretical knowledge and practical applications. It prepares students with the mathematical competence required for modern computing disciplines while encouraging critical thinking, innovation and lifelong learning to contribute effectively to academic, professional and technological development.',
    TRUE
);

INSERT INTO departments
(name, description, status)
VALUES
(
    'Information Technology and Systems Management (ITSM) Department',
    'The Information Technology and Systems Management Department is committed to providing quality and innovative education that promotes positive change, peace, and sustainable progress. It strives to build a knowledgeable, forward-thinking, and globally competitive community while empowering students to reach their full potential. Through a holistic education system, the department prepares graduates to contribute responsibly to national development and create a peaceful and sustainable society.',
    TRUE
),
(
    'Department of Natural Language (Myanmar and English)',
    'The Department of Natural Language (Myanmar and English) is committed to developing students'' communication skills in both Myanmar and English while preparing them for academic and professional success. The department emphasizes English language proficiency through IELTS-based learning, helping students strengthen their reading, writing, listening, and speaking skills. It also enhances students'' academic vocabulary and language accuracy, enabling them to pursue higher education, especially in IT and computing, and communicate effectively in a global environment.',
    TRUE
),
(
    'Department of Natural Science (Physics)',
    'The Department of Natural Science (Physics) is dedicated to providing quality education that connects theoretical knowledge with practical applications. It emphasizes hands-on learning through experiments, tutorials, and project-based activities to strengthen students'' scientific understanding and problem-solving skills. The department aims to develop competent graduates with strong analytical abilities, practical experience, and the knowledge needed to contribute effectively to science, engineering, and technological advancement.',
    TRUE
);

INSERT INTO administrative_units
(name, description, status)
VALUES
(
    'Library',
    'The University Library provides students, faculty members, and researchers with access to quality academic resources and learning facilities. It offers a wide collection of books, journals, reference materials, and digital resources to support teaching, learning, and research. The library encourages lifelong learning, independent study, and academic excellence by creating a quiet, resourceful, and welcoming environment for the university community.',
    TRUE
),
(
    'Finance Department',
    'The Finance Department is responsible for managing the university''s financial resources efficiently and transparently. It oversees budgeting, accounting, procurement, and financial administration to ensure the smooth operation of academic and administrative activities. The department supports students, faculty, and staff by maintaining sound financial practices and contributing to the sustainable development of the university.',
    TRUE
),
(
    'Administration Department',
    'The Administration Department is responsible for ensuring the efficient operation and effective management of the university''s administrative services. It supports teaching, research, and student affairs by providing quality administrative assistance, maintaining transparent policies, and coordinating essential university operations. Through a service-oriented approach, the department works closely with students, faculty, staff, parents, and other stakeholders to create a well-organized, supportive, and productive academic environment.',
    TRUE
),
(
    'Student Affairs Department',
    'The Student Affairs Department is committed to supporting students throughout their academic journey by promoting their personal, academic, and social development. The department manages student registration, welfare services, extracurricular activities, scholarships, and campus events while fostering a safe, inclusive, and disciplined learning environment. It serves as a bridge between students and the university administration to enhance the overall student experience.',
    TRUE
);

-- Facilities table removed. Campus life content is no longer managed via database.

INSERT INTO academic_years
(year_name, start_date, end_date, status)
VALUES
(
    '2024-2025',
    '2024-12-01',
    '2025-10-31',
    'Archived'
),
(
    '2025-2026',
    '2025-12-01',
    '2026-10-31',
    'Active'
),
(
    '2026-2027',
    '2026-12-01',
    '2027-10-31',
    'Preparation'
);

INSERT INTO majors
(faculty_id, name, short_name, degree_name, description, status)
VALUES
(
    NULL,
    'Computer Science',
    'CS',
    'B.C.Sc.',
    'An undergraduate major focusing on computer science, software development, algorithms and computing concepts.',
    TRUE
),
(
    NULL,
    'Computer Technology',
    'CT',
    'B.C.Tech.',
    'An undergraduate major focusing on computer technology, systems and practical computing applications.',
    TRUE
);
INSERT INTO courses
(
    academic_year_id,
    major_id,
    course_code,
    course_name,
    year_level,
    semester,
    credit_hours,
    description,
    status
)
VALUES
-- =========================================================
-- FIRST YEAR (CS)
-- =========================================================
(
    2, NULL,
    'E-1101',
    'English Proficiency I',
    'First Year',
    NULL,
    NULL,
    NULL,
    TRUE
),
(
    2, NULL,
    'CST-1102',
    'Introduction to Computer Science',
    'First Year',
    NULL,
    NULL,
    NULL,
    TRUE
),
(
    2, NULL,
    'CST-1103',
    'Discrete Mathematics',
    'First Year',
    NULL,
    NULL,
    NULL,
    TRUE
),
(
    2, NULL,
    'CST-1104',
    'Programming Fundamentals',
    'First Year',
    NULL,
    NULL,
    NULL,
    TRUE
),
(
    2, NULL,
    'CST-1105',
    'Digital Electronics',
    'First Year',
    NULL,
    NULL,
    NULL,
    TRUE
),
(
    2, NULL,
    'M-1106',
    'Calculus and Analytical Geometry',
    'First Year',
    NULL,
    NULL,
    NULL,
    TRUE
),
-- =========================================================
-- FIRST YEAR (CT)
-- =========================================================
(
    2, NULL,
    'E-1101',
    'English Proficiency I',
    'First Year',
    NULL,
    NULL,
    NULL,
    TRUE
),
(
    2, NULL,
    'CT-1102',
    'Introduction to Computer Technology',
    'First Year',
    NULL,
    NULL,
    NULL,
    TRUE
),
(
    2, NULL,
    'CT-1103',
    'Basic Mathematics',
    'First Year',
    NULL,
    NULL,
    NULL,
    TRUE
),
(
    2, NULL,
    'CT-1104',
    'Programming Basics',
    'First Year',
    NULL,
    NULL,
    NULL,
    TRUE
),
(
    2, NULL,
    'CT-1105',
    'Computer Hardware Fundamentals',
    'First Year',
    NULL,
    NULL,
    NULL,
    TRUE
),
-- =========================================================
-- SECOND YEAR (CS)
-- =========================================================
(
    2, 1,
    'E-2201',
    'English Proficiency IV',
    'Second Year',
    NULL,
    NULL,
    NULL,
    TRUE
),
(
    1, 1,
    'CST-2241',
    'Differential Equations and Numerical Analysis',
    'Second Year',
    NULL,
    NULL,
    NULL,
    TRUE
),
(
    1, 1,
    'CST-2212',
    'Artificial Intelligence',
    'Second Year',
    NULL,
    NULL,
    NULL,
    TRUE
),
(
    1, 1,
    'CST-2213',
    'Operating Systems',
    'Second Year',
    NULL,
    NULL,
    NULL,
    TRUE
),
(
    1, 1,
    'CST-2224',
    'Software Analysis and Design',
    'Second Year',
    NULL,
    NULL,
    NULL,
    TRUE
),
(
    1, 1,
    'CST-2235',
    'Data Communication and Networking',
    'Second Year',
    NULL,
    NULL,
    NULL,
    TRUE
),
(
    1, 1,
    'CS-2256',
    'Web Technology (JavaScript)',
    'Second Year',
    NULL,
    NULL,
    NULL,
    TRUE
),

-- =========================================================
-- SECOND YEAR (CT)
-- =========================================================

(
    2, 2,
    'E-2201',
    'English Proficiency IV',
    'Second Year',
    NULL,
    NULL,
    NULL,
    TRUE
),
(
    2, 2,
    'CST-2241',
    'Differential Equations and Numerical Analysis',
    'Second Year',
    NULL,
    NULL,
    NULL,
    TRUE
),
(
    2, 2,
    'CST-2212',
    'Artificial Intelligence',
    'Second Year',
    NULL,
    NULL,
    NULL,
    TRUE
),
(
    2, 2,
    'CST-2213',
    'Operating Systems',
    'Second Year',
    NULL,
    NULL,
    NULL,
    TRUE
),
(
    2, 2,
    'CST-2224',
    'Software Analysis and Design',
    'Second Year',
    NULL,
    NULL,
    NULL,
    TRUE
),
(
    2, 2,
    'CST-2235',
    'Data Communication and Networking',
    'Second Year',
    NULL,
    NULL,
    NULL,
    TRUE
),
(
    2, 2,
    'CT-2236',
    'Circuits and Electronics',
    'Second Year',
    NULL,
    NULL,
    NULL,
    TRUE
),

-- =========================================================
-- THIRD YEAR (CS)
-- =========================================================

(
    2, 1,
    'CST-3211',
    'Operating Systems',
    'Third Year',
    NULL,
    NULL,
    NULL,
    TRUE
),
(
    2, 1,
    'CST-3242',
    'Probability and Statistics',
    'Third Year',
    NULL,
    NULL,
    NULL,
    TRUE
),
(
    2, 1,
    'CST-3213',
    'Professional Ethics',
    'Third Year',
    NULL,
    NULL,
    NULL,
    TRUE
),
(
    2, 1,
    'CS-3224',
    'Software Quality Assurance and Testing',
    'Third Year',
    NULL,
    NULL,
    NULL,
    TRUE
),
(
    2, 1,
    'CST-3235',
    'Computer Networks I',
    'Third Year',
    NULL,
    NULL,
    NULL,
    TRUE
),
(
    2, 1,
    'CST-3256',
    'Human Computer Interaction',
    'Third Year',
    NULL,
    NULL,
    NULL,
    TRUE
),
(
    2, 1,
    'CST-3257(SS)',
    'Supporting Skill IV - Applied Database and Application (ADO.Net, C#)',
    'Third Year',
    NULL,
    NULL,
    NULL,
    TRUE
),

-- =========================================================
-- THIRD YEAR (CT)
-- =========================================================

(
    2, 2,
    'CST-3211',
    'Operating Systems',
    'Third Year',
    NULL,
    NULL,
    NULL,
    TRUE
),
(
    2, 2,
    'CST-3242',
    'Probability and Statistics',
    'Third Year',
    NULL,
    NULL,
    NULL,
    TRUE
),
(
    2, 2,
    'CST-3213',
    'Professional Ethics',
    'Third Year',
    NULL,
    NULL,
    NULL,
    TRUE
),
(
    2, 2,
    'CT-3234',
    'Computer Architecture and Organization II',
    'Third Year',
    NULL,
    NULL,
    NULL,
    TRUE
),
(
    2, 2,
    'CST-3235',
    'Computer Networks I',
    'Third Year',
    NULL,
    NULL,
    NULL,
    TRUE
),
(
    2, 2,
    'CST-3256',
    'Human Computer Interaction',
    'Third Year',
    NULL,
    NULL,
    NULL,
    TRUE
),
(
    2, 2,
    'CST-3257(SS)',
    'Supporting Skill IV - Applied Database and Application (ADO.Net, C#)',
    'Third Year',
    NULL,
    NULL,
    NULL,
    TRUE
),

-- =========================================================
-- FOURTH YEAR (CS)
-- =========================================================

(
    2, 1,
    'CST-4211',
    'Distributed and Parallel Computing',
    'Fourth Year',
    NULL,
    NULL,
    NULL,
    TRUE
),
(
    2, 1,
    'CST-4242',
    'Modeling and Simulations',
    'Fourth Year',
    NULL,
    NULL,
    NULL,
    TRUE
),
(
    2, 1,
    'CS-4223',
    'Object-Oriented Design and Development',
    'Fourth Year',
    NULL,
    NULL,
    NULL,
    TRUE
),
(
    2, 1,
    'CS-4214(Elective I A)',
    'Advanced Artificial Intelligence (Knowledge Representation and Management, Machine Learning with Python)',
    'Fourth Year',
    NULL,
    NULL,
    NULL,
    TRUE
),
(
    2, 1,
    'CS-4225(Elective II A)',
    'Advanced Database System',
    'Fourth Year',
    NULL,
    NULL,
    NULL,
    TRUE
),
(
    2, 1,
    'CS-4216(Elective III A)',
    'Strategic for Emerging Technologies (Cloud Computing)',
    'Fourth Year',
    NULL,
    NULL,
    NULL,
    TRUE
),
(
    2, 1,
    'CST-4257(Elective IV A)',
    'Business Information System (Digital Business and e-Commerce)',
    'Fourth Year',
    NULL,
    NULL,
    NULL,
    TRUE
),

-- =========================================================
-- FOURTH YEAR (CT)
-- =========================================================

(
    2, 2,
    'CST-4211',
    'Distributed and Parallel Computing',
    'Fourth Year',
    NULL,
    NULL,
    NULL,
    TRUE
),
(
    2, 2,
    'CST-4242',
    'Modeling and Simulations',
    'Fourth Year',
    NULL,
    NULL,
    NULL,
    TRUE
),
(
    2, 2,
    'CT-4233',
    'Cryptography and Network Security',
    'Fourth Year',
    NULL,
    NULL,
    NULL,
    TRUE
),
(
    2, 2,
    'CT-4234',
    'Embedded Systems Integrating IoT',
    'Fourth Year',
    NULL,
    NULL,
    NULL,
    TRUE
),
(
    2, 2,
    'CT-4235(Elective I)',
    'Signals and Systems',
    'Fourth Year',
    NULL,
    NULL,
    NULL,
    TRUE
),
(
    2, 2,
    'CT-4236(Elective II)',
    'Cyber Security and Ethical Hacking',
    'Fourth Year',
    NULL,
    NULL,
    NULL,
    TRUE
),
(
    2, 2,
    'CST-4257(Elective III)',
    'Business Information System (Digital Business and e-Commerce)',
    'Fourth Year',
    NULL,
    NULL,
    NULL,
    TRUE
),

-- =========================================================
-- FIFTH YEAR (CS)
-- =========================================================

(
    2, 1,
    'E-5101',
    'Communication in Business English',
    'Fifth Year',
    NULL,
    NULL,
    NULL,
    TRUE
),
(
    2, 1,
    'CS-5121',
    'Cyber Security and Digital Forensics',
    'Fifth Year',
    NULL,
    NULL,
    NULL,
    TRUE
),
(
    2, 1,
    'CS-5112(Elective I B)',
    'Natural Language Processing',
    'Fifth Year',
    NULL,
    NULL,
    NULL,
    TRUE
),
(
    2, 1,
    'CST-5123 A(Elective II B)',
    'Data Science Fundamental',
    'Fifth Year',
    NULL,
    NULL,
    NULL,
    TRUE
),
(
    2, 1,
    'CST-5123 B(Elective II B)',
    'Data Analytic and Mining',
    'Fifth Year',
    NULL,
    NULL,
    NULL,
    TRUE
),
(
    2, 1,
    'CS-5114(Elective III B)',
    'Strategies for Emerging Technology (Virtualization, Blockchain, Cloud Security and Internet of Things)',
    'Fifth Year',
    NULL,
    NULL,
    NULL,
    TRUE
),
(
    2, 1,
    'CST-5155(Elective IV B)',
    'Business Information System (Enterprise Resource Planning (ERP))',
    'Fifth Year',
    NULL,
    NULL,
    NULL,
    TRUE
),

-- =========================================================
-- FIFTH YEAR (CT)
-- =========================================================

(
    2, 2,
    'E-5101',
    'Communication in Business English',
    'Fifth Year',
    NULL,
    NULL,
    NULL,
    TRUE
),
(
    2, 2,
    'CT-5131',
    'Digital Forensics',
    'Fifth Year',
    NULL,
    NULL,
    NULL,
    TRUE
),
(
    2, 2,
    'CT-5132',
    'Advanced Networking',
    'Fifth Year',
    NULL,
    NULL,
    NULL,
    TRUE
),
(
    2, 2,
    'CST-5123 A(Elective)',
    'Data Science Fundamental',
    'Fifth Year',
    NULL,
    NULL,
    NULL,
    TRUE
),
(
    2, 2,
    'CST-5123 B(Elective)',
    'Data Analytic and Mining',
    'Fifth Year',
    NULL,
    NULL,
    NULL,
    TRUE
),
(
    2, 2,
    'CT-5134(Elective)',
    'Digital Signal Processing',
    'Fifth Year',
    NULL,
    NULL,
    NULL,
    TRUE
),
(
    2, 2,
    'CST-5155(Elective)',
    'Business Information System (Enterprise Resource Planning (ERP))',
    'Fifth Year',
    NULL,
    NULL,
    NULL,
    TRUE
),
(
    2, 2,
    'CT-5136(Elective)',
    'Image Processing and Computer Vision',
    'Fifth Year',
    NULL,
    NULL,
    NULL,
    TRUE
);

INSERT INTO classrooms
(
    id,
    academic_year_id,
    major_id,
    year_level,
    section,
    classroom_name,
    status
)
VALUES
-- =========================================================
-- ACADEMIC YEAR 2025-2026 (id=2) — ACTIVE
-- =========================================================

-- FIRST YEAR - COMMON (CS&T)
( 1,  2, NULL, 'First Year',    'A', 'First Year (A)',        TRUE),
( 2,  2, NULL, 'First Year',    'B', 'First Year (B)',        TRUE),
( 3,  2, NULL, 'First Year',    'C', 'First Year (C)',        TRUE),

-- FIRST YEAR - COMPUTER SCIENCE
(32,  2, 1, 'First Year',    'D', 'First Year (D)',        TRUE),

-- SECOND YEAR - COMPUTER SCIENCE
( 4,  2, 1, 'Second Year',   'A', 'Second Year CS (A)',    TRUE),
( 5,  2, 1, 'Second Year',   'B', 'Second Year CS (B)',    TRUE),

-- SECOND YEAR - COMPUTER TECHNOLOGY
( 6,  2, 2, 'Second Year',   NULL, 'Second Year CT',        TRUE),

-- THIRD YEAR - COMPUTER SCIENCE
( 7,  2, 1, 'Third Year',    'A', 'Third Year CS (A)',     TRUE),
( 8,  2, 1, 'Third Year',    'B', 'Third Year CS (B)',     TRUE),

-- THIRD YEAR - COMPUTER TECHNOLOGY
( 9,  2, 2, 'Third Year',    NULL, 'Third Year CT',         TRUE),

-- FOURTH YEAR - COMPUTER SCIENCE
(10,  2, 1, 'Fourth Year',   'A', 'Fourth Year CS (A)',    TRUE),
(11,  2, 1, 'Fourth Year',   'B', 'Fourth Year CS (B)',    TRUE),

-- FOURTH YEAR - COMPUTER TECHNOLOGY
(12,  2, 2, 'Fourth Year',   NULL, 'Fourth Year CT',        TRUE),

-- FIFTH YEAR - COMPUTER SCIENCE
(13,  2, 1, 'Fifth Year',    'A', 'Fifth Year CS (A)',     TRUE),
(14,  2, 1, 'Fifth Year',    'B', 'Fifth Year CS (B)',     TRUE),

-- FIFTH YEAR - COMPUTER TECHNOLOGY
(15,  2, 2, 'Fifth Year',    NULL, 'Fifth Year CT',         TRUE);

INSERT INTO timetables
(
    classroom_id,
    semester,
    title,
    image,
    status
)
VALUES

-- =========================================================
-- FIRST YEAR - COMMON
-- =========================================================

(
    1,
    'First Semester',
    'First Year (A) - First Semester Timetable',
    'timetables/first_year_cs_first_semester.jpg',
    TRUE
),
(
    2,
    'First Semester',
    'First Year (B) - First Semester Timetable',
    'timetables/first-year-b-first-semester.jpg',
    TRUE
),
(
    3,
    'First Semester',
    'First Year (C) - First Semester Timetable',
    'timetables/first-year-c-first-semester.jpg',
    TRUE
),

-- =========================================================
-- SECOND YEAR - COMPUTER SCIENCE
-- =========================================================

(
    4,
    'First Semester',
    'Second Year CS (A) - First Semester Timetable',
    'timetables/second-year-cs-a-first-semester.jpg',
    TRUE
),
(
    5,
    'First Semester',
    'Second Year CS (B) - First Semester Timetable',
    'timetables/second-year-cs-b-first-semester.jpg',
    TRUE
),

-- =========================================================
-- SECOND YEAR - COMPUTER TECHNOLOGY
-- =========================================================

(
    6,
    'First Semester',
    'Second Year CT - First Semester Timetable',
    'timetables/second-year-ct-first-semester.jpg',
    TRUE
),

-- =========================================================
-- THIRD YEAR - COMPUTER SCIENCE
-- =========================================================

(
    7,
    'First Semester',
    'Third Year CS (A) - First Semester Timetable',
    'timetables/third-year-cs-a-first-semester.jpg',
    TRUE
),
(
    8,
    'First Semester',
    'Third Year CS (B) - First Semester Timetable',
    'timetables/third-year-cs-b-first-semester.jpg',
    TRUE
),

-- =========================================================
-- THIRD YEAR - COMPUTER TECHNOLOGY
-- =========================================================

(
    9,
    'First Semester',
    'Third Year CT - First Semester Timetable',
    'timetables/third-year-ct-first-semester.jpg',
    TRUE
),

-- =========================================================
-- FOURTH YEAR - COMPUTER SCIENCE
-- =========================================================

(
    10,
    'First Semester',
    'Fourth Year CS (A) - First Semester Timetable',
    'timetables/fourth-year-cs-a-first-semester.jpg',
    TRUE
),
(
    11,
    'First Semester',
    'Fourth Year CS (B) - First Semester Timetable',
    'timetables/fourth-year-cs-b-first-semester.jpg',
    TRUE
),

-- =========================================================
-- FOURTH YEAR - COMPUTER TECHNOLOGY
-- =========================================================

(
    12,
    'First Semester',
    'Fourth Year CT - First Semester Timetable',
    'timetables/fourth-year-ct-first-semester.jpg',
    TRUE
),

-- =========================================================
-- FIFTH YEAR - COMPUTER SCIENCE
-- =========================================================

(
    13,
    'First Semester',
    'Fifth Year CS (A) - First Semester Timetable',
    'timetables/fifth_year_cs_first_semester.jpg',
    TRUE
),
(
    14,
    'First Semester',
    'Fifth Year CS (B) - First Semester Timetable',
    'timetables/fifth-year-cs-b-first-semester.jpg',
    TRUE
),

-- =========================================================
-- FIFTH YEAR - COMPUTER TECHNOLOGY
-- =========================================================

(
    15,
    'First Semester',
    'Fifth Year CT - First Semester Timetable',
    'timetables/fifth_year_ct_first_semester.jpg',
    TRUE
),

-- =========================================================
-- FIRST YEAR - COMMON
-- SECOND SEMESTER
-- =========================================================

(
    1,
    'Second Semester',
    'First Year (A) - Second Semester Timetable',
    'timetables/first-year-a-second-semester.jpg',
    TRUE
),
(
    2,
    'Second Semester',
    'First Year (B) - Second Semester Timetable',
    'timetables/first-year-b-second-semester.jpg',
    TRUE
),
(
    3,
    'Second Semester',
    'First Year (C) - Second Semester Timetable',
    'timetables/first-year-c-second-semester.jpg',
    TRUE
),

-- =========================================================
-- SECOND YEAR - COMPUTER SCIENCE
-- =========================================================

(
    4,
    'Second Semester',
    'Second Year CS (A) - Second Semester Timetable',
    'timetables/second-year-cs-a-second-semester.jpg',
    TRUE
),
(
    5,
    'Second Semester',
    'Second Year CS (B) - Second Semester Timetable',
    'timetables/second-year-cs-b-second-semester.jpg',
    TRUE
),

-- =========================================================
-- SECOND YEAR - COMPUTER TECHNOLOGY
-- =========================================================

(
    6,
    'Second Semester',
    'Second Year CT - Second Semester Timetable',
    'timetables/second-year-ct-second-semester.jpg',
    TRUE
),

-- =========================================================
-- THIRD YEAR - COMPUTER SCIENCE
-- =========================================================

(
    7,
    'Second Semester',
    'Third Year CS (A) - Second Semester Timetable',
    'timetables/third-year-cs-a-second-semester.jpg',
    TRUE
),
(
    8,
    'Second Semester',
    'Third Year CS (B) - Second Semester Timetable',
    'timetables/third-year-cs-b-second-semester.jpg',
    TRUE
),

-- =========================================================
-- THIRD YEAR - COMPUTER TECHNOLOGY
-- =========================================================

(
    9,
    'Second Semester',
    'Third Year CT - Second Semester Timetable',
    'timetables/third-year-ct-second-semester.jpg',
    TRUE
),

-- =========================================================
-- FOURTH YEAR - COMPUTER SCIENCE
-- =========================================================

(
    10,
    'Second Semester',
    'Fourth Year CS (A) - Second Semester Timetable',
    'timetables/fourth-year-cs-a-second-semester.jpg',
    TRUE
),
(
    11,
    'Second Semester',
    'Fourth Year CS (B) - Second Semester Timetable',
    'timetables/fourth-year-cs-b-second-semester.jpg',
    TRUE
),

-- =========================================================
-- FOURTH YEAR - COMPUTER TECHNOLOGY
-- =========================================================

(
    12,
    'Second Semester',
    'Fourth Year CT - Second Semester Timetable',
    'timetables/fourth-year-ct-second-semester.jpg',
    TRUE
),

-- =========================================================
-- FIFTH YEAR - COMPUTER SCIENCE
-- =========================================================

(
    13,
    'Second Semester',
    'Fifth Year CS (A) - Second Semester Timetable',
    'timetables/fifth-year-cs-a-second-semester.jpg',
    TRUE
),
(
    14,
    'Second Semester',
    'Fifth Year CS (B) - Second Semester Timetable',
    'timetables/fifth-year-cs-b-second-semester.jpg',
    TRUE
),

-- =========================================================
-- FIFTH YEAR - COMPUTER TECHNOLOGY
-- =========================================================

(
    15,
    'Second Semester',
    'Fifth Year CT - Second Semester Timetable',
    'timetables/fifth-year-ct-second-semester.jpg',
    TRUE
);

-- Default student credentials: student1@gmail.com / student123 and
-- student2@gmail.com / student123. Passwords are stored as bcrypt hashes
-- (never plaintext) via PHP's password_hash() / password_verify() pair.
INSERT INTO students
(
    student_id,
    roll_number,
    name,
    email,
    password,
    classroom_id,
    status
)
VALUES
(
    '1',
    'R-1001',
    'Wai Mar Aung',
    'student1@gmail.com',
    '$2y$10$3i/6X9a5ZYymr/i0SCNQpuFnrA9phW.iLuz7eUXE7AzDsRPpX0gmi',
    1,
    TRUE
),
(
    '2',
    'R-1002',
    'Htoo Lwin',
    'student2@gmail.com',
    '$2y$10$3i/6X9a5ZYymr/i0SCNQpuFnrA9phW.iLuz7eUXE7AzDsRPpX0gmi',
    2,
    TRUE
);

-- Admissions data is now stored in university_profile admission_* columns.

INSERT INTO categories
(name, description, status)
VALUES
(
    'News',
    'General university news and updates.',
    TRUE
),
(
    'Announcement',
    'Important announcements for students and the university community.',
    TRUE
),
(
    'Event',
    'University events and activities.',
    TRUE
),
(
    'Academic',
    'Academic-related information and updates.',
    TRUE
),
(
    'Admission',
    'Admission and entrance-related information.',
    TRUE
);

-- Default admin credentials: admin1@gmail.com / admin123
-- Password is stored as a bcrypt hash (never plaintext) via PHP's
-- password_hash() / password_verify() pair.
INSERT INTO admins
(
    name,
    email,
    password,
    status
)
VALUES
(
    'System Administrator',
    'admin1@gmail.com',
    '$2y$10$XI2ymgRBFawQDRsTu/EEv.NW8Y/nFMw/akwARvUnrAs6JAXHFSklK',
    TRUE
);

INSERT INTO news
(
    category_id,
    admin_id,
    title,
    slug,
    content,
    cover_image,
    published_at,
    status
)
VALUES
(
    1,
    1,
    'Welcome to UCSMTLA Academic Hub',
    'welcome-to-ucsmtla-academic-hub',
    'Welcome to the UCSMTLA Academic Hub, providing university information, academic information, admission information, campus facilities and university news.',
    'news/welcome.jpg',
    NOW(),
    'Published'
),
(
    2,
    1,
    'Semester Examination Announcement',
    'semester-examination-announcement',
    'Important information regarding the upcoming semester examination will be announced here.',
    'news/examination.jpg',
    NOW(),
    'Published'
),
(
    3,
    1,
    'University Sports Event',
    'university-sports-event',
    'The university will organize a sports event for students.',
    'news/sports-event.jpg',
    NOW(),
    'Published'
),
(
    4,
    1,
    'Course Registration Information',
    'course-registration-information',
    'Information regarding course registration for students.',
    'news/course-registration.jpg',
    NOW(),
    'Published'
),
(
    2,
    1,
    'Third Year CS Timetable Announcement',
    'third-year-cs-timetable-announcement',
    'The timetable for Third Year Computer Science students has been published.',
    'news/timetable.jpg',
    NOW(),
    'Published'
);

INSERT INTO news_targets
(
    news_id,
    academic_year_id,
    major_id,
    year_level,
    section
)
VALUES
(
    5,
    2,
    1,
    'Third Year',
   'A'
);

INSERT INTO teachers
(teacher_id, name, email, phone, faculty_id, department_id, specialization, status)
VALUES
(
    'T-001',
    'Dr. Aung Aung',
    'aung.aung@ucsmtla.edu.mm',
    '09-123 456 001',
    1, NULL,
    'Artificial Intelligence',
    TRUE
),
(
    'T-002',
    'Daw Su Su',
    'su.su@ucsmtla.edu.mm',
    '09-123 456 002',
    NULL, 1,
    'Information Technology',
    TRUE
),
(
    'T-003',
    'Dr. Myo Myo',
    'myo.myo@ucsmtla.edu.mm',
    '09-123 456 003',
    2, NULL,
    'Library Science',
    TRUE
),
(
    'T-004',
    'U Kyaw Kyaw',
    'kyaw.kyaw@ucsmtla.edu.mm',
    '09-123 456 004',
    NULL, 2,
    'Natural Language Processing',
    TRUE
),
(
    'T-005',
    'Daw Thin Thin',
    'thin.thin@ucsmtla.edu.mm',
    '09-123 456 005',
    1, NULL,
    'Software Engineering',
    TRUE
);

INSERT INTO teacher_course_assignments
(teacher_id, course_id, classroom_id, semester)
VALUES
(1, (SELECT id FROM courses WHERE course_code = 'CST-2212' AND academic_year_id = 2 LIMIT 1),
 (SELECT id FROM classrooms WHERE id = 4 LIMIT 1),
 'First Semester'),
(2, (SELECT id FROM courses WHERE course_code = 'E-2201' AND academic_year_id = 2 AND major_id = 1 LIMIT 1),
 (SELECT id FROM classrooms WHERE id = 4 LIMIT 1),
 'First Semester');