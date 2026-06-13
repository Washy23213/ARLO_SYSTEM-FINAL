<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Deletion | CMU OARL Alumni Portal</title>
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

        ol {
            padding-left: 1.2rem;
        }

        a {
            color: #047857;
            font-weight: 700;
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
            <h1>Data Deletion Instructions</h1>
            <p class="updated">Last updated: May 5, 2026</p>

            <p>
                CMU OARL Alumni Portal users may request deletion of their portal account and related personal data.
            </p>

            <h2>Delete From Inside the Portal</h2>
            <ol>
                <li>Sign in to your alumni account.</li>
                <li>Open Settings.</li>
                <li>Go to the account deletion section.</li>
                <li>Complete the verification and confirm deletion.</li>
            </ol>

            <h2>Request Manual Deletion</h2>
            <p>
                If you cannot access your account, contact the CMU Office of Alumni Relations and Linkages or the
                portal administrator and include the email address used for registration.
            </p>

            <h2>Facebook Data</h2>
            <p>
                The portal uses Meta APIs only for authorized Page posting and public Page post synchronization.
                To remove Facebook account data from Meta, use your Facebook account privacy and app settings.
            </p>

            <p><a href="{{ route('privacy-policy') }}">View Privacy Policy</a></p>
        </article>
    </main>
</body>
</html>
