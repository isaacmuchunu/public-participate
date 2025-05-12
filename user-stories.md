# 🇰🇪 Kenyan Public Participation System — User Stories

## Overview
The Kenyan Public Participation System enables citizens to submit their views and comments on proposed bills and legislative clauses. The system ensures inclusive participation, transparency, and accessibility in the legislative process.  
There are three main user roles:
- **Kenyan Citizen**
- **Member of Parliament (MP) / Senator**
- **Clerk (of the National Assembly or Senate)** — super admins

---

## 👤 1. Kenyan Citizen (Registered Voter with a Valid National ID)

### Account & Authentication
- As a citizen, I want to **create an account** using my ID number and date of birth so that my eligibility can be verified.
- As a citizen, I want to **log in securely** so that I can access bills open for public comment.
- As a citizen, I want to **recover my password** if I forget it.

### Bill Interaction
- As a citizen, I want to **view all bills currently open for public participation**, so that I can choose one to review.
- As a citizen, I want to **filter or search bills by topic, ministry, or date**, so I can find the ones relevant to me.
- As a citizen, I want to **read a bill clause by clause**, so I can understand and comment on specific sections.
- As a citizen, I want to **submit my comment or suggestion on each clause**, so my voice is captured precisely.
- As a citizen, I want to **edit or delete my comment before submission closes**, so I can make corrections if necessary.
- As a citizen, I want to **see a summary or visualization of overall public feedback** (once published), so I can understand public consensus.

### Notifications
- As a citizen, I want to **receive email/SMS notifications** when new bills are opened for participation or when deadlines approach.
- As a citizen, I want to **receive confirmation** that my comments have been successfully submitted.

---

## 🧑‍⚖️ 2. Member of Parliament / Senator

### Account & Access
- As a Member of Parliament, I want to **receive an invitation and credentials from the Clerk**, so I can log in securely.
- As a Member of Parliament, I want to **log in using my official Parliament/Senate email**, so that only authorized legislators have access.

### Review & Analysis
- As a Member of Parliament, I want to **view all bills under discussion in my house (National Assembly or Senate)**.
- As a Member of Parliament, I want to **read citizen comments clause by clause**, so I can understand public perspectives.
- As a Member of Parliament, I want to **view AI-aggregated summaries of similar comments**, so I can quickly grasp major public sentiments.
- As a Member of Parliament, I want to **download a report of all public comments per bill or clause**, so I can reference it during debates.
- As a Member of Parliament, I want to **bookmark or highlight specific citizen arguments**, so I can refer to them during parliamentary sessions.
- As a Member of Parliament, I want to **filter citizen feedback by demographic or region (if available)**, so I can assess regional interests.

---

## 🧑‍💼 3. Clerk (of National Assembly or Senate) — Super Admin

### User Management
- As a Clerk, I want to **create and manage my account as a verified administrative user**, so I can oversee the participation process.
- As a Clerk, I want to **invite Members of Parliament or Senators** by sending them official login credentials via email.
- As a Clerk, I want to **manage citizen accounts**, including verifying, suspending, or reactivating accounts when necessary.

### Bill Management
- As a Clerk, I want to **publish new bills for public comment**, so that citizens can start participating.
- As a Clerk, I want to **upload the bill text in a structured, clause-based format**, so each clause can receive targeted feedback.
- As a Clerk, I want to **set the start and end date for public commentary**, so participation windows are clearly defined.
- As a Clerk, I want to **edit or withdraw a bill** if it contains errors or has been replaced.
- As a Clerk, I want to **close the commentary period manually or automatically**, so feedback submission stops after deadlines.

### Comment Management & Analytics
- As a Clerk, I want to **view all citizen comments for each bill**, so I can monitor public engagement.
- As a Clerk, I want to **use AI to aggregate and summarize similar comments**, so analysis is faster and more accurate.
- As a Clerk, I want to **generate comprehensive participation reports**, including statistics like comment count per clause, sentiment analysis, and regional participation.
- As a Clerk, I want to **export or print reports** for submission to committees or the Speaker’s office.

### Transparency & Communication
- As a Clerk, I want to **publish summarized reports of public feedback** on the system’s portal, so citizens can see how their contributions were considered.
- As a Clerk, I want to **notify citizens and Members of Parliament** when new bills are published or commentary closes.

---

## 🧠 Additional (AI & Automation Features)
- As the system, I want to **use natural language processing (NLP) to group similar comments**, so the Clerk and MPs can review feedback efficiently.
- As the system, I want to **auto-tag feedback sentiment** (supportive, opposing, neutral), so trend analysis is easier.
- As the system, I want to **detect duplicate or spam comments**, so data remains clean and meaningful.

---

## ✅ Acceptance Criteria (Examples)
- Citizens can only comment if logged in and verified by ID.
- Each bill must have a defined open/close date for commentary.
- MPs can only view bills belonging to their respective House (Senate or National Assembly).
- Clerks can manage all users, bills, and feedback reports.
- AI summaries must group comments with >70% similarity under the same cluster.

---

## 🧩 Future Enhancements
- Integration with eCitizen for ID verification.
- Public dashboard for bill participation analytics.
- Real-time commenting statistics.
- Multilingual support (English, Kiswahili).
- Accessibility compliance for persons with disabilities.

---

**Document Version:** 1.0  
**Prepared by:** [Your Name]  
**Date:** October 2025
