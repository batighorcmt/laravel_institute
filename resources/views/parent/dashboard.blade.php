@extends('layouts.admin')
@section('title', 'ড্যাশবোর্ড')

@push('styles')
<style>
    .mobile-app-card { border-radius: 1.5rem; transition: all 0.3s ease; border: none; }
    .mobile-app-card:hover { transform: translateY(-3px); box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1); }
    .quick-access-btn { 
        display: flex; flex-direction: column; align-items: center; justify-content: center; 
        padding: 1.25rem; border-radius: 1.25rem; background: white; border: 1px solid #f1f5f9;
        transition: all 0.2s ease-in-out; text-decoration: none; height: 100%;
    }
    .quick-access-btn:hover { background: #f8fafc; border-color: #cbd5e1; transform: scale(1.02); }
    .quick-access-btn .icon-bg {
        width: 3.5rem; height: 3.5rem; border-radius: 1rem; display: flex; align-items: center; justify-content: center;
        margin-bottom: 0.75rem; font-size: 1.25rem;
    }
    .due-alert-card {
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        border-radius: 1.5rem; padding: 1.5rem; color: white; display: flex; align-items: center; justify-content: space-between;
        box-shadow: 0 10px 20px -5px rgba(239, 68, 68, 0.4);
    }
    .student-badge { background: #eff6ff; color: #1e40af; border-radius: 0.75rem; padding: 0.4rem 0.75rem; border: 1px solid #dbeafe; }
</style>
@endpush

@section('content')
@if($children->count() > 0)
<div class="container-fluid pb-5">
    <!-- Student Header Section -->
    <div class="row align-items-center mb-6 pt-3">
        <div class="col-md-6 mb-3 mb-md-0">
            <h1 class="text-2xl font-black text-gray-800 tracking-tight">আসসালামু আলাইকুম!</h1>
            <p class="text-gray-500 font-medium mt-1">স্বাগতম, আপনার শিক্ষার্থীর বর্তমান অবস্থা দেখে নিন</p>
        </div>
        <div class="col-md-6 text-md-right">
            @if($children->count() > 1)
            <div class="dropdown d-inline-block shadow-sm">
                <button class="btn btn-white border-slate-200 rounded-xl px-4 py-2 font-bold dropdown-toggle" type="button" data-toggle="dropdown">
                    <img class="img-circle mr-2" width="24" height="24" src="{{ $selectedStudent->photo ? asset('storage/'.$selectedStudent->photo) : asset('images/default-student.png') }}">
                    {{ $selectedStudent->student_name_en }}
                </button>
                <div class="dropdown-menu dropdown-menu-right rounded-xl shadow-lg border-0 mt-2">
                    @foreach($children as $child)
                    <a class="dropdown-item py-2 {{ $selectedStudent->id == $child->id ? 'active bg-indigo-50 text-indigo-700' : '' }}" 
                       href="{{ route('parent.dashboard', ['student_id' => $child->id]) }}">
                       {{ $child->student_name_en }}
                    </a>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
    </div>

    @if(!$selectedStudent)
        <div class="alert alert-warning border-0 rounded-2xl shadow-sm">কোনো শিক্ষার্থীর তথ্য পাওয়া যায়নি।</div>
    @else
        <div class="row">
            <!-- Student Mini Profile Card -->
            <div class="col-12 mb-6">
                <div class="bg-white p-4 rounded-3xl shadow-sm border border-slate-100 d-flex flex-column flex-md-row align-items-center gap-4">
                    <div class="flex-shrink-0">
                        <img class="rounded-2xl border-4 border-slate-50 shadow-sm" width="80" height="80" 
                             src="{{ $selectedStudent->photo ? asset('storage/'.$selectedStudent->photo) : asset('images/default-student.png') }}">
                    </div>
                    <div class="text-center text-md-left flex-grow-1">
                        <h2 class="text-xl font-black text-slate-900 mb-2">{{ $selectedStudent->student_name_en }}</h2>
                        <div class="d-flex flex-wrap gap-2 justify-center justify-md-start">
                             <span class="student-badge text-xs font-bold"><i class="fas fa-id-card mr-1"></i> ID: {{ $selectedStudent->student_id }}</span>
                             <span class="student-badge text-xs font-bold"><i class="fas fa-graduation-cap mr-1"></i> Class: {{ $selectedStudent->enrollments()->latest()->first()?->class?->name ?? 'N/A' }}</span>
                             <span class="student-badge text-xs font-bold"><i class="fas fa-user-friends mr-1"></i> Section: {{ $selectedStudent->enrollments()->latest()->first()?->section?->name ?? 'N/A' }}</span>
                             <span class="student-badge text-xs font-bold"><i class="fas fa-hashtag mr-1"></i> Roll: {{ $selectedStudent->enrollments()->latest()->first()?->roll_no ?? 'N/A' }}</span>
                        </div>
                    </div>
                    <div class="text-md-right mt-3 mt-md-0 d-none d-lg-block">
                         <p class="text-[10px] text-slate-400 font-black uppercase tracking-widest mb-1">প্রোফাইল স্ট্যাটাস</p>
                         <span class="badge badge-success px-3 py-2 rounded-pill font-bold">Active Student</span>
                    </div>
                </div>
            </div>

            <!-- Quick Access Menu -->
            <div class="col-12 mb-8">
                <h3 class="text-slate-900 font-black text-sm uppercase tracking-widest mb-5 flex items-center gap-2">
                    <span class="w-2 h-6 bg-indigo-600 rounded-full"></span>
                    দ্রুত এক্সেস (Quick Access)
                </h3>
                <div class="row g-4 d-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 1rem;">
                    <a href="{{ route('parent.fees') }}" class="quick-access-btn">
                        <div class="icon-bg bg-orange-50 text-orange-600"><i class="fas fa-wallet"></i></div>
                        <span class="text-slate-800 font-bold text-sm">ফি হিসাব</span>
                    </a>
                    <a href="{{ route('parent.routine') }}" class="quick-access-btn">
                        <div class="icon-bg bg-indigo-50 text-indigo-600"><i class="fas fa-calendar-alt"></i></div>
                        <span class="text-slate-800 font-bold text-sm">রুটিন</span>
                    </a>
                    <a href="{{ route('parent.attendance.class') }}" class="quick-access-btn">
                        <div class="icon-bg bg-green-50 text-green-600"><i class="fas fa-user-check"></i></div>
                        <span class="text-slate-800 font-bold text-sm">হাজিরা রিপোর্ট</span>
                    </a>
                    <a href="{{ route('parent.homework') }}" class="quick-access-btn">
                        <div class="icon-bg bg-blue-50 text-blue-600"><i class="fas fa-edit"></i></div>
                        <span class="text-slate-800 font-bold text-sm">হোমওয়ার্ক</span>
                    </a>
                    <a href="{{ route('parent.evaluations') }}" class="quick-access-btn">
                        <div class="icon-bg bg-purple-50 text-purple-600"><i class="fas fa-chart-line"></i></div>
                        <span class="text-slate-800 font-bold text-sm">ইভ্যালুয়েশন</span>
                    </a>
                    <a href="{{ route('parent.notices') }}" class="quick-access-btn">
                        <div class="icon-bg bg-red-50 text-red-600"><i class="fas fa-bullhorn"></i></div>
                        <span class="text-slate-800 font-bold text-sm">নোটিশ</span>
                    </a>
                </div>
            </div>

            <!-- Outstanding Dues Section (If any) -->
            @if($totalDueAmount > 0)
            <div class="col-12 mb-8 animate-in slide-in-from-bottom">
                 <div class="due-alert-card">
                      <div class="d-flex align-items-center gap-4">
                           <div class="w-14 h-14 bg-white/20 rounded-2xl flex items-center justify-center text-3xl">
                                <i class="fas fa-exclamation-triangle"></i>
                           </div>
                           <div>
                                <p class="text-white/80 font-bold text-sm mb-1 uppercase tracking-wider">বকেয়া ফিসের পরিসংখ্যান</p>
                                <h3 class="text-3xl font-black mb-0">৳{{ number_format($totalDueAmount, 0) }}</h3>
                           </div>
                      </div>
                      <a href="{{ route('parent.fees') }}" class="btn btn-white btn-lg rounded-2xl font-black px-6 shadow-xl hover:scale-105 transition-all text-red-600">
                           পেমেন্ট করুন <i class="fas fa-arrow-right ml-2"></i>
                      </a>
                 </div>
            </div>
            @endif

            <!-- Regular Stats Rows -->
            <div class="col-md-6 mb-6">
                 <div class="bg-white rounded-[2rem] p-6 shadow-sm border border-slate-100 h-full">
                      <div class="d-flex justify-between items-center mb-6">
                           <h3 class="text-slate-900 font-black tracking-tight">সর্বশেষ ফলাফল</h3>
                           <a href="#" class="text-indigo-600 font-bold text-xs">সবগুলো দেখুন</a>
                      </div>
                      <div class="table-responsive">
                           <table class="table table-borderless align-middle mb-0">
                                <thead>
                                     <tr class="text-slate-400 text-[10px] uppercase font-black tracking-widest border-bottom border-slate-50">
                                          <th>পরীক্ষা</th>
                                          <th class="text-center">গ্রেড</th>
                                     </tr>
                                </thead>
                                <tbody>
                                     @forelse($latestResults as $result)
                                     <tr>
                                          <td class="py-3 font-bold text-slate-800">{{ $result->exam->name }}</td>
                                          <td class="py-3 text-center">
                                               <span class="badge badge-success px-3 py-2 rounded-lg font-black">{{ $result->grade ?? $result->letter_grade }}</span>
                                          </td>
                                     </tr>
                                     @empty
                                     <tr>
                                          <td colspan="2" class="py-10 text-center text-slate-300 italic">ফলাফল পাওয়া যায়নি</td>
                                     </tr>
                                     @endforelse
                                </tbody>
                           </table>
                      </div>
                 </div>
            </div>

            <!-- Attendance Mini Card -->
            <div class="col-md-6 mb-6">
                 <div class="bg-white rounded-[2rem] p-6 shadow-sm border border-slate-100 h-full">
                      <div class="d-flex justify-between items-center mb-6">
                           <h3 class="text-slate-900 font-black tracking-tight">উপস্থিতি রেকর্ডস</h3>
                           <div class="bg-indigo-50 text-indigo-700 px-3 py-1 rounded-pill text-[10px] font-black uppercase">এই বছর</div>
                      </div>
                      
                      <div class="row g-4 text-center">
                           <div class="col-6">
                                <div class="bg-green-50 border border-green-100 rounded-3xl p-5">
                                     <h3 class="text-3xl font-black text-green-600 mb-1">{{ $attendanceStats['present'] }}</h3>
                                     <p class="text-green-800/60 font-black text-[10px] uppercase tracking-widest mb-0">উপস্থিতি</p>
                                </div>
                           </div>
                           <div class="col-6">
                                <div class="bg-red-50 border border-red-100 rounded-3xl p-5">
                                     <h3 class="text-3xl font-black text-red-600 mb-1">{{ $attendanceStats['absent'] }}</h3>
                                     <p class="text-red-800/60 font-black text-[10px] uppercase tracking-widest mb-0">অনুপস্থিতি</p>
                                </div>
                           </div>
                      </div>

                      <div class="mt-8 pt-8 border-t border-slate-50">
                           <a href="{{ route('parent.attendance.class') }}" class="btn btn-outline-indigo btn-block rounded-2xl font-black py-3">বিস্তারিত হাজিরা দেখুন</a>
                      </div>
                 </div>
            </div>

        </div>
    @endif
</div>
@endif

@endsection

@push('scripts')
<script>
    // Animations for elements
    document.addEventListener('DOMContentLoaded', () => {
        const cards = document.querySelectorAll('.animate-in');
        cards.forEach((card, index) => {
            card.style.animationDelay = `${index * 0.1}s`;
        });
    });
</script>
@endpush
