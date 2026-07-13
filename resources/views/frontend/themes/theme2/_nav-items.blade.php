@php($items = $items ?? [])
@foreach($items as $item)
    @php($hasChildren = !empty($item['children']))
    <li class="nav-item">
        <a href="{{ $item['url'] ?? '#' }}" @if(($item['target'] ?? '_self') === '_blank') target="_blank" rel="noopener noreferrer" @endif>
            {{ $item['label'] ?? '' }}
            @if($hasChildren)
                <i class="fa-solid fa-chevron-down chev"></i>
            @endif
        </a>
        @if($hasChildren)
            <ul class="submenu">
                @foreach($item['children'] as $child)
                    <li>
                        <a href="{{ $child['url'] ?? '#' }}" @if(($child['target'] ?? '_self') === '_blank') target="_blank" rel="noopener noreferrer" @endif>
                            {{ $child['label'] ?? '' }}
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
    </li>
@endforeach
