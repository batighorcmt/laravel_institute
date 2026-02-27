@extends('layouts.admin')

@section('title', 'Result Settings')

@section('content')
<div class="row">
    <div class="col-md-8 mx-auto">
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-chart-line mr-2"></i> রেজাল্ট সেটিংস</h3>
            </div>
            <form action="{{ route('principal.institute.result-settings.store', $school) }}" method="POST">
                @csrf
                <div class="card-body">
                    <div class="form-group">
                        <label for="marks_decimal_position">মার্কস ডেসিমেল পজিশন (নম্বর দশমিকের পরে কত সংখ্যা হবে)</label>
                        <input type="number" name="marks_decimal_position" id="marks_decimal_position" 
                               class="form-control @error('marks_decimal_position') is-invalid @enderror" 
                               value="{{ old('marks_decimal_position', $settings['marks_decimal_position']->value ?? 0) }}" 
                               min="0" max="5" required>
                        <small class="form-text text-muted">0 নির্ধারণ করা হলে কোন দশমিক সংখ্যা থাকবে না।</small>
                        @error('marks_decimal_position')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
                <div class="card-footer text-right">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> আপডেট করুন</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
