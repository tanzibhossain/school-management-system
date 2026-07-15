{{-- Settings Tab --}}
<div class="row g-4">
    {{-- Personal Settings --}}
    <div class="col-xl-6">
        <x-card title="Personal Information" subtitle="Student details">
            <form action="{{ route('admin.students.update', $student) }}" method="POST" class="form-layout">
                @csrf @method('PUT')
                <div class="row g-3">
                    <div class="col-md-6">
                        <x-input label="First Name" name="first_name" value="{{ $student->first_name }}" required />
                    </div>
                    <div class="col-md-6">
                        <x-input label="Last Name" name="last_name" value="{{ $student->last_name }}" />
                    </div>
                    <div class="col-md-6">
                        <x-input label="Admission Number" name="admission_number" value="{{ $student->admission_number }}" required />
                    </div>
                    <div class="col-md-6">
                        <x-input type="date" label="Date of Birth" name="dob" :value="$student->dob?->format('Y-m-d')" />
                    </div>
                    <div class="col-md-6">
                        <x-select label="Gender" name="gender" :value="$student->gender" :options="['male' => 'Male', 'female' => 'Female', 'other' => 'Other']" required />
                    </div>
                    <div class="col-md-6">
                        <x-input label="Religion" name="religion" value="{{ $student->religion }}" />
                    </div>
                    <div class="col-md-6">
                        <x-select label="Blood Group" name="blood_group" :value="$student->blood_group" :options="['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-']" placeholder="Select blood group" />
                    </div>
                    <div class="col-md-6">
                        <x-input label="Category" name="category" value="{{ $student->category }}" placeholder="General/OBC/SC/ST" />
                    </div>
                    <div class="col-md-6">
                        <x-input label="Nationality" name="nationality" value="{{ $student->nationality }}" />
                    </div>
                    <div class="col-md-6">
                        <x-input label="Mother Tongue" name="mother_tongue" value="{{ $student->mother_tongue }}" />
                    </div>
                </div>
            </form>
        </x-card>
    </div>

    {{-- Contact Settings --}}
    <div class="col-xl-6">
        <x-card title="Contact Information" subtitle="Phone, email, and address">
            <form action="{{ route('admin.students.update', $student) }}" method="POST" class="form-layout">
                @csrf @method('PUT')
                <div class="row g-3">
                    <div class="col-md-6">
                        <x-input label="Phone" name="phone" value="{{ $student->phone }}" type="tel" />
                    </div>
                    <div class="col-md-6">
                        <x-input label="Email" name="email" type="email" value="{{ $student->email }}" />
                    </div>
                    <div class="col-12">
                        <x-input label="Present Address" name="present_address" type="textarea" :value="$student->present_address" rows="3" />
                    </div>
                    <div class="col-12">
                        <x-input label="Permanent Address" name="permanent_address" type="textarea" :value="$student->permanent_address" rows="3" />
                    </div>
                    <div class="col-md-6">
                        <x-input label="City" name="city" value="{{ $student->city }}" />
                    </div>
                    <div class="col-md-6">
                        <x-input label="State" name="state" value="{{ $student->state }}" />
                    </div>
                    <div class="col-md-6">
                        <x-input label="Postal Code" name="postal_code" value="{{ $student->postal_code }}" />
                    </div>
                    <div class="col-md-6">
                        <x-input label="Country" name="country" value="{{ $student->country }}" />
                    </div>
                </div>
            </form>
        </x-card>
    </div>
</div>

<div class="row g-4 mt-4">
    {{-- Guardian Settings --}}
    <div class="col-xl-6">
        <x-card title="Guardian Information" subtitle="Primary guardian contact">
            @if($guardian = $student->guardians->first())
                <form action="{{ route('admin.students.guardian.update', $student) }}" method="POST" class="form-layout">
                    @csrf @method('PUT')
                    <div class="row g-3">
                        <div class="col-md-6">
                            <x-input label="Guardian Name" name="guardian_name" value="{{ $guardian->name }}" required />
                        </div>
                        <div class="col-md-6">
                            <x-select label="Relation" name="guardian_relation" :value="$guardian->relation" :options="['father' => 'Father', 'mother' => 'Mother', 'guardian' => 'Other Guardian']" required />
                        </div>
                        <div class="col-md-6">
                            <x-input label="Guardian Phone" name="guardian_phone" value="{{ $guardian->phone }}" type="tel" required />
                        </div>
                        <div class="col-md-6">
                            <x-input label="Guardian Email" name="guardian_email" type="email" value="{{ $guardian->email }}" />
                        </div>
                        <div class="col-md-6">
                            <x-input label="Occupation" name="guardian_occupation" value="{{ $guardian->occupation }}" />
                        </div>
                        <div class="col-md-6">
                            <x-input label="Office Phone" name="guardian_office_phone" value="{{ $guardian->office_phone }}" />
                        </div>
                        <div class="col-12">
                            <x-input label="Guardian Address" name="guardian_address" type="textarea" :value="$guardian->address" rows="2" />
                        </div>
                    </div>
                    <div class="mt-3">
                        <x-button variant="primary">Save Guardian Info</x-button>
                    </div>
                </form>
            @else
                <div class="text-center py-4">
                    <i class="bi bi-person-plus fs-1 text-slate-300"></i>
                    <p class="mt-2 mb-3 text-muted">No guardian information on file</p>
                    <a href="{{ route('admin.students.guardian.create', $student) }}" class="btn btn-primary">
                        <i class="bi bi-person-plus me-1"></i> Add Guardian
                    </a>
                </div>
            @endif
        </x-card>
    </div>

    {{-- Account Settings --}}
    <div class="col-xl-6">
        <x-card title="Account & Security" subtitle="User account management">
            <dl class="row mb-4">
                <dt class="col-sm-4 text-muted small">Username</dt>
                <dd class="col-sm-8 fw-medium">{{ $student->user->username ?? 'Not linked' }}</dd>

                <dt class="col-sm-4 text-muted small">Email</dt>
                <dd class="col-sm-8">{{ $student->user->email ?? 'Not linked' }}</dd>

                <dt class="col-sm-4 text-muted small">Roles</dt>
                <dd class="col-sm-8">
                    @foreach($student->user->getRoleNames() as $role)
                        <span class="badge bg-primary-light text-primary me-1">{{ $role }}</span>
                    @endforeach
                </dd>

                <dt class="col-sm-4 text-muted small">Last Login</dt>
                <dd class="col-sm-8">{{ $student->user->last_login_at?->format('M j, Y H:i') ?? 'Never' }}</dd>
            </dl>

            <div class="d-flex flex-wrap gap-2">
                @can('users.edit')
                    <a href="{{ route('admin.users.edit', $student->user) }}" class="btn btn-outline-primary">
                        <i class="bi bi-person-gear me-1"></i> Manage Account
                    </a>
                @endcan
                @can('users.reset-password')
                    <button type="button" class="btn btn-outline-warning" onclick="resetPassword({{ $student->user->id }})">
                        <i class="bi bi-key me-1"></i> Reset Password
                    </button>
                @endcan
            </div>
        </x-card>
    </div>

    {{-- Notification Preferences --}}
    <div class="col-xl-6">
        <x-card title="Notification Preferences" subtitle="Choose how you want to be notified">
            <form action="{{ route('admin.students.notifications.update', $student) }}" method="POST" class="form-layout">
                @csrf @method('PUT')
                <div class="row g-3">
                    <div class="col-12">
                        <fieldset>
                            <legend class="form-label fw-medium mb-2">SMS Notifications</legend>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="notify_sms_fee" id="notify_sms_fee" {{ $student->notify_sms_fee ? 'checked' : '' }}>
                                        <label class="form-check-label" for="notify_sms_fee">Fee reminders</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="notify_sms_attendance" id="notify_sms_attendance" {{ $student->notify_sms_attendance ? 'checked' : '' }}>
                                        <label class="form-check-label" for="notify_sms_attendance">Absence alerts</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="notify_sms_exam" id="notify_sms_exam" {{ $student->notify_sms_exam ? 'checked' : '' }}>
                                        <label class="form-check-label" for="notify_sms_exam">Exam notifications</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="notify_sms_result" id="notify_sms_result" {{ $student->notify_sms_result ? 'checked' : '' }}>
                                        <label class="form-check-label" for="notify_sms_result">Result announcements</label>
                                    </div>
                                </div>
                            </div>
                        </fieldset>
                    </div>

                    <div class="col-12">
                        <fieldset>
                            <legend class="form-label fw-medium mb-2">Email Notifications</legend>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="notify_email_fee" id="notify_email_fee" {{ $student->notify_email_fee ? 'checked' : '' }}>
                                        <label class="form-check-label" for="notify_email_fee">Fee reminders</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="notify_email_attendance" id="notify_email_attendance" {{ $student->notify_email_attendance ? 'checked' : '' }}>
                                        <label class="form-check-label" for="notify_email_attendance">Absence alerts</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="notify_email_exam" id="notify_email_exam" {{ $student->notify_email_exam ? 'checked' : '' }}>
                                        <label class="form-check-label" for="notify_email_exam">Exam notifications</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="notify_email_result" id="notify_email_result" {{ $student->notify_email_result ? 'checked' : '' }}>
                                        <label class="form-check-label" for="notify_email_result">Result announcements</label>
                                    </div>
                                </div>
                            </div>
                        </fieldset>
                    </div>

                    <div class="col-12">
                        <fieldset>
                            <legend class="form-label fw-medium mb-2">Push Notifications (App)</legend>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="notify_push_fee" id="notify_push_fee" {{ $student->notify_push_fee ? 'checked' : '' }}>
                                        <label class="form-check-label" for="notify_push_fee">Fee reminders</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="notify_push_attendance" id="notify_push_attendance" {{ $student->notify_push_attendance ? 'checked' : '' }}>
                                        <label class="form-check-label" for="notify_push_attendance">Absence alerts</label>
                                    </div>
                                </div>
                            </div>
                        </fieldset>
                    </div>

                    <div class="col-12 mt-4">
                        <x-button variant="primary" type="submit">Save Preferences</x-button>
                    </div>
                </div>
            </form>
        </x-card>
    </div>
</div>