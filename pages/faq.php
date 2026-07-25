<?php
$page_title = "FAQ - Wild Haryanvi";
include '../includes/header.php';
?>

<style>
    .faq-container {
        max-width: 900px;
        margin: 3rem auto;
        padding: 0 2rem;
    }

    .faq-header {
        text-align: center;
        margin-bottom: 3rem;
    }

    .faq-header h1 {
        font-size: 2.5rem;
        margin-bottom: 1rem;
    }

    .faq-grid {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    .faq-item {
        background: linear-gradient(135deg, var(--secondary-black) 0%, var(--light-black) 100%);
        border-radius: 10px;
        border: 2px solid var(--light-black);
        transition: var(--transition);
    }

    .faq-question {
        padding: 1.5rem;
        cursor: pointer;
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-weight: 600;
        transition: var(--transition);
    }

    .faq-item:hover .faq-question {
        color: var(--primary-red);
    }

    .faq-icon {
        font-size: 1.5rem;
        transition: var(--transition);
    }

    .faq-item.active .faq-icon {
        transform: rotate(180deg);
    }

    .faq-answer {
        max-height: 0;
        overflow: hidden;
        padding: 0 1.5rem;
        transition: var(--transition);
        border-top: 2px solid var(--light-black);
    }

    .faq-item.active .faq-answer {
        max-height: 1000px;
        padding: 1.5rem;
    }

    .faq-answer p {
        color: var(--text-gray);
        line-height: 1.8;
    }
</style>

<div class="faq-container">
    <div class="faq-header">
        <h1>Frequently Asked Questions</h1>
        <p>Find answers to common questions about Wild Haryanvi</p>
    </div>

    <div class="faq-grid">
        <div class="faq-item">
            <div class="faq-question">
                <span>What is Wild Haryanvi?</span>
                <span class="faq-icon">▼</span>
            </div>
            <div class="faq-answer">
                <p>Wild Haryanvi is a digital platform dedicated to Haryanvi entertainment. We provide authentic Haryanvi content including songs, documentaries, shorts, news, and more. Our mission is to celebrate and showcase Haryanvi culture and talent.</p>
            </div>
        </div>

        <div class="faq-item">
            <div class="faq-question">
                <span>Can I watch videos for free?</span>
                <span class="faq-icon">▼</span>
            </div>
            <div class="faq-answer">
                <p>Yes! We offer a collection of free videos with you'll need to create an account and registration required. To access our premium content you'll need purchase a subscription.</p>
            </div>
        </div>

        <div class="faq-item">
            <div class="faq-question">
                <span>How much does a subscription cost?</span>
                <span class="faq-icon">▼</span>
            </div>
            <div class="faq-answer">
                <p>We offer flexible subscription plans:<br>• Monthly: ₹99<br>• Yearly: ₹999 (Save 15%)<br>Both plans provide unlimited access to all premium content.</p>
            </div>
        </div>

        <div class="faq-item">
            <div class="faq-question">
                <span>How do I create an account?</span>
                <span class="faq-icon">▼</span>
            </div>
            <div class="faq-answer">
                <p>Click on the "Sign Up" button, fill in your name, email, and create a password. You'll receive a confirmation and can start watching immediately!</p>
            </div>
        </div>

        <div class="faq-item">
            <div class="faq-question">
                <span>Can I download videos?</span>
                <span class="faq-icon">▼</span>
            </div>
            <div class="faq-answer">
                <p>Download functionality is available for premium subscribers. You can download premium videos and watch them offline on your device.</p>
            </div>
        </div>

        <div class="faq-item">
            <div class="faq-question">
                <span>How many devices can I use?</span>
                <span class="faq-icon">▼</span>
            </div>
            <div class="faq-answer">
                <p>Premium subscribers can watch on multiple devices. Our yearly plan offers extended multi-device support for seamless streaming experience.</p>
            </div>
        </div>

        <div class="faq-item">
            <div class="faq-question">
                <span>Can I cancel my subscription anytime?</span>
                <span class="faq-icon">▼</span>
            </div>
            <div class="faq-answer">
                <p>Yes, you can cancel your subscription anytime from your profile settings. Your access will continue until the end of your billing period.</p>
            </div>
        </div>

        <div class="faq-item">
            <div class="faq-question">
                <span>Is there a mobile app?</span>
                <span class="faq-icon">▼</span>
            </div>
            <div class="faq-answer">
                <p>We're currently working on mobile apps for iOS and Android. Stay tuned for announcements on our platform!</p>
            </div>
        </div>

        <div class="faq-item">
            <div class="faq-question">
                <span>How often is new content added?</span>
                <span class="faq-icon">▼</span>
            </div>
            <div class="faq-answer">
                <p>We regularly add new content every week. Follow our Updates section to stay informed about upcoming releases and announcements.</p>
            </div>
        </div>

        <div class="faq-item">
            <div class="faq-question">
                <span>How do I report inappropriate content?</span>
                <span class="faq-icon">▼</span>
            </div>
            <div class="faq-answer">
                <p>You can report any inappropriate content by clicking the "Report" button on the video page or by contacting us through our Contact form. We take all reports seriously.</p>
            </div>
        </div>
    </div>
</div>

<script>
    document.querySelectorAll('.faq-question').forEach(question => {
        question.addEventListener('click', () => {
            const item = question.parentElement;
            item.classList.toggle('active');
        });
    });
</script>

<?php include '../includes/footer.php'; ?>
