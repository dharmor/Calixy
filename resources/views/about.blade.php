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

        * {
            box-sizing: border-box;
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
            width: min(100%, 680px);
            border: 1px solid #d4e2f3;
            border-radius: 24px;
            padding: 1.5rem;
            background: #ffffff;
            box-shadow: 0 18px 32px rgba(15, 40, 74, 0.10);
            margin: 0 auto;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 1.1rem;
            justify-content: center;
        }

        .logo-fallback {
            width: 240px;
            max-width: 100%;
            height: 128px;
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
            width: 260px;
            max-width: 100%;
            height: auto;
            max-height: 160px;
            border-radius: 24px;
            object-fit: contain;
            object-position: center;
            border: 1px solid #d4e2f3;
            background: #fff;
            padding: 0.35rem;
            display: block;
            flex-shrink: 0;
        }

        .brand-copy {
            display: grid;
            gap: 0.8rem;
            min-width: 0;
            flex: 0 1 300px;
        }

        .brand-copy h1 {
            margin: 0;
            font-size: 1.15rem;
            line-height: 1.3;
        }

        .brand-copy div {
            color: #4d6981;
        }

        .meta {
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

        .page-actions {
            margin-top: 1rem;
            display: flex;
            justify-content: space-between;
            gap: 0.75rem;
            flex-wrap: wrap;
        }

        .dashboard-button {
            display: inline-block;
            border-radius: 999px;
            padding: 0.66rem 0.95rem;
            background: #0b6ea8;
            color: #fff;
            text-decoration: none;
            font-weight: 700;
        }

        .logout-row {
            display: flex;
            justify-content: flex-end;
        }

        .logout-button {
            border: 0;
            border-radius: 999px;
            padding: 0.66rem 0.95rem;
            background: #b91c1c;
            color: #fff5f5;
            font-weight: 700;
            cursor: pointer;
        }

        @media (max-width: 520px) {
            body {
                padding: 1rem;
                place-items: start center;
            }

            .about-card {
                padding: 1.1rem;
            }

            .brand {
                align-items: flex-start;
                flex-wrap: wrap;
            }

            .logo-image,
            .logo-fallback {
                width: 100%;
            }

            .brand-copy {
                flex-basis: 100%;
            }
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
            <h1>Scheduling and Availability Application</h1>
            <section class="meta">
                <div class="meta-label">Application Version</div>
                <div class="meta-value">{{ $applicationName }}</div>
                <div class="meta-subvalue">{{ $packageVersion }}</div>
            </section>
        </div>
    </div>

    @if (!empty($donationwareUrl))
        <a href="{{ $donationwareUrl }}" target="_blank" rel="noopener" class="donate">Support Donationware</a>
    @endif

    <div class="page-actions">
        <a href="{{ route('program') }}" class="dashboard-button">Dashboard</a>
        @auth
        <div class="logout-row">
            <form method="post" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="logout-button">Logout</button>
            </form>
        </div>
        @endauth
    </div>
</main>
</body>
</html>
