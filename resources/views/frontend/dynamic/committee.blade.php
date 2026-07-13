@php($committee = $dynamicData ?? [])

<div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 md:p-10">
    @if(!empty($committee['intro']))
        <div class="prose max-w-none mb-6">{!! $committee['intro'] !!}</div>
    @endif

    @php($members = $committee['members'] ?? [])
    @if(count($members))
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 text-slate-600 text-sm">
                        <th class="p-3 border border-slate-100">ক্রমিক</th>
                        <th class="p-3 border border-slate-100">নাম</th>
                        <th class="p-3 border border-slate-100">পদবী</th>
                        <th class="p-3 border border-slate-100">মোবাইল নং</th>
                        <th class="p-3 border border-slate-100">ঠিকানা</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($members as $member)
                        <tr class="text-sm">
                            <td class="p-3 border border-slate-100">{{ $member['serial'] ?? '' }}</td>
                            <td class="p-3 border border-slate-100 font-semibold">{{ $member['name'] ?? '' }}</td>
                            <td class="p-3 border border-slate-100">{{ $member['designation'] ?? '' }}</td>
                            <td class="p-3 border border-slate-100">{{ $member['mobile'] ?? '' }}</td>
                            <td class="p-3 border border-slate-100">{{ $member['address'] ?? '' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <p class="text-slate-500 text-center py-10">কোনো কমিটি সদস্য যুক্ত করা হয়নি।</p>
    @endif
</div>
