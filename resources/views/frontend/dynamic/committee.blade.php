@php($committee = $dynamicData ?? [])

<div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 md:p-10">
    @if(!empty($committee['intro']))
        <div class="prose max-w-none mb-6">{!! $committee['intro'] !!}</div>
    @endif

    @php($members = $committee['members'] ?? [])
    @if(count($members))
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($members as $member)
                <div class="flex items-center gap-4 p-4 rounded-2xl border border-slate-100 bg-slate-50/50">
                    <img
                        src="{{ $member['photo'] ?? asset('images/default-avatar.svg') }}"
                        alt="{{ $member['name'] ?? '' }}"
                        class="w-16 h-16 rounded-full object-cover border border-slate-200 shrink-0"
                    >
                    <div class="min-w-0">
                        <div class="font-bold text-slate-800 truncate">{{ $member['name'] ?? '' }}</div>
                        @if(!empty($member['designation']))
                            <div class="text-indigo-600 text-sm font-semibold">{{ $member['designation'] }}</div>
                        @endif
                        @if(!empty($member['mobile']))
                            <div class="text-slate-500 text-xs mt-1"><i class="fas fa-phone-alt mr-1"></i>{{ $member['mobile'] }}</div>
                        @endif
                        @if(!empty($member['address']))
                            <div class="text-slate-500 text-xs mt-0.5"><i class="fas fa-location-dot mr-1"></i>{{ $member['address'] }}</div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <p class="text-slate-500 text-center py-10">কোনো কমিটি সদস্য যুক্ত করা হয়নি।</p>
    @endif
</div>
