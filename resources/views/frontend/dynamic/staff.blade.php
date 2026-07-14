@include('frontend.dynamic._person-grid', [
    'people' => $dynamicData['staff'] ?? [],
    'placeholderIcon' => 'fa-user-tie',
    'gridId' => 'staff-grid',
])
