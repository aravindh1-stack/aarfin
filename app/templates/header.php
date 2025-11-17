<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) : 'Aarfin'; ?></title>

    <script src="https://cdn.tailwindcss.com"></script>

    <link rel="icon" type="image/svg+xml" href="<?php echo URL_ROOT; ?>/assets/image/logo/aarfin-logo.svg">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous" referrerpolicy="no-referrer" />

    <link rel="stylesheet" href="<?php echo URL_ROOT; ?>/assets/css/style.css">

    <script>
        if (window.tailwind) {
            tailwind.config = {
                theme: {
                    extend: {
                        colors: {
                            primary: '#2563eb',
                            'primary-dark': '#1d4ed8',
                            slate: {
                                50: '#f8fafc', 100: '#f1f5f9', 200: '#e2e8f0', 300: '#cbd5e1', 400: '#94a3b8',
                                500: '#64748b', 600: '#475569', 700: '#334155', 800: '#1e293b', 900: '#0f172a'
                            }
                        },
                        fontFamily: {
                            sans: ['Inter', '-apple-system', 'BlinkMacSystemFont', 'Segoe UI', 'sans-serif'],
                        },
                        letterSpacing: {
                            tighter: '-0.02em',
                            tight: '-0.01em',
                            normal: '0em',
                            wide: '0.025em',
                            wider: '0.05em',
                            widest: '0.1em',
                        },
                        boxShadow: {
                            'lg': '0 10px 25px -5px rgba(15, 23, 42, 0.08)',
                            'xl': '0 20px 25px -5px rgba(15, 23, 42, 0.1)',
                        }
                    },
                },
            };
        }
    </script>

    <style>
        * { --tw-ring-offset-shadow: 0 0 #0000; --tw-ring-shadow: 0 0 #0000; --tw-shadow: 0 0 #0000; }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
        h1, h2, h3, h4, h5, h6 { line-height: 1.2; letter-spacing: -0.02em; }
        button, a { transition: all 0.2s ease-in-out; }
        input, textarea, select { transition: all 0.2s ease-in-out; }
        .custom-scrollbar::-webkit-scrollbar { width: 6px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 3px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
    </style>
</head>
<body class="bg-slate-50 text-slate-900 antialiased">