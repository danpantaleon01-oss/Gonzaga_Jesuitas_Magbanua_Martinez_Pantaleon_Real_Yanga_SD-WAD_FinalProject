<?php
require_once 'includes/functions.php';

logActivity('logout', 'User logged out');
session_destroy();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logging Out...</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
            background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
        }

        .logout-container {
            text-align: center;
            color: white;
        }

        .logout-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 24px;
            background: rgba(255, 255, 255, 0.15);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            animation: iconPop 0.5s ease;
        }

        .logout-icon svg {
            width: 40px;
            height: 40px;
            stroke: white;
            stroke-width: 2.5;
            fill: none;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .logout-icon svg path,
        .logout-icon svg line {
            stroke-dasharray: 60;
            stroke-dashoffset: 60;
            animation: drawOut 0.6s ease 0.3s forwards;
        }

        .logout-text {
            font-size: 1.5rem;
            font-weight: 700;
            opacity: 0;
            animation: fadeUp 0.4s ease 0.5s forwards;
        }

        .logout-subtext {
            font-size: 0.95rem;
            opacity: 0;
            margin-top: 8px;
            animation: fadeUp 0.4s ease 0.7s forwards;
        }

        @keyframes iconPop {
            0% { transform: scale(0); opacity: 0; }
            60% { transform: scale(1.1); }
            100% { transform: scale(1); opacity: 1; }
        }

        @keyframes drawOut {
            to { stroke-dashoffset: 0; }
        }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 0.9; transform: translateY(0); }
        }

        .fade-out {
            opacity: 0;
            transform: scale(0.95);
            transition: all 0.4s ease;
        }
    </style>
</head>
<body>
    <div class="logout-container" id="logoutContainer">
        <div class="logout-icon">
            <svg viewBox="0 0 24 24">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                <polyline points="16 17 21 12 16 7"/>
                <line x1="21" y1="12" x2="9" y2="12"/>
            </svg>
        </div>
        <div class="logout-text">Signing out...</div>
        <div class="logout-subtext">See you soon!</div>
    </div>

    <script>
        setTimeout(function() {
            const container = document.getElementById('logoutContainer');
            container.classList.add('fade-out');
        }, 1800);

        setTimeout(function() {
            window.location.href = '<?= BASE_URL ?>/login.php';
        }, 2200);
    </script>
</body>
</html>
