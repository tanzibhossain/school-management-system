# Module Build Order & Status

Build in dependency order — never start a module before its dependencies are complete.

| # | Module | Depends On | Status | Notes |
|---|--------|-----------|--------|-------|
| 1 | School | — | ✅ | School, SchoolPhone, SchoolOpeningHour; locale (currency/timezone/locale/academic_year_pattern), country_code, subdomain |
| 2 | Academic | School | ✅ | AcademicYear, SchoolClass, Section(+class_teacher_id), Subject, SubjectRelation, AcademicGroup, Version, Shift, ClassRoutine |
| 3 | User/Auth | — | ✅ | User+Sanctum+Spatie. Roles: `super_admin, admin, teacher, accountant, librarian, receptionist, student, parent` (real list — DevPlan's "moderator"/"Finance"/"Head Teacher" don't exist, never invent them) |
| 4 | Student | Academic, User | ✅ | Student, StudentAcademic, StudentSubject (optional/4th-subject enrollment) |
| 5 | Staff | Academic, User | ✅ | Staff (rfid_number) |
| 6 | Announcement | — | ✅ | Announcement |
| 7 | FeeItem | Academic | ✅ | FeeCategory, FeeItem, FeeDiscount |
| 8 | Payment | Student, FeeItem | ✅ | Invoice, Payment(multi-currency), Refund, StudentCredit, CreditTransaction, PaymentConfig, PaymentGatewayLog. Gateways by `country_code`: BD=bKash+SSLCommerz, else Stripe+PayPal; each declares `SUPPORTED_CURRENCIES` |
| 9 | Examination | Academic, Student | ✅ | ExamType, Exam, ExamSubject, ExamHall, ExamHallSeat, ExamSeating (anti_adjacency + blank_every) |
| 10 | Attendance | Student, Staff | ✅ tests green | see `02-module-specs.md` |
| 11 | Mark | Examination, Attendance, Student | ✅ tests green | see `02-module-specs.md` (needs `student_subjects`, done) |
| 12 | Leave | Student, Staff | ✅ tests green | LeaveType, StudentLeaveRequest, StaffLeaveRequest; approved leave overrides `absent`→`leave` via `WorkingDayService`; staff approval admin-only (no manager field) |
| 13 | Loan | Staff | ✅ tests green | StaffLoan, LoanSchedule; interest-free, request→approve, repayment/paid-marking deferred to Payroll |
| 14 | Certificate | Student, Mark | ✅ tests green | AdmitCard, TestimonialTemplate, Testimonial; Transfer Certificate lives in Student module; shared `App\Services\PdfRenderingService` (DomPDF, no Blade views) |
| 15 | IdCard | Student, Staff | ✅ tests green | IdCardTemplate, IdCardBatch, IdCardBatchFile; first queued job (Horizon `GenerateIdCardBatchJob`), 200-cards-per-PDF chunking, photos inlined as base64 (dompdf can't fetch remote URLs) |
| 16 | Report | Payment, Mark, Student | ✅ tests green | No new tables — pure aggregation. Fee Collection / Outstanding Dues / Student Ledger; JSON + streamed PDF; admin+accountant only; no cache |
| 17 | Sms | Student, Payment | ✅ tests green | SmsBatch, SmsLog; per-school billing (`schools.sms_api_key/sms_sender_id/sms_cost_per_segment`); `SmsSegmentCalculator` (GSM-7 160/153, unicode 70/67); stub `LogGateway` behind `SmsGatewayContract` |
| 18 | DataImport | Student, Academic | ✅ tests green | ImportBatch only (errors as JSON). Reuses `StudentService::enrol()`/`StaffService::hire()` per row; queued Horizon job reads MinIO file via `maatwebsite/excel` |
| 19 | OnlineAdmission | Academic, Student | ✅ tests green | AdmissionApplication (own table). Public `POST /v2/admission-applications` + status check (reference+phone). `approve()` calls `StudentService::enrol()` |
| 20 | Website | — | ✅ tests green | 9 tables: Page, PageRedirect, PageLayout, SiteLayout, SiteSetting, Menu, MenuItem, PageTemplate, WebsiteMedia. `layout_json` opaque LONGTEXT blob, every save is a NEW row (versioned). Public `/public/*` (pages, site-chrome, notices, staff, routine, stats, result-check) |
| 21 | Payroll *(optional)* | Staff | ✅ tests green | SalaryComponent, StaffSalaryValue, PayrollRun, PayrollEntry, SalaryCertificateRequest. Flat component sums only (no attendance proration). Integrates Loan's deferred repayment. Fixed a real bug: `User::abilitiesForRole()` never emitted `teacher:*`/`staff:*` wildcards, so those ability-gated routes never matched a real login |
| 22 | LMS *(optional)* | Academic, Student | ✅ tests green | Course, Lesson, Assignment, Submission, SubmissionAiCheck. Real Anthropic API integration (`AnthropicAiChecker`, Http-facade, no SDK). Introduced `school_module_settings`/`CheckModuleEnabled` (`module.enabled:{name}` middleware) — also retrofitted onto Payroll |
| 23 | Platform | — | ✅ tests green | Plan, PendingSchoolSignup, SubscriptionReminder. Platform-level (not tenant-scoped) — see `02-module-specs.md` |
| 24 | Library *(optional)* | Student, Staff | ⬜ pending |
| 25 | Transport *(optional)* | Student, Payment | ⬜ pending |
| 26 | Messaging *(optional)* | User | ⬜ pending |
