@extends('layouts.admin')

@section('title', 'পরীক্ষার বিস্তারিত')

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">{{ $exam->name }}</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('principal.dashboard') }}">ড্যাশবোর্ড</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('principal.institute.exams.index', $school) }}">পরীক্ষা তালিকা</a></li>
                    <li class="breadcrumb-item active">বিস্তারিত</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        @endif

        <!-- Exam Information Card -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">পরীক্ষার তথ্য</h3>
                <div class="card-tools">
                    <a href="{{ route('principal.institute.exams.edit', [$school, $exam]) }}" class="btn btn-sm btn-warning">
                        <i class="fas fa-edit"></i> সম্পাদনা করুন
                    </a>
                    <a href="{{ route('principal.institute.marks.show', [$school, $exam]) }}" class="btn btn-sm btn-primary">
                        <i class="fas fa-pen"></i> নম্বর Entry
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-borderless">
                            <tr>
                                <th width="40%">পরীক্ষার নাম:</th>
                                <td>{{ $exam->name }}</td>
                            </tr>
                            <tr>
                                <th>পরীক্ষার নাম (বাংলা):</th>
                                <td>{{ $exam->name_bn ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th>শ্রেণি:</th>
                                <td>{{ $exam->class->name ?? 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th>শিক্ষাবর্ষ:</th>
                                <td>{{ $exam->academicYear->name ?? 'N/A' }}</td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-borderless">
                            <tr>
                                <th width="40%">শুরুর তারিখ:</th>
                                <td>{{ $exam->start_date ? $exam->start_date->format('d/m/Y') : 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th>শেষের তারিখ:</th>
                                <td>{{ $exam->end_date ? $exam->end_date->format('d/m/Y') : 'N/A' }}</td>
                            </tr>
                            <tr>
                                <th>অবস্থা:</th>
                                <td>
                                    @if($exam->status == 'active')
                                        <span class="badge badge-success">সক্রিয়</span>
                                    @elseif($exam->status == 'completed')
                                        <span class="badge badge-info">সম্পন্ন</span>
                                    @elseif($exam->status == 'cancelled')
                                        <span class="badge badge-danger">বাতিল</span>
                                    @else
                                        <span class="badge badge-secondary">খসড়া</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>বিবরণ:</th>
                                <td>{{ $exam->description ?? 'N/A' }}</td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Exam Subjects Card -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">পরীক্ষার বিষয়সমূহ</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-sm btn-primary" data-toggle="modal" data-target="#addSubjectModal">
                        <i class="fas fa-plus"></i> নতুন বিষয় যুক্ত করুন
                    </button>
                </div>
            </div>
            <div class="card-body">
                @if($exam->examSubjects->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th>ক্রমিক</th>
                                    <th>বিষয়ের নাম</th>
                                    <th>শিক্ষক</th>
                                    <th>সৃজনশীল</th>
                                    <th>MCQ</th>
                                    <th>ব্যবহারিক</th>
                                    <th>মোট</th>
                                    <th>পরীক্ষার তারিখ</th>
                                    <th>কার্যক্রম</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($exam->examSubjects->sortBy('display_order') as $examSubject)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td><strong>{{ $examSubject->subject->name ?? 'N/A' }}</strong></td>
                                        <td>{{ $examSubject->teacher->name ?? 'Not Assigned' }}</td>
                                        <td>{{ $examSubject->creative_full_mark }}</td>
                                        <td>{{ $examSubject->mcq_full_mark }}</td>
                                        <td>{{ $examSubject->practical_full_mark }}</td>
                                        <td><strong>{{ $examSubject->total_full_mark }}</strong></td>
                                        <td>{{ $examSubject->exam_date ? $examSubject->exam_date->format('d/m/Y') : 'N/A' }}</td>
                                        <td>
                                            <button class="btn btn-sm btn-warning" data-toggle="modal" data-target="#editSubjectModal{{ $examSubject->id }}">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <form action="{{ route('principal.institute.exams.subjects.remove', [$school, $exam, $examSubject]) }}" method="POST" class="d-inline" onsubmit="return confirm('আপনি কি নিশ্চিত?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>

                                    <!-- Edit Subject Modal -->
                                    <div class="modal fade" id="editSubjectModal{{ $examSubject->id }}" tabindex="-1">
                                        <div class="modal-dialog modal-lg">
                                            <div class="modal-content">
                                                <form action="{{ route('principal.institute.exams.subjects.update', [$school, $exam, $examSubject]) }}" method="POST">
                                                    @csrf
                                                    @method('PUT')
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">বিষয় সম্পাদনা করুন</h5>
                                                        <button type="button" class="close" data-dismiss="modal">
                                                            <span>&times;</span>
                                                        </button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="row">
                                                            <div class="col-md-6">
                                                                <div class="form-group">
                                                                    <label>বিষয়</label>
                                                                    <input type="text" class="form-control" value="{{ $examSubject->subject->name }}" readonly>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <div class="form-group">
                                                                    <label>শিক্ষক</label>
                                                                    <select name="teacher_id" class="form-control">
                                                                        <option value="">-- নির্বাচন করুন --</option>
                                                                        @foreach($teachers as $teacher)
                                                                            <option value="{{ $teacher->id }}" {{ $examSubject->teacher_id == $teacher->id ? 'selected' : '' }}>
                                                                                {{ $teacher->name }}
                                                                            </option>
                                                                        @endforeach
                                                                    </select>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div class="row">
                                                            <div class="col-md-4">
                                                                <div class="form-group">
                                                                    <label>সৃজনশীল পূর্ণমান</label>
                                                                    <input type="number" name="creative_full_mark" class="form-control" value="{{ $examSubject->creative_full_mark }}" min="0" required>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-4">
                                                                <div class="form-group">
                                                                    <label>MCQ পূর্ণমান</label>
                                                                    <input type="number" name="mcq_full_mark" class="form-control" value="{{ $examSubject->mcq_full_mark }}" min="0" required>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-4">
                                                                <div class="form-group">
                                                                    <label>ব্যবহারিক পূর্ণমান</label>
                                                                    <input type="number" name="practical_full_mark" class="form-control" value="{{ $examSubject->practical_full_mark }}" min="0" required>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div class="row">
                                                            <div class="col-md-4">
                                                                <div class="form-group">
                                                                    <label>সৃজনশীল পাস মার্ক</label>
                                                                    <input type="number" name="creative_pass_mark" class="form-control" value="{{ $examSubject->creative_pass_mark }}" min="0" required>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-4">
                                                                <div class="form-group">
                                                                    <label>MCQ পাস মার্ক</label>
                                                                    <input type="number" name="mcq_pass_mark" class="form-control" value="{{ $examSubject->mcq_pass_mark }}" min="0" required>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-4">
                                                                <div class="form-group">
                                                                    <label>ব্যবহারিক পাস মার্ক</label>
                                                                    <input type="number" name="practical_pass_mark" class="form-control" value="{{ $examSubject->practical_pass_mark }}" min="0" required>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div class="row">
                                                            <div class="col-md-4">
                                                                <div class="form-group">
                                                                    <label>পাস টাইপ</label>
                                                                    <select name="pass_type" class="form-control" required>
                                                                        <option value="combined" {{ $examSubject->pass_type == 'combined' ? 'selected' : '' }}>সম্মিলিত</option>
                                                                        <option value="each" {{ $examSubject->pass_type == 'each' ? 'selected' : '' }}>প্রতিটিতে আলাদা</option>
                                                                    </select>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-4">
                                                                <div class="form-group">
                                                                    <label>পরীক্ষার তারিখ</label>
                                                                    <input type="date" name="exam_date" class="form-control" value="{{ $examSubject->exam_date ? $examSubject->exam_date->format('Y-m-d') : '' }}">
                                                                </div>
                                                            </div>
                                                            <div class="col-md-4">
                                                                <div class="form-group">
                                                                    <label>ক্রম নম্বর</label>
                                                                    <input type="number" name="display_order" class="form-control" value="{{ $examSubject->display_order }}" min="0">
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div class="row">
                                                            <div class="col-md-4">
                                                                <div class="form-group">
                                                                    <label>শুরুর সময়</label>
                                                                    <input type="time" name="exam_start_time" class="form-control" value="{{ $examSubject->exam_start_time }}">
                                                                </div>
                                                            </div>
                                                            <div class="col-md-4">
                                                                <div class="form-group">
                                                                    <label>শেষের সময়</label>
                                                                    <input type="time" name="exam_end_time" class="form-control" value="{{ $examSubject->exam_end_time }}">
                                                                </div>
                                                            </div>
                                                            <div class="col-md-4">
                                                                <div class="form-group">
                                                                    <label>নম্বর Entry শেষ তারিখ</label>
                                                                    <input type="date" name="mark_entry_deadline" class="form-control" value="{{ $examSubject->mark_entry_deadline ? $examSubject->mark_entry_deadline->format('Y-m-d') : '' }}">
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-dismiss="modal">বাতিল</button>
                                                        <button type="submit" class="btn btn-primary">সংরক্ষণ করুন</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> এই পরীক্ষায় এখনো কোনো বিষয় যুক্ত করা হয়নি।
                    </div>
                @endif
            </div>
        </div>

        <!-- Add Subject Modal -->
        <div class="modal fade" id="addSubjectModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <form action="{{ route('principal.institute.exams.subjects.add', [$school, $exam]) }}" method="POST">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title">নতুন বিষয় যুক্ত করুন</h5>
                            <button type="button" class="close" data-dismiss="modal">
                                <span>&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>বিষয় <span class="text-danger">*</span></label>
                                        <select name="subject_id" class="form-control" required>
                                            <option value="">-- নির্বাচন করুন --</option>
                                            @foreach($subjects as $subject)
                                                <option value="{{ $subject->id }}">{{ $subject->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>শিক্ষক</label>
                                        <select name="teacher_id" class="form-control">
                                            <option value="">-- নির্বাচন করুন --</option>
                                            @foreach($teachers as $teacher)
                                                <option value="{{ $teacher->id }}">{{ $teacher->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>সৃজনশীল পূর্ণমান</label>
                                        <input type="number" name="creative_full_mark" class="form-control" value="0" min="0" required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>MCQ পূর্ণমান</label>
                                        <input type="number" name="mcq_full_mark" class="form-control" value="0" min="0" required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>ব্যবহারিক পূর্ণমান</label>
                                        <input type="number" name="practical_full_mark" class="form-control" value="0" min="0" required>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>সৃজনশীল পাস মার্ক</label>
                                        <input type="number" name="creative_pass_mark" class="form-control" value="0" min="0" required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>MCQ পাস মার্ক</label>
                                        <input type="number" name="mcq_pass_mark" class="form-control" value="0" min="0" required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>ব্যবহারিক পাস মার্ক</label>
                                        <input type="number" name="practical_pass_mark" class="form-control" value="0" min="0" required>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>পাস টাইপ</label>
                                        <select name="pass_type" class="form-control" required>
                                            <option value="combined">সম্মিলিত</option>
                                            <option value="each">প্রতিটিতে আলাদা</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>পরীক্ষার তারিখ</label>
                                        <input type="date" name="exam_date" class="form-control">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>ক্রম নম্বর</label>
                                        <input type="number" name="display_order" class="form-control" value="0" min="0">
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>শুরুর সময়</label>
                                        <input type="time" name="exam_start_time" class="form-control">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>শেষের সময়</label>
                                        <input type="time" name="exam_end_time" class="form-control">
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>নম্বর Entry শেষ তারিখ</label>
                                <input type="date" name="mark_entry_deadline" class="form-control">
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">বাতিল</button>
                            <button type="submit" class="btn btn-primary">সংরক্ষণ করুন</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
