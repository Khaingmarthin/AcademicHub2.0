-- =========================================================
-- UCSMTLA ACADEMIC HUB
-- ALUMNI MODULE SEED DATA
-- Run AFTER seed.sql
-- =========================================================
-- This file adds demonstration data for the Alumni Module:
-- graduated students, alumni profiles, alumni stories,
-- career discussions, and discussion replies.
-- Safe to re-run: uses INSERT IGNORE to prevent duplicates.
-- Uses subqueries to find correct IDs regardless of
-- auto-increment values.
-- =========================================================

USE ucsmtla_academic_hub;


-- =========================================================
-- 1. GRADUATED STUDENTS
-- =========================================================
-- Password for all demo students: student123
-- (same bcrypt hash as existing demo students)
-- =========================================================

INSERT IGNORE INTO students
(student_id, roll_number, name, email, password, classroom_id, status, student_status, graduation_year)
VALUES
('3',  'R-1003', 'Aung Min Thu',   'student3@gmail.com', '$2y$10$3i/6X9a5ZYymr/i0SCNQpuFnrA9phW.iLuz7eUXE7AzDsRPpX0gmi', 13, TRUE, 'graduated', 2025),
('4',  'R-1004', 'Thin Zar Aung',  'student4@gmail.com', '$2y$10$3i/6X9a5ZYymr/i0SCNQpuFnrA9phW.iLuz7eUXE7AzDsRPpX0gmi', 13, TRUE, 'graduated', 2025),
('5',  'R-1005', 'Kyaw Zin Oo',    'student5@gmail.com', '$2y$10$3i/6X9a5ZYymr/i0SCNQpuFnrA9phW.iLuz7eUXE7AzDsRPpX0gmi', 14, TRUE, 'graduated', 2025),
('6',  'R-1006', 'May Phyu Aung',  'student6@gmail.com', '$2y$10$3i/6X9a5ZYymr/i0SCNQpuFnrA9phW.iLuz7eUXE7AzDsRPpX0gmi', 14, TRUE, 'graduated', 2024),
('7',  'R-1007', 'Soe Myat Noe',   'student7@gmail.com', '$2y$10$3i/6X9a5ZYymr/i0SCNQpuFnrA9phW.iLuz7eUXE7AzDsRPpX0gmi', 15, TRUE, 'graduated', 2025),
('8',  'R-1008', 'Zaw Lin Htut',   'student8@gmail.com', '$2y$10$3i/6X9a5ZYymr/i0SCNQpuFnrA9phW.iLuz7eUXE7AzDsRPpX0gmi', 15, TRUE, 'graduated', 2024),
('9',  'R-1009', 'Nandar Aung',    'student9@gmail.com', '$2y$10$3i/6X9a5ZYymr/i0SCNQpuFnrA9phW.iLuz7eUXE7AzDsRPpX0gmi', 13, TRUE, 'graduated', 2025);


-- =========================================================
-- 2. ALUMNI PROFILES
-- =========================================================
-- Uses subqueries to find correct student IDs.
-- Distribution:
--   4 verified + public
--   1 verified + private
--   1 pending + public
--   1 rejected + public
-- =========================================================

INSERT IGNORE INTO alumni_profiles
(student_id, current_job, company, professional_field, career_journey, skills, bio, linkedin_url, github_url, visibility, verification_status)
SELECT * FROM (
    SELECT
        (SELECT id FROM students WHERE student_id = '3') as student_id,
        'Junior Software Developer', 'Myanmar Tech Solutions', 'Software Development',
        'After graduating from UCSMTLA with a B.C.Sc. in Computer Science, I joined Myanmar Tech Solutions as a junior developer. My university coursework in web development and database systems gave me a solid foundation.',
        'PHP, MySQL, JavaScript, Git, HTML, CSS',
        'Junior software developer with a focus on web application development. Graduate of UCSMTLA Computer Science program.',
        'https://linkedin.com/in/aungminthu', 'https://github.com/aungminthu',
        'public', 'verified'
    UNION ALL SELECT
        (SELECT id FROM students WHERE student_id = '4'),
        'IT Support Specialist', 'Digital Myanmar Group', 'Information Technology',
        'My time at UCSMTLA taught me the importance of understanding both hardware and software systems. After graduation, I started as an IT support specialist.',
        'Windows Server, Linux, Networking, Active Directory, Troubleshooting',
        'IT support specialist with experience in enterprise systems management. B.C.Sc. graduate from UCSMTLA.',
        'https://linkedin.com/in/thinzaraung', NULL,
        'public', 'verified'
    UNION ALL SELECT
        (SELECT id FROM students WHERE student_id = '5'),
        'Web Developer', 'Yangon Digital Agency', 'Web Development',
        'UCSMTLA gave me the programming foundation I needed. After graduation, I joined a digital agency where I work on client websites and web applications.',
        'PHP, Laravel, JavaScript, React, MySQL, Tailwind CSS',
        'Full-stack web developer specializing in PHP and JavaScript frameworks. UCSMTLA Computer Science graduate.',
        NULL, 'https://github.com/kyawzinoO',
        'public', 'verified'
    UNION ALL SELECT
        (SELECT id FROM students WHERE student_id = '6'),
        'Data Analyst', 'Myanmar Data Insights', 'Data Science',
        'The mathematics and statistics courses at UCSMTLA were instrumental in shaping my career path. I work with SQL databases and Python for data processing.',
        'SQL, Python, Excel, Data Visualization, Statistical Analysis',
        'Data analyst with a strong foundation in mathematics and computing. Graduate of UCSMTLA Computer Science program.',
        'https://linkedin.com/in/mayphyuaung', NULL,
        'public', 'verified'
    UNION ALL SELECT
        (SELECT id FROM students WHERE student_id = '7'),
        'Network Engineer', 'Telecom Myanmar', 'Networking',
        'My Computer Technology background from UCSMTLA prepared me well for a career in networking.',
        'Cisco Networking, TCP/IP, Firewall Management, Network Security, Linux',
        'Network engineer specializing in enterprise infrastructure. B.C.Tech. graduate from UCSMTLA.',
        NULL, NULL,
        'private', 'verified'
    UNION ALL SELECT
        (SELECT id FROM students WHERE student_id = '8'),
        NULL, NULL, NULL, NULL, NULL,
        'UCSMTLA Computer Technology graduate seeking to connect with fellow alumni.',
        NULL, NULL,
        'public', 'pending'
    UNION ALL SELECT
        (SELECT id FROM students WHERE student_id = '9'),
        NULL, NULL, NULL, NULL, NULL,
        'Computer Science graduate from UCSMTLA.',
        NULL, NULL,
        'public', 'rejected'
) AS profiles
WHERE profiles.student_id IS NOT NULL;


-- =========================================================
-- 3. ALUMNI STORIES
-- =========================================================
-- Uses subqueries to find correct profile IDs.
-- Status distribution:
--   4 published
--   1 pending
--   1 rejected
-- =========================================================

INSERT IGNORE INTO alumni_stories
(alumni_profile_id, admin_id, alumni_student_id, title, summary, content, career_field, publication_date, status)
SELECT * FROM (
    SELECT
        (SELECT id FROM alumni_profiles WHERE student_id = (SELECT id FROM students WHERE student_id = '3')) as alumni_profile_id,
        1 as admin_id,
        (SELECT id FROM students WHERE student_id = '3') as alumni_student_id,
        'My Journey from UCSMTLA to Software Development' as title,
        'How my university education prepared me for a career in web development.' as summary,
        'When I first enrolled at UCSMTLA, I was not sure what career path to take. The Computer Science program introduced me to programming, database systems, and web development. During my third year, I discovered my passion for building web applications.

My OJT experience at a local software company was a turning point. I worked on a real client project using PHP and MySQL, and I realized how much the classroom knowledge could be applied in practice. The debugging skills I learned during coursework helped me solve problems quickly in a professional setting.

After graduation, I joined Myanmar Tech Solutions as a junior software developer. The transition from university to the workplace required me to learn new tools and adapt to team-based development. However, the foundation I built at UCSMTLA made this transition manageable.

My advice to current students is to focus on building projects during your studies. Do not just learn theory -- apply it. Practice with Git, work on team projects, and do not be afraid to ask questions during OJT.' as content,
        'Software Development' as career_field,
        '2025-10-01' as publication_date,
        'published' as status
    UNION ALL SELECT
        (SELECT id FROM alumni_profiles WHERE student_id = (SELECT id FROM students WHERE student_id = '4')),
        1,
        (SELECT id FROM students WHERE student_id = '4'),
        'From Classroom to IT Support: My Career Path',
        'How UCSMTLA prepared me for a career in information technology support.',
        'Studying Computer Technology at UCSMTLA gave me a broad understanding of both hardware and software systems. The curriculum covered operating systems, networking, and system administration, which are all directly relevant to my current work.

During my studies, I particularly enjoyed the hands-on lab sessions. Setting up servers, configuring networks, and troubleshooting system issues gave me practical skills that I use every day in my job.

My first position after graduation was as a help desk technician. It was a good starting point because I could apply the troubleshooting methodology I learned at university. After six months, I was promoted to IT support specialist, where I manage enterprise systems and network infrastructure.

For students preparing for their careers, I recommend getting familiar with both Windows and Linux environments. Understanding networking fundamentals is also essential. The courses at UCSMTLA cover these topics well, so take them seriously.',
        'Information Technology',
        '2025-09-20',
        'published'
    UNION ALL SELECT
        (SELECT id FROM alumni_profiles WHERE student_id = (SELECT id FROM students WHERE student_id = '5')),
        1,
        (SELECT id FROM students WHERE student_id = '5'),
        'Building a Career in Web Development',
        'Practical advice for Computer Science students interested in web development.',
        'Web development was one of my favorite subjects at UCSMTLA. The Web Technology course introduced me to HTML, CSS, and JavaScript, and I quickly became interested in building interactive websites.

I spent much of my free time during university learning PHP and building small projects. This self-directed learning, combined with the formal coursework, gave me a solid foundation. When I started looking for jobs after graduation, I had several portfolio projects to show potential employers.

At Yangon Digital Agency, I work on a variety of client projects, from simple landing pages to complex web applications. I use PHP with Laravel and JavaScript with React regularly. The database design skills I learned at UCSMTLA are invaluable for building efficient applications.

My recommendation for students is to start building projects as early as possible. Create a portfolio website, contribute to open source, and practice with version control tools like Git. These skills will set you apart when you start your job search.',
        'Web Development',
        '2025-09-10',
        'published'
    UNION ALL SELECT
        (SELECT id FROM alumni_profiles WHERE student_id = (SELECT id FROM students WHERE student_id = '6')),
        1,
        (SELECT id FROM students WHERE student_id = '6'),
        'How Mathematics Shaped My Data Career',
        'Why mathematical thinking matters for technology careers.',
        'Many students wonder why they need to study mathematics in a computer science program. Looking back at my career, I can say that the mathematical foundation I built at UCSMTLA was one of the most valuable aspects of my education.

The probability and statistics courses taught me how to analyze data and draw meaningful conclusions. The discrete mathematics course strengthened my logical thinking and problem-solving abilities. These skills are directly applicable to my work as a data analyst.

At Myanmar Data Insights, I work with large datasets, write SQL queries, and create visualizations to help businesses understand their data. The analytical approach I developed through my mathematics courses at UCSMTLA guides my work every day.

For current students, do not underestimate the value of your mathematics courses. They may seem abstract now, but they build the critical thinking skills that are essential for any technology career. Practice applying mathematical concepts to real problems, and you will see their value.',
        'Data Science',
        '2025-08-15',
        'published'
    UNION ALL SELECT
        (SELECT id FROM alumni_profiles WHERE student_id = (SELECT id FROM students WHERE student_id = '3')),
        NULL,
        (SELECT id FROM students WHERE student_id = '3'),
        'Tips for Preparing Your First Developer CV',
        'Practical guidance on creating a CV that stands out to employers.',
        'Writing your first CV as a fresh graduate can be challenging. After going through the job search process myself, I want to share some tips that helped me.

Start with a clear structure: contact information, education, technical skills, projects, and any experience. Keep it to one or two pages. Employers spend only a few seconds reviewing each CV, so make every line count.

For the skills section, list technologies you have actually used in projects or coursework. Be honest about your proficiency level. It is better to have a shorter list of skills you know well than a long list of technologies you have only heard of.

The projects section is where you can stand out. Include personal projects, university assignments, and any OJT work. For each project, briefly describe what it does, what technologies you used, and what you learned.',
        'CV Preparation',
        NULL,
        'pending'
    UNION ALL SELECT
        (SELECT id FROM alumni_profiles WHERE student_id = (SELECT id FROM students WHERE student_id = '7')),
        NULL,
        (SELECT id FROM students WHERE student_id = '7'),
        'My Experience with Cisco Networking',
        'A brief overview of network certification preparation.',
        'This story covers my experience preparing for networking certifications after graduation from UCSMTLA.',
        'Networking',
        NULL,
        'rejected'
) AS stories
WHERE stories.alumni_profile_id IS NOT NULL;


-- =========================================================
-- 4. CAREER DISCUSSIONS
-- =========================================================
-- Uses subqueries to find correct student IDs.
-- =========================================================

INSERT IGNORE INTO discussions
(category_id, author_student_id, title, content, status, is_pinned)
SELECT * FROM (
    SELECT
        2 as category_id,
        (SELECT id FROM students WHERE student_id = '3') as author_student_id,
        'What should I prepare before starting OJT?' as title,
        'I am a final year Computer Science student and I will start my OJT next month. I want to make sure I am well prepared. What skills and knowledge should I focus on before starting my internship? Any advice from alumni who have been through this process would be very helpful.' as content,
        'open' as status,
        0 as is_pinned
    UNION ALL SELECT
        5,
        (SELECT id FROM students WHERE student_id = '4'),
        'Which programming skills are most useful for a junior developer?',
        'I have been working as an IT support specialist for a year and I want to transition into software development. Which programming languages and frameworks should I learn first? I have some experience with Python from university.',
        'open',
        0
    UNION ALL SELECT
        3,
        (SELECT id FROM students WHERE student_id = '5'),
        'How should a Computer Science student prepare a CV?',
        'I am about to graduate and I need to prepare my CV for job applications. What should I include? How should I present my university projects and skills? Any tips from alumni who have successfully found jobs would be appreciated.',
        'open',
        0
    UNION ALL SELECT
        6,
        (SELECT id FROM students WHERE student_id = '6'),
        'What should I expect during my first software development job?',
        'I recently graduated and will be starting my first job as a web developer. I am excited but also nervous. What should I expect in the first few months? How is working in a professional team different from university group projects?',
        'open',
        0
    UNION ALL SELECT
        4,
        (SELECT id FROM students WHERE student_id = '7'),
        'How can I improve my technical interview skills?',
        'I have been applying for software development positions but I am struggling with technical interviews. I can code but I get nervous during live coding exercises. How did alumni prepare for technical interviews? Any practice resources or strategies?',
        'open',
        0
    UNION ALL SELECT
        1,
        (SELECT id FROM students WHERE student_id = '8'),
        'Which Git skills should students learn before OJT?',
        'In my experience, version control is one of the most important skills for any developer. However, many students do not learn Git properly during university. What Git concepts and workflows should students focus on before starting their OJT?',
        'open',
        1
    UNION ALL SELECT
        5,
        (SELECT id FROM students WHERE student_id = '9'),
        'How important is SQL for web development jobs?',
        'I noticed that many job postings for web developers mention SQL as a required skill. How important is SQL knowledge for getting hired? Should I focus on learning MySQL or PostgreSQL? Any recommendations from alumni working in web development?',
        'open',
        0
) AS discussions
WHERE discussions.author_student_id IS NOT NULL;


-- =========================================================
-- 5. DISCUSSION REPLIES
-- =========================================================
-- Uses subqueries to find correct discussion and student IDs.
-- =========================================================

INSERT IGNORE INTO discussion_replies
(discussion_id, author_student_id, content, status)
SELECT * FROM (
    -- Replies to Discussion: OJT preparation
    SELECT
        (SELECT id FROM discussions WHERE title = 'What should I prepare before starting OJT?' LIMIT 1) as discussion_id,
        (SELECT id FROM students WHERE student_id = '4') as author_student_id,
        'From my experience, the most important things to prepare are: 1) Git basics - learn how to clone, commit, push, pull, and create branches. 2) Basic SQL - most projects use databases. 3) One programming language well - do not try to learn everything. 4) Be ready to read documentation and ask questions. Good luck with your OJT!' as content,
        'visible' as status
    UNION ALL SELECT
        (SELECT id FROM discussions WHERE title = 'What should I prepare before starting OJT?' LIMIT 1),
        (SELECT id FROM students WHERE student_id = '6'),
        'I agree with the previous advice. I would also add: learn how to read other people code. In a professional environment, you will spend more time reading existing code than writing new code. Practice understanding code written by others.',
        'visible'
    UNION ALL SELECT
        (SELECT id FROM discussions WHERE title = 'What should I prepare before starting OJT?' LIMIT 1),
        (SELECT id FROM students WHERE student_id = '5'),
        'Thank you for the advice! I have been practicing Git and SQL. Should I also learn a framework like Laravel or React before starting OJT, or is basic PHP and JavaScript enough?',
        'visible'

    -- Replies to Discussion: Programming skills
    UNION ALL SELECT
        (SELECT id FROM discussions WHERE title = 'Which programming skills are most useful for a junior developer?' LIMIT 1),
        (SELECT id FROM students WHERE student_id = '3'),
        'I recommend starting with Python since you already have some experience. Python is versatile and used in many fields including web development, data analysis, and automation. After that, learn JavaScript for web development. Both are in high demand.',
        'visible'
    UNION ALL SELECT
        (SELECT id FROM discussions WHERE title = 'Which programming skills are most useful for a junior developer?' LIMIT 1),
        (SELECT id FROM students WHERE student_id = '6'),
        'For web development specifically, I would suggest learning PHP with Laravel or JavaScript with React. Both have strong job markets. PHP is still widely used for backend development, and JavaScript frameworks are popular for modern web applications.',
        'visible'

    -- Replies to Discussion: CV preparation
    UNION ALL SELECT
        (SELECT id FROM discussions WHERE title = 'How should a Computer Science student prepare a CV?' LIMIT 1),
        (SELECT id FROM students WHERE student_id = '3'),
        'I recently went through this process. My advice: keep your CV concise (1-2 pages), highlight your technical skills prominently, and describe your projects with specific details. For each project, mention the technologies used and what you accomplished.',
        'visible'
    UNION ALL SELECT
        (SELECT id FROM discussions WHERE title = 'How should a Computer Science student prepare a CV?' LIMIT 1),
        (SELECT id FROM students WHERE student_id = '8'),
        'One thing I wish I had done better was create a portfolio website. Having a place to showcase your projects makes a big difference. Even simple projects demonstrate your skills better than just listing technologies on a CV.',
        'visible'

    -- Replies to Discussion: First job experience
    UNION ALL SELECT
        (SELECT id FROM discussions WHERE title = 'What should I expect during my first software development job?' LIMIT 1),
        (SELECT id FROM students WHERE student_id = '3'),
        'The biggest adjustment for me was learning to work in a team with version control. At university, we often worked individually or in small groups. In a professional setting, you need to coordinate with multiple developers using Git. Also, be prepared to attend meetings and communicate your progress regularly.',
        'visible'
    UNION ALL SELECT
        (SELECT id FROM discussions WHERE title = 'What should I expect during my first software development job?' LIMIT 1),
        (SELECT id FROM students WHERE student_id = '7'),
        'Expect to feel overwhelmed at first - that is normal. Focus on learning the codebase, understanding the development process, and building relationships with your team. Do not be afraid to ask questions. Most colleagues are happy to help new developers.',
        'visible'

    -- Replies to Discussion: Technical interview
    UNION ALL SELECT
        (SELECT id FROM discussions WHERE title = 'How can I improve my technical interview skills?' LIMIT 1),
        (SELECT id FROM students WHERE student_id = '4'),
        'Practice solving coding problems on platforms like LeetCode or HackerRank. Start with easy problems and gradually increase difficulty. Also, practice explaining your thought process out loud - interviewers want to see how you approach problems, not just the final solution.',
        'visible'
    UNION ALL SELECT
        (SELECT id FROM discussions WHERE title = 'How can I improve my technical interview skills?' LIMIT 1),
        (SELECT id FROM students WHERE student_id = '6'),
        'I found that doing mock interviews with friends helped a lot. Practice coding on a whiteboard or shared document since that is how many technical interviews work. Also, review data structures and algorithms - they come up frequently in interviews.',
        'visible'

    -- Replies to Discussion: Git skills
    UNION ALL SELECT
        (SELECT id FROM discussions WHERE title = 'Which Git skills should students learn before OJT?' LIMIT 1),
        (SELECT id FROM students WHERE student_id = '3'),
        'Essential Git skills: branching and merging, creating pull requests, resolving merge conflicts, writing good commit messages, and understanding the difference between git merge and git rebase. These are used daily in professional development.',
        'visible'
    UNION ALL SELECT
        (SELECT id FROM discussions WHERE title = 'Which Git skills should students learn before OJT?' LIMIT 1),
        (SELECT id FROM students WHERE student_id = '8'),
        'I would also add: learn about Git workflows like Git Flow or GitHub Flow. Understanding how teams organize their code branches helps you contribute effectively from day one.',
        'visible'

    -- Replies to Discussion: SQL importance
    UNION ALL SELECT
        (SELECT id FROM discussions WHERE title = 'How important is SQL for web development jobs?' LIMIT 1),
        (SELECT id FROM students WHERE student_id = '3'),
        'SQL is very important for web development. Almost every web application uses a database. I use MySQL daily at work. Learn the basics well: SELECT, JOIN, INSERT, UPDATE, DELETE, and indexing. Understanding database design is also valuable.',
        'visible'
    UNION ALL SELECT
        (SELECT id FROM discussions WHERE title = 'How important is SQL for web development jobs?' LIMIT 1),
        (SELECT id FROM students WHERE student_id = '6'),
        'I agree SQL is essential. For web development, MySQL is the most common choice since it pairs well with PHP. However, PostgreSQL is also worth learning as it is used in many modern applications. Start with MySQL and expand from there.',
        'visible'
) AS replies
WHERE replies.discussion_id IS NOT NULL AND replies.author_student_id IS NOT NULL;
