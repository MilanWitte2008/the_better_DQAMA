<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#0b2942">
    <title>Inloggen | DQAMA</title>
    <?php echo app('Illuminate\Foundation\Vite')('resources/css/app.css'); ?>
</head>
<body class="login-body">
    <main class="login-shell">
        <section class="login-intro" aria-labelledby="welcome-title">
            <a class="login-brand" href="/" aria-label="DQAMA startpagina">
                <span class="login-brand-mark" aria-hidden="true">D</span>
                <span>
                    <strong>DQAMA</strong>
                    <small>Ontwikkeling in beeld</small>
                </span>
            </a>

            <div class="intro-copy">
                <p class="intro-eyebrow">Jouw ontwikkeling, helder in beeld</p>
                <h1 id="welcome-title">Ruimte om te groeien.</h1>
                <p>Een overzichtelijke plek voor reflectie, leerdoelen en gesprekken. Log in en ga verder met jouw ontwikkeling.</p>

            </div>

            <p class="intro-footer">Een stap tegelijk. Met zicht op vooruitgang.</p>
            <span class="intro-decoration intro-decoration-one" aria-hidden="true"></span>
            <span class="intro-decoration intro-decoration-two" aria-hidden="true"></span>
        </section>

        <section class="login-form-side" aria-labelledby="login-title">
            <div class="login-form-wrap">
                <div class="login-mobile-brand" aria-hidden="true">
                    <span class="login-brand-mark">D</span>
                    <strong>DQAMA</strong>
                </div>

                <p class="form-eyebrow">Welkom terug</p>
                <h2 id="login-title">Log in op je account</h2>
                <p class="login-subtitle">Vul je gegevens in om verder te gaan.</p>

                <?php if($errors->any()): ?>
                    <div class="login-error" role="alert">
                        <strong>Inloggen is niet gelukt.</strong>
                        <span><?php echo e($errors->first('email')); ?></span>
                    </div>
                <?php endif; ?>

                <form method="POST" action="<?php echo e(url('/login')); ?>" class="login-form">
                    <?php echo csrf_field(); ?>

                    <label for="email">E-mailadres</label>
                    <input
                        id="email"
                        name="email"
                        type="email"
                        value="<?php echo e(old('email')); ?>"
                        placeholder="naam@school.nl"
                        autocomplete="username"
                        inputmode="email"
                        required
                        autofocus
                        aria-describedby="email-error"
                    >
                    <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                        <p id="email-error" class="field-error"><?php echo e($message); ?></p>
                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>

                    <div class="password-label-row">
                        <label for="password">Wachtwoord</label>
                    </div>
                    <input
                        id="password"
                        name="password"
                        type="password"
                        placeholder="Vul je wachtwoord in"
                        autocomplete="current-password"
                        required
                    >

                    <button class="login-submit" type="submit">
                        <span>Inloggen</span>
                        <span class="submit-arrow" aria-hidden="true">&#8594;</span>
                    </button>
                </form>

                <p class="secure-note"><span aria-hidden="true">&#9679;</span> Je gegevens worden veilig verwerkt</p>
            </div>
        </section>
    </main>
</body>
</html>
<?php /**PATH C:\xampp\htdocs\the_better_DQAMA\dashboard\resources\views/home.blade.php ENDPATH**/ ?>