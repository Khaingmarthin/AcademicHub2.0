<?php
/**
 * Email template for news notifications.
 *
 * Expects variables: $title, $summary, $readUrl, $uniName
 */

if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../../config/app.php';
}

$uniName = $uniName ?? (APP_NAME ?? 'Academic Hub');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($title); ?></title>
</head>
<body style="margin:0;padding:0;background-color:#f4f6f8;font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f6f8;padding:32px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="background-color:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,0.08);">

                    <!-- Header -->
                    <tr>
                        <td style="background-color:#1e40af;padding:28px 32px;text-align:center;">
                            <h1 style="margin:0;color:#ffffff;font-size:20px;font-weight:700;letter-spacing:-0.01em;">
                                <?php echo htmlspecialchars($uniName); ?>
                            </h1>
                            <p style="margin:6px 0 0;color:#93c5fd;font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:0.1em;">
                                New Announcement
                            </p>
                        </td>
                    </tr>

                    <!-- Body -->
                    <tr>
                        <td style="padding:32px;">
                            <h2 style="margin:0 0 16px;color:#111827;font-size:22px;font-weight:700;line-height:1.3;">
                                <?php echo htmlspecialchars($title); ?>
                            </h2>

                            <?php if (!empty($summary)): ?>
                                <p style="margin:0 0 20px;color:#4b5563;font-size:15px;line-height:1.7;">
                                    <?php echo nl2br(htmlspecialchars($summary)); ?>
                                </p>
                            <?php endif; ?>

                            <table role="presentation" cellpadding="0" cellspacing="0" style="margin-top:24px;">
                                <tr>
                                    <td style="background-color:#1e40af;border-radius:8px;">
                                        <a href="<?php echo htmlspecialchars($readUrl); ?>"
                                           style="display:inline-block;padding:12px 28px;color:#ffffff;font-size:14px;font-weight:600;text-decoration:none;">
                                            Read Full Article &rarr;
                                        </a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background-color:#f9fafb;border-top:1px solid #e5e7eb;padding:20px 32px;text-align:center;">
                            <p style="margin:0;color:#9ca3af;font-size:12px;line-height:1.6;">
                                You received this because you are a student at <?php echo htmlspecialchars($uniName); ?>.<br>
                                To stop receiving these emails, update your notification settings in your student profile.
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
