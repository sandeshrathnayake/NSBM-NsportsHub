<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once 'PHPMailer/Exception.php';
require_once 'PHPMailer/PHPMailer.php';
require_once 'PHPMailer/SMTP.php';

function sendStatusEmail($toEmail, $userName, $requestType, $itemName, $status, $extraDetails = '') {
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'YOUR_GMAIL@gmail.com';
        $mail->Password   = 'YOUR_16_DIGIT_APP_PASSWORD';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ];

        $mail->setFrom('YOUR_GMAIL@gmail.com', 'SportsHub Student Portal');
        $mail->addReplyTo('YOUR_GMAIL@gmail.com', 'SportsHub Support');
        $mail->addAddress($toEmail, $userName);

        $isApproved = (strtolower($status) === 'approved');
        $badgeColor = $isApproved ? '#0abf30' : '#ef4444';
        $statusText = $isApproved ? 'APPROVED' : 'REJECTED';

        $mail->isHTML(true);
        $mail->Subject = "SportsHub Update: {$requestType} Request ({$statusText})";

        $mail->Body = "
        <div style='font-family: Arial, sans-serif; max-width: 560px; margin: auto; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden;'>
            <div style='background: #0f172a; padding: 20px; text-align: center; color: #ffffff;'>
                <h2 style='margin: 0; color: #0abf30;'>SportsHub Portal</h2>
                <p style='margin: 4px 0 0; font-size: 13px; color: #cbd5e1;'>University Sports Clubs Circle</p>
            </div>
            <div style='padding: 24px; background: #ffffff; color: #1e293b;'>
                <p>Hello <strong>" . htmlspecialchars($userName) . "</strong>,</p>
                <p>Your recent <strong>" . htmlspecialchars($requestType) . "</strong> request has been reviewed by the administration.</p>
                <div style='background: #f8fafc; border-left: 4px solid {$badgeColor}; padding: 14px 16px; margin: 18px 0; border-radius: 6px;'>
                    <p style='margin: 0 0 6px;'><strong>Request:</strong> " . htmlspecialchars($itemName) . "</p>
                    " . (!empty($extraDetails) ? "<p style='margin: 0 0 6px;'><strong>Details:</strong> " . htmlspecialchars($extraDetails) . "</p>" : "") . "
                    <p style='margin: 0;'><strong>Status:</strong> <span style='color: {$badgeColor}; font-weight: bold;'>{$statusText}</span></p>
                </div>
                <p style='font-size: 13.5px; color: #475569;'>
                    " . ($isApproved 
                        ? "Congratulations! You can now log in to your SportsHub account to view your active status and schedule." 
                        : "If you have any questions regarding this decision, please contact the Sports Council or submit a new request.") . "
                </p>
            </div>
            <div style='background: #f1f5f9; padding: 14px; text-align: center; font-size: 12px; color: #64748b;'>
                &copy; " . date('Y') . " University Sports Club Circle. All Rights Reserved.
            </div>
        </div>";

        $mail->AltBody = "Hello {$userName},\n\nYour {$requestType} request for {$itemName} has been {$statusText}.\n{$extraDetails}\n\nThank you,\nSportsHub Portal";

        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}
