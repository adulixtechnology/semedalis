<?php
/**
 * Donate — public submission form for a donation screenshot.
 *
 * Uses the single-owner validate_upload() (design fix #3): the helper validates
 * and moves the file, returning the bare filename; THIS page owns the single
 * PDO INSERT and unlink()s the moved file on DB failure (no orphan).
 *
 * Donations are never listed publicly — this page only accepts submissions.
 */
require __DIR__ . '/../config/config.php';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    // --- Validate text fields (bounds aligned to the live donations columns) --
    $donor_name = trim($_POST['donor_name'] ?? '');
    set_old('donor_name', $donor_name);
    if ($donor_name === '') {
        $errors['donor_name'] = t('donate.err_name_required');
    } elseif (mb_strlen($donor_name) > 150) {
        $errors['donor_name'] = t('donate.err_name_long');
    }

    $email = trim($_POST['email'] ?? '');
    set_old('email', $email);
    if ($email !== '') {
        if (mb_strlen($email) > 150) {
            $errors['email'] = t('donate.err_email_long');
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = t('donate.err_email_invalid');
        }
    }

    $phone = trim($_POST['phone'] ?? '');
    set_old('phone', $phone);
    if ($phone !== '' && mb_strlen($phone) > 50) {
        $errors['phone'] = t('donate.err_phone_long');
    }

    $amount = trim($_POST['amount'] ?? '');
    set_old('amount', $amount);
    if ($amount !== '') {
        // Decimal >= 0, <= 2 decimal places, <= 9999999999.99 (decimal(12,2)).
        if (!preg_match('/^\d{1,10}(\.\d{1,2})?$/', $amount)) {
            $errors['amount'] = t('donate.err_amount_invalid');
        }
    }

    $bank_reference = trim($_POST['bank_reference'] ?? '');
    set_old('bank_reference', $bank_reference);
    if ($bank_reference !== '' && mb_strlen($bank_reference) > 255) {
        $errors['bank_reference'] = t('donate.err_ref_long');
    }

    $message = trim($_POST['message'] ?? '');
    set_old('message', $message);
    if ($message !== '' && mb_strlen($message) > 65000) {
        $errors['message'] = t('donate.err_msg_long');
    }

    // --- Validate + move the screenshot (only once text fields are clean) ----
    $screenshot = null;
    if (empty($errors)) {
        $upload = validate_upload('screenshot', 'donations');
        if ($upload === '') {
            // No file was uploaded — required field.
            $errors['screenshot'] = t('donate.err_screenshot_required');
        } elseif (ctype_digit($upload[0] ?? '')) {
            // SUCCESS: a bare filename (starts with a digit); file already moved.
            $screenshot = $upload;
        } else {
            // FAILURE: an error message from the helper (nothing was moved).
            $errors['screenshot'] = $upload;
        }
    }

    // --- Single-owner DB write (INSERT + unlink on failure) ------------------
    if (empty($errors) && $screenshot !== null) {
        try {
            $stmt = db()->prepare(
                "INSERT INTO donations (donor_name, email, phone, amount, bank_reference, screenshot, message, status) "
                . "VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')"
            );
            $stmt->execute([
                $donor_name,
                $email !== '' ? $email : null,
                $phone !== '' ? $phone : null,
                $amount !== '' ? $amount : null,
                $bank_reference !== '' ? $bank_reference : null,
                $screenshot,
                $message !== '' ? $message : null,
            ]);
            flash('donate', 'Thank you! Your donation has been received and is pending verification.');
            header('Location: ' . url('pages/donate.php'));
            exit;
        } catch (PDOException $e) {
            // DB failure: remove the moved file so no orphan remains (fix #3).
            @unlink(dirname(__DIR__) . '/backend/uploads/donations/' . $screenshot);
            error_log('Donation insert failed: ' . $e->getMessage());
            $errors['form'] = t('donate.err_form_generic');
        }
    }
}

$pageTitle = t('nav.donations');
require __DIR__ . '/../includes/header.php';
$success = flash('donate');
?>

<!-- Page Hero -->
<div class="donate-hero">
    <div class="container">
        <p class="donate-hero__label"><?= t('donate.give_today') ?></p>
        <h1 class="donate-hero__title"><?= t('donate.submit_title') ?></h1>
        <p class="donate-hero__sub"><?= t('donate.submit_sub') ?></p>
    </div>
</div>

<div class="container">
<section class="section">
<div class="donate-form-layout">

    <!-- Sidebar -->
    <aside class="donate-form-sidebar">
        <div class="donate-form-sidebar__block">
            <h2><?= t('donate.why_give') ?></h2>
            <p><?= t('donate.why_body') ?></p>
        </div>

        <div class="donate-form-sidebar__steps">
            <div class="donate-form-sidebar__step">
                <div class="donate-form-sidebar__num">1</div>
                <div>
                    <strong><?= t('donate.step1_title') ?></strong>
                    <span><?= t('donate.step1_body') ?></span>
                </div>
            </div>
            <div class="donate-form-sidebar__step">
                <div class="donate-form-sidebar__num">2</div>
                <div>
                    <strong><?= t('donate.step2_title') ?></strong>
                    <span><?= t('donate.step2_body') ?></span>
                </div>
            </div>
            <div class="donate-form-sidebar__step">
                <div class="donate-form-sidebar__num">3</div>
                <div>
                    <strong><?= t('donate.step3_title') ?></strong>
                    <span><?= t('donate.step3_body') ?></span>
                </div>
            </div>
        </div>

        <blockquote class="donate-form-sidebar__verse">
            <p><?= t('donate.verse_body') ?></p>
            <cite><?= t('donate.verse_cite') ?></cite>
        </blockquote>

        <a class="donate-form-sidebar__link" href="<?= e(url('pages/donations.php')) ?>"><?= t('donate.view_bank') ?></a>
    </aside>

    <!-- Form -->
    <div class="donate-form-main">

        <?php if ($success): ?>
        <div class="alert alert--success"><?= e($success) ?></div>
        <?php endif; ?>

        <?php if (!empty($errors['form'])): ?>
        <div class="alert alert--danger"><?= e($errors['form']) ?></div>
        <?php endif; ?>

        <form class="donate-form" action="<?= e(url('pages/donate.php')) ?>" method="post" enctype="multipart/form-data" novalidate>
            <?= csrf_field() ?>

            <div class="donate-form__section-label"><?= t('donate.your_details') ?></div>

            <div class="donate-form__row donate-form__row--2">
                <div class="donate-form__field">
                    <label for="donor_name"><?= t('donate.full_name') ?> <span class="donate-form__req"><?= t('donate.required_field') ?></span></label>
                    <input type="text" id="donor_name" name="donor_name" value="<?= e(old('donor_name')) ?>" maxlength="150" placeholder="e.g. Maria Santos" required>
                    <?php if (!empty($errors['donor_name'])): ?><div class="donate-form__error"><?= e($errors['donor_name']) ?></div><?php endif; ?>
                </div>
                <div class="donate-form__field">
                    <label for="email"><?= t('donate.email') ?> <span class="donate-form__opt"><?= t('donate.optional') ?></span></label>
                    <input type="email" id="email" name="email" value="<?= e(old('email')) ?>" maxlength="150" placeholder="you@example.com">
                    <?php if (!empty($errors['email'])): ?><div class="donate-form__error"><?= e($errors['email']) ?></div><?php endif; ?>
                </div>
            </div>

            <div class="donate-form__row donate-form__row--2">
                <div class="donate-form__field">
                    <label for="phone"><?= t('donate.phone') ?> <span class="donate-form__opt"><?= t('donate.optional') ?></span></label>
                    <input type="tel" id="phone" name="phone" value="<?= e(old('phone')) ?>" maxlength="50" placeholder="+63 912 345 6789">
                    <?php if (!empty($errors['phone'])): ?><div class="donate-form__error"><?= e($errors['phone']) ?></div><?php endif; ?>
                </div>
                <div class="donate-form__field">
                    <label for="amount"><?= t('donate.amount') ?> <span class="donate-form__opt"><?= t('donate.optional') ?></span></label>
                    <div class="donate-form__input-prefix">
                        <span>PHP</span>
                        <input type="text" id="amount" name="amount" value="<?= e(old('amount')) ?>" inputmode="decimal" placeholder="0.00">
                    </div>
                    <?php if (!empty($errors['amount'])): ?><div class="donate-form__error"><?= e($errors['amount']) ?></div><?php endif; ?>
                </div>
            </div>

            <div class="donate-form__divider"></div>
            <div class="donate-form__section-label"><?= t('donate.payment_details') ?></div>

            <div class="donate-form__field">
                <label for="bank_reference"><?= t('donate.bank_ref') ?> <span class="donate-form__opt"><?= t('donate.optional') ?></span></label>
                <input type="text" id="bank_reference" name="bank_reference" value="<?= e(old('bank_reference')) ?>" maxlength="255" placeholder="Reference number from your transfer">
                <?php if (!empty($errors['bank_reference'])): ?><div class="donate-form__error"><?= e($errors['bank_reference']) ?></div><?php endif; ?>
            </div>

            <div class="donate-form__field">
                <label for="screenshot"><?= t('donate.payment_screenshot') ?> <span class="donate-form__req"><?= t('donate.required_field') ?></span></label>
                <label for="screenshot" class="donate-form__upload" id="upload-label">
                    <div class="donate-form__upload-inner">
                        <div class="donate-form__upload-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                        </div>
                        <p class="donate-form__upload-text"><?= t('donate.upload_text') ?></p>
                        <p class="donate-form__upload-hint"><?= t('donate.upload_hint') ?></p>
                        <p class="donate-form__upload-name" id="upload-name"></p>
                    </div>
                    <input type="file" id="screenshot" name="screenshot" accept="image/jpeg,image/png,image/gif,image/webp" required class="donate-form__upload-input">
                </label>
                <?php if (!empty($errors['screenshot'])): ?><div class="donate-form__error"><?= e($errors['screenshot']) ?></div><?php endif; ?>
            </div>

            <div class="donate-form__divider"></div>
            <div class="donate-form__section-label"><?= t('donate.message') ?> <span class="donate-form__opt"><?= t('donate.optional') ?></span></div>

            <div class="donate-form__field">
                <textarea id="message" name="message" rows="4" placeholder="Leave a note or dedication with your donation..."><?= e(old('message')) ?></textarea>
                <?php if (!empty($errors['message'])): ?><div class="donate-form__error"><?= e($errors['message']) ?></div><?php endif; ?>
            </div>

            <div class="donate-form__actions">
                <button type="submit" class="btn btn--primary donate-form__submit"><?= t('donate.submit_btn') ?></button>
                <a class="btn btn--ghost" href="<?= e(url('pages/donations.php')) ?>"><?= t('donate.cancel') ?></a>
            </div>

        </form>
    </div>

</div>
</section>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>

<script>
(function () {
    var input = document.getElementById('screenshot');
    var nameEl = document.getElementById('upload-name');
    var label  = document.getElementById('upload-label');
    if (!input || !nameEl) return;
    input.addEventListener('change', function () {
        if (input.files && input.files[0]) {
            nameEl.textContent = input.files[0].name;
            label.classList.add('donate-form__upload--selected');
        } else {
            nameEl.textContent = '';
            label.classList.remove('donate-form__upload--selected');
        }
    });
    var drop = label;
    drop.addEventListener('dragover', function (e) { e.preventDefault(); label.classList.add('donate-form__upload--drag'); });
    drop.addEventListener('dragleave', function () { label.classList.remove('donate-form__upload--drag'); });
    drop.addEventListener('drop', function (e) {
        e.preventDefault();
        label.classList.remove('donate-form__upload--drag');
        if (e.dataTransfer.files[0]) {
            input.files = e.dataTransfer.files;
            nameEl.textContent = e.dataTransfer.files[0].name;
            label.classList.add('donate-form__upload--selected');
        }
    });
}());
</script>
