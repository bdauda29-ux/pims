@foreach($nodes as $node)
    @php
        $office = $node['office'];
        $children = $node['children'] ?? [];
    @endphp

    <div class="flex items-center justify-between gap-4 py-2">
        <div class="min-w-0">
            <div class="flex items-center gap-2">
                @if((int) $level > 0)
                    <div class="h-2 w-2 rounded-full bg-gray-300"></div>
                @endif
                <div class="font-semibold text-gray-900 truncate">{{ $office->name }}</div>
                @if(!empty($office->type))
                    <span class="text-xs px-2 py-1 rounded bg-gray-100 text-gray-700 font-semibold whitespace-nowrap">
                        {{ $office->type }}
                    </span>
                @endif
            </div>
        </div>

        <div class="shrink-0">
            <form method="POST" action="{{ route('offices.destroy', $office) }}" onsubmit="return confirm('Delete this office?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="text-red-600 font-semibold hover:underline">
                    Delete
                </button>
            </form>
        </div>
    </div>

    @if(!empty($children))
        <div class="border-l border-gray-200 pl-4" style="margin-left: {{ (int) $indentPx }}px;">
            @include('offices._tree', ['nodes' => $children, 'level' => $level + 1, 'indentPx' => $indentPx])
        </div>
    @endif
@endforeach
