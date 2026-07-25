<?php
$page_title = "Premium Subscription - Wild Haryanvi";
require_once '../includes/db.php';

$success = '';
$error = '';
$active_subscription = null;

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];

    // Check active subscription
    $active_stmt = $conn->prepare("
        SELECT subscriptions.*, plans.name AS plan_name
        FROM subscriptions
        LEFT JOIN plans ON subscriptions.plan_id = plans.id
        WHERE subscriptions.user_id = ? AND subscriptions.status = 'active' AND subscriptions.expires_at > NOW()
        ORDER BY subscriptions.expires_at DESC LIMIT 1
    ");
    $active_stmt->bind_param("i", $user_id);
    $active_stmt->execute();
    $active_subscription = $active_stmt->get_result()->fetch_assoc();

    // Handle manual subscription request (pending until admin verifies payment)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['plan_id'])) {
        $plan_id = intval($_POST['plan_id']);
        $transaction_ref = trim($_POST['transaction_ref'] ?? '');

        $plan_stmt = $conn->prepare("SELECT * FROM plans WHERE id = ? AND is_active = 1");
        $plan_stmt->bind_param("i", $plan_id);
        $plan_stmt->execute();
        $plan = $plan_stmt->get_result()->fetch_assoc();

        if (!$plan) {
            $error = 'Invalid plan selected!';
        } elseif (empty($transaction_ref)) {
            $error = 'Please enter your payment transaction ID / UTR number.';
        } else {
            $screenshot_name = null;

            // Handle screenshot upload (optional but recommended)
            if (isset($_FILES['screenshot']) && $_FILES['screenshot']['error'] === 0) {
                $allowed = ['jpg', 'jpeg', 'png', 'webp'];
                $ext = strtolower(pathinfo($_FILES['screenshot']['name'], PATHINFO_EXTENSION));
                if (in_array($ext, $allowed)) {
                    $screenshot_name = 'pay_' . $user_id . '_' . time() . '.' . $ext;
                    $upload_path = '../uploads/payment_proofs/' . $screenshot_name;
                    move_uploaded_file($_FILES['screenshot']['tmp_name'], $upload_path);
                }
            }

            $insert_stmt = $conn->prepare("
                INSERT INTO subscriptions (user_id, plan_id, razorpay_order_id, razorpay_payment_id, payment_screenshot, transaction_ref, amount, status, starts_at, expires_at, created_at)
                VALUES (?, ?, NULL, NULL, ?, ?, ?, 'pending', NULL, NULL, NOW())
            ");
            $insert_stmt->bind_param("iissd", $user_id, $plan_id, $screenshot_name, $transaction_ref, $plan['price']);

            if ($insert_stmt->execute()) {
                $success = 'Your request has been submitted! We will verify your payment and activate your plan within a few hours.';
            } else {
                $error = 'Something went wrong. Please try again.';
            }
        }
    }

    // Check if user already has a pending request
    $pending_stmt = $conn->prepare("SELECT subscriptions.*, plans.name AS plan_name FROM subscriptions LEFT JOIN plans ON subscriptions.plan_id = plans.id WHERE subscriptions.user_id = ? AND subscriptions.status = 'pending' ORDER BY subscriptions.created_at DESC LIMIT 1");
    $pending_stmt->bind_param("i", $user_id);
    $pending_stmt->execute();
    $pending_request = $pending_stmt->get_result()->fetch_assoc();
}

// Fetch plans dynamically from DB
$plans = $conn->query("SELECT * FROM plans WHERE is_active = 1 ORDER BY price ASC");

include '../includes/header.php';
?>

<style>
    .premium-container {
        max-width: 1200px;
        margin: 4rem auto;
        padding: 0 2rem;
    }

    .premium-hero {
        text-align: center;
        margin-bottom: 3rem;
    }

    .premium-hero h1 {
        font-size: 2.5rem;
        margin-bottom: 1rem;
        background: linear-gradient(135deg, var(--primary-red) 0%, #ff6666 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    .premium-hero p {
        font-size: 1.1rem;
        color: var(--text-gray);
    }

    .alert {
        max-width: 600px;
        margin: 0 auto 2rem;
        padding: 1rem;
        border-radius: 10px;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .alert-success {
        background: rgba(76, 175, 80, 0.2);
        color: #309c30;
        border: 1px solid rgba(76, 175, 80, 0.5);
    }

    .alert-error {
        background: rgba(255, 68, 68, 0.2);
        color: #dc4c4c;
        border: 1px solid rgba(255, 68, 68, 0.5);
    }

    .current-plan-banner {
        max-width: 600px;
        margin: 0 auto 2rem;
        background: linear-gradient(135deg, #e1c526 0%, #FFA500 100%);
        color: #1a1a1a;
        padding: 1.3rem 2rem;
        border-radius: 15px;
        text-align: center;
    }

    .current-plan-banner h3 {
        font-size: 1.2rem;
        margin-bottom: 0.3rem;
    }

    .pricing-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 2rem;
        margin-top: 3rem;
    }

    .pricing-card {
        background: linear-gradient(135deg, var(--secondary-black) 0%, var(--light-black) 100%);
        padding: 2rem;
        border-radius: 15px;
        border: 2px solid var(--light-black);
        transition: var(--transition);
        text-align: center;
        position: relative;
    }

    .pricing-card:hover {
        transform: translateY(-10px);
        border-color: var(--primary-red);
        box-shadow: 0 20px 50px rgba(255, 68, 68, 0.3);
    }

    .pricing-card.popular {
        border-color: var(--primary-red);
        box-shadow: 0 10px 40px rgba(255, 68, 68, 0.2);
    }

    .pricing-card.popular::before {
        content: 'POPULAR';
        position: absolute;
        top: -12px;
        left: 50%;
        transform: translateX(-50%);
        background: var(--primary-red);
        color: var(--white);
        padding: 0.5rem 1rem;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 700;
        letter-spacing: 1px;
    }

    .plan-name {
        font-size: 1.5rem;
        font-weight: 700;
        margin-bottom: 1rem;
    }

    .plan-price {
        font-size: 2.5rem;
        font-weight: 700;
        color: var(--primary-red);
        margin-bottom: 0.5rem;
    }

    .plan-price span {
        font-size: 1rem;
        color: var(--text-gray);
    }

    .plan-period {
        color: var(--text-gray);
        margin-bottom: 1.5rem;
        font-size: 0.9rem;
    }

    .plan-features {
        list-style: none;
        margin: 2rem 0;
        text-align: left;
    }

    .plan-features li {
        padding: 0.8rem 0;
        border-bottom: 1px solid var(--light-black);
        color: var(--text-gray);
    }

    .plan-features li:before {
        content: '✓ ';
        color: var(--primary-red);
        font-weight: 700;
        margin-right: 0.5rem;
    }

    .plan-features li:last-child {
        border-bottom: none;
    }

    .plan-btn {
        width: 100%;
        padding: 1rem;
        background: linear-gradient(135deg, var(--primary-red) 0%, #ff6666 100%);
        color: var(--white);
        border: none;
        border-radius: 10px;
        font-weight: 600;
        cursor: pointer;
        transition: var(--transition);
        margin-top: 1.5rem;
        text-decoration: none;
        display: block;
        text-align: center;
    }

    .plan-btn:hover {
        box-shadow: 0 10px 30px rgba(255, 68, 68, 0.4);
        transform: translateY(-3px);
    }

    .benefits-section {
        background: linear-gradient(135deg, var(--secondary-black) 0%, var(--light-black) 100%);
        padding: 3rem;
        border-radius: 15px;
        margin-top: 4rem;
        border: 2px solid var(--light-black);
    }

    .benefits-section h2 {
        text-align: center;
        margin-bottom: 2rem;
        font-size: 2rem;
    }

    .benefits-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 2rem;
    }

    .benefit-item {
        text-align: center;
    }

    .benefit-icon {
        font-size: 2.5rem;
        margin-bottom: 1rem;
    }

    .benefit-title {
        font-weight: 600;
        margin-bottom: 0.5rem;
    }

    .benefit-text {
        color: var(--text-gray);
        font-size: 0.9rem;
    }

    .payment-modal-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.75);
        z-index: 2000;
        align-items: center;
        justify-content: center;
        padding: 1rem;
    }

    .payment-modal-overlay.show {
        display: flex;
    }

    .payment-modal {
        background: var(--secondary-black);
        border: 2px solid var(--primary-red);
        border-radius: 15px;
        padding: 2rem 2.5rem;
        max-width: 700px;
        width: 100%;
        position: relative;
        max-height: 90vh;
        overflow-y: auto;
        grid-template-columns: 1fr 1fr;
        gap: 1.5rem 2rem;
        align-items: start;
    }

    .payment-modal-close {
        position: absolute;
        top: 12px;
        right: 15px;
        background: none;
        border: none;
        color: var(--text-gray);
        font-size: 1.5rem;
        cursor: pointer;
    }

    .payment-modal h3 {
        margin-bottom: 0.5rem;
    }

    .payment-modal-sub {
        color: var(--text-gray);
        font-size: 0.9rem;
        margin-bottom: 1.2rem;
    }

    .upi-box {
        background: var(--light-black);
        padding: 1rem;
        border-radius: 10px;
        margin-bottom: 1.5rem;
        text-align: center;
    }

    .upi-id {
        font-size: 1.2rem;
        font-weight: 700;
        color: var(--primary-red);
        margin: 0.5rem 0;
    }

    .upi-note {
        font-size: 0.8rem;
        color: var(--text-gray);
    }

    .upi-copy-box{
        display:flex;
        justify-content:center;
        align-items:center;
        gap:10px;
        margin:15px 0;
    }

    .copy-btn{
        background:#ff4d4d;
        color:#fff;
        border:none;
        padding:8px 14px;
        border-radius:6px;
        cursor:pointer;
        font-size:14px;
        font-weight:600;
    }

    .copy-btn:hover{
        background:#e53935;
    }

    .qr-box{
        text-align:center;
        margin:15px 0;
    }

    .qr-box img{
        width:170px;
        height:170px;
        border-radius:10px;
        background:#fff;
        padding:8px;
        object-fit:contain;
        display: block;
        margin: auto;
    }

    .form-note{
        display:block;
        margin-top:6px;
        color:#777;
        font-size:13px;
    }

    .verify-time{
        text-align:center;
        margin:18px 0;
        color:#555;
        font-size:14px;
    }

    .security-note{
        text-align:center;
        color:#777;
        margin-top:18px;
        font-size:13px;
    }

    .upi-buttons{
        margin-top:15px;
    }

    .upi-pay-btn{
        display:block;
        width:100%;
        padding:14px;
        background:#00a651;
        color:#fff;
        text-decoration:none;
        border-radius:8px;
        font-weight:600;
        transition:.3s;
    }

    .upi-pay-btn:hover{
        background:#008d45;
    }

    .payment-modal h3,
    .payment-modal-sub,
    .payment-modal-close {
        grid-column: 1 / -1;
    }

    .payment-modal form {
        display: contents;
    }

    .payment-modal form .form-group,
    .payment-modal form .verify-time,
    .payment-modal form button[type="submit"],
    .payment-modal form .security-note {
        grid-column: 1 / -1;
    }

    @media (max-width: 640px) {
        .payment-modal {
            grid-template-columns: 1fr;
            max-width: 420px;
            padding: 1.8rem;
        }
    }

</style>

<div class="premium-container">
    <div class="premium-hero">
        <h1>Unlock Premium Content</h1>
        <p>Get unlimited access to all our exclusive Haryanvi content</p>
    </div>

    <?php if (!empty($success)): ?>
        <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <?php if ($active_subscription): ?>
        <div class="current-plan-banner">
            <h3><i class="fas fa-crown"></i> You're on the <?php echo htmlspecialchars($active_subscription['plan_name']); ?> Plan</h3>
            <p>Valid until <?php echo date('d M Y', strtotime($active_subscription['expires_at'])); ?></p>
        </div>
    <?php endif; ?>
    <?php if (!empty($pending_request)): ?>
        <div class="current-plan-banner" style="background: linear-gradient(135deg, #ffb84d 0%, #ff9800 100%);">
            <h3><i class="fas fa-hourglass-half"></i> Your <?php echo htmlspecialchars($pending_request['plan_name']); ?> request is under review</h3>
            <p>We'll activate it once your payment is verified (usually within a few hours).</p>
        </div>
    <?php endif; ?>

    <div class="pricing-grid">
        <?php
        if ($plans && $plans->num_rows > 0) {
            while ($plan = $plans->fetch_assoc()) {
                $is_popular = ($plan['name'] === 'Yearly');
                $per_period = $plan['duration_days'] >= 300 ? '/year' : '/month';
                ?>
                <div class="pricing-card <?php echo $is_popular ? 'popular' : ''; ?>">
                    <div class="plan-name"><?php echo htmlspecialchars($plan['name']); ?></div>
                    <div class="plan-price">₹<?php echo number_format($plan['price'], 0); ?><span><?php echo $per_period; ?></span></div>
                    <div class="plan-period"><?php echo $plan['duration_days']; ?> days access</div>

                    <ul class="plan-features">
                        <li>Unlimited premium videos</li>
                        <li>Ad-free experience</li>
                        <li>Watch on any device</li>
                        <li>Early access to new releases</li>
                        <?php if ($is_popular): ?>
                            <li>Priority support</li>
                        <?php endif; ?>
                    </ul>

                    <?php if (isset($_SESSION['user_id'])): ?>
                        <button type="button" class="plan-btn" onclick="openPaymentModal(<?php echo $plan['id']; ?>, '<?php echo htmlspecialchars($plan['name']); ?>', '<?php echo number_format($plan['price'], 0); ?>')">
                            <?php echo $active_subscription ? 'Subscribe Now' : 'Subscribe Now'; ?>
                        </button>
                    <?php else: ?>
                        <a href="login.php" class="plan-btn">Login to Subscribe</a>
                    <?php endif; ?>
                </div>
                <?php
            }
        } else {
            echo '<p style="text-align:center; grid-column:1/-1; color: var(--text-gray);">No plans available right now.</p>';
        }
        ?>
    </div>

    <div class="benefits-section">
        <h2>Premium Benefits</h2>
        <div class="benefits-grid">
            <div class="benefit-item">
                <div class="benefit-icon">🎬</div>
                <div class="benefit-title">Exclusive Content</div>
                <div class="benefit-text">Watch premium videos unavailable elsewhere</div>
            </div>
            <div class="benefit-item">
                <div class="benefit-icon">📱</div>
                <div class="benefit-title">Multi-Device</div>
                <div class="benefit-text">Watch on phone, tablet, and desktop</div>
            </div>
            <div class="benefit-item">
                <div class="benefit-icon">✨</div>
                <div class="benefit-title">Ad-Free</div>
                <div class="benefit-text">Enjoy content without interruptions</div>
            </div>
            <div class="benefit-item">
                <div class="benefit-icon">🔔</div>
                <div class="benefit-title">Priority</div>
                <div class="benefit-text">Get priority support from our team</div>
            </div>
        </div>
    </div>
</div>


<!-- Payment Modal -->
<div class="payment-modal-overlay" id="paymentModalOverlay">
    <div class="payment-modal">
        <button type="button" class="payment-modal-close" onclick="closePaymentModal()">&times;</button>
        <h3>Complete Your Payment</h3>
        <p class="payment-modal-sub">You're subscribing to <strong id="modalPlanName"></strong> — <span id="modalPlanPrice"></span></p>

        <div class="upi-box">
            <p><strong>Pay via UPI:</strong></p>
            <div class="upi-copy-box">
                <div class="upi-id" id="upiId">9996034453@ybl</div>
                <button type="button" class="copy-btn" onclick="copyUPI()">
                    📋 Copy
                </button>
            </div>

            <!-- QR Code -->
            <div class="qr-box">
                <img src="../assets/images/qr-code.jpeg" alt="UPI QR Code">
            </div>

            <div class="upi-buttons">
                <a id="upiPayBtn"
                href="#"
                class="upi-pay-btn">
                    📱 Pay with UPI Apps
                </a>
            </div>

            <p class="upi-note">
                Tap the button above to open PhonePe, Google Pay, Paytm or any UPI app.
            </p>
        </div>

        <form method="POST" action="" enctype="multipart/form-data">
            <input type="hidden" name="plan_id" id="modalPlanId">

            <div class="form-group">
                <label>Transaction ID / UTR Number *</label>
                <input 
                    type="text" 
                    name="transaction_ref" 
                    placeholder="Enter UTR Number (e.g. 402812345678)" 
                    required>
            </div>

            <div class="form-group">
                <label>Payment Screenshot (optional)</label>
                <input type="file" name="screenshot" accept="image/*">
                
            </div>
            
            <p class="verify-time">
                ⏱ Verification usually takes <strong>5–30 minutes.</strong>
            </p>

            <button type="submit" class="plan-btn">Verify Payment</button>
            
            <p class="security-note">
                🔒 Your payment information is used only for verification.
            </p>

        </form>
    </div>
</div>


<?php include '../includes/footer.php'; ?>

<script>
    function openPaymentModal(planId, planName, planPrice)
    {
        document.getElementById('modalPlanId').value = planId;
        document.getElementById('modalPlanName').innerHTML = planName;
        document.getElementById('modalPlanPrice').innerHTML = "₹" + planPrice;

        var upiLink =
            "upi://pay?pa=9996034453@ybl" +
            "&pn=Harshit%20Nain" +
            "&am=" + planPrice +
            "&cu=INR" +
            "&tn=Wild%20Haryanvi%20Subscription";

        document
            .getElementById("upiPayBtn")
            .href = upiLink;

        document
            .getElementById("paymentModalOverlay")
            .classList.add("show");
    }

    function closePaymentModal() {
        document.getElementById('paymentModalOverlay').classList.remove('show');
    }

    function copyUPI() {
        const upiId = document.getElementById('upiId').textContent;
        navigator.clipboard.writeText(upiId).then(() => {
            alert('UPI ID copied: ' + upiId);
        }).catch(() => {
            alert('Could not copy. UPI ID: ' + upiId);
        });
    }

</script>
