<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>NexoPOS — نظام نقطة البيع السحابي</title>
    <meta name="description" content="نظام نقطة بيع سحابي متكامل للشركات الصغيرة والمتوسطة. إدارة المبيعات، المخزون، والحسابات في مكان واحد.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        :root {
            --brand: #2563eb;
            --brand-dark: #1d4ed8;
            --brand-light: #eff6ff;
            --accent: #7c3aed;
            --success: #059669;
        }
        * { font-family: 'Tajawal', sans-serif; }

        /* ── Navbar ── */
        .navbar { background: rgba(255,255,255,.95); backdrop-filter: blur(10px); border-bottom: 1px solid #e5e7eb; }
        .navbar-brand { font-weight: 900; font-size: 1.5rem; color: var(--brand) !important; }

        /* ── Hero ── */
        .hero {
            background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 50%, #7c3aed 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            position: relative;
            overflow: hidden;
        }
        .hero::before {
            content: '';
            position: absolute;
            inset: 0;
            background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.04'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
        }
        .hero-title { font-size: clamp(2.5rem, 5vw, 4rem); font-weight: 900; line-height: 1.2; }
        .hero-subtitle { font-size: 1.25rem; opacity: .85; max-width: 550px; }
        .hero-badge { background: rgba(255,255,255,.15); border: 1px solid rgba(255,255,255,.3); border-radius: 2rem; padding: .35rem 1rem; font-size: .85rem; display: inline-block; }
        .hero-stats { background: rgba(255,255,255,.1); border-radius: 1rem; padding: 1.5rem; backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,.2); }
        .stat-number { font-size: 2rem; font-weight: 900; }

        /* ── Section titles ── */
        .section-label { color: var(--brand); font-weight: 700; letter-spacing: .05em; text-transform: uppercase; font-size: .85rem; }
        .section-title { font-size: 2.2rem; font-weight: 900; }

        /* ── Features ── */
        .feature-card { border: 1px solid #e5e7eb; border-radius: 1rem; padding: 2rem; transition: all .3s; height: 100%; }
        .feature-card:hover { border-color: var(--brand); transform: translateY(-4px); box-shadow: 0 20px 40px rgba(37,99,235,.1); }
        .feature-icon { width: 56px; height: 56px; border-radius: .75rem; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; margin-bottom: 1rem; }

        /* ── Pricing ── */
        .pricing-card { border: 2px solid #e5e7eb; border-radius: 1.25rem; padding: 2.5rem; position: relative; height: 100%; transition: all .3s; }
        .pricing-card:hover { transform: translateY(-4px); }
        .pricing-card.featured { border-color: var(--brand); background: var(--brand-light); }
        .pricing-card.featured::before { content: 'الأكثر شيوعاً'; position: absolute; top: -1px; left: 50%; transform: translateX(-50%); background: var(--brand); color: #fff; padding: .3rem 1.5rem; border-radius: 0 0 .75rem .75rem; font-size: .8rem; font-weight: 700; white-space: nowrap; }
        .price-amount { font-size: 3rem; font-weight: 900; color: var(--brand); line-height: 1; }
        .price-unit { font-size: 1rem; color: #6b7280; }
        .feature-list { list-style: none; padding: 0; }
        .feature-list li { padding: .4rem 0; display: flex; align-items: center; gap: .5rem; }
        .feature-list .check { color: var(--success); }
        .feature-list .cross { color: #d1d5db; }

        /* ── CTA ── */
        .cta-section { background: linear-gradient(135deg, #1e3a8a, #7c3aed); }

        /* ── Footer ── */
        footer { background: #111827; color: #9ca3af; }
        footer a { color: #9ca3af; text-decoration: none; }
        footer a:hover { color: #fff; }

        /* ── Buttons ── */
        .btn-brand { background: var(--brand); color: #fff; border: none; padding: .75rem 2rem; border-radius: .625rem; font-weight: 700; font-size: 1rem; transition: all .2s; }
        .btn-brand:hover { background: var(--brand-dark); color: #fff; transform: translateY(-1px); }
        .btn-outline-white { border: 2px solid rgba(255,255,255,.7); color: #fff; background: transparent; padding: .75rem 2rem; border-radius: .625rem; font-weight: 700; font-size: 1rem; transition: all .2s; }
        .btn-outline-white:hover { background: rgba(255,255,255,.1); color: #fff; }

        /* ── Testimonials ── */
        .testimonial-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 1rem; padding: 2rem; }
        .avatar { width: 48px; height: 48px; border-radius: 50%; background: var(--brand); display: flex; align-items: center; justify-content: center; color: #fff; font-weight: 700; font-size: 1.1rem; flex-shrink: 0; }
    </style>
</head>
<body>

{{-- ── Navbar ── --}}
<nav class="navbar navbar-expand-lg sticky-top">
    <div class="container">
        <a class="navbar-brand" href="{{ route('welcome') }}">
            <i class="fas fa-cash-register me-2"></i>NexoPOS
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto gap-1">
                <li class="nav-item"><a class="nav-link" href="#features">المميزات</a></li>
                <li class="nav-item"><a class="nav-link" href="#pricing">الأسعار</a></li>
                <li class="nav-item"><a class="nav-link" href="#testimonials">آراء العملاء</a></li>
            </ul>
            <div class="d-flex gap-2">
                @auth
                    <a href="{{ route('dashboard') }}" class="btn btn-brand">
                        <i class="fas fa-tachometer-alt me-1"></i>لوحة التحكم
                    </a>
                @else
                    <a href="{{ route('login') }}" class="btn btn-outline-secondary">تسجيل الدخول</a>
                    <a href="{{ route('register') }}" class="btn btn-brand">ابدأ مجاناً</a>
                @endauth
            </div>
        </div>
    </div>
</nav>

{{-- ── Hero ── --}}
<section class="hero text-white">
    <div class="container py-5 position-relative" style="z-index:1">
        <div class="row align-items-center g-5">
            <div class="col-lg-6">
                <span class="hero-badge mb-3">
                    <i class="fas fa-rocket me-1"></i>نظام سحابي متكامل
                </span>
                <h1 class="hero-title mt-3 mb-4">
                    أدِر متجرك<br>بذكاء وسهولة
                </h1>
                <p class="hero-subtitle mb-5">
                    نظام نقطة بيع سحابي متكامل يجمع المبيعات، المخزون، الحسابات،
                    والتقارير في مكان واحد — بدون تعقيد.
                </p>
                <div class="d-flex flex-wrap gap-3">
                    <a href="{{ route('register') }}" class="btn btn-brand" style="background:#fff;color:var(--brand);font-size:1.1rem;padding:.875rem 2.5rem;">
                        <i class="fas fa-play me-2"></i>ابدأ تجربة مجانية 14 يوم
                    </a>
                    <a href="#pricing" class="btn btn-outline-white" style="font-size:1.1rem;padding:.875rem 2rem;">
                        عرض الأسعار
                    </a>
                </div>
                <p class="mt-3 small opacity-75">لا يلزم بطاقة ائتمانية · إلغاء في أي وقت</p>
            </div>
            <div class="col-lg-6">
                <div class="row g-3">
                    <div class="col-6">
                        <div class="hero-stats text-center">
                            <div class="stat-number">+500</div>
                            <div class="small opacity-75">متجر نشط</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="hero-stats text-center">
                            <div class="stat-number">99.9%</div>
                            <div class="small opacity-75">وقت التشغيل</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="hero-stats text-center">
                            <div class="stat-number">+1M</div>
                            <div class="small opacity-75">فاتورة معالجة</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="hero-stats text-center">
                            <div class="stat-number">24/7</div>
                            <div class="small opacity-75">دعم فني</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ── Features ── --}}
<section id="features" class="py-6" style="padding:6rem 0; background:#f9fafb;">
    <div class="container">
        <div class="text-center mb-5">
            <div class="section-label mb-2">المميزات</div>
            <h2 class="section-title">كل ما تحتاجه في مكان واحد</h2>
            <p class="text-muted mt-2 mx-auto" style="max-width:500px">منصة متكاملة تغطي كافة احتياجات إدارة متجرك من البيع حتى التقارير المالية.</p>
        </div>
        <div class="row g-4">
            @foreach([
                ['icon'=>'fa-cash-register','bg'=>'#eff6ff','color'=>'#2563eb','title'=>'نقطة بيع سريعة','desc'=>'واجهة بيع سهلة الاستخدام مع دعم الباركود، الكميات، والخصومات التلقائية.'],
                ['icon'=>'fa-boxes-stacked','bg'=>'#f0fdf4','color'=>'#059669','title'=>'إدارة المخزون','desc'=>'تتبع المخزون لحظياً مع تنبيهات النفاد وإدارة المستودعات المتعددة.'],
                ['icon'=>'fa-file-invoice-dollar','bg'=>'#fdf4ff','color'=>'#7c3aed','title'=>'محاسبة متكاملة','desc'=>'قيود يومية، ميزانية عمومية، قائمة دخل، وتقارير مالية احترافية.'],
                ['icon'=>'fa-chart-line','bg'=>'#fff7ed','color'=>'#ea580c','title'=>'تقارير ذكية','desc'=>'تقارير مبيعات، أرباح، مرتجعات، وتدفق نقدي مع إمكانية التصدير.'],
                ['icon'=>'fa-users','bg'=>'#f0f9ff','color'=>'#0284c7','title'=>'إدارة العملاء','desc'=>'مجموعات العملاء، مستويات الأسعار، وتاريخ الشراء كاملاً.'],
                ['icon'=>'fa-truck','bg'=>'#fefce8','color'=>'#ca8a04','title'=>'أوامر الشراء','desc'=>'إدارة الموردين، أوامر الشراء، ومرتجعات المشتريات بسهولة.'],
                ['icon'=>'fa-tags','bg'=>'#fff1f2','color'=>'#e11d48','title'=>'العروض والترقيات','desc'=>'خصومات مرنة بالنسبة أو القيمة مع اشتري X واحصل على Y.'],
                ['icon'=>'fa-shield-halved','bg'=>'#f5f3ff','color'=>'#6d28d9','title'=>'أمان متقدم','desc'=>'صلاحيات دقيقة للمستخدمين، تحقق ثنائي، وسجل تدقيق كامل.'],
            ] as $f)
            <div class="col-md-6 col-lg-3">
                <div class="feature-card">
                    <div class="feature-icon" style="background:{{ $f['bg'] }};color:{{ $f['color'] }}">
                        <i class="fas {{ $f['icon'] }}"></i>
                    </div>
                    <h5 class="fw-bold mb-2">{{ $f['title'] }}</h5>
                    <p class="text-muted small mb-0">{{ $f['desc'] }}</p>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ── Pricing ── --}}
<section id="pricing" style="padding:6rem 0;">
    <div class="container">
        <div class="text-center mb-5">
            <div class="section-label mb-2">الأسعار</div>
            <h2 class="section-title">خطة مناسبة لكل حجم</h2>
            <p class="text-muted mt-2">جميع الخطط تشمل تجربة مجانية 14 يوماً بدون بطاقة ائتمانية.</p>
        </div>
        <div class="row g-4 justify-content-center">

            {{-- Basic --}}
            <div class="col-md-6 col-lg-4">
                <div class="pricing-card">
                    <div class="mb-4">
                        <span class="badge bg-secondary mb-2">Basic</span>
                        <div class="d-flex align-items-end gap-1">
                            <span class="price-amount">$49</span>
                            <span class="price-unit mb-2">/ شهر</span>
                        </div>
                        <p class="text-muted small">مثالي للمتاجر الصغيرة والناشئة</p>
                    </div>
                    <ul class="feature-list mb-4">
                        @foreach(['نقطة بيع واحدة','حتى 3 مستخدمين','إدارة المخزون','تقارير أساسية','دعم بالبريد الإلكتروني'] as $f)
                        <li><i class="fas fa-check-circle check"></i>{{ $f }}</li>
                        @endforeach
                        @foreach(['مستودعات متعددة','محاسبة متقدمة','API متكامل'] as $f)
                        <li><i class="fas fa-times-circle cross"></i><span class="text-muted">{{ $f }}</span></li>
                        @endforeach
                    </ul>
                    <a href="{{ route('register') }}" class="btn btn-outline-primary w-100 fw-bold">ابدأ مجاناً</a>
                </div>
            </div>

            {{-- Pro --}}
            <div class="col-md-6 col-lg-4">
                <div class="pricing-card featured">
                    <div class="mb-4">
                        <span class="badge bg-primary mb-2">Pro</span>
                        <div class="d-flex align-items-end gap-1">
                            <span class="price-amount">$99</span>
                            <span class="price-unit mb-2">/ شهر</span>
                        </div>
                        <p class="text-muted small">للمتاجر المتوسطة ذات الفروع المتعددة</p>
                    </div>
                    <ul class="feature-list mb-4">
                        @foreach(['نقاط بيع غير محدودة','حتى 15 مستخدم','مستودعات متعددة','محاسبة متكاملة','تقارير ذكية وتصدير','إدارة عملاء متقدمة','العروض والترقيات','دعم أولوية'] as $f)
                        <li><i class="fas fa-check-circle check"></i>{{ $f }}</li>
                        @endforeach
                        @foreach(['API متكامل'] as $f)
                        <li><i class="fas fa-times-circle cross"></i><span class="text-muted">{{ $f }}</span></li>
                        @endforeach
                    </ul>
                    <a href="{{ route('register') }}" class="btn btn-primary w-100 fw-bold">ابدأ مجاناً</a>
                </div>
            </div>

            {{-- Enterprise --}}
            <div class="col-md-6 col-lg-4">
                <div class="pricing-card">
                    <div class="mb-4">
                        <span class="badge" style="background:#7c3aed">Enterprise</span>
                        <div class="d-flex align-items-end gap-1">
                            <span class="price-amount" style="color:#7c3aed">$199</span>
                            <span class="price-unit mb-2">/ شهر</span>
                        </div>
                        <p class="text-muted small">للشركات الكبيرة والسلاسل التجارية</p>
                    </div>
                    <ul class="feature-list mb-4">
                        @foreach(['كل مميزات Pro','مستخدمون غير محدودون','API متكامل كامل','تقارير مخصصة','مدير حساب مخصص','SLA 99.9% مضمون','تكاملات مخصصة','تدريب وإعداد مجاني'] as $f)
                        <li><i class="fas fa-check-circle check"></i>{{ $f }}</li>
                        @endforeach
                    </ul>
                    <a href="{{ route('register') }}" class="btn w-100 fw-bold" style="background:#7c3aed;color:#fff;">ابدأ مجاناً</a>
                </div>
            </div>
        </div>

        <p class="text-center text-muted mt-4 small">
            <i class="fas fa-shield-halved me-1"></i>
            جميع الخطط تشمل SSL، نسخ احتياطي يومي، وضمان استرداد المال خلال 30 يوماً.
        </p>
    </div>
</section>

{{-- ── Testimonials ── --}}
<section id="testimonials" style="padding:6rem 0; background:#f9fafb;">
    <div class="container">
        <div class="text-center mb-5">
            <div class="section-label mb-2">آراء العملاء</div>
            <h2 class="section-title">يثقون بنا كل يوم</h2>
        </div>
        <div class="row g-4">
            @foreach([
                ['name'=>'أحمد الشمري','role'=>'صاحب سلسلة مطاعم','text'=>'منذ تحولنا إلى NexoPOS انخفض وقت الدفع بنسبة 40٪ وأصبح بإمكاني متابعة جميع الفروع من مكان واحد.','initial'=>'أ'],
                ['name'=>'سارة العتيبي','role'=>'مديرة متجر ملابس','text'=>'التقارير المالية أصبحت جاهزة في ثوانٍ، وتوفير الوقت الذي كنت أضيعه في الإكسل يستحق السعر ثلاث مرات.','initial'=>'س'],
                ['name'=>'محمد الغامدي','role'=>'صاحب صيدلية','text'=>'إدارة انتهاء الصلاحية والمخزون أصبحت تلقائية تماماً. فريق الدعم ممتاز ومتجاوب في أي وقت.','initial'=>'م'],
            ] as $t)
            <div class="col-md-4">
                <div class="testimonial-card h-100">
                    <div class="mb-3">
                        @for($i=0;$i<5;$i++)<i class="fas fa-star text-warning"></i>@endfor
                    </div>
                    <p class="text-muted mb-4">"{{ $t['text'] }}"</p>
                    <div class="d-flex align-items-center gap-3">
                        <div class="avatar">{{ $t['initial'] }}</div>
                        <div>
                            <div class="fw-bold">{{ $t['name'] }}</div>
                            <div class="text-muted small">{{ $t['role'] }}</div>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ── CTA ── --}}
<section class="cta-section text-white" style="padding:6rem 0;">
    <div class="container text-center">
        <h2 style="font-size:2.5rem;font-weight:900;" class="mb-3">جاهز لتطوير متجرك؟</h2>
        <p class="mb-5 opacity-85" style="font-size:1.2rem;max-width:500px;margin:auto;">
            ابدأ تجربتك المجانية اليوم — لا بطاقة ائتمانية، لا التزام، لا تعقيد.
        </p>
        <div class="d-flex flex-wrap gap-3 justify-content-center">
            <a href="{{ route('register') }}" class="btn" style="background:#fff;color:#1d4ed8;font-weight:700;font-size:1.1rem;padding:.875rem 3rem;border-radius:.625rem;">
                <i class="fas fa-play me-2"></i>ابدأ مجاناً الآن
            </a>
            <a href="{{ route('login') }}" class="btn btn-outline-white">
                لدي حساب بالفعل
            </a>
        </div>
    </div>
</section>

{{-- ── Footer ── --}}
<footer style="padding:4rem 0 2rem;">
    <div class="container">
        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="fw-bold text-white mb-2" style="font-size:1.25rem;">
                    <i class="fas fa-cash-register me-2" style="color:var(--brand)"></i>NexoPOS
                </div>
                <p class="small">نظام نقطة بيع سحابي متكامل للشركات العربية الصغيرة والمتوسطة.</p>
            </div>
            <div class="col-md-2">
                <div class="fw-semibold text-white mb-3">المنتج</div>
                <ul class="list-unstyled small">
                    <li class="mb-2"><a href="#features">المميزات</a></li>
                    <li class="mb-2"><a href="#pricing">الأسعار</a></li>
                    <li class="mb-2"><a href="{{ route('register') }}">تسجيل جديد</a></li>
                </ul>
            </div>
            <div class="col-md-2">
                <div class="fw-semibold text-white mb-3">الشركة</div>
                <ul class="list-unstyled small">
                    <li class="mb-2"><a href="#">من نحن</a></li>
                    <li class="mb-2"><a href="#">التواصل</a></li>
                    <li class="mb-2"><a href="#">الخصوصية</a></li>
                </ul>
            </div>
            <div class="col-md-4">
                <div class="fw-semibold text-white mb-3">ابق على اطلاع</div>
                <p class="small mb-3">احصل على آخر التحديثات والميزات الجديدة.</p>
                <div class="input-group">
                    <input type="email" class="form-control form-control-sm" placeholder="بريدك الإلكتروني" style="background:#1f2937;border-color:#374151;color:#fff;">
                    <button class="btn btn-primary btn-sm">اشترك</button>
                </div>
            </div>
        </div>
        <hr style="border-color:#374151;">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 small">
            <span>© {{ date('Y') }} NexoPOS. جميع الحقوق محفوظة.</span>
            <div class="d-flex gap-3">
                <a href="#"><i class="fab fa-twitter"></i></a>
                <a href="#"><i class="fab fa-linkedin"></i></a>
                <a href="#"><i class="fab fa-github"></i></a>
            </div>
        </div>
    </div>
</footer>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/js/bootstrap.bundle.min.js"></script>
<script>
document.querySelectorAll('a[href^="#"]').forEach(a => {
    a.addEventListener('click', e => {
        const target = document.querySelector(a.getAttribute('href'));
        if (target) { e.preventDefault(); target.scrollIntoView({ behavior: 'smooth' }); }
    });
});
</script>
</body>
</html>
