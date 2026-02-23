@if($children->count() > 1)
<div class="card mb-3">
    <div class="card-body p-2">
        <form action="{{ url()->current() }}" method="GET" class="form-inline justify-content-center">
            <label class="mr-2">শিক্ষার্থী নির্বাচন করুন: </label>
            <select name="student_id" class="form-control form-control-sm mr-2" onchange="this.form.submit()">
                @foreach($children as $child)
                <option value="{{ $child->id }}" {{ $selectedStudent->id == $child->id ? 'selected' : '' }}>
                    {{ $child->student_name_en }} ({{ $child->student_id }})
                </option>
                @endforeach
            </select>
            <button type="submit" class="btn btn-primary btn-sm">পরিবর্তন করুন</button>
        </form>
    </div>
</div>
@endif
