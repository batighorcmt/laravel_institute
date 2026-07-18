<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="{{ asset('images/batighor-favicon.png') }}">
    <title>Batighor EIMS | Sign In</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;600;800&family=Hind+Siliguri:wght@300;400;600;700&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Vue 3 CDN -->
    <script src="https://unpkg.com/vue@3/dist/vue.global.prod.js"></script>

    <style>
        :root {
            --primary: #4f46e5;
            --primary-gradient: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            --glass-bg: rgba(255, 255, 255, 0.85);
            --glass-border: rgba(255, 255, 255, 0.5);
            --text-main: #1e293b;
            --text-muted: #64748b;
            --input-bg: rgba(255, 255, 255, 0.9);
        }

        * { box-sizing: border-box; }

        body {
            font-family: 'Plus Jakarta Sans', 'Hind Siliguri', sans-serif;
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #0f172a;
            overflow: hidden;
        }

        /* Animated Mesh Background */
        .mesh-bg {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            background-color: #0f172a;
            background-image: 
                radial-gradient(at 0% 0%, hsla(253,16%,7%,1) 0, transparent 50%), 
                radial-gradient(at 50% 0%, hsla(225,39%,30%,1) 0, transparent 50%), 
                radial-gradient(at 100% 0%, hsla(339,49%,30%,1) 0, transparent 50%), 
                radial-gradient(at 0% 100%, hsla(339,49%,30%,1) 0, transparent 50%), 
                radial-gradient(at 50% 100%, hsla(225,39%,30%,1) 0, transparent 50%), 
                radial-gradient(at 100% 100%, hsla(253,16%,7%,1) 0, transparent 50%);
            filter: blur(80px);
            opacity: 0.8;
            animation: pulse 10s ease infinite alternate;
        }

        @keyframes pulse {
            0% { transform: scale(1); opacity: 0.7; }
            100% { transform: scale(1.1); opacity: 0.9; }
        }

        .login-wrapper {
            width: 100%;
            max-width: 460px;
            padding: 20px;
            z-index: 10;
        }

        .glass-card {
            background: var(--glass-bg);
            backdrop-filter: blur(25px) saturate(200%);
            -webkit-backdrop-filter: blur(25px) saturate(200%);
            border: 1px solid var(--glass-border);
            border-radius: 32px;
            padding: 50px 45px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.4);
            animation: slideUp 0.8s cubic-bezier(0.16, 1, 0.3, 1) both;
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .brand-section {
            text-align: center;
            margin-bottom: 40px;
        }

        .brand-logo {
            width: 90px;
            height: 90px;
            margin-bottom: 16px;
            filter: drop-shadow(0 10px 15px rgba(79, 70, 229, 0.3));
        }

        .brand-name {
            font-size: 24px;
            font-weight: 800;
            color: var(--text-main);
            margin: 0;
            letter-spacing: -0.5px;
        }

        .brand-tagline {
            font-size: 14px;
            color: var(--text-muted);
            margin-top: 4px;
        }

        .form-group {
            margin-bottom: 24px;
            position: relative;
            padding: 0 4px; /* Slight side margin for the group */
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 700;
            font-size: 13px;
            color: var(--text-main);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-left: 4px;
        }

        .input-control {
            width: 100%;
            padding: 12px 16px;
            background: var(--input-bg);
            border: 1px solid rgba(0,0,0,0.08);
            border-radius: 14px;
            color: var(--text-main);
            font-size: 15px;
            font-weight: 500;
            transition: all 0.3s;
            outline: none;
            box-shadow: inset 0 2px 4px rgba(0,0,0,0.02);
            height: 52px; /* Fixed height for consistency */
        }

        .input-control:focus {
            background: #fff;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1);
        }

        .password-wrapper {
            position: relative;
            width: 100%;
            display: flex;
            align-items: center;
        }

        .input-control.has-toggle {
            padding-right: 46px;
        }

        .toggle-password {
            position: absolute;
            right: 12px;
            height: 100%;
            width: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: none;
            border: none;
            color: var(--text-muted);
            cursor: pointer;
            font-size: 16px;
            z-index: 5;
            transition: color 0.2s;
        }

        .toggle-password:hover {
            color: var(--primary);
        }

        .btn-submit {
            width: 100%;
            padding: 16px;
            background: var(--primary-gradient);
            border: none;
            border-radius: 16px;
            color: white;
            font-weight: 700;
            font-size: 16px;
            margin-top: 12px;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 10px 20px -5px rgba(79, 70, 229, 0.4);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            filter: brightness(1.1);
            box-shadow: 0 20px 25px -5px rgba(79, 70, 229, 0.5);
        }

        .btn-submit:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
        }

        .extras {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 16px;
            font-size: 14px;
        }

        .remember-me {
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--text-muted);
            cursor: pointer;
        }

        .forgot-link {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
        }

        .forgot-link:hover { text-decoration: underline; }

        .error-box {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.2);
            color: #b91c1c;
            padding: 14px 18px;
            border-radius: 16px;
            font-size: 13px;
            margin-bottom: 24px;
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }

        .caps-warning {
            font-size: 11px;
            color: #ef4444;
            margin-top: 6px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .spinner {
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255,255,255,0.3);
            border-top-color: #fff;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin { to { transform: rotate(360deg); } }

        .footer-text {
            text-align: center;
            margin-top: 32px;
            font-size: 12px;
            color: rgba(255,255,255,0.5);
            font-weight: 500;
            letter-spacing: 0.5px;
        }
    </style>
</head>
<body>
    <div id="app">
        <div class="mesh-bg"></div>
        
        <div class="login-wrapper">
            <div class="glass-card">
                <div class="brand-section">
                    <img src="{{ asset('images/batighor_eims.png') }}" alt="Batighor" class="brand-logo">
                    <h1 class="brand-name">Batighor EIMS</h1>
                    <p class="brand-tagline">Educational Institute Management System</p>
                </div>

                @if (session('status'))
                <div class="error-box">
                    <i class="fas fa-info-circle mt-1"></i>
                    <div>{{ session('status') }}</div>
                </div>
                @endif

                @if ($errors->any())
                <div class="error-box">
                    <i class="fas fa-exclamation-circle mt-1"></i>
                    <div>
                        @foreach ($errors->all() as $error)
                            <div>{{ $error }}</div>
                        @endforeach
                    </div>
                </div>
                @endif

                <form action="{{ route('login') }}" method="POST" @submit="handleSubmit">
                    @csrf
                    
                    <div class="form-group">
                        <label for="email" class="form-label">Email / Username</label>
                        <input 
                            type="text" 
                            id="email" 
                            name="email" 
                            class="input-control" 
                            placeholder="Email or Username"
                            required
                            v-model="form.email"
                            autocomplete="username"
                        >
                    </div>

                    <div class="form-group">
                        <label for="password" class="form-label">Password</label>
                        <div class="password-wrapper">
                            <input 
                                :type="showPassword ? 'text' : 'password'" 
                                id="password" 
                                name="password" 
                                class="input-control has-toggle" 
                                placeholder="••••••••"
                                required
                                v-model="form.password"
                                @keyup="checkCapsLock"
                                autocomplete="current-password"
                            >
                            <button type="button" @click="showPassword = !showPassword" class="toggle-password">
                                <i :class="showPassword ? 'fas fa-eye-slash' : 'fas fa-eye'"></i>
                            </button>
                        </div>
                        <div v-if="capsLockActive" class="caps-warning">
                            <i class="fas fa-triangle-exclamation"></i> CAPS LOCK ACTIVE
                        </div>
                    </div>

                    <div class="extras">
                        <label class="remember-me">
                            <input type="checkbox" name="remember" v-model="form.remember">
                            <span>Remember Me</span>
                        </label>
                        @if (Route::has('password.request'))
                            <a href="{{ route('password.request') }}" class="forgot-link">Forgot?</a>
                        @endif
                    </div>

                    <button type="submit" class="btn-submit" :disabled="loading">
                        <span v-if="loading" class="spinner"></span>
                        <span v-else>Sign In</span>
                    </button>
                </form>
            </div>
            
            <div class="footer-text">
                © {{ date('Y') }} BATIGHOR SOFTWARE SYSTEMS LTD.
            </div>
        </div>
    </div>

    <script>
        const { createApp, ref } = Vue;

        createApp({
            setup() {
                const loading = ref(false);
                const showPassword = ref(false);
                const capsLockActive = ref(false);
                const form = ref({
                    email: '{{ old("email") }}',
                    password: '',
                    remember: {{ old("remember") ? 'true' : 'false' }}
                });

                const checkCapsLock = (e) => {
                    capsLockActive.value = e.getModifierState && e.getModifierState('CapsLock');
                };

                const handleSubmit = () => {
                    loading.value = true;
                };

                return {
                    loading,
                    showPassword,
                    capsLockActive,
                    form,
                    checkCapsLock,
                    handleSubmit
                };
            }
        }).mount('#app');
    </script>
</body>
</html>
