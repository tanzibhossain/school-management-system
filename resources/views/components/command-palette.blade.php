{{-- Command Palette Component --}}
@props([
    'trigger' => 'meta+k',
    'placeholder' => null,
    'class' => '',
    'maxResults' => 8,
])

@php
    $placeholder = $placeholder ?? __('Search commands...');
@endphp

@php
    $paletteId = 'command-palette-' . uniqid();
    $enabledModules = $enabledModules ?? [];

    // Generate route URLs for command palette
    $routes = [
        'dashboard' => route('admin.dashboard'),
        'students' => route('admin.students.index'),
        'students.create' => route('admin.students.create'),
        'staff' => route('admin.staff.index'),
        'school.edit' => route('admin.school.edit'),
        'modules' => route('admin.modules.index'),
        'pages' => route('admin.pages.index'),
        'page-templates' => route('admin.page-templates.index'),
        'academic-years' => route('admin.academic-years.index'),
        'classes' => route('admin.classes.index'),
        'subjects' => route('admin.subjects.index'),
        'groups' => route('admin.groups.index'),
        'versions' => route('admin.versions.index'),
        'shifts' => route('admin.shifts.index'),
        'routine' => route('admin.routine.index'),
        'designations' => route('admin.designations.index'),
        'departments' => route('admin.departments.index'),
        'admissions' => route('admin.admissions.index'),
        'data-import' => route('admin.data-import.index'),
        'users' => route('admin.users.index'),
        'fee-categories' => route('admin.fee-categories.index'),
        'fee-items' => route('admin.fee-items.index'),
        'fee-discounts' => route('admin.fee-discounts.index'),
        'invoices' => route('admin.invoices.index'),
        'payments' => route('admin.payments.index'),
        'refunds' => route('admin.refunds.index'),
        'student-credit' => route('admin.student-credit.index'),
        'payment-config' => route('admin.payment-config.edit'),
        'attendance' => route('admin.attendance.index'),
        'exam-types' => route('admin.exam-types.index'),
        'exams' => route('admin.exams.index'),
        'mark-settings' => route('admin.mark-settings.index'),
        'exam-halls' => route('admin.exam-halls.index'),
        'exam-marks-entry' => route('admin.exam-marks.entry', ['examId' => ':examId', 'divisionId' => ':divisionId']),
        'exam-marks-results' => route('admin.exam-marks.results', ['examId' => ':examId']),
        'exam-seating' => route('admin.exam-seating.index', ['examId' => ':examId']),
        'announcements' => route('admin.announcements.index'),
        'sms' => route('admin.sms.index'),
        'messages' => route('admin.messages.index'),
        'enquiries' => route('admin.enquiries.index'),
        'leave-types' => route('admin.leave-types.index'),
        'student-leave' => route('admin.student-leave.index'),
        'staff-leave' => route('admin.staff-leave.index'),
        'staff-loans' => route('admin.staff-loans.index'),
        'reports-fee' => route('admin.reports.fee-collection'),
        'reports-dues' => route('admin.reports.outstanding-dues'),
        'reports-ledger' => route('admin.reports.student-ledger'),
        'cert-templates' => route('admin.cert-templates.index'),
        'testimonials' => route('admin.testimonials.index'),
        'admit-cards' => route('admin.admit-cards.index'),
        'id-card-templates' => route('admin.id-card-templates.index'),
        'id-cards' => route('admin.id-cards.index'),
        'library.books' => route('admin.library.books.index'),
        'library.members' => route('admin.library.members.index'),
        'library.borrow' => route('admin.library.borrow.index'),
        'transport.drivers' => route('admin.transport.drivers.index'),
        'transport.vehicles' => route('admin.transport.vehicles.index'),
        'transport.routes' => route('admin.transport.routes.index'),
        'payroll.components' => route('admin.payroll.components.index'),
        'payroll.staff-salaries' => route('admin.payroll.staff-salaries.index'),
        'payroll.runs' => route('admin.payroll.runs.index'),
        'lms.courses' => route('admin.lms.courses.index'),
    ];
@endphp

<!-- Command Palette Modal -->
<div
    id="{{ $paletteId }}"
    class="command-palette fixed inset-0 z-[9999] hidden"
    role="dialog"
    aria-modal="true"
    aria-labelledby="command-palette-title"
>
    <!-- Backdrop -->
    <div
        class="command-palette-backdrop absolute inset-0 bg-black/50"
        data-command-palette-close
        aria-hidden="true"
    ></div>

    <!-- Palette Window -->
    <div
        class="command-palette-window relative w-full max-w-2xl mx-auto mt-20 rounded-xl bg-white shadow-2xl overflow-hidden"
        role="document"
    >
        <!-- Header -->
        <div class="flex items-center gap-3 p-4 border-b border-slate-200">
            <div class="flex items-center gap-2 text-slate-500">
                <kbd class="kbd px-2 py-1 text-xs font-mono bg-black text-white rounded js-shortcut-hint">{{ __('Ctrl K') }}</kbd>
                <span class="text-xs">{{ __('To Open') }}</span>
            </div>
            <div class="flex-1 relative">
                <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" aria-hidden="true"></i>
                <input
                    type="search"
                    id="command-palette-input"
                    class="command-input w-full pl-10 pr-4 py-2.5 text-base bg-slate-50 border-0 focus:outline-none focus:ring-0"
                    placeholder="{{ $placeholder }}"
                    autocomplete="off"
                    autocorrect="off"
                    autocapitalize="off"
                    spellcheck="false"
                    aria-label="{{ $placeholder }}"
                    autocomplete="off"
                    aria-autocomplete="list"
                    aria-controls="command-palette-results"
                    role="combobox"
                    aria-expanded="false"
                >
                <div class="absolute right-3 top-1/2 -translate-y-1/2 flex items-center gap-1 text-slate-400">
                    <kbd class="kbd px-1.5 py-0.5 text-[10px] font-mono bg-slate-100 rounded">↑</kbd>
                    <kbd class="kbd px-1.5 py-0.5 text-[10px] font-mono bg-slate-100 rounded">↓</kbd>
                    <kbd class="kbd px-1.5 py-0.5 text-[10px] font-mono bg-slate-100 rounded">⏎</kbd>
                    <kbd class="kbd px-1.5 py-0.5 text-[10px] font-mono bg-slate-100 rounded">{{ __('Esc') }}</kbd>
                </div>
            </div>
        </div>

        <!-- Results -->
        <div
            id="command-palette-results"
            class="command-results max-h-96 overflow-y-auto"
            role="listbox"
            aria-label="Commands"
        >
            <!-- Sections will be rendered here by JS -->
        </div>

        <!-- Empty State -->
        <div class="command-empty hidden p-8 text-center text-slate-500">
            <i class="bi bi-search text-4xl text-slate-300 mb-3"></i>
            <p class="text-slate-500">{{ __('No Commands Found') }}</p>
            <p class="text-sm text-slate-400 mt-1">{{ __('Try A Different Search Term') }}</p>
        </div>

        <!-- Footer Hint -->
        <div class="p-3 border-t border-slate-100 bg-slate-50">
            <div class="flex items-center justify-between text-xs text-slate-400">
                <span>{{ __('Navigate With') }} <kbd class="kbd">↑</kbd><kbd class="kbd">↓</kbd>, select with <kbd class="kbd">⏎</kbd>, close with <kbd class="kbd">{{ __('Esc') }}</kbd></span>
                <span class="text-slate-300 js-shortcut-hint">{{ __('Ctrl K') }}</span>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function() {
    // ─── Route URLs from PHP ───
    const routes = @json($routes);

    // ─── Command Data ───
    const commandData = [
        // Navigation
        { id: 'dashboard', label: {!! json_encode(__('Dashboard')) !!}, description: {!! json_encode(__('Go to dashboard')) !!}, section: {!! json_encode(__('Navigation')) !!}, icon: 'bi-speedometer2', url: routes.dashboard, keywords: 'home main overview', shortcut: 'g d' },
        { id: 'students', label: {!! json_encode(__('Students')) !!}, description: {!! json_encode(__('Manage students')) !!}, section: {!! json_encode(__('Navigation')) !!}, icon: 'bi-people-fill', url: routes.students, keywords: 'pupils list', shortcut: 'g s' },
        { id: 'students-create', label: {!! json_encode(__('New Student')) !!}, description: {!! json_encode(__('Add new student')) !!}, section: {!! json_encode(__('Navigation')) !!}, icon: 'bi-person-plus', url: routes['students.create'], keywords: 'add new pupil', shortcut: 'n s' },
        { id: 'staff', label: {!! json_encode(__('Staff')) !!}, description: {!! json_encode(__('Manage staff')) !!}, section: {!! json_encode(__('Navigation')) !!}, icon: 'bi-person-badge', url: routes.staff, keywords: 'teachers employees', shortcut: 'g t' },

        // Setup
        { id: 'school-settings', label: {!! json_encode(__('School Settings')) !!}, description: {!! json_encode(__('Configure school settings')) !!}, section: {!! json_encode(__('Setup')) !!}, icon: 'bi-building-gear', url: routes['school.edit'], keywords: 'configuration', shortcut: 'g c' },
        { id: 'modules', label: {!! json_encode(__('Modules')) !!}, description: {!! json_encode(__('Enable/disable modules')) !!}, section: {!! json_encode(__('Setup')) !!}, icon: 'bi-toggles', url: routes.modules, keywords: 'features toggle optional', shortcut: 'g m' },
        { id: 'pages', label: {!! json_encode(__('Website Pages')) !!}, description: {!! json_encode(__('Manage website pages')) !!}, section: {!! json_encode(__('Setup')) !!}, icon: 'bi-window', url: routes.pages, keywords: 'website content', shortcut: 'g p' },
        { id: 'page-templates', label: {!! json_encode(__('Page Templates')) !!}, description: {!! json_encode(__('Manage saved page templates')) !!}, section: {!! json_encode(__('Setup')) !!}, icon: 'bi-window-stack', url: routes['page-templates'], keywords: 'website starter save as template', shortcut: 'g w' },
        { id: 'academic-years', label: {!! json_encode(__('Academic Years')) !!}, description: {!! json_encode(__('Manage academic years')) !!}, section: {!! json_encode(__('Setup')) !!}, icon: 'bi-calendar3', url: routes['academic-years'], keywords: 'years sessions', shortcut: 'g y' },
        { id: 'classes', label: {!! json_encode(__('Classes & Sections')) !!}, description: {!! json_encode(__('Manage classes and sections')) !!}, section: {!! json_encode(__('Setup')) !!}, icon: 'bi-diagram-3', url: routes.classes, keywords: 'classrooms grades', shortcut: 'g c' },
        { id: 'subjects', label: {!! json_encode(__('Subjects')) !!}, description: {!! json_encode(__('Manage subjects')) !!}, section: {!! json_encode(__('Setup')) !!}, icon: 'bi-book', url: routes.subjects, keywords: 'courses', shortcut: 'g b' },
        { id: 'academic-groups', label: {!! json_encode(__('Academic Groups')) !!}, description: {!! json_encode(__('Manage academic groups')) !!}, section: {!! json_encode(__('Setup')) !!}, icon: 'bi-people', url: routes.groups, keywords: 'streams tracks', shortcut: 'g g' },
        { id: 'versions', label: {!! json_encode(__('Versions')) !!}, description: {!! json_encode(__('Manage versions')) !!}, section: {!! json_encode(__('Setup')) !!}, icon: 'bi-translate', url: routes.versions, keywords: 'streams', shortcut: 'g v' },
        { id: 'shifts', label: {!! json_encode(__('Shifts')) !!}, description: {!! json_encode(__('Manage shifts')) !!}, section: {!! json_encode(__('Setup')) !!}, icon: 'bi-clock-history', url: routes.shifts, keywords: 'morning evening', shortcut: 'g h' },
        { id: 'routine', label: {!! json_encode(__('Class Routine')) !!}, description: {!! json_encode(__('Manage class routine')) !!}, section: {!! json_encode(__('Setup')) !!}, icon: 'bi-calendar3-week', url: routes.routine, keywords: 'schedule timetable', shortcut: 'g r' },

        // People
        { id: 'designations', label: {!! json_encode(__('Designations')) !!}, description: {!! json_encode(__('Manage designations')) !!}, section: {!! json_encode(__('People')) !!}, icon: 'bi-award', url: routes.designations, keywords: 'roles titles', shortcut: 'g d' },
        { id: 'departments', label: {!! json_encode(__('Departments')) !!}, description: {!! json_encode(__('Manage departments')) !!}, section: {!! json_encode(__('People')) !!}, icon: 'bi-building', url: routes.departments, keywords: 'divisions', shortcut: 'g e' },
        { id: 'admissions', label: {!! json_encode(__('Admissions')) !!}, description: {!! json_encode(__('Manage admissions')) !!}, section: {!! json_encode(__('People')) !!}, icon: 'bi-clipboard-check', url: routes.admissions, keywords: 'applications', shortcut: 'g a' },
        { id: 'data-import', label: {!! json_encode(__('Data Import')) !!}, description: {!! json_encode(__('Import students/staff')) !!}, section: {!! json_encode(__('People')) !!}, icon: 'bi-upload', url: routes['data-import'], keywords: 'bulk upload csv excel', shortcut: 'g i' },
        { id: 'users', label: {!! json_encode(__('Users & Roles')) !!}, description: {!! json_encode(__('Manage users and roles')) !!}, section: {!! json_encode(__('People')) !!}, icon: 'bi-person-gear', url: routes.users, keywords: 'accounts permissions', shortcut: 'g u' },

        // Finance
        { id: 'fee-categories', label: {!! json_encode(__('Fee Categories')) !!}, description: {!! json_encode(__('Manage fee categories')) !!}, section: {!! json_encode(__('Finance')) !!}, icon: 'bi-tags', url: routes['fee-categories'], keywords: 'fees types', shortcut: 'f c' },
        { id: 'fee-items', label: {!! json_encode(__('Fee Items')) !!}, description: {!! json_encode(__('Manage fee items')) !!}, section: {!! json_encode(__('Finance')) !!}, icon: 'bi-cash-stack', url: routes['fee-items'], keywords: 'fees charges', shortcut: 'f i' },
        { id: 'discounts', label: {!! json_encode(__('Discounts')) !!}, description: {!! json_encode(__('Manage fee discounts')) !!}, section: {!! json_encode(__('Finance')) !!}, icon: 'bi-percent', url: routes['fee-discounts'], keywords: 'concessions scholarships', shortcut: 'f d' },
        { id: 'invoices', label: {!! json_encode(__('Invoices')) !!}, description: {!! json_encode(__('Manage invoices')) !!}, section: {!! json_encode(__('Finance')) !!}, icon: 'bi-receipt', url: routes.invoices, keywords: 'bills', shortcut: 'f v' },
        { id: 'payments', label: {!! json_encode(__('Payments')) !!}, description: {!! json_encode(__('Record payments')) !!}, section: {!! json_encode(__('Finance')) !!}, icon: 'bi-credit-card', url: routes.payments, keywords: 'transactions', shortcut: 'f p' },
        { id: 'refunds', label: {!! json_encode(__('Refunds')) !!}, description: {!! json_encode(__('Process refunds')) !!}, section: {!! json_encode(__('Finance')) !!}, icon: 'bi-arrow-return-left', url: routes.refunds, keywords: 'reimbursements', shortcut: 'f r' },
        { id: 'student-credit', label: {!! json_encode(__('Student Credit')) !!}, description: {!! json_encode(__('Manage student credit')) !!}, section: {!! json_encode(__('Finance')) !!}, icon: 'bi-wallet2', url: routes['student-credit'], keywords: 'balance ledger', shortcut: 'f s' },
        { id: 'payment-config', label: {!! json_encode(__('Payment Settings')) !!}, description: {!! json_encode(__('Payment mode, gateways & credentials')) !!}, section: {!! json_encode(__('Setup')) !!}, icon: 'bi-credit-card', url: routes['payment-config'], keywords: 'gateway settings bkash sslcommerz online offline', shortcut: 'g y' },

        // Academics
        { id: 'attendance', label: {!! json_encode(__('Attendance')) !!}, description: {!! json_encode(__('Record attendance')) !!}, section: {!! json_encode(__('Academics')) !!}, icon: 'bi-calendar-check', url: routes.attendance, keywords: 'presence roll-call', shortcut: 'a a' },
        { id: 'exam-types', label: {!! json_encode(__('Exam Types')) !!}, description: {!! json_encode(__('Manage exam types')) !!}, section: {!! json_encode(__('Academics')) !!}, icon: 'bi-card-list', url: routes['exam-types'], keywords: 'examination types', shortcut: 'a e' },
        { id: 'exams', label: {!! json_encode(__('Exams')) !!}, description: {!! json_encode(__('Manage exams')) !!}, section: {!! json_encode(__('Academics')) !!}, icon: 'bi-journal-text', url: routes.exams, keywords: 'examinations tests', shortcut: 'a x' },
        { id: 'mark-settings', label: {!! json_encode(__('Mark Settings')) !!}, description: {!! json_encode(__('Configure mark settings')) !!}, section: {!! json_encode(__('Academics')) !!}, icon: 'bi-sliders', url: routes['mark-settings'], keywords: 'grading configuration', shortcut: 'a m' },
        { id: 'exam-halls', label: {!! json_encode(__('Exam Halls')) !!}, description: {!! json_encode(__('Manage exam halls')) !!}, section: {!! json_encode(__('Academics')) !!}, icon: 'bi-grid-3x3', url: routes['exam-halls'], keywords: 'rooms venues', shortcut: 'a h' },

        // Exam sub-pages (require exam context - show when on exam pages)
        { id: 'exam-marks-entry', label: {!! json_encode(__('Mark Entry')) !!}, description: {!! json_encode(__('Enter marks for exam')) !!}, section: {!! json_encode(__('Academics')) !!}, icon: 'bi-pencil-square', url: '#', keywords: 'marks entry grades', shortcut: 'm e', context: 'exam' },
        { id: 'exam-marks-results', label: {!! json_encode(__('Exam Results')) !!}, description: {!! json_encode(__('View exam results')) !!}, section: {!! json_encode(__('Academics')) !!}, icon: 'bi-bar-chart', url: '#', keywords: 'results tabulation', shortcut: 'm r', context: 'exam' },
        { id: 'exam-seating', label: {!! json_encode(__('Exam Seating')) !!}, description: {!! json_encode(__('Manage exam seating')) !!}, section: {!! json_encode(__('Academics')) !!}, icon: 'bi-grid-3x3-gap', url: '#', keywords: 'seating arrangement', shortcut: 'm s', context: 'exam' },

        // Comms
        { id: 'announcements', label: {!! json_encode(__('Announcements')) !!}, description: {!! json_encode(__('Manage announcements')) !!}, section: {!! json_encode(__('Comms')) !!}, icon: 'bi-megaphone', url: routes.announcements, keywords: 'notices circulars', shortcut: 'c a' },
        { id: 'sms', label: {!! json_encode(__('SMS')) !!}, description: {!! json_encode(__('Send SMS')) !!}, section: {!! json_encode(__('Comms')) !!}, icon: 'bi-chat-dots', url: routes.sms, keywords: 'text messages', shortcut: 'c s' },
        { id: 'messages', label: {!! json_encode(__('Messages')) !!}, description: {!! json_encode(__('View messages')) !!}, section: {!! json_encode(__('Comms')) !!}, icon: 'bi-chat-left-text', url: routes.messages, keywords: 'chat inbox', shortcut: 'c m' },
        { id: 'enquiries', label: {!! json_encode(__('Enquiries')) !!}, description: {!! json_encode(__('Contact-form enquiries')) !!}, section: {!! json_encode(__('Comms')) !!}, icon: 'bi-envelope-paper', url: routes.enquiries, keywords: 'contact messages inbox', shortcut: 'c q' },

        // HR
        { id: 'leave-types', label: {!! json_encode(__('Leave Types')) !!}, description: {!! json_encode(__('Manage leave types')) !!}, section: {!! json_encode(__('HR')) !!}, icon: 'bi-card-checklist', url: routes['leave-types'], keywords: 'vacation sick', shortcut: 'h l' },
        { id: 'student-leave', label: {!! json_encode(__('Student Leave')) !!}, description: {!! json_encode(__('Student leave requests')) !!}, section: {!! json_encode(__('HR')) !!}, icon: 'bi-person-vcard', url: routes['student-leave'], keywords: 'absences', shortcut: 'h s' },
        { id: 'staff-leave', label: {!! json_encode(__('Staff Leave')) !!}, description: {!! json_encode(__('Staff leave requests')) !!}, section: {!! json_encode(__('HR')) !!}, icon: 'bi-person-workspace', url: routes['staff-leave'], keywords: 'teacher absence', shortcut: 'h t' },
        { id: 'staff-loans', label: {!! json_encode(__('Staff Loans')) !!}, description: {!! json_encode(__('Staff loan requests')) !!}, section: {!! json_encode(__('HR')) !!}, icon: 'bi-cash-stack', url: routes['staff-loans'], keywords: 'advances', shortcut: 'h n' },

        // Reports
        { id: 'reports-fee', label: {!! json_encode(__('Fee Collection')) !!}, description: {!! json_encode(__('Fee collection report')) !!}, section: {!! json_encode(__('Reports')) !!}, icon: 'bi-file-earmark-bar-graph', url: routes['reports-fee'], keywords: 'revenue', shortcut: 'r f' },
        { id: 'reports-dues', label: {!! json_encode(__('Outstanding Dues')) !!}, description: {!! json_encode(__('Outstanding dues report')) !!}, section: {!! json_encode(__('Reports')) !!}, icon: 'bi-file-earmark-bar-graph', url: routes['reports-dues'], keywords: 'arrears', shortcut: 'r o' },
        { id: 'reports-ledger', label: {!! json_encode(__('Student Ledger')) !!}, description: {!! json_encode(__('Student ledger report')) !!}, section: {!! json_encode(__('Reports')) !!}, icon: 'bi-file-earmark-bar-graph', url: routes['reports-ledger'], keywords: 'ledger statement', shortcut: 'r l' },

        // Optional Modules
        { id: 'library', label: {!! json_encode(__('Library')) !!}, description: {!! json_encode(__('Manage library')) !!}, section: {!! json_encode(__('Optional')) !!}, icon: 'bi-book-half', url: routes['library.books'], keywords: 'books borrow return', condition: 'library', shortcut: 'o l' },
        { id: 'library-members', label: {!! json_encode(__('Library Members')) !!}, description: {!! json_encode(__('Manage library members')) !!}, section: {!! json_encode(__('Optional')) !!}, icon: 'bi-person-lines-fill', url: routes['library.members'], keywords: 'members borrowers', condition: 'library', shortcut: 'o m' },
        { id: 'library-borrow', label: {!! json_encode(__('Borrow/Return')) !!}, description: {!! json_encode(__('Manage borrow/return')) !!}, section: {!! json_encode(__('Optional')) !!}, icon: 'bi-arrow-left-right', url: routes['library.borrow'], keywords: 'issue return books', condition: 'library', shortcut: 'o b' },
        { id: 'transport', label: {!! json_encode(__('Transport')) !!}, description: {!! json_encode(__('Manage transport')) !!}, section: {!! json_encode(__('Optional')) !!}, icon: 'bi-bus-front', url: routes['transport.routes'], keywords: 'bus routes vehicles', condition: 'transport', shortcut: 'o t' },
        { id: 'transport-drivers', label: {!! json_encode(__('Drivers')) !!}, description: {!! json_encode(__('Manage drivers')) !!}, section: {!! json_encode(__('Optional')) !!}, icon: 'bi-person-badge', url: routes['transport.drivers'], keywords: 'drivers staff', condition: 'transport', shortcut: 'o d' },
        { id: 'transport-vehicles', label: {!! json_encode(__('Vehicles')) !!}, description: {!! json_encode(__('Manage vehicles')) !!}, section: {!! json_encode(__('Optional')) !!}, icon: 'bi-truck', url: routes['transport.vehicles'], keywords: 'buses vans', condition: 'transport', shortcut: 'o v' },
        { id: 'payroll', label: {!! json_encode(__('Payroll')) !!}, description: {!! json_encode(__('Manage payroll')) !!}, section: {!! json_encode(__('Optional')) !!}, icon: 'bi-cash-coin', url: routes['payroll.runs'], keywords: 'salary payroll', condition: 'payroll', shortcut: 'o p' },
        { id: 'payroll-components', label: {!! json_encode(__('Salary Components')) !!}, description: {!! json_encode(__('Manage salary components')) !!}, section: {!! json_encode(__('Optional')) !!}, icon: 'bi-sliders', url: routes['payroll.components'], keywords: 'components allowances', condition: 'payroll', shortcut: 'o c' },
        { id: 'payroll-salaries', label: {!! json_encode(__('Staff Salaries')) !!}, description: {!! json_encode(__('Manage staff salaries')) !!}, section: {!! json_encode(__('Optional')) !!}, icon: 'bi-person-badge', url: routes['payroll.staff-salaries'], keywords: 'salaries payslips', condition: 'payroll', shortcut: 'o s' },
        { id: 'lms', label: {!! json_encode(__('LMS')) !!}, description: {!! json_encode(__('Learning management')) !!}, section: {!! json_encode(__('Optional')) !!}, icon: 'bi-easel', url: routes['lms.courses'], keywords: 'courses lessons', condition: 'lms', shortcut: 'o e' },

        // Certificates & IDs
        { id: 'cert-templates', label: {!! json_encode(__('Certificate Templates')) !!}, description: {!! json_encode(__('Manage certificate templates')) !!}, section: {!! json_encode(__('Certificates')) !!}, icon: 'bi-file-earmark-text', url: routes['cert-templates'], keywords: 'templates design', shortcut: 't c' },
        { id: 'testimonials', label: {!! json_encode(__('Testimonials')) !!}, description: {!! json_encode(__('Issue testimonials')) !!}, section: {!! json_encode(__('Certificates')) !!}, icon: 'bi-award', url: routes.testimonials, keywords: 'testimonial certificate', shortcut: 't t' },
        { id: 'admit-cards', label: {!! json_encode(__('Admit Cards')) !!}, description: {!! json_encode(__('Generate admit cards')) !!}, section: {!! json_encode(__('Certificates')) !!}, icon: 'bi-card-checklist', url: routes['admit-cards'], keywords: 'admit card hall ticket', shortcut: 't a' },
        { id: 'id-card-templates', label: {!! json_encode(__('ID Card Templates')) !!}, description: {!! json_encode(__('Manage ID card templates')) !!}, section: {!! json_encode(__('Certificates')) !!}, icon: 'bi-credit-card-2-front', url: routes['id-card-templates'], keywords: 'id card template design', shortcut: 't i' },
        { id: 'id-cards', label: {!! json_encode(__('ID Cards')) !!}, description: {!! json_encode(__('Generate ID cards')) !!}, section: {!! json_encode(__('Certificates')) !!}, icon: 'bi-person-badge', url: routes['id-cards'], keywords: 'id card batch generate', shortcut: 't d' },

        // Actions
        { id: 'new-student', label: {!! json_encode(__('New Student')) !!}, description: {!! json_encode(__('Create new student')) !!}, section: {!! json_encode(__('Actions')) !!}, icon: 'bi-person-plus', url: routes['students.create'], keywords: 'add pupil register', shortcut: 'n s' },
        { id: 'new-admission', label: {!! json_encode(__('New Admission')) !!}, description: {!! json_encode(__('Process new admission')) !!}, section: {!! json_encode(__('Actions')) !!}, icon: 'bi-clipboard-check', url: routes.admissions, keywords: 'enroll register', shortcut: 'n a' },
    ];

    // ─── Fuzzy Search (Optimized) ───
    function fuzzyMatch(query, item) {
        if (!query) return 0;

        const needle = query.toLowerCase();
        const haystack = [
            item.label,
            item.description,
            item.section,
            item.keywords,
            item.shortcut || ''
        ].join(' ').toLowerCase();

        // Quick reject for empty query
        if (!needle.trim()) return 0;

        let score = 0;
        let haystackIndex = 0;

        for (let i = 0; i < needle.length; i++) {
            const char = needle[i];
            const index = haystack.indexOf(char, haystackIndex);
            if (index === -1) return -1;
            score += (index - haystackIndex) * 0.1;
            haystackIndex = index + 1;
        }

        // Boost scoring
        const labelLower = item.label.toLowerCase();
        const sectionLower = item.section.toLowerCase();
        const keywordsLower = item.keywords.toLowerCase();

        if (labelLower.startsWith(needle)) score -= 15;
        if (labelLower === needle) score -= 25;
        if (sectionLower.startsWith(needle)) score -= 8;
        if (item.keywords && keywordsLower.includes(needle)) score -= 5;
        if (item.shortcut && item.shortcut.toLowerCase().includes(needle)) score -= 10;

        // Penalize longer matches
        score += needle.length * 0.5;

        return score;
    }

    // ─── Render Results ───
    function renderResults(query, container, maxResults) {
        const filtered = commandData
            .filter(item => {
                if (item.condition && !window.enabledModules?.includes(item.condition)) {
                    return false;
                }
                // Skip context-dependent items unless we're on the right page
                if (item.context === 'exam' && !window.location.pathname.includes('/exams/')) {
                    return false;
                }
                return fuzzyMatch(query, item) !== -1;
            })
            .sort((a, b) => fuzzyMatch(query, a) - fuzzyMatch(query, b))
            .slice(0, maxResults);

        // Group by section
        const sections = {};
        filtered.forEach(item => {
            if (!sections[item.section]) sections[item.section] = [];
            sections[item.section].push(item);
        });

        const resultsContainer = container.querySelector('.command-results');
        const emptyState = container.querySelector('.command-empty');

        if (Object.keys(sections).length === 0) {
            resultsContainer.innerHTML = '';
            container.querySelector('.command-empty').classList.remove('hidden');
            return;
        }

        container.querySelector('.command-empty').classList.add('hidden');

        let html = '';
        for (const [section, items] of Object.entries(sections)) {
            html += `
                <div class="command-section" role="group" aria-label="${section}">
                    <div class="command-section-header px-4 py-2 text-xs font-semibold text-slate-400 uppercase tracking-wider bg-slate-50 border-b border-slate-100">
                        ${section}
                    </div>
                    ${items.map((item, idx) => `
                        <a href="${item.url}" class="command-item flex items-center gap-3 px-4 py-2.5 hover:bg-slate-50 transition-colors" role="option" data-id="${item.id}" tabindex="-1">
                            <i class="bi ${item.icon} text-slate-400 w-5 text-center" aria-hidden="true"></i>
                            <div class="flex-1 min-w-0">
                                <div class="font-medium text-slate-900 truncate">${item.label}</div>
                                <div class="text-xs text-slate-500 truncate">${item.description}</div>
                            </div>
                            ${item.shortcut ? `<kbd class="kbd px-2 py-0.5 text-[10px] font-mono bg-slate-100 rounded text-slate-500">${item.shortcut}</kbd>` : ''}
                        </a>
                    `).join('')}
                </div>
            `;
        }

        container.querySelector('.command-results').innerHTML = html;
    }

    // ─── Initialize ───
    document.addEventListener('DOMContentLoaded', function() {
        const palette = document.getElementById('{{ $paletteId }}');
        const input = document.getElementById('command-palette-input');
        const resultsContainer = palette?.querySelector('.command-results');
        const emptyState = palette?.querySelector('.command-empty');

        if (!palette || !input) return;

        let selectedIndex = -1;
        let isOpen = false;
        let debounceTimer = null;
        const MAX_RESULTS = {{ $maxResults }};
        const ENABLED_MODULES = @json($enabledModules);

        // Make enabled modules available globally for filtering
        window.enabledModules = @json($enabledModules);

        function open() {
            palette.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
            input.value = '';
            input.focus();
            isOpen = true;
            selectedIndex = -1;
            renderResults('', palette);
            document.addEventListener('keydown', handleKeydown);
            input.setAttribute('aria-expanded', 'true');
        }

        function close() {
            palette.classList.add('hidden');
            document.body.style.overflow = '';
            isOpen = false;
            document.removeEventListener('keydown', handleKeydown);
            input.setAttribute('aria-expanded', 'false');
        }

        function handleKeydown(e) {
            if (!isOpen) return;

            const items = palette.querySelectorAll('.command-item');

            switch (e.key) {
                case 'Escape':
                    e.preventDefault();
                    close();
                    break;
                case 'ArrowDown':
                    e.preventDefault();
                    selectedIndex = Math.min(selectedIndex + 1, items.length - 1);
                    updateSelection(items);
                    break;
                case 'ArrowUp':
                    e.preventDefault();
                    selectedIndex = Math.max(selectedIndex - 1, -1);
                    updateSelection(items);
                    break;
                case 'Enter':
                    e.preventDefault();
                    if (selectedIndex >= 0 && items[selectedIndex]) {
                        items[selectedIndex].click();
                    }
                    break;
                case 'Tab':
                    // Allow tab to close
                    close();
                    break;
            }
        }

        function updateSelection(items) {
            items.forEach((item, index) => {
                const isSelected = index === selectedIndex;
                item.classList.toggle('bg-slate-50', isSelected);
                item.classList.toggle('ring-2', isSelected);
                item.classList.toggle('ring-primary-500', isSelected);
                item.setAttribute('aria-selected', isSelected);
                if (isSelected) {
                    item.scrollIntoView({ block: 'nearest' });
                }
            });
        }

        // ─── Open Handlers ───
        document.addEventListener('keydown', function(e) {
            const isMac = navigator.platform.toUpperCase().indexOf('MAC') >= 0;
            const modifier = e.metaKey || (e.ctrlKey && !isMac);
            const key = e.key.toLowerCase();

            if (modifier && key === 'k') {
                e.preventDefault();
                if (!isOpen) open();
            }
        });

        // Let other UI (e.g. the header search box) open the palette.
        document.addEventListener('command-palette:open', function() {
            if (!isOpen) open();
        });

        // ─── Input Handling ───
        input.addEventListener('input', function() {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                renderResults(this.value, palette, {{ $maxResults }});
                selectedIndex = -1;
            }, 50);
        });

        input.addEventListener('blur', function(e) {
            setTimeout(() => {
                if (!palette.contains(document.activeElement)) {
                    close();
                }
            }, 200);
        });

        // Click outside to close
        palette?.querySelector('.command-palette-backdrop')?.addEventListener('click', close);

        // Handle item clicks
        palette?.addEventListener('click', function(e) {
            const item = e.target.closest('.command-item');
            if (item) {
                close();
            }
        });

        // Expose globally
        window.CommandPalette = { open, close };

        // Expose for testing
        window.__COMMAND_PALETTE__ = {
            open,
            close,
            renderResults,
            fuzzyMatch: function(query, item) { return fuzzyMatch(query, item); }
        };
    });
})();
</script>
@endpush