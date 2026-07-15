{{-- Command Palette Component --}}
@props([
    'trigger' => 'meta+k', // meta+k, ctrl+k, etc.
    'placeholder' => 'Search commands...',
    'class' => '',
])

@php
    $paletteId = 'command-palette-' . uniqid();
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
                <kbd class="kbd px-2 py-1 text-xs font-mono bg-slate-100 rounded">
                    {{ Str::upper(str_replace('+', ' + ', $trigger)) }}
                </kbd>
                <span class="text-xs">to open</span>
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
                >
                <div class="absolute right-3 top-1/2 -translate-y-1/2 flex items-center gap-1 text-slate-400">
                    <kbd class="kbd px-1.5 py-0.5 text-[10px] font-mono bg-slate-100 rounded">↑</kbd>
                    <kbd class="kbd px-1.5 py-0.5 text-[10px] font-mono bg-slate-100 rounded">↓</kbd>
                    <kbd class="kbd px-1.5 py-0.5 text-[10px] font-mono bg-slate-100 rounded">⏎</kbd>
                    <kbd class="kbd px-1.5 py-0.5 text-[10px] font-mono bg-slate-100 rounded">Esc</kbd>
                </div>
            </div>
        </div>

        <!-- Results -->
        <div class="command-results max-h-96 overflow-y-auto">
            <!-- Sections will be rendered here by JS -->
        </div>

        <!-- Empty State -->
        <div class="command-empty hidden p-8 text-center text-slate-500">
            <i class="bi bi-search text-4xl text-slate-300 mb-3"></i>
            <p class="text-slate-500">No commands found</p>
            <p class="text-sm text-slate-400 mt-1">Try a different search term</p>
        </div>

        <!-- Footer Hint -->
        <div class="p-3 border-t border-slate-100 bg-slate-50">
            <div class="flex items-center justify-between text-xs text-slate-400">
                <span>Navigate with <kbd class="kbd">↑</kbd><kbd class="kbd">↓</kbd>, select with <kbd class="kbd">⏎</kbd>, close with <kbd class="kbd">Esc</kbd></span>
                <span>Built with ❤️</span>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function() {
    // Command Palette Data
    const commandData = [
        // Navigation
        { id: 'dashboard', label: 'Dashboard', description: 'Go to dashboard', section: 'Navigation', icon: 'bi-speedometer2', url: '/admin', keywords: 'home main overview' },
        { id: 'students', label: 'Students', description: 'Manage students', section: 'Navigation', icon: 'bi-people-fill', url: '/admin/students', keywords: 'pupils list' },
        { id: 'students-create', label: 'New Student', description: 'Add new student', section: 'Navigation', icon: 'bi-person-plus', url: '/admin/students/create', keywords: 'add new pupil' },
        { id: 'staff', label: 'Staff', description: 'Manage staff', section: 'Navigation', icon: 'bi-person-badge', url: '/admin/staff', keywords: 'teachers employees' },
        { id: 'staff-create', label: 'New Staff', description: 'Add new staff member', section: 'Navigation', icon: 'bi-person-badge-plus', url: '/admin/staff/create', keywords: 'add teacher employee' },

        // Setup
        { id: 'school-settings', label: 'School Settings', description: 'Configure school settings', section: 'Setup', icon: 'bi-building-gear', url: '/admin/school', keywords: 'configuration' },
        { id: 'modules', label: 'Modules', description: 'Enable/disable modules', section: 'Setup', icon: 'bi-toggles', url: '/admin/modules', keywords: 'features toggle' },
        { id: 'pages', label: 'Website Pages', description: 'Manage website pages', section: 'Setup', icon: 'bi-window', url: '/admin/pages', keywords: 'website content' },
        { id: 'academic-years', label: 'Academic Years', description: 'Manage academic years', section: 'Setup', icon: 'bi-calendar3', url: '/admin/academic-years', keywords: 'years sessions' },
        { id: 'classes', label: 'Classes & Sections', description: 'Manage classes and sections', section: 'Setup', icon: 'bi-diagram-3', url: '/admin/classes', keywords: 'classrooms grades' },
        { id: 'subjects', label: 'Subjects', description: 'Manage subjects', section: 'Setup', icon: 'bi-book', url: '/admin/subjects', keywords: 'courses' },
        { id: 'academic-groups', label: 'Academic Groups', description: 'Manage academic groups', section: 'Setup', icon: 'bi-people', url: '/admin/groups', keywords: 'streams tracks' },
        { id: 'versions', label: 'Versions', description: 'Manage versions', section: 'Setup', icon: 'bi-translate', url: '/admin/versions', keywords: 'streams' },
        { id: 'shifts', label: 'Shifts', description: 'Manage shifts', section: 'Setup', icon: 'bi-clock-history', url: '/admin/shifts', keywords: 'morning evening' },
        { id: 'routine', label: 'Class Routine', description: 'Manage class routine', section: 'Setup', icon: 'bi-calendar3-week', url: '/admin/routine', keywords: 'schedule timetable' },

        // People
        { id: 'students-index', label: 'Students', description: 'List all students', section: 'People', icon: 'bi-people-fill', url: '/admin/students', keywords: 'pupils list' },
        { id: 'staff-index', label: 'Staff', description: 'List all staff', section: 'People', icon: 'bi-person-badge', url: '/admin/staff', keywords: 'teachers employees list' },
        { id: 'designations', label: 'Designations', description: 'Manage designations', section: 'People', icon: 'bi-award', url: '/admin/designations', keywords: 'roles titles' },
        { id: 'departments', label: 'Departments', description: 'Manage departments', section: 'People', icon: 'bi-building', url: '/admin/departments', keywords: 'divisions' },
        { id: 'admissions', label: 'Admissions', description: 'Manage admissions', section: 'People', icon: 'bi-clipboard-check', url: '/admin/admissions', keywords: 'applications' },
        { id: 'data-import', label: 'Data Import', description: 'Import students/staff', section: 'People', icon: 'bi-upload', url: '/admin/data-import', keywords: 'bulk upload csv excel' },
        { id: 'users', label: 'Users & Roles', description: 'Manage users and roles', section: 'People', icon: 'bi-person-gear', url: '/admin/users', keywords: 'accounts permissions' },

        // Finance
        { id: 'fee-categories', label: 'Fee Categories', description: 'Manage fee categories', section: 'Finance', icon: 'bi-tags', url: '/admin/fee-categories', keywords: 'fees types' },
        { id: 'fee-items', label: 'Fee Items', description: 'Manage fee items', section: 'Finance', icon: 'bi-cash-stack', url: '/admin/fee-items', keywords: 'fees charges' },
        { id: 'discounts', label: 'Discounts', description: 'Manage fee discounts', section: 'Finance', icon: 'bi-percent', url: '/admin/fee-discounts', keywords: 'concessions scholarships' },
        { id: 'invoices', label: 'Invoices', description: 'Manage invoices', section: 'Finance', icon: 'bi-receipt', url: '/admin/invoices', keywords: 'bills' },
        { id: 'payments', label: 'Payments', description: 'Record payments', section: 'Finance', icon: 'bi-credit-card', url: '/admin/payments', keywords: 'transactions' },
        { id: 'refunds', label: 'Refunds', description: 'Process refunds', section: 'Finance', icon: 'bi-arrow-return-left', url: '/admin/refunds', keywords: 'reimbursements' },
        { id: 'student-credit', label: 'Student Credit', description: 'Manage student credit', section: 'Finance', icon: 'bi-wallet2', url: '/admin/student-credit', keywords: 'balance ledger' },
        { id: 'payment-config', label: 'Payment Config', description: 'Configure payment gateways', section: 'Finance', icon: 'bi-gear', url: '/admin/payment-config', keywords: 'gateway settings' },

        // Academics
        { id: 'attendance', label: 'Attendance', description: 'Record attendance', section: 'Academics', icon: 'bi-calendar-check', url: '/admin/attendance', keywords: 'presence roll-call' },
        { id: 'exam-types', label: 'Exam Types', description: 'Manage exam types', section: 'Academics', icon: 'bi-card-list', url: '/admin/exam-types', keywords: 'examination types' },
        { id: 'exams', label: 'Exams', description: 'Manage exams', section: 'Academics', icon: 'bi-journal-text', url: '/admin/exams', keywords: 'examinations tests' },
        { id: 'mark-settings', label: 'Mark Settings', description: 'Configure mark settings', section: 'Academics', icon: 'bi-sliders', url: '/admin/mark-settings', keywords: 'grading configuration' },
        { id: 'exam-halls', label: 'Exam Halls', description: 'Manage exam halls', section: 'Academics', icon: 'bi-grid-3x3', url: '/admin/exam-halls', keywords: 'rooms venues' },

        // Comms
        { id: 'announcements', label: 'Announcements', description: 'Manage announcements', section: 'Comms', icon: 'bi-megaphone', url: '/admin/announcements', keywords: 'notices circulars' },
        { id: 'sms', label: 'SMS', description: 'Send SMS', section: 'Comms', icon: 'bi-chat-dots', url: '/admin/sms', keywords: 'text messages' },
        { id: 'messages', label: 'Messages', description: 'View messages', section: 'Comms', icon: 'bi-chat-left-text', url: '/admin/messages', keywords: 'chat inbox' },

        // HR
        { id: 'leave-types', label: 'Leave Types', description: 'Manage leave types', section: 'HR', icon: 'bi-card-checklist', url: '/admin/leave-types', keywords: 'vacation sick' },
        { id: 'student-leave', label: 'Student Leave', description: 'Student leave requests', section: 'HR', icon: 'bi-person-vcard', url: '/admin/student-leave', keywords: 'absences' },
        { id: 'staff-leave', label: 'Staff Leave', description: 'Staff leave requests', section: 'HR', icon: 'bi-person-workspace', url: '/admin/staff-leave', keywords: 'teacher absence' },
        { id: 'staff-loans', label: 'Staff Loans', description: 'Staff loan requests', section: 'HR', icon: 'bi-cash-stack', url: '/admin/staff-loans', keywords: 'advances' },

        // Reports
        { id: 'reports-fee', label: 'Fee Collection', description: 'Fee collection report', section: 'Reports', icon: 'bi-file-earmark-bar-graph', url: '/admin/reports/fee-collection', keywords: 'revenue' },
        { id: 'reports-dues', label: 'Outstanding Dues', description: 'Outstanding dues report', section: 'Reports', icon: 'bi-file-earmark-bar-graph', url: '/admin/reports/outstanding-dues', keywords: 'arrears' },
        { id: 'reports-ledger', label: 'Student Ledger', description: 'Student ledger report', section: 'Reports', icon: 'bi-file-earmark-bar-graph', url: '/admin/reports/student-ledger', keywords: 'ledger statement' },

        // Optional Modules
        { id: 'library', label: 'Library', description: 'Manage library', section: 'Optional', icon: 'bi-book-half', url: '/admin/library/books', keywords: 'books borrow return', condition: 'library' },
        { id: 'transport', label: 'Transport', description: 'Manage transport', section: 'Optional', icon: 'bi-bus-front', url: '/admin/transport/routes', keywords: 'bus routes vehicles', condition: 'transport' },
        { id: 'payroll', label: 'Payroll', description: 'Manage payroll', section: 'Optional', icon: 'bi-cash-coin', url: '/admin/payroll/runs', keywords: 'salary payroll', condition: 'payroll' },
        { id: 'lms', label: 'LMS', description: 'Learning management', section: 'Optional', icon: 'bi-easel', url: '/admin/lms/courses', keywords: 'courses lessons', condition: 'lms' },

        // Actions
        { id: 'new-student', label: 'New Student', description: 'Create new student', section: 'Actions', icon: 'bi-person-plus', url: '/admin/students/create', keywords: 'add pupil register' },
        { id: 'new-staff', label: 'New Staff', description: 'Add new staff member', section: 'Actions', icon: 'bi-person-badge-plus', url: '/admin/staff/create', keywords: 'hire teacher employee' },
        { id: 'new-admission', label: 'New Admission', description: 'Process new admission', section: 'Actions', icon: 'bi-clipboard-check', url: '/admin/admissions/index', keywords: 'enroll register' },
    ];

    // Fuzzy search function
    function fuzzyMatch(query, item) {
        const haystack = [
            item.label,
            item.description,
            item.section,
            item.keywords
        ].join(' ').toLowerCase();

        const needle = query.toLowerCase();
        if (!needle) return 0;

        let score = 0;
        let haystackIndex = 0;

        for (let i = 0; i < needle.length; i++) {
            const char = needle[i];
            const index = haystack.indexOf(char, haystackIndex);
            if (index === -1) return -1;
            score += (index - haystackIndex) * 0.1;
            haystackIndex = index + 1;
        }

        // Boost for exact prefix matches
        if (item.label.toLowerCase().startsWith(needle)) score -= 10;
        if (item.section.toLowerCase().startsWith(needle)) score -= 5;

        // Boost for exact keyword matches
        if (item.keywords && item.keywords.toLowerCase().includes(needle)) score -= 3;

        return score;
    }

    function renderResults(query, container) {
        const filtered = commandData
            .filter(item => {
                if (item.condition && !window.enabledModules?.includes(item.condition)) {
                    return false;
                }
                return fuzzyMatch(query, item) !== -1;
            })
            .sort((a, b) => fuzzyMatch(query, a) - fuzzyMatch(query, b));

        // Group by section
        const sections = {};
        filtered.forEach(item => {
            if (!sections[item.section]) sections[item.section] = [];
            sections[item.section].push(item);
        });

        if (Object.keys(sections).length === 0) {
            container.querySelector('.command-results').innerHTML = '';
            container.querySelector('.command-empty').classList.remove('hidden');
            return;
        }

        container.querySelector('.command-empty').classList.add('hidden');

        let html = '';
        for (const [section, items] of Object.entries(sections)) {
            html += `
                <div class="command-section">
                    <div class="command-section-header px-4 py-2 text-xs font-semibold text-slate-400 uppercase tracking-wider bg-slate-50 border-b border-slate-100">
                        ${section}
                    </div>
                    ${items.map(item => `
                        <a href="${item.url}" class="command-item flex items-center gap-3 px-4 py-2.5 hover:bg-slate-50 transition-colors" data-id="${item.id}">
                            <i class="bi ${item.icon} text-slate-400 w-5 text-center" aria-hidden="true"></i>
                            <div class="flex-1 min-w-0">
                                <div class="font-medium text-slate-900 truncate">${item.label}</div>
                                <div class="text-xs text-slate-500 truncate">${item.description}</div>
                            </div>
                            <i class="bi bi-chevron-right text-slate-300" aria-hidden="true"></i>
                        </a>
                    `).join('')}
                </div>
            `;
        }

        container.querySelector('.command-results').innerHTML = html;
    }

    // Initialize
    document.addEventListener('DOMContentLoaded', function() {
        const palette = document.getElementById('{{ $paletteId }}');
        const input = document.getElementById('command-palette-input');
        const resultsContainer = palette?.querySelector('.command-results');
        const emptyState = palette?.querySelector('.command-empty');

        if (!palette || !input) return;

        let selectedIndex = -1;
        let isOpen = false;

        function open() {
            palette.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
            input.value = '';
            input.focus();
            isOpen = true;
            selectedIndex = -1;
            renderResults('', palette);
            document.addEventListener('keydown', handleKeydown);
        }

        function close() {
            palette.classList.add('hidden');
            document.body.style.overflow = '';
            isOpen = false;
            document.removeEventListener('keydown', handleKeydown);
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
            }
        }

        function updateSelection(items) {
            items.forEach((item, index) => {
                item.classList.toggle('bg-slate-50', index === selectedIndex);
                item.classList.toggle('ring-2', index === selectedIndex);
                item.classList.toggle('ring-primary-500', index === selectedIndex);
                if (index === selectedIndex) {
                    item.scrollIntoView({ block: 'nearest' });
                }
            });
        }

        // Open handlers
        document.addEventListener('keydown', function(e) {
            const isMac = navigator.platform.toUpperCase().indexOf('MAC') >= 0;
            const modifier = e.metaKey || (e.ctrlKey && !isMac);
            const key = e.key.toLowerCase();

            if (modifier && key === 'k') {
                e.preventDefault();
                if (!isOpen) open();
            }
        });

        // Input handling
        input.addEventListener('input', function() {
            renderResults(this.value, palette);
            selectedIndex = -1;
        });

        input.addEventListener('blur', function(e) {
            // Delay close to allow clicks
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
    });
})();
</script>
@endpush