# UCAM Integration Plugin for Moodle

## 🔍 Overview

The **UCAM Integration Plugin** is designed to streamline course and user management in Moodle. It eliminates the need for manual enrollment, unenrollment, and course creation by seamlessly integrating with the UCAM system.

This plugin was built to solve:

- ❌ Manual enrollment/unenrollment of users (teachers and students)
- ❌ Manual course creation errors

After successful integration:

- ✅ Users are automatically enrolled/unenrolled based on UCAM data
- ✅ Courses are automatically created in Moodle

---

## ⚙️ Tech Stack

- **Backend**: PHP, SQL  
- **Frontend**: HTML, JavaScript, CSS  
- **Platform**: Moodle

---

## 🚀 Key Benefits

- ⏱️ **Significant reduction in manual labor and administrative time**
- 🔄 **Accurate and real-time syncing of courses and users**
- ⚡ **Enhanced user experience for both students and teachers**

---

## 📈 System Workflow

```mermaid
graph TD
    A[UCAM Database] -->|User & Course Data| B[Integration Plugin]
    B --> C[Moodle LMS]
    C --> D[Create Courses Automatically]
    C --> E[Enroll/Unenroll Users Automatically]
