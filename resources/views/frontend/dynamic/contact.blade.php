@php($contact = $dynamicData)

<div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 md:p-10 space-y-4">
    @if(!empty($contact['address']))
        <p><i class="fas fa-map-marker-alt text-indigo-500 mr-2"></i> {{ $contact['address'] }}</p>
    @endif
    @if(!empty($contact['phone']))
        <p><i class="fas fa-phone text-indigo-500 mr-2"></i> {{ $contact['phone'] }}</p>
    @endif
    @if(!empty($contact['email']))
        <p><i class="fas fa-envelope text-indigo-500 mr-2"></i> {{ $contact['email'] }}</p>
    @endif
    <div class="flex gap-3 pt-2">
        @if(!empty($contact['facebook_url']))
            <a href="{{ $contact['facebook_url'] }}" target="_blank" class="text-indigo-600"><i class="fab fa-facebook fa-lg"></i></a>
        @endif
        @if(!empty($contact['youtube_url']))
            <a href="{{ $contact['youtube_url'] }}" target="_blank" class="text-indigo-600"><i class="fab fa-youtube fa-lg"></i></a>
        @endif
    </div>
</div>
