@extends('layouts.admin')

@section('title', 'ফিস হিসাব')

@section('content')
<div id="app">
    <parent-fee-dashboard 
        :children="{{ json_encode($children) }}" 
        :selected-student="{{ json_encode($selectedStudent) }}">
    </parent-fee-dashboard>
</div>
@endsection
