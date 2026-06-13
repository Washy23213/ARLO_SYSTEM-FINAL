<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Privacy Policy | CMU OARL Alumni Portal</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            margin: 0;
            background: #f5f7f3;
            color: #0f172a;
            font-family: "Plus Jakarta Sans", system-ui, sans-serif;
            line-height: 1.7;
        }

        main {
            max-width: 880px;
            margin: 0 auto;
            padding: 48px 20px;
        }

        article {
            background: #ffffff;
            border: 1px solid #dbe7dc;
            border-radius: 18px;
            padding: 34px;
            box-shadow: 0 18px 45px rgba(15, 23, 42, 0.08);
        }

        h1, h2 {
            color: #064e3b;
            line-height: 1.25;
        }

        h1 {
            margin-top: 0;
            font-size: 2rem;
        }

        h2 {
            margin-top: 2rem;
            font-size: 1.15rem;
        }

        a {
            color: #047857;
            font-weight: 700;
        }

        ul {
            padding-left: 1.2rem;
        }

        .updated {
            color: #475569;
            font-size: 0.95rem;
        }
    </style>
</head>
<body>
    <main>
        <article>
            <h1>Privacy Policy</h1>
            <p class="updated">Last updated: May 5, 2026</p>

            <p>
                The CMU OARL Alumni Portal is operated for alumni registration, graduate tracer updates,
                announcements, job opportunities, feedback, and official alumni office communication.
            </p>

            <h2>Information We Collect</h2>
            <p>We may collect account, alumni, education, employment, tracer, feedback, and contact information submitted through the portal.</p>

            <h2>How We Use Information</h2>
            <ul>
                <li>To verify alumni registration and account access.</li>
                <li>To provide announcements, job opportunities, notifications, and alumni services.</li>
                <li>To support graduate tracer reporting and institutional analytics.</li>
                <li>To send registration, password reset, and account-related emails.</li>
            </ul>

            <h2>Facebook Page Integration</h2>
            <p>
                The portal may synchronize public posts from the official CMU OARL Facebook Page and may publish
                authorized announcements to that Page using Meta platform APIs. Facebook access tokens are used only
                for authorized Page management features.
            </p>

            <h2>Email</h2>
            <p>
                The portal uses email services for verification codes, password reset links, and notifications.
                Users should keep their email accounts secure.
            </p>

            <h2>Data Sharing</h2>
            <p>
                We do not sell personal information. Data may be accessed only by authorized portal administrators
                for legitimate alumni office and institutional purposes.
            </p>

            <h2>Data Deletion</h2>
            <p>
                Users may request account or data deletion by visiting the
                <a href="{{ route('data-deletion') }}">Data Deletion Instructions</a>.
            </p>

            <h2>Contact</h2>
            <p>
                For privacy concerns, contact the CMU Office of Alumni Relations and Linkages or the portal administrator.
            </p>
        </article>
    </main>
</body>
</html>
