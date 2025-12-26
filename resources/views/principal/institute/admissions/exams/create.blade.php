@extends('layouts.admin')
@section('title','নতুন ভর্তি পরীক্ষা')
@section('content')
<h4 class="mb-3">নতুন ভর্তি পরীক্ষা তৈরি</h4>
<form method="POST" action="{{ route('principal.institute.admissions.exams.store',$school) }}" id="examForm">
    @csrf
    <div class="card mb-3">
        <div class="card-body">
            <div class="form-row">
                <div class="form-group col-md-3">
                    <label>শ্রেণি *</label>
                    <select name="class_name" class="form-control" required>
                        <option value="">-- নির্বাচন করুন --</option>
                        @foreach(($classOptions ?? []) as $cls)
                            <option value="{{ $cls }}" {{ old('class_name')===$cls?'selected':'' }}>{{ $cls }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-md-6">
                    <label>পরীক্ষার নাম *</label>
                    <input type="text" name="name" class="form-control" required value="{{ old('name') }}">
                </div>
                <div class="form-group col-md-3">
                    <label>পরীক্ষার ধরন *</label>
                    <select name="type" class="form-control" id="examType" required>
                        <option value="subject" {{ old('type')==='subject'?'selected':'' }}>প্রতি বিষয়ে মূল্যায়ন</option>
                        <option value="overall" {{ old('type')==='overall'?'selected':'' }}>সামগ্রীক মূল্যায়ন</option>
                    </select>
                </div>
                <div class="form-group col-md-3 overall-only d-none">
                    <label>সামগ্রীক পূর্ণ নম্বর *</label>
                    <input type="number" name="overall_full_mark" class="form-control" min="1" value="{{ old('overall_full_mark') }}">
                </div>
                <div class="form-group col-md-3 overall-only d-none">
                    <label>সামগ্রীক পাস নম্বর *</label>
                    <input type="number" name="overall_pass_mark" class="form-control" min="0" value="{{ old('overall_pass_mark') }}">
                </div>
                <div class="form-group col-md-3">
                    <label>পরীক্ষার তারিখ</label>
                    <input type="date" name="exam_date" class="form-control" value="{{ old('exam_date') }}">
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-3" id="subjectsCard">
        <div class="card-header d-flex justify-content-between align-items-center">
            <strong>বিষয়সমূহ</strong>
            <button type="button" class="btn btn-sm btn-outline-primary" onclick="addSubjectRow()">বিষয় যোগ</button>
        </div>
        <div class="card-body p-0">
            <table class="table table-sm mb-0" id="subjectsTable">
                <thead>
                    <tr>
                        <th style="width:30%">বিষয়ের নাম *</th>
                        <th style="width:15%">পূর্ণ নম্বর *</th>
                        <th style="width:15%" class="subject-only">পাস নম্বর *</th>
                        <th style="width:10%"></th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

    <button class="btn btn-success">সংরক্ষণ করুন</button>
    <a href="{{ route('principal.institute.admissions.exams.index',$school) }}" class="btn btn-secondary">ফিরে যান</a>
</form>

<script>
function addSubjectRow(name='', full='', pass=''){
    const tbody = document.querySelector('#subjectsTable tbody');
    const tr = document.createElement('tr');
    tr.innerHTML = `
        <td><input name="subject_name[]" class="form-control form-control-sm" required value="${name}"></td>
        <td><input type="number" min="1" name="full_mark[]" class="form-control form-control-sm" required value="${full}"></td>
        <td class="subject-only"><input type="number" min="0" name="pass_mark[]" class="form-control form-control-sm" value="${pass}"></td>
        <td><button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('tr').remove()">X</button></td>`;
    tbody.appendChild(tr);
}
document.addEventListener('DOMContentLoaded', function(){
    // seed one row
    if (document.querySelector('#subjectsTable tbody').children.length===0){ addSubjectRow(); }
    const typeSelect = document.getElementById('examType');
    function adjustType(){
        const isOverall = typeSelect.value==='overall';
        document.querySelectorAll('.subject-only').forEach(el=>{ el.style.display = isOverall? 'none':'table-cell'; });
        document.querySelectorAll('.overall-only').forEach(el=>{ el.classList.toggle('d-none', !isOverall); });
        // Toggle required for overall fields
        document.querySelectorAll('[name="overall_full_mark"], [name="overall_pass_mark"]').forEach(el=>{
            el.required = isOverall;
        });
    }
    typeSelect.addEventListener('change', adjustType); adjustType();
});
</script>
@endsection