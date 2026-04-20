@extends('layouts.dashboard')

@section('title', __('notifications.title'))

@section('content')
    <div class="mb-6 flex items-start md:items-center justify-between gap-3 flex-col md:flex-row">
        <div>
            <h1 class="text-2xl font-medium text-[#0f172a] dark:text-[#EDEDEC]">{{ __('notifications.title') }}</h1>
            <p class="text-sm text-[#64748b] dark:text-[#A1A09A] mt-1">{{ __('notifications.subtitle') }}</p>
        </div>

        <form method="POST" action="{{ route('notifications.read_all') }}">
            @csrf
            <button type="submit" class="px-4 py-2 rounded-lg border border-[#7c8799] dark:border-[#3E3E3A] text-sm text-[#64748b] dark:text-[#A1A09A] hover:border-[#f59e0b] hover:text-[#f59e0b] transition-colors">
                {{ __('notifications.mark_all_read') }}
            </button>
        </form>
    </div>

    @if (session('status'))
        <div class="mb-4 px-4 py-3 rounded-lg bg-emerald-50 dark:bg-emerald-500/10 text-emerald-800 dark:text-emerald-200 text-sm border border-emerald-200 dark:border-emerald-500/30">
            {{ session('status') }}
        </div>
    @endif

    <div class="space-y-3">
        @forelse ($notifications as $n)
            <div class="rounded-lg border border-[#7c8799] dark:border-[#3E3E3A] bg-white dark:bg-[#161615] p-4">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <div class="font-medium text-[#0f172a] dark:text-[#EDEDEC]">{{ $n->title }}</div>
                        @if (!empty($n->comment))
                            <div class="text-sm text-[#64748b] dark:text-[#A1A09A] mt-1 whitespace-pre-line">{{ $n->comment }}</div>
                        @endif
                        <div class="text-xs text-[#94a3b8] dark:text-[#71717a] mt-2">{{ optional($n->created_at)->format('Y-m-d H:i') }}</div>
                    </div>

                    <div class="flex items-center gap-2 shrink-0">
                    @if ($n->is_read)
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300">
                                {{ __('notifications.read') }}
                            </span>
                        @else
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-200">
                                {{ __('notifications.unread') }}
                            </span>
                            <form method="POST" action="{{ route('notifications.read', $n->id) }}">
                                @csrf
                                <button type="submit" class="px-3 py-1.5 rounded-lg border border-[#7c8799] dark:border-[#3E3E3A] text-xs text-[#64748b] dark:text-[#A1A09A] hover:border-[#f59e0b] hover:text-[#f59e0b] transition-colors">
                                    {{ __('notifications.mark_read') }}
                                </button>
                            </form>
                        @endif
                        @if ($n->action_key === 'confirm_referral_supplier' && !empty($n->related_supplier_id))
                            <form method="POST" action="{{ route('notifications.confirm_referral_supplier', $n->id) }}">
                                @csrf
                                <button type="submit" class="px-3 py-1.5 rounded-lg border border-emerald-200 dark:border-emerald-900/40 text-xs text-emerald-700 dark:text-emerald-300 hover:bg-emerald-50 dark:hover:bg-emerald-900/20 transition-colors">
                                    {{ __('notifications.referral_supplier_add') }}
                                </button>
                            </form>
                        @endif
                        @if (!empty($n->related_supplier_id))
                            <a href="{{ route('suppliers.show', ['supplierId' => $n->related_supplier_id, 'readonly' => 1]) }}" class="px-3 py-1.5 rounded-lg border border-[#7c8799] dark:border-[#3E3E3A] text-xs text-[#64748b] dark:text-[#A1A09A] hover:border-[#f59e0b] hover:text-[#f59e0b] transition-colors">
                                {{ __('notifications.view_supplier') }}
                            </a>
                        @endif


                        <form method="POST" action="{{ route('notifications.destroy', $n->id) }}" onsubmit="return confirm('{{ __('notifications.delete_confirm') }}')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="px-3 py-1.5 rounded-lg border border-red-200 dark:border-red-900/40 text-xs text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors">
                                {{ __('notifications.delete') }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @empty
            <div class="rounded-lg border border-[#7c8799] dark:border-[#3E3E3A] bg-white dark:bg-[#161615] p-8 text-center text-[#64748b] dark:text-[#A1A09A]">
                {{ __('notifications.empty') }}
            </div>
        @endforelse
    </div>

    @if ($notifications->hasPages())
        <div class="mt-6">{{ $notifications->links() }}</div>
    @endif
@endsection
