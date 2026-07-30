@php
    use App\Enums\TeamRole;

    $isCorporate = $isCorporate ?? false;
    $team = $team ?? null;
    $members = $members ?? collect();
    $invitations = $invitations ?? collect();
    $canManageMembers = $canManageMembers ?? false;
    $seatUsed = $seatUsed ?? 0;
    $seatMax = $seatMax ?? 5;
    $seatsAvailable = max(0, $seatMax - $seatUsed);

    $assignableRoles = [
        TeamRole::Admin->value => TeamRole::Admin->label(),
        TeamRole::Designer->value => TeamRole::Designer->label(),
    ];
@endphp

@push('styles')
    <style>
        .team-locked {
            text-align: center;
            padding: 2.5rem 1.5rem;
            max-width: 420px;
            margin: 0 auto;
        }
        .team-locked-icon {
            width: 48px;
            height: 48px;
            margin: 0 auto 1rem;
            border-radius: 12px;
            border: 1px solid #7c8799;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #f59e0b;
        }
        .dark .team-locked-icon { border-color: #3E3E3A; }
        .team-locked-title {
            font-size: 1.125rem;
            font-weight: 600;
            color: #0f172a;
            margin-bottom: 0.5rem;
        }
        .dark .team-locked-title { color: #EDEDEC; }
        .team-locked-body {
            font-size: 13px;
            color: #64748b;
            margin-bottom: 1.25rem;
            line-height: 1.5;
        }
        .dark .team-locked-body { color: #A1A09A; }
        .team-cta {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 8px 16px;
            border-radius: 8px;
            border: 1px solid #f59e0b;
            background: #f59e0b;
            color: #111827;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
        }
        .team-cta:hover { opacity: 0.92; }
        .team-header {
            display: flex;
            flex-wrap: wrap;
            align-items: flex-start;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: 1.25rem;
        }
        .team-meta {
            font-size: 13px;
            color: #64748b;
            margin-top: 0.35rem;
        }
        .dark .team-meta { color: #A1A09A; }
        .team-seats-bar {
            height: 4px;
            border-radius: 999px;
            background: rgba(124,135,153,0.25);
            overflow: hidden;
            max-width: 200px;
            margin-top: 0.5rem;
        }
        .team-seats-bar > span {
            display: block;
            height: 100%;
            background: #f59e0b;
            border-radius: inherit;
        }
        .team-add-btn {
            padding: 8px 14px;
            border-radius: 8px;
            border: 1px solid #f59e0b;
            background: #f59e0b;
            color: #111827;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
        }
        .team-add-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        .team-section-title {
            font-size: 15px;
            font-weight: 600;
            color: #0f172a;
            margin: 1.25rem 0 0.65rem;
        }
        .dark .team-section-title { color: #EDEDEC; }
        .team-table-wrap { overflow-x: auto; }
        .team-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }
        .team-table th {
            text-align: left;
            font-weight: 500;
            color: #64748b;
            padding: 0.5rem 0.65rem;
            border-bottom: 1px solid #7c8799;
            white-space: nowrap;
        }
        .dark .team-table th { color: #A1A09A; border-bottom-color: #3E3E3A; }
        .team-table td {
            padding: 0.65rem;
            border-bottom: 1px solid rgba(124,135,153,0.35);
            color: #0f172a;
            vertical-align: middle;
        }
        .dark .team-table td { color: #EDEDEC; border-bottom-color: #3E3E3A; }
        .team-table tr:last-child td { border-bottom: 0; }
        .team-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.15rem 0.5rem;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 600;
            border: 1px solid rgba(124,135,153,0.45);
            color: #64748b;
        }
        .team-badge.is-active {
            border-color: rgba(245,158,11,0.45);
            color: #f59e0b;
            background: rgba(245,158,11,0.08);
        }
        .team-badge.is-pending {
            border-color: rgba(124,135,153,0.45);
            background: rgba(124,135,153,0.08);
        }
        .team-actions-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            border: 1px solid #7c8799;
            border-radius: 6px;
            background: #fff;
            color: #64748b;
            cursor: pointer;
        }
        .dark .team-actions-btn {
            border-color: #3E3E3A;
            background: #161615;
            color: #A1A09A;
        }
        .team-actions-btn:hover { border-color: #f59e0b; color: #f59e0b; }
        .team-empty {
            font-size: 13px;
            color: #64748b;
            padding: 0.75rem 0;
        }
        .dark .team-empty { color: #A1A09A; }
        .team-modal {
            display: none;
            position: fixed;
            inset: 0;
            z-index: 90;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            background: rgba(0,0,0,0.55);
        }
        .team-modal.is-open { display: flex; }
        .team-modal-panel {
            width: 100%;
            max-width: 440px;
            border-radius: 12px;
            border: 1px solid #7c8799;
            background: #fff;
            padding: 1.25rem;
            max-height: 90vh;
            overflow-y: auto;
        }
        .dark .team-modal-panel {
            border-color: #3E3E3A;
            background: #161615;
        }
        .team-modal-tabs {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin-bottom: 1rem;
        }
        .team-modal-tab {
            padding: 6px 10px;
            border-radius: 6px;
            border: 1px solid #7c8799;
            background: #fff;
            color: #64748b;
            font-size: 12px;
            cursor: pointer;
        }
        .dark .team-modal-tab {
            border-color: #3E3E3A;
            background: #0a0a0a;
            color: #A1A09A;
        }
        .team-modal-tab.active {
            border-color: #f59e0b;
            color: #f59e0b;
        }
        .team-modal-pane { display: none; }
        .team-modal-pane.active { display: block; }
        .team-form-grid {
            display: grid;
            gap: 0.65rem;
        }
        .team-form-label {
            font-size: 12px;
            color: #64748b;
            margin-bottom: 0.2rem;
        }
        .dark .team-form-label { color: #A1A09A; }
        .team-form-hint {
            font-size: 11px;
            color: #64748b;
            margin-top: 0.25rem;
        }
        .dark .team-form-hint { color: #A1A09A; }
        .team-modal-actions {
            display: flex;
            gap: 0.5rem;
            margin-top: 1rem;
        }
        .team-modal-submit {
            flex: 1;
            padding: 8px 14px;
            border-radius: 8px;
            border: 1px solid #f59e0b;
            background: #f59e0b;
            color: #111827;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
        }
        .team-modal-cancel {
            padding: 8px 14px;
            border-radius: 8px;
            border: 1px solid #7c8799;
            background: #fff;
            color: #64748b;
            font-size: 13px;
            cursor: pointer;
        }
        .dark .team-modal-cancel {
            border-color: #3E3E3A;
            background: #161615;
            color: #EDEDEC;
        }
        .team-expired-banner {
            border-radius: 8px;
            border: 1px solid rgba(245,158,11,0.35);
            background: rgba(245,158,11,0.08);
            padding: 0.85rem 1rem;
            margin-bottom: 1rem;
            font-size: 13px;
            color: #0f172a;
        }
        .dark .team-expired-banner { color: #EDEDEC; }
    </style>
@endpush

@if (! $isCorporate && ! $team)
    <div class="team-locked">
        <div class="team-locked-icon" aria-hidden="true">
            <svg width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
        </div>
        <h3 class="team-locked-title">{{ __('team.locked_title') }}</h3>
        <p class="team-locked-body">{{ __('team.locked_body') }}</p>
        <a href="{{ route('subscription.index') }}" class="team-cta">{{ __('team.go_to_corporate') }}</a>
    </div>
@elseif (! $isCorporate && $team)
    <div class="team-expired-banner">
        <strong>{{ __('subscription.corporate_expired_title') }}</strong>
        <p class="mt-1 mb-0">{{ __('subscription.corporate_expired_body') }}</p>
        @if (($canManageMembers ?? false) || ($team->owner_id ?? null) === auth()->id())
            <a href="{{ route('subscription.index') }}" class="team-cta mt-3 inline-flex">{{ __('subscription.corporate_renew') }}</a>
        @else
            <p class="mt-2 mb-0 team-meta">{{ __('subscription.corporate_contact_owner') }}</p>
        @endif
    </div>
@else
    <div class="team-header">
        <div>
            <h2 class="settings-section-title text-xl mb-0">{{ $team?->name ?? __('team.team_name') }}</h2>
            <p class="team-meta">
                {{ __('team.member_count', ['count' => $members->count()]) }}
                · {{ __('team.seats_used', ['used' => $seatUsed, 'max' => $seatMax]) }}
            </p>
            @php $seatPct = $seatMax > 0 ? min(100, round(($seatUsed / $seatMax) * 100)) : 0; @endphp
            <div class="team-seats-bar" role="progressbar" aria-valuenow="{{ $seatUsed }}" aria-valuemin="0" aria-valuemax="{{ $seatMax }}">
                <span style="width: {{ $seatPct }}%"></span>
            </div>
        </div>
        @if ($canManageMembers)
            <button type="button"
                class="team-add-btn"
                id="team-open-add-modal"
                @disabled($seatsAvailable <= 0)
                title="{{ $seatsAvailable <= 0 ? __('team.no_seats_left') : '' }}">
                {{ __('team.add_member') }}
            </button>
        @endif
    </div>

    <h3 class="team-section-title">{{ __('team.members_section') }}</h3>
    <div class="team-table-wrap">
        <table class="team-table">
            <thead>
                <tr>
                    <th>{{ __('team.col_name') }}</th>
                    <th>{{ __('team.col_email') }}</th>
                    <th>{{ __('team.col_role') }}</th>
                    <th>{{ __('team.col_status') }}</th>
                    <th>{{ __('team.col_joined_at') }}</th>
                    @if ($canManageMembers)
                        <th>{{ __('team.col_actions') }}</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @forelse ($members as $member)
                    @php
                        $memberUser = $member->user;
                        $memberRole = $member->role instanceof TeamRole ? $member->role : TeamRole::tryFrom((string) $member->role);
                        $isOwner = $memberRole === TeamRole::Owner;
                    @endphp
                    <tr>
                        <td>{{ $memberUser?->name ?? '—' }}</td>
                        <td>{{ $memberUser?->email ?? '—' }}</td>
                        <td>{{ $memberRole?->label() ?? '—' }}</td>
                        <td>
                            <span class="team-badge {{ $member->isActive() ? 'is-active' : '' }}">
                                {{ $member->status?->label() ?? __('team.status_inactive') }}
                            </span>
                        </td>
                        <td>{{ $member->joined_at?->format('d.m.Y') ?? '—' }}</td>
                        @if ($canManageMembers)
                            <td>
                                @if (! $isOwner)
                                    <button type="button"
                                        class="team-actions-btn"
                                        aria-label="{{ __('team.actions') }}"
                                        data-team-member-actions
                                        data-member-id="{{ $member->id }}"
                                        data-member-name="{{ $memberUser?->name ?? '' }}"
                                        data-member-role="{{ $memberRole?->value ?? '' }}">
                                        <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><circle cx="8" cy="3" r="1.5"/><circle cx="8" cy="8" r="1.5"/><circle cx="8" cy="13" r="1.5"/></svg>
                                    </button>
                                @endif
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $canManageMembers ? 6 : 5 }}" class="team-empty">{{ __('team.invitations_empty') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <h3 class="team-section-title">{{ __('team.invitations_section') }}</h3>
    @if ($invitations->isEmpty())
        <p class="team-empty">{{ __('team.invitations_empty') }}</p>
    @else
        <div class="team-table-wrap">
            <table class="team-table">
                <thead>
                    <tr>
                        <th>{{ __('team.col_email') }}</th>
                        <th>{{ __('team.col_role') }}</th>
                        <th>{{ __('team.col_expires_at') }}</th>
                        @if ($canManageMembers)
                            <th>{{ __('team.col_actions') }}</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @foreach ($invitations as $invitation)
                        @php
                            $invRole = $invitation->role instanceof TeamRole ? $invitation->role : TeamRole::tryFrom((string) $invitation->role);
                        @endphp
                        <tr>
                            <td>{{ $invitation->email }}</td>
                            <td>{{ $invRole?->label() ?? '—' }}</td>
                            <td>
                                <span class="team-badge is-pending">{{ __('team.status_pending') }}</span>
                                @if ($invitation->expires_at)
                                    <span class="team-meta ml-1">{{ $invitation->expires_at->format('d.m.Y') }}</span>
                                @endif
                            </td>
                            @if ($canManageMembers)
                                <td>
                                    @if (Route::has('settings.team.cancel-invitation'))
                                        <form method="POST" action="{{ route('settings.team.cancel-invitation', $invitation) }}" class="inline" onsubmit="return confirm(@json(__('team.confirm_cancel_invite_body', ['email' => $invitation->email])))">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="team-modal-cancel text-xs">{{ __('team.cancel_invitation') }}</button>
                                        </form>
                                    @endif
                                </td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    {{--
        Expected routes (SettingsController / TeamController):
        POST   settings.team.add-member      { email, role }
        POST   settings.team.invite          { email, role }
        POST   settings.team.create          { name, email, password, password_confirmation, role }
        PATCH  settings.team.change-role     { member, role }
        DELETE settings.team.remove          { member }
        DELETE settings.team.cancel-invitation { invitation }
    --}}
    @if ($canManageMembers)
        <div id="team-add-modal" class="team-modal" role="dialog" aria-modal="true" aria-labelledby="team-modal-title">
            <div class="team-modal-panel">
                <h3 id="team-modal-title" class="settings-section-title text-lg mb-3">{{ __('team.modal_title') }}</h3>

                <div class="team-modal-tabs" role="tablist">
                    <button type="button" class="team-modal-tab active" data-team-tab="find" role="tab">{{ __('team.tab_find') }}</button>
                    <button type="button" class="team-modal-tab" data-team-tab="invite" role="tab">{{ __('team.tab_invite') }}</button>
                    <button type="button" class="team-modal-tab" data-team-tab="create" role="tab">{{ __('team.tab_create') }}</button>
                </div>

                <div class="team-modal-pane active" data-team-pane="find" role="tabpanel">
                    <form method="POST" action="{{ Route::has('settings.team.add-member') ? route('settings.team.add-member') : '#' }}" class="team-form-grid">
                        @csrf
                        <div>
                            <label class="team-form-label">{{ __('team.find_user_label') }}</label>
                            <input type="email" name="email" required class="settings-input" placeholder="{{ __('team.find_user_placeholder') }}">
                            <p class="team-form-hint">{{ __('team.find_user_hint') }}</p>
                        </div>
                        <div>
                            <label class="team-form-label">{{ __('team.select_role') }}</label>
                            <select name="role" required class="settings-select">
                                @foreach ($assignableRoles as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="team-modal-actions">
                            <button type="button" class="team-modal-cancel" data-team-modal-close>{{ __('team.modal_cancel') }}</button>
                            <button type="submit" class="team-modal-submit">{{ __('team.submit_add') }}</button>
                        </div>
                    </form>
                </div>

                <div class="team-modal-pane" data-team-pane="invite" role="tabpanel">
                    <form method="POST" action="{{ Route::has('settings.team.invite') ? route('settings.team.invite') : '#' }}" class="team-form-grid">
                        @csrf
                        <div>
                            <label class="team-form-label">{{ __('team.invite_email_label') }}</label>
                            <input type="email" name="email" required class="settings-input" placeholder="{{ __('team.find_user_placeholder') }}">
                            <p class="team-form-hint">{{ __('team.invite_email_hint') }}</p>
                        </div>
                        <div>
                            <label class="team-form-label">{{ __('team.select_role') }}</label>
                            <select name="role" required class="settings-select">
                                @foreach ($assignableRoles as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="team-modal-actions">
                            <button type="button" class="team-modal-cancel" data-team-modal-close>{{ __('team.modal_cancel') }}</button>
                            <button type="submit" class="team-modal-submit">{{ __('team.submit_invite') }}</button>
                        </div>
                    </form>
                </div>

                <div class="team-modal-pane" data-team-pane="create" role="tabpanel">
                    <form method="POST" action="{{ Route::has('settings.team.create') ? route('settings.team.create') : '#' }}" class="team-form-grid">
                        @csrf
                        <div>
                            <label class="team-form-label">{{ __('team.create_name') }}</label>
                            <input type="text" name="name" required class="settings-input">
                        </div>
                        <div>
                            <label class="team-form-label">{{ __('team.create_email') }}</label>
                            <input type="email" name="email" required class="settings-input">
                        </div>
                        <div>
                            <label class="team-form-label">{{ __('team.create_password') }}</label>
                            <input type="password" name="password" required class="settings-input" autocomplete="new-password">
                        </div>
                        <div>
                            <label class="team-form-label">{{ __('team.create_password_confirmation') }}</label>
                            <input type="password" name="password_confirmation" required class="settings-input" autocomplete="new-password">
                        </div>
                        <div>
                            <label class="team-form-label">{{ __('team.select_role') }}</label>
                            <select name="role" required class="settings-select">
                                @foreach ($assignableRoles as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="team-modal-actions">
                            <button type="button" class="team-modal-cancel" data-team-modal-close>{{ __('team.modal_cancel') }}</button>
                            <button type="submit" class="team-modal-submit">{{ __('team.submit_create') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Hidden forms for member actions --}}
        @if (Route::has('settings.team.change-role'))
            <form id="team-change-role-form" method="POST" action="" class="hidden">
                @csrf
                @method('PATCH')
                <input type="hidden" name="role" id="team-change-role-input">
            </form>
        @endif
        @if (Route::has('settings.team.remove'))
            <form id="team-remove-form" method="POST" action="" class="hidden">
                @csrf
                @method('DELETE')
            </form>
        @endif
    @endif
@endif

@push('scripts')
    <script src="{{ asset('js/crm-action-menu.js') }}"></script>
    <script>
    (function () {
        const modal = document.getElementById('team-add-modal');
        const openBtn = document.getElementById('team-open-add-modal');
        if (openBtn && modal) {
            openBtn.addEventListener('click', () => modal.classList.add('is-open'));
            modal.querySelectorAll('[data-team-modal-close]').forEach((btn) => {
                btn.addEventListener('click', () => modal.classList.remove('is-open'));
            });
            modal.addEventListener('mousedown', (e) => {
                if (e.target === modal) modal.classList.remove('is-open');
            });
        }

        document.querySelectorAll('[data-team-tab]').forEach((tab) => {
            tab.addEventListener('click', () => {
                const key = tab.getAttribute('data-team-tab');
                document.querySelectorAll('[data-team-tab]').forEach((t) => t.classList.toggle('active', t === tab));
                document.querySelectorAll('[data-team-pane]').forEach((p) => {
                    p.classList.toggle('active', p.getAttribute('data-team-pane') === key);
                });
            });
        });

        const roleLabels = @json($assignableRoles);
        const i18n = {
            changeRole: @json(__('team.change_role')),
            removeMember: @json(__('team.remove_member')),
            confirmRemove: @json(__('team.confirm_remove_body', ['name' => ':name'])),
        };

        document.querySelectorAll('[data-team-member-actions]').forEach((btn) => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                if (!window.CrmActionMenu?.open) return;

                const memberId = btn.getAttribute('data-member-id');
                const memberName = btn.getAttribute('data-member-name') || '';
                const currentRole = btn.getAttribute('data-member-role') || '';
                const items = [];

                Object.entries(roleLabels).forEach(([value, label]) => {
                    if (value === currentRole) return;
                    items.push({
                        id: 'role-' + value,
                        label: i18n.changeRole + ': ' + label,
                        onSelect: () => {
                            const form = document.getElementById('team-change-role-form');
                            if (!form) return;
                            form.action = @json(Route::has('settings.team.change-role') ? route('settings.team.change-role', ['member' => '__MEMBER__']) : '#').replace('__MEMBER__', memberId);
                            document.getElementById('team-change-role-input').value = value;
                            form.submit();
                        },
                    });
                });

                items.push({
                    id: 'remove',
                    label: i18n.removeMember,
                    danger: true,
                    onSelect: () => {
                        if (!confirm(i18n.confirmRemove.replace(':name', memberName))) return;
                        const form = document.getElementById('team-remove-form');
                        if (!form) return;
                        form.action = @json(Route::has('settings.team.remove') ? route('settings.team.remove', ['member' => '__MEMBER__']) : '#').replace('__MEMBER__', memberId);
                        form.submit();
                    },
                });

                window.CrmActionMenu.open(btn, {
                    items,
                    ariaLabel: @json(__('team.actions')),
                });
            });
        });
    })();
    </script>
@endpush
