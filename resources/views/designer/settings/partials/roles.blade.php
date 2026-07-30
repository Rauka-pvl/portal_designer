@php
    $isCorporate = $isCorporate ?? false;

    $matrix = [
        ['key' => 'perm_view_projects', 'owner' => 'yes', 'admin' => 'yes', 'designer' => 'yes'],
        ['key' => 'perm_edit_projects', 'owner' => 'yes', 'admin' => 'yes', 'designer' => 'yes'],
        ['key' => 'perm_view_tasks', 'owner' => 'yes', 'admin' => 'yes', 'designer' => 'no'],
        ['key' => 'perm_create_tasks', 'owner' => 'yes', 'admin' => 'yes', 'designer' => 'yes'],
        ['key' => 'perm_manage_members', 'owner' => 'yes', 'admin' => 'yes', 'designer' => 'no'],
        ['key' => 'perm_assign_roles', 'owner' => 'yes', 'admin' => 'limited', 'designer' => 'no'],
        ['key' => 'perm_manage_subscription', 'owner' => 'yes', 'admin' => 'no', 'designer' => 'no'],
        ['key' => 'perm_checklists', 'owner' => 'yes', 'admin' => 'yes', 'designer' => 'yes'],
        ['key' => 'perm_financial', 'owner' => 'yes', 'admin' => 'yes', 'designer' => 'no'],
    ];

    $cellLabel = fn (string $val) => match ($val) {
        'yes' => __('team.perm_yes'),
        'no' => __('team.perm_no'),
        'limited' => __('team.perm_limited'),
        default => '—',
    };

    $cellClass = fn (string $val) => match ($val) {
        'yes' => 'text-[#f59e0b] font-medium',
        'limited' => 'text-amber-600 dark:text-amber-400',
        default => 'text-[#64748b] dark:text-[#A1A09A]',
    };
@endphp

@push('styles')
    <style>
        .roles-locked {
            text-align: center;
            padding: 2.5rem 1.5rem;
            max-width: 420px;
            margin: 0 auto;
        }
        .roles-locked-icon {
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
        .dark .roles-locked-icon { border-color: #3E3E3A; }
        .roles-locked-title {
            font-size: 1.125rem;
            font-weight: 600;
            color: #0f172a;
            margin-bottom: 0.5rem;
        }
        .dark .roles-locked-title { color: #EDEDEC; }
        .roles-locked-body {
            font-size: 13px;
            color: #64748b;
            margin-bottom: 1.25rem;
            line-height: 1.5;
        }
        .dark .roles-locked-body { color: #A1A09A; }
        .roles-cta {
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
        .roles-matrix-wrap { overflow-x: auto; }
        .roles-matrix {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }
        .roles-matrix th,
        .roles-matrix td {
            padding: 0.65rem 0.75rem;
            border-bottom: 1px solid rgba(124,135,153,0.35);
            text-align: center;
        }
        .dark .roles-matrix th,
        .dark .roles-matrix td { border-bottom-color: #3E3E3A; }
        .roles-matrix th:first-child,
        .roles-matrix td:first-child {
            text-align: left;
            color: #0f172a;
            font-weight: 500;
            min-width: 220px;
        }
        .dark .roles-matrix th:first-child,
        .dark .roles-matrix td:first-child { color: #EDEDEC; }
        .roles-matrix thead th {
            font-weight: 600;
            color: #64748b;
            border-bottom: 1px solid #7c8799;
        }
        .dark .roles-matrix thead th { color: #A1A09A; border-bottom-color: #3E3E3A; }
        .roles-matrix-hint {
            font-size: 13px;
            color: #64748b;
            margin-bottom: 1rem;
            line-height: 1.5;
        }
        .dark .roles-matrix-hint { color: #A1A09A; }
    </style>
@endpush

@if (! $isCorporate)
    <div class="roles-locked">
        <div class="roles-locked-icon" aria-hidden="true">
            <svg width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
        </div>
        <h3 class="roles-locked-title">{{ __('team.roles_locked_title') }}</h3>
        <p class="roles-locked-body">{{ __('team.roles_locked_body') }}</p>
        <a href="{{ route('subscription.index') }}" class="roles-cta">{{ __('team.roles_locked_cta') }}</a>
    </div>
@else
    <h2 class="settings-section-title text-xl">{{ __('team.matrix_title') }}</h2>
    <p class="roles-matrix-hint">{{ __('team.matrix_hint') }}</p>

    <div class="roles-matrix-wrap">
        <table class="roles-matrix">
            <thead>
                <tr>
                    <th scope="col">{{ __('subscription.compare_feature') }}</th>
                    <th scope="col">{{ __('team.matrix_role_owner') }}</th>
                    <th scope="col">{{ __('team.matrix_role_admin') }}</th>
                    <th scope="col">{{ __('team.matrix_role_designer') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($matrix as $row)
                    <tr>
                        <td>{{ __('team.'.$row['key']) }}</td>
                        <td class="{{ $cellClass($row['owner']) }}">{{ $cellLabel($row['owner']) }}</td>
                        <td class="{{ $cellClass($row['admin']) }}">{{ $cellLabel($row['admin']) }}</td>
                        <td class="{{ $cellClass($row['designer']) }}">{{ $cellLabel($row['designer']) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
