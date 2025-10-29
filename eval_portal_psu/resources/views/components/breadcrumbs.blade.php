@props(['links' => []])

<nav class="text-sm text-gray-600 dark:text-gray-300 mb-4">
    <ol class="list-reset flex items-center space-x-2">
        @foreach ($links as $label => $url)
            @if ($loop->last)
                <li class="font-semibold text-gray-900 dark:text-gray-100">{{ $label }}</li>
            @else
                <li>
                    <a href="{{ $url }}" class="hover:text-blue-600 dark:hover:text-blue-400">{{ $label }}</a>
                    <span class="mx-1 text-gray-400">/</span>
                </li>
            @endif
        @endforeach
    </ol>
</nav>
