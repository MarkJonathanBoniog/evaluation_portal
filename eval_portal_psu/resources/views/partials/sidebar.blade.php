@php
    use Illuminate\Support\Facades\Route;

    $user = auth()->user();

    $navItems = collect(config('nav'))
        ->filter(function ($item) use ($user) {
            if (! $user) {
                return false;
            }

            // Require all roles in `roles` if provided
            if (! empty($item['roles'])) {
                foreach ($item['roles'] as $role) {
                    if (! $user->hasRole($role)) {
                        return false;
                    }
                }
            }

            // At least one role in `roles_any`
            if (! empty($item['roles_any'])) {
                $hasAny = collect($item['roles_any'])->some(fn ($role) => $user->hasRole($role));
                if (! $hasAny) {
                    return false;
                }
            }

            return true;
        })
        ->filter(fn ($i) => Route::has($i['route'] ?? ''))
        ->values();

    [$primaryItems, $managementItems] = $navItems->partition(fn ($i) => ! ($i['mgmt'] ?? false));

    $prepareItems = function ($items) {
        return $items->map(function ($item) {
            $prefixes = $item['active_prefix'] ?? [($item['route'] ?? '') . '*'];
            $activePatterns = is_array($prefixes) ? $prefixes : [$prefixes];

            $isActive = request()->routeIs($activePatterns);

            $iconName = $isActive
                ? ($item['icon_fill'] ?? $item['icon'] ?? null)
                : ($item['icon'] ?? null);

            return [
                'label'     => $item['label'] ?? '',
                'route'     => $item['route'] ?? '',
                'icon'      => $iconName,
                'is_active' => $isActive,
            ];
        });
    };

    $primaryView    = $prepareItems($primaryItems);
    $managementView = $prepareItems($managementItems);
@endphp

<style>
    #portal-rail .rail-label { transition: opacity .15s ease; }
    #portal-rail:not(.expanded) .rail-label { opacity: 0; }
</style>

<div x-data="{ expanded:false }">
    <nav id="portal-rail"
        x-on:open-rail.window="expanded = true"
        x-on:toggle-rail.window="expanded = !expanded"
        x-init="
            $el.addEventListener('mouseenter', () => {
                if (window.matchMedia('(min-width: 1024px)').matches) expanded = true;
            });
            $el.addEventListener('mouseleave', () => {
                if (window.matchMedia('(min-width: 1024px)').matches) expanded = false;
            });
        "
        class="fixed top-16 bottom-0 left-0 z-40 w-64 bg-[#1520a6] text-white transform transition-[width,transform] overflow-hidden flex flex-col shadow-lg"
        :class="expanded ? 'expanded translate-x-0' : '-translate-x-full lg:translate-x-0 lg:w-16'"
        aria-label="Primary">

        <div class="flex-1 min-h-0 overflow-y-auto mt-3">
            <div class="px-2">
                @if ($primaryView->isNotEmpty())
                    <ul class="space-y-1">
                        @foreach ($primaryView as $item)
                            <li>
                                <a href="{{ route($item['route']) }}"
                                   class="flex items-center gap-3 px-3 py-2 rounded-md transition-colors
                                          {{ $item['is_active'] ? 'bg-white/10 text-white' : 'text-white/80 hover:bg-white/10 hover:text-white' }}"
                                   @if($item['is_active']) aria-current="page" @endif>
                                    <span class="w-5 text-center">
                                        @if($item['icon'])
                                            <i class="bi bi-{{ $item['icon'] }}"></i>
                                        @endif
                                    </span>
                                    <span class="text-sm rail-label" x-show="expanded">
                                        {{ $item['label'] }}
                                    </span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif

                @if ($managementView->isNotEmpty())
                    <div class="mt-6 mb-2 flex items-center justify-between text-[11px] uppercase tracking-wide text-white/60 px-3">
                        <span x-show="expanded" x-transition.opacity.duration.150ms>Management</span>
                        <span class="border-t border-white/10 flex-1 ml-2"></span>
                    </div>

                    <ul class="space-y-1">
                        @foreach ($managementView as $item)
                            <li>
                                <a href="{{ route($item['route']) }}"
                                   class="flex items-center gap-3 px-3 py-2 rounded-md transition-colors
                                          {{ $item['is_active'] ? 'bg-white/10 text-white' : 'text-white/80 hover:bg-white/10 hover:text-white' }}"
                                   @if($item['is_active']) aria-current="page" @endif>
                                    <span class="w-5 text-center">
                                        @if($item['icon'])
                                            <i class="bi bi-{{ $item['icon'] }}"></i>
                                        @endif
                                    </span>
                                    <span class="text-sm rail-label" x-show="expanded">
                                        {{ $item['label'] }}
                                    </span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>

        <div class="border-t border-white/10 px-3 py-3 text-xs text-white/80">
            @auth
                <div class="flex items-center gap-3">
                    <div class="leading-tight" x-show="expanded" x-transition.opacity.duration.150ms>
                        <div class="font-semibold text-white">{{ auth()->user()->name }}</div>
                        <div class="text-white/60">{{ auth()->user()->email }}</div>
                    </div>
                </div>
            @endauth
        </div>
    </nav>

    <div class="fixed inset-0 bg-black/40 z-30 lg:hidden"
         x-show="expanded"
         x-transition.opacity
         x-on:click="expanded = false">
    </div>
</div>
