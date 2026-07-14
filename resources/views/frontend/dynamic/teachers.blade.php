@include('frontend.dynamic._person-grid', [
    'people' => $dynamicData['teachers'] ?? [],
    'placeholderIcon' => 'fa-chalkboard-teacher',
    'gridId' => 'teachers-grid',
])
