<x-layout.public :school="$school" :title="'ভর্তি নির্দেশনা — ' . $school->name">
    @push('styles')
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .instruction-card {
            border-radius: 15px;
            overflow: hidden;
        }
        .instruction-list li {
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        .instruction-list li:last-child {
            border-bottom: none;
        }
        .form-check-input:checked {
            background-color: #0d6efd;
            border-color: #0d6efd;
        }
        .btn-primary {
            transition: all 0.3s;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .container {
            max-width: 1024px;
            margin-left: auto;
            margin-right: auto;
        }
    </style>
    @endpush

    <div class="container my-5">
        <div class="row justify-content-center">
            <div class="col-lg-9">
                <div class="card instruction-card shadow-lg border-0">
                    <div class="card-header bg-primary text-white py-3">
                        <h4 class="mb-0 text-center"><i class="fas fa-info-circle me-2"></i> ভর্তি ফরম নির্দেশনা - {{ $school->name }}</h4>
                    </div>
                    <div class="card-body p-4 p-md-5">
                        <p class="lead text-center mb-4">ভর্তি আবেদন ফরম পূরণের পূর্বে অনুগ্রহ করে নিচের নির্দেশনাগুলো মনোযোগ দিয়ে পড়ুন।</p>
                        
                        <ul class="list-group list-group-flush instruction-list mb-4">
                            <li class="list-group-item"><i class="fas fa-star-of-life text-danger me-2 fa-xs"></i> তারকা (*) চিহ্নিত সকল ফিল্ড অবশ্যই পূরণ করতে হবে।</li>
                            <li class="list-group-item"><i class="fas fa-people-arrows text-primary me-2"></i> আবেদনকারীর নাম, পিতার নাম, মাতার নাম ও অভিভাবকের নাম বাংলা ও ইংরেজীতে লিখতে হবে।</li>
                            <li class="list-group-item"><i class="fas fa-mobile-alt text-primary me-2"></i> যে সকল ঘরে সংখ্যা হবে যেমন মোবাইল নং, জন্ম তারিখ ইত্যাদি ইংরেজীতে সংখ্যায় লিখতে হবে।</li>
                            <li class="list-group-item"><i class="fas fa-mobile-alt text-primary me-2"></i> একটি মাত্র মোবাইল নং দিয়ে একজন শিক্ষার্থীর ভর্তি আবেদন করা যাবে।</li>
                            <li class="list-group-item"><i class="fas fa-mobile-alt text-primary me-2"></i> আপনার সঠিক মোবাইল নম্বর দিন, কারণ এটি পরবর্তী যোগাযোগের জন্য ব্যবহৃত হবে।</li>
                            <li class="list-group-item"><i class="fas fa-image text-primary me-2"></i> আবেদনকারীর ছবির সাইজ অবশ্যই পাসপোর্ট সাইজের এবং 1MB এর কম হতে হবে।</li>
                            <li class="list-group-item"><i class="fas fa-location text-primary me-2"></i> ঠিকানা অবশ্যই বাংলায় লিখতে হবে।</li>
                            <li class="list-group-item"><i class="fas fa-exclamation-triangle text-warning me-2"></i> ভুল বা অসম্পূর্ণ তথ্য প্রদান করলে আবেদনপত্র বাতিল বলে গণ্য হবে।</li>
                            <li class="list-group-item text-justify"><i class="fas fa-info-circle text-warning me-2"></i> আবেদন সম্পন্ন হওয়ার পর প্রদত্ত মোবাইল নম্বরে ইউজার নেম ও পাসওয়ার্ড পাঠানো হবে। যা দিয়ে পরবর্তীতে আবেদনপত্রের অবস্থা দেখা যাবে। আবেদন করে অনলাইনে ভর্তি পরীক্ষার ফিস জমা দিতে হবে। ভর্তি ফিস জমা হলে আবেদন কপি পাওয়া যাবে। আবেদনপত্রটি বিদ্যালয়ে জমা দিয়ে ভর্তি পরীক্ষার প্রবেশপত্র, মৌলিক বিষয়াদি বই ও ২০২৬ সালের ক্যালেন্ডার সংগ্রহ করুন। </li>
                            <li class="list-group-item"><i class="fas fa-exclamation-triangle text-warning me-2"></i> ভর্তি পরীক্ষার দিন প্রবেশপত্র ছাড়া পরীক্ষায় অংশগ্রহণ করা যাবে না। ফলে প্রবেশপত্র যত্নসহকারে সংরক্ষণ করুন।</li>
                            <li class="list-group-item"><i class="fas fa-phone text-primary me-2"></i> ভর্তি আবেদন করতে কোন সমস্যা হলে হটলাইনে যোগাযোগ করুন: ০১৭৬২৩৯৬৭১৩, ০১৮৮৫৯২৬৩৬৩, ০১৭১৩৯১৩০৭৬।</li>
                        </ul>

                        <form action="{{ route('admission.instruction.consent', $school->code) }}" method="post" class="needs-validation" novalidate>
                            @csrf
                            <div class="bg-light p-3 rounded text-center">
                                <div class="form-check form-check-inline d-inline-flex align-items-center">
                                    <input type="checkbox" name="consent" value="1" class="form-check-input me-2" id="consentCheck" required style="width: 1.5em; height: 1.5em;">
                                    <label class="form-check-label h5 mb-0" for="consentCheck">
                                        আমি উপরের সকল নির্দেশনা পড়েছি এবং আবেদন করতে সম্মত।
                                    </label>
                                </div>
                                <div class="invalid-feedback d-block text-center mt-2">
                                    ফরম পূরণ শুরু করার জন্য, অনুগ্রহ করে এখানে টিক দিন।
                                </div>
                            </div>
                            <div class="mt-4 text-center">
                                <button type="submit" class="btn btn-primary btn-lg px-5">
                                    <i class="fas fa-arrow-right me-2"></i> ফরম পূরণ শুরু করুন
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        // Bootstrap form validation
        (function() {
            'use strict';
            const forms = document.querySelectorAll('.needs-validation');
            Array.prototype.slice.call(forms)
                .forEach(function(form) {
                    form.addEventListener('submit', function(event) {
                        if (!form.checkValidity()) {
                            event.preventDefault();
                            event.stopPropagation();
                        }
                        form.classList.add('was-validated');
                    }, false);
                });
        })();
    </script>
    @endpush
</x-layout.public>

