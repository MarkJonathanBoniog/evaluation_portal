@php
    /** @var \App\Models\User|null $user */
    $user = auth()->user();
    $isGuest = ! $user;
    $fullName = $user?->name ?? 'Guest';
    $roleName = $user?->getRoleNames()->first() ?? 'guest';
@endphp

<header
    x-data="{ openRail() { window.dispatchEvent(new CustomEvent('open-rail')) } }"
    class="fixed top-0 inset-x-0 h-16 bg-white border-b-4 border-[#1520a6] shadow-sm z-40">
    <div class="h-full px-4 sm:px-6 lg:px-8 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <button
                type="button"
                class="inline-flex items-center justify-center h-10 w-10 rounded-md border border-gray-200 text-gray-700 hover:bg-gray-100 lg:hidden"
                x-on:click="openRail()"
                aria-label="Toggle menu"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </button>

            <div class="flex items-center gap-2">
                <div class="h-10 w-10 rounded-full bg-indigo-600 text-white grid place-items-center font-semibold">
                    PSU
                </div>
                <div class="leading-tight">
                    <div class="text-sm font-semibold text-gray-900">Pangasinan State University</div>
                    <div class="text-xs text-gray-500">Evaluation Portal</div>
                </div>
            </div>
        </div>

        <div class="relative" x-data="{ open:false }">
            <button
                type="button"
                class="inline-flex items-center gap-2 px-3 py-2 rounded-md bg-white border border-gray-200 text-sm font-medium text-gray-700 hover:bg-gray-50 shadow-sm"
                x-on:click="open = !open"
                aria-haspopup="true"
                x-bind:aria-expanded="open"
            >
                <div class="text-left">
                    <div class="text-sm font-semibold text-gray-900 truncate">{{ $fullName }}</div>
                    <div class="text-xs text-gray-500 truncate">{{ \Illuminate\Support\Str::title($roleName) }}</div>
                </div>
                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </button>

            <div
                x-cloak
                x-show="open"
                x-transition
                x-on:click.outside="open = false"
                class="absolute right-0 mt-2 w-56 bg-white border border-gray-200 rounded-md shadow-lg z-50 overflow-hidden"
            >
                @if (! $isGuest)
                    <a href="{{ route('profile.edit') }}" class="block px-4 py-3 text-sm text-gray-700 hover:bg-gray-50">
                        Profile
                    </a>
                    <div class="border-t border-gray-100"></div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button
                            type="submit"
                            class="w-full text-left px-4 py-3 text-sm text-gray-700 hover:bg-gray-50"
                        >
                            Log Out
                        </button>
                    </form>
                @else
                    <div class="px-4 py-3 text-sm text-gray-600">
                        You are browsing as a guest.
                    </div>
                @endif
            </div>
        </div>
    </div>
</header>
