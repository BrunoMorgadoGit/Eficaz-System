@props(['title' => 'Eficaz B2B', 'activeNav' => ''])

@include('layouts.app', [
    'title' => $title,
    'activeNav' => $activeNav,
    'slot' => $slot,
])
