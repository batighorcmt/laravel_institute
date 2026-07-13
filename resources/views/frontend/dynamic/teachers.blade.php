@php($teachers = $dynamicData['teachers'] ?? [])

<div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6">
    @forelse($teachers as $teacher)
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-5 text-center">
            <img src="{{ $teacher['photo'] ?? asset('images/avatar-placeholder.png') }}" alt="{{ $teacher['name'] }}"
                 class="w-24 h-24 rounded-full object-cover mx-auto mb-3 border-4 border-slate-50">
            <h3 class="font-bold text-lg text-indigo-950">{{ $teacher['name'] }}</h3>
            <p class="text-sm text-slate-500">{{ $teacher['designation'] }}</p>
        </div>
    @empty
        <p class="text-slate-500 col-span-full text-center py-10">কোনো তথ্য পাওয়া যায়নি।</p>
    @endforelse
</div>
