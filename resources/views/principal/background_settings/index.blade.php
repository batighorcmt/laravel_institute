@extends('layouts.admin')

@section('title', 'Documents Background Settings')

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Documents Background Settings</h1>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Admit Card Background</h3>
            </div>
            <div class="card-body">
                @if(session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif

                <form action="{{ route('principal.institute.background_settings.update', $school) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="form-group">
                        <label for="admit_card_v1_background">Upload Admit Card (v1) Background</label>
                        <input type="file" name="admit_card_v1_background" id="admit_card_v1_background" class="form-control">
                        <small class="text-muted">Recommended size: 700x1005px. Max size: 2MB.</small>
                    </div>

                    @if($admitBackground)
                        <div class="mt-3">
                            <p>Current Background:</p>
                            <img src="{{ asset('storage/' . $admitBackground) }}" alt="Background" style="max-width: 200px; border: 1px solid #ddd;">
                        </div>
                    @endif

                    <button type="submit" class="btn btn-primary mt-4">Save Background</button>
                </form>
            </div>
        </div>
    </div>
</section>
@endsection
