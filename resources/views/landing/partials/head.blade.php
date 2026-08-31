<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>DANUM — Administrasi Persuratan Digital</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        :root { color-scheme: light; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; background: #f7f9fc; color: #0f172a; }
        .landing { min-height: 100vh; overflow: hidden; }
        .nav { height: 72px; display: flex; align-items: center; justify-content: space-between; max-width: 1180px; margin: 0 auto; padding: 0 28px; }
        .logo { font-size: 31px; line-height: 1; font-weight: 900; letter-spacing: -1.8px; color: #fbbd00; }
        .nav-actions { display: flex; gap: 10px; align-items: center; }
        .btn { display: inline-flex; align-items: center; justify-content: center; min-height: 42px; padding: 0 18px; border-radius: 11px; text-decoration: none; font-size: 13px; font-weight: 700; transition: .18s ease; }
        .btn-primary { background: #0f172a; color: #fff; box-shadow: 0 5px 14px rgba(15,23,42,.14); }
        .btn-primary:hover { transform: translateY(-1px); background: #182238; }
        .btn-secondary { background: #fff; color: #334155; border: 1px solid #dbe3ef; }
        .hero { max-width: 1180px; margin: 0 auto; padding: 76px 28px 45px; display: grid; grid-template-columns: 1.08fr .92fr; gap: 70px; align-items: center; }
        .eyebrow { display: inline-flex; align-items: center; gap: 8px; padding: 7px 11px; border-radius: 999px; background: #fff; border: 1px solid #e3e9f2; color: #64748b; font-size: 11px; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; }
        .dot { width: 7px; height: 7px; border-radius: 50%; background: #22c55e; }
        h1 { margin: 20px 0 18px; max-width: 700px; font-size: clamp(40px, 5.4vw, 68px); line-height: 1.02; letter-spacing: -3px; font-weight: 850; }
        h1 span { color: #eab308; }
        .lead { max-width: 620px; margin: 0; color: #64748b; font-size: 17px; line-height: 1.75; }
        .hero-actions { display: flex; flex-wrap: wrap; gap: 11px; margin-top: 28px; }
        .trust { margin-top: 26px; color: #94a3b8; font-size: 12px; }
        .preview-wrap { position: relative; padding: 16px 10px 22px 2px; }
        .preview-wrap::before, .preview-wrap::after { content: ""; position: absolute; border-radius: 50%; pointer-events: none; }
        .preview-wrap::before { width: 150px; height: 150px; right: -28px; top: -30px; background: rgba(234,179,8,.08); filter: blur(2px); animation: ambientFloat 6s ease-in-out infinite; }
        .preview-wrap::after { width: 90px; height: 90px; left: -30px; bottom: -16px; background: rgba(15,23,42,.045); animation: ambientFloat 7s ease-in-out infinite reverse; }
        .preview-card { position: relative; z-index: 1; background: rgba(255,255,255,.96); border: 1px solid #e1e7f0; border-radius: 22px; padding: 18px; box-shadow: 0 22px 55px rgba(15,23,42,.10); transform: rotate(1deg); animation: cardFloat 5.5s ease-in-out infinite; overflow: hidden; }
        .preview-card::before { content: ""; position: absolute; inset: 0; background: linear-gradient(115deg, transparent 28%, rgba(255,255,255,.72) 45%, transparent 60%); transform: translateX(-120%); animation: cardSweep 5.5s ease-in-out infinite; pointer-events: none; }
        .preview-card::after { content: ""; position: absolute; width: 8px; height: 8px; border-radius: 50%; right: 20px; bottom: 18px; background: #fbbd00; box-shadow: 0 0 0 5px rgba(251,189,0,.10); animation: livePulse 2.4s ease-in-out infinite; }
        .preview-head { position: relative; z-index: 1; display: flex; justify-content: space-between; align-items: center; padding: 4px 3px 17px; border-bottom: 1px solid #edf1f6; }
        .preview-title { font-size: 14px; font-weight: 800; }
        .pill { font-size: 10px; font-weight: 800; color: #047857; background: #ecfdf5; padding: 6px 9px; border-radius: 999px; animation: pillPulse 2.8s ease-in-out infinite; }
        .metric-grid { position: relative; z-index: 1; display: grid; grid-template-columns: repeat(3,1fr); gap: 9px; margin-top: 14px; }
        .metric { padding: 13px; background: #f8fafc; border: 1px solid #edf1f5; border-radius: 13px; transition: transform .25s ease, box-shadow .25s ease; }
        .metric:nth-child(1) { animation: metricLift 4.2s ease-in-out infinite; }
        .metric:nth-child(2) { animation: metricLift 4.2s ease-in-out .45s infinite; }
        .metric:nth-child(3) { animation: metricLift 4.2s ease-in-out .9s infinite; }
        .metric small { color: #94a3b8; font-size: 10px; }
        .metric strong { display: block; margin-top: 6px; font-size: 21px; }
        .workflow { position: relative; z-index: 1; margin-top: 12px; padding: 14px; border: 1px solid #edf1f5; border-radius: 14px; }
        .workflow-label { display: flex; justify-content: space-between; gap: 12px; color: #64748b; font-size: 11px; margin-bottom: 11px; }
        .steps { display: grid; grid-template-columns: repeat(4,1fr); gap: 6px; }
        .step { position: relative; height: 8px; border-radius: 999px; background: #e2e8f0; overflow: hidden; }
        .step.active { background: #0f172a; }
        .step.active::after { content: ""; position: absolute; inset: 0; background: linear-gradient(90deg, transparent, rgba(255,255,255,.45), transparent); transform: translateX(-110%); animation: stepSweep 2.8s ease-in-out infinite; }
        .step:nth-child(2).active::after { animation-delay: .25s; }
        .step:nth-child(3).active::after { animation-delay: .5s; }
        @keyframes cardFloat { 0%,100% { transform: translate3d(0,0,0) rotate(1deg); } 50% { transform: translate3d(0,-9px,0) rotate(.2deg); } }
        @keyframes cardSweep { 0%,35% { transform: translateX(-120%); } 65%,100% { transform: translateX(120%); } }
        @keyframes metricLift { 0%,100% { transform: translateY(0); box-shadow: none; } 50% { transform: translateY(-3px); box-shadow: 0 7px 18px rgba(15,23,42,.055); } }
        @keyframes stepSweep { 0%,35% { transform: translateX(-110%); } 70%,100% { transform: translateX(110%); } }
        @keyframes pillPulse { 0%,100% { box-shadow: 0 0 0 0 rgba(34,197,94,0); } 50% { box-shadow: 0 0 0 5px rgba(34,197,94,.08); } }
        @keyframes livePulse { 0%,100% { transform: scale(1); opacity: .8; } 50% { transform: scale(1.35); opacity: 1; } }
        @keyframes ambientFloat { 0%,100% { transform: translate3d(0,0,0); } 50% { transform: translate3d(8px,-12px,0); } }
        @media (prefers-reduced-motion: reduce) { .preview-wrap::before,.preview-wrap::after,.preview-card,.preview-card::before,.preview-card::after,.pill,.metric,.step.active::after { animation: none; } }
        .features { max-width: 1180px; margin: 22px auto 0; padding: 20px 28px 82px; display: grid; grid-template-columns: repeat(3,1fr); gap: 16px; }
        .feature { background: #fff; border: 1px solid #e1e7f0; border-radius: 17px; padding: 23px; }
        .feature-icon { width: 38px; height: 38px; display: grid; place-items: center; border-radius: 11px; background: #fff8db; color: #a16207; font-weight: 900; font-size: 11px; }
        .feature h2 { margin: 15px 0 7px; font-size: 16px; }
        .feature p { margin: 0; color: #718096; font-size: 13px; line-height: 1.7; }
        .footer { border-top: 1px solid #e5eaf1; color: #94a3b8; font-size: 11px; }
        .footer-inner { max-width: 1180px; margin: auto; padding: 20px 28px; display: flex; justify-content: space-between; gap: 16px; }
        @media (max-width: 820px) { .nav { padding: 0 18px; } .nav .btn-secondary { display: none; } .hero { grid-template-columns: 1fr; gap: 38px; padding: 55px 18px 25px; } h1 { letter-spacing: -2px; } .preview-wrap { padding: 8px 4px 18px; } .preview-card { transform: none; animation-name: cardFloatMobile; } .features { grid-template-columns: 1fr; padding: 20px 18px 55px; } .footer-inner { padding: 18px; flex-direction: column; } }
        @keyframes cardFloatMobile { 0%,100% { transform: translateY(0); } 50% { transform: translateY(-7px); } }
    </style>
</head>