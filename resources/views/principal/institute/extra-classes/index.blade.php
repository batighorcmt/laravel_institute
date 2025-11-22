@extends('layouts.admin')

@section('title', 'Extra Classes - ' . $school->name)

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Extra Classes</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('principal.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('principal.institute.manage', $school) }}">{{ $school->name }}</a></li>
                    <li class="breadcrumb-item active">Extra Classes</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">All Extra Classes</h3>
                <div class="card-tools">
                    <a href="{{ route('principal.institute.extra-classes.create', $school) }}" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus"></i> Create Extra Class
                    </a>
                </div>
            </div>
            <div class="card-body">
                @if($extraClasses->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Class</th>
                                    <th>Section</th>
                                    <th>Subject</th>
                                    <th>Teacher</th>
                                    <th>Academic Year</th>
                                    <th>Schedule</th>
                                    <th>Students</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($extraClasses as $extraClass)
                                <tr>
                                    <td>{{ $extraClass->name }}</td>
                                    <td>{{ $extraClass->schoolClass->name ?? 'N/A' }}</td>
                                    <td>{{ $extraClass->section->name ?? 'N/A' }}</td>
                                    <td>{{ $extraClass->subject->name ?? 'N/A' }}</td>
                                    <td>{{ $extraClass->teacher->name ?? 'N/A' }}</td>
                                    <td>{{ $extraClass->academicYear->name ?? 'N/A' }}</td>
                                    <td>{{ $extraClass->schedule ?? 'Not Set' }}</td>
                                    <td>
                                        <span class="badge badge-info">
                                            {{ $extraClass->enrollments->count() }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($extraClass->status === 'active')
                                            <span class="badge badge-success">Active</span>
                                        @else
                                            <span class="badge badge-secondary">Inactive</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="{{ route('principal.institute.extra-classes.students', [$school, $extraClass]) }}" 
                                               class="btn btn-info" title="Manage Students">
                                                <i class="fas fa-users"></i>
                                            </a>
                                            <a href="{{ route('principal.institute.extra-classes.edit', [$school, $extraClass]) }}" 
                                               class="btn btn-warning" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <form action="{{ route('principal.institute.extra-classes.destroy', [$school, $extraClass]) }}" 
                                                  method="POST" 
                                                  onsubmit="return confirm('Are you sure you want to delete this extra class?');"
                                                  style="display: inline;">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-danger" title="Delete">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="mt-3">
                        {{ $extraClasses->links() }}
                    </div>
                @else
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> No extra classes found. 
                        <a href="{{ route('principal.institute.extra-classes.create', $school) }}">Create one now</a>.
                    </div>
                @endif
            </div>
        </div>
    </div>
</section>
@endsection
