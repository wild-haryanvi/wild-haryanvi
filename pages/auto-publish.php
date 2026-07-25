<?php
require_once '../includes/db.php';

// Simple secret key check — prevents random people from triggering this manually
$secret_key = 'wild_haryanvi_2026'; // change this to your own secret

if (!isset($_GET['key']) || $_GET['key'] !== $secret_key) {
    http_response_code(403);
    die('Access denied');
}

$stmt = $conn->prepare("UPDATE videos SET status = 'published' WHERE status = 'upcoming' AND release_date <= NOW()");
$stmt->execute();
$affected = $stmt->affected_rows;

echo "Checked at " . date('Y-m-d H:i:s') . " — Published: " . $affected . " video(s).";

?>





<!-- Step : Live Hosting Pe Cron Job Set Karna (Jab Site Live Ho)

Zyadatar shared hosting (Hostinger, GoDaddy, Bluehost waghera) mein cPanel hota hai jisme "Cron Jobs" naam ka section milता hai:

cPanel mein login karo → Cron Jobs dhoondo
"Add New Cron Job" pe click karo
Timing set karo — jaise "Every 5 minutes" (*/5 * * * *)
Command mein ye daalo:
   curl "https://aapkidomain.com/pages/auto-publish.php?key=wildharyanvi_cron_2026"

(Apna asli domain aur secret key daalna)
5. Save karo

Bas itna hi — ab har 5 minute mein server khud check karегा ki koi upcoming video ka time aa gaya hai, aur usse automatically publish kar देगा. Aapka phone/laptop on ho ya off, koi farak nahi padеgा, kyunki ye hosting server pe chal raha hai, aapke device pe nahi. -->