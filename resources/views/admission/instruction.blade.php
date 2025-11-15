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
                @if (session('error'))
                    <div class="alert alert-danger mb-3" role="alert">
                        {{ session('error') }}
                    </div>
                @endif
                @if (session('success'))
                    <div class="alert alert-success mb-3" role="alert">
                        {{ session('success') }}
                    </div>
                @endif
                <div class="card instruction-card shadow-lg border-0">
                    <div class="card-header bg-primary text-white py-3">
                        <h4 class="mb-0 text-center"><i class="fas fa-info-circle me-2"></i> ভর্তি নির্দেশনা - {{ $school->name }}</h4>
                    </div>
                    <div class="card-body p-4 p-md-5">
                        <p class="lead text-center mb-4">ভর্তি আবেদন ফরম পূরণের পূর্বে অনুগ্রহ করে নিচের নির্দেশনাগুলো মনোযোগ দিয়ে পড়ুন।</p>
                        
                        <ul class="list-group list-group-flush instruction-list mb-4">
                            <li class="list-group-item"><i class="fas fa-star-of-life text-danger me-2 fa-xs"></i> তারকা (*) চিহ্নিত সকল ফিল্ড অবশ্যই পূরণ করতে হবে।</li>
                            <li class="list-group-item"><i class="fas fa-image text-primary me-2"></i> আবেদনকারীর ছবির সাইজ অবশ্যই 300x300 পিক্সেল এবং 1MB এর কম হতে হবে।</li>
                            <li class="list-group-item"><i class="fas fa-mobile-alt text-primary me-2"></i> আপনার সঠিক মোবাইল নম্বর দিন, কারণ এটি পরবর্তী যোগাযোগের জন্য ব্যবহৃত হবে।</li>
                            <li class="list-group-item"><i class="fas fa-exclamation-triangle text-warning me-2"></i> ভুল বা অসম্পূর্ণ তথ্য প্রদান করলে আবেদনপত্র বাতিল বলে গণ্য হবে।</li>
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

