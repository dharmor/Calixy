<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $applicationName }} About</title>
    <style>
        :root {
            color-scheme: light;
            font-family: Arial, "Helvetica Neue", sans-serif;
        }

        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 1.5rem;
            background: linear-gradient(145deg, #e7f1ff 0%, #f9fcff 100%);
            color: #11314c;
        }

        .about-card {
            width: min(100%, 640px);
            border: 1px solid #d4e2f3;
            border-radius: 24px;
            padding: 1.5rem;
            background: #ffffff;
            box-shadow: 0 18px 32px rgba(15, 40, 74, 0.10);
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 1.1rem;
        }

        .logo-fallback {
            width: 156px;
            height: 104px;
            border-radius: 24px;
            display: inline-grid;
            place-items: center;
            background: radial-gradient(circle at 30% 30%, #3aa1ff 0%, #0d5fbd 65%, #0b3f7e 100%);
            color: #fff;
            font-weight: 800;
            letter-spacing: 0.06em;
            flex-shrink: 0;
        }

        .logo-image {
            width: 156px;
            height: 104px;
            border-radius: 24px;
            object-fit: cover;
            object-position: center;
            border: 1px solid #d4e2f3;
            background: #fff;
            padding: 0;
            display: block;
            flex-shrink: 0;
        }

        .brand-copy {
            display: grid;
            gap: 0.35rem;
        }

        .brand-copy h1 {
            margin: 0;
            font-size: clamp(1.9rem, 5vw, 2.45rem);
        }

        .brand-copy div {
            color: #4d6981;
        }

        .meta {
            margin-top: 1rem;
            border: 1px solid #d4e2f3;
            border-radius: 16px;
            padding: 0.9rem 1rem;
            background: #f3f8ff;
        }

        .meta-label {
            color: #4d6981;
            font-size: 0.8rem;
            text-transform: uppercase;
            font-weight: 700;
            letter-spacing: 0.06em;
        }

        .meta-value {
            margin-top: 0.3rem;
            font-size: 1.2rem;
            font-weight: 700;
        }

        .meta-subvalue {
            margin-top: 0.25rem;
            color: #11314c;
            font-size: 0.95rem;
            font-weight: 600;
        }

        .donate {
            margin-top: 1rem;
            display: inline-block;
            border-radius: 999px;
            padding: 0.7rem 1rem;
            background: #0b6ea8;
            color: #fff;
            text-decoration: none;
            font-weight: 700;
        }
    </style>
</head>
<body>
<main class="about-card">
    <div class="brand">
        @if ($logoUrl)
            <img src="{{ $logoUrl }}" alt="{{ $applicationName }} logo" class="logo-image">
        @else
            <div class="logo-fallback" aria-hidden="true">CA</div>
        @endif
        <div class="brand-copy">
            <h1>{{ $applicationName }}</h1>
            <div>Unified scheduling and availability toolkit for Laravel.</div>
        </div>
    </div>

    <section class="meta">
        <div class="meta-label">Application Version</div>
        <div class="meta-value">{{ $applicationName }}</div>
        <div class="meta-subvalue">{{ $packageVersion }}</div>
    </section>

    <a href="{{ $donationwareUrl }}" target="_blank" rel="noopener" class="donate">Support Donationware</a>
</main>
</body>
</html>
